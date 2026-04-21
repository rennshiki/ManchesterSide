<?php
/**
 * Manchester Side - Edit Staff with Photo Upload
 */
require_once '../../includes/config.php';

if (!isAdminLoggedIn()) {
    redirect('../login.php');
}

$db = getDB();
$admin = getCurrentAdmin();
$errors = [];

// Get staff ID
$staff_id = (int)($_GET['id'] ?? 0);

// Get staff data
$stmt = $db->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();

if (!$staff) {
    setFlashMessage('error', 'Staff tidak ditemukan');
    redirect('index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $club_id = (int)$_POST['club_id'];
    $nationality = trim($_POST['nationality'] ?? '');
    $birth_date = $_POST['birth_date'] ?? null;
    $join_date = $_POST['join_date'] ?? null;
    $previous_club = trim($_POST['previous_club'] ?? '');
    $jersey_number = !empty($_POST['jersey_number']) ? (int)$_POST['jersey_number'] : null;
    $height = !empty($_POST['height']) ? (int)$_POST['height'] : null;
    $weight = !empty($_POST['weight']) ? (int)$_POST['weight'] : null;
    $biography = trim($_POST['biography'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Nama staff wajib diisi';
    }
    
    if (empty($role)) {
        $errors[] = 'Role wajib diisi';
    }
    
    if (empty($club_id)) {
        $errors[] = 'Klub wajib dipilih';
    }
    
    // Handle photo upload
    $photo_url = $staff['photo_url'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        // Delete old photo
        if ($photo_url && file_exists('../../' . $photo_url)) {
            unlink('../../' . $photo_url);
        }
        
        $upload_result = uploadImage($_FILES['photo'], 'staff');
        if ($upload_result['success']) {
            $photo_url = 'includes/uploads/staff/' . $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }
    
    if (empty($errors)) {
        // Check which columns exist in the staff table
        $columns_check = $db->query("SHOW COLUMNS FROM staff");
        $existing_columns = [];
        while ($col = $columns_check->fetch_assoc()) {
            $existing_columns[] = $col['Field'];
        }
        
        // Build dynamic UPDATE query based on existing columns
        $update_fields = ['name = ?', 'role = ?', 'club_id = ?', 'nationality = ?'];
        $bind_types = 'ssis';
        $bind_values = [$name, $role, $club_id, $nationality];
        

        
        if (in_array('jersey_number', $existing_columns)) {
            $update_fields[] = 'jersey_number = ?';
            $bind_types .= 'i';
            $bind_values[] = $jersey_number;
        }
        
        if (in_array('birth_date', $existing_columns)) {
            $update_fields[] = 'birth_date = ?';
            $bind_types .= 's';
            $bind_values[] = !empty($birth_date) ? $birth_date : null;
        }
        
        if (in_array('join_date', $existing_columns)) {
            $update_fields[] = 'join_date = ?';
            $bind_types .= 's';
            $bind_values[] = !empty($join_date) ? $join_date : null;
        }
        
        if (in_array('previous_club', $existing_columns)) {
            $update_fields[] = 'previous_club = ?';
            $bind_types .= 's';
            $bind_values[] = $previous_club;
        }
        
        if (in_array('height', $existing_columns)) {
            $update_fields[] = 'height = ?';
            $bind_types .= 'i';
            $bind_values[] = $height;
        }
        
        if (in_array('weight', $existing_columns)) {
            $update_fields[] = 'weight = ?';
            $bind_types .= 'i';
            $bind_values[] = $weight;
        }
        
        if (in_array('biography', $existing_columns)) {
            $update_fields[] = 'biography = ?';
            $bind_types .= 's';
            $bind_values[] = $biography;
        }
        
        if (in_array('photo_url', $existing_columns)) {
            $update_fields[] = 'photo_url = ?';
            $bind_types .= 's';
            $bind_values[] = $photo_url;
        }
        
        if (in_array('is_active', $existing_columns)) {
            $update_fields[] = 'is_active = ?';
            $bind_types .= 'i';
            $bind_values[] = $is_active;
        }
        
        $bind_types .= 'i';
        $bind_values[] = $staff_id;
        
        $sql = "UPDATE staff SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param($bind_types, ...$bind_values);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Staff berhasil diupdate!');
            redirect('index.php');
        } else {
            $errors[] = 'Gagal mengupdate staff: ' . $stmt->error;
        }
    }
}

// Get clubs - ONLY Manchester City and Manchester United
$clubs = $db->query("SELECT id, name, code FROM clubs WHERE code IN ('CITY', 'UNITED') ORDER BY name");

$flash = getFlashMessage();
$page_title = 'Edit Staff';
include '../includes/header.php';
?>

<div class="p-8">
    
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">✏️ Edit Staff</h1>
                <p class="text-gray-600 mt-1">Update data staff</p>
            </div>
            <a href="index.php" class="px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition">
                ← Kembali
            </a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="mb-6 bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-50 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-200 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-800 px-4 py-3 rounded-lg">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="" enctype="multipart/form-data" class="bg-white rounded-xl shadow-lg p-8">
        
        <div class="grid md:grid-cols-2 gap-6">
            
            <!-- Name -->
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap *</label>
                <input 
                    type="text" 
                    name="name" 
                    value="<?php echo htmlspecialchars($staff['name'] ?? ''); ?>"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue"
                >
            </div>

            <!-- Role -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Role *</label>
                <select 
                    name="role" 
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue"
                >
                    <option value="">Pilih Role</option>
                    <option value="Manajer" <?php echo ($staff['role'] === 'Manajer') ? 'selected' : ''; ?>>Manajer</option>
                    <option value="Asisten Manajer" <?php echo ($staff['role'] === 'Asisten Manajer') ? 'selected' : ''; ?>>Asisten Manajer</option>
                </select>
            </div>


            <!-- Club -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Klub *</label>
                <select name="club_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue">
                    <option value="">Pilih Klub</option>
                    <?php while ($club = $clubs->fetch_assoc()): ?>
                        <option value="<?php echo $club['id']; ?>" <?php echo ($staff['club_id'] == $club['id']) ? 'selected' : ''; ?>>
                            <?php echo $club['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Nationality -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Kebangsaan</label>
                <input 
                    type="text" 
                    name="nationality" 
                    value="<?php echo htmlspecialchars($staff['nationality'] ?? ''); ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue"
                >
            </div>

            <!-- Birth Date -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Lahir</label>
                <input 
                    type="date" 
                    name="birth_date" 
                    value="<?php echo $staff['birth_date'] ?? ''; ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue"
                >
            </div>

            <!-- Join Date -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Bergabung</label>
                <input 
                    type="date" 
                    name="join_date" 
                    value="<?php echo $staff['join_date'] ?? ''; ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue"
                >
            </div>

            <!-- Current Photo -->
            <?php if ($staff['photo_url']): ?>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Foto Saat Ini</label>
                    <img src="../../<?php echo $staff['photo_url']; ?>" alt="<?php echo $staff['name']; ?>" class="w-32 h-32 object-cover rounded-lg border-2 border-gray-300">
                </div>
            <?php endif; ?>

            <!-- Photo Upload -->
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Upload Foto Baru</label>
                <input 
                    type="file" 
                    name="photo" 
                    accept="image/*"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue"
                    onchange="previewImage(this)"
                >
                <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, WEBP. Max 5MB. Kosongkan jika tidak ingin mengubah foto.</p>
                
                <!-- Image Preview -->
                <div id="imagePreview" class="mt-4 hidden">
                    <p class="text-sm font-semibold text-gray-700 mb-2">Preview Foto Baru:</p>
                    <img id="preview" src="" alt="Preview" class="w-32 h-32 object-cover rounded-lg border-2 border-gray-300">
                </div>
            </div>

            <!-- Is Active -->
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input 
                        type="checkbox" 
                        name="is_active" 
                        value="1"
                        <?php echo $staff['is_active'] ? 'checked' : ''; ?>
                        class="w-5 h-5 text-city-blue border-gray-300 rounded focus:ring-city-blue"
                    >
                    <span class="ml-3 text-sm font-semibold text-gray-700">Staff Aktif</span>
                </label>
            </div>

        </div>

        <!-- Submit Buttons -->
        <div class="flex gap-3 mt-8 pt-6 border-t border-gray-200">
            <button 
                type="submit"
                class="flex-1 py-3 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition"
            >
                💾 Update Staff
            </button>
            <a 
                href="index.php"
                class="px-8 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition"
            >
                ❌ Batal
            </a>
        </div>

    </form>

</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('preview');
    const previewContainer = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.classList.remove('hidden');
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>


