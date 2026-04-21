<?php
/**
 * Manchester Side - Admin Players Management with Flip Cards
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
    
    // Delete photo if exists
    $photo_query = $db->prepare("SELECT photo_url FROM players WHERE id = ?");
    $photo_query->bind_param("i", $id);
    $photo_query->execute();
    $photo_result = $photo_query->get_result()->fetch_assoc();
    
    if ($photo_result && $photo_result['photo_url'] && file_exists('../../' . $photo_result['photo_url'])) {
        unlink('../../' . $photo_result['photo_url']);
    }
    
    $stmt = $db->prepare("DELETE FROM players WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        setFlashMessage('success', 'Pemain berhasil dihapus');
    } else {
        setFlashMessage('error', 'Gagal menghapus pemain');
    }
    redirect('index.php');
}

// Handle toggle active
if (isset($_GET['toggle_active'])) {
    $id = (int)$_GET['toggle_active'];
    $stmt = $db->prepare("UPDATE players SET is_active = NOT is_active WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        setFlashMessage('success', 'Status pemain berhasil diubah');
    }
    redirect('index.php');
}

// Get filters
$club_filter = $_GET['club'] ?? 'all';
$position_filter = $_GET['position'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT 
    p.*, c.name as club_name, c.code as club_code
FROM players p
JOIN clubs c ON p.club_id = c.id
WHERE 1=1";

$params = [];
$types = "";

if ($club_filter === 'city') {
    $query .= " AND c.code = 'CITY'";
} elseif ($club_filter === 'united') {
    $query .= " AND c.code = 'UNITED'";
}

if ($position_filter !== 'all') {
    $query .= " AND p.position = ?";
    $params[] = $position_filter;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.nationality LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$query .= " ORDER BY c.id, p.position, p.jersey_number";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$players_result = $stmt->get_result();

// Helper functions
function getPlayerPhoto($photo_url, $name, $team) {
    if (!empty($photo_url) && file_exists('../../' . $photo_url)) {
        return '../../' . $photo_url;
    }
    $bg = $team === 'CITY' ? '6CABDD' : 'DA291C';
    return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&size=400&background={$bg}&color=fff&bold=true&font-size=0.4";
}

function calculateAge($birth_date) {
    if (!$birth_date) return null;
    $birth = new DateTime($birth_date);
    $today = new DateTime();
    return $today->diff($birth)->y;
}

$flash = getFlashMessage();

$page_title = "Manage Pemain";
include '../includes/header.php';
?>

<style>
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

<div class="p-8">
    
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Manage Pemain</h1>
            <p class="text-gray-600 mt-1">Kelola data pemain Manchester City & United</p>
        </div>
        <a href="create.php" class="px-6 py-3 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition">
            ➕ Tambah Pemain
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="mb-6 bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-50 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-200 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-800 px-4 py-3 rounded-lg">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <form method="GET" action="" class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">🔍 Cari Pemain</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nama atau kebangsaan..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">⚽ Klub</label>
                <select name="club" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue">
                    <option value="all" <?php echo $club_filter === 'all' ? 'selected' : ''; ?>>Semua Klub</option>
                    <option value="city" <?php echo $club_filter === 'city' ? 'selected' : ''; ?>>Man City</option>
                    <option value="united" <?php echo $club_filter === 'united' ? 'selected' : ''; ?>>Man United</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">📍 Posisi</label>
                <select name="position" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue">
                    <option value="all" <?php echo $position_filter === 'all' ? 'selected' : ''; ?>>Semua Posisi</option>
                    <option value="Goalkeeper" <?php echo $position_filter === 'Goalkeeper' ? 'selected' : ''; ?>>🧤 Goalkeeper</option>
                    <option value="Defender" <?php echo $position_filter === 'Defender' ? 'selected' : ''; ?>>🛡️ Defender</option>
                    <option value="Midfielder" <?php echo $position_filter === 'Midfielder' ? 'selected' : ''; ?>>⚙️ Midfielder</option>
                    <option value="Forward" <?php echo $position_filter === 'Forward' ? 'selected' : ''; ?>>⚽ Forward</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-city-blue text-white font-semibold rounded-lg hover:bg-city-navy transition">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Players Grid with Flip Cards -->
    <?php if ($players_result->num_rows > 0): ?>
        <div class="grid md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php while ($player = $players_result->fetch_assoc()): ?>
                <?php
                $player_photo = getPlayerPhoto($player['photo_url'], $player['name'], $player['club_code']);
                $age = calculateAge($player['birth_date']);
                $border_color = $player['club_code'] === 'CITY' ? 'border-sky-400' : 'border-red-500';
                $accent_color = $player['club_code'] === 'CITY' ? 'sky' : 'red';
                ?>
                
                <div class="flip-card-container">
                    <div class="flip-card" onclick="this.classList.toggle('flipped')">
                        
                        <!-- FRONT SIDE -->
                        <div class="flip-card-front">
                            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden border-4 <?php echo $border_color; ?> h-full">
                                <!-- Player Photo with Jersey Number Badge -->
                                <div class="relative h-64 bg-gray-100 flex items-center justify-center overflow-hidden">
                                    <img src="<?php echo $player_photo; ?>" alt="<?php echo $player['name']; ?>" class="w-full h-full object-cover">
                                    <!-- Jersey Number Badge -->
                                    <div class="absolute top-3 right-3 w-14 h-14 bg-gradient-to-br from-<?php echo $player['club_code'] === 'CITY' ? 'sky-400' : 'red-500'; ?> to-<?php echo $player['club_code'] === 'CITY' ? 'blue-800' : 'red-900'; ?> rounded-full flex items-center justify-center shadow-lg border-2 border-white">
                                        <span class="text-2xl font-black text-white"><?php echo $player['jersey_number']; ?></span>
                                    </div>
                                </div>
                                
                                <!-- Player Info -->
                                <div class="p-4 relative">
                                    <h3 class="font-bold text-gray-900 text-lg mb-2 text-center"><?php echo $player['name']; ?></h3>
                                    <div class="text-center mb-3">
                                        <span class="px-3 py-1 bg-<?php echo $accent_color; ?>-100 text-<?php echo $accent_color; ?>-800 rounded-full text-xs font-bold">
                                            <?php echo $player['position']; ?>
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 space-y-1">
                                        <p class="flex items-center justify-center">
                                            <span class="mr-2">🌍</span>
                                            <?php echo $player['nationality']; ?>
                                        </p>
                                        <?php if ($age): ?>
                                            <p class="flex items-center justify-center">
                                                <span class="mr-2">🎂</span>
                                                <?php echo $age; ?> tahun
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
                            <div class="bg-gradient-to-br from-<?php echo $accent_color; ?>-500 to-<?php echo $accent_color === 'sky' ? 'blue' : 'red'; ?>-900 text-white h-full p-6 flex flex-col">
                                <div class="text-center mb-4">
                                    <div class="text-5xl font-black mb-2"><?php echo $player['jersey_number']; ?></div>
                                    <h3 class="text-xl font-bold"><?php echo $player['name']; ?></h3>
                                </div>
                                
                                <div class="flex-1 space-y-3 text-sm">
                                    <div class="flex justify-between pb-2 border-b border-white/20">
                                        <span>Posisi:</span>
                                        <span class="font-bold"><?php echo $player['position']; ?></span>
                                    </div>
                                    <div class="flex justify-between pb-2 border-b border-white/20">
                                        <span>Kebangsaan:</span>
                                        <span class="font-bold"><?php echo $player['nationality']; ?></span>
                                    </div>
                                    <?php if ($player['height']): ?>
                                        <div class="flex justify-between pb-2 border-b border-white/20">
                                            <span>Tinggi:</span>
                                            <span class="font-bold"><?php echo $player['height']; ?> cm</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($age): ?>
                                        <div class="flex justify-between pb-2 border-b border-white/20">
                                            <span>Umur:</span>
                                            <span class="font-bold"><?php echo $age; ?> tahun</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($player['birth_date']): ?>
                                        <div class="flex justify-between pb-2 border-b border-white/20">
                                            <span>Lahir:</span>
                                            <span class="font-bold"><?php echo formatDateIndo($player['birth_date']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($player['previous_club'])): ?>
                                        <div class="flex justify-between pb-2 border-b border-white/20">
                                            <span>Klub Sebelumnya:</span>
                                            <span class="font-bold"><?php echo htmlspecialchars($player['previous_club']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                   
                                    
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="mt-4 grid grid-cols-3 gap-2">
                                    <a href="edit.php?id=<?php echo $player['id']; ?>" class="px-3 py-2 bg-white/20 hover:bg-white/30 rounded text-center text-xs font-semibold transition" onclick="event.stopPropagation()">
                                        ✏️ Edit
                                    </a>
                                    <a href="?toggle_active=<?php echo $player['id']; ?>" class="px-3 py-2 bg-white/20 hover:bg-white/30 rounded text-center text-xs font-semibold transition" onclick="event.stopPropagation()">
                                        <?php echo $player['is_active'] ? '❌' : '✅'; ?>
                                    </a>
                                    <a href="?delete=<?php echo $player['id']; ?>" onclick="event.stopPropagation(); return confirm('Yakin hapus pemain ini?')" class="px-3 py-2 bg-red-600/80 hover:bg-red-700 rounded text-center text-xs font-semibold transition">
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
            <div class="text-6xl mb-4">👥</div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Belum Ada Pemain</h3>
            <p class="text-gray-600 mb-6">Mulai tambahkan data pemain</p>
            <a href="create.php" class="inline-block px-6 py-3 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition">
                ➕ Tambah Pemain Pertama
            </a>
        </div>
    <?php endif; ?>

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


