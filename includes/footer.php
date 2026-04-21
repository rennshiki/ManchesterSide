<?php
/**
 * Manchester Side - Footer Component
 * Universal footer untuk semua halaman dengan social media official teams
 */

// Social Media Official Teams
$club_socials = [
    'CITY' => [
        'instagram' => 'https://www.instagram.com/mancity/',
        'facebook' => 'https://www.facebook.com/mancity',
        'twitter' => 'https://twitter.com/ManCity',
        'youtube' => 'https://www.youtube.com/mcfcofficial'
    ],
    'UNITED' => [
        'instagram' => 'https://www.instagram.com/manchesterunited/',
        'facebook' => 'https://www.facebook.com/manchesterunited',
        'twitter' => 'https://twitter.com/ManUtd',
        'youtube' => 'https://www.youtube.com/user/manchesterunited'
    ]
];

// Club logos
$club_logos = [
    'CITY' => 'https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg',
    'UNITED' => 'https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_crest.svg'
];
?>

<!-- Footer -->
<footer class="bg-gray-900 text-white py-12 mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-4 gap-8">
            
            <!-- Brand Section -->
            <div>
                <div class="flex items-center space-x-2 mb-4">
                    <div class="flex">
                        <div class="w-6 h-6 bg-city-blue rounded-full"></div>
                        <div class="w-6 h-6 bg-united-red rounded-full -ml-2"></div>
                    </div>
                    <span class="text-xl font-bold">Manchester Side</span>
                </div>
                <p class="text-gray-400 text-sm mb-4">
                    Two Sides, One City, Endless Rivalry
                </p>
                <p class="text-gray-500 text-xs">
                    Portal berita terpercaya untuk fans Manchester City dan Manchester United di Indonesia.
                </p>
            </div>
            
            <!-- Navigasi -->
            <div>
                <h4 class="font-bold mb-4 text-lg">Navigasi</h4>
                <ul class="space-y-2 text-gray-400">
                    <li><a href="index.php" class="hover:text-city-blue transition">Beranda</a></li>
                    <li><a href="news.php" class="hover:text-city-blue transition">Berita</a></li>
                    <li><a href="fixtures.php" class="hover:text-city-blue transition">Jadwal & Hasil</a></li>
                    <li><a href="tentang-kami.php" class="hover:text-city-blue transition">Tentang Kami</a></li>
                </ul>
            </div>
            
            <!-- Klub -->
            <div>
                <h4 class="font-bold mb-4 text-lg">Klub</h4>
                <ul class="space-y-3 text-gray-400">
                    <li>
                        <a href="profil-klub.php?team=city" class="flex items-center gap-2 hover:text-city-blue transition group">
                            <img src="<?php echo $club_logos['CITY']; ?>" alt="Man City" class="w-5 h-5 group-hover:scale-110 transition">
                            <span>Manchester City</span>
                        </a>
                    </li>
                    <li>
                        <a href="profil-klub.php?team=united" class="flex items-center gap-2 hover:text-united-red transition group">
                            <img src="<?php echo $club_logos['UNITED']; ?>" alt="Man United" class="w-5 h-5 group-hover:scale-110 transition">
                            <span>Manchester United</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Social Media Official Teams -->
            <div>
                <h4 class="font-bold mb-4 text-lg">Media Sosial Official</h4>
                
                <!-- Manchester City -->
                <div class="mb-6">
                    <div class="flex items-center gap-2 mb-3">
                        <img src="<?php echo $club_logos['CITY']; ?>" alt="Man City" class="w-5 h-5">
                        <p class="text-sm font-semibold text-city-blue">Manchester City</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="<?php echo $club_socials['CITY']['instagram']; ?>" target="_blank" rel="noopener noreferrer" class="w-9 h-9 bg-gray-800 hover:bg-gradient-to-r hover:from-purple-600 hover:to-pink-600 rounded-lg flex items-center justify-center transition group">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>
                        <a href="<?php echo $club_socials['CITY']['facebook']; ?>" target="_blank" rel="noopener noreferrer" class="w-9 h-9 bg-gray-800 hover:bg-blue-600 rounded-lg flex items-center justify-center transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/>
                            </svg>
                        </a>
                        <a href="<?php echo $club_socials['CITY']['twitter']; ?>" target="_blank" rel="noopener noreferrer" class="w-9 h-9 bg-gray-800 hover:bg-sky-500 rounded-lg flex items-center justify-center transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                        </a>
                        <a href="<?php echo $club_socials['CITY']['youtube']; ?>" target="_blank" rel="noopener noreferrer" class="w-9 h-9 bg-gray-800 hover:bg-red-600 rounded-lg flex items-center justify-center transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Manchester United -->
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <img src="<?php echo $club_logos['UNITED']; ?>" alt="Man United" class="w-5 h-5">
                        <p class="text-sm font-semibold text-united-red">Manchester United</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="<?php echo $club_socials['UNITED']['instagram']; ?>" target="_blank" rel="noopener noreferrer" class="w-9 h-9 bg-gray-800 hover:bg-gradient-to-r hover:from-purple-600 hover:to-pink-600 rounded-lg flex items-center justify-center transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>
                        <a href="<?php echo $club_socials['UNITED']['facebook']; ?>" target="_blank" rel="noopener noreferrer" class="w-9 h-9 bg-gray-800 hover:bg-blue-600 rounded-lg flex items-center justify-center transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/>
                            </svg>
                        </a>
                        <a href="<?php echo $club_socials['UNITED']['twitter']; ?>" target="_blank" rel="noopener noreferrer" class="w-9 h-9 bg-gray-800 hover:bg-sky-500 rounded-lg flex items-center justify-center transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                        </a>
                        <a href="<?php echo $club_socials['UNITED']['youtube']; ?>" target="_blank" rel="noopener noreferrer" class="w-9 h-9 bg-gray-800 hover:bg-red-600 rounded-lg flex items-center justify-center transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="border-t border-gray-800 mt-8 pt-8 text-center">
            <p class="text-gray-400 text-sm">
                &copy; <?php echo date('Y'); ?> Manchester Side. All rights reserved.
            </p>
            <p class="text-gray-500 text-xs mt-2">
                Two Sides, One City, Endless Rivalry ⚽
            </p>
        </div>
    </div>
</footer>

</body>
</html>