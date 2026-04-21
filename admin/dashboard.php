<?php
/**
 * Manchester Side - Admin Dashboard (FIXED NAVIGATION)
 */
require_once '../includes/config.php';

// Check admin authentication
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$db = getDB();
$admin = getCurrentAdmin();

// Get statistics
$stats = [];

// Total articles
$result = $db->query("SELECT COUNT(*) as total FROM articles");
$stats['total_articles'] = $result->fetch_assoc()['total'];

// Published articles
$result = $db->query("SELECT COUNT(*) as total FROM articles WHERE is_published = 1");
$stats['published_articles'] = $result->fetch_assoc()['total'];

// Draft articles
$result = $db->query("SELECT COUNT(*) as total FROM articles WHERE is_published = 0");
$stats['draft_articles'] = $result->fetch_assoc()['total'];

// Total users
$result = $db->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $result->fetch_assoc()['total'];

// Total reactions
$result = $db->query("SELECT COUNT(*) as total FROM article_reactions");
$stats['total_reactions'] = $result->fetch_assoc()['total'];

// Total players
$result = $db->query("SELECT COUNT(*) as total FROM players");
$stats['total_players'] = $result->fetch_assoc()['total'];

// Total views
$result = $db->query("SELECT SUM(views) as total FROM articles");
$stats['total_views'] = $result->fetch_assoc()['total'] ?? 0;

// Total matches/schedule
$result = $db->query("SELECT COUNT(*) as total FROM matches");
$stats['total_matches'] = $result->fetch_assoc()['total'];

// Upcoming matches
$result = $db->query("SELECT COUNT(*) as total FROM matches WHERE match_date > NOW()");
$stats['upcoming_matches'] = $result->fetch_assoc()['total'];

// City vs United article count - ONLY Manchester City and Manchester United
$result = $db->query("SELECT c.name, c.code, COUNT(a.id) as count FROM clubs c LEFT JOIN articles a ON c.id = a.club_id WHERE c.code IN ('CITY', 'UNITED') GROUP BY c.id");
$club_stats = [];
while ($row = $result->fetch_assoc()) {
    $club_stats[$row['code']] = $row;
}

// Recent articles
$recent_articles = $db->query("SELECT 
    a.id, a.title, a.slug, a.is_published, a.views, a.created_at,
    c.name as club_name, c.code as club_code
FROM articles a
LEFT JOIN clubs c ON a.club_id = c.id
ORDER BY a.created_at DESC LIMIT 5");

// Recent reactions
$recent_reactions = $db->query("SELECT 
    r.reaction_type, r.created_at,
    u.username,
    a.title as article_title, a.slug as article_slug
FROM article_reactions r
JOIN users u ON r.user_id = u.id
JOIN articles a ON r.article_id = a.id
ORDER BY r.created_at DESC LIMIT 5");

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="flex h-screen">
        
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-white flex flex-col">
            <!-- Logo -->
            <div class="p-6 border-b border-gray-800">
                <div class="flex items-center space-x-3">
                    <div class="flex">
                        <div class="w-8 h-8 bg-city-blue rounded-full"></div>
                        <div class="w-8 h-8 bg-united-red rounded-full -ml-3"></div>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold">Admin Panel</h1>
                        <p class="text-xs text-gray-400">Two Sides, One City,</p>
                        <p class="text-xs text-gray-400">Endless Rivalry</p>
                    </div>
                </div>
            </div>

            <!-- Navigation - FIXED LINKS -->
            <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
                <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 bg-city-blue rounded-lg text-white font-semibold">
                    <span class="text-xl">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="article/index.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">📰</span>
                    <span>Berita</span>
                </a>
                <a href="profil-klub/index.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">🏆</span>
                    <span>Profil Klub</span>
                </a>
                <a href="players/index.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">👥</span>
                    <span>Pemain</span>
                </a>
                <a href="staff/index.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">🎯</span>
                    <span>Staff Kepelatihan</span>
                </a>
                <a href="schedule/index.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">📅</span>
                    <span>Jadwal & Hasil</span>
                </a>
                <a href="users/index.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">👤</span>
                    <span>Users</span>
                </a>
                <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">⚙️</span>
                    <span>Settings</span>
                </a>
            </nav>

            <!-- User Info -->
            <div class="p-4 border-t border-gray-800">
                <div class="flex items-center space-x-3 mb-3">
                    <?php if (!empty($admin['photo_url']) && file_exists('../' . $admin['photo_url'])): ?>
                        <img src="../<?php echo $admin['photo_url']; ?>" 
                             alt="<?php echo $admin['full_name']; ?>" 
                             class="w-10 h-10 rounded-full object-cover border-2 border-city-blue">
                    <?php else: ?>
                        <div class="w-10 h-10 bg-gradient-to-r from-city-blue to-united-red rounded-full flex items-center justify-center text-white font-bold">
                            <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <p class="font-semibold text-sm"><?php echo $admin['full_name']; ?></p>
                        <p class="text-xs text-gray-400"><?php echo ucfirst($admin['role']); ?></p>
                    </div>
                </div>
                <a href="../index.php" target="_blank" class="block w-full text-center px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-lg text-sm font-semibold transition mb-2">
                    👁️ View Site
                </a>
                <a href="logout.php" class="block w-full text-center px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold transition">
                    🚪 Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Dashboard Overview</h1>
                        <p class="text-gray-600 mt-1">Selamat datang kembali, <?php echo $admin['full_name']; ?>!</p>
                    </div>
                    <div class="text-right text-sm text-gray-600">
                        <p class="font-semibold"><?php echo formatDateIndo(date('Y-m-d')); ?></p>
                        <p><?php echo date('H:i'); ?> WIB</p>
                    </div>
                </div>
            </header>

            <div class="p-6">

                <?php if ($flash): ?>
                    <div class="mb-6 bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-50 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-200 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-800 px-4 py-3 rounded-lg">
                        <?php echo $flash['message']; ?>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    
                    <!-- Total Articles -->
                    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-city-blue">
                        <div class="flex flex-col items-center justify-center mb-4">
                            <div class="text-4xl mb-2">📰</div>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $stats['total_articles']; ?></p>
                            <p class="text-sm text-gray-600 text-center">Total Berita</p>
                        </div>
                        <div class="text-xs text-gray-500 flex justify-between">
                            <span>✅ Published: <?php echo $stats['published_articles']; ?></span>
                            <span>📝 Draft: <?php echo $stats['draft_articles']; ?></span>
                        </div>
                    </div>

                    <!-- Total Users -->
                    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-united-red">
                        <div class="flex flex-col items-center justify-center mb-4">
                            <div class="text-4xl mb-2">👥</div>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $stats['total_users']; ?></p>
                            <p class="text-sm text-gray-600 text-center">Registered Users</p>
                        </div>
                        <a href="users/index.php" class="block text-center text-xs text-city-blue hover:underline font-semibold">
                            Manage Users →
                        </a>
                    </div>

                    <!-- Total Reactions -->
                    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                        <div class="flex flex-col items-center justify-center mb-4">
                            <div class="text-4xl mb-2">👍</div>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $stats['total_reactions']; ?></p>
                            <p class="text-sm text-gray-600 text-center">Total Reaksi</p>
                        </div>
                        <div class="text-xs text-green-600 font-semibold text-center">
                            ✅ User engagement metrics
                        </div>
                    </div>

                    <!-- Total Views -->
                    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                        <div class="flex flex-col items-center justify-center mb-4">
                            <div class="text-4xl mb-2">👁️</div>
                            <p class="text-3xl font-bold text-gray-900"><?php echo formatNumber($stats['total_views']); ?></p>
                            <p class="text-sm text-gray-600 text-center">Total Views</p>
                        </div>
                        <div class="text-xs text-gray-500 text-center">
                            📊 Rata-rata: <?php echo $stats['total_articles'] > 0 ? formatNumber($stats['total_views'] / $stats['total_articles']) : 0; ?> per artikel
                        </div>
                    </div>

                </div>

                <!-- Club Statistics -->
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    
                    <!-- Manchester City Stats -->
                    <div class="bg-gradient-to-br from-city-blue to-city-navy text-white rounded-xl shadow-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-2xl font-bold flex items-center gap-2">
                                    <img src="https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg" alt="Man City" class="w-8 h-8 object-contain">
                                    Manchester City
                                </h3>
                                <p class="text-blue-100 text-sm">Content Statistics</p>
                            </div>
                            <div class="text-5xl opacity-50">⚽</div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center pb-2 border-b border-white/20">
                                <span>Total Berita</span>
                                <span class="text-2xl font-bold"><?php echo $club_stats['CITY']['count'] ?? 0; ?></span>
                            </div>
                            <div class="flex justify-between items-center pb-2 border-b border-white/20">
                                <span>Total Pemain</span>
                                <span class="text-2xl font-bold"><?php echo $db->query("SELECT COUNT(*) as c FROM players WHERE club_id = 1")->fetch_assoc()['c']; ?></span>
                            </div>
                            <div class="flex justify-between items-center pb-2 border-b border-white/20">
                                <span>Staff</span>
                                <span class="text-2xl font-bold"><?php echo $db->query("SELECT COUNT(*) as c FROM staff WHERE club_id = 1")->fetch_assoc()['c']; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Piala</span>
                                <span class="text-2xl font-bold"><?php echo $db->query("SELECT COUNT(*) as c FROM club_trophies WHERE club_id = 1")->fetch_assoc()['c'] ?? 0; ?></span>
                            </div>
                        </div>
                        <a href="profil-klub/edit.php?id=1" class="mt-4 block w-full py-2 bg-white/20 hover:bg-white/30 rounded-lg text-center font-semibold transition">
                            ✏️ Edit Profil
                        </a>
                    </div>

                    <!-- Manchester United Stats -->
                    <div class="bg-gradient-to-br from-united-red to-red-900 text-white rounded-xl shadow-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-2xl font-bold flex items-center gap-2">
                                    <img src="https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg" alt="Man United" class="w-8 h-8 object-contain">
                                    Manchester United
                                </h3>
                                <p class="text-red-100 text-sm">Content Statistics</p>
                            </div>
                            <div class="text-5xl opacity-50">⚽</div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center pb-2 border-b border-white/20">
                                <span>Total Berita</span>
                                <span class="text-2xl font-bold"><?php echo $club_stats['UNITED']['count'] ?? 0; ?></span>
                            </div>
                            <div class="flex justify-between items-center pb-2 border-b border-white/20">
                                <span>Total Pemain</span>
                                <span class="text-2xl font-bold"><?php echo $db->query("SELECT COUNT(*) as c FROM players WHERE club_id = 2")->fetch_assoc()['c']; ?></span>
                            </div>
                            <div class="flex justify-between items-center pb-2 border-b border-white/20">
                                <span>Staff</span>
                                <span class="text-2xl font-bold"><?php echo $db->query("SELECT COUNT(*) as c FROM staff WHERE club_id = 2")->fetch_assoc()['c']; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Piala</span>
                                <span class="text-2xl font-bold"><?php echo $db->query("SELECT COUNT(*) as c FROM club_trophies WHERE club_id = 2")->fetch_assoc()['c'] ?? 0; ?></span>
                            </div>
                        </div>
                        <a href="profil-klub/edit.php?id=2" class="mt-4 block w-full py-2 bg-white/20 hover:bg-white/30 rounded-lg text-center font-semibold transition">
                            ✏️ Edit Profil
                        </a>
                    </div>

                </div>

                <!-- Recent Activity -->
                <div class="grid md:grid-cols-2 gap-6">
                    
                    <!-- Recent Articles -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-gray-900">📰 Berita Terbaru</h3>
                            <a href="article/index.php" class="text-sm text-city-blue hover:underline font-semibold">
                                Lihat Semua →
                            </a>
                        </div>
                        <div class="space-y-3">
                            <?php while ($article = $recent_articles->fetch_assoc()): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                    <div class="flex-1">
                                        <a href="article/edit.php?id=<?php echo $article['id']; ?>" class="font-semibold text-gray-900 hover:text-city-blue">
                                            <?php echo truncateText($article['title'], 50); ?>
                                        </a>
                                        <div class="flex items-center gap-2 mt-1 text-xs text-gray-500">
                                            <?php if ($article['club_code']): ?>
                                                <span class="px-2 py-0.5 bg-<?php echo $article['club_code'] === 'CITY' ? 'city-blue' : 'united-red'; ?> text-white rounded">
                                                    <?php echo $article['club_code']; ?>
                                                </span>
                                            <?php endif; ?>
                                            <span><?php echo timeAgo($article['created_at']); ?></span>
                                            <span>•</span>
                                            <span>👁️ <?php echo $article['views']; ?></span>
                                        </div>
                                    </div>
                                    <div>
                                        <?php if ($article['is_published']): ?>
                                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded font-semibold">Published</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded font-semibold">Draft</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Recent Reactions -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-gray-900">👍 Reaksi Terbaru</h3>
                        </div>
                        <div class="space-y-3">
                            <?php 
                            $reaction_emojis = [
                                'like' => '👍',
                                'love' => '❤️',
                                'wow' => '😮',
                                'sad' => '😢',
                                'angry' => '😠'
                            ];
                            while ($reaction = $recent_reactions->fetch_assoc()): 
                            ?>
                                <div class="p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-semibold text-gray-900">@<?php echo $reaction['username']; ?></span>
                                        <span class="text-2xl"><?php echo $reaction_emojis[$reaction['reaction_type']]; ?></span>
                                    </div>
                                    <p class="text-sm text-gray-700 mb-2"><?php echo truncateText($reaction['article_title'], 60); ?></p>
                                    <div class="text-xs text-gray-500">
                                        <?php echo timeAgo($reaction['created_at']); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                </div>

                <!-- Quick Actions -->
                <div class="mt-8 bg-gradient-to-r from-city-blue to-united-red rounded-xl shadow-xl p-6 text-white">
                    <h3 class="text-2xl font-bold mb-4">⚡ Quick Actions</h3>
                    <div class="grid md:grid-cols-5 gap-4">
                        <a href="article/create.php" class="bg-white/20 hover:bg-white/30 rounded-lg p-4 text-center transition backdrop-blur-sm">
                            <div class="text-3xl mb-2">➕</div>
                            <p class="font-semibold">Buat Berita Baru</p>
                        </a>
                        <a href="profil-klub/index.php" class="bg-white/20 hover:bg-white/30 rounded-lg p-4 text-center transition backdrop-blur-sm">
                            <div class="text-3xl mb-2">🏆</div>
                            <p class="font-semibold">Kelola Profil Klub</p>
                        </a>
                        <a href="players/create.php" class="bg-white/20 hover:bg-white/30 rounded-lg p-4 text-center transition backdrop-blur-sm">
                            <div class="text-3xl mb-2">👤</div>
                            <p class="font-semibold">Tambah Pemain</p>
                        </a>
                        <a href="staff/create.php" class="bg-white/20 hover:bg-white/30 rounded-lg p-4 text-center transition backdrop-blur-sm">
                            <div class="text-3xl mb-2">🎯</div>
                            <p class="font-semibold">Tambah Staff</p>
                        </a>
                        <a href="users/index.php" class="bg-white/20 hover:bg-white/30 rounded-lg p-4 text-center transition backdrop-blur-sm">
                            <div class="text-3xl mb-2">👥</div>
                            <p class="font-semibold">Manage Users</p>
                        </a>
                    </div>
                </div>

            </div>

        </main>

    </div>

</body>
</html>