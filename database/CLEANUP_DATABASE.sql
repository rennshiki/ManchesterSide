-- ========================================
-- CLEANUP DATABASE - Manchester Side
-- Hapus klub selain Manchester City & United
-- ========================================

-- ⚠️ PERINGATAN:
-- Script ini akan menghapus SEMUA klub selain Manchester City dan Manchester United
-- beserta data terkait (pemain, staff, artikel, dll)
-- 
-- BACKUP DATABASE TERLEBIH DAHULU!
-- 
-- Cara backup:
-- mysqldump -u root -p manchesterside > backup_$(date +%Y%m%d_%H%M%S).sql

-- ========================================
-- 1. CEK KLUB YANG AKAN DIHAPUS
-- ========================================
-- Jalankan query ini terlebih dahulu untuk melihat klub yang akan dihapus

SELECT 
    id,
    name,
    code,
    founded_year,
    (SELECT COUNT(*) FROM players WHERE club_id = clubs.id) as total_players,
    (SELECT COUNT(*) FROM staff WHERE club_id = clubs.id) as total_staff,
    (SELECT COUNT(*) FROM articles WHERE club_id = clubs.id) as total_articles,
    (SELECT COUNT(*) FROM matches WHERE home_team_id = clubs.id OR away_team_id = clubs.id) as total_matches
FROM clubs
WHERE code NOT IN ('CITY', 'UNITED')
ORDER BY name;

-- ========================================
-- 2. HAPUS DATA TERKAIT (OPSIONAL)
-- ========================================
-- Jika ingin hapus semua data terkait klub lain, uncomment query di bawah

-- Hapus pemain dari klub lain
-- DELETE FROM players 
-- WHERE club_id IN (
--     SELECT id FROM clubs WHERE code NOT IN ('CITY', 'UNITED')
-- );

-- Hapus staff dari klub lain
-- DELETE FROM staff 
-- WHERE club_id IN (
--     SELECT id FROM clubs WHERE code NOT IN ('CITY', 'UNITED')
-- );

-- Hapus artikel terkait klub lain
-- DELETE FROM articles 
-- WHERE club_id IN (
--     SELECT id FROM clubs WHERE code NOT IN ('CITY', 'UNITED')
-- );

-- Hapus trofi klub lain
-- DELETE FROM club_trophies 
-- WHERE club_id IN (
--     SELECT id FROM clubs WHERE code NOT IN ('CITY', 'UNITED')
-- );

-- ========================================
-- 3. HAPUS PERTANDINGAN (HATI-HATI!)
-- ========================================
-- ⚠️ PERINGATAN: Ini akan menghapus SEMUA pertandingan yang melibatkan klub lain
-- Termasuk pertandingan City vs Arsenal, United vs Liverpool, dll
-- 
-- Jika Anda ingin TETAP MENYIMPAN jadwal pertandingan dengan klub lain,
-- JANGAN jalankan query ini!

-- Hapus gol dari pertandingan yang melibatkan klub lain
-- DELETE FROM match_goals 
-- WHERE match_id IN (
--     SELECT id FROM matches 
--     WHERE home_team_id IN (SELECT id FROM clubs WHERE code NOT IN ('CITY', 'UNITED'))
--        OR away_team_id IN (SELECT id FROM clubs WHERE code NOT IN ('CITY', 'UNITED'))
-- );

-- Hapus pertandingan yang melibatkan klub lain
-- DELETE FROM matches 
-- WHERE home_team_id IN (SELECT id FROM clubs WHERE code NOT IN ('CITY', 'UNITED'))
--    OR away_team_id IN (SELECT id FROM clubs WHERE code NOT IN ('CITY', 'UNITED'));

-- ========================================
-- 4. HAPUS KLUB LAIN
-- ========================================
-- Setelah semua data terkait dihapus, baru hapus klub

-- DELETE FROM clubs WHERE code NOT IN ('CITY', 'UNITED');

-- ========================================
-- 5. VERIFIKASI HASIL
-- ========================================
-- Jalankan query ini untuk memastikan hanya City & United yang tersisa

SELECT 
    id,
    name,
    code,
    founded_year,
    (SELECT COUNT(*) FROM players WHERE club_id = clubs.id) as total_players,
    (SELECT COUNT(*) FROM staff WHERE club_id = clubs.id) as total_staff,
    (SELECT COUNT(*) FROM articles WHERE club_id = clubs.id) as total_articles,
    (SELECT COUNT(*) FROM matches WHERE home_team_id = clubs.id OR away_team_id = clubs.id) as total_matches
FROM clubs
ORDER BY name;

-- Hasil yang diharapkan:
-- +----+-------------------+--------+--------------+---------------+-------------+----------------+---------------+
-- | id | name              | code   | founded_year | total_players | total_staff | total_articles | total_matches |
-- +----+-------------------+--------+--------------+---------------+-------------+----------------+---------------+
-- |  1 | Manchester City   | CITY   | 1880         | 5             | 2           | 2              | 5             |
-- |  2 | Manchester United | UNITED | 1878         | 5             | 2           | 3              | 5             |
-- +----+-------------------+--------+--------------+---------------+-------------+----------------+---------------+

-- ========================================
-- 6. RESET AUTO INCREMENT (OPSIONAL)
-- ========================================
-- Jika ingin reset ID klub agar mulai dari 3 untuk klub baru

-- ALTER TABLE clubs AUTO_INCREMENT = 3;

-- ========================================
-- ALTERNATIF: SOFT DELETE
-- ========================================
-- Jika tidak ingin menghapus permanen, bisa tambah kolom is_managed

-- Tambah kolom is_managed
-- ALTER TABLE clubs ADD COLUMN is_managed TINYINT(1) DEFAULT 0;

-- Set City & United sebagai managed
-- UPDATE clubs SET is_managed = 1 WHERE code IN ('CITY', 'UNITED');

-- Kemudian di query admin, gunakan:
-- SELECT * FROM clubs WHERE is_managed = 1;

-- ========================================
-- CATATAN PENTING
-- ========================================

-- 1. BACKUP DATABASE TERLEBIH DAHULU!
--    mysqldump -u root -p manchesterside > backup.sql

-- 2. Jika ingin TETAP MENYIMPAN jadwal pertandingan dengan klub lain,
--    JANGAN hapus klub dari database. Biarkan sistem seperti sekarang.

-- 3. Admin sudah difilter untuk hanya menampilkan City & United,
--    jadi tidak perlu hapus klub lain dari database.

-- 4. Klub lain di database tidak akan muncul di admin, tapi tetap
--    bisa digunakan untuk jadwal pertandingan.

-- 5. Jika Anda yakin ingin menghapus, uncomment query di atas
--    dan jalankan satu per satu (jangan sekaligus).

-- ========================================
-- RESTORE DATABASE (Jika Ada Masalah)
-- ========================================

-- Jika terjadi kesalahan dan ingin restore:
-- mysql -u root -p manchesterside < backup.sql

-- ========================================
-- SELESAI
-- ========================================
