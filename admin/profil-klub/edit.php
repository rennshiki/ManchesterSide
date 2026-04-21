<?php
/**
 * Manchester Side - Edit Profil Klub (Identitas, Sejarah, Prestasi, Manajemen)
 * LENGKAP dengan Trophy Management System
 */
require_once '../../includes/config.php';

if (!isAdminLoggedIn()) {
    redirect('../login.php');
}

$db = getDB();
$admin = getCurrentAdmin();
$errors = [];

// Get club ID
$club_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($club_id === 0) {
    setFlashMessage('error', 'ID klub tidak valid');
    redirect('index.php');
}

// Get club data - ONLY Manchester City and Manchester United
$stmt = $db->prepare("SELECT * FROM clubs WHERE id = ? AND code IN ('CITY', 'UNITED')");
$stmt->bind_param("i", $club_id);
$stmt->execute();
$club = $stmt->get_result()->fetch_assoc();

if (!$club) {
    setFlashMessage('error', 'Klub tidak ditemukan atau tidak dapat diedit. Hanya Manchester City dan Manchester United yang dapat dikelola.');
    redirect('index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_club'])) {
    $full_name = lightSanitize($_POST['full_name'] ?? '');
    $nickname = lightSanitize($_POST['nickname'] ?? '');
    $stadium_name = lightSanitize($_POST['stadium_name'] ?? '');
    $stadium_location = lightSanitize($_POST['stadium_location'] ?? '');
    $stadium_capacity = (int)($_POST['stadium_capacity'] ?? 0);
    $founded_year = (int)($_POST['founded_year'] ?? 0);
    $history = lightSanitize($_POST['history'] ?? '');
    $achievements = lightSanitize($_POST['achievements'] ?? '');
    $owner = lightSanitize($_POST['owner'] ?? '');
    $chairman = lightSanitize($_POST['chairman'] ?? '');
    $board_members = lightSanitize($_POST['board_members'] ?? '');
    $color_primary = lightSanitize($_POST['color_primary'] ?? '');
    $color_secondary = lightSanitize($_POST['color_secondary'] ?? '');
    
    // Validation
    if (empty($full_name)) {
        $errors[] = 'Nama lengkap klub wajib diisi';
    }
    
    if ($founded_year < 1800 || $founded_year > date('Y')) {
        $errors[] = 'Tahun berdiri tidak valid';
    }
    
    if (empty($errors)) {
        $update_stmt = $db->prepare("UPDATE clubs SET 
            full_name = ?, 
            nickname = ?,
            stadium_name = ?, 
            stadium_location = ?, 
            stadium_capacity = ?,
            founded_year = ?,
            history = ?, 
            achievements = ?,
            owner = ?,
            chairman = ?,
            board_members = ?,
            color_primary = ?, 
            color_secondary = ?
            WHERE id = ?");
        
        $update_stmt->bind_param("ssssiisssssssi", 
            $full_name, $nickname, $stadium_name, $stadium_location, $stadium_capacity,
            $founded_year, $history, $achievements, $owner, $chairman, $board_members,
            $color_primary, $color_secondary, $club_id
        );
        
        if ($update_stmt->execute()) {
            setFlashMessage('success', 'Profil klub berhasil diperbarui!');
            
            // Reload club data
            $stmt = $db->prepare("SELECT * FROM clubs WHERE id = ?");
            $stmt->bind_param("i", $club_id);
            $stmt->execute();
            $club = $stmt->get_result()->fetch_assoc();
        } else {
            $errors[] = 'Gagal memperbarui profil klub: ' . $db->error;
        }
    }
}

// Handle trophy management with file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trophy'])) {
    $trophy_name = lightSanitize($_POST['trophy_name'] ?? '');
    $trophy_image = '';
    
    // Handle file upload
    if (isset($_FILES['trophy_photo']) && $_FILES['trophy_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../includes/uploads/trophies/';
        
        // Create directory if not exists
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['trophy_photo']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = 'trophy_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['trophy_photo']['tmp_name'], $upload_path)) {
                $trophy_image = 'includes/uploads/trophies/' . $new_filename;
            }
        }
    } else {
        // Fallback to URL if provided
        $trophy_image = lightSanitize($_POST['trophy_image_url'] ?? '');
    }
    $trophy_years = lightSanitize($_POST['trophy_years'] ?? '');
    
    if (!empty($trophy_name) && !empty($trophy_years)) {
        // Parse years (comma separated)
        $years_array = array_map('trim', explode(',', $trophy_years));
        $count = count($years_array);
        
        $trophy_stmt = $db->prepare("INSERT INTO club_trophies (club_id, trophy_name, trophy_image, years_won, win_count) VALUES (?, ?, ?, ?, ?)");
        $trophy_stmt->bind_param("isssi", $club_id, $trophy_name, $trophy_image, $trophy_years, $count);
        
        if ($trophy_stmt->execute()) {
            setFlashMessage('success', 'Piala berhasil ditambahkan!');
            redirect('edit.php?id=' . $club_id . '#tab-prestasi');
        }
    }
}

// Handle trophy deletion
if (isset($_GET['delete_trophy'])) {
    $trophy_id = (int)$_GET['delete_trophy'];
    
    // Get trophy image to delete file
    $trophy_query = $db->prepare("SELECT trophy_image FROM club_trophies WHERE id = ? AND club_id = ?");
    $trophy_query->bind_param("ii", $trophy_id, $club_id);
    $trophy_query->execute();
    $trophy_data = $trophy_query->get_result()->fetch_assoc();
    
    // Delete image file if exists
    if ($trophy_data && !empty($trophy_data['trophy_image']) && file_exists('../../' . $trophy_data['trophy_image'])) {
        unlink('../../' . $trophy_data['trophy_image']);
    }
    
    // Delete trophy from database
    $delete_stmt = $db->prepare("DELETE FROM club_trophies WHERE id = ? AND club_id = ?");
    $delete_stmt->bind_param("ii", $trophy_id, $club_id);
    
    if ($delete_stmt->execute()) {
        setFlashMessage('success', 'Piala berhasil dihapus!');
    } else {
        setFlashMessage('error', 'Gagal menghapus piala');
    }
    
    redirect('edit.php?id=' . $club_id . '#tab-prestasi');
}

// Get trophies
$trophies_query = $db->prepare("SELECT * FROM club_trophies WHERE club_id = ? ORDER BY win_count DESC, trophy_name");
$trophies_query->bind_param("i", $club_id);
$trophies_query->execute();
$trophies_result = $trophies_query->get_result();

$flash = getFlashMessage();

$logo_url = $club['code'] === 'CITY' 
    ? 'https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg'
    : 'https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg';

$gradient_colors = $club['code'] === 'CITY' 
    ? 'from-city-blue to-city-navy'
    : 'from-united-red to-red-900';

$page_title = 'Edit Profil ' . $club['name'];
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
        
        .tab-button.active {
            background: linear-gradient(135deg, 
                <?php echo $club['code'] === 'CITY' ? '#6CABDD' : '#DA291C'; ?> 0%, 
                <?php echo $club['code'] === 'CITY' ? '#1C2C5B' : '#8B0000'; ?> 100%);
            color: white;
        }
        
        .trophy-card {
            transition: all 0.3s ease;
        }
        
        .trophy-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="flex h-screen">
        
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <img src="<?php echo $logo_url; ?>" alt="<?php echo $club['name']; ?>" class="w-16 h-16 object-contain">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Edit Profil <?php echo $club['name']; ?></h1>
                            <p class="text-gray-600 mt-1">Kelola identitas, sejarah, prestasi & manajemen klub</p>
                        </div>
                    </div>
                    <a href="index.php" class="px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition">
                        ← Kembali
                    </a>
                </div>
            </header>

            <div class="p-6">

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

                <!-- Tabs -->
                <div class="mb-6">
                    <div class="flex gap-2">
                        <button onclick="switchTab('identitas')" class="tab-button active px-6 py-3 rounded-lg font-bold transition" data-tab="identitas">
                            🏛️ Identitas Klub
                        </button>
                        <button onclick="switchTab('sejarah')" class="tab-button px-6 py-3 bg-gray-200 rounded-lg font-bold transition" data-tab="sejarah">
                            📜 Sejarah
                        </button>
                        <button onclick="switchTab('prestasi')" class="tab-button px-6 py-3 bg-gray-200 rounded-lg font-bold transition" data-tab="prestasi">
                            🏆 Prestasi & Piala
                        </button>
                        <button onclick="switchTab('manajemen')" class="tab-button px-6 py-3 bg-gray-200 rounded-lg font-bold transition" data-tab="manajemen">
                            👔 Manajemen
                        </button>
                    </div>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">

<!-- TAB: IDENTITAS KLUB -->
<div id="tab-identitas" class="tab-content">
    <div class="bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
            <span class="text-3xl mr-3">🏛️</span>
            Identitas Klub
        </h2>
        
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Full Name -->
            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nama Resmi Klub *</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($club['full_name']); ?>" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue">
            </div>

            <!-- Nickname -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Julukan</label>
                <input type="text" name="nickname" value="<?php echo htmlspecialchars($club['nickname'] ?? ''); ?>" placeholder="Contoh: The Citizens, Red Devils" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue">
            </div>

            <!-- Founded Year -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Tahun Berdiri *</label>
                <input type="number" name="founded_year" value="<?php echo $club['founded_year']; ?>" required min="1800" max="<?php echo date('Y'); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue">
            </div>

            <!-- Stadium Name -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Nama Stadion *</label>
                <input type="text" name="stadium_name" value="<?php echo htmlspecialchars($club['stadium_name']); ?>" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue">
            </div>

            <!-- Stadium Location -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Lokasi Stadion</label>
                <input type="text" name="stadium_location" value="<?php echo htmlspecialchars($club['stadium_location']); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue">
            </div>

            <!-- Stadium Capacity -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Kapasitas Stadion</label>
                <input type="number" name="stadium_capacity" value="<?php echo $club['stadium_capacity']; ?>" min="0" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue">
            </div>



            <!-- Color Primary -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Warna Utama</label>
                <div class="flex gap-2">
                    <input type="color" name="color_primary" value="<?php echo $club['color_primary']; ?>" class="h-12 w-20 border border-gray-300 rounded">
                    <input type="text" value="<?php echo $club['color_primary']; ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                </div>
            </div>

            <!-- Color Secondary -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Warna Sekunder</label>
                <div class="flex gap-2">
                    <input type="color" name="color_secondary" value="<?php echo $club['color_secondary']; ?>" class="h-12 w-20 border border-gray-300 rounded">
                    <input type="text" value="<?php echo $club['color_secondary']; ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TAB: SEJARAH -->
<div id="tab-sejarah" class="tab-content" style="display:none;">
    <div class="bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
            <span class="text-3xl mr-3">📜</span>
            Sejarah Klub
        </h2>
        
        <textarea name="history" rows="15" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue resize-none"><?php echo htmlspecialchars($club['history']); ?></textarea>
        
        <p class="text-sm text-gray-600 mt-3">
            💡 Tip: Tulis kronologi sejarah klub, momen penting, transformasi, dan pencapaian bersejarah. Pisahkan paragraf dengan enter.
        </p>
    </div>
</div>

<!-- TAB: PRESTASI & PIALA -->
<div id="tab-prestasi" class="tab-content" style="display:none;">
    
    <!-- General Achievements Text -->
    <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
            <span class="text-3xl mr-3">🏆</span>
            Prestasi Umum
        </h2>
        
        <textarea name="achievements" rows="8" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue resize-none"><?php echo htmlspecialchars($club['achievements']); ?></textarea>
        
        <p class="text-sm text-gray-600 mt-3">
            💡 Tulis ringkasan prestasi umum, rekor, dan pencapaian lainnya
        </p>
    </div>

    <!-- Trophy Management -->
    <div class="bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
            <span class="text-3xl mr-3">🏆</span>
            Koleksi Piala & Trofi
        </h2>

        <!-- Add Trophy Button -->
        <button type="button" onclick="document.getElementById('addTrophyModal').classList.remove('hidden')" class="mb-6 px-6 py-3 bg-gradient-to-r <?php echo $gradient_colors; ?> text-white font-bold rounded-lg hover:shadow-lg transition">
            ➕ Tambah Piala Baru
        </button>

        <!-- Trophies Grid -->
        <?php if ($trophies_result->num_rows > 0): ?>
            <div class="grid md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php while ($trophy = $trophies_result->fetch_assoc()): ?>
                    <?php $years_list = explode(',', $trophy['years_won']); ?>
                    <div class="trophy-card bg-gradient-to-br <?php echo $gradient_colors; ?> text-white rounded-xl shadow-lg p-6 text-center relative">
                        
                        <!-- Delete Button -->
                        <a href="?id=<?php echo $club_id; ?>&delete_trophy=<?php echo $trophy['id']; ?>#tab-prestasi" 
                           onclick="return confirm('Yakin ingin menghapus piala <?php echo htmlspecialchars($trophy['trophy_name']); ?>?')"
                           class="absolute top-3 right-3 w-8 h-8 bg-red-500 hover:bg-red-600 rounded-full flex items-center justify-center text-white transition shadow-lg z-10"
                           title="Hapus Piala">
                            🗑️
                        </a>

                        <!-- Trophy Image -->
                        <div class="mb-4">
                            <?php if (!empty($trophy['trophy_image'])): ?>
                                <img src="<?php echo htmlspecialchars($trophy['trophy_image']); ?>" alt="<?php echo htmlspecialchars($trophy['trophy_name']); ?>" class="w-20 h-20 mx-auto object-contain filter drop-shadow-lg" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23FFD700%22%3E%3Cpath d=%22M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z%22/%3E%3C/svg%3E'">
                            <?php else: ?>
                                <span class="text-6xl">🏆</span>
                            <?php endif; ?>
                        </div>

                        <!-- Trophy Name -->
                        <h3 class="font-bold text-lg mb-2"><?php echo htmlspecialchars($trophy['trophy_name']); ?></h3>

                        <!-- Win Count Badge -->
                        <div class="mb-3">
                            <span class="inline-block px-4 py-2 bg-white/20 backdrop-blur rounded-full text-2xl font-black">
                                <?php echo $trophy['win_count']; ?>×
                            </span>
                        </div>

                        <!-- Years Toggle Button -->
                        <button type="button" onclick="toggleYears(<?php echo $trophy['id']; ?>)" class="w-full py-2 bg-white/20 hover:bg-white/30 backdrop-blur rounded-lg text-sm font-semibold transition">
                            📅 Lihat Tahun
                        </button>

                        <!-- Years List (Hidden by default) -->
                        <div id="years-<?php echo $trophy['id']; ?>" class="hidden mt-3 pt-3 border-t border-white/20">
                            <div class="flex flex-wrap gap-2 justify-center">
                                <?php foreach ($years_list as $year): ?>
                                    <span class="px-3 py-1 bg-white/30 backdrop-blur rounded text-sm font-bold">
                                        <?php echo trim($year); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12 bg-gray-50 rounded-xl">
                <span class="text-6xl block mb-4">🏆</span>
                <p class="text-gray-600 font-semibold">Belum ada piala yang ditambahkan</p>
                <p class="text-sm text-gray-500 mt-2">Klik tombol "Tambah Piala Baru" untuk memulai</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- TAB: MANAJEMEN -->
<div id="tab-manajemen" class="tab-content" style="display:none;">
    <div class="bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
            <span class="text-3xl mr-3">👔</span>
            Struktur Manajemen
        </h2>
        
        <div class="space-y-6">
            <!-- Owner -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Pemilik Klub</label>
                <input type="text" name="owner" value="<?php echo htmlspecialchars($club['owner'] ?? ''); ?>" placeholder="Contoh: City Football Group, Glazer Family" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue">
            </div>

            <!-- Chairman -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Ketua/Chairman</label>
                <input type="text" name="chairman" value="<?php echo htmlspecialchars($club['chairman'] ?? ''); ?>" placeholder="Nama ketua dewan" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue">
            </div>

            <!-- Board Members -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Dewan Direksi</label>
                <textarea name="board_members" rows="6" placeholder="Daftar anggota dewan direksi (pisahkan dengan enter)" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue resize-none"><?php echo htmlspecialchars($club['board_members'] ?? ''); ?></textarea>
                <p class="text-sm text-gray-600 mt-2">
                    💡 Tip: Tuliskan nama-nama anggota dewan direksi, satu per baris
                </p>
            </div>
        </div>
    </div>
</div>

                    <!-- Submit Button -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <button type="submit" name="update_club" class="w-full py-4 bg-gradient-to-r <?php echo $gradient_colors; ?> text-white font-bold rounded-lg hover:shadow-lg transition text-lg">
                            💾 Simpan Semua Perubahan
                        </button>
                    </div>

                </form>

            </div>

        </main>

    </div>

    <!-- Add Trophy Modal -->
    <div id="addTrophyModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">➕ Tambah Piala Baru</h3>
                <button type="button" onclick="document.getElementById('addTrophyModal').classList.add('hidden')" class="w-10 h-10 bg-gray-200 hover:bg-gray-300 rounded-full flex items-center justify-center text-gray-700 transition">
                    ✕
                </button>
            </div>

            <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                
                <!-- Trophy Name -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nama Piala *</label>
                    <input type="text" name="trophy_name" required placeholder="Contoh: Premier League, FA Cup" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue">
                </div>

                <!-- Trophy Photo Upload -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Upload Foto Piala</label>
                    <div class="flex items-center gap-4">
                        <label class="flex-1 cursor-pointer">
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-city-blue transition text-center">
                                <div class="text-4xl mb-2">📸</div>
                                <p class="text-sm font-semibold text-gray-700">Klik untuk upload foto</p>
                                <p class="text-xs text-gray-500 mt-1">JPG, PNG, SVG, WEBP (Max 2MB)</p>
                            </div>
                            <input type="file" name="trophy_photo" accept="image/*" class="hidden" onchange="previewTrophyImage(this)">
                        </label>
                        <div id="imagePreview" class="hidden w-32 h-32 border-2 border-gray-300 rounded-lg overflow-hidden">
                            <img id="previewImg" src="" alt="Preview" class="w-full h-full object-contain">
                        </div>
                    </div>
                </div>

                <!-- Trophy Image URL (Alternative) -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Atau URL Gambar Piala</label>
                    <input type="url" name="trophy_image_url" placeholder="https://example.com/trophy.png" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue">
                    <p class="text-xs text-gray-500 mt-2">
                        💡 Jika tidak upload foto, bisa gunakan URL. Kosongkan untuk emoji default 🏆
                    </p>
                </div>

                <!-- Years Won -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Tahun Memenangkan Piala *</label>
                    <input type="text" name="trophy_years" required placeholder="Contoh: 2020, 2021, 2023" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue">
                    <p class="text-xs text-gray-500 mt-2">
                        💡 Pisahkan dengan koma untuk multiple tahun
                    </p>
                </div>

                <!-- Preview -->
                <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                    <p class="text-sm font-bold text-gray-700 mb-3">Preview:</p>
                    <div class="flex items-center gap-4">
                        <span class="text-5xl">🏆</span>
                        <div>
                            <p class="font-bold text-gray-900">Nama piala akan muncul di sini</p>
                            <p class="text-sm text-gray-600">Jumlah kemenangan akan dihitung otomatis dari tahun yang diinput</p>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex gap-3">
                    <button type="submit" name="add_trophy" class="flex-1 py-3 bg-gradient-to-r <?php echo $gradient_colors; ?> text-white font-bold rounded-lg hover:shadow-lg transition">
                        ➕ Tambah Piala
                    </button>
                    <button type="button" onclick="document.getElementById('addTrophyModal').classList.add('hidden')" class="px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition">
                        Batal
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
        // Tab switching
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
                btn.classList.add('bg-gray-200');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tabName).style.display = 'block';
            
            // Add active class to clicked button
            const activeBtn = document.querySelector('[data-tab="' + tabName + '"]');
            activeBtn.classList.add('active');
            activeBtn.classList.remove('bg-gray-200');
            
            // Update URL hash without scrolling
            history.replaceState(null, null, '#tab-' + tabName);
        }

        // Auto-open tab based on URL hash
        window.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash;
            if (hash && hash.startsWith('#tab-')) {
                const tabName = hash.replace('#tab-', '');
                switchTab(tabName);
            }
        });

        // Toggle trophy years display
        function toggleYears(trophyId) {
            const yearsDiv = document.getElementById('years-' + trophyId);
            const isHidden = yearsDiv.classList.contains('hidden');
            
            if (isHidden) {
                yearsDiv.classList.remove('hidden');
                yearsDiv.style.animation = 'fadeIn 0.3s ease-in';
            } else {
                yearsDiv.classList.add('hidden');
            }
        }

        // Close modal when clicking outside
        document.getElementById('addTrophyModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });

        // Confirm before leaving with unsaved changes
        let formChanged = false;
        document.querySelectorAll('input, textarea, select').forEach(element => {
            element.addEventListener('change', () => {
                formChanged = true;
            });
        });

        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Mark form as saved when submitting
        document.querySelector('form')?.addEventListener('submit', () => {
            formChanged = false;
        });

        // Color picker sync
        document.querySelectorAll('input[type="color"]').forEach(colorInput => {
            colorInput.addEventListener('input', function() {
                const textInput = this.nextElementSibling.nextElementSibling;
                if (textInput) {
                    textInput.value = this.value;
                }
            });
        });

        // Preview trophy image
        function previewTrophyImage(input) {
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

</body>
</html