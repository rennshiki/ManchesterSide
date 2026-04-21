<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Panel'; ?> - Manchester Side</title>
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
        <aside class="w-64 bg-gray-900 text-white flex flex-col flex-shrink-0">
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
                <?php
                // Detect current directory to build correct paths
                $current_path = $_SERVER['PHP_SELF'];
                $is_in_subdir = strpos($current_path, '/admin/') !== false && substr_count($current_path, '/') > 2;
                $base = $is_in_subdir ? '../' : '';
                
                // Detect active page
                $current_page = basename(dirname($_SERVER['PHP_SELF']));
                ?>
                <a href="<?php echo $base; ?>dashboard.php" class="flex items-center space-x-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'bg-city-blue text-white font-semibold' : 'hover:bg-gray-800'; ?> rounded-lg transition">
                    <span class="text-xl">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo $base; ?>article/index.php" class="flex items-center space-x-3 px-4 py-3 <?php echo $current_page === 'article' ? 'bg-city-blue text-white font-semibold' : 'hover:bg-gray-800'; ?> rounded-lg transition">
                    <span class="text-xl">📰</span>
                    <span>Berita</span>
                </a>
                <a href="<?php echo $base; ?>profil-klub/index.php" class="flex items-center space-x-3 px-4 py-3 <?php echo $current_page === 'profil-klub' ? 'bg-city-blue text-white font-semibold' : 'hover:bg-gray-800'; ?> rounded-lg transition">
                    <span class="text-xl">🏆</span>
                    <span>Profil Klub</span>
                </a>
                <a href="<?php echo $base; ?>players/index.php" class="flex items-center space-x-3 px-4 py-3 <?php echo $current_page === 'players' ? 'bg-city-blue text-white font-semibold' : 'hover:bg-gray-800'; ?> rounded-lg transition">
                    <span class="text-xl">👥</span>
                    <span>Pemain</span>
                </a>
                <a href="<?php echo $base; ?>staff/index.php" class="flex items-center space-x-3 px-4 py-3 <?php echo $current_page === 'staff' ? 'bg-city-blue text-white font-semibold' : 'hover:bg-gray-800'; ?> rounded-lg transition">
                    <span class="text-xl">🎯</span>
                    <span>Staff Kepelatihan</span>
                </a>
                <a href="<?php echo $base; ?>schedule/index.php" class="flex items-center space-x-3 px-4 py-3 <?php echo $current_page === 'schedule' ? 'bg-city-blue text-white font-semibold' : 'hover:bg-gray-800'; ?> rounded-lg transition">
                    <span class="text-xl">📅</span>
                    <span>Jadwal & Hasil</span>
                </a>
                <a href="<?php echo $base; ?>users/index.php" class="flex items-center space-x-3 px-4 py-3 <?php echo $current_page === 'users' ? 'bg-city-blue text-white font-semibold' : 'hover:bg-gray-800'; ?> rounded-lg transition">
                    <span class="text-xl">👤</span>
                    <span>Users</span>
                </a>
                <a href="<?php echo $base; ?>settings.php" class="flex items-center space-x-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'bg-city-blue text-white font-semibold' : 'hover:bg-gray-800'; ?> rounded-lg transition">
                    <span class="text-xl">⚙️</span>
                    <span>Settings</span>
                </a>
            </nav>

            <div class="p-4 border-t border-gray-800">
                <div class="flex items-center space-x-3 mb-3">
                    <?php if (!empty($admin['photo_url']) && file_exists('../' . $admin['photo_url'])): ?>
                        <img src="<?php echo $is_in_subdir ? '../../' : '../'; ?><?php echo $admin['photo_url']; ?>" 
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
                <a href="<?php echo $is_in_subdir ? '../../' : '../'; ?>index.php" target="_blank" class="block w-full text-center px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-lg text-sm font-semibold transition mb-2">
                    👁️ View Site
                </a>
                <a href="<?php echo $base; ?>logout.php" class="block w-full text-center px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold transition">
                    🚪 Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
