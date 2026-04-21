<?php
/**
 * Manchester Side - Create New Player (WITH PHOTO UPLOAD)
 */
require_once '../../includes/config.php';

if (!isAdminLoggedIn()) {
    redirect('../login.php');
}

$db = getDB();
$admin = getCurrentAdmin();
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $club_id = (int)($_POST['club_id'] ?? 0);
    $position = trim($_POST['position'] ?? '');
    $jersey_number = (int)($_POST['jersey_number'] ?? 0);
    $nationality = trim($_POST['nationality'] ?? '');
    $birth_date = $_POST['birth_date'] ?? '';
    $height = !empty($_POST['height']) ? (int)$_POST['height'] : null;
    $weight = !empty($_POST['weight']) ? (int)$_POST['weight'] : null;
    $joined_date = $_POST['joined_date'] ?? '';
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
    
    // Check if jersey number already exists for this club
    if (empty($errors)) {
        $check_jersey = $db->prepare("SELECT id FROM players WHERE club_id = ? AND jersey_number = ?");
        $check_jersey->bind_param("ii", $club_id, $jersey_number);
        $check_jersey->execute();
        if ($check_jersey->get_result()->num_rows > 0) {
            $errors[] = 'Nomor punggung sudah digunakan di klub ini';
        }
    }
    
    // Handle photo upload
    $photo_url = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
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
        
        $stmt = $db->prepare("INSERT INTO players (name, club_id, position, jersey_number, nationality, birth_date, height, weight, joined_date, previous_club, is_active, photo_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("sisississsis", $name, $club_id, $position, $jersey_number, $nationality, 
            $birth_date_value, $height, $weight, $joined_date_value, $previous_club, $is_active, $photo_url);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Pemain berhasil ditambahkan!');
            redirect('index.php');
        } else {
            $errors[] = 'Gagal menyimpan data pemain';
        }
    }
}

// Get clubs - ONLY Manchester City and Manchester United
$clubs = $db->query("SELECT id, name, code FROM clubs WHERE code IN ('CITY', 'UNITED') ORDER BY name");

$page_title = "Tambah Pemain";
include '../includes/header.php';
?>

<div class="p-8">
    
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Tambah Pemain Baru</h1>
            <p class="text-gray-600 mt-1">Masukkan data pemain baru</p>
        </div>
        <a href="index.php" class="px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition">
            ← Kembali
        </a>
    </div>

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
                                            value="<?php echo $_POST['name'] ?? ''; ?>"
                                            required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                            placeholder="Contoh: Erling Haaland"
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
                                            value="<?php echo $_POST['jersey_number'] ?? ''; ?>"
                                            required
                                            min="1"
                                            max="99"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                            placeholder="9"
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
                                            value="<?php echo $_POST['nationality'] ?? ''; ?>"
                                            required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                            placeholder="Norway"
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
                                            value="<?php echo $_POST['birth_date'] ?? ''; ?>"
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
                                            value="<?php echo $_POST['joined_date'] ?? ''; ?>"
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
                                            value="<?php echo $_POST['previous_club'] ?? ''; ?>"
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
                                                        <p class="text-sm font-semibold text-gray-700">Klik untuk upload foto</p>
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
                                            <div id="photoPreview" class="hidden w-40 h-40 border-2 border-gray-300 rounded-lg overflow-hidden">
                                                <img id="previewImage" src="" alt="Preview" class="w-full h-full object-cover">
                                            </div>
                                        </div>
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
                                            value="<?php echo $_POST['height'] ?? ''; ?>"
                                            min="150"
                                            max="220"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                            placeholder="194"
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
                                            value="<?php echo $_POST['weight'] ?? ''; ?>"
                                            min="50"
                                            max="120"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                            placeholder="88"
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
                                    <option value="">Pilih Klub</option>
                                    <?php while ($club = $clubs->fetch_assoc()): ?>
                                        <option value="<?php echo $club['id']; ?>" <?php echo (isset($_POST['club_id']) && $_POST['club_id'] == $club['id']) ? 'selected' : ''; ?>>
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
                                    <option value="">Pilih Posisi</option>
                                    <option value="Goalkeeper" <?php echo (($_POST['position'] ?? '') === 'Goalkeeper') ? 'selected' : ''; ?>>🧤 Goalkeeper</option>
                                    <option value="Defender" <?php echo (($_POST['position'] ?? '') === 'Defender') ? 'selected' : ''; ?>>🛡️ Defender</option>
                                    <option value="Midfielder" <?php echo (($_POST['position'] ?? '') === 'Midfielder') ? 'selected' : ''; ?>>⚙️ Midfielder</option>
                                    <option value="Forward" <?php echo (($_POST['position'] ?? '') === 'Forward') ? 'selected' : ''; ?>>⚽ Forward</option>
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
                                        <?php echo (!isset($_POST['name']) || isset($_POST['is_active'])) ? 'checked' : ''; ?>
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
                                    class="w-full py-3 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition"
                                >
                                    💾 Simpan Pemain
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

