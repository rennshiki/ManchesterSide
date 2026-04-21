<?php
/**
 * Manchester Side - News Detail Page with Reactions
 */
require_once 'includes/config.php';

$db = getDB();

// Get article slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    redirect('index.php');
}

// Get article details
$stmt = $db->prepare("SELECT 
    a.id, a.title, a.slug, a.content, a.excerpt, a.image_url, a.category, a.views, a.published_at,
    a.reaction_like, a.reaction_love, a.reaction_wow, a.reaction_sad, a.reaction_angry, a.total_reactions,
    c.name as club_name, c.code as club_code, c.color_primary, c.color_secondary,
    ad.full_name as author_name
FROM articles a
LEFT JOIN clubs c ON a.club_id = c.id
JOIN admins ad ON a.author_id = ad.id
WHERE a.slug = ? AND a.is_published = 1");

$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('index.php');
}

$article = $result->fetch_assoc();

// Update views count
$update_views = $db->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
$update_views->bind_param("i", $article['id']);
$update_views->execute();

// Check if user has favorited this article
$is_favorited = false;
$user_reaction = null;
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $fav_check = $db->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND article_id = ?");
    $fav_check->bind_param("ii", $user_id, $article['id']);
    $fav_check->execute();
    $is_favorited = $fav_check->get_result()->num_rows > 0;
    
    // Get user's reaction
    $reaction_check = $db->prepare("SELECT reaction_type FROM article_reactions WHERE user_id = ? AND article_id = ?");
    $reaction_check->bind_param("ii", $user_id, $article['id']);
    $reaction_check->execute();
    $reaction_result = $reaction_check->get_result();
    if ($reaction_result->num_rows > 0) {
        $user_reaction = $reaction_result->fetch_assoc()['reaction_type'];
    }
}

// Handle favorite toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_favorite'])) {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Silakan login untuk menyimpan favorit');
        redirect('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
    
    $user_id = $_SESSION['user_id'];
    $article_id = $article['id'];
    
    if ($is_favorited) {
        $stmt = $db->prepare("DELETE FROM user_favorites WHERE user_id = ? AND article_id = ?");
        $stmt->bind_param("ii", $user_id, $article_id);
        $stmt->execute();
        setFlashMessage('success', 'Berita dihapus dari favorit');
    } else {
        $stmt = $db->prepare("INSERT INTO user_favorites (user_id, article_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $article_id);
        $stmt->execute();
        setFlashMessage('success', 'Berita ditambahkan ke favorit');
    }
    
    redirect($_SERVER['REQUEST_URI']);
}

// Handle reaction submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reaction'])) {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Login required']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $reaction_type = $_POST['reaction_type'] ?? '';
    $article_id = $article['id'];
    
    $valid_reactions = ['like', 'love', 'wow', 'sad', 'angry'];
    if (!in_array($reaction_type, $valid_reactions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid reaction']);
        exit;
    }
    
    // Check if user already reacted
    $check = $db->prepare("SELECT id, reaction_type FROM article_reactions WHERE user_id = ? AND article_id = ?");
    $check->bind_param("ii", $user_id, $article_id);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    
    if ($existing) {
        if ($existing['reaction_type'] === $reaction_type) {
            // Remove reaction if same
            $stmt = $db->prepare("DELETE FROM article_reactions WHERE user_id = ? AND article_id = ?");
            $stmt->bind_param("ii", $user_id, $article_id);
            $stmt->execute();
            $message = 'Reaksi dihapus';
            $new_reaction = null;
        } else {
            // Update to new reaction
            $stmt = $db->prepare("UPDATE article_reactions SET reaction_type = ?, updated_at = NOW() WHERE user_id = ? AND article_id = ?");
            $stmt->bind_param("sii", $reaction_type, $user_id, $article_id);
            $stmt->execute();
            $message = 'Reaksi diubah';
            $new_reaction = $reaction_type;
        }
    } else {
        // Add new reaction
        $stmt = $db->prepare("INSERT INTO article_reactions (user_id, article_id, reaction_type) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $article_id, $reaction_type);
        $stmt->execute();
        $message = 'Reaksi ditambahkan';
        $new_reaction = $reaction_type;
    }
    
    // Get updated counts
    $counts_query = $db->prepare("SELECT reaction_like, reaction_love, reaction_wow, reaction_sad, reaction_angry, total_reactions FROM articles WHERE id = ?");
    $counts_query->bind_param("i", $article_id);
    $counts_query->execute();
    $counts = $counts_query->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'user_reaction' => $new_reaction,
        'counts' => $counts
    ]);
    exit;
}

// Get related articles (same club)
if ($article['club_code']) {
    $club_id = ($article['club_code'] === 'CITY') ? 1 : 2;
    $related_query = $db->prepare("SELECT 
        a.id, a.title, a.slug, a.image_url, a.published_at,
        c.code as club_code
    FROM articles a
    LEFT JOIN clubs c ON a.club_id = c.id
    WHERE a.is_published = 1 AND a.id != ? AND a.club_id = ?
    ORDER BY a.published_at DESC
    LIMIT 3");
    $related_query->bind_param("ii", $article['id'], $club_id);
    $related_query->execute();
    $related_result = $related_query->get_result();
} else {
    // If article has no club, get general articles
    $related_query = $db->prepare("SELECT 
        a.id, a.title, a.slug, a.image_url, a.published_at,
        c.code as club_code
    FROM articles a
    LEFT JOIN clubs c ON a.club_id = c.id
    WHERE a.is_published = 1 AND a.id != ?
    ORDER BY a.published_at DESC
    LIMIT 3");
    $related_query->bind_param("i", $article['id']);
    $related_query->execute();
    $related_result = $related_query->get_result();
}

$current_user = getCurrentUser();
$flash = getFlashMessage();

// Reaction emoji mapping
$reaction_emojis = [
    'like' => '👍',
    'love' => '❤️',
    'wow' => '😮',
    'sad' => '😢',
    'angry' => '😠'
];
?>
<?php
$page_title = $article['title'];
$page_description = $article['excerpt'];
include 'includes/header.php';
?>

<style>
    .article-content p {
        margin-bottom: 1rem;
        line-height: 1.8;
    }
    
    .article-content h2 {
        font-size: 1.5rem;
        font-weight: bold;
        margin-top: 2rem;
        margin-bottom: 1rem;
    }
    
    .article-content h3 {
        font-size: 1.25rem;
        font-weight: bold;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
    }
    
    .reaction-btn {
        transition: all 0.3s ease;
    }
    
    .reaction-btn:hover {
        transform: scale(1.2);
    }
    
    .reaction-btn.active {
        transform: scale(1.3);
        filter: drop-shadow(0 0 8px rgba(0,0,0,0.3));
    }
</style>

    <?php if ($flash): ?>
        <div class="max-w-4xl mx-auto px-4 mt-4">
            <div class="bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-50 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-200 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-800 px-4 py-3 rounded-lg">
                <?php echo $flash['message']; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Article Content -->
    <article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        <!-- Breadcrumb -->
        <nav class="mb-6 text-sm">
            <a href="index.php" class="text-gray-600 hover:text-city-blue">Beranda</a>
            <span class="text-gray-400 mx-2">/</span>
            <a href="news.php" class="text-gray-600 hover:text-city-blue">Berita</a>
            <span class="text-gray-400 mx-2">/</span>
            <span class="text-gray-900 font-semibold"><?php echo $article['club_name'] ?? 'Umum'; ?></span>
        </nav>

        <!-- Article Header -->
        <header class="mb-8">
            <?php if ($article['club_code']): ?>
                <div class="mb-4">
                    <span class="inline-block px-4 py-2 bg-gradient-to-r from-<?php echo $article['club_code'] === 'CITY' ? 'city-blue' : 'united-red'; ?>-500 to-<?php echo $article['club_code'] === 'CITY' ? 'city-navy' : 'red'; ?>-900 text-white rounded-full text-sm font-bold">
                        <?php echo getClubEmoji($article['club_code']); ?> <?php echo strtoupper($article['club_name']); ?>
                    </span>
                    <span class="ml-3 text-gray-500 text-sm uppercase font-semibold"><?php echo $article['category']; ?></span>
                </div>
            <?php endif; ?>
            
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4 leading-tight">
                <?php echo $article['title']; ?>
            </h1>
            
            <div class="flex items-center justify-between py-4 border-y border-gray-200">
                <div class="flex items-center space-x-4 text-gray-600">
                    <div class="flex items-center space-x-2">
                        <span class="text-lg">✍️</span>
                        <span class="font-semibold"><?php echo $article['author_name']; ?></span>
                    </div>
                    <span class="text-gray-400">•</span>
                    <div class="flex items-center space-x-2">
                        <span class="text-lg">📅</span>
                        <span><?php echo formatDateIndo($article['published_at']); ?></span>
                    </div>
                    <span class="text-gray-400">•</span>
                    <div class="flex items-center space-x-2">
                        <span class="text-lg">👁️</span>
                        <span><?php echo formatNumber($article['views']); ?> views</span>
                    </div>
                </div>
                
                <!-- Favorite Button -->
                <form method="POST" action="">
                    <button type="submit" name="toggle_favorite" class="flex items-center space-x-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                        <span class="text-xl"><?php echo $is_favorited ? '❤️' : '🤍'; ?></span>
                        <span class="font-semibold"><?php echo $is_favorited ? 'Tersimpan' : 'Simpan'; ?></span>
                    </button>
                </form>
            </div>
        </header>

        <!-- Featured Image -->
        <div class="mb-8">
            <div class="aspect-w-16 aspect-h-9 bg-gray-200 rounded-xl overflow-hidden shadow-xl">
                <img src="<?php echo getArticleImage($article['image_url'], $article['club_code']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="w-full h-full object-cover">
            </div>
        </div>

        <!-- Article Excerpt -->
        <?php if ($article['excerpt']): ?>
            <div class="bg-gray-100 border-l-4 border-<?php echo $article['club_code'] === 'CITY' ? 'city-blue' : 'united-red'; ?> p-6 mb-8 rounded-r-lg">
                <p class="text-lg text-gray-700 italic leading-relaxed">
                    <?php echo cleanContent($article['excerpt']); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Article Body -->
        <div class="article-content prose prose-lg max-w-none text-gray-800">
            <?php echo nl2br(cleanContent($article['content'])); ?>
        </div>

        <!-- Reactions Section -->
        <div class="mt-12 pt-8 border-t-2 border-gray-200">
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 text-center">
                    Bagaimana reaksi Anda terhadap berita ini?
                </h3>
                
                <!-- Reaction Buttons -->
                <div id="reaction-buttons" class="flex justify-center items-center gap-6 mb-8">
                    <?php foreach ($reaction_emojis as $type => $emoji): ?>
                        <button 
                            onclick="handleReaction('<?php echo $type; ?>')"
                            class="reaction-btn flex flex-col items-center group <?php echo $user_reaction === $type ? 'active' : ''; ?>"
                            data-reaction="<?php echo $type; ?>"
                        >
                            <div class="text-5xl mb-2 transition-transform <?php echo $user_reaction === $type ? 'scale-125' : ''; ?>">
                                <?php echo $emoji; ?>
                            </div>
                            <span class="text-sm font-semibold text-gray-600 group-hover:text-gray-900 capitalize">
                                <?php echo ucfirst($type); ?>
                            </span>
                            <span class="reaction-count text-xs text-gray-500 font-bold" data-type="<?php echo $type; ?>">
                                <?php echo formatNumber($article['reaction_' . $type]); ?>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Total Reactions -->
                <div class="text-center text-gray-600">
                    <p class="text-lg">
                        <span id="total-reactions" class="font-bold text-gray-900"><?php echo formatNumber($article['total_reactions']); ?></span> 
                        orang telah memberikan reaksi
                    </p>
                </div>

                <?php if (!isLoggedIn()): ?>
                    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                        <p class="text-gray-700 mb-3">Silakan login untuk memberikan reaksi</p>
                        <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="inline-block px-6 py-3 bg-city-blue text-white font-bold rounded-lg hover:bg-city-navy transition">
                            Login Sekarang
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Reaction Distribution Bar -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <p class="text-sm text-gray-600 mb-3 font-semibold text-center">Distribusi Reaksi</p>
                    <div class="flex h-8 rounded-full overflow-hidden shadow-md">
                        <?php 
                        $total = max(1, $article['total_reactions']);
                        foreach (['like', 'love', 'wow', 'sad', 'angry'] as $type):
                            $count = $article['reaction_' . $type];
                            $percentage = ($count / $total) * 100;
                            $colors = [
                                'like' => 'bg-blue-500',
                                'love' => 'bg-red-500',
                                'wow' => 'bg-yellow-500',
                                'sad' => 'bg-purple-500',
                                'angry' => 'bg-orange-500'
                            ];
                            if ($percentage > 0):
                        ?>
                            <div 
                                class="<?php echo $colors[$type]; ?> flex items-center justify-center text-white text-xs font-bold"
                                style="width: <?php echo $percentage; ?>%"
                                title="<?php echo ucfirst($type); ?>: <?php echo $count; ?>"
                            >
                                <?php if ($percentage > 15): ?>
                                    <?php echo $reaction_emojis[$type]; ?> <?php echo round($percentage, 1); ?>%
                                <?php endif; ?>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Share Buttons -->
        <div class="mt-12 pt-8 border-t border-gray-200">
            <p class="text-gray-600 font-semibold mb-4">Bagikan artikel ini:</p>
            <div class="flex space-x-3">
                <button class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                    📘 Facebook
                </button>
                <button class="px-6 py-3 bg-sky-500 text-white rounded-lg hover:bg-sky-600 transition font-semibold">
                    🐦 Twitter
                </button>
                <button class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                    💬 WhatsApp
                </button>
            </div>
        </div>

    </article>

    <!-- Related Articles -->
    <?php if ($related_result->num_rows > 0): ?>
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 bg-gray-100">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Berita Terkait</h2>
        <div class="grid md:grid-cols-3 gap-6">
            <?php while ($related = $related_result->fetch_assoc()): ?>
                <a href="news-detail.php?slug=<?php echo $related['slug']; ?>" class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition group">
                    <div class="h-48 bg-gray-200 overflow-hidden">
                        <img src="<?php echo getArticleImage($related['image_url'], $related['club_code']); ?>" alt="<?php echo htmlspecialchars($related['title']); ?>" class="w-full h-full object-cover">
                    </div>
                    <div class="p-5">
                        <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-city-blue transition">
                            <?php echo truncateText($related['title'], 70); ?>
                        </h3>
                        <p class="text-sm text-gray-500"><?php echo timeAgo($related['published_at']); ?></p>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    </section>
    <?php endif; ?>

<script>
        let userReaction = <?php echo $user_reaction ? "'".$user_reaction."'" : 'null'; ?>;
        const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
        const articleId = <?php echo $article['id']; ?>;

        function handleReaction(reactionType) {
            if (!isLoggedIn) {
                window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
                return;
            }

            // Send AJAX request
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'add_reaction=1&reaction_type=' + reactionType
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    updateReactionUI(data.user_reaction, data.counts);
                    
                    // Show toast notification
                    showToast(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Terjadi kesalahan', 'error');
            });
        }

        function updateReactionUI(newUserReaction, counts) {
            // Remove active class from all buttons
            document.querySelectorAll('.reaction-btn').forEach(btn => {
                btn.classList.remove('active');
                const emoji = btn.querySelector('div');
                emoji.classList.remove('scale-125');
            });

            // Add active class to current reaction
            if (newUserReaction) {
                const activeBtn = document.querySelector(`[data-reaction="${newUserReaction}"]`);
                if (activeBtn) {
                    activeBtn.classList.add('active');
                    const emoji = activeBtn.querySelector('div');
                    emoji.classList.add('scale-125');
                }
            }

            // Update counts
            document.querySelector('[data-type="like"]').textContent = formatNumber(counts.reaction_like);
            document.querySelector('[data-type="love"]').textContent = formatNumber(counts.reaction_love);
            document.querySelector('[data-type="wow"]').textContent = formatNumber(counts.reaction_wow);
            document.querySelector('[data-type="sad"]').textContent = formatNumber(counts.reaction_sad);
            document.querySelector('[data-type="angry"]').textContent = formatNumber(counts.reaction_angry);
            document.getElementById('total-reactions').textContent = formatNumber(counts.total_reactions);

            userReaction = newUserReaction;
        }

        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white font-semibold ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} z-50`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>

<?php include 'includes/footer.php'; ?>