<?php
/**
 * Manchester Side - Profil Klub dengan Flip Card System
 */
require_once 'includes/config.php';

$db = getDB();
$team = strtoupper($_GET['team'] ?? 'CITY');
if (!in_array($team, ['CITY', 'UNITED'])) $team = 'CITY';

// Get club data
$stmt = $db->prepare("SELECT * FROM clubs WHERE code = ?");
$stmt->bind_param("s", $team);
$stmt->execute();
$club = $stmt->get_result()->fetch_assoc();

if (!$club) redirect('index.php');

// Get players
$players_query = $db->prepare("SELECT * FROM players WHERE club_id = ? AND is_active = 1 ORDER BY position, jersey_number");
$players_query->bind_param("i", $club['id']);
$players_query->execute();
$players_result = $players_query->get_result();

$players_by_position = [
    'Goalkeeper' => [],
    'Defender' => [],
    'Midfielder' => [],
    'Forward' => []
];

while ($player = $players_result->fetch_assoc()) {
    $players_by_position[$player['position']][] = $player;
}

// Get staff
$staff_query = $db->prepare("SELECT * FROM staff WHERE club_id = ? AND is_active = 1 ORDER BY role");
$staff_query->bind_param("i", $club['id']);
$staff_query->execute();
$staff_result = $staff_query->get_result();

// Group staff by role
$staff_by_role = [];
while ($staff = $staff_result->fetch_assoc()) {
    $staff_by_role[$staff['role']][] = $staff;
}

// Get trophies
$trophies_query = $db->prepare("SELECT * FROM club_trophies WHERE club_id = ? ORDER BY win_count DESC, trophy_name");
$trophies_query->bind_param("i", $club['id']);
$trophies_query->execute();
$trophies_result = $trophies_query->get_result();

$trophies = [];
while ($trophy = $trophies_result->fetch_assoc()) {
    $trophies[] = $trophy;
}

$stats = [];
$stats['total_players'] = $db->query("SELECT COUNT(*) as c FROM players WHERE club_id = {$club['id']} AND is_active = 1")->fetch_assoc()['c'];
$stats['total_staff'] = $db->query("SELECT COUNT(*) as c FROM staff WHERE club_id = {$club['id']} AND is_active = 1")->fetch_assoc()['c'];
$stats['total_trophies'] = count($trophies);

$current_user = getCurrentUser();
$social_media = getClubSocialMedia($team);

function getPlayerPhoto($photo_url, $name, $team) {
    if (!empty($photo_url) && file_exists($photo_url)) {
        return $photo_url;
    }
    $bg = $team === 'CITY' ? '6CABDD' : 'DA291C';
    return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&size=400&background={$bg}&color=fff&bold=true&font-size=0.4";
}

function getStaffPhoto($photo_url, $name, $team) {
    if (!empty($photo_url) && file_exists($photo_url)) {
        return $photo_url;
    }
    $bg = $team === 'CITY' ? '6CABDD' : 'DA291C';
    return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&size=400&background={$bg}&color=fff&bold=true&font-size=0.33";
}

function calculateAge($birth_date) {
    if (!$birth_date) return null;
    $birth = new DateTime($birth_date);
    $today = new DateTime();
    return $today->diff($birth)->y;
}


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil <?php echo $club['name']; ?> - Manchester Side</title>
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
        
        .flip-card-container {
            perspective: 1000px;
            height: 420px;
        }
        
        .flip-card {
            position: relative;
            width: 100%;
            height: 100%;
            transition: transform 0.7s;
            transform-style: preserve-3d;
            cursor: pointer;
        }
        
        .flip-card.flipped {
            transform: rotateY(180deg);
        }
        
        .flip-card-front,
        .flip-card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .flip-card-back {
            transform: rotateY(180deg);
        }
        
        .tab-button.active {
            background: linear-gradient(135deg, 
                <?php echo $team === 'CITY' ? '#6CABDD' : '#DA291C'; ?> 0%, 
                <?php echo $team === 'CITY' ? '#1C2C5B' : '#8B0000'; ?> 100%);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .trophy-card-interactive {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .trophy-card-interactive:hover {
            transform: translateY(-8px);
        }
        
        #trophyModal {
            animation: modalFadeIn 0.3s ease-out;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body class="bg-gray-50">

    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-<?php echo $team === 'CITY' ? 'city-blue' : 'united-red'; ?> to-<?php echo $team === 'CITY' ? 'city-navy' : 'red'; ?>-900 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="mb-6">
                <img src="<?php echo getClubLogo($team); ?>" alt="<?php echo $club['name']; ?>" class="w-32 h-32 mx-auto object-contain filter drop-shadow-2xl">
            </div>
            <h1 class="text-5xl md:text-6xl font-black mb-4">
                <?php echo $club['full_name']; ?>
            </h1>
            <p class="text-2xl mb-6 text-white/90">
                Founded <?php echo $club['founded_year']; ?>
            </p>
            <div class="flex justify-center gap-6 text-lg">
                <div>
                    <span class="font-bold"><?php echo $stats['total_players']; ?></span>
                    <span class="text-white/80 ml-2">Pemain</span>
                </div>
                <div class="text-white/50">•</div>
                <div>
                    <span class="font-bold"><?php echo $stats['total_staff']; ?></span>
                    <span class="text-white/80 ml-2">Staff</span>
                </div>
                <div class="text-white/50">•</div>
                <div>
                    <span class="font-bold"><?php echo $stats['total_trophies']; ?></span>
                    <span class="text-white/80 ml-2">Piala</span>
                </div>
            </div>
        </div>
    </section>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <!-- Switch Club Button -->
        <div class="text-center mb-8">
            <a href="?team=<?php echo $team === 'CITY' ? 'united' : 'city'; ?>" class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-<?php echo $team === 'CITY' ? 'united-red' : 'city-blue'; ?> to-<?php echo $team === 'CITY' ? 'red' : 'city-navy'; ?>-900 text-white font-bold rounded-lg hover:shadow-lg transition text-lg">
                <img src="<?php echo $team === 'CITY' ? 'https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg' : 'https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg'; ?>" 
                     alt="<?php echo $team === 'CITY' ? 'Manchester United' : 'Manchester City'; ?>" 
                     class="w-6 h-6 object-contain">
                <?php echo $team === 'CITY' ? 'Manchester United' : 'Manchester City'; ?>
            </a>
        </div>

        <!-- Tabs -->
        <div class="mb-8">
            <div class="flex justify-center gap-2 mb-8 flex-wrap">
                <button onclick="switchTab('info')" class="tab-button active px-6 py-3 bg-white rounded-lg font-bold transition shadow-md" data-tab="info">
                    ℹ️ Informasi Klub
                </button>
                <button onclick="switchTab('trophies')" class="tab-button px-6 py-3 bg-white rounded-lg font-bold transition shadow-md" data-tab="trophies">
                    🏆 Piala & Prestasi (<?php echo $stats['total_trophies']; ?>)
                </button>
                <button onclick="switchTab('players')" class="tab-button px-6 py-3 bg-white rounded-lg font-bold transition shadow-md" data-tab="players">
                    👥 Skuad Pemain (<?php echo $stats['total_players']; ?>)
                </button>
                <button onclick="switchTab('staff')" class="tab-button px-6 py-3 bg-white rounded-lg font-bold transition shadow-md" data-tab="staff">
                    🎯 Tim Pelatih (<?php echo $stats['total_staff']; ?>)
                </button>
            </div>
        </div>

        <!-- Tab Content: Info -->
        <div id="tab-info" class="tab-content active">
            <!-- Club Identity -->
            <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">🏛️ Identitas Klub</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Nama Resmi</p>
                        <p class="text-xl font-bold text-gray-900"><?php echo $club['full_name']; ?></p>
                    </div>
                    <?php if (!empty($club['nickname'])): ?>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Julukan</p>
                        <p class="text-xl font-bold text-gray-900"><?php echo $club['nickname']; ?></p>
                    </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Tahun Berdiri</p>
                        <p class="text-xl font-bold text-gray-900"><?php echo $club['founded_year']; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Kode Klub</p>
                        <p class="text-xl font-bold text-gray-900"><?php echo $club['code']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Stadium -->
            <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">🏟️ Stadion</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Nama Stadion</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $club['stadium_name']; ?></p>
                    </div>
                    <?php if (!empty($club['stadium_location'])): ?>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Lokasi</p>
                        <p class="text-lg text-gray-700"><?php echo $club['stadium_location']; ?></p>
                    </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Kapasitas</p>
                        <p class="text-lg font-semibold text-gray-900"><?php echo number_format($club['stadium_capacity']); ?> penonton</p>
                    </div>
                </div>
            </div>

            <!-- History -->
            <?php if (!empty($club['history'])): ?>
            <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">📜 Sejarah</h2>
                <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed">
                    <?php echo nl2br(htmlspecialchars(html_entity_decode(stripslashes($club['history'])))); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Management -->
            <?php if (!empty($club['owner']) || !empty($club['chairman']) || !empty($club['board_members'])): ?>
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">👔 Manajemen</h2>
                <div class="space-y-4">
                    <?php if (!empty($club['owner'])): ?>
                    <div class="pb-4 border-b border-gray-200">
                        <p class="text-sm text-gray-500 mb-1">Pemilik</p>
                        <p class="text-lg font-semibold text-gray-900"><?php echo $club['owner']; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($club['chairman'])): ?>
                    <div class="pb-4 border-b border-gray-200">
                        <p class="text-sm text-gray-500 mb-1">Ketua/Chairman</p>
                        <p class="text-lg font-semibold text-gray-900"><?php echo $club['chairman']; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($club['board_members'])): ?>
                    <div>
                        <p class="text-sm text-gray-500 mb-2">Dewan Direksi</p>
                        <div class="text-gray-700 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars(html_entity_decode(stripslashes($club['board_members'])))); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tab Content: Trophies -->
        <div id="tab-trophies" class="tab-content">
            <!-- Achievements Text -->
            <?php if (!empty($club['achievements'])): ?>
            <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">🏆 Prestasi Umum</h2>
                <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed">
                    <?php echo nl2br(htmlspecialchars(html_entity_decode(stripslashes($club['achievements'])))); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Trophy Cabinet -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-6 flex items-center">
                    <span class="text-4xl mr-3">🏆</span>
                    Lemari Piala
                    <span class="ml-3 text-lg text-gray-500">(<?php echo count($trophies); ?> Piala)</span>
                </h2>

                <?php if (!empty($trophies)): ?>
                    <div class="grid md:grid-cols-3 lg:grid-cols-4 gap-6">
                        <?php foreach ($trophies as $trophy): ?>
                            <?php
                            $years_list = array_map('trim', explode(',', $trophy['years_won']));
                            $border_color = $team === 'CITY' ? 'border-sky-400' : 'border-red-500';
                            $bg_color = $team === 'CITY' ? 'bg-sky-50' : 'bg-red-50';
                            $text_color = $team === 'CITY' ? 'text-sky-900' : 'text-red-900';
                            $badge_color = $team === 'CITY' ? 'bg-sky-500' : 'bg-red-500';
                            ?>
                            
                            <!-- Trophy Card - Bayern Munich Style -->
                            <div class="trophy-card-interactive bg-white rounded-2xl shadow-lg overflow-hidden border-2 <?php echo $border_color; ?> hover:shadow-2xl transition-all cursor-pointer group"
                                 onclick="openTrophyModal(<?php echo htmlspecialchars(json_encode($trophy)); ?>, <?php echo htmlspecialchars(json_encode($years_list)); ?>, '<?php echo $team; ?>')">
                                
                                <!-- Trophy Image Container -->
                                <div class="relative h-48 <?php echo $bg_color; ?> flex items-center justify-center p-6 group-hover:scale-105 transition-transform">
                                    <?php if (!empty($trophy['trophy_image'])): ?>
                                        <img 
                                            src="<?php echo htmlspecialchars($trophy['trophy_image']); ?>" 
                                            alt="<?php echo htmlspecialchars($trophy['trophy_name']); ?>"
                                            class="max-h-40 max-w-full object-contain filter drop-shadow-xl"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                        >
                                        <span class="text-8xl" style="display:none;">🏆</span>
                                    <?php else: ?>
                                        <span class="text-8xl">🏆</span>
                                    <?php endif; ?>
                                    
                                    <!-- Win Count Badge - Top Right -->
                                    <div class="absolute top-3 right-3 <?php echo $badge_color; ?> text-white px-4 py-2 rounded-full shadow-lg">
                                        <span class="text-2xl font-black"><?php echo $trophy['win_count']; ?></span>
                                        <span class="text-sm font-bold">×</span>
                                    </div>
                                </div>

                                <!-- Trophy Info -->
                                <div class="p-4 text-center">
                                    <h3 class="font-bold text-lg <?php echo $text_color; ?> mb-2 line-clamp-2 min-h-[56px] flex items-center justify-center">
                                        <?php echo htmlspecialchars($trophy['trophy_name']); ?>
                                    </h3>
                                    
                                    <!-- Click to view hint -->
                                    <p class="text-sm text-gray-500 group-hover:text-gray-700 transition">
                                        👆 Klik untuk detail
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-16 bg-gray-50 rounded-xl">
                        <span class="text-8xl block mb-4">🏆</span>
                        <p class="text-gray-600 font-semibold text-lg">Belum ada data piala</p>
                        <p class="text-sm text-gray-500 mt-2">Data piala akan ditampilkan di sini</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Content: Players with FLIP CARDS -->
        <div id="tab-players" class="tab-content">
            <?php foreach ($players_by_position as $position => $players): ?>
                <?php if (!empty($players)): ?>
                    <div class="mb-12">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">
                            <?php 
                            echo match($position) {
                                'Goalkeeper' => 'Penjaga Gawang',
                                'Defender' => 'Bek',
                                'Midfielder' => 'Gelandang',
                                'Forward' => 'Penyerang',
                                default => $position
                            };
                            ?>
                            <span class="ml-3 text-lg text-gray-500">(<?php echo count($players); ?>)</span>
                        </h2>
                        
                        <div class="grid md:grid-cols-3 lg:grid-cols-4 gap-6">
                            <?php foreach ($players as $player): ?>
                                <?php
                                $player_photo = getPlayerPhoto($player['photo_url'], $player['name'], $team);
                                $age = calculateAge($player['birth_date']);
                                $border_color = $team === 'CITY' ? 'border-sky-400' : 'border-red-500';
                                $accent_color = $team === 'CITY' ? 'sky' : 'red';
                                ?>
                                
                                <div class="flip-card-container">
                                    <div class="flip-card" onclick="this.classList.toggle('flipped')">
                                        
                                        <!-- FRONT SIDE -->
                                        <div class="flip-card-front">
                                            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden border-4 <?php echo $border_color; ?> h-full hover:shadow-3xl transition-shadow">
                                                <!-- Photo - Full Size -->
                                                <div class="flex justify-center items-center p-6 pt-8">
                                                    <div class="w-48 h-48 rounded-full overflow-hidden border-4 border-<?php echo $team === 'CITY' ? 'sky-400' : 'red-500'; ?> shadow-2xl bg-gray-100">
                                                        <img 
                                                            src="<?php echo $player_photo; ?>" 
                                                            alt="<?php echo $player['name']; ?>"
                                                            class="w-full h-full object-cover"
                                                            onerror="this.src='<?php echo getPlayerPhoto('', $player['name'], $team); ?>'"
                                                        >
                                                    </div>
                                                </div>
                                                
                                                <!-- Name -->
                                                <div class="px-6 text-center">
                                                    <h3 class="text-xl font-black text-gray-900 mb-2 line-clamp-2 min-h-[56px] flex items-center justify-center">
                                                        <?php echo $player['name']; ?>
                                                    </h3>
                                                    <p class="text-<?php echo $accent_color; ?>-600 font-bold text-sm mb-4">
                                                        <?php echo $position; ?>
                                                    </p>
                                                </div>
                                                
                                                <!-- Quick Info -->
                                                <div class="px-6 pb-4 space-y-2">
                                                    <div class="flex items-center justify-center text-gray-600 text-sm">
                                                        <span class="font-semibold"><?php echo $player['nationality']; ?></span>
                                                    </div>
                                                    <?php if ($age): ?>
                                                        <div class="flex items-center justify-center text-gray-600 text-sm">
                                                            <span class="font-semibold"><?php echo $age; ?> tahun</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Flip Hint -->
                                                <div class="absolute bottom-3 left-0 right-0 text-center px-4">
                                                    <p class="text-xs text-gray-500 font-medium bg-white/80 py-2 rounded-lg">
                                                        Klik untuk info lengkap
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- BACK SIDE -->
                                        <div class="flip-card-back">
                                            <div class="bg-gradient-to-br from-<?php echo $accent_color; ?>-500 to-<?php echo $accent_color === 'sky' ? 'blue' : 'red'; ?>-900 text-white rounded-2xl shadow-2xl h-full p-6 flex flex-col border-4 <?php echo $border_color; ?>">
                                                <!-- Header -->
                                                <div class="text-center mb-4">
                                                    <h3 class="text-2xl font-bold mb-2"><?php echo $player['name']; ?></h3>
                                                    <p class="text-lg opacity-90 font-semibold"><?php echo $position; ?></p>
                                                    <p class="text-sm opacity-75 mt-1">No. <?php echo $player['jersey_number']; ?></p>
                                                </div>
                                                
                                                <!-- Detailed Info -->
                                                <div class="flex-1 space-y-2 text-sm">
                                                    <div class="flex justify-between pb-2 border-b border-white/20">
                                                        <span>Kebangsaan:</span>
                                                        <span class="font-bold"><?php echo $player['nationality']; ?></span>
                                                    </div>
                                                    
                                                    <?php if ($player['height']): ?>
                                                        <div class="flex justify-between pb-2 border-b border-white/20">
                                                            <span>Tinggi:</span>
                                                            <span class="font-bold"><?php echo $player['height']; ?> cm</span>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($player['weight']): ?>
                                                        <div class="flex justify-between pb-2 border-b border-white/20">
                                                            <span>Berat:</span>
                                                            <span class="font-bold"><?php echo $player['weight']; ?> kg</span>
                                                        </div>
                                                    <?php endif; ?>

                                                        <div class="flex justify-between pb-2 border-b border-white/20">
                                                            <span>Tanggal Lahir:</span>
                                                            <span class="font-bold"><?php echo formatDateIndo($player['birth_date']); ?></span>
                                                        </div>
                                                    
                                                    <?php if (!empty($player['joined_date'])): ?>
                                                        <div class="flex justify-between pb-2 border-b border-white/20">
                                                            <span>Bergabung:</span>
                                                            <span class="font-bold"><?php echo formatDateIndo($player['joined_date']); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                        <?php if (!empty($player['previous_club'])): ?>
                                                        <div class="flex justify-between pb-2 border-b border-white/20">
                                                            <span>Klub Sebelumnya:</span>
                                                            <span class="font-bold"><?php echo htmlspecialchars($player['previous_club']); ?></span>
                                                        </div>
                                                        <?php endif; ?>
                                                </div>
                                                
                                                <!-- Flip Back Hint -->
                                                <div class="mt-4 pt-3 border-t border-white/20 text-center">
                                                    <p class="text-xs opacity-70">
                                                         Klik untuk kembali
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Tab Content: Staff with FLIP CARDS -->
        <div id="tab-staff" class="tab-content">
            <?php foreach ($staff_by_role as $role => $staff_members): ?>
                <?php if (!empty($staff_members)): ?>
                    <div class="mb-12">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">
                            <?php echo $role; ?>
                            <span class="ml-3 text-lg text-gray-500">(<?php echo count($staff_members); ?>)</span>
                        </h2>
                        
                        <div class="grid md:grid-cols-3 lg:grid-cols-4 gap-6">
                            <?php foreach ($staff_members as $staff): ?>
                                <?php
                                $staff_photo = getStaffPhoto($staff['photo_url'], $staff['name'], $team);
                                $border_color = $team === 'CITY' ? 'border-sky-400' : 'border-red-500';
                                $accent_color = $team === 'CITY' ? 'sky' : 'red';
                                ?>
                                
                                <div class="flip-card-container">
                                    <div class="flip-card" onclick="this.classList.toggle('flipped')">
                                        
                                        <!-- FRONT SIDE -->
                                        <div class="flip-card-front">
                                            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden border-4 <?php echo $border_color; ?> h-full hover:shadow-3xl transition-shadow relative">

                                                
                                                <!-- Photo - Full Size -->
                                                <div class="flex justify-center items-center p-6 pt-8">
                                                    <div class="w-48 h-48 rounded-full overflow-hidden border-4 border-<?php echo $team === 'CITY' ? 'sky-400' : 'red-500'; ?> shadow-2xl bg-gray-100">
                                                        <img 
                                                            src="<?php echo $staff_photo; ?>" 
                                                            alt="<?php echo $staff['name']; ?>"
                                                            class="w-full h-full object-cover"
                                                            onerror="this.src='<?php echo getStaffPhoto('', $staff['name'], $team); ?>'"
                                                        >
                                                    </div>
                                                </div>
                                                
                                                <!-- Name -->
                                                <div class="px-6 text-center">
                                                    <h3 class="text-xl font-black text-gray-900 mb-2 line-clamp-2 min-h-[56px] flex items-center justify-center">
                                                        <?php echo $staff['name']; ?>
                                                    </h3>
                                                    <p class="text-<?php echo $accent_color; ?>-600 font-bold text-sm mb-4">
                                                        <?php echo $staff['role']; ?>
                                                    </p>
                                                </div>
                                                
                                                <!-- Quick Info -->
                                                <div class="px-6 pb-4 space-y-2">
                                                    <div class="flex items-center justify-center text-gray-600 text-sm">
                                                        <span class="font-semibold"><?php echo $staff['nationality']; ?></span>
                                                    </div>
                                                    <?php if (!empty($staff['join_date'])): ?>
                                                        <div class="flex items-center justify-center text-gray-600 text-sm">
                                                            <span class="font-semibold">Sejak <?php echo date('Y', strtotime($staff['join_date'])); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Flip Hint -->
                                                <div class="absolute bottom-3 left-0 right-0 text-center px-4">
                                                    <p class="text-xs text-gray-500 font-medium bg-white/80 py-2 rounded-lg">
                                                        Klik untuk info lengkap
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- BACK SIDE -->
                                        <div class="flip-card-back">
                                            <div class="bg-gradient-to-br from-<?php echo $accent_color; ?>-500 to-<?php echo $accent_color === 'sky' ? 'blue' : 'red'; ?>-900 text-white rounded-2xl shadow-2xl h-full p-6 flex flex-col border-4 <?php echo $border_color; ?>">
                                                <!-- Header -->
                                                <div class="text-center mb-4">
                                                    <h3 class="text-2xl font-bold mb-2"><?php echo $staff['name']; ?></h3>
                                                    <p class="text-lg opacity-90 font-semibold"><?php echo $staff['role']; ?></p>
                                                    <?php if (!empty($staff['position'])): ?>
                                                        <p class="text-sm opacity-75"><?php echo $staff['position']; ?></p>
                                                    <?php endif; ?>
                                                    <?php if (!empty($staff['jersey_number'])): ?>
                                                        <p class="text-sm opacity-75 mt-1">No. <?php echo $staff['jersey_number']; ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Detailed Info -->
                                                <div class="flex-1 space-y-2 text-sm">
                                                    <div class="flex justify-between pb-2 border-b border-white/20">
                                                        <span>Kebangsaan:</span>
                                                        <span class="font-bold"><?php echo $staff['nationality']; ?></span>
                                                    </div>
                                                    
                                                    <div class="flex justify-between pb-2 border-b border-white/20">
                                                        <span>Tanggal Lahir:</span>
                                                        <span class="font-bold"><?php echo formatDateIndo($staff['birth_date']); ?></span>
                                                     </div>
                                                    
                                                    <?php if (!empty($staff['join_date'])): ?>
                                                    <div class="flex justify-between pb-2 border-b border-white/20">
                                                        <span>Tanggal Bergabung:</span>
                                                        <span class="font-bold"><?php echo formatDateIndo($staff['join_date']); ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                            

                                                </div>
                                                
                                                <!-- Flip Back Hint -->
                                                <div class="mt-4 pt-3 border-t border-white/20 text-center">
                                                    <p class="text-xs opacity-70">
                                                         Klik untuk kembali
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Trophy Detail Modal -->
    <div id="trophyModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4" onclick="closeTrophyModal(event)">
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden" onclick="event.stopPropagation()">
            <!-- Modal Header -->
            <div id="modalHeader" class="relative h-64 flex items-center justify-center p-8">
                <button onclick="closeTrophyModal()" class="absolute top-4 right-4 w-10 h-10 bg-white/90 hover:bg-white rounded-full flex items-center justify-center text-gray-700 transition shadow-lg z-10">
                    <span class="text-xl">✕</span>
                </button>
                <img id="modalTrophyImage" src="" alt="" class="max-h-52 max-w-full object-contain filter drop-shadow-2xl">
            </div>
            
            <!-- Modal Body -->
            <div class="p-8">
                <div class="text-center mb-6">
                    <h2 id="modalTrophyName" class="text-3xl font-black text-gray-900 mb-2"></h2>
                    <div id="modalWinCount" class="inline-block px-6 py-2 bg-gradient-to-r rounded-full text-white text-2xl font-bold shadow-lg"></div>
                </div>
                
                <!-- Years Grid -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 text-center">🏆 Tahun Kemenangan</h3>
                    <div id="modalYearsGrid" class="grid grid-cols-4 sm:grid-cols-6 gap-3">
                        <!-- Years will be inserted here -->
                    </div>
                </div>
                
                <!-- Close Button -->
                <button onclick="closeTrophyModal()" class="w-full py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold rounded-lg transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById('tab-' + tabName).classList.add('active');
            document.querySelector('[data-tab="' + tabName + '"]').classList.add('active');
        }
        
        function toggleTrophyYears(trophyId) {
            const yearsDiv = document.getElementById('trophy-years-' + trophyId);
            if (yearsDiv) {
                yearsDiv.classList.toggle('hidden');
            }
        }
        
        // Open trophy modal
        function openTrophyModal(trophy, years, team) {
            const modal = document.getElementById('trophyModal');
            const modalHeader = document.getElementById('modalHeader');
            const modalImage = document.getElementById('modalTrophyImage');
            const modalName = document.getElementById('modalTrophyName');
            const modalWinCount = document.getElementById('modalWinCount');
            const modalYearsGrid = document.getElementById('modalYearsGrid');
            
            // Set colors based on team
            const bgColor = team === 'CITY' ? 'bg-gradient-to-br from-sky-100 to-blue-200' : 'bg-gradient-to-br from-red-100 to-red-200';
            const badgeColor = team === 'CITY' ? 'from-sky-500 to-blue-700' : 'from-red-500 to-red-700';
            const yearBgColor = team === 'CITY' ? 'bg-sky-500' : 'bg-red-500';
            
            modalHeader.className = 'relative h-64 flex items-center justify-center p-8 ' + bgColor;
            
            // Set trophy image
            if (trophy.trophy_image) {
                modalImage.src = trophy.trophy_image;
                modalImage.alt = trophy.trophy_name;
                modalImage.style.display = 'block';
            } else {
                modalImage.style.display = 'none';
            }
            
            // Set trophy name
            modalName.textContent = trophy.trophy_name;
            
            // Set win count
            modalWinCount.className = 'inline-block px-6 py-2 bg-gradient-to-r rounded-full text-white text-2xl font-bold shadow-lg ' + badgeColor;
            modalWinCount.innerHTML = trophy.win_count + '× <span class="text-sm">Juara</span>';
            
            // Set years
            modalYearsGrid.innerHTML = '';
            years.forEach(year => {
                const yearDiv = document.createElement('div');
                yearDiv.className = 'text-center py-3 ' + yearBgColor + ' text-white font-bold rounded-lg shadow hover:scale-105 transition';
                yearDiv.textContent = year.trim();
                modalYearsGrid.appendChild(yearDiv);
            });
            
            // Show modal
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        // Close trophy modal
        function closeTrophyModal(event) {
            if (!event || event.target.id === 'trophyModal') {
                const modal = document.getElementById('trophyModal');
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        }
        
        // Keyboard support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeTrophyModal();
                document.querySelectorAll('.flip-card.flipped').forEach(card => {
                    card.classList.remove('flipped');
                });
            }
        });

        // Handle URL parameter tab on page load
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            
            if (tab && ['info', 'trophies', 'players', 'staff'].includes(tab)) {
                switchTab(tab);
            }
        });
    </script>

</body>
</html>