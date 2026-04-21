<?php
/**
 * Manchester Side - Admin Users Management
 */
require_once '../../includes/config.php';

if (!isAdminLoggedIn()) {
    redirect('../login.php');
}

$db = getDB();
$admin = getCurrentAdmin();

// Handle toggle active status
if (isset($_GET['toggle_active'])) {
    $id = (int)$_GET['toggle_active'];
    $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        setFlashMessage('success', 'Status user berhasil diubah');
    }
    redirect('index.php');
}

// Handle delete action
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Don't allow deleting users with favorites
    $check_favorites = $db->query("SELECT COUNT(*) as c FROM user_favorites WHERE user_id = $id")->fetch_assoc()['c'];
    
    if ($check_favorites > 0) {
        setFlashMessage('error', 'User tidak bisa dihapus karena memiliki aktivitas (favorit). Nonaktifkan saja.');
    } else {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            setFlashMessage('success', 'User berhasil dihapus');
        } else {
            setFlashMessage('error', 'Gagal menghapus user');
        }
    }
    redirect('index.php');
}

// Get filters
$team_filter = $_GET['team'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

if ($team_filter === 'city') {
    $query .= " AND favorite_team = 'CITY'";
} elseif ($team_filter === 'united') {
    $query .= " AND favorite_team = 'UNITED'";
} elseif ($team_filter === 'none') {
    $query .= " AND favorite_team IS NULL";
}

if ($status_filter === 'active') {
    $query .= " AND is_active = 1";
} elseif ($status_filter === 'inactive') {
    $query .= " AND is_active = 0";
}

if (!empty($search)) {
    $query .= " AND (username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users_result = $stmt->get_result();

// Get statistics
$stats = [];
$stats['total_users'] = $db->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$stats['active_users'] = $db->query("SELECT COUNT(*) as c FROM users WHERE is_active = 1")->fetch_assoc()['c'];
$stats['city_fans'] = $db->query("SELECT COUNT(*) as c FROM users WHERE favorite_team = 'CITY'")->fetch_assoc()['c'];
$stats['united_fans'] = $db->query("SELECT COUNT(*) as c FROM users WHERE favorite_team = 'UNITED'")->fetch_assoc()['c'];

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
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
                <a href="../dashboard.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="../article/index.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">📰</span>
                    <span>Berita</span>
                </a>
                <a href="../profil-klub/index.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">🏆</span>
                    <span>Profil Klub</span>
                </a>
                <a href="../players/index.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">👥</span>
                    <span>Pemain</span>
                </a>
                <a href="../staff/index.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">🎯</span>
                    <span>Staff Kepelatihan</span>
                </a>
                <a href="../schedule/index.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">📅</span>
                    <span>Jadwal & Hasil</span>
                </a>
                <a href="index.php" class="flex items-center space-x-3 px-4 py-3 bg-city-blue rounded-lg text-white font-semibold">
                    <span class="text-xl">👤</span>
                    <span>Users</span>
                </a>
                <a href="../settings.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">⚙️</span>
                    <span>Settings</span>
                </a>
            </nav>

            <div class="p-4 border-t border-gray-800">
                <div class="flex items-center space-x-3 mb-3">
                    <?php if (!empty($admin['photo_url']) && file_exists('../../' . $admin['photo_url'])): ?>
                        <img src="../../<?php echo $admin['photo_url']; ?>" 
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
                <a href="../../index.php" target="_blank" class="block w-full text-center px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-lg text-sm font-semibold transition mb-2">
                    👁️ View Site
                </a>
                <a href="../logout.php" class="block w-full text-center px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold transition">
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
                        <h1 class="text-3xl font-bold text-gray-900">Manage Users</h1>
                        <p class="text-gray-600 mt-1">Kelola pengguna terdaftar Manchester Side</p>
                    </div>
                </div>
            </header>

            <div class="p-6">

                <?php if ($flash): ?>
                    <div class="mb-6 bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-50 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-200 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-800 px-4 py-3 rounded-lg">
                        <?php echo $flash['message']; ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="grid md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex flex-col items-center justify-center mb-2">
                            <span class="text-3xl mb-2">👥</span>
                            <span class="text-3xl font-bold text-gray-900"><?php echo $stats['total_users']; ?></span>
                        </div>
                        <p class="text-gray-600 font-semibold text-center">Total Users</p>
                    </div>

                    <div class="bg-gradient-to-br from-green-500 to-green-700 text-white rounded-xl shadow-lg p-6">
                        <div class="flex flex-col items-center justify-center mb-2">
                            <span class="text-3xl mb-2">✅</span>
                            <span class="text-3xl font-bold"><?php echo $stats['active_users']; ?></span>
                        </div>
                        <p class="font-semibold text-center">Active Users</p>
                    </div>

                    <div class="bg-gradient-to-br from-city-blue to-city-navy text-white rounded-xl shadow-lg p-6">
                        <div class="flex flex-col items-center justify-center mb-2">
                            <img src="https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg" alt="Man City" class="w-12 h-12 object-contain mb-2">
                            <span class="text-3xl font-bold"><?php echo $stats['city_fans']; ?></span>
                        </div>
                        <p class="font-semibold text-center">City Fans</p>
                    </div>

                    <div class="bg-gradient-to-br from-united-red to-red-900 text-white rounded-xl shadow-lg p-6">
                        <div class="flex flex-col items-center justify-center mb-2">
                            <img src="https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg" alt="Man United" class="w-12 h-12 object-contain mb-2">
                            <span class="text-3xl font-bold"><?php echo $stats['united_fans']; ?></span>
                        </div>
                        <p class="font-semibold text-center">United Fans</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <form method="GET" action="" class="grid md:grid-cols-4 gap-4">
                        
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">🔍 Cari User</label>
                            <input 
                                type="text" 
                                name="search" 
                                value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Username, email, atau nama..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                            >
                        </div>

                        <!-- Team Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">⚽ Tim Favorit</label>
                            <select name="team" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent">
                                <option value="all" <?php echo $team_filter === 'all' ? 'selected' : ''; ?>>Semua</option>
                                <option value="city" <?php echo $team_filter === 'city' ? 'selected' : ''; ?>>Man City</option>
                                <option value="united" <?php echo $team_filter === 'united' ? 'selected' : ''; ?>>Man United</option>
                                <option value="none" <?php echo $team_filter === 'none' ? 'selected' : ''; ?>>⚪ Belum Pilih</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">📊 Status</label>
                            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Semua</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>✅ Active</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>❌ Inactive</option>
                            </select>
                        </div>

                        <div class="md:col-span-4 flex gap-2">
                            <button type="submit" class="px-6 py-2 bg-city-blue text-white font-semibold rounded-lg hover:bg-city-navy transition">
                                Filter
                            </button>
                            <a href="index.php" class="px-6 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition">
                                Reset
                            </a>
                        </div>

                    </form>
                </div>

                <!-- Users Table -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">User</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">Email</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">Tim Favorit</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">Aktivitas</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">Bergabung</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if ($users_result->num_rows > 0): ?>
                                <?php while ($user = $users_result->fetch_assoc()): ?>
                                    <?php
                                    // Get user activity
                                    $favorites_count = $db->query("SELECT COUNT(*) as c FROM user_favorites WHERE user_id = {$user['id']}")->fetch_assoc()['c'];
                                    $reactions_count = $db->query("SELECT COUNT(*) as c FROM article_reactions WHERE user_id = {$user['id']}")->fetch_assoc()['c'];
                                    ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-10 h-10 bg-gradient-to-r from-city-blue to-united-red rounded-full flex items-center justify-center text-white font-bold">
                                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-900"><?php echo $user['username']; ?></p>
                                                    <?php if ($user['full_name']): ?>
                                                        <p class="text-xs text-gray-500"><?php echo $user['full_name']; ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <?php echo $user['email']; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($user['favorite_team']): ?>
                                                <span class="px-3 py-1 bg-<?php echo $user['favorite_team'] === 'CITY' ? 'city-blue' : 'united-red'; ?> text-white rounded-full text-xs font-bold flex items-center gap-1 inline-flex">
                                                    <img src="<?php echo $user['favorite_team'] === 'CITY' ? 'https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg' : 'https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg'; ?>" 
                                                         alt="<?php echo $user['favorite_team']; ?>" 
                                                         class="w-3 h-3">
                                                    <?php echo $user['favorite_team']; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 bg-gray-300 text-gray-700 rounded-full text-xs font-bold">
                                                    ⚪ None
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <div class="space-y-1">
                                                <p>❤️ <?php echo $favorites_count; ?> favorit</p>
                                                <p>👍 <?php echo $reactions_count; ?> reaksi</p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($user['is_active']): ?>
                                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-bold">
                                                    ✅ Active
                                                </span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-bold">
                                                    ❌ Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <?php echo formatDateIndo($user['created_at']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end space-x-2">
                                                <a href="?toggle_active=<?php echo $user['id']; ?>" onclick="return confirm('Ubah status user?')" class="px-3 py-1 bg-purple-100 text-purple-800 rounded-lg text-xs font-semibold hover:bg-purple-200 transition" title="Toggle Status">
                                                    <?php echo $user['is_active'] ? '🔴' : '✅'; ?>
                                                </a>
                                                <a href="?delete=<?php echo $user['id']; ?>" onclick="return confirm('Yakin ingin menghapus user ini?\n\nUser dengan aktivitas tidak bisa dihapus.')" class="px-3 py-1 bg-red-100 text-red-800 rounded-lg text-xs font-semibold hover:bg-red-200 transition" title="Delete">
                                                    🗑️
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        <div class="text-6xl mb-4">👤</div>
                                        <p class="text-lg font-semibold">Tidak ada user ditemukan</p>
                                        <p class="text-sm mt-2">Coba ubah filter pencarian</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </main>

    </div>

</body>
</html>