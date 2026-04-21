<?php
/**
 * Manchester Side - Admin Settings
 */
require_once '../includes/config.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$db = getDB();
$admin = getCurrentAdmin();
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings_to_update = [
        'site_name' => lightSanitize($_POST['site_name'] ?? ''),
        'site_tagline' => lightSanitize($_POST['site_tagline'] ?? ''),
        'site_description' => lightSanitize($_POST['site_description'] ?? ''),
        'site_email' => lightSanitize($_POST['site_email'] ?? ''),
        'articles_per_page' => (int)($_POST['articles_per_page'] ?? 12),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
    ];
    
    // Validation
    if (empty($settings_to_update['site_name'])) {
        $errors[] = 'Nama situs wajib diisi';
    }
    
    if (!filter_var($settings_to_update['site_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid';
    }
    
    if ($settings_to_update['articles_per_page'] < 6 || $settings_to_update['articles_per_page'] > 50) {
        $errors[] = 'Artikel per halaman harus antara 6-50';
    }
    
    if (empty($errors)) {
        $update_stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        
        foreach ($settings_to_update as $key => $value) {
            $update_stmt->bind_param("ss", $value, $key);
            $update_stmt->execute();
        }
        
        setFlashMessage('success', 'Pengaturan berhasil disimpan!');
        redirect('settings.php');
    }
}

// Get current settings
$settings_query = $db->query("SELECT * FROM settings");
$current_settings = [];
while ($row = $settings_query->fetch_assoc()) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}

// Get website statistics
$stats = [];
$stats['total_articles'] = $db->query("SELECT COUNT(*) as c FROM articles")->fetch_assoc()['c'];
$stats['published_articles'] = $db->query("SELECT COUNT(*) as c FROM articles WHERE is_published = 1")->fetch_assoc()['c'];
$stats['total_users'] = $db->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$stats['total_reactions'] = $db->query("SELECT COUNT(*) as c FROM article_reactions")->fetch_assoc()['c'];
$stats['total_players'] = $db->query("SELECT COUNT(*) as c FROM players")->fetch_assoc()['c'];
$stats['total_staff'] = $db->query("SELECT COUNT(*) as c FROM staff")->fetch_assoc()['c'];
$stats['total_matches'] = $db->query("SELECT COUNT(*) as c FROM matches")->fetch_assoc()['c'];
$stats['total_views'] = $db->query("SELECT SUM(views) as total FROM articles")->fetch_assoc()['total'] ?? 0;

// Get database size (approximate)
$db_size_query = $db->query("SELECT 
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
FROM information_schema.TABLES 
WHERE table_schema = '" . DB_NAME . "'");
$db_size = $db_size_query->fetch_assoc()['size_mb'] ?? 0;

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
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

            <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
                <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition">
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
                <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 bg-city-blue rounded-lg text-white font-semibold">
                    <span class="text-xl">⚙️</span>
                    <span>Settings</span>
                </a>
            </nav>

            <div class="p-4 border-t border-gray-800">
                <div class="flex items-center space-x-3 mb-3">
                    <?php if (!empty($admin['photo_url']) && file_exists('../' . $admin['photo_url'])): ?>
                        <div class="relative">
                            <img src="../<?php echo $admin['photo_url']; ?>" 
                                 alt="<?php echo $admin['full_name']; ?>" 
                                 class="w-10 h-10 rounded-full object-cover border-2 border-city-blue">
                            <div class="absolute inset-0 rounded-full border border-white/30 pointer-events-none"></div>
                        </div>
                    <?php else: ?>
                        <div class="relative">
                            <div class="w-10 h-10 bg-gradient-to-r from-city-blue to-united-red rounded-full flex items-center justify-center text-white font-bold">
                                <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                            </div>
                            <div class="absolute inset-0 rounded-full border border-white/30 pointer-events-none"></div>
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
                        <h1 class="text-3xl font-bold text-gray-900">Settings</h1>
                        <p class="text-gray-600 mt-1">Konfigurasi website Manchester Side</p>
                    </div>
                </div>
            </header>

            <div class="p-6">

                <?php if ($flash): ?>
                    <div class="mb-6 bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-50 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-200 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-800 px-4 py-3 rounded-lg">
                        <?php echo $flash['message']; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                        <p class="font-semibold mb-2">❌ Terjadi kesalahan:</p>
                        <ul class="list-disc list-inside text-sm space-y-1">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="grid lg:grid-cols-3 gap-6">
                    
                    <!-- Main Settings Form -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <form method="POST" action="" class="space-y-6">
                            
                            <!-- Site Information -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                    <span class="text-2xl mr-2">🌐</span>
                                    Informasi Website
                                </h3>
                                
                                <div class="space-y-4">
                                    <!-- Site Name -->
                                    <div>
                                        <label for="site_name" class="block text-sm font-bold text-gray-700 mb-2">
                                            Nama Website
                                        </label>
                                        <input 
                                            type="text" 
                                            id="site_name" 
                                            name="site_name" 
                                            value="<?php echo htmlspecialchars($current_settings['site_name'] ?? SITE_NAME); ?>"
                                            required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                        >
                                    </div>

                                    <!-- Site Tagline -->
                                    <div>
                                        <label for="site_tagline" class="block text-sm font-bold text-gray-700 mb-2">
                                            Tagline
                                        </label>
                                        <input 
                                            type="text" 
                                            id="site_tagline" 
                                            name="site_tagline" 
                                            value="<?php echo htmlspecialchars($current_settings['site_tagline'] ?? SITE_TAGLINE); ?>"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                        >
                                    </div>

                                    <!-- Site Email -->
                                    <div>
                                        <label for="site_email" class="block text-sm font-bold text-gray-700 mb-2">
                                            Email Kontak
                                        </label>
                                        <input 
                                            type="email" 
                                            id="site_email" 
                                            name="site_email" 
                                            value="<?php echo htmlspecialchars($current_settings['site_email'] ?? SITE_EMAIL); ?>"
                                            required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Content Settings -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                    <span class="text-2xl mr-2">📄</span>
                                    Pengaturan Konten
                                </h3>
                                
                                <div class="space-y-4">
                                    <!-- Articles Per Page -->
                                    <div>
                                        <label for="articles_per_page" class="block text-sm font-bold text-gray-700 mb-2">
                                            Artikel Per Halaman
                                        </label>
                                        <input 
                                            type="number" 
                                            id="articles_per_page" 
                                            name="articles_per_page" 
                                            value="<?php echo $current_settings['articles_per_page'] ?? ARTICLES_PER_PAGE; ?>"
                                            min="6"
                                            max="50"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                        >
                                        <p class="mt-2 text-xs text-gray-500">Jumlah artikel yang ditampilkan per halaman (6-50)</p>
                                    </div>
                                </div>
                            </div>

                    
                            <!-- Submit Button -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <button 
                                    type="submit"
                                    class="w-full py-3 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition"
                                >
                                    💾 Simpan Pengaturan
                                </button>
                            </div>

                        </form>

                    </div>

                    <!-- Sidebar Info -->
                    <div class="space-y-6">
                        
                        <!-- Website Statistics -->
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                                <span class="text-xl mr-2">📊</span>
                                Statistik Website
                            </h3>
                            
                            <div class="space-y-3">
                                <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Total Artikel</span>
                                    <span class="font-bold text-gray-900"><?php echo formatNumber($stats['total_articles']); ?></span>
                                </div>
                                <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Artikel Published</span>
                                    <span class="font-bold text-gray-900"><?php echo formatNumber($stats['published_articles']); ?></span>
                                </div>
                                <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Total Users</span>
                                    <span class="font-bold text-gray-900"><?php echo formatNumber($stats['total_users']); ?></span>
                                </div>
                                <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Total Reaksi</span>
                                    <span class="font-bold text-gray-900"><?php echo formatNumber($stats['total_reactions']); ?></span>
                                </div>
                                <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Total Jadwal</span>
                                    <span class="font-bold text-gray-900"><?php echo formatNumber($stats['total_matches']); ?></span>
                                </div>
                                <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Total Pemain</span>
                                    <span class="font-bold text-gray-900"><?php echo formatNumber($stats['total_players']); ?></span>
                                </div>
                                <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Total Staff</span>
                                    <span class="font-bold text-gray-900"><?php echo formatNumber($stats['total_staff']); ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Total Views</span>
                                    <span class="font-bold text-gray-900"><?php echo formatNumber($stats['total_views']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- System Info -->
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                                <span class="text-xl mr-2">💻</span>
                                Informasi Sistem
                            </h3>
                            
                            <div class="space-y-3 text-sm">
                                <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                                    <span class="text-gray-600">PHP Version</span>
                                    <span class="font-semibold text-gray-900"><?php echo phpversion(); ?></span>
                                </div>
                                <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                                    <span class="text-gray-600">Database</span>
                                    <span class="font-semibold text-gray-900">MySQL</span>
                                </div>
                                <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                                    <span class="text-gray-600">DB Size</span>
                                    <span class="font-semibold text-gray-900"><?php echo $db_size; ?> MB</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Server Time</span>
                                    <span class="font-semibold text-gray-900"><?php echo date('H:i:s'); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="bg-gradient-to-br from-purple-600 to-purple-900 text-white rounded-xl shadow-lg p-6">
                            <h3 class="font-bold mb-4 flex items-center">
                                <span class="text-xl mr-2">⚡</span>
                                Quick Actions
                            </h3>
                            
                            <div class="space-y-2">
                                <a href="dashboard.php" class="block w-full py-2 px-4 bg-white/20 hover:bg-white/30 rounded-lg text-center font-semibold transition">
                                    📊 Dashboard
                                </a>
                                <a href="article/create.php" class="block w-full py-2 px-4 bg-white/20 hover:bg-white/30 rounded-lg text-center font-semibold transition">
                                    ➕ Buat Berita
                                </a>
                                <a href="../index.php" target="_blank" class="block w-full py-2 px-4 bg-white/20 hover:bg-white/30 rounded-lg text-center font-semibold transition">
                                    👁️ View Website
                                </a>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </main>

    </div>

</body>
</html>