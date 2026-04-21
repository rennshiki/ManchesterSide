<?php
/**
 * Manchester Side - Admin Staff Management
 */
require_once '../../includes/config.php';

if (!isAdminLoggedIn()) {
    redirect('../login.php');
}

$db = getDB();
$admin = getCurrentAdmin();

// Handle delete action
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM staff WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        setFlashMessage('success', 'Staff berhasil dihapus');
    } else {
        setFlashMessage('error', 'Gagal menghapus staff');
    }
    redirect('index.php');
}

// Handle toggle active status
if (isset($_GET['toggle_active'])) {
    $id = (int)$_GET['toggle_active'];
    $stmt = $db->prepare("UPDATE staff SET is_active = NOT is_active WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        setFlashMessage('success', 'Status staff berhasil diubah');
    }
    redirect('index.php');
}

// Get filters
$club_filter = $_GET['club'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT s.*, c.name as club_name, c.code as club_code 
          FROM staff s 
          JOIN clubs c ON s.club_id = c.id 
          WHERE 1=1";
$params = [];
$types = "";

if ($club_filter === 'city') {
    $query .= " AND c.code = 'CITY'";
} elseif ($club_filter === 'united') {
    $query .= " AND c.code = 'UNITED'";
}

if ($status_filter === 'active') {
    $query .= " AND s.is_active = 1";
} elseif ($status_filter === 'inactive') {
    $query .= " AND s.is_active = 0";
}

if (!empty($search)) {
    $query .= " AND (s.name LIKE ? OR s.role LIKE ? OR s.nationality LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$query .= " ORDER BY s.created_at DESC";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$staff_result = $stmt->get_result();

// Get statistics
$stats = [];
$stats['total_staff'] = $db->query("SELECT COUNT(*) as c FROM staff")->fetch_assoc()['c'];
$stats['active_staff'] = $db->query("SELECT COUNT(*) as c FROM staff WHERE is_active = 1")->fetch_assoc()['c'];
$stats['city_staff'] = $db->query("SELECT COUNT(*) as c FROM staff WHERE club_id = 1")->fetch_assoc()['c'];
$stats['united_staff'] = $db->query("SELECT COUNT(*) as c FROM staff WHERE club_id = 2")->fetch_assoc()['c'];

$flash = getFlashMessage();
$page_title = 'Manage Staff';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Panel</title>
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
        
        .flip-card-container {
            perspective: 1000px;
            height: 420px;
        }
        
        .flip-card {
            position: relative;
            width: 100%;
            height: 100%;
            transition: transform 0.7s;
            transform-style: preserve-3d;
            cursor: pointer;
        }
        
        .flip-card.flipped {
            transform: rotateY(180deg);
        }
        
        .flip-card-front,
        .flip-card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .flip-card-back {
            transform: rotateY(180deg);
        }
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
                <a href="index.php" class="flex items-center space-x-3 px-4 py-3 bg-city-blue rounded-lg text-white font-semibold">
                    <span class="text-xl">🎯</span>
                    <span>Staff Kepelatihan</span>
                </a>
                <a href="../schedule/index.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">📅</span>
                    <span>Jadwal & Hasil</span>
                </a>
                <a href="../users/index.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition">
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
                        <h1 class="text-3xl font-bold text-gray-900">Staff Kepelatihan</h1>
                        <p class="text-gray-600 mt-1">Kelola staff kepelatihan Manchester City & United</p>
                    </div>
                    <a href="create.php" class="px-6 py-3 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition">
                        ➕ Tambah Staff Kepelatihan
                    </a>
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
                            <span class="text-3xl mb-2">🎯</span>
                            <span class="text-3xl font-bold text-gray-900"><?php echo $stats['total_staff']; ?></span>
                        </div>
                        <p class="text-gray-600 font-semibold text-center">Total Staff</p>
                    </div>

                    <div class="bg-gradient-to-br from-green-500 to-green-700 text-white rounded-xl shadow-lg p-6">
                        <div class="flex flex-col items-center justify-center mb-2">
                            <span class="text-3xl mb-2">✅</span>
                            <span class="text-3xl font-bold"><?php echo $stats['active_staff']; ?></span>
                        </div>
                        <p class="font-semibold text-center">Active Staff</p>
                    </div>

                    <div class="bg-gradient-to-br from-city-blue to-city-navy text-white rounded-xl shadow-lg p-6">
                        <div class="flex flex-col items-center justify-center mb-2">
                            <img src="https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg" alt="Man City" class="w-12 h-12 object-contain mb-2">
                            <span class="text-3xl font-bold"><?php echo $stats['city_staff']; ?></span>
                        </div>
                        <p class="font-semibold text-center">City Staff</p>
                    </div>

                    <div class="bg-gradient-to-br from-united-red to-red-900 text-white rounded-xl shadow-lg p-6">
                        <div class="flex flex-col items-center justify-center mb-2">
                            <img src="https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg" alt="Man United" class="w-12 h-12 object-contain mb-2">
                            <span class="text-3xl font-bold"><?php echo $stats['united_staff']; ?></span>
                        </div>
                        <p class="font-semibold text-center">United Staff</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <form method="GET" action="" class="grid md:grid-cols-4 gap-4">
                        
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">🔍 Cari Staff</label>
                            <input 
                                type="text" 
                                name="search" 
                                value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Nama, role, atau nationality..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                            >
                        </div>

                        <!-- Club Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">⚽ Klub</label>
                            <select name="club" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent">
                                <option value="all" <?php echo $club_filter === 'all' ? 'selected' : ''; ?>>Semua</option>
                                <option value="city" <?php echo $club_filter === 'city' ? 'selected' : ''; ?>>Man City</option>
                                <option value="united" <?php echo $club_filter === 'united' ? 'selected' : ''; ?>>Man United</option>
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

                <!-- Staff Grid with Flip Cards -->
                <?php if ($staff_result->num_rows > 0): ?>
                    <div class="grid md:grid-cols-3 lg:grid-cols-4 gap-6">
                        <?php 
                        function getStaffPhoto($photo_url, $name, $club_code) {
                            if (!empty($photo_url) && file_exists('../../' . $photo_url)) {
                                return '../../' . $photo_url;
                            }
                            $bg = $club_code === 'CITY' ? '6CABDD' : 'DA291C';
                            return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&size=400&background={$bg}&color=fff&bold=true&font-size=0.33";
                        }
                        
                        while ($staff = $staff_result->fetch_assoc()): 
                            $staff_photo = getStaffPhoto($staff['photo_url'], $staff['name'], $staff['club_code']);
                            $border_color = $staff['club_code'] === 'CITY' ? 'border-sky-400' : 'border-red-500';
                            $accent_color = $staff['club_code'] === 'CITY' ? 'sky' : 'red';
                        ?>
                            
                            <div class="flip-card-container">
                                <div class="flip-card" onclick="this.classList.toggle('flipped')">
                                    
                                    <!-- FRONT SIDE -->
                                    <div class="flip-card-front">
                                        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden border-4 <?php echo $border_color; ?> h-full">
                                            <!-- Staff Photo with Role Badge -->
                                            <div class="relative h-64 bg-gray-100 flex items-center justify-center overflow-hidden">
                                                <img src="<?php echo $staff_photo; ?>" alt="<?php echo $staff['name']; ?>" class="w-full h-full object-cover">
                                                <!-- Role Badge -->
                                                <div class="absolute top-3 right-3 px-3 py-1 bg-gradient-to-br from-<?php echo $staff['club_code'] === 'CITY' ? 'sky-400' : 'red-500'; ?> to-<?php echo $staff['club_code'] === 'CITY' ? 'blue-800' : 'red-900'; ?> rounded-full shadow-lg border-2 border-white">
                                                    <span class="text-xs font-black text-white"><?php echo strtoupper(substr($staff['role'], 0, 3)); ?></span>
                                                </div>
                                            </div>
                                            
                                            <!-- Staff Info -->
                                            <div class="p-4 relative">
                                                <h3 class="font-bold text-gray-900 text-lg mb-2 text-center"><?php echo $staff['name']; ?></h3>
                                                <div class="text-center mb-3">
                                                    <span class="px-3 py-1 bg-<?php echo $accent_color; ?>-100 text-<?php echo $accent_color; ?>-800 rounded-full text-xs font-bold">
                                                        <?php echo $staff['role']; ?>
                                                    </span>
                                                </div>
                                                <div class="text-sm text-gray-600 space-y-1">
                                                    <p class="flex items-center justify-center">
                                                        <span class="mr-2">🌍</span>
                                                        <?php echo $staff['nationality']; ?>
                                                    </p>
                                                    <?php if ($staff['join_date']): ?>
                                                        <p class="flex items-center justify-center">
                                                            <span class="mr-2">📅</span>
                                                            Sejak <?php echo date('Y', strtotime($staff['join_date'])); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Click hint - positioned lower with more spacing -->
                                                <div class="mt-6 text-center text-xs text-gray-400">
                                                    Klik untuk detail →
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- BACK SIDE -->
                                    <div class="flip-card-back">
                                        <div class="bg-gradient-to-br from-<?php echo $accent_color; ?>-500 to-<?php echo $accent_color === 'sky' ? 'blue' : 'red'; ?>-900 text-white h-full p-6 flex flex-col rounded-2xl shadow-2xl border-4 <?php echo $border_color; ?>">
                                            <div class="text-center mb-4">
                                                <div class="text-4xl font-black mb-2">
                                                    <?php echo strtoupper(substr($staff['role'], 0, 3)); ?>
                                                </div>
                                                <h3 class="text-xl font-bold"><?php echo $staff['name']; ?></h3>
                                            </div>
                                            
                                            <div class="flex-1 space-y-3 text-sm">
                                                <div class="flex justify-between pb-2 border-b border-white/20">
                                                    <span>Role:</span>
                                                    <span class="font-bold"><?php echo $staff['role']; ?></span>
                                                </div>
                                                <div class="flex justify-between pb-2 border-b border-white/20">
                                                    <span>Kebangsaan:</span>
                                                    <span class="font-bold"><?php echo $staff['nationality']; ?></span>
                                                </div>
                                                 <div class="flex justify-between pb-2 border-b border-white/20">
                                                    <span>Tanggal Lahir:</span>
                                                    <span class="font-bold"><?php echo formatDateIndo($staff['birth_date']); ?></span>
                                                </div>
                                                <?php if ($staff['join_date']): ?>
                                                    <div class="flex justify-between pb-2 border-b border-white/20">
                                                        <span>Bergabung:</span>
                                                        <span class="font-bold"><?php echo formatDateIndo($staff['join_date']); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="flex justify-between pb-2 border-b border-white/20">
                                                    <span>Status:</span>
                                                    <span class="font-bold"><?php echo $staff['is_active'] ? '✅ Aktif' : '❌ Nonaktif'; ?></span>
                                                </div>
                                            </div>
                                            
                                            <!-- Action Buttons -->
                                            <div class="mt-4 grid grid-cols-3 gap-2">
                                                <a href="edit.php?id=<?php echo $staff['id']; ?>" class="px-3 py-2 bg-white/20 hover:bg-white/30 rounded text-center text-xs font-semibold transition" onclick="event.stopPropagation()">
                                                    ✏️ Edit
                                                </a>
                                                <a href="?toggle_active=<?php echo $staff['id']; ?>" class="px-3 py-2 bg-white/20 hover:bg-white/30 rounded text-center text-xs font-semibold transition" onclick="event.stopPropagation()">
                                                    <?php echo $staff['is_active'] ? '❌' : '✅'; ?>
                                                </a>
                                                <a href="?delete=<?php echo $staff['id']; ?>" onclick="event.stopPropagation(); return confirm('Yakin hapus staff ini?')" class="px-3 py-2 bg-red-600/80 hover:bg-red-700 rounded text-center text-xs font-semibold transition">
                                                    🗑️
                                                </a>
                                            </div>
                                            
                                            <div class="mt-3 text-center text-xs opacity-70">
                                                Klik untuk kembali
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                        <div class="text-6xl mb-4">🎯</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Tidak ada staff ditemukan</h3>
                        <p class="text-gray-600 mb-6">Coba ubah filter pencarian atau tambah staff baru</p>
                        <a href="create.php" class="inline-block px-6 py-3 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition">
                            ➕ Tambah Staff Baru
                        </a>
                    </div>
                <?php endif; ?>

            </div>

        </main>

    </div>

<script>
    // Keyboard support for flip cards
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.flip-card.flipped').forEach(card => {
                card.classList.remove('flipped');
            });
        }
    });
</script>

</body>
</html>
