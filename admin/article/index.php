<?php
/**
 * Manchester Side - Admin Articles Management
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
    $stmt = $db->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        setFlashMessage('success', 'Berita berhasil dihapus');
    } else {
        setFlashMessage('error', 'Gagal menghapus berita');
    }
    redirect('index.php');
}

// Handle publish/unpublish toggle
if (isset($_GET['toggle_publish'])) {
    $id = (int)$_GET['toggle_publish'];
    $stmt = $db->prepare("UPDATE articles SET is_published = NOT is_published WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        setFlashMessage('success', 'Status publikasi berhasil diubah');
    }
    redirect('index.php');
}

// Get filter and search parameters
$search = $_GET['search'] ?? '';
$club_filter = $_GET['club'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

// Build query
$query = "SELECT 
    a.id, a.title, a.slug, a.is_published, a.views, a.published_at, a.created_at,
    c.name as club_name, c.code as club_code,
    ad.full_name as author_name
FROM articles a
LEFT JOIN clubs c ON a.club_id = c.id
JOIN admins ad ON a.author_id = ad.id
WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (a.title LIKE ? OR a.content LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($club_filter === 'city') {
    $query .= " AND c.code = 'CITY'";
} elseif ($club_filter === 'united') {
    $query .= " AND c.code = 'UNITED'";
} elseif ($club_filter === 'general') {
    $query .= " AND a.club_id IS NULL";
}

if ($status_filter === 'published') {
    $query .= " AND a.is_published = 1";
} elseif ($status_filter === 'draft') {
    $query .= " AND a.is_published = 0";
}

$query .= " ORDER BY a.created_at DESC";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$articles_result = $stmt->get_result();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Berita - Admin Panel</title>
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
                        <h1 class="text-3xl font-bold text-gray-900">Manage Berita</h1>
                        <p class="text-gray-600 mt-1">Kelola semua artikel berita Manchester Side</p>
                    </div>
                    <a href="create.php" class="px-6 py-3 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition">
                        ➕ Buat Berita Baru
                    </a>
                </div>
            </header>

            <div class="p-6">

                <?php if ($flash): ?>
                    <div class="mb-6 bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-50 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-200 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-800 px-4 py-3 rounded-lg">
                        <?php echo $flash['message']; ?>
                    </div>
                <?php endif; ?>

                <!-- Filters & Search -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <form method="GET" action="" class="grid md:grid-cols-4 gap-4">
                        
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">🔍 Cari Berita</label>
                            <input 
                                type="text" 
                                name="search" 
                                value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Cari judul atau konten..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent"
                            >
                        </div>

                        <!-- Club Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">⚽ Klub</label>
                            <select name="club" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent">
                                <option value="all" <?php echo $club_filter === 'all' ? 'selected' : ''; ?>>Semua Klub</option>
                                <option value="city" <?php echo $club_filter === 'city' ? 'selected' : ''; ?>>Man City</option>
                                <option value="united" <?php echo $club_filter === 'united' ? 'selected' : ''; ?>>Man United</option>
                                <option value="general" <?php echo $club_filter === 'general' ? 'selected' : ''; ?>>⚪ Umum</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">📊 Status</label>
                            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                                <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>✅ Published</option>
                                <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>📝 Draft</option>
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

                <!-- Articles Table -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Judul Berita</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Klub</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Penulis</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Views</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if ($articles_result->num_rows > 0): ?>
                                <?php while ($article = $articles_result->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4">
                                            <div>
                                                <a href="edit.php?id=<?php echo $article['id']; ?>" class="font-semibold text-gray-900 hover:text-city-blue">
                                                    <?php echo truncateText($article['title'], 60); ?>
                                                </a>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    <?php echo $article['slug']; ?>
                                                </p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($article['club_code']): ?>
                                                <span class="px-3 py-1 bg-<?php echo $article['club_code'] === 'CITY' ? 'city-blue' : 'united-red'; ?> text-white rounded-full text-xs font-bold flex items-center gap-1 inline-flex">
                                                    <img src="<?php echo $article['club_code'] === 'CITY' ? 'https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg' : 'https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg'; ?>" 
                                                         alt="<?php echo $article['club_code']; ?>" 
                                                         class="w-3 h-3">
                                                    <?php echo $article['club_code']; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 bg-gray-300 text-gray-700 rounded-full text-xs font-bold">
                                                    ⚪ UMUM
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <?php echo $article['author_name']; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            👁️ <?php echo formatNumber($article['views']); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($article['is_published']): ?>
                                                <span class="inline-flex items-center justify-center gap-1 px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-bold">
                                                    <span>✅</span>
                                                    <span>Published</span>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center justify-center gap-1 px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-bold">
                                                    <span>📝</span>
                                                    <span>Draft</span>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <?php echo formatDateIndo($article['created_at']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end space-x-2">
                                               
                                                <a href="edit.php?id=<?php echo $article['id']; ?>" class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-lg text-xs font-semibold hover:bg-yellow-200 transition" title="Edit">
                                                    ✏️
                                                </a>
                                                <a href="?toggle_publish=<?php echo $article['id']; ?>" onclick="return confirm('Ubah status publikasi?')" class="px-3 py-1 bg-purple-100 text-purple-800 rounded-lg text-xs font-semibold hover:bg-purple-200 transition" title="Toggle Publish">
                                                    <?php echo $article['is_published'] ? '📥' : '📤'; ?>
                                                </a>
                                                <a href="?delete=<?php echo $article['id']; ?>" onclick="return confirm('Yakin ingin menghapus berita ini?')" class="px-3 py-1 bg-red-100 text-red-800 rounded-lg text-xs font-semibold hover:bg-red-200 transition" title="Delete">
                                                    🗑️
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        <div class="text-6xl mb-4">📰</div>
                                        <p class="text-lg font-semibold">Tidak ada berita ditemukan</p>
                                        <p class="text-sm mt-2">Coba ubah filter atau buat berita baru</p>
                                        <a href="create.php" class="inline-block mt-4 px-6 py-2 bg-city-blue text-white font-semibold rounded-lg hover:bg-city-navy transition">
                                            ➕ Buat Berita Baru
                                        </a>
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