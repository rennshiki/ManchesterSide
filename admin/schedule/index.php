<?php
/**
 * Manchester Side - Admin Jadwal & Hasil Management
 */
require_once '../../includes/config.php';

if (!isAdminLoggedIn()) {
    redirect('../login.php');
}

$db = getDB();
$admin = getCurrentAdmin();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM matches WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        setFlashMessage('success', 'Jadwal berhasil dihapus');
    } else {
        setFlashMessage('error', 'Gagal menghapus jadwal');
    }
    redirect('index.php');
}

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Build query
$where = "1=1";
if ($filter === 'upcoming') {
    $where .= " AND m.match_date > NOW()";
} elseif ($filter === 'finished') {
    $where .= " AND m.status = 'finished'";
}

// Check if logo_url column exists
$logo_column_exists = false;
try {
    $check_column = $db->query("SHOW COLUMNS FROM clubs LIKE 'logo_url'");
    $logo_column_exists = $check_column->num_rows > 0;
} catch (Exception $e) {
    $logo_column_exists = false;
}

// Get matches
if ($logo_column_exists) {
    $query = "SELECT 
        m.*,
        h.name as home_team, h.code as home_code, h.logo_url as home_logo,
        a.name as away_team, a.code as away_code, a.logo_url as away_logo
    FROM matches m
    JOIN clubs h ON m.home_team_id = h.id
    JOIN clubs a ON m.away_team_id = a.id
    WHERE $where
    ORDER BY m.match_date DESC";
} else {
    $query = "SELECT 
        m.*,
        h.name as home_team, h.code as home_code,
        a.name as away_team, a.code as away_code
    FROM matches m
    JOIN clubs h ON m.home_team_id = h.id
    JOIN clubs a ON m.away_team_id = a.id
    WHERE $where
    ORDER BY m.match_date DESC";
}

$matches_result = $db->query($query);

// Check if match_goals table exists
$table_exists = false;
try {
    $check_table = $db->query("SHOW TABLES LIKE 'match_goals'");
    $table_exists = $check_table->num_rows > 0;
} catch (Exception $e) {
    $table_exists = false;
}

// Get matches with goals
$matches = [];
while ($row = $matches_result->fetch_assoc()) {
    $row['goals'] = [];
    
    // Only get goals if table exists
    if ($table_exists) {
        try {
            $goals_query = "SELECT mg.*, p.name as player_name, c.code as team_code, c.name as team_name 
                           FROM match_goals mg 
                           JOIN players p ON mg.player_id = p.id 
                           JOIN clubs c ON mg.team_id = c.id 
                           WHERE mg.match_id = {$row['id']} 
                           ORDER BY mg.minute";
            $goals_result = $db->query($goals_query);
            if ($goals_result) {
                while ($goal = $goals_result->fetch_assoc()) {
                    $row['goals'][] = $goal;
                }
            }
        } catch (Exception $e) {
            // Ignore error if table doesn't exist
        }
    }
    
    $matches[] = $row;
}

// Get statistics
$stats = [];
$stats['total'] = $db->query("SELECT COUNT(*) as c FROM matches")->fetch_assoc()['c'];
$stats['upcoming'] = $db->query("SELECT COUNT(*) as c FROM matches WHERE match_date > NOW()")->fetch_assoc()['c'];
$stats['finished'] = $db->query("SELECT COUNT(*) as c FROM matches WHERE status = 'finished'")->fetch_assoc()['c'];
// Remove live status - not used anymore

$flash = getFlashMessage();
$page_title = "Manage Jadwal";
include '../includes/header.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Manage Jadwal</h1>
            <p class="text-gray-600 mt-1">Kelola jadwal pertandingan semua tim</p>
        </div>
        <a href="create.php" class="px-6 py-3 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition">
            ➕ Tambah Jadwal
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="mb-6 bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-50 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-200 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-800 px-4 py-3 rounded-lg">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>

    <?php if (!$table_exists || !$logo_column_exists): ?>
        <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 px-6 py-4 rounded-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <span class="text-2xl mr-3">⚠️</span>
                    <div>
                        <h3 class="font-bold">Fitur Belum Lengkap</h3>
                        <p class="text-sm">
                            <?php if (!$table_exists): ?>
                                Tabel match_goals belum ada. 
                            <?php endif; ?>
                            <?php if (!$logo_column_exists): ?>
                                Kolom logo_url belum ada. 
                            <?php endif; ?>
                            Jalankan setup untuk mengaktifkan semua fitur.
                        </p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="setup.php" class="px-4 py-2 bg-yellow-600 text-white font-bold rounded-lg hover:bg-yellow-700 transition text-sm">
                        🔧 Setup Lengkap
                    </a>
                    <?php if (!$logo_column_exists): ?>
                        <a href="../../quick_setup.php" class="px-4 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition text-sm">
                            ⚡ Quick Fix
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-gray-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Total Jadwal</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
                </div>
                <div class="text-4xl">📅</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Akan Datang</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $stats['upcoming']; ?></p>
                </div>
                <div class="text-4xl">⏰</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Selesai</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $stats['finished']; ?></p>
                </div>
                <div class="text-4xl">✅</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Selesai</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $stats['finished']; ?></p>
                </div>
                <div class="text-4xl">✅</div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <div class="flex items-center gap-3">
            <span class="font-semibold text-gray-700">Filter:</span>
            <a href="?filter=all" class="px-4 py-2 <?php echo $filter === 'all' ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded-lg font-semibold transition text-sm">
                Semua
            </a>
            <a href="?filter=upcoming" class="px-4 py-2 <?php echo $filter === 'upcoming' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded-lg font-semibold transition text-sm">
                Akan Datang
            </a>

            <a href="?filter=finished" class="px-4 py-2 <?php echo $filter === 'finished' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded-lg font-semibold transition text-sm">
                Selesai
            </a>
        </div>
    </div>

    <!-- Matches List -->
    <?php if (count($matches) > 0): ?>
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pertandingan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kompetisi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Skor</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($matches as $match): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    <span class="font-semibold"><?php echo $match['home_team']; ?></span>
                                    <span class="text-gray-400">vs</span>
                                    <span class="font-semibold"><?php echo $match['away_team']; ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-semibold">
                                    <?php echo $match['competition']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?php echo formatDateIndo($match['match_date']); ?>
                                <br>
                                <span class="text-xs text-gray-500"><?php echo date('H:i', strtotime($match['match_date'])); ?> WIB</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_colors = [
                                    'scheduled' => 'bg-yellow-100 text-yellow-800',
                                    'finished' => 'bg-green-100 text-green-800',
                                    'postponed' => 'bg-gray-100 text-gray-800'
                                ];
                                $status_labels = [
                                    'scheduled' => 'Terjadwal',
                                    'finished' => 'Selesai',
                                    'postponed' => 'Ditunda'
                                ];
                                ?>
                                <span class="px-2 py-1 <?php echo $status_colors[$match['status']]; ?> rounded text-xs font-semibold">
                                    <?php echo $status_labels[$match['status']]; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($match['status'] === 'finished'): ?>
                                    <div class="font-bold text-lg mb-1">
                                        <?php echo $match['home_score']; ?> - <?php echo $match['away_score']; ?>
                                    </div>
                                    
                                    <?php if (!empty($match['goals'])): ?>
                                        <div class="text-xs text-gray-600 space-y-1">
                                            <?php foreach ($match['goals'] as $goal): ?>
                                                <div class="flex items-center space-x-1">
                                                    <span class="font-semibold"><?php echo $goal['minute']; ?>'</span>
                                                    <?php if (in_array($goal['team_code'], ['CITY', 'UNITED'])): ?>
                                                        <a href="../../profil-klub.php?tab=players&team=<?php echo strtolower($goal['team_code']); ?>" 
                                                           class="text-blue-600 hover:text-blue-800 hover:underline">
                                                            <?php echo $goal['player_name']; ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span><?php echo $goal['player_name']; ?></span>
                                                    <?php endif; ?>
                                                    <span class="text-gray-400">(<?php echo $goal['team_code']; ?>)</span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="edit.php?id=<?php echo $match['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                <a href="?delete=<?php echo $match['id']; ?>" onclick="return confirm('Yakin ingin menghapus jadwal ini?')" class="text-red-600 hover:text-red-900">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-lg p-12 text-center">
            <div class="text-6xl mb-4">📅</div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Belum Ada Jadwal</h3>
            <p class="text-gray-600 mb-6">Mulai tambahkan jadwal pertandingan</p>
            <a href="create.php" class="inline-block px-6 py-3 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition">
                ➕ Tambah Jadwal Pertama
            </a>
        </div>
    <?php endif; ?>

</main>


