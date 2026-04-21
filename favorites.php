<?php
/**
 * Manchester Side - User Favorites Page
 * Enhanced with sorting and bulk actions
 */
require_once 'includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('error', 'Silakan login untuk melihat berita favorit');
    redirect('login.php');
}

$db = getDB();
$user = getCurrentUser();

// Handle remove from favorites
if (isset($_GET['remove'])) {
    $article_id = (int)$_GET['remove'];
    $stmt = $db->prepare("DELETE FROM user_favorites WHERE user_id = ? AND article_id = ?");
    $stmt->bind_param("ii", $user['id'], $article_id);
    if ($stmt->execute()) {
        setFlashMessage('success', 'Berita berhasil dihapus dari favorit');
    } else {
        setFlashMessage('error', 'Gagal menghapus berita dari favorit');
    }
    redirect('favorites.php');
}

// Get sort parameter
$sort = $_GET['sort'] ?? 'newest'; // newest, oldest, club

// Determine ORDER BY
switch ($sort) {
    case 'oldest':
        $order_by = "uf.created_at ASC";
        break;
    case 'club':
        $order_by = "c.code ASC, uf.created_at DESC";
        break;
    default: // newest
        $order_by = "uf.created_at DESC";
}

// Get user's favorite articles
$favorites_query = $db->prepare("SELECT 
    a.id, a.title, a.slug, a.excerpt, a.image_url, a.category, a.views, a.published_at,
    c.name as club_name, c.code as club_code, c.color_primary,
    uf.created_at as favorited_at
FROM user_favorites uf
JOIN articles a ON uf.article_id = a.id
LEFT JOIN clubs c ON a.club_id = c.id
WHERE uf.user_id = ?
ORDER BY $order_by");

$favorites_query->bind_param("i", $user['id']);
$favorites_query->execute();
$favorites_result = $favorites_query->get_result();

// Collect articles for statistics
$favorites_array = [];
while ($row = $favorites_result->fetch_assoc()) {
    $favorites_array[] = $row;
}

// Calculate statistics
$stats = [
    'total' => count($favorites_array),
    'city' => 0,
    'united' => 0,
    'general' => 0,
    'categories' => []
];

foreach ($favorites_array as $article) {
    if ($article['club_code'] === 'CITY') {
        $stats['city']++;
    } elseif ($article['club_code'] === 'UNITED') {
        $stats['united']++;
    } else {
        $stats['general']++;
    }
    
    $category = $article['category'];
    if (!isset($stats['categories'][$category])) {
        $stats['categories'][$category] = 0;
    }
    $stats['categories'][$category]++;
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Favorit - Manchester Side</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'city-blue': '#6CABDD',
                        'city-navy': '#1C2C5B',
                        'united-red': '#DA291C',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
    </style>
</head>
<body class="bg-gray-50">

    <?php include 'includes/header.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <?php if ($flash): ?>
            <div class="mb-6 bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-50 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-200 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-800 px-4 py-3 rounded-lg">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">
                        ❤️ Berita Favorit Saya
                    </h1>
                    <p class="text-gray-600">
                        Kumpulan berita yang telah Anda simpan untuk dibaca nanti
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-5xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
                    <p class="text-sm text-gray-600">Total Favorit</p>
                </div>
            </div>
        </div>

        <?php if ($stats['total'] > 0): ?>
            
            <!-- Statistics Dashboard -->
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                
                <!-- City Stats -->
                <div class="bg-gradient-to-br from-city-blue to-city-navy text-white rounded-xl shadow-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <img src="https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg" alt="Man City" class="w-16 h-16 object-contain">
                        <span class="text-4xl font-bold"><?php echo $stats['city']; ?></span>
                    </div>
                    <p class="font-bold text-lg">Manchester City</p>
                    <p class="text-sm text-blue-100">Berita yang disimpan</p>
                    <?php if ($stats['total'] > 0): ?>
                        <div class="mt-3 pt-3 border-t border-white/20">
                            <p class="text-sm"><?php echo round(($stats['city'] / $stats['total']) * 100, 1); ?>% dari total favorit</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- United Stats -->
                <div class="bg-gradient-to-br from-united-red to-red-900 text-white rounded-xl shadow-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <img src="https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg" alt="Man United" class="w-16 h-16 object-contain">
                        <span class="text-4xl font-bold"><?php echo $stats['united']; ?></span>
                    </div>
                    <p class="font-bold text-lg">Manchester United</p>
                    <p class="text-sm text-red-100">Berita yang disimpan</p>
                    <?php if ($stats['total'] > 0): ?>
                        <div class="mt-3 pt-3 border-t border-white/20">
                            <p class="text-sm"><?php echo round(($stats['united'] / $stats['total']) * 100, 1); ?>% dari total favorit</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- General Stats -->
                <div class="bg-gradient-to-br from-gray-600 to-gray-900 text-white rounded-xl shadow-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-5xl">⚽</span>
                        <span class="text-4xl font-bold"><?php echo $stats['general']; ?></span>
                    </div>
                    <p class="font-bold text-lg">Berita Umum</p>
                    <p class="text-sm text-gray-300">Derby & Lainnya</p>
                    <?php if ($stats['total'] > 0): ?>
                        <div class="mt-3 pt-3 border-t border-white/20">
                            <p class="text-sm"><?php echo round(($stats['general'] / $stats['total']) * 100, 1); ?>% dari total favorit</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Sort Options -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-gray-900">📊 Urutkan Berdasarkan:</h3>
                    <div class="flex gap-2">
                        <a href="?sort=newest" class="px-4 py-2 <?php echo $sort === 'newest' ? 'bg-city-blue text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded-lg font-semibold transition text-sm">
                            🕐 Terbaru Disimpan
                        </a>
                        <a href="?sort=oldest" class="px-4 py-2 <?php echo $sort === 'oldest' ? 'bg-city-blue text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded-lg font-semibold transition text-sm">
                            ⏰ Terlama Disimpan
                        </a>
                        <a href="?sort=club" class="px-4 py-2 <?php echo $sort === 'club' ? 'bg-city-blue text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded-lg font-semibold transition text-sm">
                            ⚽ Berdasarkan Klub
                        </a>
                    </div>
                </div>
            </div>

            <!-- Favorites Grid -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <?php foreach ($favorites_array as $article): ?>
                    <div class="card-hover bg-white rounded-xl shadow-lg overflow-hidden group relative">
                        
                        <!-- Remove Button -->
                        <button 
                            onclick="confirmRemove(<?php echo $article['id']; ?>, '<?php echo addslashes($article['title']); ?>')"
                            class="absolute top-4 right-4 z-10 w-10 h-10 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center shadow-lg transition opacity-0 group-hover:opacity-100"
                            title="Hapus dari favorit"
                        >
                            <span class="text-xl">🗑️</span>
                        </button>

                        <!-- Favorited Badge -->
                        <div class="absolute top-4 left-4 z-10 px-3 py-1 bg-red-500 text-white rounded-full text-xs font-bold shadow-lg">
                            ❤️ FAVORIT
                        </div>

                        <a href="news-detail.php?slug=<?php echo $article['slug']; ?>" class="block">
                            <!-- Image -->
                            <div class="h-48 bg-gray-200 overflow-hidden">
                                <img src="<?php echo getArticleImage($article['image_url'], $article['club_code']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="w-full h-full object-cover">
                            </div>

                            <!-- Content -->
                            <div class="p-5">
                                <!-- Club Badge & Category -->
                                <div class="flex items-center gap-2 mb-3">
                                    <?php if ($article['club_code']): ?>
                                        <span class="px-3 py-1 bg-gradient-to-r from-<?php echo $article['club_code'] === 'CITY' ? 'city-blue' : 'united-red'; ?>-500 to-<?php echo $article['club_code'] === 'CITY' ? 'city-navy' : 'red'; ?>-900 text-white rounded-full text-xs font-bold">
                                            <?php echo getClubEmoji($article['club_code']); ?> <?php echo strtoupper($article['club_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-semibold">
                                        <?php echo ucfirst($article['category']); ?>
                                    </span>
                                </div>

                                <!-- Title -->
                                <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-city-blue transition line-clamp-2">
                                    <?php echo $article['title']; ?>
                                </h3>

                                <!-- Excerpt -->
                                <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                                    <?php echo truncateText($article['excerpt'], 100); ?>
                                </p>

                                <!-- Meta Info -->
                                <div class="flex items-center justify-between text-xs text-gray-500 pt-3 border-t border-gray-200">
                                    <span>📅 <?php echo formatDateIndo($article['published_at']); ?></span>
                                    <span>👁️ <?php echo formatNumber($article['views']); ?></span>
                                </div>

                                <!-- Favorited Date -->
                                <div class="mt-2 text-xs text-gray-400 flex items-center">
                                    <span class="mr-1">❤️</span>
                                    Disimpan <?php echo timeAgo($article['favorited_at']); ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Category Distribution -->
            <?php if (!empty($stats['categories'])): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h3 class="font-bold text-gray-900 mb-4">📊 Distribusi Kategori Favorit</h3>
                <div class="grid md:grid-cols-5 gap-4">
                    <?php
                    $category_icons = [
                        'news' => '📰',
                        'match' => '⚽',
                        'transfer' => '💼',
                        'interview' => '🎤',
                        'analysis' => '📊'
                    ];
                    
                    foreach ($stats['categories'] as $category => $count):
                    ?>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-3xl mb-2"><?php echo $category_icons[$category] ?? '📄'; ?></div>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $count; ?></p>
                            <p class="text-xs text-gray-600"><?php echo ucfirst($category); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            
            <!-- Empty State -->
            <div class="text-center py-20">
                <div class="inline-block p-8 bg-white rounded-full shadow-xl mb-6">
                    <span class="text-8xl">❤️</span>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-4">
                    Belum Ada Berita Favorit
                </h2>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                    Anda belum menyimpan berita apapun sebagai favorit. Mulai jelajahi berita dan simpan artikel favorit Anda!
                </p>
                <div class="flex justify-center gap-4">
                    <a href="index.php" class="px-8 py-4 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition">
                        📰 Jelajahi Berita
                    </a>
                    <a href="news.php" class="px-8 py-4 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition">
                        🔍 Semua Berita
                    </a>
                </div>
            </div>

            <!-- How to Save Tips -->
            <div class="max-w-2xl mx-auto mt-12 bg-blue-50 border border-blue-200 rounded-xl p-6">
                <h3 class="font-bold text-blue-900 mb-3 text-lg">💡 Cara Menyimpan Berita Favorit:</h3>
                <ol class="space-y-2 text-blue-800 text-sm">
                    <li class="flex items-start">
                        <span class="font-bold mr-2 flex-shrink-0">1.</span>
                        <span>Buka halaman berita yang ingin Anda simpan</span>
                    </li>
                    <li class="flex items-start">
                        <span class="font-bold mr-2 flex-shrink-0">2.</span>
                        <span>Klik tombol <strong>"Simpan"</strong> atau ikon ❤️ di bagian atas artikel</span>
                    </li>
                    <li class="flex items-start">
                        <span class="font-bold mr-2 flex-shrink-0">3.</span>
                        <span>Berita akan otomatis tersimpan dan bisa Anda akses kapan saja di halaman ini</span>
                    </li>
                    <li class="flex items-start">
                        <span class="font-bold mr-2 flex-shrink-0">4.</span>
                        <span>Untuk menghapus, hover di card berita dan klik tombol 🗑️</span>
                    </li>
                </ol>
            </div>

        <?php endif; ?>

    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        function confirmRemove(articleId, title) {
            const confirmed = confirm(
                '❤️ Hapus dari Favorit?\n\n' +
                'Berita: ' + title + '\n\n' +
                'Anda yakin ingin menghapus berita ini dari favorit?'
            );
            
            if (confirmed) {
                window.location.href = '?remove=' + articleId;
            }
        }
    </script>

</body>
</html>