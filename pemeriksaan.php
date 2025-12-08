<?php
require_once 'koneksi.php';
cek_login();

// [FIX 1] Definisikan role_user dari session untuk mencegah error undefined variable
$role_user = $_SESSION['role'] ?? ''; 
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

// Handler POST (Create, Update, Delete)
if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_admin) {
    
    // Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> Permintaan tidak valid (CSRF Token mismatch).
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
    } else {
        $action_type = $_POST['action_type'] ?? '';

        // [FIX 5] Logic DELETE dipindah ke POST
        if ($action_type == 'delete') {
            $id_pemeriksaan_del = (int)($_POST['id'] ?? 0);
            $stmt_delete = $koneksi->prepare("DELETE FROM pemeriksaan WHERE id_pemeriksaan = ?");
            $stmt_delete->bind_param("i", $id_pemeriksaan_del);
            
            if ($stmt_delete->execute()) {
                header('Location: pemeriksaan.php?status=sukses_hapus');
                exit;
            } else {
                $message = '<div class="alert alert-danger">Gagal menghapus data: ' . $stmt_delete->error . '</div>';
            }
            $stmt_delete->close();
        }
        
        // Logic Create & Update
        elseif ($action_type == 'create' || $action_type == 'update') {
            $id_peserta = (int)($_POST['id_peserta'] ?? 0);
            $tanggal_periksa = sanitize_data_for_bind($_POST['tanggal_periksa'] ?? '');
            $tensi = sanitize_data_for_bind($_POST['tensi'] ?? '');
            $gdp = sanitize_data_for_bind($_POST['gdp'] ?? '');
            $asam_urat = sanitize_data_for_bind($_POST['asam_urat'] ?? '');
            $kolesterol = sanitize_data_for_bind($_POST['kolesterol'] ?? '');

            if ($action_type == 'create') {
                // [FIX 3] Tipe data diperbaiki: Tensi string ('s'), angka desimal aman pakai 's' atau 'd'
                // isssss -> int, string, string, string, string, string (aman untuk semua tipe input form)
                $query = "INSERT INTO pemeriksaan (id_peserta, tanggal_periksa, tensi, gdp, asam_urat, kolesterol) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $koneksi->prepare($query);
                $stmt->bind_param("isssss", $id_peserta, $tanggal_periksa, $tensi, $gdp, $asam_urat, $kolesterol);
                
                if ($stmt->execute()) {
                    header('Location: pemeriksaan.php?status=sukses_tambah');
                    exit;
                } else {
                    $message = '<div class="alert alert-danger">Gagal menambahkan data: ' . $stmt->error . '</div>';
                }
                $stmt->close();
            }

            if ($action_type == 'update') {
                // [FIX 2] Ambil id_pemeriksaan dari POST
                $id_pemeriksaan_upd = (int)($_POST['id_pemeriksaan'] ?? 0);

                $query = "UPDATE pemeriksaan SET id_peserta=?, tanggal_periksa=?, tensi=?, gdp=?, asam_urat=?, kolesterol=? WHERE id_pemeriksaan=?";
                $stmt = $koneksi->prepare($query);
                // [FIX 3] Tipe data bind diperbaiki: isssssi
                $stmt->bind_param("isssssi", $id_peserta, $tanggal_periksa, $tensi, $gdp, $asam_urat, $kolesterol, $id_pemeriksaan_upd);
                
                if ($stmt->execute()) {
                    header('Location: pemeriksaan.php?status=sukses_edit');
                    exit;
                } else {
                    $message = '<div class="alert alert-danger">Gagal memperbarui data: ' . $stmt->error . '</div>';
                }
                $stmt->close();
            }
        }
    }
}

include 'template/header.php'; 
echo '
<style>
.data-table thead th {
    font-size: 16px !important; 
    font-weight: bold;
    vertical-align: middle;
}
/* Style untuk form delete inline */
.form-delete {
    display: inline-block;
    margin: 0;
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
        $alert_type = 'success';
        $msg_text = '';
        if ($_GET['status'] == 'sukses_tambah') $msg_text = 'Data pemeriksaan berhasil ditambahkan!';
        elseif ($_GET['status'] == 'sukses_edit') $msg_text = 'Data pemeriksaan berhasil diperbarui!';
        elseif ($_GET['status'] == 'sukses_hapus') $msg_text = 'Data pemeriksaan berhasil dihapus!';
        
        if ($msg_text) {
            echo '<div class="alert alert-'.$alert_type.' alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> '.$msg_text.'
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        }
    }
    echo $message;
    ?>
    
    <?php 
    // FORM CREATE / EDIT
    if (($action == 'create' && $is_admin) || ($action == 'edit' && $is_admin && $id_pemeriksaan > 0)): 
        $data = [
            'id_pemeriksaan' => 0, 'id_peserta' => '', 'tanggal_periksa' => date('Y-m-d'), 
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
                echo '<div class="alert alert-danger">Data tidak ditemukan.</div>';
                // Fallback agar tidak lanjut menampilkan form kosong
                $action = 'read';
            }
            $stmt_edit->close();
        }
        
        $peserta_result = $koneksi->query("SELECT id_peserta, nama, bidang FROM peserta ORDER BY nama ASC");

        if ($action != 'read'): // Pastikan form hanya muncul jika valid
    ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-<?php echo $action_type == 'create' ? 'plus-circle' : 'edit'; ?>"></i> 
                <?php echo $form_title; ?>
            </h6>
        </div>
        <div class="card-body">
            <form action="pemeriksaan.php" method="POST">
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
                                    <?php echo htmlspecialchars($p['nama']) . ' - ' . htmlspecialchars($p['bidang']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Tanggal Pemeriksaan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_periksa" class="form-control" 
                               value="<?php echo htmlspecialchars($data['tanggal_periksa']); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Tensi (mmHg)</label>
                        <input type="text" name="tensi" class="form-control" 
                               placeholder="Contoh: 120/80" 
                               value="<?php echo htmlspecialchars($data['tensi']); ?>">
                        <small class="text-muted">Format: sistol/diastol</small>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">GDP (mg/dL)</label>
                        <input type="number" step="0.01" name="gdp" class="form-control" 
                               placeholder="Contoh: 95.5" 
                               value="<?php echo htmlspecialchars($data['gdp']); ?>">
                        <small class="text-muted">Gula Darah Puasa</small>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Asam Urat (mg/dL)</label>
                        <input type="number" step="0.01" name="asam_urat" class="form-control" 
                               placeholder="Contoh: 6.5" 
                               value="<?php echo htmlspecialchars($data['asam_urat']); ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Kolesterol (mg/dL)</label>
                        <input type="number" step="0.01" name="kolesterol" class="form-control" 
                               placeholder="Contoh: 180" 
                               value="<?php echo htmlspecialchars($data['kolesterol']); ?>">
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
    // VIEW TABLE
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
        }
        
        $stmt_read = $koneksi->prepare($query_read);
        
        // [FIX 4] Menggunakan spread operator (...) menggantikan call_user_func_array
        if (!empty($bind_params)) {
             $stmt_read->bind_param($bind_types, ...$bind_params);
        }
        
        $stmt_read->execute();
        $result_read = $stmt_read->get_result();
        
        $peserta_list = $koneksi->query("SELECT id_peserta, nama FROM peserta ORDER BY nama ASC");
        $bulan_list = $koneksi->query("SELECT DISTINCT DATE_FORMAT(tanggal_periksa, '%Y-%m') as bulan FROM pemeriksaan ORDER BY bulan DESC");
    ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter"></i> Filter dan Pencarian
            </h6>
        </div>
        <div class="card-body">
            <form action="pemeriksaan.php" method="GET">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Filter Bulan</label>
                        <select name="filter_bulan" class="form-select" required onchange="this.form.submit()">
                            <?php 
                            $bulan_list->data_seek(0);
                            while ($bln = $bulan_list->fetch_assoc()): 
                                $bulan_value = $bln['bulan'];
                                $bulan_text = date('F Y', strtotime($bulan_value . '-01'));
                            ?>
                                <option value="<?php echo $bulan_value; ?>" <?php echo ($filter_bulan == $bulan_value) ? 'selected' : ''; ?>>
                                    <?php echo $bulan_text; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Cari Nama/Bidang</label>
                        <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Peserta</label>
                        <select name="filter_peserta" class="form-select">
                            <option value="">-- Semua Peserta --</option>
                            <?php 
                            $peserta_list->data_seek(0);
                            while ($ps = $peserta_list->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $ps['id_peserta']; ?>" <?php echo ($filter_peserta == $ps['id_peserta']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ps['nama']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2"><i class="fas fa-search"></i> Cari</button>
                        <a href="pemeriksaan.php" class="btn btn-secondary"><i class="fas fa-sync-alt"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Daftar Pemeriksaan - <?php echo date('F Y', strtotime($filter_bulan . '-01')); ?>
            </h6>
            <div>
                 <form action="cetak_pemeriksaan.php" method="POST" target="_blank" style="display: inline-block;">
                    <input type="hidden" name="bulan" value="<?php echo $filter_bulan; ?>">
                    <input type="hidden" name="peserta" value="<?php echo $filter_peserta; ?>">
                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-print"></i> Cetak</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover data-table" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">NO</th>
                            <th>Tanggal</th>
                            <th>Nama Peserta</th>
                            <th class="text-center">L/P</th>
                            <th>Bidang</th>
                            <th class="text-center">Tensi</th>
                            <th class="text-center">GDP</th>
                            <th class="text-center">A. Urat</th>
                            <th class="text-center">Kolesterol</th>
                            <?php if ($is_admin): ?>
                                <th width="10%" class="text-center">Aksi</th>
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
                            <td><?php echo htmlspecialchars($row['nama']); ?></td>
                            <td class="text-center"><?php echo $row['gender']; ?></td>
                            <td><?php echo htmlspecialchars($row['bidang']); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($row['tensi']); ?></td>
                            <td class="text-center"><?php echo $row['gdp']; ?></td>
                            <td class="text-center"><?php echo $row['asam_urat']; ?></td>
                            <td class="text-center"><?php echo $row['kolesterol']; ?></td>
                            
                            <?php if ($is_admin): ?>
                                <td class="text-center">
                                    <a href="pemeriksaan.php?action=edit&id=<?php echo $row['id_pemeriksaan']; ?>" 
                                       class="btn btn-warning btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <form action="pemeriksaan.php" method="POST" class="form-delete" 
                                          onsubmit="return confirm('Yakin ingin menghapus data ini?');">
                                        <input type="hidden" name="action_type" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $row['id_pemeriksaan']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="10" class="text-center">Tidak ada data.</td></tr>
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

<?php include 'template/footer.php'; ?>