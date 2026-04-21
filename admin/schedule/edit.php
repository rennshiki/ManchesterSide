<?php
/**
 * Manchester Side - Admin Edit Schedule
 */
require_once '../../includes/config.php';

if (!isAdminLoggedIn()) {
    redirect('../login.php');
}

$db = getDB();
$admin = getCurrentAdmin();
$errors = [];

// Get match ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    redirect('index.php');
}

// Get match data
$stmt = $db->prepare("SELECT * FROM matches WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();

if (!$match) {
    setFlashMessage('error', 'Jadwal tidak ditemukan');
    redirect('index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $home_team_name = lightSanitize($_POST['home_team_name']);
    $away_team_name = lightSanitize($_POST['away_team_name']);
    $home_logo_url = lightSanitize($_POST['home_logo_url']);
    $away_logo_url = lightSanitize($_POST['away_logo_url']);
    $competition = lightSanitize($_POST['competition']);
    $match_date = $_POST['match_date'];
    $venue = lightSanitize($_POST['venue']);
    $status = $_POST['status'];
    $home_score = isset($_POST['home_score']) ? (int)$_POST['home_score'] : null;
    $away_score = isset($_POST['away_score']) ? (int)$_POST['away_score'] : null;
    
    // Goals data
    $goal_minutes = $_POST['goal_minute'] ?? [];
    $goal_players = $_POST['goal_player'] ?? [];
    $goal_teams = $_POST['goal_team'] ?? [];
    $goal_types = $_POST['goal_type'] ?? [];
    
    // Validation
    if (empty($home_team_name) || empty($away_team_name)) {
        $errors[] = 'Nama tim harus diisi';
    }
    
    if ($home_team_name === $away_team_name) {
        $errors[] = 'Tim home dan away tidak boleh sama';
    }
    
    if (empty($competition)) {
        $errors[] = 'Kompetisi wajib diisi';
    }
    
    if (empty($match_date)) {
        $errors[] = 'Tanggal pertandingan wajib diisi';
    }
    
    if (empty($errors)) {
        // Get or create team IDs with logo URLs
        $home_team_id = getOrCreateTeam($db, $home_team_name, $home_logo_url);
        $away_team_id = getOrCreateTeam($db, $away_team_name, $away_logo_url);
        
        $stmt = $db->prepare("UPDATE matches SET home_team_id = ?, away_team_id = ?, competition = ?, match_date = ?, venue = ?, status = ?, home_score = ?, away_score = ? WHERE id = ?");
        $stmt->bind_param("iissssiii", $home_team_id, $away_team_id, $competition, $match_date, $venue, $status, $home_score, $away_score, $id);
        
        if ($stmt->execute()) {
            // Delete existing goals and insert new ones if table exists
            try {
                $check_table = $db->query("SHOW TABLES LIKE 'match_goals'");
                if ($check_table->num_rows > 0) {
                    $stmt_delete = $db->prepare("DELETE FROM match_goals WHERE match_id = ?");
                    $stmt_delete->bind_param("i", $id);
                    $stmt_delete->execute();
                    
                    // Insert new goals if any
                    if (!empty($goal_minutes)) {
                        insertMatchGoals($db, $id, $goal_minutes, $goal_players, $goal_teams, $home_team_id, $away_team_id, $goal_types);
                    }
                }
            } catch (Exception $e) {
                // Ignore if match_goals table doesn't exist
            }
            
            setFlashMessage('success', 'Jadwal berhasil diupdate');
            redirect('index.php');
        } else {
            $errors[] = 'Gagal mengupdate jadwal';
        }
    }
}

// Helper functions (same as create.php)
function getOrCreateTeam($db, $team_name, $logo_url = '') {
    // Validate logo URL - reject base64 data and very long URLs
    if (!empty($logo_url)) {
        // Reject base64 data URLs
        if (strpos($logo_url, 'data:image') === 0) {
            $logo_url = ''; // Ignore base64 data
        }
        // Reject URLs longer than 500 characters
        if (strlen($logo_url) > 500) {
            $logo_url = ''; // Ignore too long URLs
        }
    }
    
    $stmt = $db->prepare("SELECT id FROM clubs WHERE name = ?");
    $stmt->bind_param("s", $team_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Update logo if provided and valid
        if (!empty($logo_url)) {
            $update_stmt = $db->prepare("UPDATE clubs SET logo_url = ? WHERE id = ?");
            $update_stmt->bind_param("si", $logo_url, $row['id']);
            $update_stmt->execute();
        }
        return $row['id'];
    }
    
    // Create new team with minimal data (for schedule only)
    $code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $team_name), 0, 3));
    if (empty($code)) $code = 'TIM';
    
    // Make sure code is unique
    $check_code = $db->prepare("SELECT id FROM clubs WHERE code = ?");
    $check_code->bind_param("s", $code);
    $check_code->execute();
    if ($check_code->get_result()->num_rows > 0) {
        $code = $code . rand(1, 99);
    }
    
    $stmt = $db->prepare("INSERT INTO clubs (name, full_name, code, founded_year, stadium_name, logo_url) VALUES (?, ?, ?, 1900, 'Unknown Stadium', ?)");
    $stmt->bind_param("ssss", $team_name, $team_name, $code, $logo_url);
    $stmt->execute();
    
    return $db->insert_id;
}

function insertMatchGoals($db, $match_id, $minutes, $players, $teams, $home_team_id, $away_team_id, $goal_types = []) {
    for ($i = 0; $i < count($minutes); $i++) {
        if (!empty($minutes[$i]) && !empty($players[$i]) && !empty($teams[$i])) {
            $minute = (int)$minutes[$i];
            $player_name = lightSanitize($players[$i]);
            $team_side = $teams[$i];
            $goal_type = isset($goal_types[$i]) ? $goal_types[$i] : 'goal';
            
            $team_id = ($team_side === 'home') ? $home_team_id : $away_team_id;
            $player_id = getOrCreatePlayer($db, $player_name, $team_id);
            
            if ($player_id) {
                $stmt = $db->prepare("INSERT INTO match_goals (match_id, player_id, team_id, minute, goal_type) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiis", $match_id, $player_id, $team_id, $minute, $goal_type);
                $stmt->execute();
            }
        }
    }
}

function getOrCreatePlayer($db, $player_name, $team_id) {
    $stmt = $db->prepare("SELECT code FROM clubs WHERE id = ?");
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $team = $stmt->get_result()->fetch_assoc();
    
    if (!$team) {
        return null;
    }
    
    $stmt = $db->prepare("SELECT id FROM players WHERE name = ? AND club_id = ?");
    $stmt->bind_param("si", $player_name, $team_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['id'];
    }
    
    // Find available jersey number
    $jersey_result = $db->query("SELECT MAX(jersey_number) as max_jersey FROM players WHERE club_id = $team_id");
    $max_jersey = $jersey_result->fetch_assoc()['max_jersey'] ?? 0;
    $new_jersey = $max_jersey + 1;
    if ($new_jersey > 99) $new_jersey = 99;
    
    $stmt = $db->prepare("INSERT INTO players (name, club_id, position, jersey_number, nationality) VALUES (?, ?, 'Forward', ?, 'Unknown')");
    $stmt->bind_param("sii", $player_name, $team_id, $new_jersey);
    $stmt->execute();
    
    return $db->insert_id;
}

// Get team names for the match
$stmt = $db->prepare("SELECT h.name as home_name, a.name as away_name FROM matches m JOIN clubs h ON m.home_team_id = h.id JOIN clubs a ON m.away_team_id = a.id WHERE m.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$team_names = $stmt->get_result()->fetch_assoc();

// Get existing goals
$existing_goals = [];
try {
    $check_table = $db->query("SHOW TABLES LIKE 'match_goals'");
    if ($check_table->num_rows > 0) {
        $goals_result = $db->query("SELECT mg.*, p.name as player_name, c.code as team_code FROM match_goals mg JOIN players p ON mg.player_id = p.id JOIN clubs c ON mg.team_id = c.id WHERE mg.match_id = $id ORDER BY mg.minute");
        if ($goals_result) {
            while ($goal = $goals_result->fetch_assoc()) {
                $existing_goals[] = $goal;
            }
        }
    }
} catch (Exception $e) {
    // Table doesn't exist, keep empty array
}

// Get all players for autocomplete selection
$man_players = $db->query("SELECT p.*, c.name as club_name, c.code as club_code FROM players p JOIN clubs c ON p.club_id = c.id ORDER BY c.name, p.name");

$page_title = "Edit Jadwal Pertandingan";
include '../includes/header.php';
?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <div class="mb-6">
        <a href="index.php" class="text-blue-600 hover:text-blue-800 font-semibold">← Kembali ke Daftar Jadwal</a>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">✏️ Edit Jadwal Pertandingan</h1>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            
            <!-- Home Team -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Tim Home</label>
                <input type="text" name="home_team_name" required value="<?php echo $team_names['home_name']; ?>" 
                       placeholder="Contoh: Manchester City, Arsenal, Chelsea" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent">
            </div>

            <!-- Away Team -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Tim Away</label>
                <input type="text" name="away_team_name" required value="<?php echo $team_names['away_name']; ?>" 
                       placeholder="Contoh: Manchester United, Liverpool, Tottenham" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent">
            </div>

            <!-- Logo URLs -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Logo Tim Home (URL)</label>
                    <input type="url" name="home_logo_url" value="<?php echo $_POST['home_logo_url'] ?? ''; ?>" 
                           placeholder="https://example.com/logo.png" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Opsional. Jika kosong, akan menggunakan logo default.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Logo Tim Away (URL)</label>
                    <input type="url" name="away_logo_url" value="<?php echo $_POST['away_logo_url'] ?? ''; ?>" 
                           placeholder="https://example.com/logo.png" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Opsional. Jika kosong, akan menggunakan logo default.</p>
                </div>
            </div>

            <!-- Competition -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Kompetisi</label>
                <select name="competition" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent">
                    <option value="">Pilih Kompetisi</option>
                    <option value="Premier League" <?php echo ($match['competition'] === 'Premier League') ? 'selected' : ''; ?>>Premier League</option>
                    <option value="FA Cup" <?php echo ($match['competition'] === 'FA Cup') ? 'selected' : ''; ?>>FA Cup</option>
                    <option value="Carabao Cup" <?php echo ($match['competition'] === 'Carabao Cup') ? 'selected' : ''; ?>>Carabao Cup</option>
                    <option value="Champions League" <?php echo ($match['competition'] === 'Champions League') ? 'selected' : ''; ?>>Champions League</option>
                    <option value="Europa League" <?php echo ($match['competition'] === 'Europa League') ? 'selected' : ''; ?>>Europa League</option>
                    <option value="Community Shield" <?php echo ($match['competition'] === 'Community Shield') ? 'selected' : ''; ?>>Community Shield</option>
                </select>
            </div>

            <!-- Match Date -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Tanggal & Waktu Pertandingan</label>
                <input type="datetime-local" name="match_date" required value="<?php echo date('Y-m-d\TH:i', strtotime($match['match_date'])); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent">
            </div>

            <!-- Venue -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Venue (Opsional)</label>
                <input type="text" name="venue" value="<?php echo $match['venue']; ?>" placeholder="Contoh: Old Trafford, Etihad Stadium" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent">
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Status</label>
                <select name="status" id="status" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent">
                    <option value="scheduled" <?php echo ($match['status'] === 'scheduled') ? 'selected' : ''; ?>>Terjadwal</option>
                    <option value="finished" <?php echo ($match['status'] === 'finished') ? 'selected' : ''; ?>>Selesai</option>
                    <option value="postponed" <?php echo ($match['status'] === 'postponed') ? 'selected' : ''; ?>>Ditunda</option>
                </select>
            </div>

            <!-- Score Section (shown when status is finished) -->
            <div id="scoreSection" style="display: <?php echo ($match['status'] === 'finished') ? 'block' : 'none'; ?>;" class="border-t pt-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">⚽ Skor Pertandingan</h3>
                
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Skor Home</label>
                        <input type="number" name="home_score" id="home_score" min="0" value="<?php echo $match['home_score']; ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Skor Away</label>
                        <input type="number" name="away_score" id="away_score" min="0" value="<?php echo $match['away_score']; ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent">
                    </div>
                </div>

                <!-- Goals Section -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-bold text-gray-900">🥅 Detail Gol</h4>
                        <button type="button" onclick="addGoal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                            ➕ Tambah Gol
                        </button>
                    </div>
                    
                    <div id="goalsContainer" class="space-y-3">
                        <?php foreach ($existing_goals as $index => $goal): ?>
                            <div class="bg-gray-50 p-4 rounded-lg border">
                                <div class="flex items-center justify-between mb-3">
                                    <h5 class="font-semibold text-gray-900">Gol #<?php echo $index + 1; ?></h5>
                                    <button type="button" onclick="removeGoal(this)" class="text-red-600 hover:text-red-800 text-sm">
                                        🗑️ Hapus
                                    </button>
                                </div>
                                
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Menit</label>
                                        <input type="number" name="goal_minute[]" min="1" max="120" value="<?php echo $goal['minute']; ?>" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Tim</label>
                                        <select name="goal_team[]" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                            <option value="">Pilih Tim</option>
                                            <option value="home" <?php echo ($goal['team_id'] == $match['home_team_id']) ? 'selected' : ''; ?>>Home</option>
                                            <option value="away" <?php echo ($goal['team_id'] == $match['away_team_id']) ? 'selected' : ''; ?>>Away</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Jenis Gol</label>
                                        <select name="goal_type[]" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                            <option value="goal" <?php echo (!isset($goal['goal_type']) || $goal['goal_type'] === 'goal') ? 'selected' : ''; ?>>⚽ Gol Biasa</option>
                                            <option value="penalty" <?php echo (isset($goal['goal_type']) && $goal['goal_type'] === 'penalty') ? 'selected' : ''; ?>>🥅 Penalti</option>
                                            <option value="own_goal" <?php echo (isset($goal['goal_type']) && $goal['goal_type'] === 'own_goal') ? 'selected' : ''; ?>>🔄 Bunuh Diri</option>
                                            <option value="free_kick" <?php echo (isset($goal['goal_type']) && $goal['goal_type'] === 'free_kick') ? 'selected' : ''; ?>>🎯 Tendangan Bebas</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Pencetak Gol</label>
                                        <input type="text" name="goal_player[]" value="<?php echo $goal['player_name']; ?>" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm player-autocomplete">
                                        <div class="autocomplete-suggestions hidden absolute z-10 bg-white border border-gray-300 rounded-lg shadow-lg max-h-40 overflow-y-auto"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <p class="text-xs text-gray-500 mt-2">
                        💡 Fitur pencetak gol tersedia untuk semua tim. Pemain akan otomatis ditambahkan jika belum ada.
                    </p>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex gap-3">
                <button type="submit" class="flex-1 py-3 bg-gradient-to-r from-city-blue to-united-red text-white font-bold rounded-lg hover:shadow-lg transition">
                    💾 Update Jadwal
                </button>
                <a href="index.php" class="px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition">
                    Batal
                </a>
            </div>

        </form>
    </div>

</main>

<script>
// Players data for autocomplete
const playersData = <?php 
$players_array = [];
$man_players->data_seek(0);
while ($player = $man_players->fetch_assoc()) {
    $players_array[] = [
        'id' => $player['id'],
        'name' => $player['name'],
        'club' => $player['club_name'],
        'code' => $player['club_code']
    ];
}
echo json_encode($players_array);
?>;

let goalCount = <?php echo count($existing_goals); ?>;

// Show/hide score section based on status
document.getElementById('status').addEventListener('change', function() {
    const scoreSection = document.getElementById('scoreSection');
    if (this.value === 'finished') {
        scoreSection.style.display = 'block';
    } else {
        scoreSection.style.display = 'none';
        // Reset scores and goals when status is not finished
        resetScoresAndGoals();
    }
});

// Reset scores and goals
function resetScoresAndGoals() {
    // Reset score inputs
    document.getElementById('home_score').value = '';
    document.getElementById('away_score').value = '';
    
    // Clear all goals
    const goalsContainer = document.getElementById('goalsContainer');
    goalsContainer.innerHTML = '';
    goalCount = 0;
}

// Add goal function
function addGoal() {
    goalCount++;
    const container = document.getElementById('goalsContainer');
    
    const goalDiv = document.createElement('div');
    goalDiv.className = 'bg-gray-50 p-4 rounded-lg border';
    goalDiv.innerHTML = `
        <div class="flex items-center justify-between mb-3">
            <h5 class="font-semibold text-gray-900">Gol #${goalCount}</h5>
            <button type="button" onclick="removeGoal(this)" class="text-red-600 hover:text-red-800 text-sm">
                🗑️ Hapus
            </button>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Menit</label>
                <input type="number" name="goal_minute[]" min="1" max="120" placeholder="90" 
                       class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Tim</label>
                <select name="goal_team[]" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    <option value="">Pilih Tim</option>
                    <option value="home">Home</option>
                    <option value="away">Away</option>
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Jenis Gol</label>
                <select name="goal_type[]" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    <option value="goal">⚽ Gol Biasa</option>
                    <option value="penalty">🥅 Penalti</option>
                    <option value="own_goal">🔄 Bunuh Diri</option>
                    <option value="free_kick">🎯 Tendangan Bebas</option>
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Pencetak Gol</label>
                <input type="text" name="goal_player[]" placeholder="Nama pemain" 
                       class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm player-autocomplete">
                <div class="autocomplete-suggestions hidden absolute z-10 bg-white border border-gray-300 rounded-lg shadow-lg max-h-40 overflow-y-auto"></div>
            </div>
        </div>
    `;
    
    container.appendChild(goalDiv);
    setupAutocomplete(goalDiv.querySelector('.player-autocomplete'));
}

// Remove goal function
function removeGoal(button) {
    button.closest('.bg-gray-50').remove();
    // Update goal numbers after removal
    updateGoalNumbers();
}

// Update goal numbers
function updateGoalNumbers() {
    const goalDivs = document.querySelectorAll('#goalsContainer .bg-gray-50');
    goalDivs.forEach((div, index) => {
        const header = div.querySelector('h5');
        if (header) {
            header.textContent = `Gol #${index + 1}`;
        }
    });
    goalCount = goalDivs.length;
}

// Setup autocomplete for player inputs
function setupAutocomplete(input) {
    const suggestionsDiv = input.nextElementSibling;
    
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        suggestionsDiv.innerHTML = '';
        
        if (query.length < 2) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        const matches = playersData.filter(player => 
            player.name.toLowerCase().includes(query)
        ).slice(0, 5);
        
        if (matches.length > 0) {
            matches.forEach(player => {
                const div = document.createElement('div');
                div.className = 'px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm';
                div.innerHTML = `
                    <div class="font-semibold">${player.name}</div>
                    <div class="text-xs text-gray-500">${player.club}</div>
                `;
                div.addEventListener('click', () => {
                    input.value = player.name;
                    suggestionsDiv.classList.add('hidden');
                });
                suggestionsDiv.appendChild(div);
            });
            suggestionsDiv.classList.remove('hidden');
        } else {
            suggestionsDiv.classList.add('hidden');
        }
    });
    
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !suggestionsDiv.contains(e.target)) {
            suggestionsDiv.classList.add('hidden');
        }
    });
}

// Setup autocomplete for existing inputs
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.player-autocomplete').forEach(setupAutocomplete);
});
</script>


