<?php
// Pengaturan cookie yang aman
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $cookieParams['lifetime'],
    'path' => $cookieParams['path'],
    'domain' => $cookieParams['domain'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();
require_once 'koneksi.php';

// Security headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com; form-action 'self';");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Jika sudah login, redirect
if (isset($_SESSION['id_user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// PROSES REGISTRASI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['daftar'])) {
    $nama_lengkap = isset($_POST['nama_lengkap']) ? trim($_POST['nama_lengkap']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
    
    // Validasi input
    if (empty($nama_lengkap) || empty($username) || empty($password)) {
        $error = 'Semua field wajib diisi!';
    } 
    elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter!';
    }
    elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    }
    elseif ($password !== $password_confirm) {
        $error = 'Konfirmasi password tidak cocok!';
    }
    else {
        // Cek username sudah ada atau belum
        $query_check = "SELECT id_user FROM tb_user WHERE username = ? LIMIT 1";
        $stmt_check = mysqli_prepare($koneksi, $query_check);
        
        if ($stmt_check) {
            mysqli_stmt_bind_param($stmt_check, "s", $username);
            mysqli_stmt_execute($stmt_check);
            $result_check = mysqli_stmt_get_result($stmt_check);
            
            if ($result_check && mysqli_num_rows($result_check) > 0) {
                $error = 'Username sudah digunakan! Silakan pilih username lain.';
                mysqli_stmt_close($stmt_check);
            } else {
                mysqli_stmt_close($stmt_check);
                
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user baru dengan role 'user'
                $query = "INSERT INTO tb_user (nama_lengkap, username, password, role) VALUES (?, ?, ?, 'user')";
                $stmt = mysqli_prepare($koneksi, $query);
                
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sss", $nama_lengkap, $username, $password_hash);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_close($stmt);
                        mysqli_close($koneksi);
                        
                        // Set pesan success di session
                        $_SESSION['register_success'] = 'Registrasi berhasil! Silakan login dengan akun Anda.';
                        header('Location: login.php');
                        exit;
                    } else {
                        $error = 'Gagal mendaftar. Silakan coba lagi.';
                        mysqli_stmt_close($stmt);
                    }
                } else {
                    $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
                }
            }
        } else {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
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
            padding: 20px;
        }
        .register-container {
            max-width: 450px;
            width: 100%;
        }
        .register-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.6s ease;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
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
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
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
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        .login-link a {
            font-weight: 600;
            color: #0d6efd;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .alert {
            border-radius: 10px;
        }
        @media (max-width: 576px) {
            .register-body {
                padding: 20px;
            }
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
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)) : ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-user"></i> Nama Lengkap
                        </label>
                        <input type="text" name="nama_lengkap" class="form-control" 
                               placeholder="Masukkan nama lengkap" required autofocus
                               value="<?php echo isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-at"></i> Username
                        </label>
                        <input type="text" name="username" class="form-control" 
                               placeholder="Masukkan username" minlength="4" required
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        <small class="text-muted">Minimal 4 karakter, digunakan untuk login</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" name="password" class="form-control" 
                               placeholder="Masukkan password" minlength="6" required>
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-lock"></i> Konfirmasi Password
                        </label>
                        <input type="password" name="password_confirm" class="form-control" 
                               placeholder="Ulangi password" minlength="6" required>
                    </div>

                    <button type="submit" name="daftar" class="btn btn-primary btn-register w-100">
                        <i class="fas fa-user-plus"></i> Daftar Sekarang
                    </button>
                </form>

                <div class="login-link">
                    Sudah punya akun?
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login di sini</a>
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