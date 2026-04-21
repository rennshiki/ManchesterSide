<?php
/**
 * Manchester Side - Setup Match Goals Table
 * Jalankan file ini sekali untuk membuat tabel match_goals
 */
require_once '../../includes/config.php';

if (!isAdminLoggedIn()) {
    redirect('../login.php');
}

$db = getDB();
$success = true;
$messages = [];

try {
    // Create match_goals table
    $sql = "CREATE TABLE IF NOT EXISTS match_goals (
        id INT PRIMARY KEY AUTO_INCREMENT,
        match_id INT NOT NULL,
        player_id INT NOT NULL,
        team_id INT NOT NULL,
        minute INT NOT NULL,
        goal_type ENUM('goal', 'penalty', 'own_goal', 'free_kick') DEFAULT 'goal',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
        FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
        FOREIGN KEY (team_id) REFERENCES clubs(id) ON DELETE CASCADE,
        INDEX idx_match (match_id),
        INDEX idx_player (player_id),
        INDEX idx_team (team_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($db->query($sql)) {
        $messages[] = "✅ Tabel match_goals berhasil dibuat";
        
        // Add logo_url column if not exists
        $check_column = $db->query("SHOW COLUMNS FROM clubs LIKE 'logo_url'");
        if ($check_column->num_rows == 0) {
            $db->query("ALTER TABLE clubs ADD COLUMN logo_url VARCHAR(500) DEFAULT NULL AFTER achievements");
            $messages[] = "✅ Kolom logo_url ditambahkan ke tabel clubs";
        }
        
        // Update Manchester City and United with logo URLs only
        $db->query("UPDATE clubs SET logo_url = 'https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg' WHERE code = 'CITY'");
        $db->query("UPDATE clubs SET logo_url = 'https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg' WHERE code = 'UNITED'");
        
        $messages[] = "✅ Logo URL Manchester City & United diperbarui";
        $messages[] = "ℹ️ Klub lain akan otomatis ditambahkan saat membuat jadwal pertandingan";
    } else {
        throw new Exception("Error creating table: " . $db->error);
    }
    
    // Check if sample data already exists
    $check = $db->query("SELECT COUNT(*) as count FROM match_goals");
    $existing_count = $check->fetch_assoc()['count'];
    
    if ($existing_count == 0) {
        // Insert sample goals only if matches and players exist
        $check_matches = $db->query("SELECT COUNT(*) as count FROM matches");
        $match_count = $check_matches->fetch_assoc()['count'];
        
        $check_players = $db->query("SELECT COUNT(*) as count FROM players");
        $player_count = $check_players->fetch_assoc()['count'];
        
        if ($match_count > 0 && $player_count > 0) {
            $sample_goals = [
                // Only add goals for existing matches and players
                // Check if match 1, 2, 3 exist and players exist
            ];
            
            // Get existing matches
            $existing_matches = $db->query("SELECT id FROM matches ORDER BY id LIMIT 3");
            $match_ids = [];
            while ($match = $existing_matches->fetch_assoc()) {
                $match_ids[] = $match['id'];
            }
            
            // Get existing players
            $existing_players = $db->query("SELECT id, club_id FROM players ORDER BY id LIMIT 10");
            $player_data = [];
            while ($player = $existing_players->fetch_assoc()) {
                $player_data[] = $player;
            }
            
            if (count($match_ids) >= 1 && count($player_data) >= 3) {
                // Add sample goals with existing data
                $sample_goals = [
                    [$match_ids[0], $player_data[0]['id'], $player_data[0]['club_id'], 15, 'goal'],
                    [$match_ids[0], $player_data[1]['id'], $player_data[1]['club_id'], 32, 'goal'],
                ];
                
                if (count($match_ids) >= 2) {
                    $sample_goals[] = [$match_ids[1], $player_data[2]['id'], $player_data[2]['club_id'], 23, 'goal'];
                }
                
                $stmt = $db->prepare("INSERT INTO match_goals (match_id, player_id, team_id, minute, goal_type) VALUES (?, ?, ?, ?, ?)");
                
                foreach ($sample_goals as $goal) {
                    $stmt->bind_param("iiiis", $goal[0], $goal[1], $goal[2], $goal[3], $goal[4]);
                    if (!$stmt->execute()) {
                        // Skip if error, don't throw exception
                        continue;
                    }
                }
                
                $messages[] = "✅ Data sample gol berhasil ditambahkan (" . count($sample_goals) . " gol)";
            } else {
                $messages[] = "ℹ️ Skip sample data - belum ada match/player yang cukup";
            }
        } else {
            $messages[] = "ℹ️ Skip sample data - belum ada match atau player";
        }
    } else {
        $messages[] = "ℹ️ Data sample sudah ada ($existing_count gol)";
    }
    
} catch (Exception $e) {
    $success = false;
    $messages[] = "❌ Error: " . $e->getMessage();
}

$page_title = "Setup Database";
include '../includes/header.php';
?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <div class="mb-6">
        <a href="index.php" class="text-blue-600 hover:text-blue-800 font-semibold">← Kembali ke Jadwal</a>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">🔧 Setup Database</h1>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-6 py-4 rounded-lg mb-6">
                <div class="flex items-center">
                    <span class="text-2xl mr-3">🎉</span>
                    <div>
                        <h3 class="font-bold text-lg">Setup Berhasil!</h3>
                        <p>Tabel match_goals sudah siap digunakan.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-lg mb-6">
                <div class="flex items-center">
                    <span class="text-2xl mr-3">❌</span>
                    <div>
                        <h3 class="font-bold text-lg">Setup Gagal!</h3>
                        <p>Terjadi error saat setup database.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-gray-50 border border-gray-200 p-4 rounded-lg mb-6">
            <h4 class="font-bold text-gray-900 mb-3">Detail Setup:</h4>
            <div class="space-y-2">
                <?php foreach ($messages as $message): ?>
                    <div class="text-sm text-gray-700"><?php echo $message; ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg mb-6">
                <h4 class="font-bold text-blue-900 mb-3">🎯 Fitur yang Tersedia:</h4>
                <ul class="list-disc list-inside space-y-1 text-blue-800 text-sm">
                    <li>Input nama tim manual (Arsenal, Chelsea, Liverpool, dll)</li>
                    <li>Tim baru otomatis ditambahkan ke database (hanya untuk jadwal)</li>
                    <li>Detail gol dengan menit dan pencetak gol untuk SEMUA tim</li>
                    <li>Pemain otomatis ditambahkan jika belum ada di database</li>
                    <li>Link ke profil pemain HANYA untuk Manchester City & United</li>
                    <li>Autocomplete nama pemain dari semua tim</li>
                </ul>
                
                <div class="mt-4 p-3 bg-white rounded border">
                    <h5 class="font-semibold text-blue-900 mb-2">📝 Contoh Penggunaan:</h5>
                    <div class="text-xs text-blue-700">
                        <strong>Tim Home:</strong> Manchester City<br>
                        <strong>Tim Away:</strong> Arsenal<br>
                        <strong>Skor:</strong> 3-1<br>
                        <strong>Gol:</strong> 15' Haaland (link), 32' De Bruyne (link), 78' Saka (teks biasa)
                    </div>
                </div>
                
                <div class="mt-4 p-3 bg-yellow-50 rounded border border-yellow-300">
                    <h5 class="font-semibold text-yellow-900 mb-2">⚠️ Catatan Penting:</h5>
                    <div class="text-xs text-yellow-800">
                        <strong>Klub yang Dikelola Penuh:</strong> Hanya Manchester City & Manchester United<br>
                        <strong>Klub Lain:</strong> Otomatis dibuat saat input jadwal (data minimal)<br>
                        <strong>Admin Klub:</strong> Hanya tersedia untuk City & United<br>
                        <strong>Pemain Link:</strong> Hanya untuk pemain City & United
                    </div>
                </div>
            </div>
            
            <div class="bg-green-50 border border-green-200 p-4 rounded-lg mb-6">
                <h4 class="font-bold text-green-900 mb-3">💡 Info Database:</h4>
                <p class="text-green-800 text-sm">
                    Semua tabel dan data sudah tersedia di file <code>database/manchester_side.sql</code>. 
                    Jika Anda setup database baru, cukup import file tersebut dan semua fitur akan langsung berfungsi.
                </p>
            </div>
        <?php endif; ?>

        <!-- Test Section -->
        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg mb-6">
            <h4 class="font-bold text-yellow-900 mb-3">🔍 Test Pencetak Gol</h4>
            
            <?php
            // Test 1: Cek tabel match_goals
            echo "<div class='mb-4'>";
            echo "<h5 class='font-semibold text-yellow-800 mb-2'>1. Status Tabel match_goals</h5>";
            try {
                $check_table = $db->query("SHOW TABLES LIKE 'match_goals'");
                if ($check_table->num_rows > 0) {
                    echo "<span class='text-green-600'>✅ Tabel match_goals ADA</span><br>";
                } else {
                    echo "<span class='text-red-600'>❌ Tabel match_goals TIDAK ADA</span><br>";
                }
            } catch (Exception $e) {
                echo "<span class='text-red-600'>❌ Error: " . $e->getMessage() . "</span><br>";
            }
            echo "</div>";

            // Test 2: Cek data gol
            echo "<div class='mb-4'>";
            echo "<h5 class='font-semibold text-yellow-800 mb-2'>2. Data Gol Tersimpan</h5>";
            try {
                $goals = $db->query("SELECT COUNT(*) as count FROM match_goals");
                $goal_count = $goals->fetch_assoc()['count'];
                
                if ($goal_count > 0) {
                    echo "<span class='text-green-600'>✅ Ditemukan $goal_count gol</span><br>";
                    
                    // Show sample goals
                    $sample_goals = $db->query("SELECT mg.minute, p.name as player_name, c.name as team_name 
                                               FROM match_goals mg 
                                               JOIN players p ON mg.player_id = p.id 
                                               JOIN clubs c ON mg.team_id = c.id 
                                               LIMIT 3");
                    
                    if ($sample_goals->num_rows > 0) {
                        echo "<div class='text-xs text-yellow-700 mt-1'>Contoh: ";
                        $examples = [];
                        while ($goal = $sample_goals->fetch_assoc()) {
                            $examples[] = "{$goal['minute']}' {$goal['player_name']} ({$goal['team_name']})";
                        }
                        echo implode(", ", $examples);
                        echo "</div>";
                    }
                } else {
                    echo "<span class='text-orange-600'>⚠️ Belum ada data gol</span><br>";
                }
            } catch (Exception $e) {
                echo "<span class='text-red-600'>❌ Error: " . $e->getMessage() . "</span><br>";
            }
            echo "</div>";

            // Test 3: Cek pertandingan selesai
            echo "<div class='mb-4'>";
            echo "<h5 class='font-semibold text-yellow-800 mb-2'>3. Pertandingan Selesai</h5>";
            try {
                $finished_matches = $db->query("SELECT COUNT(*) as count FROM matches WHERE status = 'finished'");
                $finished_count = $finished_matches->fetch_assoc()['count'];
                
                if ($finished_count > 0) {
                    echo "<span class='text-green-600'>✅ Ditemukan $finished_count pertandingan selesai</span><br>";
                } else {
                    echo "<span class='text-orange-600'>⚠️ Belum ada pertandingan selesai</span><br>";
                    echo "<div class='text-xs text-yellow-700 mt-1'>Tip: Edit pertandingan dan ubah status ke 'Selesai'</div>";
                }
            } catch (Exception $e) {
                echo "<span class='text-red-600'>❌ Error: " . $e->getMessage() . "</span><br>";
            }
            echo "</div>";

            // Test 4: Test query fixtures
            echo "<div>";
            echo "<h5 class='font-semibold text-yellow-800 mb-2'>4. Test Query Fixtures</h5>";
            try {
                $test_query = "SELECT m.id, h.name as home_team, a.name as away_team, m.home_score, m.away_score
                              FROM matches m
                              JOIN clubs h ON m.home_team_id = h.id
                              JOIN clubs a ON m.away_team_id = a.id
                              WHERE m.status = 'finished'
                              LIMIT 1";
                
                $test_result = $db->query($test_query);
                
                if ($test_result->num_rows > 0) {
                    $match = $test_result->fetch_assoc();
                    echo "<span class='text-green-600'>✅ Query fixtures berhasil</span><br>";
                    echo "<div class='text-xs text-yellow-700 mt-1'>Sample: {$match['home_team']} {$match['home_score']}-{$match['away_score']} {$match['away_team']}</div>";
                    
                    // Test goals query
                    $goals_query = "SELECT COUNT(*) as count FROM match_goals WHERE match_id = {$match['id']}";
                    $goals_test = $db->query($goals_query);
                    $goals_for_match = $goals_test->fetch_assoc()['count'];
                    
                    if ($goals_for_match > 0) {
                        echo "<div class='text-xs text-green-700 mt-1'>⚽ Match ini memiliki $goals_for_match gol</div>";
                    } else {
                        echo "<div class='text-xs text-orange-700 mt-1'>⚠️ Match ini belum ada data gol</div>";
                    }
                } else {
                    echo "<span class='text-orange-600'>⚠️ Tidak ada pertandingan untuk ditest</span><br>";
                }
            } catch (Exception $e) {
                echo "<span class='text-red-600'>❌ Error: " . $e->getMessage() . "</span><br>";
            }
            echo "</div>";
            ?>
            
            <div class="mt-4 p-3 bg-white rounded border">
                <p class="text-xs text-yellow-700">
                    <strong>💡 Kesimpulan:</strong> Jika semua test menunjukkan ✅, maka pencetak gol sudah berfungsi dengan baik.
                    Jika ada ❌ atau ⚠️, ikuti petunjuk untuk memperbaiki masalah.
                </p>
            </div>
        </div>

        <div class="flex space-x-3">
            <a href="index.php" class="px-6 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition">
                📅 Lihat Jadwal
            </a>
            <a href="create.php" class="px-6 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition">
                ➕ Tambah Pertandingan
            </a>
            <a href="../../fixtures.php?tab=results" class="px-6 py-3 bg-purple-600 text-white font-bold rounded-lg hover:bg-purple-700 transition">
                🎯 Lihat Hasil & Gol
            </a>
        </div>
    </div>

</main>

<?php include '../../includes/footer.php'; ?>