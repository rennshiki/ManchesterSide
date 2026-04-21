<?php
/**
 * Manchester Side - Tentang Kami
 */
require_once 'includes/config.php';

$current_user = getCurrentUser();

// Admin profiles
$admins = [
    [
        'name' => 'Muhammad Rafi Hasan',
        'role' => 'Founder & Lead Developer',
        'photo' => 'images/rafi.jpeg', // Foto dengan topi
        'bio' => 'Penggemar berat Manchester City sejak 2022. Bertanggung jawab atas pengembangan dan pemeliharaan website.',
        'favorite_team' => 'CITY',
        'expertise' => ['Web Development', 'Database Management', 'Content Strategy'],
        'social' => [
            'email' => 'raficyborg855@gmail.com',
            'instagram' => 'raff_cityzens',
            'tiktok' => 'raffcityzenss'
        ]
    ],
    [
        'name' => 'Rheno Wahyu Febriansyah',
        'role' => 'Content Manager & Designer',
        'photo' => 'images/rheno.jpeg', // Foto tanpa topi
        'bio' => 'Supporter fanatik Manchester United. Mengelola konten berita dan desain visual website.',
        'favorite_team' => 'UNITED',
        'expertise' => ['Content Writing', 'UI/UX Design'],
        'social' => [
            'email' => 'rhenowahyuf@gmail.com',
            'instagram' => 'rensyuee',
            'tiktok' => 'rensyue'
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Manchester Side</title>
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
        
        /* Mobile Responsive Improvements */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem !important;
            }
            .hero-subtitle {
                font-size: 1.125rem !important;
            }
            .section-title {
                font-size: 1.875rem !important;
            }
            .card-padding {
                padding: 1.5rem !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50">

    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-city-blue via-purple-600 to-united-red text-white py-12 md:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="hero-title text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black mb-4 md:mb-6">
                Tentang Manchester Side
            </h1>
            <p class="hero-subtitle text-base sm:text-lg md:text-xl lg:text-2xl mb-6 md:mb-8 max-w-3xl mx-auto leading-relaxed">
                Platform berita terpercaya untuk fans Manchester City dan Manchester United di Indonesia
            </p>
        </div>
    </section>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <!-- Mission & Vision -->
        <div class="mb-8 md:mb-16">
            <div class="bg-white rounded-2xl shadow-xl card-padding p-6 md:p-8 lg:p-12">
                <div class="text-center mb-8 md:mb-12">
                    <h2 class="section-title text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        🎯 Visi & Misi Kami
                    </h2>
                </div>

                <div class="grid md:grid-cols-2 gap-6 md:gap-8">
                    <!-- Vision -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 md:p-8">
                        <div class="text-3xl md:text-4xl mb-3 md:mb-4">🌟</div>
                        <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-3 md:mb-4">Visi</h3>
                        <p class="text-gray-700 leading-relaxed">
                            Menjadi platform berita sepak bola terdepan di Indonesia yang menyajikan informasi 
                            akurat, objektif, dan terkini tentang Manchester City dan Manchester United, 
                            serta menjembatani kedua kubu fans dengan konten berkualitas.
                        </p>
                    </div>

                    <!-- Mission -->
                    <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-6 md:p-8">
                        <div class="text-3xl md:text-4xl mb-3 md:mb-4">🎯</div>
                        <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-3 md:mb-4">Misi</h3>
                        <ul class="space-y-3 text-gray-700">
                            <li class="flex items-start">
                                <span class="text-green-500 mr-2">✓</span>
                                <span>Menyediakan berita sepak bola yang faktual dan terpercaya</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-500 mr-2">✓</span>
                                <span>Menghormati kedua kubu fans tanpa bias</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-500 mr-2">✓</span>
                                <span>Membangun komunitas pecinta sepak bola yang positif</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-500 mr-2">✓</span>
                                <span>Update cepat untuk setiap berita dan pertandingan</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Why This Website -->
                <div class="mt-8 md:mt-12 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-6 md:p-8">
                    <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 md:mb-6 text-center">
                        💡 Mengapa Manchester Side?
                    </h3>
                    <div class="prose prose-lg max-w-none text-gray-700">
                        <p class="mb-4">
                            <strong>Manchester Side</strong> lahir dari kecintaan mendalam terhadap sepak bola, 
                            khususnya rivalitas klasik antara Manchester City dan Manchester United. Kami menyadari 
                            bahwa fans Indonesia membutuhkan platform yang menyajikan berita kedua klub secara 
                            <strong>seimbang, objektif, dan berkualitas</strong>.
                        </p>
                        <p class="mb-4">
                            Di tengah banyaknya informasi yang tersebar di media sosial, kami hadir untuk memberikan 
                            <strong>sumber berita terpercaya</strong> dengan liputan mendalam tentang:
                        </p>
                        <ul class="list-disc list-inside space-y-2 mb-4">
                            <li>Hasil pertandingan dan analisis taktik</li>
                            <li>Transfer pemain dan rumor pasar</li>
                            <li>Profil pemain dan staff pelatih</li>
                            <li>Sejarah dan rivalitas Manchester Derby</li>
                            <li>Opini dan diskusi konstruktif</li>
                        </ul>
                        <p>
                            Kami percaya bahwa <strong>rivalitas adalah keindahan sepak bola</strong>, dan melalui 
                            Manchester Side, kami ingin memfasilitasi diskusi yang sehat dan sportif antara fans 
                            kedua kubu. <em>Two Sides, One City, Endless Rivalry</em> - itulah semangat kami.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Team -->
        <div class="mb-8 md:mb-16">
            <div class="text-center mb-8 md:mb-12">
                <h2 class="section-title text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-3 md:mb-4">
                    👥 Tim Kami
                </h2>
                <p class="text-base md:text-xl text-gray-600">
                    Dikelola oleh fans sejati dari kedua kubu
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-6 md:gap-8">
                <?php foreach ($admins as $admin): ?>
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition">
                        <!-- Header -->
                        <div class="h-24 md:h-32 bg-gradient-to-br from-<?php echo $admin['favorite_team'] === 'CITY' ? 'city-blue' : 'united-red'; ?> to-<?php echo $admin['favorite_team'] === 'CITY' ? 'city-navy' : 'red'; ?>-900"></div>
                        
                        <!-- Content -->
                        <div class="card-padding p-6 md:p-8">
                            <!-- Avatar with Manual Photo Support -->
                            <div class="flex justify-center -mt-16 md:-mt-20 mb-4 md:mb-6">
                                <?php 
                                // Check if admin has custom photo in uploads folder
                                $custom_photo_path = "includes/uploads/profiles/admin_" . strtolower(str_replace(' ', '_', $admin['name'])) . ".jpg";
                                $photo_src = file_exists($custom_photo_path) ? $custom_photo_path : $admin['photo'];
                                ?>
                                <div class="relative">
                                    <img 
                                        src="<?php echo $photo_src; ?>" 
                                        alt="<?php echo $admin['name']; ?>"
                                        class="w-24 h-24 md:w-32 md:h-32 rounded-lg border-4 border-white shadow-xl object-cover"
                                    >
                                    <!-- Photo Frame Border -->
                                    <div class="absolute inset-0 rounded-lg border-2 border-<?php echo $admin['favorite_team'] === 'CITY' ? 'city-blue' : 'united-red'; ?> pointer-events-none"></div>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="text-center mb-4 md:mb-6">
                                <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-2">
                                    <?php echo $admin['name']; ?>
                                </h3>
                                <p class="text-sm md:text-base text-<?php echo $admin['favorite_team'] === 'CITY' ? 'city-blue' : 'united-red'; ?> font-semibold">
                                    <?php echo $admin['role']; ?>
                                </p>
                            </div>

                            <!-- Bio -->
                            <p class="text-sm md:text-base text-gray-600 text-center mb-4 md:mb-6 leading-relaxed">
                                <?php echo $admin['bio']; ?>
                            </p>

                            <!-- Expertise -->
                            <div class="mb-4 md:mb-6">
                                <p class="text-xs md:text-sm font-semibold text-gray-700 mb-2 md:mb-3 text-center">Keahlian:</p>
                                <div class="flex flex-wrap justify-center gap-2">
                                    <?php foreach ($admin['expertise'] as $skill): ?>
                                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-semibold">
                                            <?php echo $skill; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Social Media -->
                            <div class="flex justify-center gap-2 md:gap-3 pt-4 md:pt-6 border-t border-gray-200">
                                <!-- Gmail -->
                                <a href="mailto:<?php echo $admin['social']['email']; ?>" class="w-9 h-9 md:w-10 md:h-10 bg-gray-100 hover:bg-red-500 hover:text-white rounded-full flex items-center justify-center transition group">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L5.455 4.64 12 9.548l6.545-4.91 1.528-1.145C21.69 2.28 24 3.434 24 5.457z"/>
                                    </svg>
                                </a>
                                
                                <!-- Instagram -->
                                <?php if (isset($admin['social']['instagram'])): ?>
                                <a href="https://www.instagram.com/<?php echo $admin['social']['instagram']; ?>" target="_blank" class="w-9 h-9 md:w-10 md:h-10 bg-gray-100 hover:bg-gradient-to-r hover:from-purple-600 hover:to-pink-600 hover:text-white rounded-full flex items-center justify-center transition group">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </a>
                                <?php endif; ?>
                                
                                <!-- TikTok -->
                                <?php if (isset($admin['social']['tiktok'])): ?>
                                <a href="https://www.tiktok.com/@<?php echo $admin['social']['tiktok']; ?>" target="_blank" class="w-9 h-9 md:w-10 md:h-10 bg-gray-100 hover:bg-black hover:text-white rounded-full flex items-center justify-center transition group">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                                    </svg>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Features -->
        <div class="mb-8 md:mb-16">
            <div class="text-center mb-8 md:mb-12">
                <h2 class="section-title text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    ✨ Fitur Unggulan
                </h2>
            </div>

            <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-6">
                <div class="bg-white rounded-xl shadow-lg p-5 md:p-6 text-center hover:shadow-xl transition">
                    <div class="text-4xl md:text-5xl mb-3 md:mb-4">📰</div>
                    <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-2 md:mb-3">Berita Terkini</h3>
                    <p class="text-sm md:text-base text-gray-600">Update berita real-time dari kedua klub dengan sumber terpercaya</p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-5 md:p-6 text-center hover:shadow-xl transition">
                    <div class="text-4xl md:text-5xl mb-3 md:mb-4">📊</div>
                    <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-2 md:mb-3">Statistik Lengkap</h3>
                    <p class="text-sm md:text-base text-gray-600">Data klasemen, jadwal, dan head-to-head Manchester Derby</p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-5 md:p-6 text-center hover:shadow-xl transition">
                    <div class="text-4xl md:text-5xl mb-3 md:mb-4">👥</div>
                    <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-2 md:mb-3">Profil Pemain</h3>
                    <p class="text-sm md:text-base text-gray-600">Informasi lengkap tentang skuad dan staff kedua tim</p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-5 md:p-6 text-center hover:shadow-xl transition">
                    <div class="text-4xl md:text-5xl mb-3 md:mb-4">❤️</div>
                    <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-2 md:mb-3">Fitur Favorit</h3>
                    <p class="text-sm md:text-base text-gray-600">Simpan dan kelola berita favorit Anda dengan mudah</p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-5 md:p-6 text-center hover:shadow-xl transition">
                    <div class="text-4xl md:text-5xl mb-3 md:mb-4">👍</div>
                    <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-2 md:mb-3">Sistem Reaksi</h3>
                    <p class="text-sm md:text-base text-gray-600">Berikan reaksi pada berita dan lihat opini komunitas</p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-5 md:p-6 text-center hover:shadow-xl transition">
                    <div class="text-4xl md:text-5xl mb-3 md:mb-4">📱</div>
                    <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-2 md:mb-3">User Friendly</h3>
                    <p class="text-sm md:text-base text-gray-600">Memiliki tampilan sederhana dan fitur yang responsif</p>
                </div>
            </div>
        </div>

        <!-- Contact CTA -->
        <div class="bg-gradient-to-r from-city-blue to-united-red rounded-2xl shadow-2xl p-6 md:p-12 text-white text-center">
            <h2 class="text-xl md:text-3xl font-bold mb-3 md:mb-4">
                Punya Saran atau Masukan?
            </h2>
            <p class="text-base md:text-xl mb-6 md:mb-8">
                Kami selalu terbuka untuk feedback dari komunitas
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-3 md:gap-4">
                <a href="mailto:<?php echo getSiteSetting('site_email', 'info@manchesterside.com'); ?>" class="px-6 md:px-8 py-3 md:py-4 bg-white text-gray-900 font-bold rounded-lg hover:bg-gray-100 transition text-sm md:text-base">
                    📧 Hubungi Kami
                </a>
                <a href="index.php" class="px-6 md:px-8 py-3 md:py-4 bg-white/20 hover:bg-white/30 font-bold rounded-lg transition backdrop-blur-sm text-sm md:text-base">
                    🏠 Kembali ke Beranda
                </a>
            </div>
        </div>

    </main>

    <?php include 'includes/footer.php'; ?>

</body>
</html>