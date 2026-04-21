<?php
/**
 * Manchester Side - Admin Sidebar Component
 * Universal sidebar untuk semua halaman admin
 */

// Detect current directory depth untuk relative paths
$current_dir = dirname($_SERVER['PHP_SELF']);
$depth = substr_count(str_replace('/admin/', '', $current_dir), '/');
$prefix = str_repeat('../', $depth);

// Determine active page
$current_page = basename($_SERVER['PHP_SELF']);
$current_folder = basename(dirname($_SERVER['PHP_SELF']));

// Get site tagline from settings
$site_tagline = getSiteSetting('site_tagline', 'Manchester Side');
?>

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

    <!-- Navigation -->
    <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
        <a href="<?php echo $prefix; ?>dashboard.php" class="flex items-center space-x-3 px-4 py-3 <?php echo $current_page === 'dashboard.php' ? 'bg-city-blue text-white font-semibold' : 'hover:bg-gray-800'; ?> rounded-lg transition">
            <span class="text-xl">📊</span>
            <span>Dashboard</span>
        </a>
        <a href="<?php echo $prefix; ?>article/index.php" class="flex items-center space-x-3 px-4 py-3 <?php echo $current_folder === 'article' ? 'bg-city-blue text-white font-semibold' : 'hover:bg-gray-800'; ?> rounded-lg transition">
            <span class="text-xl">📰</span>
            <span>Berita</span>
        </a>
        <a href="<?php echo $prefix; ?>profil-klub/index.php" class="flex items-center space-x-3 px-4 py-3 <?php echo $current_folder === 'profil-klub' ? 'bg-city-blue text-white font-semibold' : 'hover:bg-gray-800'; ?> rounded-lg transition">
            <span class="text-xl">🏆</span>
            <span>Profil Klub</span>
        </a>
        <a href="<?php echo $prefix; ?>players/index.php" class="flex items-center space-x-3 px-4 py-3 <?php echo $current_folder === 'players' ? 'bg-city-blue text-white font-semibold' : 'hover:bg-gray-800'; ?> rounded-lg transition">
            <span class="text-xl">👥</span>
            <span>Pemain</span>
        </a>
        <a href="<?php echo $prefix; ?>staff/index.php" class="flex items-center space-x-3 px-4 py-3 <?php echo $current_folder === 'staff' ? 'bg-city-blue text-white font-semibold' : 'hover:bg-gray-800'; ?> rounded-lg transition">
            <span class="text-xl">🎯</span>
            <span>Staff Kepelatihan</span>
        </a>
        <a href="<?php echo $prefix; ?>schedule/index.php" class="flex items-center space-x-3 px-4 py-3 <?php echo $current_folder === 'schedule' ? 'bg-city-blue text-white font-semibold' : 'hover:bg-gray-800'; ?> rounded-lg transition">
            <span class="text-xl">📅</span>
            <span>Jadwal & Hasil</span>
        </a>
        <a href="<?php echo $prefix; ?>users/index.php" class="flex items-center space-x-3 px-4 py-3 <?php echo $current_folder === 'users' ? 'bg-city-blue text-white font-semibold' : 'hover:bg-gray-800'; ?> rounded-lg transition">
            <span class="text-xl">👤</span>
            <span>Users</span>
        </a>
        <a href="<?php echo $prefix; ?>settings.php" class="flex items-center space-x-3 px-4 py-3 <?php echo $current_page === 'settings.php' ? 'bg-city-blue text-white font-semibold' : 'hover:bg-gray-800'; ?> rounded-lg transition">
            <span class="text-xl">⚙️</span>
            <span>Settings</span>
        </a>
    </nav>

    <!-- User Info -->
    <div class="p-4 border-t border-gray-800">
        <div class="flex items-center space-x-3 mb-3">
            <div class="w-10 h-10 bg-gradient-to-r from-city-blue to-united-red rounded-full flex items-center justify-center text-white font-bold">
                <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
            </div>
            <div class="flex-1">
                <p class="font-semibold text-sm"><?php echo $admin['full_name']; ?></p>
                <p class="text-xs text-gray-400"><?php echo ucfirst($admin['role']); ?></p>
            </div>
        </div>
        <a href="<?php echo str_repeat('../', $depth + 1); ?>index.php" target="_blank" class="block w-full text-center px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-lg text-sm font-semibold transition mb-2">
            👁️ View Site
        </a>
        <a href="<?php echo $prefix; ?>logout.php" class="block w-full text-center px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold transition">
            🚪 Logout
        </a>
    </div>
</aside>
