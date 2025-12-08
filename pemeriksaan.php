<?php
require_once 'koneksi.php';
cek_login();

$is_admin = ($role_user === 'admin');
$message = ''; 
$action = $_GET['action'] ?? 'read';
$id_pemeriksaan = (int)($_GET['id'] ?? 0);

$search = $_GET['search'] ?? '';
$filter_peserta = $_GET['filter_peserta'] ?? '';
$filter_bulan = $_GET['filter_bulan'] ?? date('Y-m');
$sort_by = $_GET['sort_by'] ?? 'tanggal_periksa';
$sort_order = $_GET['sort_order'] ?? 'DESC';

$search = htmlspecialchars($search);
$filter_peserta = (int)$filter_peserta;
$filter_bulan = htmlspecialchars($filter_bulan);
$sort_by = htmlspecialchars($sort_by);
$sort_order = htmlspecialchars(strtoupper($sort_order)); 

function sanitize_data_for_bind($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_admin) {
    $id_peserta = (int)($_POST['id_peserta'] ?? 0);
    $tanggal_periksa = sanitize_data_for_bind($_POST['tanggal_periksa'] ?? '');
    $tensi = sanitize_data_for_bind($_POST['tensi'] ?? '');
    $gdp = sanitize_data_for_bind($_POST['gdp'] ?? '');
    $asam_urat = sanitize_data_for_bind($_POST['asam_urat'] ?? '');
    $kolesterol = sanitize_data_for_bind($_POST['kolesterol'] ?? '');

    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> Permintaan tidak valid (CSRF Token mismatch).
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        $action = 'read'; 
    } else {
        if ($_POST['action_type'] == 'create') {
            $query = "INSERT INTO pemeriksaan (id_peserta, tanggal_periksa, tensi, gdp, asam_urat, kolesterol) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $koneksi->prepare($query);
            $stmt->bind_param("issddd", $id_peserta, $tanggal_periksa, $tensi, $gdp, $asam_urat, $kolesterol);
            
            if ($stmt->execute()) {
                header('Location: pemeriksaan.php?status=sukses_tambah');
                exit;
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-times-circle"></i> Gagal menambahkan data: ' . $stmt->error . '
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
            }
            $stmt->close();
            $action = 'read';
        }

        if ($_POST['action_type'] == 'update') {
            $query = "UPDATE pemeriksaan SET id_peserta=?, tanggal_periksa=?, tensi=?, gdp=?, asam_urat=?, kolesterol=? WHERE id_pemeriksaan=?";
            $stmt = $koneksi->prepare($query);
            $stmt->bind_param("issdddi", $id_peserta, $tanggal_periksa, $tensi, $gdp, $asam_urat, $kolesterol, $id_pemeriksaan);
            
            if ($stmt->execute()) {
                header('Location: pemeriksaan.php?status=sukses_edit');
                exit;
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-times-circle"></i> Gagal memperbarui data: ' . $stmt->error . '
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
            }
            $stmt->close();
            $action = 'read';
        }
    }
}

if ($action == 'delete' && $is_admin) {
    if (isset($_GET['id']) && isset($_GET['csrf_token']) && validate_csrf_token($_GET['csrf_token'])) {
        $id_pemeriksaan_del = (int)$_GET['id'];
        
        $stmt_delete = $koneksi->prepare("DELETE FROM pemeriksaan WHERE id_pemeriksaan = ?");
        $stmt_delete->bind_param("i", $id_pemeriksaan_del);
        
        if ($stmt_delete->execute()) {
            header('Location: pemeriksaan.php?status=sukses_hapus');
            exit;
        } else {
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-times-circle"></i> Gagal menghapus data: ' . $stmt_delete->error . '
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        }
        $stmt_delete->close();
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-times-circle"></i> Invalid ID atau CSRF Token.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
    }
    $action = 'read'; 
}

include 'template/header.php'; 
echo '
<style>
.data-table thead th {
    font-size: 16px !important; 
    font-weight: bold;
    vertical-align: middle;
}
</style>
';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-stethoscope"></i> Data Pemeriksaan Kesehatan
        </h1>
        <?php if ($action == 'read' && $is_admin): ?>
            <a href="pemeriksaan.php?action=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Pemeriksaan
            </a>
        <?php endif; ?>
    </div>
    
    <?php 
    if (isset($_GET['status'])) {
        if ($_GET['status'] == 'sukses_tambah') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Data pemeriksaan berhasil ditambahkan!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } elseif ($_GET['status'] == 'sukses_edit') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Data pemeriksaan berhasil diperbarui!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } elseif ($_GET['status'] == 'sukses_hapus') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Data pemeriksaan berhasil dihapus!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        }
    }
    
    echo $message;
    ?>
    
    <?php 
    if (($action == 'create' && $is_admin) || ($action == 'edit' && $is_admin && $id_pemeriksaan > 0)): 
        $data = [
            'id_pemeriksaan' => 0, 'id_peserta' => '', 'tanggal_periksa' => '', 
            'tensi' => '', 'gdp' => '', 'asam_urat' => '', 'kolesterol' => ''
        ];
        $form_title = "Tambah Data Pemeriksaan";
        $action_type = "create";
        
        if ($action == 'edit') {
            $stmt_edit = $koneksi->prepare("SELECT * FROM pemeriksaan WHERE id_pemeriksaan = ?");
            $stmt_edit->bind_param("i", $id_pemeriksaan);
            $stmt_edit->execute();
            $result_edit = $stmt_edit->get_result();
            
            if ($result_edit->num_rows == 1) {
                $data = $result_edit->fetch_assoc();
                $form_title = "Edit Data Pemeriksaan (ID: " . $id_pemeriksaan . ")";
                $action_type = "update";
            } else {
                $message = '<div class="alert alert-danger">Data tidak ditemukan.</div>';
                $action = 'read';
            }
            $stmt_edit->close();
        }
        
        $peserta_result = $koneksi->query("SELECT id_peserta, nama, bidang FROM peserta ORDER BY nama ASC");

        if ($action != 'read'): 
    ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-<?php echo $action_type == 'create' ? 'plus-circle' : 'edit'; ?>"></i> 
                <?php echo $form_title; ?>
            </h6>
        </div>
        <div class="card-body">
            <form action="pemeriksaan.php?action=read&id=<?php echo $data['id_pemeriksaan']; ?>" method="POST">
                <input type="hidden" name="action_type" value="<?php echo $action_type; ?>">
                <?php if ($action_type == 'update'): ?>
                    <input type="hidden" name="id_pemeriksaan" value="<?php echo $data['id_pemeriksaan']; ?>">
                <?php endif; ?>
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Nama Peserta <span class="text-danger">*</span></label>
                        <select name="id_peserta" class="form-select" required>
                            <option value="">-- Pilih Peserta --</option>
                            <?php while ($p = $peserta_result->fetch_assoc()): ?>
                                <option value="<?php echo $p['id_peserta']; ?>" 
                                    <?php echo ($p['id_peserta'] == $data['id_peserta']) ? 'selected' : ''; ?>>
                                    <?php echo escape_html($p['nama']) . ' - ' . escape_html($p['bidang']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Tanggal Pemeriksaan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_periksa" class="form-control" 
                               value="<?php echo escape_html($data['tanggal_periksa']); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Tensi (mmHg)</label>
                        <input type="text" name="tensi" class="form-control" 
                               placeholder="Contoh: 120/80" 
                               value="<?php echo escape_html($data['tensi']); ?>">
                        <small class="text-muted">Format: sistol/diastol</small>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">GDP (mg/dL)</label>
                        <input type="number" step="0.01" name="gdp" class="form-control" 
                               placeholder="Contoh: 95.5" 
                               value="<?php echo escape_html($data['gdp']); ?>">
                        <small class="text-muted">Gula Darah Puasa</small>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Asam Urat (mg/dL)</label>
                        <input type="number" step="0.01" name="asam_urat" class="form-control" 
                               placeholder="Contoh: 6.5" 
                               value="<?php echo escape_html($data['asam_urat']); ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Kolesterol (mg/dL)</label>
                        <input type="number" step="0.01" name="kolesterol" class="form-control" 
                               placeholder="Contoh: 180" 
                               value="<?php echo escape_html($data['kolesterol']); ?>">
                        <small class="text-muted">Kolesterol Total</small>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <strong><i class="fas fa-info-circle"></i> Nilai Normal:</strong>
                    <ul class="mb-0 mt-2">
                        <li><strong>Tensi:</strong> 120/80 mmHg (normal), >140/90 (hipertensi)</li>
                        <li><strong>GDP:</strong> 70-100 mg/dL (normal), >126 (diabetes)</li>
                        <li><strong>Asam Urat:</strong> Pria: 3.4-7.0 mg/dL, Wanita: 2.4-6.0 mg/dL</li>
                        <li><strong>Kolesterol:</strong> <200 mg/dL (baik), 200-239 (batas), ≥240 (tinggi)</li>
                    </ul>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Data
                    </button>
                    <a href="pemeriksaan.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php 
        endif; 
    endif; 
    ?>
    
    <?php 
    if ($action == 'read'): 
        $query_read = "SELECT p.*, ps.nama, ps.gender, ps.bidang 
                       FROM pemeriksaan p 
                       JOIN peserta ps ON p.id_peserta = ps.id_peserta 
                       WHERE DATE_FORMAT(p.tanggal_periksa, '%Y-%m') = ?";
        $bind_types = 's';
        $bind_params = [&$filter_bulan];
    
        if (!empty($search)) {
            $search_param = '%' . $search . '%';
            $query_read .= " AND (ps.nama LIKE ? OR ps.bidang LIKE ?)";
            $bind_types .= 'ss';
            $bind_params[] = &$search_param;
            $bind_params[] = &$search_param;
        }
        
        if (!empty($filter_peserta)) {
            $query_read .= " AND p.id_peserta = ?";
            $bind_types .= 'i';
            $bind_params[] = &$filter_peserta;
        }
        
        $allowed_sort = ['tanggal_periksa', 'nama', 'tensi', 'gdp', 'asam_urat', 'kolesterol'];
        $allowed_order = ['ASC', 'DESC'];
        
        if (in_array($sort_by, $allowed_sort) && in_array($sort_order, $allowed_order)) {
            $query_read .= " ORDER BY " . $sort_by . " " . $sort_order;
        } else {
            $query_read .= " ORDER BY p.tanggal_periksa DESC";
            $sort_by = 'tanggal_periksa';
            $sort_order = 'DESC';
        }
        
        $stmt_read = $koneksi->prepare($query_read);
        array_unshift($bind_params, $bind_types);
        call_user_func_array(array($stmt_read, 'bind_param'), $bind_params);
        
        $stmt_read->execute();
        $result_read = $stmt_read->get_result();
        
        $peserta_list = $koneksi->query("SELECT id_peserta, nama FROM peserta ORDER BY nama ASC");
        
        // Ambil daftar bulan yang tersedia
        $bulan_list = $koneksi->query("SELECT DISTINCT DATE_FORMAT(tanggal_periksa, '%Y-%m') as bulan 
                                       FROM pemeriksaan 
                                       ORDER BY bulan DESC");
    ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter"></i> Filter dan Pencarian
            </h6>
        </div>
        <div class="card-body">
            <form action="pemeriksaan.php" method="GET">
                <input type="hidden" name="sort_by" value="<?php echo escape_html($sort_by); ?>">
                <input type="hidden" name="sort_order" value="<?php echo escape_html($sort_order); ?>">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label class="form-label"><i class="fas fa-calendar-alt"></i> Filter Bulan <span class="text-danger">*</span></label>
                        <select name="filter_bulan" class="form-select" required onchange="this.form.submit()">
                            <?php 
                            $bulan_list->data_seek(0);
                            while ($bln = $bulan_list->fetch_assoc()): 
                                $bulan_value = $bln['bulan'];
                                $bulan_text = date('F Y', strtotime($bulan_value . '-01'));
                            ?>
                                <option value="<?php echo $bulan_value; ?>" 
                                    <?php echo ($filter_bulan == $bulan_value) ? 'selected' : ''; ?>>
                                    <?php echo $bulan_text; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label"><i class="fas fa-search"></i> Cari Nama/Bidang</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Nama atau Bidang..." 
                               value="<?php echo escape_html($search); ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label"><i class="fas fa-user"></i> Peserta</label>
                        <select name="filter_peserta" class="form-select">
                            <option value="">-- Semua Peserta --</option>
                            <?php 
                            $peserta_list->data_seek(0);
                            while ($ps = $peserta_list->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $ps['id_peserta']; ?>" 
                                    <?php echo ($filter_peserta == $ps['id_peserta']) ? 'selected' : ''; ?>>
                                    <?php echo escape_html($ps['nama']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <div>
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <?php if (!empty($search) || !empty($filter_peserta)): ?>
                                <a href="pemeriksaan.php?filter_bulan=<?php echo $filter_bulan; ?>" class="btn btn-secondary" title="Reset Filter">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Daftar Pemeriksaan Kesehatan - <?php echo date('F Y', strtotime($filter_bulan . '-01')); ?>
                <?php if (!$is_admin): ?>
                <span class="badge bg-info text-white ms-2">Mode: Read Only</span>
                <?php endif; ?>
            </h6>
            <div>
                <form action="cetak_pemeriksaan.php" method="POST" target="_blank" style="display: inline-block;">
                    <input type="hidden" name="bulan" value="<?php echo $filter_bulan; ?>">
                    <input type="hidden" name="peserta" value="<?php echo $filter_peserta; ?>">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-print"></i> Cetak / PDF
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover data-table" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">NO</th>
                            <th width="12%">Tanggal</th>
                            <th width="18%">Nama Peserta</th>
                            <th width="10%" class="text-center">Gender</th>
                            <th width="15%">Bidang</th>
                            <th width="10%" class="text-center">Tensi</th>
                            <th width="8%" class="text-center">GDP</th>
                            <th width="8%" class="text-center">A. Urat</th>
                            <th width="8%" class="text-center">Kolesterol</th>
                            <?php if ($is_admin): ?>
                                <th width="6%" class="text-center">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result_read->num_rows > 0):
                            $no = 1; 
                            while ($row = $result_read->fetch_assoc()): 
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $no++; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($row['tanggal_periksa'])); ?></td>
                            <td><?php echo escape_html($row['nama']); ?></td>
                            <td class="text-center">
                                <?php if ($row['gender'] == 'L'): ?>
                                    <span class="badge bg-primary">L</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">P</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo escape_html($row['bidang']); ?></td>
                            <td class="text-center">
                                <?php if (!empty($row['tensi'])): ?>
                                    <?php 
                                    $tensi_parts = explode('/', $row['tensi']);
                                    $sistol = isset($tensi_parts[0]) ? (int)$tensi_parts[0] : 0;
                                    $diastol = isset($tensi_parts[1]) ? (int)$tensi_parts[1] : 0;
                                    $tensi_class = 'text-success';
                                    if ($sistol >= 140 || $diastol >= 90) {
                                        $tensi_class = 'text-danger';
                                    } elseif ($sistol >= 130 || $diastol >= 85) {
                                        $tensi_class = 'text-warning';
                                    }
                                    ?>
                                    <span class="<?php echo $tensi_class; ?>">
                                        <?php echo escape_html($row['tensi']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($row['gdp'] > 0): ?>
                                    <?php 
                                    $gdp_class = 'text-success';
                                    if ($row['gdp'] >= 126) {
                                        $gdp_class = 'text-danger';
                                    } elseif ($row['gdp'] >= 100) {
                                        $gdp_class = 'text-warning';
                                    }
                                    ?>
                                    <span class="<?php echo $gdp_class; ?>">
                                        <?php echo number_format($row['gdp'], 1); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($row['asam_urat'] > 0): ?>
                                    <?php 
                                    $au_class = 'text-success';
                                    $batas = ($row['gender'] == 'L') ? 7.0 : 6.0;
                                    if ($row['asam_urat'] > $batas) {
                                        $au_class = 'text-danger';
                                    } elseif ($row['asam_urat'] > ($batas - 0.5)) {
                                        $au_class = 'text-warning';
                                    }
                                    ?>
                                    <span class="<?php echo $au_class; ?>">
                                        <?php echo number_format($row['asam_urat'], 1); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($row['kolesterol'] > 0): ?>
                                    <?php 
                                    $kol_class = 'text-success';
                                    if ($row['kolesterol'] >= 240) {
                                        $kol_class = 'text-danger';
                                    } elseif ($row['kolesterol'] >= 200) {
                                        $kol_class = 'text-warning';
                                    }
                                    ?>
                                    <span class="<?php echo $kol_class; ?>">
                                        <?php echo number_format($row['kolesterol'], 0); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($is_admin): ?>
                                <td class="text-center">
                                    <a href="pemeriksaan.php?action=edit&id=<?php echo $row['id_pemeriksaan']; ?>" 
                                       class="btn btn-warning btn-sm" title="Edit Data">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="pemeriksaan.php?action=delete&id=<?php echo $row['id_pemeriksaan']; ?>&csrf_token=<?php echo generate_csrf_token(); ?>" 
                                       class="btn btn-danger btn-sm" title="Hapus Data" 
                                       onclick="return confirm('Yakin ingin menghapus data pemeriksaan ini?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php 
                            endwhile; 
                        else:
                        ?>
                        <tr>
                            <td colspan="<?php echo $is_admin ? '10' : '9'; ?>" class="text-center">
                                Tidak ada data pemeriksaan pada bulan <?php echo date('F Y', strtotime($filter_bulan . '-01')); ?>.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
    <?php 
        $stmt_read->close();
    endif; 
    ?>
</div>

<?php
include 'template/footer.php'; 
?>