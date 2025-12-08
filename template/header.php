<?php
if (!isset($koneksi)) {
    include 'koneksi.php';
}

cek_login();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Sistem Arsip Surat'; ?></title>
    
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body>
    <div id="overlay"></div>

    <button class="hamburger-btn" id="hamburgerBtn">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-folder-open"></i> Arsip Surat</h3>
        </div>

        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>

            <hr class="sidebar-divider">

            <?php if ($role_user == 'admin'): ?>
            <li>
                <a href="#" class="dropdown-toggle-custom">
                    <i class="fas fa-database"></i> Master Data
                    <span class="arrow">▼</span>
                </a>
                <ul class="submenu">
                    <li><a href="kategori.php"><i class="fas fa-tags"></i> Kategori</a></li>
                    <li><a href="user.php"><i class="fas fa-users"></i> User</a></li>
                </ul>
            </li>
            <?php endif; ?>

            <li>
                <a href="#" class="dropdown-toggle-custom">
                    <i class="fas fa-envelope"></i> Surat
                    <span class="arrow">▼</span>
                </a>
                <ul class="submenu">
                    <li><a href="surat_masuk.php"><i class="fas fa-inbox"></i> Surat Masuk</a></li>
                    <li><a href="surat_keluar.php"><i class="fas fa-paper-plane"></i> Surat Keluar</a></li>
                </ul>
            </li>

            <li>
                <a href="#" class="dropdown-toggle-custom">
                    <i class="fas fa-sticky-note"></i> Memo
                    <span class="arrow">▼</span>
                </a>
                <ul class="submenu">
                    <li><a href="memo_masuk.php"><i class="fas fa-inbox"></i> Memo Masuk</a></li>
                    <li><a href="memo_keluar.php"><i class="fas fa-paper-plane"></i> Memo Keluar</a></li>
                </ul>
            </li>

            <li>
                <a href="#" class="dropdown-toggle-custom">
                    <i class="fas fa-file-contract"></i> Kontrak
                    <span class="arrow">▼</span>
                </a>
                <ul class="submenu">
                    <li><a href="kontrak_sopir.php"><i class="fas fa-car"></i> Kontrak Sopir</a></li>
                    <li><a href="kontrak_staf.php"><i class="fas fa-user-tie"></i> Kontrak Staf Administrasi</a></li>
                </ul>
            </li>

            <li>
                <a href="pinjaman_modal.php">
                    <i class="fas fa-money-bill-wave"></i> Pinjaman Modal
                </a>
            </li>

            <li>
                <a href="#" class="dropdown-toggle-custom">
                    <i class="fas fa-heartbeat"></i> Kesehatan
                    <span class="arrow">▼</span>
                </a>
                <ul class="submenu">
                    <li><a href="peserta.php"><i class="fas fa-users"></i> Data Peserta</a></li>
                    <li><a href="pemeriksaan.php"><i class="fas fa-stethoscope"></i> Data Pemeriksaan</a></li>
                </ul>
            </li>

            <hr class="sidebar-divider">

            <li>
                <a href="profil.php">
                    <i class="fas fa-user-circle"></i> Profil
                </a>
            </li>

            <li>
                <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?')">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="header">
        <div class="title">
            <i class="fas fa-file-alt"></i> 
            <?php echo isset($page_title) ? $page_title : 'Dashboard'; ?>
        </div>
        <div class="user-info">
            <span class="username">
                <i class="fas fa-user"></i> <?php echo escape_html($nama_user); ?>
                <?php if ($role_user == 'admin'): ?>
                    <span style="font-size: 11px; background: #0d6efd; color: white; padding: 2px 8px; border-radius: 10px; margin-left: 5px;">Admin</span>
                <?php endif; ?>
            </span>
        </div>
    </div>

    <div class="main-content">