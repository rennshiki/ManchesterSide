<?php
/**
 * Manchester Side - User Login Page
 */
require_once 'includes/config.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = lightSanitize($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username_or_email) || empty($password)) {
        $error = 'Username/email dan password wajib diisi';
    } else {
        $db = getDB();
        
        // Check user by username or email
        $stmt = $db->prepare("SELECT id, username, email, password, full_name, favorite_team, is_active FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username_or_email, $username_or_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if account is active
            if (!$user['is_active']) {
                $error = 'Akun Anda telah dinonaktifkan. Hubungi administrator.';
            } 
            // Verify password
            elseif (verifyPassword($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['favorite_team'] = $user['favorite_team'];
                
                setFlashMessage('success', 'Selamat datang kembali, ' . $user['full_name'] . '!');
                
                // Redirect to previous page or homepage
                $redirect_to = $_GET['redirect'] ?? 'index.php';
                redirect($redirect_to);
            } else {
                $error = 'Username/email atau password salah';
            }
        } else {
            $error = 'Username/email atau password salah';
        }
    }
}

// Get flash message
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
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
            
            <!-- Login Card -->
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                <div class="text-center mb-8">
                    <div class="flex justify-center mb-4">
                        <div class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center text-white text-3xl">
                            👤
                        </div>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Selamat Datang!</h2>
                    <p class="text-gray-600">Login ke akun Manchester Side Anda</p>
                </div>

                <?php if ($flash && $flash['type'] === 'success'): ?>
                    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                        <p class="flex items-center">
                            <span class="text-xl mr-2">✅</span>
                            <?php echo $flash['message']; ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                        <p class="flex items-center">
                            <span class="text-xl mr-2">❌</span>
                            <?php echo $error; ?>
                        </p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-6">
                    
                    <!-- Username or Email -->
                    <div>
                        <label for="username_or_email" class="block text-sm font-semibold text-gray-700 mb-2">
                            Username atau Email
                        </label>
                        <input 
                            type="text" 
                            id="username_or_email" 
                            name="username_or_email" 
                            value="<?php echo $_POST['username_or_email'] ?? ''; ?>"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent transition"
                            placeholder="Username atau email Anda"
                            autofocus
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
                            placeholder="Masukkan password"
                        >
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="remember" 
                                class="w-4 h-4 text-city-blue border-gray-300 rounded focus:ring-city-blue"
                            >
                            <span class="ml-2 text-sm text-gray-600">Ingat saya</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit"
                        class="w-full gradient-bg text-white font-bold py-3 px-4 rounded-lg hover:shadow-lg transform hover:scale-[1.02] transition duration-200"
                    >
                        Login
                    </button>

                </form>

                <!-- Register Link -->
                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        Belum punya akun? 
                        <a href="register.php" class="text-city-blue font-semibold hover:underline">
                            Daftar sekarang
                        </a>
                    </p>
                </div>

            </div>

        </div>
    </div>

</body>
</html>