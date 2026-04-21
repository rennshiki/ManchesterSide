<?php
/**
 * Manchester Side - User Registration Page
 */
require_once 'includes/config.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = lightSanitize($_POST['username'] ?? '');
    $email = lightSanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = lightSanitize($_POST['full_name'] ?? '');
    $favorite_team = lightSanitize($_POST['favorite_team'] ?? '');
    
    // Validation
    if (empty($username)) {
        $errors[] = 'Username wajib diisi';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username minimal 3 karakter';
    }
    
    if (empty($email)) {
        $errors[] = 'Email wajib diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid';
    }
    
    if (empty($password)) {
        $errors[] = 'Password wajib diisi';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Password dan konfirmasi password tidak cocok';
    }
    
    if (empty($full_name)) {
        $errors[] = 'Nama lengkap wajib diisi';
    }
    
    if (empty($favorite_team)) {
        $errors[] = 'Pilih salah satu tim favorit (Manchester City atau Manchester United)';
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        $db = getDB();
        
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = 'Username atau email sudah terdaftar';
        }
    }
    
    // Insert new user
    if (empty($errors)) {
        $db = getDB();
        $hashed_password = hashPassword($password);
        
        $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, favorite_team) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $favorite_team);
        
        if ($stmt->execute()) {
            $success = true;
            setFlashMessage('success', 'Registrasi berhasil! Silakan login.');
            
            // Auto redirect setelah 2 detik
            echo "<meta http-equiv='refresh' content='2;url=login.php'>";
        } else {
            $errors[] = 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - <?php echo SITE_NAME; ?></title>
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
        
        .gradient-bg {
            background: linear-gradient(135deg, #6CABDD 0%, #1C2C5B 50%, #DA291C 100%);
        }
    </style>
</head>
<body class="bg-gray-50">

    <?php include 'includes/header.php'; ?>

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            
            <!-- Registration Card -->
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                <div class="text-center mb-8">
                    <div class="flex justify-center mb-4">
                        <div class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center text-white text-3xl">
                            ⚽
                        </div>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Daftar Akun Baru</h2>
                    <p class="text-gray-600">Bergabung dengan komunitas Manchester Side</p>
                </div>

                <?php if ($success): ?>
                    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <span class="text-2xl mr-3">✅</span>
                            <div>
                                <p class="font-semibold">Registrasi Berhasil!</p>
                                <p class="text-sm">Mengarahkan ke halaman login...</p>
                            </div>
                        </div>
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

                <form method="POST" action="" class="space-y-5">
                    
                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">
                            Username
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            value="<?php echo $_POST['username'] ?? ''; ?>"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent transition"
                            placeholder="username_anda"
                        >
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                            Email
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?php echo $_POST['email'] ?? ''; ?>"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent transition"
                            placeholder="email@example.com"
                        >
                    </div>

                    <!-- Full Name -->
                    <div>
                        <label for="full_name" class="block text-sm font-semibold text-gray-700 mb-2">
                            Nama Lengkap
                        </label>
                        <input 
                            type="text" 
                            id="full_name" 
                            name="full_name" 
                            value="<?php echo $_POST['full_name'] ?? ''; ?>"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent transition"
                            placeholder="Nama Lengkap Anda"
                        >
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                            Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent transition"
                            placeholder="Minimal 6 karakter"
                        >
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-2">
                            Konfirmasi Password
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent transition"
                            placeholder="Ulangi password"
                        >
                    </div>

                    <!-- Favorite Team -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">
                            Pilih Tim Favorit <span class="text-red-600">*</span>
                        </label>
                        <!-- PERUBAHAN 2: Logo di Pilihan Tim Favorit -->
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative cursor-pointer">
                                <input 
                                    type="radio" 
                                    name="favorite_team" 
                                    value="CITY" 
                                    class="peer sr-only"
                                    <?php echo (($_POST['favorite_team'] ?? '') === 'CITY') ? 'checked' : ''; ?>
                                >
                                <div class="border-2 border-gray-300 peer-checked:border-city-blue peer-checked:bg-city-blue/10 rounded-lg p-4 text-center transition">
                                    <img src="https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg" alt="Man City" class="w-16 h-16 mx-auto mb-2 object-contain">
                                    <div class="font-bold text-gray-700 peer-checked:text-city-blue">Man City</div>
                                </div>
                            </label>

                            <label class="relative cursor-pointer">
                                <input 
                                    type="radio" 
                                    name="favorite_team" 
                                    value="UNITED" 
                                    class="peer sr-only"
                                    <?php echo (($_POST['favorite_team'] ?? '') === 'UNITED') ? 'checked' : ''; ?>
                                >
                                <div class="border-2 border-gray-300 peer-checked:border-united-red peer-checked:bg-united-red/10 rounded-lg p-4 text-center transition">
                                    <img src="https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg" alt="Man United" class="w-16 h-16 mx-auto mb-2 object-contain">
                                    <div class="font-bold text-gray-700 peer-checked:text-united-red">Man United</div>
                                </div>
                            </label>
                        </div>
                        <p class="text-xs text-red-600 mt-2 font-semibold">
                            ⚠️ Wajib memilih salah satu tim favorit untuk melanjutkan
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit"
                        class="w-full gradient-bg text-white font-bold py-3 px-4 rounded-lg hover:shadow-lg transform hover:scale-[1.02] transition duration-200"
                    >
                        Daftar Sekarang
                    </button>

                </form>

                <!-- Login Link -->
                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        Sudah punya akun? 
                        <a href="login.php" class="text-city-blue font-semibold hover:underline">
                            Login di sini
                        </a>
                    </p>
                </div>

            </div>

            <!-- Info Card -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                <p class="font-semibold mb-2">✨ Keuntungan Bergabung:</p>
                <ul class="space-y-1 text-xs">
                    <li>✅ Simpan berita favorit</li>
                    <li>✅ Personalisasi profil dengan warna tim</li>
                    <li>✅ Akses konten eksklusif</li>
                    <li>✅ Komentar di artikel</li>
                </ul>
            </div>

        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Form validation enhancement
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const favoriteTeam = document.querySelector('input[name="favorite_team"]:checked');
            
            if (!favoriteTeam) {
                e.preventDefault();
                alert('⚠️ Silakan pilih salah satu tim favorit (Manchester City atau Manchester United)!');
                // Scroll to favorite team section
                document.querySelector('input[name="favorite_team"]').closest('div').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak cocok!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                return false;
            }
        });
        
        // Add visual feedback when team is selected
        document.querySelectorAll('input[name="favorite_team"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Remove warning text color when team is selected
                const warningText = document.querySelector('.text-red-600.mt-2');
                if (warningText) {
                    warningText.classList.remove('text-red-600');
                    warningText.classList.add('text-green-600');
                    warningText.innerHTML = '✅ Tim favorit sudah dipilih';
                }
            });
        });
    </script>

</body>
</html>