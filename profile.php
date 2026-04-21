<?php
/**
 * Manchester Side - User Profile Page with Reaction Activity
 */
require_once 'includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$db = getDB();
$user = getCurrentUser();
$errors = [];
$success = false;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = lightSanitize($_POST['full_name'] ?? '');
    $favorite_team = lightSanitize($_POST['favorite_team'] ?? '');
    
    if (empty($full_name)) {
        $errors[] = 'Nama lengkap wajib diisi';
    }
    
    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, favorite_team = ? WHERE id = ?");
        $stmt->bind_param("ssi", $full_name, $favorite_team, $user['id']);
        
        if ($stmt->execute()) {
            $success = true;
            setFlashMessage('success', 'Profil berhasil diperbarui!');
            
            // Update session if favorite team changed
            $_SESSION['favorite_team'] = $favorite_team;
            
            // Refresh user data
            $user = getCurrentUser();
        } else {
            $errors[] = 'Gagal memperbarui profil';
        }
    }
}

// Get user statistics
$stats = [];

// Total favorites
$fav_result = $db->query("SELECT COUNT(*) as total FROM user_favorites WHERE user_id = " . $user['id']);
$stats['favorites'] = $fav_result->fetch_assoc()['total'];

// Total reactions
$reaction_result = $db->query("SELECT COUNT(*) as total FROM article_reactions WHERE user_id = " . $user['id']);
$stats['reactions'] = $reaction_result->fetch_assoc()['total'];

// Member since
$stats['member_since'] = formatDateIndo($user['created_at'] ?? date('Y-m-d'));

// Get recent reactions activity
$recent_reactions = $db->query("SELECT 
    r.reaction_type, r.created_at,
    a.title as article_title, a.slug as article_slug,
    c.code as club_code
FROM article_reactions r
JOIN articles a ON r.article_id = a.id
LEFT JOIN clubs c ON a.club_id = c.id
WHERE r.user_id = {$user['id']}
ORDER BY r.created_at DESC
LIMIT 10");

// Get reaction type distribution
$reaction_stats = $db->query("SELECT 
    reaction_type, 
    COUNT(*) as count 
FROM article_reactions 
WHERE user_id = {$user['id']}
GROUP BY reaction_type");

$reaction_distribution = [];
while ($row = $reaction_stats->fetch_assoc()) {
    $reaction_distribution[$row['reaction_type']] = $row['count'];
}

$flash = getFlashMessage();
$theme_color = $user['favorite_team'] ? getClubColor($user['favorite_team']) : '#6b7280';

$reaction_emojis = [
    'like' => '👍',
    'love' => '❤️',
    'wow' => '😮',
    'sad' => '😢',
    'angry' => '😠'
];

$page_title = "Profil - " . $user['username'];
include 'includes/header.php';
?>
    

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <?php if ($flash): ?>
            <div class="mb-6 bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-50 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-200 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-800 px-4 py-3 rounded-lg">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="grid lg:grid-cols-3 gap-8">
            
            <!-- Profile Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                    <!-- Profile Header with Team Color -->
                    <div class="h-32 bg-gradient-to-r from-<?php echo $user['favorite_team'] === 'CITY' ? 'city-blue' : ($user['favorite_team'] === 'UNITED' ? 'united-red' : 'gray'); ?>-500 to-<?php echo $user['favorite_team'] === 'CITY' ? 'city-navy' : ($user['favorite_team'] === 'UNITED' ? 'red' : 'gray'); ?>-900"></div>
                    
                    <div class="px-6 pb-6">
                        <!-- Avatar with Club Logo -->
                        <div class="flex justify-center -mt-16 mb-4">
                            <?php if ($user['favorite_team'] === 'CITY'): ?>
                                <div class="w-32 h-32 bg-white rounded-full flex items-center justify-center border-4 border-white shadow-xl p-4">
                                    <img src="https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg" alt="Man City" class="w-full h-full object-contain">
                                </div>
                            <?php elseif ($user['favorite_team'] === 'UNITED'): ?>
                                <div class="w-32 h-32 bg-white rounded-full flex items-center justify-center border-4 border-white shadow-xl p-4">
                                    <img src="https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg" alt="Man United" class="w-full h-full object-contain">
                                </div>
                            <?php else: ?>
                                <div class="w-32 h-32 bg-gradient-to-r from-city-blue to-united-red rounded-full flex items-center justify-center text-white text-5xl font-bold border-4 border-white shadow-xl">
                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- User Info -->
                        <div class="text-center mb-6">
                            <h1 class="text-2xl font-bold text-gray-900 mb-1"><?php echo $user['full_name'] ?? $user['username']; ?></h1>
                            <p class="text-gray-700 font-medium">@<?php echo $user['username']; ?></p>
                            <?php if ($user['favorite_team']): ?>
                                <div class="mt-3">
                                    <span class="inline-block px-4 py-2 bg-<?php echo $user['favorite_team'] === 'CITY' ? 'city-blue' : 'united-red'; ?> text-white rounded-full text-sm font-bold shadow-md">
                                        <?php echo $user['favorite_team'] === 'CITY' ? '🔵 Man City Fan' : '🔴 Man United Fan'; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Statistics -->
                        <div class="grid grid-cols-3 gap-4 py-4 border-t border-gray-200">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['favorites']; ?></p>
                                <p class="text-sm text-gray-700 font-medium">Favorit</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['reactions']; ?></p>
                                <p class="text-sm text-gray-700 font-medium">Reaksi</p>
                            </div>
                            <div class="text-center">
                                <?php if ($user['favorite_team'] === 'CITY'): ?>
                                    <div class="flex justify-center mb-1">
                                        <img src="https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg" alt="Man City" class="w-8 h-8 object-contain">
                                    </div>
                                <?php elseif ($user['favorite_team'] === 'UNITED'): ?>
                                    <div class="flex justify-center mb-1">
                                        <img src="https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg" alt="Man United" class="w-8 h-8 object-contain">
                                    </div>
                                <?php else: ?>
                                    <p class="text-2xl font-bold text-gray-900">👤</p>
                                <?php endif; ?>
                                <p class="text-sm text-gray-700 font-medium">Member</p>
                            </div>
                        </div>

                        <!-- Member Since -->
                        <div class="mt-4 text-center text-sm text-gray-700">
                            <p class="font-medium">📅 Bergabung sejak</p>
                            <p class="font-bold text-gray-900"><?php echo $stats['member_since']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="mt-6 bg-white rounded-xl shadow-lg p-6">
                    <h3 class="font-bold text-gray-900 mb-4">Menu Cepat</h3>
                    <div class="space-y-2">
                        <a href="favorites.php" class="block px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition">
                            <span class="text-xl mr-2">❤️</span>
                            <span class="font-semibold text-gray-900">Berita Favorit</span>
                        </a>
                        <a href="index.php" class="block px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition">
                            <span class="text-xl mr-2">📰</span>
                            <span class="font-semibold text-gray-900">Semua Berita</span>
                        </a>
                        <a href="logout.php" class="block px-4 py-3 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg transition">
                            <span class="text-xl mr-2">🚪</span>
                            <span class="font-semibold">Logout</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Edit Profile Form -->
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <span class="text-3xl mr-3">✏️</span>
                        Edit Profil
                    </h2>

                    <form method="POST" action="" class="space-y-6">
                        
                        <!-- Full Name -->
                        <div>
                            <label for="full_name" class="block text-sm font-bold text-gray-700 mb-2">
                                Nama Lengkap
                            </label>
                            <input 
                                type="text" 
                                id="full_name" 
                                name="full_name" 
                                value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                placeholder="Nama lengkap Anda"
                            >
                        </div>

                        <!-- Favorite Team -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-3">
                                Tim Favorit
                            </label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="relative cursor-pointer">
                                    <input 
                                        type="radio" 
                                        name="favorite_team" 
                                        value="CITY" 
                                        class="peer sr-only"
                                        <?php echo $user['favorite_team'] === 'CITY' ? 'checked' : ''; ?>
                                    >
                                    <div class="border-2 border-gray-300 peer-checked:border-city-blue peer-checked:bg-city-blue/10 rounded-xl p-6 text-center transition">
                                        <div class="flex justify-center mb-3">
                                            <img src="https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg" alt="Man City" class="w-20 h-20 object-contain">
                                        </div>
                                        <div class="font-bold text-gray-700 peer-checked:text-city-blue text-lg">Man City</div>
                                    </div>
                                </label>

                                <label class="relative cursor-pointer">
                                    <input 
                                        type="radio" 
                                        name="favorite_team" 
                                        value="UNITED" 
                                        class="peer sr-only"
                                        <?php echo $user['favorite_team'] === 'UNITED' ? 'checked' : ''; ?>
                                    >
                                    <div class="border-2 border-gray-300 peer-checked:border-united-red peer-checked:bg-united-red/10 rounded-xl p-6 text-center transition">
                                        <div class="flex justify-center mb-3">
                                            <img src="https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg" alt="Man United" class="w-20 h-20 object-contain">
                                        </div>
                                        <div class="font-bold text-gray-700 peer-checked:text-united-red text-lg">Man United</div>
                                    </div>
                                </label>
                            </div>
                            <p class="mt-3 text-sm text-gray-500">
                                💡 Tim favorit akan mengubah warna tema profil Anda
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex gap-3">
                            <button 
                                type="submit" 
                                name="update_profile"
                                class="flex-1 py-3 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition"
                            >
                                💾 Simpan Perubahan
                            </button>
                            <a 
                                href="profile.php"
                                class="px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition"
                            >
                                ❌ Batal
                            </a>
                        </div>

                    </form>
                </div>

                <!-- Reaction Statistics -->
                <?php if (!empty($reaction_distribution)): ?>
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <span class="text-3xl mr-3">📊</span>
                        Statistik Reaksi
                    </h2>

                    <div class="grid grid-cols-5 gap-4 mb-6">
                        <?php foreach ($reaction_emojis as $type => $emoji): ?>
                            <?php $count = $reaction_distribution[$type] ?? 0; ?>
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <div class="text-4xl mb-2"><?php echo $emoji; ?></div>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $count; ?></p>
                                <p class="text-xs text-gray-600 capitalize"><?php echo $type; ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Distribution Bar -->
                    <div class="flex h-8 rounded-full overflow-hidden shadow-md">
                        <?php 
                        $total = max(1, $stats['reactions']);
                        $colors = [
                            'like' => 'bg-blue-500',
                            'love' => 'bg-red-500',
                            'wow' => 'bg-yellow-500',
                            'sad' => 'bg-purple-500',
                            'angry' => 'bg-orange-500'
                        ];
                        foreach ($reaction_emojis as $type => $emoji):
                            $count = $reaction_distribution[$type] ?? 0;
                            $percentage = ($count / $total) * 100;
                            if ($percentage > 0):
                        ?>
                            <div 
                                class="<?php echo $colors[$type]; ?> flex items-center justify-center text-white text-xs font-bold"
                                style="width: <?php echo $percentage; ?>%"
                                title="<?php echo ucfirst($type); ?>: <?php echo $count; ?>"
                            >
                                <?php if ($percentage > 15): ?>
                                    <?php echo $emoji; ?> <?php echo round($percentage); ?>%
                                <?php endif; ?>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Reactions Activity -->
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <span class="text-3xl mr-3">🎯</span>
                        Aktivitas Reaksi Terbaru
                    </h2>

                    <?php if ($recent_reactions->num_rows > 0): ?>
                        <div class="space-y-4">
                            <?php while ($reaction = $recent_reactions->fetch_assoc()): ?>
                                <div class="flex items-start space-x-4 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                    <div class="text-3xl flex-shrink-0">
                                        <?php echo $reaction_emojis[$reaction['reaction_type']]; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-gray-700 mb-1">
                                            Anda memberikan reaksi <span class="font-bold capitalize"><?php echo $reaction['reaction_type']; ?></span> pada:
                                        </p>
                                        <a href="news-detail.php?slug=<?php echo $reaction['article_slug']; ?>" class="text-city-blue hover:underline font-semibold block truncate">
                                            <?php echo $reaction['article_title']; ?>
                                        </a>
                                        <div class="flex items-center mt-2 text-xs text-gray-500">
                                            <?php if ($reaction['club_code']): ?>
                                                <span class="mr-2"><?php echo getClubEmoji($reaction['club_code']); ?></span>
                                            <?php endif; ?>
                                            <span><?php echo timeAgo($reaction['created_at']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 text-gray-500">
                            <p class="text-6xl mb-4">🎯</p>
                            <p class="text-lg">Belum ada aktivitas reaksi</p>
                            <p class="text-sm mt-2">Mulai berikan reaksi pada berita untuk melihat aktivitas Anda</p>
                            <a href="index.php" class="inline-block mt-4 px-6 py-3 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition">
                                Jelajahi Berita
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>

    </main>

    <!-- Footer -->
    

<?php include 'includes/footer.php'; ?>
