<?php
/**
 * Manchester Side - Edit Player
 */
require_once '../../includes/config.php';

if (!isAdminLoggedIn()) {
    redirect('../login.php');
}

$db = getDB();
$admin = getCurrentAdmin();
$errors = [];

// Get player ID
$player_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($player_id === 0) {
    setFlashMessage('error', 'ID pemain tidak valid');
    redirect('index.php');
}

// Delete feature removed - use index page to delete

// Get player data
$stmt = $db->prepare("SELECT p.*, c.name as club_name, c.code as club_code FROM players p JOIN clubs c ON p.club_id = c.id WHERE p.id = ?");
$stmt->bind_param("i", $player_id);
$stmt->execute();
$player = $stmt->get_result()->fetch_assoc();

if (!$player) {
    setFlashMessage('error', 'Pemain tidak ditemukan');
    redirect('index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_player'])) {
    $name = trim($_POST['name'] ?? '');
    $club_id = (int)($_POST['club_id'] ?? 0);
    $position = trim($_POST['position'] ?? '');
    $jersey_number = (int)($_POST['jersey_number'] ?? 0);
    $nationality = trim($_POST['nationality'] ?? '');
    $birth_date = $_POST['birth_date'] ?? null;
    $height = !empty($_POST['height']) ? (int)$_POST['height'] : null;
    $weight = !empty($_POST['weight']) ? (int)$_POST['weight'] : null;
    $joined_date = $_POST['joined_date'] ?? null;
    $previous_club = trim($_POST['previous_club'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Nama pemain wajib diisi';
    }
    
    if ($club_id === 0) {
        $errors[] = 'Klub wajib dipilih';
    }
    
    if (empty($position)) {
        $errors[] = 'Posisi wajib dipilih';
    }
    
    if ($jersey_number === 0) {
        $errors[] = 'Nomor punggung wajib diisi';
    }
    
    if (empty($nationality)) {
        $errors[] = 'Kebangsaan wajib diisi';
    }
    // Check if jersey number already exists (excluding current player)
    if (empty($errors)) {
        $check_jersey = $db->prepare("SELECT id FROM players WHERE club_id = ? AND jersey_number = ? AND id != ?");
        $check_jersey->bind_param("iii", $club_id, $jersey_number, $player_id);
        $check_jersey->execute();
        if ($check_jersey->get_result()->num_rows > 0) {
            $errors[] = 'Nomor punggung sudah digunakan di klub ini';
        }
    }
    
    // Handle photo upload
    $photo_url = $player['photo_url']; // Keep existing photo
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        // Delete old photo if exists
        if (!empty($player['photo_url']) && file_exists('../../' . $player['photo_url'])) {
            unlink('../../' . $player['photo_url']);
        }
        
        $upload_result = uploadImage($_FILES['photo'], 'players');
        if ($upload_result['success']) {
            $photo_url = 'includes/uploads/players/' . $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }
    
    if (empty($errors)) {
        // Prepare date values for binding
        $birth_date_value = !empty($birth_date) ? $birth_date : null;
        $joined_date_value = !empty($joined_date) ? $joined_date : null;
        
        $update_stmt = $db->prepare("UPDATE players SET name = ?, club_id = ?, position = ?, jersey_number = ?, nationality = ?, birth_date = ?, height = ?, weight = ?, joined_date = ?, previous_club = ?, is_active = ?, photo_url = ? WHERE id = ?");
        
        $update_stmt->bind_param("sisississsisi", $name, $club_id, $position, $jersey_number, $nationality, 
            $birth_date_value, $height, $weight, $joined_date_value, $previous_club, $is_active, $photo_url, $player_id);
        
        if ($update_stmt->execute()) {
            setFlashMessage('success', 'Data pemain berhasil diperbarui!');
            redirect('edit.php?id=' . $player_id);
        } else {
            $errors[] = 'Gagal memperbarui data pemain: ' . $db->error;
        }
    }
    
    // Reload player data
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT p.*, c.name as club_name, c.code as club_code FROM players p JOIN clubs c ON p.club_id = c.id WHERE p.id = ?");
        $stmt->bind_param("i", $player_id);
        $stmt->execute();
        $player = $stmt->get_result()->fetch_assoc();
    }
}

// Get clubs - ONLY Manchester City and Manchester United
$clubs = $db->query("SELECT id, name, code FROM clubs WHERE code IN ('CITY', 'UNITED') ORDER BY name");

$flash = getFlashMessage();

$page_title = "Edit Pemain";
include '../includes/header.php';
?>

<div class="p-8">
    
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Edit Pemain</h1>
            <p class="text-gray-600 mt-1">Perbarui data: <span class="font-semibold"><?php echo $player['name']; ?></span></p>
        </div>
        <a href="index.php" class="px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition">
            ← Kembali
        </a>
    </div>

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

                <!-- Player Info Card -->
                <div class="bg-gradient-to-r from-<?php echo $player['club_code'] === 'CITY' ? 'city-blue' : 'united-red'; ?> to-<?php echo $player['club_code'] === 'CITY' ? 'city-navy' : 'red'; ?>-900 text-white rounded-xl shadow-xl p-6 mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-6">
                            <div class="text-6xl font-black">
                                <?php echo $player['jersey_number']; ?>
                            </div>
                            <div>
                                <h2 class="text-3xl font-bold mb-1"><?php echo $player['name']; ?></h2>
                                <p class="text-lg"><?php echo $player['position']; ?> • <?php echo $player['club_name']; ?></p>
                                <p class="text-sm opacity-90 mt-1">🌍 <?php echo $player['nationality']; ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm mb-1">ID Pemain</p>
                            <p class="text-3xl font-bold">#<?php echo $player['id']; ?></p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                    
                    <div class="grid lg:grid-cols-3 gap-6">
                        
                        <!-- Main Info -->
                        <div class="lg:col-span-2 space-y-6">
                            
                            <!-- Basic Info Card -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                    <span class="text-2xl mr-2">👤</span>
                                    Informasi Dasar
                                </h3>
                                
                                <div class="grid md:grid-cols-2 gap-4">
                                    <!-- Name -->
                                    <div class="md:col-span-2">
                                        <label for="name" class="block text-sm font-bold text-gray-700 mb-2">
                                            Nama Lengkap <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="name" 
                                            name="name" 
                                            value="<?php echo htmlspecialchars($player['name']); ?>"
                                            required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                        >
                                    </div>

                                    <!-- Jersey Number -->
                                    <div>
                                        <label for="jersey_number" class="block text-sm font-bold text-gray-700 mb-2">
                                            Nomor Punggung <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            id="jersey_number" 
                                            name="jersey_number" 
                                            value="<?php echo $player['jersey_number']; ?>"
                                            required
                                            min="1"
                                            max="99"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                        >
                                    </div>

                                    <!-- Nationality -->
                                    <div>
                                        <label for="nationality" class="block text-sm font-bold text-gray-700 mb-2">
                                            Kebangsaan <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="nationality" 
                                            name="nationality" 
                                            value="<?php echo htmlspecialchars($player['nationality']); ?>"
                                            required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                        >
                                    </div>

                                    <!-- Birth Date -->
                                    <div>
                                        <label for="birth_date" class="block text-sm font-bold text-gray-700 mb-2">
                                            Tanggal Lahir
                                        </label>
                                        <input 
                                            type="date" 
                                            id="birth_date" 
                                            name="birth_date" 
                                            value="<?php echo $player['birth_date']; ?>"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                        >
                                    </div>

                                    <!-- Joined Date -->
                                    <div>
                                        <label for="joined_date" class="block text-sm font-bold text-gray-700 mb-2">
                                            Tanggal Bergabung
                                        </label>
                                        <input 
                                            type="date" 
                                            id="joined_date" 
                                            name="joined_date" 
                                            value="<?php echo $player['joined_date']; ?>"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                        >
                                    </div>

                                    <!-- Previous Club -->
                                    <div>
                                        <label for="previous_club" class="block text-sm font-bold text-gray-700 mb-2">
                                            Klub Sebelumnya <span class="text-gray-400 font-normal">(Opsional)</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="previous_club" 
                                            name="previous_club" 
                                            value="<?php echo htmlspecialchars($player['previous_club'] ?? ''); ?>"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                            placeholder="Contoh: Barcelona"
                                        >
                                    </div>

                                    <!-- PHOTO UPLOAD -->
                                    <div class="md:col-span-2">
                                        <label for="photo" class="block text-sm font-bold text-gray-700 mb-2">
                                            📷 Foto Pemain
                                        </label>
                                        <div class="flex gap-4 items-start">
                                            <div class="flex-1">
                                                <label class="cursor-pointer">
                                                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-city-blue transition text-center">
                                                        <div class="text-4xl mb-2">📸</div>
                                                        <p class="text-sm font-semibold text-gray-700">Klik untuk upload foto baru</p>
                                                        <p class="text-xs text-gray-500 mt-1">JPG, PNG, WebP (Max 5MB)</p>
                                                    </div>
                                                    <input 
                                                        type="file" 
                                                        id="photo" 
                                                        name="photo" 
                                                        accept="image/*"
                                                        class="hidden"
                                                        onchange="previewPlayerPhoto(this)"
                                                    >
                                                </label>
                                            </div>
                                            <div id="photoPreview" class="w-40 h-40 border-2 border-gray-300 rounded-lg overflow-hidden <?php echo empty($player['photo_url']) ? 'hidden' : ''; ?>">
                                                <img id="previewImage" src="<?php echo !empty($player['photo_url']) ? '../../' . $player['photo_url'] : ''; ?>" alt="Preview" class="w-full h-full object-cover">
                                            </div>
                                        </div>
                                        <?php if (!empty($player['photo_url'])): ?>
                                            <p class="text-xs text-gray-500 mt-2">
                                                ✅ Foto saat ini: <?php echo basename($player['photo_url']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Physical Stats -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                    <span class="text-2xl mr-2">📏</span>
                                    Data Fisik
                                </h3>
                                
                                <div class="grid md:grid-cols-2 gap-4">
                                    <!-- Height -->
                                    <div>
                                        <label for="height" class="block text-sm font-bold text-gray-700 mb-2">
                                            Tinggi Badan (cm)
                                        </label>
                                        <input 
                                            type="number" 
                                            id="height" 
                                            name="height" 
                                            value="<?php echo $player['height']; ?>"
                                            min="150"
                                            max="220"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                        >
                                    </div>

                                    <!-- Weight -->
                                    <div>
                                        <label for="weight" class="block text-sm font-bold text-gray-700 mb-2">
                                            Berat Badan (kg)
                                        </label>
                                        <input 
                                            type="number" 
                                            id="weight" 
                                            name="weight" 
                                            value="<?php echo $player['weight']; ?>"
                                            min="50"
                                            max="120"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                        >
                                    </div>
                                </div>
                            </div>


                        </div>

                        <!-- Sidebar Settings -->
                        <div class="space-y-6">
                            
                            <!-- Club Selection -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                                    <span class="text-xl mr-2">⚽</span>
                                    Klub <span class="text-red-500 ml-1">*</span>
                                </h3>
                                
                                <select 
                                    name="club_id" 
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                >
                                    <?php while ($club = $clubs->fetch_assoc()): ?>
                                        <option value="<?php echo $club['id']; ?>" <?php echo ($player['club_id'] == $club['id']) ? 'selected' : ''; ?>>
                                            <?php echo $club['code'] === 'CITY' ? '🔵' : '🔴'; ?> <?php echo $club['name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Position -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                                    <span class="text-xl mr-2">📍</span>
                                    Posisi <span class="text-red-500 ml-1">*</span>
                                </h3>
                                
                                <select 
                                    name="position" 
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                >
                                    <option value="Goalkeeper" <?php echo $player['position'] === 'Goalkeeper' ? 'selected' : ''; ?>>🧤 Goalkeeper</option>
                                    <option value="Defender" <?php echo $player['position'] === 'Defender' ? 'selected' : ''; ?>>🛡️ Defender</option>
                                    <option value="Midfielder" <?php echo $player['position'] === 'Midfielder' ? 'selected' : ''; ?>>⚙️ Midfielder</option>
                                    <option value="Forward" <?php echo $player['position'] === 'Forward' ? 'selected' : ''; ?>>⚽ Forward</option>
                                </select>
                            </div>

                            <!-- Status -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                                    <span class="text-xl mr-2">✅</span>
                                    Status
                                </h3>
                                
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        name="is_active" 
                                        value="1"
                                        <?php echo $player['is_active'] ? 'checked' : ''; ?>
                                        class="w-5 h-5 text-city-blue border-gray-300 rounded focus:ring-city-blue"
                                    >
                                    <div>
                                        <span class="font-semibold text-gray-900">Pemain Aktif</span>
                                        <p class="text-xs text-gray-500">Tampilkan di website</p>
                                    </div>
                                </label>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="bg-white rounded-xl shadow-lg p-6 space-y-3">
                                <button 
                                    type="submit"
                                    name="update_player"
                                    class="w-full py-3 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition"
                                >
                                    💾 Update Pemain
                                </button>
                                
                                <a 
                                    href="index.php"
                                    class="block w-full py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition text-center"
                                >
                                    ❌ Batal
                                </a>


                            </div>

                        </div>

                    </div>

                </form>

</div>

<script>
function previewPlayerPhoto(input) {
    const preview = document.getElementById('photoPreview');
    const previewImage = document.getElementById('previewImage');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            preview.classList.remove('hidden');
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

