<?php
// Konfigurasi Database
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'db_arsip_surat';

// Koneksi ke Database
$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset & Session
mysqli_set_charset($koneksi, "utf8mb4");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === FUNGSI KEAMANAN (CSRF TOKEN) ===
// Wajib ada agar CRUD tidak ditolak
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validate_csrf_token')) {
    function validate_csrf_token($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Variabel User Login
$role_user = $_SESSION['role'] ?? '';
$nama_user = $_SESSION['nama_lengkap'] ?? '';
$id_user   = $_SESSION['id_user'] ?? '';

// Fungsi Validasi Input
function clean_input($data) {
    global $koneksi;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($koneksi, $data);
}

function escape_html($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Cek Login & Admin
function cek_login() {
    if (!isset($_SESSION['id_user'])) {
        header("Location: login.php");
        exit;
    }
}
?>