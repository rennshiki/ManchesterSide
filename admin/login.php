<?php
/**
 * Manchester Side - Admin Login
 */
require_once '../includes/config.php';

// Redirect jika sudah login sebagai admin
if (isAdminLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = lightSanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi';
    } else {
        $db = getDB();
        
        $stmt = $db->prepare("SELECT id, username, email, password, full_name, role, is_active FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            if (!$admin['is_active']) {
                $error = 'Akun admin Anda telah dinonaktifkan';
            } elseif (verifyPassword($password, $admin['password'])) {
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Set admin session
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_last_activity'] = time();
                
                // Update last login
                $update_login = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $update_login->bind_param("i", $admin['id']);
                $update_login->execute();
                
                setFlashMessage('success', 'Selamat datang, ' . $admin['full_name'] . '!');
                redirect('dashboard.php');
            } else {
                $error = 'Username atau password salah';
            }
        } else {
            $error = 'Username atau password salah';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
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
            background: linear-gradient(135deg, #1C2C5B 0%, #6CABDD 50%, #DA291C 100%);
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            
            <!-- Back to Site -->
            <div class="text-center mb-6">
                <a href="../index.php" class="text-gray-600 hover:text-city-blue text-sm font-semibold">
                    ← Kembali ke Website
                </a>
            </div>

            <!-- Admin Login Card -->
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                <div class="text-center mb-8">
                    <div class="flex justify-center mb-4">
                        <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center text-white text-4xl shadow-lg">
                            🔐
                        </div>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Admin Panel</h2>
                    <p class="text-gray-600">Manchester Side Control Center</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                        <p class="flex items-center">
                            <span class="text-xl mr-2">❌</span>
                            <?php echo $error; ?>
                        </p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-6">
                    
                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-bold text-gray-700 mb-2">
                            Username Admin
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 text-xl">
                                👤
                            </span>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                value="<?php echo $_POST['username'] ?? ''; ?>"
                                required
                                class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent transition"
                                placeholder="Username admin"
                                autofocus
                            >
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-bold text-gray-700 mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 text-xl">
                                🔒
                            </span>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required
                                class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-city-blue focus:border-transparent transition"
                                placeholder="Password admin"
                            >
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            name="remember" 
                            id="remember"
                            class="w-4 h-4 text-city-blue border-gray-300 rounded focus:ring-city-blue"
                        >
                        <label for="remember" class="ml-2 text-sm text-gray-600">
                            Ingat saya
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit"
                        class="w-full gradient-bg text-white font-bold py-3 px-4 rounded-lg hover:shadow-lg transform hover:scale-[1.02] transition duration-200"
                    >
                        Login ke Admin Panel
                    </button>

                </form>

            </div>

            <!-- Demo Account Info -->
            <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
                <p class="font-semibold mb-2">🔐 Demo Admin Account:</p>
                <div class="space-y-1 text-xs">
                    <p><strong>Username:</strong> superadmin</p>
                    <p><strong>Password:</strong> password</p>
                    <p><strong>Role:</strong> Super Admin</p>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="mt-4 text-center text-xs text-gray-500">
                <p>🔒 Halaman ini dilindungi dengan enkripsi dan monitoring keamanan</p>
            </div>

        </div>
    </div>

</body>
</html>