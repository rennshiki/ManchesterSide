# 🔐 MANUAL ADMIN PANEL
## Manchester Side - Panduan Administrator

---

## 🎯 TENTANG ADMIN PANEL
Dashboard untuk mengelola seluruh konten website Manchester Side.
**Akses:** `website.com/admin/login.php`

---

## 🔑 BAGIAN 1: LOGIN ADMIN

### 1.1 Akses Admin Panel
1. Buka `website.com/admin/login.php`
2. Username: `superadmin`
3. Password: `password`
4. Klik "Login"

⚠️ **PENTING:** Ubah password default setelah login pertama!

### 1.2 Dashboard Overview
Setelah login, lihat:
- **Statistik:** Total artikel, user, pemain, staff
- **Grafik:** Views, reactions per klub
- **Aktivitas:** Artikel, user, reactions terbaru
- **Quick Actions:** Tombol cepat ke fitur utama

---

## 📰 BAGIAN 2: MENGELOLA ARTIKEL

### 2.1 Lihat Daftar Artikel
1. Klik menu "Berita" di sidebar
2. Lihat semua artikel
3. Filter: Status (Published/Draft), Klub, Kategori

### 2.2 Buat Artikel Baru
1. "Berita" → "Tambah Artikel"
2. Isi form:
   - **Judul:** Wajib, menarik
   - **Slug:** Auto dari judul
   - **Kategori:** News, Match, Transfer, Interview, Analysis
   - **Klub:** City, United, atau Umum
   - **Excerpt:** Ringkasan max 500 karakter
   - **Konten:** Artikel lengkap min 200 kata
   - **Gambar:** JPG/PNG max 2MB
3. Status: Draft (simpan) atau Published (langsung tayang)
4. Centang "Featured" untuk homepage
5. Klik "Simpan Artikel"

### 2.3 Edit Artikel
1. "Berita" → Cari artikel → "Edit"
2. Ubah informasi
3. "Update Artikel"

### 2.4 Hapus Artikel
1. "Berita" → Cari artikel → "Hapus"
2. Konfirmasi penghapusan

⚠️ **Artikel terhapus tidak bisa dikembalikan!**

---

## 👥 BAGIAN 3: MENGELOLA PEMAIN

### 3.1 Lihat Pemain
1. Menu "Pemain"
2. Filter: Klub (City/United), Posisi

### 3.2 Tambah Pemain
1. "Pemain" → "Tambah Pemain"
2. Isi form:
   - **Nama:** Wajib
   - **Klub:** City atau United (wajib)
   - **Posisi:** Goalkeeper/Defender/Midfielder/Forward
   - **Nomor Punggung:** 1-99
   - **Kebangsaan, Tanggal Lahir, Tinggi, Berat**
   - **Biografi:** Deskripsi pemain
   - **Foto:** JPG/PNG max 2MB
   - **Tanggal Bergabung, Klub Sebelumnya**
3. "Simpan Pemain"

### 3.3 Edit/Hapus Pemain
- Edit: Cari pemain → "Edit" → Ubah → "Update"
- Hapus: Cari pemain → "Hapus" → Konfirmasi

---

## 👔 BAGIAN 4: MENGELOLA STAFF

### 4.1 Tambah Staff
1. "Staff Kepelatihan" → "Tambah Staff"
2. Isi form:
   - **Nama, Klub:** Wajib
   - **Role:** Manajer atau Asisten Manajer
   - **Kebangsaan, Tanggal Lahir**
   - **Tanggal Bergabung, Klub Sebelumnya**
   - **Pencapaian, Biografi**
   - **Foto:** JPG/PNG max 2MB
3. "Simpan Staff"

### 4.2 Edit/Hapus Staff
Sama seperti pemain (lihat 3.3)

---

## 🏆 BAGIAN 5: MENGELOLA PROFIL KLUB

### 5.1 Edit Identitas Klub
1. "Profil Klub" → Pilih klub → "Edit Profil Klub"
2. Tab "Identitas Klub":
   - Nama Resmi, Julukan
   - Tahun Berdiri
   - Stadion (nama, lokasi, kapasitas)
   - Warna Utama & Sekunder
3. "Simpan Semua Perubahan"

### 5.2 Edit Sejarah Klub
1. Tab "Sejarah"
2. Tulis/edit sejarah klub
3. "Simpan Semua Perubahan"

### 5.3 Kelola Piala & Trofi
1. Tab "Prestasi & Piala"
2. "Tambah Piala Baru"
3. Isi:
   - **Nama Piala:** Premier League, FA Cup, dll
   - **Upload Foto/URL Gambar:** Opsional
   - **Tahun Menang:** 2020, 2021, 2023 (pisah koma)
4. "Tambah Piala"

**Hapus Piala:** Klik 🗑️ di card piala

### 5.4 Edit Manajemen Klub
1. Tab "Manajemen"
2. Edit: Pemilik, Chairman, Dewan Direksi
3. "Simpan Semua Perubahan"

---

## 📅 BAGIAN 6: MENGELOLA JADWAL

### 6.1 Lihat Jadwal
1. "Jadwal & Hasil"
2. Filter: Semua, Akan Datang, Selesai

### 6.2 Tambah Jadwal
1. "Jadwal & Hasil" → "Tambah Jadwal"
2. Isi form:
   - **Tim Home/Away:** Ketik nama (City, Arsenal, Liverpool, dll)
   - **Logo:** URL logo (opsional)
   - **Kompetisi:** Premier League, FA Cup, dll
   - **Tanggal & Waktu, Venue**
   - **Status:** Terjadwal/Selesai/Ditunda
3. Jika "Selesai":
   - **Skor Home/Away**
   - **Detail Gol:** Klik "Tambah Gol"
     - Menit (1-120)
     - Tim (Home/Away)
     - Jenis: Biasa/Penalti/Bunuh Diri/Tendangan Bebas
     - Pencetak Gol
4. "Simpan Jadwal"

**Catatan:** Klub & pemain baru otomatis dibuat jika belum ada

### 6.3 Edit/Hapus Jadwal
- Edit: Cari pertandingan → "Edit" → Ubah → "Update"
- Hapus: Cari pertandingan → "Hapus" → Konfirmasi

---

## 👤 BAGIAN 7: MENGELOLA USERS

### 7.1 Lihat Users
1. Menu "Users"
2. Filter: Status (Active/Inactive), Tim Favorit

### 7.2 Edit User
1. Cari user → "Edit"
2. Ubah: Nama, Email, Status, Tim Favorit
3. "Update User"

### 7.3 Nonaktifkan/Hapus User
- Nonaktif: Edit → Status "Inactive"
- Hapus: "Hapus" → Konfirmasi

⚠️ **User terhapus tidak bisa dikembalikan!**

---

## ⚙️ BAGIAN 8: PENGATURAN

### 8.1 Setting Website
1. Menu "Settings"
2. Edit:
   - **Site Name:** Nama website
   - **Site Tagline:** Tagline
   - **Site Email:** Email kontak
   - **Articles Per Page:** Jumlah per halaman
   - **Allow Comments:** Ya/Tidak
   - **Maintenance Mode:** Aktif/Nonaktif
3. "Simpan Pengaturan"

### 8.2 Mode Maintenance
1. "Settings" → "Maintenance Mode" → "Aktif"
2. Website tampil halaman maintenance
3. Admin tetap bisa akses

---

## 📊 BAGIAN 9: STATISTIK

### 9.1 Dashboard Stats
1. Menu "Dashboard"
2. Lihat:
   - Total artikel, users, pemain, staff
   - Views & reactions
   - Artikel terpopuler
   - Aktivitas terbaru

---

## 🔒 BAGIAN 10: KEAMANAN

### 10.1 Ubah Password
1. Klik nama Anda (pojok kanan) → "Profil"
2. "Ubah Password":
   - Password Lama
   - Password Baru (min 6 karakter)
   - Konfirmasi
3. "Ubah Password"

### 10.2 Logout
1. Klik nama Anda → "Logout"

⚠️ **Selalu logout setelah selesai!**

---

## 💡 BAGIAN 11: TIPS ADMIN

### 11.1 Tips Artikel
- Judul menarik & informatif
- Gambar HD (min 1200x800px)
- Excerpt max 2-3 kalimat
- Paragraf pendek
- Cek ejaan sebelum publish

### 11.2 Tips Upload
- Format: JPG (foto), PNG (logo)
- Max 2MB per file
- Resolusi min 1200x800px
- Compress sebelum upload

### 11.3 Tips Jadwal
- Update rutin setiap minggu
- Pastikan skor akurat
- Input semua pencetak gol
- Gunakan logo resmi

### 11.4 Tips Backup
- Backup database mingguan
- Backup folder uploads bulanan
- Simpan di cloud storage
- Test restore berkala

---

## ⚠️ BAGIAN 12: TROUBLESHOOTING

### 12.1 Tidak Bisa Login
- Cek username/password
- Clear cache browser
- Coba browser lain

### 12.2 Gambar Tidak Upload
- Cek ukuran (max 2MB)
- Cek format (JPG/PNG)
- Cek permission folder

### 12.3 Artikel Tidak Muncul
- Pastikan status "Published"
- Cek tanggal publish
- Clear cache website

### 12.4 Error 500
- Cek error log PHP
- Cek syntax error
- Hubungi developer

---

## 📞 BAGIAN 13: SUPPORT

**Developer:** developer@manchesterside.com
**Support:** support@manchesterside.com
**Jam:** Senin-Jumat 09:00-17:00 WIB

---

## ✅ CHECKLIST HARIAN

### Pagi (09:00-12:00)
- [ ] Login admin panel
- [ ] Review artikel baru
- [ ] Publish artikel siap
- [ ] Update jadwal hari ini

### Siang (13:00-15:00)
- [ ] Buat artikel baru (min 1/hari)
- [ ] Update skor pertandingan
- [ ] Input pencetak gol
- [ ] Cek statistik

### Sore (15:00-17:00)
- [ ] Review draft artikel
- [ ] Update profil pemain/staff
- [ ] Backup (jika Jumat)
- [ ] Logout admin

---

## 🏆 SELAMAT BEKERJA!

**Two Sides, One City, Endless Rivalry** ⚽

---
**Versi:** 1.0 | **Update:** Desember 2025
