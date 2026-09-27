<?php
/* ==========================================================================
 *  SIM MAHASISWA — Universitas Muhammadiyah Bengkulu
 *  Aplikasi 1 FILE: landing page, login, dashboard, logout.
 *  Tabel database dibuat OTOMATIS saat halaman pertama kali dibuka.
 * ==========================================================================
 *
 *  >>> PENGATURAN DATABASE — UBAH BAGIAN INI SAJA <<<
 *  Lihat di: Client Area InfinityFree > akun Anda > MySQL Databases
 *  Password = password akun hosting (BUKAN password login Client Area)
 */
$DB_HOST = '';
$DB_NAME = '';
$DB_USER = '';
$DB_PASS = '';
/* ======================= batas pengaturan ======================= */

// Jika dibuka dari XAMPP (localhost), otomatis pakai root tanpa password
if (in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1'], true)) {
    $DB_HOST = 'localhost';
    $DB_USER = 'root';
    $DB_PASS = '';
}

const APP_NAME   = 'SIM Mahasiswa';
const APP_KAMPUS = 'Universitas Muhammadiyah Bengkulu';
const TABEL      = ['universitas', 'fakultas', 'program_studi', 'jenis_kelamin', 'mahasiswa', 'pengguna'];

date_default_timezone_set('Asia/Jakarta');

// ==========================================================================
// SESSION & FUNGSI BANTU
// ==========================================================================
session_name('SIMMHS');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

$SELF = basename(__FILE__);   // index.php

function e($teks): string
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function pindah(string $tujuan): void
{
    header('Location: ' . $tujuan);
    exit;
}

function set_flash(string $tipe, string $pesan): void
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function ambil_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(): bool
{
    $t = $_POST['csrf'] ?? '';
    return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

function tanggal_indo(?string $tgl): string
{
    if (empty($tgl) || ($w = strtotime($tgl)) === false) {
        return '-';
    }
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
              'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return date('j', $w) . ' ' . $bulan[(int) date('n', $w)] . ' ' . date('Y', $w);
}

// ==========================================================================
// DATABASE — struktur hasil normalisasi 3NF + data contoh
// ==========================================================================
function buat_tabel(PDO $pdo): void
{
    $sql = [
        "CREATE TABLE IF NOT EXISTS universitas (
            id_universitas BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            kode_universitas VARCHAR(20) NOT NULL,
            nama_universitas VARCHAR(200) NOT NULL,
            slogan VARCHAR(255) DEFAULT NULL,
            alamat TEXT DEFAULT NULL,
            kota VARCHAR(100) DEFAULT NULL,
            provinsi VARCHAR(100) DEFAULT NULL,
            kode_pos VARCHAR(10) DEFAULT NULL,
            PRIMARY KEY (id_universitas),
            UNIQUE KEY uk_universitas_kode (kode_universitas)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS fakultas (
            id_fakultas BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_universitas BIGINT UNSIGNED NOT NULL,
            kode_fakultas VARCHAR(20) NOT NULL,
            nama_fakultas VARCHAR(150) NOT NULL,
            PRIMARY KEY (id_fakultas),
            UNIQUE KEY uk_fakultas_kode_universitas (id_universitas, kode_fakultas),
            UNIQUE KEY uk_fakultas_nama_universitas (id_universitas, nama_fakultas),
            CONSTRAINT fk_fakultas_universitas FOREIGN KEY (id_universitas)
                REFERENCES universitas (id_universitas) ON UPDATE CASCADE ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS program_studi (
            id_prodi BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_fakultas BIGINT UNSIGNED NOT NULL,
            kode_prodi VARCHAR(20) NOT NULL,
            nama_prodi VARCHAR(150) NOT NULL,
            PRIMARY KEY (id_prodi),
            UNIQUE KEY uk_prodi_kode_fakultas (id_fakultas, kode_prodi),
            UNIQUE KEY uk_prodi_nama_fakultas (id_fakultas, nama_prodi),
            CONSTRAINT fk_prodi_fakultas FOREIGN KEY (id_fakultas)
                REFERENCES fakultas (id_fakultas) ON UPDATE CASCADE ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS jenis_kelamin (
            id_jenis_kelamin TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nama_jenis_kelamin VARCHAR(30) NOT NULL,
            PRIMARY KEY (id_jenis_kelamin),
            UNIQUE KEY uk_jenis_kelamin_nama (nama_jenis_kelamin)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS mahasiswa (
            npm VARCHAR(30) NOT NULL,
            id_prodi BIGINT UNSIGNED NOT NULL,
            id_jenis_kelamin TINYINT UNSIGNED NOT NULL,
            nama_mahasiswa VARCHAR(150) NOT NULL,
            tempat_lahir VARCHAR(100) DEFAULT NULL,
            tanggal_lahir DATE DEFAULT NULL,
            tanggal_masuk DATE DEFAULT NULL,
            alamat TEXT DEFAULT NULL,
            PRIMARY KEY (npm),
            KEY idx_mahasiswa_prodi (id_prodi),
            KEY idx_mahasiswa_jenis_kelamin (id_jenis_kelamin),
            CONSTRAINT fk_mahasiswa_prodi FOREIGN KEY (id_prodi)
                REFERENCES program_studi (id_prodi) ON UPDATE CASCADE ON DELETE RESTRICT,
            CONSTRAINT fk_mahasiswa_jenis_kelamin FOREIGN KEY (id_jenis_kelamin)
                REFERENCES jenis_kelamin (id_jenis_kelamin) ON UPDATE CASCADE ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS pengguna (
            id_pengguna BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            npm VARCHAR(30) NOT NULL,
            username VARCHAR(100) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
            terakhir_login DATETIME DEFAULT NULL,
            PRIMARY KEY (id_pengguna),
            UNIQUE KEY uk_pengguna_npm (npm),
            UNIQUE KEY uk_pengguna_username (username),
            CONSTRAINT fk_pengguna_mahasiswa FOREIGN KEY (npm)
                REFERENCES mahasiswa (npm) ON UPDATE CASCADE ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // ---- Data awal (INSERT IGNORE: aman dijalankan berulang) ----
        "INSERT IGNORE INTO universitas (id_universitas, kode_universitas, nama_universitas, kota, provinsi)
         VALUES (1, 'UMB', 'Universitas Muhammadiyah Bengkulu', 'Bengkulu', 'Bengkulu')",
        "INSERT IGNORE INTO fakultas (id_fakultas, id_universitas, kode_fakultas, nama_fakultas)
         VALUES (1, 1, 'FT', 'Teknik')",
        "INSERT IGNORE INTO program_studi (id_prodi, id_fakultas, kode_prodi, nama_prodi)
         VALUES (1, 1, 'TI', 'Teknik Informatika')",
        "INSERT IGNORE INTO jenis_kelamin (id_jenis_kelamin, nama_jenis_kelamin)
         VALUES (1, 'Laki-laki'), (2, 'Perempuan')",

        // Data mahasiswa contoh (fiktif)
        "INSERT IGNORE INTO mahasiswa
            (npm, id_prodi, id_jenis_kelamin, nama_mahasiswa, tempat_lahir, tanggal_lahir, tanggal_masuk, alamat)
         VALUES
            ('22010001', 1, 1, 'Ahmad Fauzi',    'Bengkulu',   '2004-01-10', '2022-09-01', 'Jl. Merapi No. 12, Kota Bengkulu'),
            ('22010002', 1, 2, 'Siti Rahmawati', 'Curup',      '2004-03-22', '2022-09-01', 'Jl. Sudirman No. 45, Rejang Lebong'),
            ('22010003', 1, 1, 'Rizky Pratama',  'Manna',      '2003-11-05', '2022-09-01', 'Jl. Raya Pasar Manna No. 7, Bengkulu Selatan'),
            ('23010001', 1, 2, 'Dewi Anggraini', 'Argamakmur', '2005-06-14', '2023-09-04', 'Jl. Ahmad Yani No. 3, Bengkulu Utara'),
            ('23010002', 1, 1, 'Muhammad Ilham', 'Kepahiang',  '2005-02-28', '2023-09-04', 'Jl. Lintas Kepahiang No. 21, Kepahiang')",

        // Akun contoh: username = NPM, password = password123 (disimpan sebagai hash bcrypt)
        "INSERT IGNORE INTO pengguna (npm, username, password_hash, status) VALUES
            ('22010001', '22010001', '\$2y\$12\$t6ckgDPqAryt.IZyXdhm2uDz65X0JWpW9s5HusgMfStMYOpa6GRxy', 'Aktif'),
            ('22010002', '22010002', '\$2y\$12\$t6ckgDPqAryt.IZyXdhm2uDz65X0JWpW9s5HusgMfStMYOpa6GRxy', 'Aktif'),
            ('22010003', '22010003', '\$2y\$12\$t6ckgDPqAryt.IZyXdhm2uDz65X0JWpW9s5HusgMfStMYOpa6GRxy', 'Aktif'),
            ('23010001', '23010001', '\$2y\$12\$t6ckgDPqAryt.IZyXdhm2uDz65X0JWpW9s5HusgMfStMYOpa6GRxy', 'Aktif'),
            ('23010002', '23010002', '\$2y\$12\$t6ckgDPqAryt.IZyXdhm2uDz65X0JWpW9s5HusgMfStMYOpa6GRxy', 'Nonaktif')",
    ];

    foreach ($sql as $perintah) {
        $pdo->exec($perintah);
    }
}

/** Menjelaskan error MySQL dengan bahasa sederhana. */
function jelaskan_error(PDOException $ex): array
{
    $kode = (int) ($ex->errorInfo[1] ?? $ex->getCode());
    $saran = [
        1045 => 'Username atau password MySQL salah. Salin ulang password dari Client Area > MySQL Databases ke baris $DB_PASS di index.php.',
        1044 => 'Nama database salah atau bukan milik username ini. Samakan $DB_NAME persis dengan nama di MySQL Databases.',
        1049 => 'Database tidak ditemukan. Buat database baru di MySQL Databases, lalu samakan $DB_NAME.',
        2002 => 'MySQL Hostname tidak bisa dihubungi. Samakan $DB_HOST dengan "MySQL Hostname" di MySQL Databases.',
        2005 => 'MySQL Hostname tidak dikenal. Samakan $DB_HOST dengan "MySQL Hostname" di MySQL Databases.',
        1040 => 'Server MySQL hosting sedang penuh. Tunggu beberapa menit lalu muat ulang halaman.',
        1203 => 'Batas koneksi MySQL akun hosting tercapai. Tunggu beberapa menit lalu muat ulang halaman.',
        1142 => 'Username MySQL tidak punya izin membuat tabel. Impor file SQL lewat phpMyAdmin sebagai gantinya.',
    ];
    return [
        'saran'  => $saran[$kode] ?? 'Periksa kembali $DB_HOST, $DB_NAME, $DB_USER, dan $DB_PASS di bagian atas index.php.',
        'detail' => $ex->getMessage(),
    ];
}

// ---- Buka koneksi & pastikan tabel ada ----
$pdo     = null;
$dbError = null;

if ($DB_PASS === 'ISI_PASSWORD_HOSTING_ANDA') {
    $dbError = [
        'saran'  => 'Password database belum diisi. Buka index.php, ganti ISI_PASSWORD_HOSTING_ANDA pada baris $DB_PASS dengan password akun hosting Anda.',
        'detail' => 'Belum mencoba terhubung.',
    ];
} else {
    try {
        $pdo = new PDO(
            "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
            $DB_USER,
            $DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 10,
            ]
        );
        $pdo->exec("SET time_zone = '+07:00'");

        $ada = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        if (array_diff(TABEL, $ada)) {
            buat_tabel($pdo);   // pertama kali dibuka: buat tabel + data contoh
        }
    } catch (PDOException $ex) {
        $pdo     = null;
        $dbError = jelaskan_error($ex);
    }
}

// Query laporan "Daftar Mahasiswa Per Program Studi" (JOIN tabel 3NF)
const SQL_LAPORAN = "
    SELECT m.npm, m.nama_mahasiswa, jk.nama_jenis_kelamin AS jenis_kelamin,
           m.tempat_lahir, m.tanggal_lahir, m.tanggal_masuk, m.alamat,
           ps.id_prodi, ps.nama_prodi AS program_studi,
           f.nama_fakultas AS fakultas, u.nama_universitas AS universitas
    FROM mahasiswa m
    JOIN jenis_kelamin jk ON jk.id_jenis_kelamin = m.id_jenis_kelamin
    JOIN program_studi ps ON ps.id_prodi = m.id_prodi
    JOIN fakultas f       ON f.id_fakultas = ps.id_fakultas
    JOIN universitas u    ON u.id_universitas = f.id_universitas
";

// ==========================================================================
// PROSES FORM (LOGIN / LOGOUT)
// ==========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if (!csrf_valid()) {
        set_flash('error', 'Sesi formulir kedaluwarsa. Silakan coba lagi.');
        pindah($SELF . '#masuk');
    }

    // ---------------- LOGOUT ----------------
    if ($aksi === 'logout') {
        $_SESSION = [];
        session_regenerate_id(true);
        set_flash('sukses', 'Anda telah keluar. Sampai jumpa!');
        pindah($SELF . '#masuk');
    }

    // ---------------- LOGIN ----------------
    if ($aksi === 'login') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $_SESSION['old_username'] = $username;

        if ($username === '' || $password === '') {
            set_flash('error', 'NPM/username dan password wajib diisi.');
            pindah($SELF . '#masuk');
        }
        if (!$pdo) {
            set_flash('error', 'Database belum terhubung: ' . $dbError['saran']);
            pindah($SELF . '#masuk');
        }

        $stmt = $pdo->prepare(
            "SELECT p.id_pengguna, p.npm, p.username, p.password_hash, p.status, p.terakhir_login,
                    m.nama_mahasiswa
             FROM pengguna p
             JOIN mahasiswa m ON m.npm = p.npm
             WHERE p.username = ? OR p.npm = ?
             LIMIT 1"
        );
        $stmt->execute([$username, $username]);
        $akun = $stmt->fetch();

        if (!$akun || !password_verify($password, $akun['password_hash'])) {
            set_flash('error', 'NPM/username atau password salah.');
            pindah($SELF . '#masuk');
        }
        if ($akun['status'] !== 'Aktif') {
            set_flash('error', 'Akun Anda sedang nonaktif. Hubungi admin program studi.');
            pindah($SELF . '#masuk');
        }
        if (password_needs_rehash($akun['password_hash'], PASSWORD_DEFAULT)) {
            $pdo->prepare('UPDATE pengguna SET password_hash = ? WHERE id_pengguna = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $akun['id_pengguna']]);
        }

        session_regenerate_id(true);
        unset($_SESSION['old_username']);
        $_SESSION['pengguna'] = [
            'npm'              => $akun['npm'],
            'nama'             => $akun['nama_mahasiswa'],
            'login_sebelumnya' => $akun['terakhir_login'],
        ];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));

        $pdo->prepare('UPDATE pengguna SET terakhir_login = NOW() WHERE id_pengguna = ?')
            ->execute([$akun['id_pengguna']]);

        set_flash('sukses', 'Selamat datang, ' . $akun['nama_mahasiswa'] . '!');
        pindah($SELF);
    }

    pindah($SELF);
}

// ==========================================================================
// SIAPKAN DATA UNTUK TAMPILAN
// ==========================================================================
$flash    = ambil_flash();
$pengguna = $_SESSION['pengguna'] ?? null;
$profil   = null;
$daftar   = [];

if ($pengguna && $pdo) {
    // Dashboard
    $stmt = $pdo->prepare(SQL_LAPORAN . ' WHERE m.npm = ?');
    $stmt->execute([$pengguna['npm']]);
    $profil = $stmt->fetch();

    if ($profil) {
        $stmt = $pdo->prepare(SQL_LAPORAN . ' WHERE ps.id_prodi = ? ORDER BY m.npm');
        $stmt->execute([$profil['id_prodi']]);
        $daftar = $stmt->fetchAll();
    } else {
        $_SESSION['pengguna'] = null;   // data mahasiswa sudah dihapus
        $pengguna = null;
    }
} elseif ($pengguna && !$pdo) {
    $pengguna = null;                   // DB putus: tampilkan halaman depan + pesan error
}

$statistik = ['mahasiswa' => '–', 'prodi' => '–', 'fakultas' => '–'];
$prodiList = [];
if (!$pengguna && $pdo) {
    $statistik = $pdo->query(
        "SELECT (SELECT COUNT(*) FROM mahasiswa)     AS mahasiswa,
                (SELECT COUNT(*) FROM program_studi) AS prodi,
                (SELECT COUNT(*) FROM fakultas)      AS fakultas"
    )->fetch();
    $prodiList = $pdo->query(
        "SELECT ps.nama_prodi, f.nama_fakultas, COUNT(m.npm) AS jumlah
         FROM program_studi ps
         JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
         LEFT JOIN mahasiswa m ON m.id_prodi = ps.id_prodi
         GROUP BY ps.id_prodi, ps.nama_prodi, f.nama_fakultas
         ORDER BY f.nama_fakultas, ps.nama_prodi"
    )->fetchAll();
}

$oldUsername = $_SESSION['old_username'] ?? '';
unset($_SESSION['old_username']);

$ikonLogo = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/></svg>';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pengguna ? 'Dashboard · ' . APP_NAME : APP_NAME . ' · ' . APP_KAMPUS ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--hijau-900:#0a3d2a;--hijau-700:#0e5a3c;--hijau-600:#13704b;--hijau-100:#e3f1ea;--hijau-50:#f1f8f4;--emas:#e0a526;--emas-soft:#fbf1d9;--ink:#14211b;--ink-2:#45544c;--muted:#6b7a72;--line:#dde5e0;--bg:#f6f8f7;--surface:#fff;--merah:#b42318;--merah-soft:#fdecea;--radius:14px;--radius-sm:10px;--shadow:0 1px 2px rgba(10,61,42,.06),0 8px 24px rgba(10,61,42,.08);--font:"Plus Jakarta Sans",system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif}
*,*::before,*::after{box-sizing:border-box}
html{scroll-behavior:smooth;scroll-padding-top:84px}
body{margin:0;font-family:var(--font);font-size:16px;line-height:1.6;color:var(--ink);background:var(--bg);-webkit-font-smoothing:antialiased}
h1,h2,h3{line-height:1.2;margin:0 0 .5rem;letter-spacing:-.015em}
p{margin:0 0 1rem}a{color:var(--hijau-700)}
code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.88em;background:var(--hijau-100);color:var(--hijau-900);padding:.1em .4em;border-radius:6px}
.container{width:100%;max-width:1140px;margin:0 auto;padding:0 20px}
.muted{color:var(--muted)}.accent{color:var(--hijau-600)}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;padding:.8rem 1.4rem;border:1px solid transparent;border-radius:var(--radius-sm);background:var(--hijau-700);color:#fff;font:600 1rem/1 var(--font);text-decoration:none;cursor:pointer;transition:background .15s}
.btn:hover{background:var(--hijau-900)}.btn:focus-visible{outline:3px solid var(--emas);outline-offset:2px}
.btn--small{padding:.55rem 1rem;font-size:.92rem}.btn--block{width:100%;margin-top:.5rem}
.btn--ghost{background:transparent;color:var(--hijau-700);border-color:var(--line)}.btn--ghost:hover{background:var(--hijau-50)}
.navbar{position:sticky;top:0;z-index:10;background:rgba(255,255,255,.92);backdrop-filter:blur(8px);border-bottom:1px solid var(--line)}
.navbar__inner{display:flex;align-items:center;justify-content:space-between;gap:1rem;min-height:68px}
.brand{display:flex;align-items:center;gap:.75rem;text-decoration:none;color:var(--ink)}
.brand__logo{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:var(--hijau-700);color:#fff;flex-shrink:0}
.brand__logo svg{width:24px;height:24px}
.brand__name{display:block;font-size:1.05rem;line-height:1.2}.brand__sub{display:block;font-size:.78rem;color:var(--muted);line-height:1.3}
.nav{display:flex;align-items:center;gap:1.5rem}
.nav>a:not(.btn){color:var(--ink-2);text-decoration:none;font-weight:500;font-size:.95rem}.nav>a:not(.btn):hover{color:var(--hijau-700)}
.hero{padding:72px 0 88px;background:radial-gradient(900px 420px at 85% 10%,var(--hijau-100),transparent 70%),linear-gradient(180deg,var(--hijau-50),var(--bg))}
.hero__grid{display:grid;grid-template-columns:1.15fr .85fr;gap:56px;align-items:center}
.hero h1{font-size:clamp(2rem,4.2vw,3.1rem);font-weight:800;margin-bottom:1rem}
.lead{font-size:1.1rem;color:var(--ink-2);max-width:36rem}
.eyebrow{display:inline-block;margin-bottom:.9rem;padding:.3rem .75rem;border-radius:999px;background:var(--emas-soft);color:#7a5608;font-size:.8rem;font-weight:700;letter-spacing:.02em}
.stats{display:flex;flex-wrap:wrap;gap:14px;margin:2rem 0 0}
.stat{min-width:130px;padding:14px 18px;background:var(--surface);border:1px solid var(--line);border-radius:var(--radius-sm)}
.stat dt{font-size:.82rem;color:var(--muted)}.stat dd{margin:0;font-size:1.7rem;font-weight:800;color:var(--hijau-700);font-variant-numeric:tabular-nums}
.dberror{margin-top:1.5rem;padding:1rem 1.1rem;border:1px solid #f6c9c4;border-left:5px solid var(--merah);background:var(--merah-soft);border-radius:var(--radius-sm);font-size:.92rem}
.dberror strong{color:var(--merah)}.dberror p{margin:.35rem 0 0}
.dberror .detail{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.8rem;color:#7a271a;word-break:break-word}
.dberror table{margin-top:.6rem;font-size:.82rem;border-collapse:collapse}.dberror td{padding:.1rem .8rem .1rem 0;vertical-align:top}
.login-card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:32px;scroll-margin-top:100px}
.login-card h2{font-size:1.6rem}
.form{display:grid;gap:.45rem;margin-top:1.25rem}
.form label{font-weight:600;font-size:.9rem;margin-top:.45rem}
.form input{width:100%;padding:.8rem .95rem;border:1px solid var(--line);border-radius:var(--radius-sm);background:#fbfcfb;font:400 1rem var(--font);color:var(--ink)}
.form input:focus{outline:none;border-color:var(--hijau-600);box-shadow:0 0 0 4px rgba(19,112,75,.15);background:#fff}
.password-field{position:relative}.password-field input{padding-right:7.2rem}
.toggle-pass{position:absolute;top:50%;right:.5rem;transform:translateY(-50%);border:0;background:transparent;color:var(--hijau-700);font:600 .85rem var(--font);padding:.4rem .5rem;cursor:pointer;border-radius:6px}
.toggle-pass:hover{background:var(--hijau-50)}
.hint{margin:1rem 0 0;font-size:.85rem;color:var(--muted)}
.alert{margin:1rem 0 0;padding:.8rem 1rem;border-radius:var(--radius-sm);font-size:.93rem;font-weight:500}
.alert--error{background:var(--merah-soft);color:var(--merah);border:1px solid #f6c9c4}
.alert--sukses{background:var(--hijau-100);color:var(--hijau-900);border:1px solid #bfdccd}
.section{padding:80px 0}.section--alt{background:var(--surface);border-block:1px solid var(--line)}
.section__head{max-width:44rem;margin-bottom:2rem}.section__head h2{font-size:clamp(1.6rem,3vw,2.1rem);font-weight:800}
.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
.card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:24px}
.card h3{font-size:1.08rem}.card p{color:var(--ink-2);font-size:.95rem;margin:0}
.card__icon{display:grid;place-items:center;width:44px;height:44px;margin-bottom:1rem;border-radius:12px;background:var(--hijau-100);color:var(--hijau-700)}
.card__icon svg{width:22px;height:22px}
.prodi-list{list-style:none;margin:0;padding:0;display:grid;gap:12px}
.prodi{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:18px 22px;border:1px solid var(--line);border-radius:var(--radius-sm);background:var(--bg)}
.prodi strong{display:block;font-size:1.05rem}.prodi .muted{font-size:.9rem}
.badge{white-space:nowrap;padding:.3rem .75rem;border-radius:999px;background:var(--hijau-100);color:var(--hijau-900);font-size:.85rem;font-weight:700}
.flow{display:flex;flex-wrap:wrap;align-items:center;gap:10px}
.flow__item{padding:.7rem 1.1rem;border:1px solid var(--line);border-radius:var(--radius-sm);background:var(--surface);font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.92rem;font-weight:600}
.flow__item--main{background:var(--hijau-700);color:#fff;border-color:var(--hijau-700)}
.flow__arrow{color:var(--muted);font-weight:700}.flow__note{margin-top:1.25rem;font-size:.95rem}
.footer{padding:28px 0;border-top:1px solid var(--line);background:var(--surface);font-size:.9rem}
.footer__inner{display:flex;flex-wrap:wrap;justify-content:space-between;gap:.5rem 1.5rem}
.dash__main{padding-top:32px;padding-bottom:64px;display:grid;gap:24px}
.dash__main>*{min-width:0}.dash__main>.alert{margin:0}
.inline{display:inline;margin:0}
.user-chip{display:inline-flex;align-items:center;gap:.6rem;font-weight:600;font-size:.95rem}
.avatar{display:inline-grid;place-items:center;width:34px;height:34px;border-radius:50%;background:var(--hijau-100);color:var(--hijau-700);font-size:.95rem;font-weight:800}
.avatar--lg{width:64px;height:64px;font-size:1.6rem;background:var(--hijau-700);color:#fff;flex-shrink:0}
.panel{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:28px}
.profile__head{display:flex;align-items:center;gap:18px;margin-bottom:24px}
.profile__head h1{font-size:1.6rem;margin-bottom:.2rem}.profile__head p{margin:0}
.profile__grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px 24px;margin:0;padding-top:20px;border-top:1px solid var(--line)}
.profile__grid dt,.report__meta dt{font-size:.82rem;color:var(--muted)}
.profile__grid dd,.report__meta dd{margin:.1rem 0 0;font-weight:600}
.report__head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem}
.report__head h2{font-size:1.3rem;margin-bottom:.1rem}
.report__meta{display:flex;flex-wrap:wrap;gap:12px 40px;margin:18px 0 20px}
.table-wrap{overflow-x:auto;border:1px solid var(--line);border-radius:var(--radius-sm)}
.table{width:100%;border-collapse:collapse;font-size:.93rem}
.table th,.table td{padding:.75rem .9rem;text-align:left;border-bottom:1px solid var(--line);vertical-align:top}
.table th{background:var(--hijau-50);font-size:.8rem;font-weight:700;color:var(--ink-2);white-space:nowrap}
.table td:nth-child(-n+2),.table td:nth-child(6),.table td:nth-child(7){white-space:nowrap}
.table td:last-child{min-width:220px}
.table tbody tr:last-child td{border-bottom:0}.table tbody tr:hover{background:var(--hijau-50)}
.table tr.is-me td{background:var(--emas-soft)}
@media (max-width:960px){.hero__grid{grid-template-columns:1fr;gap:40px}.cards{grid-template-columns:repeat(2,1fr)}}
@media (max-width:640px){.container{padding:0 16px}.nav>a:not(.btn){display:none}.brand__sub{display:none}.user-chip{font-size:0;gap:0}.hero{padding:40px 0 56px}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.stat{min-width:0;padding:12px 14px}.stat dd{font-size:1.4rem}.login-card,.panel{padding:22px}.cards{grid-template-columns:1fr}.section{padding:56px 0}.prodi{flex-direction:column;align-items:flex-start}.profile__head{align-items:flex-start}}
@media print{body{background:#fff}.navbar,.profile,.no-print,.hint,.alert{display:none!important}.dash__main{padding:0}.panel{border:0;padding:0}.table-wrap{border:0;overflow:visible}.table th,.table td{border:1px solid #999;padding:4px 6px}.table tr.is-me td,.table th{background:none}}
</style>
</head>
<body>

<?php if ($pengguna && $profil): ?>
<?php
    /* ======================================================================
     *  DASHBOARD (setelah login)
     * ====================================================================== */
    $inisial = strtoupper(substr($profil['nama_mahasiswa'], 0, 1));
    $sebelum = $pengguna['login_sebelumnya'] ?? null;
?>
<header class="navbar">
    <div class="container navbar__inner">
        <a href="<?= e($SELF) ?>" class="brand">
            <span class="brand__logo" aria-hidden="true"><?= $ikonLogo ?></span>
            <span><strong class="brand__name"><?= e(APP_NAME) ?></strong><small class="brand__sub"><?= e($profil['universitas']) ?></small></span>
        </a>
        <div class="nav">
            <span class="user-chip"><span class="avatar" aria-hidden="true"><?= e($inisial) ?></span><?= e($pengguna['nama']) ?></span>
            <form method="post" action="<?= e($SELF) ?>" class="inline">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="aksi" value="logout">
                <button type="submit" class="btn btn--small btn--ghost">Keluar</button>
            </form>
        </div>
    </div>
</header>

<main class="container dash__main">
    <?php if ($flash): ?>
        <div class="alert alert--<?= $flash['tipe'] === 'sukses' ? 'sukses' : 'error' ?>" role="alert"><?= e($flash['pesan']) ?></div>
    <?php endif; ?>

    <section class="panel profile">
        <div class="profile__head">
            <span class="avatar avatar--lg" aria-hidden="true"><?= e($inisial) ?></span>
            <div>
                <h1><?= e($profil['nama_mahasiswa']) ?></h1>
                <p class="muted">NPM <?= e($profil['npm']) ?> · <?= e($profil['program_studi']) ?> · Fakultas <?= e($profil['fakultas']) ?></p>
            </div>
        </div>
        <dl class="profile__grid">
            <div><dt>Jenis Kelamin</dt><dd><?= e($profil['jenis_kelamin']) ?></dd></div>
            <div><dt>Tempat, Tanggal Lahir</dt><dd><?= e($profil['tempat_lahir'] ?: '-') ?>, <?= e(tanggal_indo($profil['tanggal_lahir'])) ?></dd></div>
            <div><dt>Tanggal Masuk</dt><dd><?= e(tanggal_indo($profil['tanggal_masuk'])) ?></dd></div>
            <div><dt>Alamat</dt><dd><?= e($profil['alamat'] ?: '-') ?></dd></div>
            <div><dt>Login Sebelumnya</dt><dd><?= $sebelum ? e(tanggal_indo($sebelum) . ', ' . date('H:i', strtotime($sebelum)) . ' WIB') : 'Ini login pertama Anda' ?></dd></div>
        </dl>
    </section>

    <section class="panel report">
        <div class="report__head">
            <div>
                <h2>Daftar Mahasiswa Per Program Studi</h2>
                <p class="muted"><?= e($profil['universitas']) ?></p>
            </div>
            <button type="button" class="btn btn--small btn--ghost no-print" onclick="window.print()">Cetak</button>
        </div>
        <dl class="report__meta">
            <div><dt>Program Studi</dt><dd><?= e($profil['program_studi']) ?></dd></div>
            <div><dt>Fakultas</dt><dd><?= e($profil['fakultas']) ?></dd></div>
            <div><dt>Jumlah</dt><dd><?= count($daftar) ?> mahasiswa</dd></div>
        </dl>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>No.</th><th>NPM</th><th>Nama Mahasiswa</th><th>Jenis Kelamin</th><th>Tempat Lahir</th><th>Tanggal Lahir</th><th>Tanggal Masuk</th><th>Alamat</th></tr>
                </thead>
                <tbody>
                <?php foreach ($daftar as $i => $m): ?>
                    <tr<?= $m['npm'] === $profil['npm'] ? ' class="is-me"' : '' ?>>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($m['npm']) ?></td>
                        <td><?= e($m['nama_mahasiswa']) ?></td>
                        <td><?= e($m['jenis_kelamin']) ?></td>
                        <td><?= e($m['tempat_lahir'] ?: '-') ?></td>
                        <td><?= e(tanggal_indo($m['tanggal_lahir'])) ?></td>
                        <td><?= e(tanggal_indo($m['tanggal_masuk'])) ?></td>
                        <td><?= e($m['alamat'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="hint">Kolom password tidak ditampilkan: password disimpan sebagai hash di tabel <code>pengguna</code>.</p>
    </section>
</main>

<?php else: ?>
<?php
    /* ======================================================================
     *  LANDING PAGE + FORM LOGIN
     * ====================================================================== */
?>
<header class="navbar">
    <div class="container navbar__inner">
        <a href="<?= e($SELF) ?>" class="brand">
            <span class="brand__logo" aria-hidden="true"><?= $ikonLogo ?></span>
            <span><strong class="brand__name"><?= e(APP_NAME) ?></strong><small class="brand__sub"><?= e(APP_KAMPUS) ?></small></span>
        </a>
        <nav class="nav" aria-label="Navigasi utama">
            <a href="#fitur">Fitur</a>
            <a href="#prodi">Program Studi</a>
            <a href="#struktur">Struktur Data</a>
            <a href="#masuk" class="btn btn--small">Masuk</a>
        </nav>
    </div>
</header>

<main>
    <section class="hero">
        <div class="container hero__grid">
            <div>
                <span class="eyebrow">Daftar Mahasiswa Per Program Studi</span>
                <h1>Data mahasiswa tersusun rapi, <span class="accent">mudah diakses</span> kapan saja.</h1>
                <p class="lead"><?= e(APP_NAME) ?> membantu mahasiswa <?= e(APP_KAMPUS) ?> melihat biodata dan daftar mahasiswa program studinya dari satu tempat, dengan akun yang aman.</p>

                <dl class="stats">
                    <div class="stat"><dt>Mahasiswa</dt><dd><?= e($statistik['mahasiswa']) ?></dd></div>
                    <div class="stat"><dt>Program Studi</dt><dd><?= e($statistik['prodi']) ?></dd></div>
                    <div class="stat"><dt>Fakultas</dt><dd><?= e($statistik['fakultas']) ?></dd></div>
                </dl>

                <?php if ($dbError): ?>
                    <div class="dberror" role="alert">
                        <strong>Database belum terhubung.</strong>
                        <p><?= e($dbError['saran']) ?></p>
                        <table>
                            <tr><td>Host</td><td><code><?= e($DB_HOST) ?></code></td></tr>
                            <tr><td>Database</td><td><code><?= e($DB_NAME) ?></code></td></tr>
                            <tr><td>Username</td><td><code><?= e($DB_USER) ?></code></td></tr>
                            <tr><td>Password</td><td><?= $DB_PASS === 'ISI_PASSWORD_HOSTING_ANDA' ? 'belum diisi' : 'sudah diisi' ?></td></tr>
                        </table>
                        <p class="detail">Pesan MySQL: <?= e($dbError['detail']) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="login-card" id="masuk">
                <h2>Masuk</h2>
                <p class="muted">Gunakan NPM dan password akun mahasiswa Anda.</p>

                <?php if ($flash): ?>
                    <div class="alert alert--<?= $flash['tipe'] === 'sukses' ? 'sukses' : 'error' ?>" role="alert"><?= e($flash['pesan']) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= e($SELF) ?>" class="form" novalidate>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="aksi" value="login">

                    <label for="username">NPM / Username</label>
                    <input type="text" id="username" name="username" value="<?= e($oldUsername) ?>" placeholder="Contoh: 22010001" autocomplete="username" required>

                    <label for="password">Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
                        <button type="button" class="toggle-pass" data-target="password" aria-label="Tampilkan password">Lihat</button>
                    </div>

                    <button type="submit" class="btn btn--block">Masuk</button>
                </form>
                <p class="hint">Lupa password? Hubungi admin program studi Anda.</p>
            </div>
        </div>
    </section>

    <section class="section" id="fitur">
        <div class="container">
            <header class="section__head"><span class="eyebrow">Fitur</span><h2>Yang bisa Anda lakukan</h2></header>
            <div class="cards">
                <article class="card">
                    <div class="card__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></div>
                    <h3>Biodata Mahasiswa</h3>
                    <p>NPM, nama, jenis kelamin, tempat dan tanggal lahir, tanggal masuk, serta alamat.</p>
                </article>
                <article class="card">
                    <div class="card__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M3 12h18M3 18h12"/></svg></div>
                    <h3>Daftar per Program Studi</h3>
                    <p>Lihat daftar mahasiswa satu program studi lengkap dengan fakultas dan universitasnya.</p>
                </article>
                <article class="card">
                    <div class="card__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></div>
                    <h3>Akun Aman</h3>
                    <p>Password disimpan sebagai hash bcrypt, dilengkapi proteksi CSRF dan session yang aman.</p>
                </article>
                <article class="card">
                    <div class="card__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v8H6z"/></svg></div>
                    <h3>Siap Dicetak</h3>
                    <p>Laporan daftar mahasiswa dapat langsung dicetak atau disimpan sebagai PDF dari browser.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section section--alt" id="prodi">
        <div class="container">
            <header class="section__head"><span class="eyebrow">Program Studi</span><h2>Program studi yang terdaftar</h2></header>
            <?php if ($prodiList): ?>
                <ul class="prodi-list">
                    <?php foreach ($prodiList as $p): ?>
                        <li class="prodi">
                            <div><strong><?= e($p['nama_prodi']) ?></strong><span class="muted">Fakultas <?= e($p['nama_fakultas']) ?></span></div>
                            <span class="badge"><?= (int) $p['jumlah'] ?> mahasiswa</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="muted">Data program studi belum tersedia.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="section" id="struktur">
        <div class="container">
            <header class="section__head">
                <span class="eyebrow">Struktur Data</span>
                <h2>Dinormalisasi hingga 3NF</h2>
                <p class="muted">Laporan “Daftar Mahasiswa Per Program Studi” dipecah menjadi enam tabel agar tidak ada data berulang dan setiap atribut bergantung langsung pada kuncinya.</p>
            </header>
            <div class="flow">
                <div class="flow__item">universitas</div><span class="flow__arrow" aria-hidden="true">→</span>
                <div class="flow__item">fakultas</div><span class="flow__arrow" aria-hidden="true">→</span>
                <div class="flow__item">program_studi</div><span class="flow__arrow" aria-hidden="true">→</span>
                <div class="flow__item flow__item--main">mahasiswa</div><span class="flow__arrow" aria-hidden="true">→</span>
                <div class="flow__item">pengguna</div>
            </div>
            <p class="flow__note muted">Tabel <code>jenis_kelamin</code> menjadi referensi untuk <code>mahasiswa</code>; password disimpan terpisah di <code>pengguna</code> dalam bentuk hash.</p>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="container footer__inner">
        <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></span>
        <span class="muted">Program Studi Teknik Informatika · Fakultas Teknik · <?= e(APP_KAMPUS) ?></span>
    </div>
</footer>

<script>
document.querySelectorAll('.toggle-pass').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(btn.dataset.target);
        var tampil = input.type === 'password';
        input.type = tampil ? 'text' : 'password';
        btn.textContent = tampil ? 'Sembunyikan' : 'Lihat';
    });
});
window.addEventListener('load', function () {
    if (location.hash !== '#masuk') return;
    setTimeout(function () {
        var u = document.getElementById('username');
        (u.value ? document.getElementById('password') : u).focus();
    }, 0);
});
</script>
<?php endif; ?>
</body>
</html>
