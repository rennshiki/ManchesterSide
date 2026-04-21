<?php
/**
 * Manchester Side - All News Page with Pagination & Filters
 */
require_once 'includes/config.php';

$db = getDB();

// Debug mode - add ?debug=1 to URL to see filter debug info
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';

// Get filters
$club_filter = $_GET['club'] ?? 'all'; // all, city, united
$category_filter = $_GET['category'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'latest'; // latest, popular, oldest

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = ARTICLES_PER_PAGE;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where_conditions = ["a.is_published = 1"];
$params = [];
$types = "";

if ($club_filter === 'city') {
    $where_conditions[] = "c.code = 'CITY'";
} elseif ($club_filter === 'united') {
    $where_conditions[] = "c.code = 'UNITED'";
}

if ($category_filter !== 'all') {
    $where_conditions[] = "a.category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if (!empty($search)) {
    $where_conditions[] = "(a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$where_clause = implode(" AND ", $where_conditions);

// Determine ORDER BY
switch ($sort) {
    case 'popular':
        $order_by = "a.views DESC, a.published_at DESC";
        break;
    case 'oldest':
        $order_by = "a.published_at ASC";
        break;
    default: // latest
        $order_by = "a.published_at DESC";
}

// Count total articles
$count_query = "SELECT COUNT(*) as total FROM articles a LEFT JOIN clubs c ON a.club_id = c.id WHERE $where_clause";
$count_stmt = $db->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_articles = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_articles / $per_page);

// Get articles
$query = "SELECT 
    a.id, a.title, a.slug, a.excerpt, a.image_url, a.category, a.views, a.published_at, a.is_featured,
    c.name as club_name, c.code as club_code, c.color_primary,
    ad.full_name as author_name
FROM articles a
LEFT JOIN clubs c ON a.club_id = c.id
JOIN admins ad ON a.author_id = ad.id
WHERE $where_clause
ORDER BY $order_by
LIMIT ? OFFSET ?";

$stmt = $db->prepare($query);
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$articles_result = $stmt->get_result();

// Debug mode output
if ($debug_mode) {
    echo "<div style='background: #f0f0f0; padding: 20px; margin: 20px; border: 1px solid #ccc; font-family: monospace;'>";
    echo "<h2>🔍 DEBUG MODE - Filter Analysis</h2>";
    
    // Show current filters
    echo "<h3>1. Current Filters:</h3>";
    echo "<ul>";
    echo "<li><strong>Club Filter:</strong> " . htmlspecialchars($club_filter) . "</li>";
    echo "<li><strong>Category Filter:</strong> " . htmlspecialchars($category_filter) . "</li>";
    echo "<li><strong>Search:</strong> " . htmlspecialchars($search) . "</li>";
    echo "<li><strong>Sort:</strong> " . htmlspecialchars($sort) . "</li>";
    echo "<li><strong>Page:</strong> {$page} of {$total_pages}</li>";
    echo "</ul>";
    
    // Show clubs data
    echo "<h3>2. Available Clubs:</h3>";
    $clubs_debug = $db->query("SELECT id, name, code FROM clubs");
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #ddd;'><th>ID</th><th>Name</th><th>Code</th></tr>";
    while ($club = $clubs_debug->fetch_assoc()) {
        echo "<tr><td>{$club['id']}</td><td>{$club['name']}</td><td>{$club['code']}</td></tr>";
    }
    echo "</table>";
    
    // Show query info
    echo "<h3>3. Query Information:</h3>";
    echo "<p><strong>WHERE Clause:</strong> " . htmlspecialchars($where_clause) . "</p>";
    echo "<p><strong>ORDER BY:</strong> " . htmlspecialchars($order_by) . "</p>";
    echo "<p><strong>Total Articles Found:</strong> {$total_articles}</p>";
    
    // Test specific filters
    echo "<h3>4. Filter Test Results:</h3>";
    
    // Test CITY filter
    $city_test = $db->query("SELECT COUNT(*) as count FROM articles a LEFT JOIN clubs c ON a.club_id = c.id WHERE a.is_published = 1 AND c.code = 'CITY'");
    $city_count = $city_test->fetch_assoc()['count'];
    echo "<p>📊 <strong>City Articles:</strong> {$city_count}</p>";
    
    // Test UNITED filter  
    $united_test = $db->query("SELECT COUNT(*) as count FROM articles a LEFT JOIN clubs c ON a.club_id = c.id WHERE a.is_published = 1 AND c.code = 'UNITED'");
    $united_count = $united_test->fetch_assoc()['count'];
    echo "<p>📊 <strong>United Articles:</strong> {$united_count}</p>";
    
    // Test all articles
    $all_test = $db->query("SELECT COUNT(*) as count FROM articles a WHERE a.is_published = 1");
    $all_count = $all_test->fetch_assoc()['count'];
    echo "<p>📊 <strong>All Published Articles:</strong> {$all_count}</p>";
    
    echo "<h3>5. Debug Links:</h3>";
    echo "<p>";
    echo "<a href='?debug=1&club=all' style='margin-right: 10px; padding: 5px 10px; background: #007cba; color: white; text-decoration: none; border-radius: 3px;'>Test All</a>";
    echo "<a href='?debug=1&club=city' style='margin-right: 10px; padding: 5px 10px; background: #6CABDD; color: white; text-decoration: none; border-radius: 3px;'>Test City</a>";
    echo "<a href='?debug=1&club=united' style='margin-right: 10px; padding: 5px 10px; background: #DA291C; color: white; text-decoration: none; border-radius: 3px;'>Test United</a>";
    echo "<a href='news.php' style='padding: 5px 10px; background: #666; color: white; text-decoration: none; border-radius: 3px;'>Exit Debug</a>";
    echo "</p>";
    
    echo "</div>";
}

// Get statistics
$stats = [];
$stats['total_city'] = $db->query("SELECT COUNT(*) as c FROM articles WHERE is_published = 1 AND club_id = 1")->fetch_assoc()['c'];
$stats['total_united'] = $db->query("SELECT COUNT(*) as c FROM articles WHERE is_published = 1 AND club_id = 2")->fetch_assoc()['c'];
$stats['total_general'] = $db->query("SELECT COUNT(*) as c FROM articles WHERE is_published = 1 AND club_id IS NULL")->fetch_assoc()['c'];

$current_user = getCurrentUser();

$page_title = "Semua Berita";
include 'includes/header.php';
?>

<style>
    .card-hover {
        transition: all 0.3s ease;
    }
    
    .card-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.12);
    }
</style>
    

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <!-- Page Header -->
        <div class="mb-8 text-center">
            <h1 class="text-5xl font-bold text-gray-900 mb-4">
                📰 Semua Berita
            </h1>
            <p class="text-xl text-gray-600">
                Jelajahi <?php echo formatNumber($total_articles); ?> berita terbaru Manchester City & Manchester United
            </p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <a href="?club=city" class="bg-gradient-to-br from-city-blue to-city-navy text-white rounded-xl shadow-lg p-6 hover:shadow-2xl transition">
                <div class="flex items-center justify-between mb-4">
                    <img src="https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg" alt="Man City" class="w-16 h-16 object-contain">
                    <span class="text-4xl font-bold"><?php echo $stats['total_city']; ?></span>
                </div>
                <p class="font-bold text-lg">Manchester City</p>
                <p class="text-sm text-blue-100">Total Berita</p>
            </a>

            <a href="?club=united" class="bg-gradient-to-br from-united-red to-red-900 text-white rounded-xl shadow-lg p-6 hover:shadow-2xl transition">
                <div class="flex items-center justify-between mb-4">
                    <img src="https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg" alt="Man United" class="w-16 h-16 object-contain">
                    <span class="text-4xl font-bold"><?php echo $stats['total_united']; ?></span>
                </div>
                <p class="font-bold text-lg">Manchester United</p>
                <p class="text-sm text-red-100">Total Berita</p>
            </a>

            <div class="bg-gradient-to-br from-gray-700 to-gray-900 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-5xl">⚽</span>
                    <span class="text-4xl font-bold"><?php echo $stats['total_general']; ?></span>
                </div>
                <p class="font-bold text-lg">Berita Umum</p>
                <p class="text-sm text-gray-300">Derby & Lainnya</p>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <form method="GET" action="" class="space-y-4">
                
                <div class="grid md:grid-cols-5 gap-4">
                    
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">🔍 Cari Berita</label>
                        <input 
                            type="text" 
                            name="search" 
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Cari judul, konten, atau ringkasan..."
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
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">🏷️ Kategori</label>
                        <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent">
                            <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>Semua</option>
                            <option value="news" <?php echo $category_filter === 'news' ? 'selected' : ''; ?>>📰 News</option>
                            <option value="match" <?php echo $category_filter === 'match' ? 'selected' : ''; ?>>⚽ Match</option>
                            <option value="transfer" <?php echo $category_filter === 'transfer' ? 'selected' : ''; ?>>💼 Transfer</option>
                            <option value="interview" <?php echo $category_filter === 'interview' ? 'selected' : ''; ?>>🎤 Interview</option>
                            <option value="analysis" <?php echo $category_filter === 'analysis' ? 'selected' : ''; ?>>📊 Analysis</option>
                        </select>
                    </div>

                    <!-- Sort -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">📊 Urutkan</label>
                        <select name="sort" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent">
                            <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>🕐 Terbaru</option>
                            <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>🔥 Terpopuler</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>⏰ Terlama</option>
                        </select>
                    </div>

                </div>

                <div class="flex gap-2">
                    <button type="submit" class="px-6 py-2 bg-gradient-to-r from-city-blue to-united-red text-white font-semibold rounded-lg hover:shadow-lg transition">
                        Filter
                    </button>
                    <a href="news.php" class="px-6 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition">
                        Reset
                    </a>
                </div>

            </form>
        </div>

        <!-- Active Filters Display -->
        <?php if ($club_filter !== 'all' || $category_filter !== 'all' || !empty($search) || $sort !== 'latest'): ?>
            <div class="mb-6 flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold text-gray-700">Filter Aktif:</span>
                
                <?php if ($club_filter !== 'all'): ?>
                    <span class="px-3 py-1 bg-<?php echo $club_filter === 'city' ? 'city-blue' : 'united-red'; ?> text-white rounded-full text-sm font-semibold flex items-center gap-1 inline-flex">
                        <img src="<?php echo $club_filter === 'city' ? 'https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg' : 'https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg'; ?>" 
                             alt="<?php echo $club_filter === 'city' ? 'Man City' : 'Man United'; ?>" 
                             class="w-4 h-4">
                        <?php echo $club_filter === 'city' ? 'Man City' : 'Man United'; ?>
                    </span>
                <?php endif; ?>
                
                <?php if ($category_filter !== 'all'): ?>
                    <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-semibold">
                        🏷️ <?php echo ucfirst($category_filter); ?>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($search)): ?>
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold">
                        🔍 "<?php echo htmlspecialchars($search); ?>"
                    </span>
                <?php endif; ?>
                
                <?php if ($sort !== 'latest'): ?>
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                        📊 <?php echo $sort === 'popular' ? 'Terpopuler' : 'Terlama'; ?>
                    </span>
                <?php endif; ?>
                
                <a href="news.php" class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-semibold hover:bg-red-200 transition">
                    ❌ Hapus Filter
                </a>
            </div>
        <?php endif; ?>

        <!-- Results Info -->
        <div class="mb-6 flex items-center justify-between">
            <p class="text-gray-600">
                Menampilkan <strong><?php echo min($per_page, $total_articles - $offset); ?></strong> dari <strong><?php echo formatNumber($total_articles); ?></strong> berita
                <?php if ($page > 1): ?>
                    (Halaman <strong><?php echo $page; ?></strong> dari <strong><?php echo $total_pages; ?></strong>)
                <?php endif; ?>
            </p>
        </div>

        <?php if ($articles_result->num_rows > 0): ?>
            
            <!-- Articles Grid -->
            <div class="grid md:grid-cols-3 gap-6 mb-12">
                <?php while ($article = $articles_result->fetch_assoc()): ?>
                    <?php 
                    // Determine background color - BIRU MUDA untuk City, MERAH MUDA untuk United
                    $bg_class = 'bg-white hover:bg-gray-50';
                    $border_class = 'border-gray-200';
                    $hover_text_color = 'city-blue';
                    
                    if ($article['club_code'] === 'CITY') {
                        $bg_class = 'bg-blue-50 hover:bg-blue-100';
                        $border_class = 'border-blue-300';
                        $hover_text_color = 'city-blue';
                    } elseif ($article['club_code'] === 'UNITED') {
                        $bg_class = 'bg-red-50 hover:bg-red-100';
                        $border_class = 'border-red-300';
                        $hover_text_color = 'united-red';
                    }
                    ?>
                    <a href="news-detail.php?slug=<?php echo $article['slug']; ?>" class="card-hover <?php echo $bg_class; ?> rounded-xl shadow-lg overflow-hidden group border-2 <?php echo $border_class; ?> <?php echo $article['is_featured'] ? 'ring-2 ring-yellow-400' : ''; ?>">
                        
                        <?php if ($article['is_featured']): ?>
                            <div class="bg-yellow-400 text-yellow-900 text-xs font-bold px-3 py-1 text-center">
                                ⭐ FEATURED
                            </div>
                        <?php endif; ?>
                        
                        <div class="h-48 bg-gray-200 overflow-hidden">
                            <img src="<?php echo getArticleImage($article['image_url'], $article['club_code']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <?php if ($article['club_code']): ?>
                                    <span class="px-3 py-1 bg-gradient-to-r from-<?php echo $article['club_code'] === 'CITY' ? 'city-blue' : 'united-red'; ?>-500 to-<?php echo $article['club_code'] === 'CITY' ? 'city-navy' : 'red'; ?>-900 text-white rounded-full text-xs font-bold">
                                        <?php echo strtoupper($article['club_name']); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-semibold">
                                    <?php echo ucfirst($article['category']); ?>
                                </span>
                            </div>
                            <h4 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-<?php echo $hover_text_color; ?> transition line-clamp-2">
                                <?php echo $article['title']; ?>
                            </h4>
                            <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                                <?php echo truncateText($article['excerpt'], 100); ?>
                            </p>
                            <div class="flex items-center justify-between text-xs text-gray-500 pt-3 border-t border-gray-200">
                                <span>📅 <?php echo formatDateIndo($article['published_at']); ?></span>
                                <span>👁️ <?php echo formatNumber($article['views']); ?></span>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center items-center space-x-2">
                    
                    <!-- Previous Button -->
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&club=<?php echo $club_filter; ?>&category=<?php echo $category_filter; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition font-semibold">
                            ← Prev
                        </a>
                    <?php else: ?>
                        <span class="px-4 py-2 bg-gray-100 border border-gray-200 rounded-lg text-gray-400 font-semibold cursor-not-allowed">
                            ← Prev
                        </span>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1): ?>
                        <a href="?page=1&club=<?php echo $club_filter; ?>&category=<?php echo $category_filter; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition font-semibold">
                            1
                        </a>
                        <?php if ($start_page > 2): ?>
                            <span class="px-2 text-gray-400">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&club=<?php echo $club_filter; ?>&category=<?php echo $category_filter; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" class="px-4 py-2 <?php echo $i === $page ? 'bg-gradient-to-r from-city-blue to-united-red text-white' : 'bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-lg transition font-semibold">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="px-2 text-gray-400">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $total_pages; ?>&club=<?php echo $club_filter; ?>&category=<?php echo $category_filter; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition font-semibold">
                            <?php echo $total_pages; ?>
                        </a>
                    <?php endif; ?>

                    <!-- Next Button -->
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&club=<?php echo $club_filter; ?>&category=<?php echo $category_filter; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition font-semibold">
                            Next →
                        </a>
                    <?php else: ?>
                        <span class="px-4 py-2 bg-gray-100 border border-gray-200 rounded-lg text-gray-400 font-semibold cursor-not-allowed">
                            Next →
                        </span>
                    <?php endif; ?>

                </div>

                <!-- Page Jump -->
                <div class="mt-6 text-center">
                    <form method="GET" action="" class="inline-flex items-center gap-2">
                        <input type="hidden" name="club" value="<?php echo $club_filter; ?>">
                        <input type="hidden" name="category" value="<?php echo $category_filter; ?>">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <input type="hidden" name="sort" value="<?php echo $sort; ?>">
                        
                        <span class="text-sm text-gray-600">Lompat ke halaman:</span>
                        <input 
                            type="number" 
                            name="page" 
                            min="1" 
                            max="<?php echo $total_pages; ?>"
                            value="<?php echo $page; ?>"
                            class="w-20 px-3 py-1 border border-gray-300 rounded text-center text-sm"
                        >
                        <button type="submit" class="px-4 py-1 bg-city-blue text-white rounded text-sm font-semibold hover:bg-city-navy transition">
                            Go
                        </button>
                    </form>
                </div>
            <?php endif; ?>

        <?php else: ?>
            
            <!-- Empty State -->
            <div class="text-center py-20">
                <div class="inline-block p-8 bg-white rounded-full shadow-xl mb-6">
                    <span class="text-8xl">🔍</span>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-4">
                    Tidak Ada Berita Ditemukan
                </h2>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                    <?php if (!empty($search)): ?>
                        Tidak ditemukan hasil untuk pencarian "<strong><?php echo htmlspecialchars($search); ?></strong>"
                    <?php else: ?>
                        Tidak ada berita yang sesuai dengan filter yang dipilih
                    <?php endif; ?>
                </p>
                <div class="flex justify-center gap-4">
                    <a href="news.php" class="inline-block px-8 py-4 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition">
                        Reset Filter
                    </a>
                    <a href="index.php" class="inline-block px-8 py-4 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition">
                        Ke Beranda
                    </a>
                </div>
            </div>

        <?php endif; ?>

    </main>

    <!-- Footer -->
    

<?php include 'includes/footer.php'; ?>
