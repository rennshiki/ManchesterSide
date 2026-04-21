<?php
/**
 * Manchester Side - Edit Article
 * Features: Image Upload Support
 * No Comments Feature
 */
require_once '../../includes/config.php';

if (!isAdminLoggedIn()) {
    redirect('../login.php');
}

$db = getDB();
$admin = getCurrentAdmin();
$errors = [];

// Get article ID
$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($article_id === 0) {
    setFlashMessage('error', 'ID artikel tidak valid');
    redirect('index.php');
}



// Get article data
$stmt = $db->prepare("SELECT a.*, c.name as club_name, c.code as club_code 
                      FROM articles a 
                      LEFT JOIN clubs c ON a.club_id = c.id 
                      WHERE a.id = ?");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$article = $stmt->get_result()->fetch_assoc();

if (!$article) {
    setFlashMessage('error', 'Artikel tidak ditemukan');
    redirect('index.php');
}



// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_article'])) {
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
    
    // Generate new slug if title changed
    if ($title !== $article['title']) {
        $slug = generateSlug($title);
        
        // Check if slug exists (excluding current article)
        $check_slug = $db->prepare("SELECT id FROM articles WHERE slug = ? AND id != ?");
        $check_slug->bind_param("si", $slug, $article_id);
        $check_slug->execute();
        if ($check_slug->get_result()->num_rows > 0) {
            $slug = $slug . '-' . time();
        }
    } else {
        $slug = $article['slug'];
    }
    
    // Auto-generate excerpt if empty
    if (empty($excerpt)) {
        $excerpt = truncateText(strip_tags($content), 200);
    }
    
    // Handle image upload
    $image_url = $article['image_url'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Delete old image if exists
        if ($image_url && file_exists('../../' . $image_url)) {
            unlink('../../' . $image_url);
        }
        
        $upload_result = uploadImage($_FILES['image'], 'articles');
        if ($upload_result['success']) {
            $image_url = 'includes/uploads/articles/' . $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }
    
    // Handle image deletion
    if (isset($_POST['delete_image']) && $image_url) {
        if (file_exists('../../' . $image_url)) {
            unlink('../../' . $image_url);
        }
        $image_url = null;
    }
    
    if (empty($errors)) {
        // Use SQL NOW() function to avoid PHP datetime issues
        $use_current_time = $is_published;
        
        // Update article without category first - use conditional SQL for published_at
        if ($use_current_time) {
            $update_stmt = $db->prepare("UPDATE articles SET 
                title = ?, 
                slug = ?, 
                content = ?, 
                excerpt = ?, 
                image_url = ?,
                club_id = ?, 
                is_published = ?, 
                is_featured = ?,
                published_at = NOW(),
                updated_at = NOW()
                WHERE id = ?");
            
            $update_stmt->bind_param("sssssiiii", 
                $title, $slug, $content, $excerpt, $image_url, $club_id, 
                $is_published, $is_featured, $article_id
            );
        } else {
            $update_stmt = $db->prepare("UPDATE articles SET 
                title = ?, 
                slug = ?, 
                content = ?, 
                excerpt = ?, 
                image_url = ?,
                club_id = ?, 
                is_published = ?, 
                is_featured = ?,
                published_at = NULL,
                updated_at = NOW()
                WHERE id = ?");
            
            $update_stmt->bind_param("sssssiiii", 
                $title, $slug, $content, $excerpt, $image_url, $club_id, 
                $is_published, $is_featured, $article_id
            );
        }
        
        if ($update_stmt->execute()) {
            // Update category separately with error handling
            $category_stmt = $db->prepare("UPDATE articles SET category = ? WHERE id = ?");
            $category_stmt->bind_param("si", $category, $article_id);
            
            if (!$category_stmt->execute()) {
                // If category update fails, log error but don't fail the whole update
                error_log("Failed to update category: " . $db->error . " - Category value: '" . $category . "'");
                // Try to set to default 'news'
                $default_category = 'news';
                $category_stmt2 = $db->prepare("UPDATE articles SET category = ? WHERE id = ?");
                $category_stmt2->bind_param("si", $default_category, $article_id);
                $category_stmt2->execute();
            }
            
            setFlashMessage('success', 'Berita berhasil diperbarui!');
            redirect('edit.php?id=' . $article_id);
        } else {
            $errors[] = 'Gagal memperbarui berita: ' . $db->error;
        }
    }
    
    // Reload article data after update
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT a.*, c.name as club_name, c.code as club_code 
                              FROM articles a 
                              LEFT JOIN clubs c ON a.club_id = c.id 
                              WHERE a.id = ?");
        $stmt->bind_param("i", $article_id);
        $stmt->execute();
        $article = $stmt->get_result()->fetch_assoc();
    }
}

// Get clubs for dropdown - ONLY Manchester City and Manchester United
$clubs = $db->query("SELECT id, name, code FROM clubs WHERE code IN ('CITY', 'UNITED') ORDER BY name");

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Berita - Admin Panel</title>
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
                        <h1 class="text-3xl font-bold text-gray-900">Edit Berita</h1>
                        <p class="text-gray-600 mt-1">Perbarui artikel: <span class="font-semibold"><?php echo truncateText($article['title'], 50); ?></span></p>
                    </div>
                    <div class="flex gap-3">
                        <a href="../../news-detail.php?slug=<?php echo $article['slug']; ?>" target="_blank" class="px-6 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition">
                            👁️ Preview
                        </a>
                        <a href="index.php" class="px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition">
                            ← Kembali
                        </a>
                    </div>
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

                <!-- Article Info Card -->
                <div class="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-xl p-6 mb-6">
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">ID Artikel</p>
                            <p class="text-2xl font-bold text-gray-900">#<?php echo $article['id']; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Status</p>
                            <span class="inline-block px-3 py-1 bg-<?php echo $article['is_published'] ? 'green' : 'yellow'; ?>-100 text-<?php echo $article['is_published'] ? 'green' : 'yellow'; ?>-800 rounded-full text-sm font-bold">
                                <?php echo $article['is_published'] ? '✅ Published' : '📝 Draft'; ?>
                            </span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Views</p>
                            <p class="text-2xl font-bold text-gray-900">👁️ <?php echo formatNumber($article['views']); ?></p>
                        </div>
                    </div>
                </div>

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
                                    value="<?php echo htmlspecialchars(html_entity_decode(stripslashes($article['title']))); ?>"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent text-lg"
                                >
                            </div>

                            <!-- Image Upload -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    📷 Gambar Artikel
                                </label>
                                
                                <?php if ($article['image_url']): ?>
                                    <div class="mb-4">
                                        <img src="../../<?php echo $article['image_url']; ?>" alt="Current Image" class="max-w-full h-64 object-cover rounded-lg border-2 border-gray-200">
                                        <label class="flex items-center mt-3">
                                            <input type="checkbox" name="delete_image" value="1" class="mr-2">
                                            <span class="text-red-600 font-semibold">🗑️ Hapus gambar ini</span>
                                        </label>
                                    </div>
                                <?php endif; ?>
                                
                                <input 
                                    type="file" 
                                    name="image" 
                                    accept="image/*"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue"
                                >
                                <p class="text-xs text-gray-500 mt-2">
                                    Format: JPG, PNG, WebP, GIF (Max 5MB)
                                </p>
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
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue resize-none font-mono text-sm"
                                ><?php echo htmlspecialchars(html_entity_decode(stripslashes($article['content']))); ?></textarea>
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
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue resize-none"
                                ><?php echo htmlspecialchars(html_entity_decode(stripslashes($article['excerpt']))); ?></textarea>
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
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            name="is_published" 
                                            value="1"
                                            <?php echo $article['is_published'] ? 'checked' : ''; ?>
                                            class="w-5 h-5 text-city-blue border-gray-300 rounded"
                                        >
                                        <div>
                                            <span class="font-semibold text-gray-900">Publish Artikel</span>
                                            <p class="text-xs text-gray-500">Berita tayang di website</p>
                                        </div>
                                    </label>

                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            name="is_featured" 
                                            value="1"
                                            <?php echo $article['is_featured'] ? 'checked' : ''; ?>
                                            class="w-5 h-5 text-city-blue border-gray-300 rounded"
                                        >
                                        <div>
                                            <span class="font-semibold text-gray-900">Featured</span>
                                            <p class="text-xs text-gray-500">Tampilkan di highlight</p>
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
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue"
                                >
                                    <option value="">⚪ Berita Umum</option>
                                    <?php while ($club = $clubs->fetch_assoc()): ?>
                                        <option value="<?php echo $club['id']; ?>" <?php echo ($article['club_id'] == $club['id']) ? 'selected' : ''; ?>>
                                            <?php echo $club['code'] === 'CITY' ? '🔵' : '🔴'; ?> <?php echo $club['name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Category -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                                    <span class="text-xl mr-2">🏷️</span>
                                    Kategori
                                </h3>
                                
                                <select 
                                    name="category" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue"
                                >
                                    <option value="news" <?php echo $article['category'] === 'news' ? 'selected' : ''; ?>>📰 News</option>
                                    <option value="match" <?php echo $article['category'] === 'match' ? 'selected' : ''; ?>>⚽ Match</option>
                                    <option value="transfer" <?php echo $article['category'] === 'transfer' ? 'selected' : ''; ?>>💼 Transfer</option>
                                    <option value="interview" <?php echo $article['category'] === 'interview' ? 'selected' : ''; ?>>🎤 Interview</option>
                                    <option value="analysis" <?php echo $article['category'] === 'analysis' ? 'selected' : ''; ?>>📊 Analysis</option>
                                </select>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="bg-white rounded-xl shadow-lg p-6 space-y-3">
                                <button 
                                    type="submit"
                                    name="update_article"
                                    class="w-full py-3 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition"
                                >
                                    💾 Update Berita
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

        </main>

    </div>

</body>
</html>