-- ============================================================
-- DUMP SQL DATABASE: if0_42301805_simmhs
-- Hasil Normalisasi Basis Data Mahasiswa sampai 3NF
-- Sumber laporan : Daftar Mahasiswa Per Program Studi
--                  Universitas Muhammadiyah Bengkulu
-- Kompatibel     : InfinityFree (MySQL/MariaDB), XAMPP, Laragon
-- ============================================================
--
-- Relasi hasil 3NF:
--   universitas   (id_universitas PK, kode_universitas, nama_universitas, ...)
--   fakultas      (id_fakultas PK, id_universitas FK, kode_fakultas, nama_fakultas)
--   program_studi (id_prodi PK, id_fakultas FK, kode_prodi, nama_prodi)
--   jenis_kelamin (id_jenis_kelamin PK, nama_jenis_kelamin)
--   mahasiswa     (npm PK, id_prodi FK, id_jenis_kelamin FK, nama_mahasiswa,
--                  tempat_lahir, tanggal_lahir, tanggal_masuk, alamat)
--   pengguna      (id_pengguna PK, npm FK, username, password_hash, status,
--                  terakhir_login)
--
-- CATATAN: IMPOR INI OPSIONAL. index.php membuat tabel + data contoh
-- secara otomatis saat pertama kali dibuka. File ini untuk laporan tugas
-- atau jika ingin mengisi database lewat phpMyAdmin.
--
-- CARA IMPOR
--   InfinityFree : phpMyAdmin > klik database if0_42301805_simmhs di kiri
--                  > tab Import > pilih file ini > Go.
--   XAMPP        : buat dulu database bernama if0_42301805_simmhs
--                  (collation utf8mb4_unicode_ci), klik database itu,
--                  lalu Import file ini.
--
-- File ini sengaja TIDAK memakai DROP/CREATE DATABASE, USE, dan
-- CREATE VIEW karena perintah tersebut diblokir di InfinityFree.
-- Tabel lama dengan nama yang sama akan dihapus lalu dibuat ulang,
-- jadi file ini aman diimpor berulang kali.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `pengguna`;
DROP TABLE IF EXISTS `mahasiswa`;
DROP TABLE IF EXISTS `jenis_kelamin`;
DROP TABLE IF EXISTS `program_studi`;
DROP TABLE IF EXISTS `fakultas`;
DROP TABLE IF EXISTS `universitas`;

-- ============================================================
-- 1. TABEL UNIVERSITAS
-- Hasil 3NF: nama_universitas bergantung pada universitas,
-- bukan pada NPM (ketergantungan transitif dipisahkan).
-- ============================================================
CREATE TABLE `universitas` (
    `id_universitas` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode_universitas` VARCHAR(20) NOT NULL,
    `nama_universitas` VARCHAR(200) NOT NULL,
    `slogan` VARCHAR(255) DEFAULT NULL,
    `alamat` TEXT DEFAULT NULL,
    `kota` VARCHAR(100) DEFAULT NULL,
    `provinsi` VARCHAR(100) DEFAULT NULL,
    `kode_pos` VARCHAR(10) DEFAULT NULL,
    PRIMARY KEY (`id_universitas`),
    UNIQUE KEY `uk_universitas_kode` (`kode_universitas`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. TABEL FAKULTAS
-- Hasil 3NF: nama_prodi -> nama_fakultas -> nama_universitas
-- ============================================================
CREATE TABLE `fakultas` (
    `id_fakultas` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_universitas` BIGINT UNSIGNED NOT NULL,
    `kode_fakultas` VARCHAR(20) NOT NULL,
    `nama_fakultas` VARCHAR(150) NOT NULL,
    PRIMARY KEY (`id_fakultas`),
    UNIQUE KEY `uk_fakultas_kode_universitas` (`id_universitas`, `kode_fakultas`),
    UNIQUE KEY `uk_fakultas_nama_universitas` (`id_universitas`, `nama_fakultas`),
    CONSTRAINT `fk_fakultas_universitas`
        FOREIGN KEY (`id_universitas`)
        REFERENCES `universitas` (`id_universitas`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. TABEL PROGRAM STUDI
-- Hasil 3NF: NPM -> id_prodi -> id_fakultas
-- ============================================================
CREATE TABLE `program_studi` (
    `id_prodi` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_fakultas` BIGINT UNSIGNED NOT NULL,
    `kode_prodi` VARCHAR(20) NOT NULL,
    `nama_prodi` VARCHAR(150) NOT NULL,
    PRIMARY KEY (`id_prodi`),
    UNIQUE KEY `uk_prodi_kode_fakultas` (`id_fakultas`, `kode_prodi`),
    UNIQUE KEY `uk_prodi_nama_fakultas` (`id_fakultas`, `nama_prodi`),
    CONSTRAINT `fk_prodi_fakultas`
        FOREIGN KEY (`id_fakultas`)
        REFERENCES `fakultas` (`id_fakultas`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. TABEL JENIS KELAMIN (tabel referensi)
-- Menjaga konsistensi nilai (tidak ada "L", "Laki2", "Pria", dst.)
-- ============================================================
CREATE TABLE `jenis_kelamin` (
    `id_jenis_kelamin` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama_jenis_kelamin` VARCHAR(30) NOT NULL,
    PRIMARY KEY (`id_jenis_kelamin`),
    UNIQUE KEY `uk_jenis_kelamin_nama` (`nama_jenis_kelamin`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. TABEL MAHASISWA
-- Semua atribut non-kunci bergantung penuh dan langsung pada NPM.
-- ============================================================
CREATE TABLE `mahasiswa` (
    `npm` VARCHAR(30) NOT NULL,
    `id_prodi` BIGINT UNSIGNED NOT NULL,
    `id_jenis_kelamin` TINYINT UNSIGNED NOT NULL,
    `nama_mahasiswa` VARCHAR(150) NOT NULL,
    `tempat_lahir` VARCHAR(100) DEFAULT NULL,
    `tanggal_lahir` DATE DEFAULT NULL,
    `tanggal_masuk` DATE DEFAULT NULL,
    `alamat` TEXT DEFAULT NULL,
    PRIMARY KEY (`npm`),
    KEY `idx_mahasiswa_prodi` (`id_prodi`),
    KEY `idx_mahasiswa_jenis_kelamin` (`id_jenis_kelamin`),
    KEY `idx_mahasiswa_nama` (`nama_mahasiswa`),
    CONSTRAINT `fk_mahasiswa_prodi`
        FOREIGN KEY (`id_prodi`)
        REFERENCES `program_studi` (`id_prodi`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_mahasiswa_jenis_kelamin`
        FOREIGN KEY (`id_jenis_kelamin`)
        REFERENCES `jenis_kelamin` (`id_jenis_kelamin`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. TABEL PENGGUNA (akun login mahasiswa)
-- Kolom "Password" pada laporan disimpan di sini sebagai
-- password_hash (bcrypt dari PHP password_hash()), bukan plaintext.
-- Relasi 1:1 dengan mahasiswa (npm UNIQUE).
-- ============================================================
CREATE TABLE `pengguna` (
    `id_pengguna` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `npm` VARCHAR(30) NOT NULL,
    `username` VARCHAR(100) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `status` ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
    `terakhir_login` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id_pengguna`),
    UNIQUE KEY `uk_pengguna_npm` (`npm`),
    UNIQUE KEY `uk_pengguna_username` (`username`),
    CONSTRAINT `fk_pengguna_mahasiswa`
        FOREIGN KEY (`npm`)
        REFERENCES `mahasiswa` (`npm`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATA AWAL / SEED
-- ============================================================

-- Universitas
INSERT INTO `universitas`
    (`id_universitas`, `kode_universitas`, `nama_universitas`, `kota`, `provinsi`)
VALUES
    (1, 'UMB', 'Universitas Muhammadiyah Bengkulu', 'Bengkulu', 'Bengkulu');

-- Fakultas
INSERT INTO `fakultas`
    (`id_fakultas`, `id_universitas`, `kode_fakultas`, `nama_fakultas`)
VALUES
    (1, 1, 'FT', 'Teknik');

-- Program Studi
INSERT INTO `program_studi`
    (`id_prodi`, `id_fakultas`, `kode_prodi`, `nama_prodi`)
VALUES
    (1, 1, 'TI', 'Teknik Informatika');

-- Jenis Kelamin
INSERT INTO `jenis_kelamin`
    (`id_jenis_kelamin`, `nama_jenis_kelamin`)
VALUES
    (1, 'Laki-laki'),
    (2, 'Perempuan');

-- ============================================================
-- DATA CONTOH MAHASISWA (fiktif, untuk uji coba aplikasi)
-- Ganti / hapus sesuai data sebenarnya.
-- ============================================================
INSERT INTO `mahasiswa`
    (`npm`, `id_prodi`, `id_jenis_kelamin`, `nama_mahasiswa`,
     `tempat_lahir`, `tanggal_lahir`, `tanggal_masuk`, `alamat`)
VALUES
    ('22010001', 1, 1, 'Ahmad Fauzi',     'Bengkulu',   '2004-01-10', '2022-09-01', 'Jl. Merapi No. 12, Kota Bengkulu'),
    ('22010002', 1, 2, 'Siti Rahmawati',  'Curup',      '2004-03-22', '2022-09-01', 'Jl. Sudirman No. 45, Rejang Lebong'),
    ('22010003', 1, 1, 'Rizky Pratama',   'Manna',      '2003-11-05', '2022-09-01', 'Jl. Raya Pasar Manna No. 7, Bengkulu Selatan'),
    ('23010001', 1, 2, 'Dewi Anggraini',  'Argamakmur', '2005-06-14', '2023-09-04', 'Jl. Ahmad Yani No. 3, Bengkulu Utara'),
    ('23010002', 1, 1, 'Muhammad Ilham',  'Kepahiang',  '2005-02-28', '2023-09-04', 'Jl. Lintas Kepahiang No. 21, Kepahiang');

-- ============================================================
-- DATA CONTOH PENGGUNA
-- Username = NPM, password awal semua akun contoh: password123
-- Hash dibuat dengan PHP: password_hash('password123', PASSWORD_DEFAULT)
-- Jangan menyimpan password plaintext.
-- ============================================================
INSERT INTO `pengguna`
    (`npm`, `username`, `password_hash`, `status`)
VALUES
    ('22010001', '22010001', '$2y$12$t6ckgDPqAryt.IZyXdhm2uDz65X0JWpW9s5HusgMfStMYOpa6GRxy', 'Aktif'),
    ('22010002', '22010002', '$2y$12$t6ckgDPqAryt.IZyXdhm2uDz65X0JWpW9s5HusgMfStMYOpa6GRxy', 'Aktif'),
    ('22010003', '22010003', '$2y$12$t6ckgDPqAryt.IZyXdhm2uDz65X0JWpW9s5HusgMfStMYOpa6GRxy', 'Aktif'),
    ('23010001', '23010001', '$2y$12$t6ckgDPqAryt.IZyXdhm2uDz65X0JWpW9s5HusgMfStMYOpa6GRxy', 'Aktif'),
    ('23010002', '23010002', '$2y$12$t6ckgDPqAryt.IZyXdhm2uDz65X0JWpW9s5HusgMfStMYOpa6GRxy', 'Nonaktif');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- CEK RELASI / LAPORAN MAHASISWA PER PROGRAM STUDI
-- (jalankan di tab SQL phpMyAdmin untuk memeriksa hasil JOIN)
-- ============================================================
-- SELECT m.npm, m.nama_mahasiswa,
--        jk.nama_jenis_kelamin AS jenis_kelamin,
--        m.tempat_lahir, m.tanggal_lahir, m.tanggal_masuk, m.alamat,
--        ps.nama_prodi       AS program_studi,
--        f.nama_fakultas     AS fakultas,
--        u.nama_universitas  AS universitas
-- FROM mahasiswa m
-- JOIN jenis_kelamin jk ON jk.id_jenis_kelamin = m.id_jenis_kelamin
-- JOIN program_studi ps ON ps.id_prodi = m.id_prodi
-- JOIN fakultas f       ON f.id_fakultas = ps.id_fakultas
-- JOIN universitas u    ON u.id_universitas = f.id_universitas
-- WHERE ps.nama_prodi = 'Teknik Informatika'
-- ORDER BY m.npm;
