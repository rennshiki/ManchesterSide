<?php
/**
 * Manchester Side - Header Template
 * Global header untuk semua halaman
 */

// Pastikan config sudah di-load
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/config.php';
}

// Get current user if logged in
$current_user = isLoggedIn() ? getCurrentUser() : null;

// Get current page for active menu
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : SITE_NAME . ' - ' . SITE_TAGLINE; ?></title>
    <?php if (isset($page_description)): ?>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <?php endif; ?>
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">

<!-- Navigation -->
<nav class="bg-white shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex items-center space-x-3">
                <div class="flex items-center">
                    <img src="https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg" alt="Man City" class="w-8 h-8 object-contain">
                    <img src="https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg" alt="Man United" class="w-8 h-8 object-contain -ml-2">
                </div>
                <a href="index.php" class="text-xl font-bold">
                    <span class="text-united-red">Manchester</span><span class="text-city-blue">Side</span>
                </a>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-1">
                <a href="index.php" class="px-4 py-2 rounded-lg font-semibold <?php echo $current_page === 'index' ? 'bg-gradient-to-r from-city-blue to-united-red text-white' : 'text-gray-700 hover:bg-gray-100'; ?> transition">
                    Beranda
                </a>
                <a href="news.php" class="px-4 py-2 rounded-lg font-semibold <?php echo $current_page === 'news' || $current_page === 'news-detail' ? 'bg-gradient-to-r from-city-blue to-united-red text-white' : 'text-gray-700 hover:bg-gray-100'; ?> transition">
                    Berita
                </a>
                
                <!-- Dropdown Klub -->
                <div class="relative group">
                    <button class="px-4 py-2 rounded-lg font-semibold <?php echo $current_page === 'profil-klub' ? 'bg-gradient-to-r from-city-blue to-united-red text-white' : 'text-gray-700 hover:bg-gray-100'; ?> transition flex items-center">
                        Klub
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="absolute left-0 mt-0 w-48 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-100 delay-100 group-hover:delay-0">
                        <a href="profil-klub.php?team=city" class="flex items-center gap-2 px-4 py-3 hover:bg-sky-50 transition">
                            <img src="<?php echo getClubLogo('CITY'); ?>" class="w-5 h-5">
                            <span class="font-semibold text-gray-700">Manchester City</span>
                        </a>
                        <a href="profil-klub.php?team=united" class="flex items-center gap-2 px-4 py-3 hover:bg-red-50 transition">
                            <img src="<?php echo getClubLogo('UNITED'); ?>" class="w-5 h-5">
                            <span class="font-semibold text-gray-700">Manchester United</span>
                        </a>
                    </div>
                </div>

                <a href="fixtures.php" class="px-4 py-2 rounded-lg font-semibold <?php echo $current_page === 'fixtures' ? 'bg-gradient-to-r from-city-blue to-united-red text-white' : 'text-gray-700 hover:bg-gray-100'; ?> transition">
                    Jadwal & Hasil
                </a>
                <a href="tentang-kami.php" class="px-4 py-2 rounded-lg font-semibold <?php echo $current_page === 'tentang-kami' ? 'bg-gradient-to-r from-city-blue to-united-red text-white' : 'text-gray-700 hover:bg-gray-100'; ?> transition">
                    Tentang Kami
                </a>
            </div>

            <!-- User Menu -->
            <div class="flex items-center space-x-3">
                <?php if ($current_user): ?>
                    <a href="favorites.php" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition" title="Favorit">
                        ❤️
                    </a>
                    <a href="profile.php" class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-city-blue to-united-red text-white rounded-lg font-semibold hover:shadow-lg transition">
                        <span>👤</span>
                        <span class="hidden sm:inline"><?php echo htmlspecialchars($current_user['username']); ?></span>
                    </a>
                    <a href="logout.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="px-4 py-2 text-gray-700 font-semibold hover:bg-gray-100 rounded-lg transition">
                        Login
                    </a>
                    <a href="register.php" class="px-4 py-2 bg-gradient-to-r from-city-blue to-united-red text-white rounded-lg font-semibold hover:shadow-lg transition">
                        Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
