<?php
session_start();
include 'koneksi.php';

// Jika sudah login, redirect
if (isset($_SESSION['id_user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// PROSES REGISTRASI
if (isset($_POST['daftar'])) {
    $nama_lengkap = clean_input($_POST['nama_lengkap']);
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    // Validasi input
    if (empty($nama_lengkap) || empty($username) || empty($password)) {
        $error = 'Semua field wajib diisi!';
    } 
    elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    }
    elseif ($password !== $password_confirm) {
        $error = 'Konfirmasi password tidak cocok!';
    }
    else {
        // Cek username sudah ada atau belum
        $query_check = "SELECT id_user FROM tb_user WHERE username = ?";
        $result_check = db_select($koneksi, $query_check, "s", [$username]);
        
        if ($result_check && mysqli_num_rows($result_check) > 0) {
            $error = 'Username sudah digunakan!';
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user baru dengan role 'user'
            $query = "INSERT INTO tb_user (nama_lengkap, username, password, role) VALUES (?, ?, ?, 'user')";
            if (db_execute($koneksi, $query, "sss", [$nama_lengkap, $username, $password_hash])) {
                header('Location: login.php?register=success');
                exit;
            } else {
                $error = 'Gagal mendaftar. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Sistem Arsip Surat</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #0b131c 0%, #1c2b3e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }
        .register-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
        }
        .register-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .register-header i {
            font-size: 50px;
            margin-bottom: 10px;
        }
        .register-body {
            padding: 30px;
        }
        .btn-register {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.4);
        }
        .login-link {
            text-align: center;
            margin-top: 15px;
        }
        .login-link a {
            font-weight: 600;
            color: #0d6efd;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="fas fa-user-plus"></i>
                <h3 class="mb-0">Daftar Akun Baru</h3>
                <p class="mb-0"><small>Sistem Arsip Surat</small></p>
            </div>

            <div class="register-body">
                <?php if (!empty($error)) : ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username</label>
                        <input type="text" name="username" class="form-control" required>
                        <small class="text-muted">Username akan digunakan untuk login</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Konfirmasi Password</label>
                        <input type="password" name="password_confirm" class="form-control" minlength="6" required>
                    </div>

                    <button type="submit" name="daftar" class="btn btn-primary btn-register w-100">
                        <i class="fas fa-user-plus"></i> Daftar Sekarang
                    </button>
                </form>

                <div class="login-link">
                    Sudah punya akun?
                    <a href="login.php">Login di sini</a>
                </div>
            </div>
        </div>

        <div class="text-center mt-3">
            <small class="text-white">
                &copy; <?php echo date('Y'); ?> Sistem Arsip Surat. All rights reserved.
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>