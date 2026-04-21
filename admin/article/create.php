<?php
/**
 * Manchester Side - Create New Article
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
    $title = lightSanitize($_POST['title'] ?? '');
    $content = lightSanitize($_POST['content'] ?? '');
    $excerpt = lightSanitize($_POST['excerpt'] ?? '');
    $club_id = !empty($_POST['club_id']) ? (int)$_POST['club_id'] : null;
    // Process category with extreme care
    $category_raw = $_POST['category'] ?? 'news';
    
    // Clean the category value thoroughly
    $category = trim($category_raw);
    $category = preg_replace('/[^a-zA-Z]/', '', $category); // Remove non-alphabetic characters
    $category = strtolower($category); // Ensure lowercase
    
    // Map to valid ENUM values with strict validation
    $category_map = [
        'news' => 'news',
        'match' => 'match', 
        'transfer' => 'transfer',
        'interview' => 'interview',
        'analysis' => 'analysis'
    ];
    
    // Force to valid category or default to news
    if (!isset($category_map[$category])) {
        $category = 'news';
    } else {
        $category = $category_map[$category];
    }
    
    // Final safety check
    if (!in_array($category, ['news', 'match', 'transfer', 'interview', 'analysis'], true)) {
        $category = 'news';
    }
    
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Judul berita wajib diisi';
    }
    
    if (empty($content)) {
        $errors[] = 'Konten berita wajib diisi';
    }
    
    // Generate slug
    $slug = generateSlug($title);
    
    // Check if slug already exists
    $check_slug = $db->prepare("SELECT id FROM articles WHERE slug = ?");
    $check_slug->bind_param("s", $slug);
    $check_slug->execute();
    if ($check_slug->get_result()->num_rows > 0) {
        $slug = $slug . '-' . time();
    }
    
    // Auto-generate excerpt if empty
    if (empty($excerpt)) {
        $excerpt = truncateText(strip_tags($content), 200);
    }
    
    // Handle image upload
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadImage($_FILES['image'], 'articles');
        if ($upload_result['success']) {
            $image_url = 'includes/uploads/articles/' . $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }
    
    if (empty($errors)) {
        $author_id = $admin['id'];
        
        // Use conditional SQL for published_at to avoid datetime issues
        if ($is_published) {
            $stmt = $db->prepare("INSERT INTO articles (title, slug, content, excerpt, image_url, club_id, author_id, category, is_published, is_featured, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssssiisii", $title, $slug, $content, $excerpt, $image_url, $club_id, $author_id, $category, $is_published, $is_featured);
        } else {
            $stmt = $db->prepare("INSERT INTO articles (title, slug, content, excerpt, image_url, club_id, author_id, category, is_published, is_featured, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)");
            $stmt->bind_param("sssssiisii", $title, $slug, $content, $excerpt, $image_url, $club_id, $author_id, $category, $is_published, $is_featured);
        }
        
        if ($stmt->execute()) {
            $article_id = $db->insert_id;
            setFlashMessage('success', 'Berita berhasil dibuat!');
            redirect('edit.php?id=' . $article_id);
        } else {
            $errors[] = 'Gagal menyimpan berita. Silakan coba lagi.';
        }
    }
}

// Get clubs for dropdown - ONLY Manchester City and Manchester United
$clubs = $db->query("SELECT id, name, code FROM clubs WHERE code IN ('CITY', 'UNITED') ORDER BY name");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Berita Baru - Admin Panel</title>
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
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Buat Berita Baru</h1>
                        <p class="text-gray-600 mt-1">Tulis dan publikasikan berita terbaru</p>
                    </div>
                    <a href="index.php" class="px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition">
                        ← Kembali
                    </a>
                </div>
            </header>

            <div class="p-6">

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
                        
                        <!-- Main Content Area -->
                        <div class="lg:col-span-2 space-y-6">
                            
                            <!-- Title -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <label for="title" class="block text-sm font-bold text-gray-700 mb-2">
                                    Judul Berita <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="title" 
                                    name="title" 
                                    value="<?php echo htmlspecialchars(html_entity_decode(stripslashes($_POST['title'] ?? ''))); ?>"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent text-lg"
                                    placeholder="Contoh: Haaland Cetak Hat-trick Lagi!"
                                    autofocus
                                >
                                <p class="mt-2 text-xs text-gray-500">
                                    💡 Tip: Gunakan judul yang menarik dan informatif
                                </p>
                            </div>

                            <!-- Image Upload -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <label for="image" class="block text-sm font-bold text-gray-700 mb-2">
                                    📷 Gambar Artikel
                                </label>
                                <input 
                                    type="file" 
                                    id="image" 
                                    name="image" 
                                    accept="image/jpeg,image/jpg,image/png,image/gif,image/webp,image/bmp,image/svg+xml"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue"
                                    onchange="previewImage(this)"
                                >
                                <p class="mt-2 text-xs text-gray-500">
                                    Format: JPG, JPEG, PNG, GIF, WebP, BMP, SVG. Max 5MB
                                </p>
                                
                                <!-- Image Preview -->
                                <div id="imagePreview" class="mt-4 hidden">
                                    <p class="text-sm font-semibold text-gray-700 mb-2">Preview:</p>
                                    <img id="preview" src="" alt="Preview" class="max-w-md h-48 object-cover rounded-lg border-2 border-gray-300">
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <label for="content" class="block text-sm font-bold text-gray-700 mb-2">
                                    Konten Berita <span class="text-red-500">*</span>
                                </label>
                                <textarea 
                                    id="content" 
                                    name="content" 
                                    rows="15"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent resize-none"
                                    placeholder="Tulis konten berita lengkap di sini..."
                                ><?php echo htmlspecialchars(html_entity_decode(stripslashes($_POST['content'] ?? ''))); ?></textarea>
                                <p class="mt-2 text-xs text-gray-500">
                                    📝 Tip: Pisahkan paragraf dengan enter untuk memudahkan pembacaan
                                </p>
                            </div>

                            <!-- Excerpt -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <label for="excerpt" class="block text-sm font-bold text-gray-700 mb-2">
                                    Ringkasan (Excerpt)
                                </label>
                                <textarea 
                                    id="excerpt" 
                                    name="excerpt" 
                                    rows="3"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent resize-none"
                                    placeholder="Ringkasan singkat berita (opsional, akan di-generate otomatis jika kosong)"
                                ><?php echo htmlspecialchars(html_entity_decode(stripslashes($_POST['excerpt'] ?? ''))); ?></textarea>
                                <p class="mt-2 text-xs text-gray-500">
                                    ℹ️ Ringkasan akan muncul di preview card dan daftar berita
                                </p>
                            </div>

                        </div>

                        <!-- Sidebar Settings -->
                        <div class="space-y-6">
                            
                            <!-- Publish Settings -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                                    <span class="text-xl mr-2">📤</span>
                                    Publikasi
                                </h3>
                                
                                <div class="space-y-4">
                                    <!-- Publish Status -->
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            name="is_published" 
                                            value="1"
                                            <?php echo (isset($_POST['is_published']) || !isset($_POST['title'])) ? 'checked' : ''; ?>
                                            class="w-5 h-5 text-city-blue border-gray-300 rounded focus:ring-city-blue"
                                        >
                                        <div>
                                            <span class="font-semibold text-gray-900">Publish Sekarang</span>
                                            <p class="text-xs text-gray-500">Berita langsung tayang di website</p>
                                        </div>
                                    </label>

                                    <!-- Featured -->
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            name="is_featured" 
                                            value="1"
                                            <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>
                                            class="w-5 h-5 text-city-blue border-gray-300 rounded focus:ring-city-blue"
                                        >
                                        <div>
                                            <span class="font-semibold text-gray-900">Featured Article</span>
                                            <p class="text-xs text-gray-500">Tampilkan di highlight homepage</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Club Selection -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                                    <span class="text-xl mr-2">⚽</span>
                                    Klub
                                </h3>
                                
                                <select 
                                    name="club_id" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                >
                                    <option value="">⚪ Berita Umum</option>
                                    <?php while ($club = $clubs->fetch_assoc()): ?>
                                        <option value="<?php echo $club['id']; ?>" <?php echo (isset($_POST['club_id']) && $_POST['club_id'] == $club['id']) ? 'selected' : ''; ?>>
                                            <?php echo $club['code'] === 'CITY' ? '🔵' : '🔴'; ?> <?php echo $club['name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <p class="mt-2 text-xs text-gray-500">
                                    ℹ️ Pilih klub terkait atau biarkan umum untuk berita derby
                                </p>
                            </div>

                            <!-- Category -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                                    <span class="text-xl mr-2">🏷️</span>
                                    Kategori
                                </h3>
                                
                                <select 
                                    name="category" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                                >
                                    <option value="news" <?php echo (($_POST['category'] ?? 'news') === 'news') ? 'selected' : ''; ?>>📰 News</option>
                                    <option value="match" <?php echo (($_POST['category'] ?? '') === 'match') ? 'selected' : ''; ?>>⚽ Match Report</option>
                                    <option value="transfer" <?php echo (($_POST['category'] ?? '') === 'transfer') ? 'selected' : ''; ?>>💼 Transfer</option>
                                    <option value="interview" <?php echo (($_POST['category'] ?? '') === 'interview') ? 'selected' : ''; ?>>🎤 Interview</option>
                                    <option value="analysis" <?php echo (($_POST['category'] ?? '') === 'analysis') ? 'selected' : ''; ?>>📊 Analysis</option>
                                </select>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="bg-white rounded-xl shadow-lg p-6 space-y-3">
                                <button 
                                    type="submit"
                                    class="w-full py-3 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition"
                                >
                                    💾 Simpan Berita
                                </button>
                                
                                <a 
                                    href="index.php"
                                    class="block w-full py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition text-center"
                                >
                                    ❌ Batal
                                </a>
                            </div>

                            <!-- Tips -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                                <p class="font-semibold mb-2">💡 Tips Menulis Berita:</p>
                                <ul class="space-y-1 text-xs">
                                    <li>✅ Gunakan judul yang menarik</li>
                                    <li>✅ Tulis paragraf pendek (3-4 kalimat)</li>
                                    <li>✅ Sertakan fakta dan data</li>
                                    <li>✅ Cek ejaan sebelum publish</li>
                                    <li>✅ Pilih klub yang tepat</li>
                                </ul>
                            </div>

                        </div>

                    </div>

                </form>

            </div>

        </main>

    </div>

    <script>
        // Auto-generate slug preview (optional)
        document.getElementById('title').addEventListener('input', function() {
            const title = this.value;
            const slug = title.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');
            // You could display this slug somewhere if needed
        });

        // Character counter for excerpt
        const excerptField = document.getElementById('excerpt');
        const maxChars = 200;
        
        if (excerptField) {
            excerptField.addEventListener('input', function() {
                const remaining = maxChars - this.value.length;
                if (remaining < 0) {
                    this.value = this.value.substring(0, maxChars);
                }
            });
        }
    </script>

    <script>
        // Image preview function
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const previewContainer = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                // Check file size (5MB max)
                if (input.files[0].size > 5 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar! Maksimal 5MB');
                    input.value = '';
                    previewContainer.classList.add('hidden');
                    return;
                }
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/svg+xml'];
                if (!allowedTypes.includes(input.files[0].type)) {
                    alert('Format file tidak didukung! Gunakan JPG, PNG, GIF, WebP, BMP, atau SVG');
                    input.value = '';
                    previewContainer.classList.add('hidden');
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                previewContainer.classList.add('hidden');
            }
        }
    </script>

</body>
</html>