<?php
/**
 * Manchester Side - Admin Profil Klub Management
 */
require_once '../../includes/config.php';

if (!isAdminLoggedIn()) {
    redirect('../login.php');
}

$db = getDB();
$admin = getCurrentAdmin();

// Get clubs data - ONLY Manchester City and Manchester United
$clubs_query = "SELECT * FROM clubs WHERE code IN ('CITY', 'UNITED') ORDER BY name";
$clubs_result = $db->query($clubs_query);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profil Klub - Admin Panel</title>
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
                        <h1 class="text-3xl font-bold text-gray-900">Manage Profil Klub</h1>
                        <p class="text-gray-600 mt-1">Kelola informasi Manchester City & Manchester United</p>
                    </div>
                </div>
            </header>

            <div class="p-6">

    <?php if ($flash): ?>
        <div class="mb-6 bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-50 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-200 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-800 px-4 py-3 rounded-lg">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>

    <!-- Clubs Grid -->
    <div class="grid md:grid-cols-2 gap-6">
        <?php while ($club = $clubs_result->fetch_assoc()): ?>
            <?php
            $is_city = $club['code'] === 'CITY';
            $gradient = $is_city ? 'from-city-blue to-city-navy' : 'from-united-red to-red-900';
            $logo = $is_city 
                ? 'https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg'
                : 'https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg';
            ?>
            
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-br <?php echo $gradient; ?> text-white p-8">
                    <div class="flex items-center justify-between mb-4">
                        <img src="<?php echo $logo; ?>" alt="<?php echo $club['name']; ?>" class="w-20 h-20 object-contain">
                        <div class="text-right">
                            <h2 class="text-3xl font-bold"><?php echo $club['name']; ?></h2>
                            <p class="text-sm opacity-90">Est. <?php echo $club['founded_year']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-3">
                    <!-- Full Name -->
                    <div class="pb-2 border-b border-gray-100">
                        <p class="text-xs text-gray-500 mb-1">Nama Lengkap</p>
                        <p class="font-semibold text-gray-900"><?php echo $club['full_name']; ?></p>
                    </div>

                    <!-- Stadium -->
                    <div class="pb-2 border-b border-gray-100">
                        <p class="text-xs text-gray-500 mb-1">🏟️ Stadion</p>
                        <p class="font-semibold text-gray-900"><?php echo $club['stadium_name']; ?></p>
                        <p class="text-sm text-gray-600"><?php echo $club['stadium_location']; ?></p>
                        <p class="text-sm text-gray-600">Kapasitas: <?php echo number_format($club['stadium_capacity']); ?> penonton</p>
                    </div>

                    <!-- History Preview -->
                    <div class="pb-2 border-b border-gray-100">
                        <p class="text-xs text-gray-500 mb-1">📖 Sejarah</p>
                        <p class="text-sm text-gray-700 line-clamp-3"><?php echo $club['history']; ?></p>
                    </div>

                    <!-- Achievements Preview -->
                    <div class="pb-2 border-b border-gray-100">
                        <p class="text-xs text-gray-500 mb-1">🏆 Prestasi</p>
                        <p class="text-sm text-gray-700 line-clamp-3"><?php echo nl2br($club['achievements']); ?></p>
                    </div>

                    <!-- Colors -->
                    <div>
                        <p class="text-xs text-gray-500 mb-2">🎨 Warna Klub</p>
                        <div class="flex gap-2">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded border-2 border-gray-300" style="background-color: <?php echo $club['color_primary']; ?>"></div>
                                <span class="text-xs text-gray-600"><?php echo $club['color_primary']; ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded border-2 border-gray-300" style="background-color: <?php echo $club['color_secondary']; ?>"></div>
                                <span class="text-xs text-gray-600"><?php echo $club['color_secondary']; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="pt-3">
                        <a href="edit.php?id=<?php echo $club['id']; ?>" class="block w-full py-3 bg-gradient-to-r <?php echo $gradient; ?> text-white font-bold rounded-lg hover:shadow-lg transition text-center">
                            ✏️ Edit Profil Klub
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Info Box -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-6">
        <div class="flex items-start gap-3">
            <div class="text-3xl">ℹ️</div>
            <div>
                <h3 class="font-bold text-gray-900 mb-2">Informasi</h3>
                <p class="text-sm text-gray-700">
                    Halaman ini digunakan untuk mengelola informasi profil klub Manchester City dan Manchester United. 
                    Informasi yang dikelola meliputi nama klub, stadion, sejarah, prestasi, dan warna klub. 
                    Data ini akan ditampilkan di halaman <strong>Profil Klub</strong> di website utama.
                </p>
            </div>
        </div>
    </div>

            </div>

        </main>

    </div>

</body>
</html>
