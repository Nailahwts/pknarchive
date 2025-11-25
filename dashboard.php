<?php
require_once 'koneksi.php';
cek_login();
include 'template/header.php';

$stats = [];

$stmt = $koneksi->prepare("SELECT COUNT(*) AS total FROM tb_surat_masuk");
$stmt->execute();
$result = $stmt->get_result();
$stats['surat_masuk'] = $result->fetch_assoc()['total'];
$stmt->close();

$stmt = $koneksi->prepare("SELECT COUNT(*) AS total FROM tb_surat_keluar");
$stmt->execute();
$result = $stmt->get_result();
$stats['surat_keluar'] = $result->fetch_assoc()['total'];
$stmt->close();

$stmt = $koneksi->prepare("SELECT COUNT(*) AS total FROM tb_memo_masuk");
$stmt->execute();
$result = $stmt->get_result();
$stats['memo_masuk'] = $result->fetch_assoc()['total'];
$stmt->close();

$stmt = $koneksi->prepare("SELECT COUNT(*) AS total FROM tb_memo_keluar");
$stmt->execute();
$result = $stmt->get_result();
$stats['memo_keluar'] = $result->fetch_assoc()['total'];
$stmt->close();

$stmt_sm = $koneksi->prepare("
    SELECT sm.*, k.nama_kategori 
    FROM tb_surat_masuk sm 
    LEFT JOIN tb_kategori k ON sm.id_kategori = k.id_kategori 
    ORDER BY sm.tgl_terima DESC 
    LIMIT 5
");
$stmt_sm->execute();
$result_sm = $stmt_sm->get_result();

$stmt_sk = $koneksi->prepare("
    SELECT sk.*, k.nama_kategori 
    FROM tb_surat_keluar sk 
    LEFT JOIN tb_kategori k ON sk.id_kategori = k.id_kategori 
    ORDER BY sk.tgl_surat DESC 
    LIMIT 5
");
$stmt_sk->execute();
$result_sk = $stmt_sk->get_result();

$stmt_mm = $koneksi->prepare("
    SELECT mm.*, k.nama_kategori 
    FROM tb_memo_masuk mm 
    LEFT JOIN tb_kategori k ON mm.id_kategori = k.id_kategori 
    ORDER BY mm.tgl_terima DESC 
    LIMIT 5
");
$stmt_mm->execute();
$result_mm = $stmt_mm->get_result();

$stmt_mk = $koneksi->prepare("
    SELECT mk.*, k.nama_kategori 
    FROM tb_memo_keluar mk 
    LEFT JOIN tb_kategori k ON mk.id_kategori = k.id_kategori 
    ORDER BY mk.tgl_memo DESC 
    LIMIT 5
");
$stmt_mk->execute();
$result_mk = $stmt_mk->get_result();
?>

<style>
.card-stats {
    transition: transform 0.2s;
}
.card-stats:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
.table-dashboard {
    font-size: 14px;
}
.badge-kategori {
    font-size: 11px;
}
</style>

<div class="container-fluid">
    
    <!-- Header Welcome -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tachometer-alt"></i> Dashboard Arsip Surat
        </h1>
        <div class="text-muted">
            <i class="far fa-clock"></i> <?php echo date('d F Y'); ?>
        </div>
    </div>

    <!-- Welcome Message -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle"></i>
        <strong>Selamat datang, <?php echo escape_html($nama_user); ?>!</strong>
        <br>
        <small>
            Role Anda: <strong><?php echo escape_html(ucfirst($role_user)); ?></strong>
            <?php if ($role_user === 'admin'): ?>
                - Anda memiliki akses penuh (CRUD) untuk mengelola semua data arsip.
            <?php else: ?>
                - Anda dapat melihat data arsip (Read Only).
            <?php endif; ?>
        </small>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <!-- Surat Masuk -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 card-stats">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Surat Masuk
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['surat_masuk']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope-open-text fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Surat Keluar -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 card-stats">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Surat Keluar
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['surat_keluar']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-paper-plane fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Memo Masuk -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 card-stats">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Memo Masuk
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['memo_masuk']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-inbox fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Memo Keluar -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 card-stats">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Memo Keluar
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['memo_keluar']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-export fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables Row -->
    <div class="row">
        
        <!-- Surat Masuk Terbaru -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-envelope-open-text"></i> Surat Masuk Terbaru
                    </h6>
                    <a href="surat_masuk.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-dashboard">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>No. Surat</th>
                                    <th>Dari</th>
                                    <th>Kategori</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($result_sm->num_rows > 0):
                                    while ($row = $result_sm->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($row['tgl_terima'])); ?></td>
                                    <td><small><?php echo escape_html($row['no_surat']); ?></small></td>
                                    <td><?php echo escape_html($row['dari']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary badge-kategori">
                                            <?php echo escape_html($row['nama_kategori']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        <i>Belum ada data surat masuk</i>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Surat Keluar Terbaru -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-paper-plane"></i> Surat Keluar Terbaru
                    </h6>
                    <a href="surat_keluar.php" class="btn btn-sm btn-success">
                        <i class="fas fa-eye"></i> Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-dashboard">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>No. Surat</th>
                                    <th>Kepada</th>
                                    <th>Kategori</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($result_sk->num_rows > 0):
                                    while ($row = $result_sk->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($row['tgl_surat'])); ?></td>
                                    <td><small><?php echo escape_html($row['no_surat']); ?></small></td>
                                    <td><?php echo escape_html($row['kepada']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary badge-kategori">
                                            <?php echo escape_html($row['nama_kategori']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        <i>Belum ada data surat keluar</i>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Memo Row -->
    <div class="row">
        
        <!-- Memo Masuk Terbaru -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-inbox"></i> Memo Masuk Terbaru
                    </h6>
                    <a href="memo_masuk.php" class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i> Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-dashboard">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>No. Memo</th>
                                    <th>Dari</th>
                                    <th>Kategori</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($result_mm->num_rows > 0):
                                    while ($row = $result_mm->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($row['tgl_terima'])); ?></td>
                                    <td><small><?php echo escape_html($row['no_memo']); ?></small></td>
                                    <td><?php echo escape_html($row['dari']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary badge-kategori">
                                            <?php echo escape_html($row['nama_kategori']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        <i>Belum ada data memo masuk</i>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Memo Keluar Terbaru -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-file-export"></i> Memo Keluar Terbaru
                    </h6>
                    <a href="memo_keluar.php" class="btn btn-sm btn-warning">
                        <i class="fas fa-eye"></i> Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-dashboard">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>No. Memo</th>
                                    <th>Kepada</th>
                                    <th>Kategori</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($result_mk->num_rows > 0):
                                    while ($row = $result_mk->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($row['tgl_memo'])); ?></td>
                                    <td><small><?php echo escape_html($row['no_memo']); ?></small></td>
                                    <td><?php echo escape_html($row['kepada']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary badge-kategori">
                                            <?php echo escape_html($row['nama_kategori']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        <i>Belum ada data memo keluar</i>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<?php
$stmt_sm->close();
$stmt_sk->close();
$stmt_mm->close();
$stmt_mk->close();

include 'template/footer.php';
?>