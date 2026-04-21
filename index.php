<?php
/**
 * Manchester Side - Homepage with Real Club Logos
 */
require_once 'includes/config.php';

$db = getDB();

// Get filter parameter
$filter = $_GET['filter'] ?? 'all'; // all, city, united

// Debug mode - add ?debug=1 to URL to see filter debug info
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';

// Build query based on filter
$query = "SELECT 
    a.id, a.title, a.slug, a.excerpt, a.image_url, a.category, a.views, a.published_at,
    c.name as club_name, c.code as club_code, c.color_primary,
    ad.full_name as author_name
FROM articles a
LEFT JOIN clubs c ON a.club_id = c.id
JOIN admins ad ON a.author_id = ad.id
WHERE a.is_published = 1";

// Filter by club
if ($filter === 'city') {
    $query .= " AND c.code = 'CITY'";
} elseif ($filter === 'united') {
    $query .= " AND c.code = 'UNITED'";
}
// If filter is 'all', show all articles (no additional WHERE clause)

$query .= " ORDER BY a.published_at DESC LIMIT 20";



$articles_result = $db->query($query);

// Debug mode output
if ($debug_mode) {
    echo "<div style='background: #f0f0f0; padding: 20px; margin: 20px; border: 1px solid #ccc; font-family: monospace;'>";
    echo "<h2>🔍 DEBUG MODE - Homepage Filter Analysis</h2>";
    
    // Show current filter
    echo "<h3>1. Current Filter:</h3>";
    echo "<p><strong>Filter:</strong> " . htmlspecialchars($filter) . "</p>";
    
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
    echo "<p><strong>Query:</strong> " . htmlspecialchars($query) . "</p>";
    echo "<p><strong>Results Found:</strong> " . $articles_result->num_rows . " articles</p>";
    
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
    echo "<a href='?debug=1&filter=all' style='margin-right: 10px; padding: 5px 10px; background: #007cba; color: white; text-decoration: none; border-radius: 3px;'>Test All</a>";
    echo "<a href='?debug=1&filter=city' style='margin-right: 10px; padding: 5px 10px; background: #6CABDD; color: white; text-decoration: none; border-radius: 3px;'>Test City</a>";
    echo "<a href='?debug=1&filter=united' style='margin-right: 10px; padding: 5px 10px; background: #DA291C; color: white; text-decoration: none; border-radius: 3px;'>Test United</a>";
    echo "<a href='index.php' style='padding: 5px 10px; background: #666; color: white; text-decoration: none; border-radius: 3px;'>Exit Debug</a>";
    echo "</p>";
    
    echo "</div>";
}

// Get featured articles (top 2)
$featured_query = "SELECT 
    a.id, a.title, a.slug, a.excerpt, a.image_url, a.published_at,
    c.name as club_name, c.code as club_code, c.color_primary
FROM articles a
LEFT JOIN clubs c ON a.club_id = c.id
WHERE a.is_published = 1 AND a.is_featured = 1
ORDER BY a.published_at DESC LIMIT 2";

$featured_result = $db->query($featured_query);
$featured_articles = [];
while ($row = $featured_result->fetch_assoc()) {
    $featured_articles[] = $row;
}

// Check if logo_url column exists
$logo_column_exists = false;
try {
    $check_column = $db->query("SHOW COLUMNS FROM clubs LIKE 'logo_url'");
    $logo_column_exists = $check_column->num_rows > 0;
} catch (Exception $e) {
    $logo_column_exists = false;
}

// Get upcoming matches
if ($logo_column_exists) {
    $matches_query = "SELECT 
        m.*,
        h.name as home_team, h.code as home_code, h.logo_url as home_logo,
        a.name as away_team, a.code as away_code, a.logo_url as away_logo
    FROM matches m
    JOIN clubs h ON m.home_team_id = h.id
    JOIN clubs a ON m.away_team_id = a.id
    WHERE m.match_date > NOW() AND m.status = 'scheduled'
    ORDER BY m.match_date ASC LIMIT 2";
} else {
    $matches_query = "SELECT 
        m.*,
        h.name as home_team, h.code as home_code,
        a.name as away_team, a.code as away_code
    FROM matches m
    JOIN clubs h ON m.home_team_id = h.id
    JOIN clubs a ON m.away_team_id = a.id
    WHERE m.match_date > NOW() AND m.status = 'scheduled'
    ORDER BY m.match_date ASC LIMIT 2";
}

$matches_result = $db->query($matches_query);

// Helper function to get team logo
function getTeamLogo($team_code, $logo_url = '') {
    if (!empty($logo_url)) {
        return $logo_url;
    }
    
    // Default logos for Manchester teams
    if ($team_code === 'CITY') {
        return 'https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg';
    } elseif ($team_code === 'UNITED') {
        return 'https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg';
    }
    
    // Generic logo for other teams
    return 'https://via.placeholder.com/100x100/cccccc/666666?text=' . urlencode(substr($team_code, 0, 3));
}

$current_user = getCurrentUser();
$flash = getFlashMessage();

// Club logo URLs
$club_logos = [
    'CITY' => 'https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg',
    'UNITED' => 'https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg'
];

$page_title = SITE_NAME . " - " . SITE_TAGLINE;
include 'includes/header.php';
?>

<style>
    html {
        scroll-behavior: smooth;
    }
    
    .hero-gradient {
        background: linear-gradient(135deg, #6CABDD 0%, #1C2C5B 50%, #DA291C 100%);
    }
    
    .card-hover {
        transition: all 0.3s ease;
    }
    
    .card-hover:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }
    
    .badge-city {
        background: linear-gradient(135deg, #6CABDD, #1C2C5B);
    }
    
    .badge-united {
        background: linear-gradient(135deg, #DA291C, #8B0000);
    }

    @keyframes marquee {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
    .animate-marquee {
        display: inline-block;
        animation: marquee 20s linear infinite;
    }

    .club-logo {
        width: 100%;
        height: 100%;
        object-fit: contain;
        filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
    }

    .club-logo-small {
        width: 80px;
        height: 80px;
    }

    .club-logo-large {
        width: 180px;
        height: 180px;
    }
</style>
    

    <?php if ($flash): ?>
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-50 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-200 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-800 px-4 py-3 rounded-lg">
                <?php echo $flash['message']; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-gradient text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <!-- Dual Logos -->
            <div class="flex justify-center items-center gap-8 mb-8">
                <img src="<?php echo $club_logos['CITY']; ?>" alt="Manchester City" class="club-logo-large">
                <div class="text-6xl font-black text-white/50">VS</div>
                <img src="<?php echo $club_logos['UNITED']; ?>" alt="Manchester United" class="club-logo-large">
            </div>
            
            <h1 class="text-5xl md:text-6xl font-bold mb-4">
                Two Sides, One City
            </h1>
            <p class="text-xl md:text-2xl mb-8 text-gray-100">
                Berita Eksklusif Manchester City & Manchester United
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4 mt-8">
                <a href="index.php#news-section" class="flex items-center justify-center gap-3 px-8 py-4 <?php echo $filter === 'all' ? 'bg-gradient-to-r from-city-blue to-united-red ring-4 ring-white' : 'bg-gray-700 hover:bg-gray-600'; ?> text-white font-bold rounded-lg shadow-lg hover:shadow-xl transition transform hover:scale-105">
                    <span>📰</span>
                    Semua Berita
                </a>
                <a href="?filter=city#news-section" class="flex items-center justify-center gap-3 px-8 py-4 <?php echo $filter === 'city' ? 'bg-city-navy ring-4 ring-white' : 'bg-city-blue hover:bg-city-navy'; ?> text-white font-bold rounded-lg shadow-lg hover:shadow-xl transition transform hover:scale-105">
                    <img src="<?php echo $club_logos['CITY']; ?>" alt="Man City" class="w-6 h-6">
                    Man City News
                </a>
                <a href="?filter=united#news-section" class="flex items-center justify-center gap-3 px-8 py-4 <?php echo $filter === 'united' ? 'bg-red-900 ring-4 ring-white' : 'bg-united-red hover:bg-red-800'; ?> text-white font-bold rounded-lg shadow-lg hover:shadow-xl transition transform hover:scale-105">
                    <img src="<?php echo $club_logos['UNITED']; ?>" alt="Man United" class="w-6 h-6">
                    Man United News
                </a>
            </div>
        </div>
    </section>

    <!-- Breaking News Ticker -->
    <div class="bg-gray-900 text-white py-3 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 flex items-center">
            <span class="bg-united-red px-4 py-1 rounded font-bold mr-4">LIVE</span>
            <div class="flex-1 overflow-hidden">
                <div class="animate-marquee whitespace-nowrap">
                    <?php 
                    $ticker_query = "SELECT title, slug FROM articles WHERE is_published = 1 ORDER BY published_at DESC LIMIT 5";
                    $ticker_result = $db->query($ticker_query);
                    while ($ticker = $ticker_result->fetch_assoc()):
                    ?>
                        <a href="news-detail.php?slug=<?php echo $ticker['slug']; ?>" class="mx-8 hover:text-city-blue transition">
                            ⚽ <?php echo $ticker['title']; ?>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main id="news-section" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 scroll-mt-20">
        
        <!-- Filter Buttons -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-4xl font-bold text-gray-900">
                    <?php 
                    if ($filter === 'city') {
                        echo '<span class="text-city-blue">Manchester City</span> News';
                    } elseif ($filter === 'united') {
                        echo '<span class="text-united-red">Manchester United</span> News';
                    } else {
                        echo 'Berita Terkini';
                    }
                    ?>
                </h2>
                <?php if ($filter !== 'all'): ?>
                    <p class="text-gray-600 mt-1">Menampilkan berita <?php echo $filter === 'city' ? 'Manchester City' : 'Manchester United'; ?> saja</p>
                <?php endif; ?>
            </div>
            <div class="flex gap-2">
                <a href="index.php" class="px-4 py-2 <?php echo $filter === 'all' ? 'bg-gray-800 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?> rounded-lg font-semibold transition">
                    Semua
                </a>
                <a href="?filter=city" class="flex items-center gap-2 px-4 py-2 <?php echo $filter === 'city' ? 'bg-city-blue text-white' : 'bg-gray-200 hover:bg-city-blue hover:text-white'; ?> rounded-lg font-semibold transition">
                    <img src="<?php echo $club_logos['CITY']; ?>" alt="City" class="w-4 h-4">
                    City
                </a>
                <a href="?filter=united" class="flex items-center gap-2 px-4 py-2 <?php echo $filter === 'united' ? 'bg-united-red text-white' : 'bg-gray-200 hover:bg-united-red hover:text-white'; ?> rounded-lg font-semibold transition">
                    <img src="<?php echo $club_logos['UNITED']; ?>" alt="United" class="w-4 h-4">
                    United
                </a>
            </div>
        </div>

        <!-- Featured Articles -->
        <?php if (count($featured_articles) > 0): ?>
        <div class="grid md:grid-cols-2 gap-8 mb-12">
            <?php foreach ($featured_articles as $article): ?>
                <?php 
                // Determine background color for featured articles - BIRU MUDA untuk City, MERAH MUDA untuk United
                $featured_bg = 'bg-white hover:bg-gray-50';
                $featured_border = 'border-gray-200';
                $text_color = 'city-blue';
                
                if ($article['club_code'] === 'CITY') {
                    $featured_bg = 'bg-blue-50 hover:bg-blue-100';
                    $featured_border = 'border-blue-300';
                    $text_color = 'city-blue';
                } elseif ($article['club_code'] === 'UNITED') {
                    $featured_bg = 'bg-red-50 hover:bg-red-100';
                    $featured_border = 'border-red-300';
                    $text_color = 'united-red';
                }
                ?>
                <a href="news-detail.php?slug=<?php echo $article['slug']; ?>" class="card-hover <?php echo $featured_bg; ?> rounded-xl shadow-lg overflow-hidden group border-2 <?php echo $featured_border; ?>">
                    <div class="h-64 bg-gray-200 overflow-hidden">
                        <?php 
                        $image_src = getArticleImage($article['image_url'], $article['club_code']);
                        ?>
                        <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="w-full h-full object-cover" 
                             onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1529900748604-07564a03e7a6?w=800&q=80';">
                    </div>
                    <div class="p-6">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="badge-<?php echo strtolower($article['club_code']); ?> text-white px-3 py-1 rounded-full text-xs font-bold">
                                <?php echo strtoupper($article['club_name']); ?>
                            </span>
                            <span class="text-gray-500 text-sm"><?php echo timeAgo($article['published_at']); ?></span>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3 group-hover:text-<?php echo $text_color; ?> transition">
                            <?php echo $article['title']; ?>
                        </h3>
                        <p class="text-gray-600 mb-4">
                            <?php echo truncateText($article['excerpt'], 120); ?>
                        </p>
                        <span class="text-<?php echo $text_color; ?> font-bold hover:underline">
                            Baca Selengkapnya →
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- All Articles Grid -->
        <?php if ($articles_result->num_rows > 0): ?>
        <div class="grid md:grid-cols-3 gap-6">
            <?php while ($article = $articles_result->fetch_assoc()): ?>
                <?php 
                // Determine background color based on club - BIRU MUDA untuk City, MERAH MUDA untuk United
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
                <a href="news-detail.php?slug=<?php echo $article['slug']; ?>" class="card-hover <?php echo $bg_class; ?> rounded-xl shadow-lg overflow-hidden group border-2 <?php echo $border_class; ?>">
                    <div class="h-48 bg-gray-200 overflow-hidden">
                        <?php 
                        $image_src = getArticleImage($article['image_url'], $article['club_code']);
                        ?>
                        <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="w-full h-full object-cover" 
                             onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1529900748604-07564a03e7a6?w=800&q=80';">
                    </div>
                    <div class="p-5">
                        <?php if ($article['club_code']): ?>
                            <span class="badge-<?php echo strtolower($article['club_code']); ?> text-white px-3 py-1 rounded-full text-xs font-bold">
                                <?php echo strtoupper($article['club_name']); ?>
                            </span>
                        <?php endif; ?>
                        <h4 class="text-lg font-bold text-gray-900 mt-3 mb-2 group-hover:text-<?php echo $hover_text_color; ?> transition">
                            <?php echo truncateText($article['title'], 60); ?>
                        </h4>
                        <p class="text-gray-600 text-sm mb-3">
                            <?php echo truncateText($article['excerpt'], 80); ?>
                        </p>
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span><?php echo timeAgo($article['published_at']); ?></span>
                            <span>👁️ <?php echo formatNumber($article['views']); ?></span>
                        </div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <!-- Empty State -->
        <div class="text-center py-16 bg-white rounded-xl shadow-lg">
            <div class="text-6xl mb-4">📰</div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">
                Belum Ada Berita <?php echo $filter === 'city' ? 'Manchester City' : ($filter === 'united' ? 'Manchester United' : ''); ?>
            </h3>
            <p class="text-gray-600 mb-6">
                Saat ini belum ada berita yang tersedia untuk filter ini.
            </p>
            <a href="index.php" class="inline-block px-6 py-3 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition">
                Lihat Semua Berita
            </a>
        </div>
        <?php endif; ?>

        <!-- Upcoming Matches -->
        <?php if ($matches_result->num_rows > 0): ?>
        <section class="mt-16">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">Jadwal Pertandingan</h2>
            <div class="grid md:grid-cols-2 gap-6">
                <?php while ($match = $matches_result->fetch_assoc()): ?>
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="text-center mb-4">
                            <span class="bg-gray-800 text-white px-4 py-1 rounded-full text-sm font-bold">
                                <?php echo $match['competition']; ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="text-center flex-1">
                                <div class="flex justify-center mb-3">
                                    <img src="<?php echo getTeamLogo($match['home_code'], $match['home_logo'] ?? ''); ?>" 
                                         alt="<?php echo $match['home_team']; ?>" 
                                         class="w-16 h-16 object-contain">
                                </div>
                                <p class="font-bold text-gray-900"><?php echo $match['home_team']; ?></p>
                            </div>
                            <div class="text-center px-6">
                                <p class="text-3xl font-bold text-gray-400">VS</p>
                                <p class="text-sm text-gray-600 mt-2">
                                    <?php echo formatDateIndo($match['match_date']); ?>
                                </p>
                            </div>
                            <div class="text-center flex-1">
                                <div class="flex justify-center mb-3">
                                    <img src="<?php echo getTeamLogo($match['away_code'], $match['away_logo'] ?? ''); ?>" 
                                         alt="<?php echo $match['away_team']; ?>" 
                                         class="w-16 h-16 object-contain">
                                </div>
                                <p class="font-bold text-gray-900"><?php echo $match['away_team']; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
        <?php endif; ?>

    </main>

    <script>
        // Auto scroll to news section when page loads with hash
        window.addEventListener('load', function() {
            if (window.location.hash === '#news-section') {
                setTimeout(function() {
                    const element = document.getElementById('news-section');
                    if (element) {
                        // Get navbar height for offset
                        const navbarHeight = 80; // Approximate navbar height
                        const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
                        const offsetPosition = elementPosition - navbarHeight;
                        
                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });
                    }
                }, 200);
            }
        });
        
        // Handle click on filter buttons to scroll smoothly
        document.addEventListener('DOMContentLoaded', function() {
            const filterLinks = document.querySelectorAll('a[href*="#news-section"]');
            filterLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Let the browser handle the navigation, then scroll
                    setTimeout(function() {
                        const element = document.getElementById('news-section');
                        if (element) {
                            const navbarHeight = 80;
                            const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
                            const offsetPosition = elementPosition - navbarHeight;
                            
                            window.scrollTo({
                                top: offsetPosition,
                                behavior: 'smooth'
                            });
                        }
                    }, 100);
                });
            });
        });
    </script>

<?php include 'includes/footer.php'; ?>
