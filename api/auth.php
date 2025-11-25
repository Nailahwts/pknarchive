<?php
require_once 'helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cek apakah input dari Form Data atau JSON Raw
    $username = isset($_POST['username']) ? clean_input($_POST['username']) : ($input['username'] ?? '');
    $password = isset($_POST['password']) ? clean_input($_POST['password']) : ($input['password'] ?? '');

    if (empty($username) || empty($password)) {
        response(false, "Username dan Password wajib diisi");
    }

    // Query User
    $query = mysqli_query($koneksi, "SELECT * FROM tb_user WHERE username = '$username'");
    $user = mysqli_fetch_assoc($query);

    if ($user) {
        // Verifikasi Password Hash
        if (password_verify($password, $user['password'])) {
            // Hapus password dari respon agar aman
            unset($user['password']);
            
            response(true, "Login Berhasil", $user);
        } else {
            response(false, "Password salah");
        }
    } else {
        response(false, "Username tidak ditemukan");
    }
} else {
    response(false, "Method not allowed");
}
?>