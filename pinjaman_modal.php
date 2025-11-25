<?php
require_once 'koneksi.php';
cek_login();

$is_admin = ($role_user === 'admin');
$message = ''; 
$action = $_GET['action'] ?? 'read';
$id_pinjaman = (int)($_GET['id'] ?? 0);

$search = $_GET['search'] ?? '';
$filter_kategori = $_GET['filter_kategori'] ?? '';
$search_tgl_dari = $_GET['search_tgl_dari'] ?? '';
$search_tgl_sampai = $_GET['search_tgl_sampai'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'tanggal_pinjaman';
$sort_order = $_GET['sort_order'] ?? 'DESC';

$search = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
$filter_kategori = (int)$filter_kategori;
$search_tgl_dari = htmlspecialchars($search_tgl_dari, ENT_QUOTES, 'UTF-8');
$search_tgl_sampai = htmlspecialchars($search_tgl_sampai, ENT_QUOTES, 'UTF-8');
$sort_by = htmlspecialchars($sort_by, ENT_QUOTES, 'UTF-8');
$sort_order = htmlspecialchars(strtoupper($sort_order), ENT_QUOTES, 'UTF-8'); 

function sanitize_data_for_bind($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_admin) {

    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $nomer_kontrak = sanitize_data_for_bind($_POST['nomer_kontrak'] ?? '');
    $dari = sanitize_data_for_bind($_POST['dari'] ?? '');
    $nominal_pinjaman = sanitize_data_for_bind($_POST['nominal_pinjaman'] ?? '');
    $tanggal_pinjaman = sanitize_data_for_bind($_POST['tanggal_pinjaman'] ?? '');
    $berkas_lama = sanitize_data_for_bind($_POST['berkas_lama'] ?? ''); 
    $berkas_nama = $berkas_lama;
    $upload_dir = 'uploads/pinjaman_modal/';

    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> Permintaan tidak valid (CSRF Token mismatch).
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        $action = 'read'; 
    } else {
        
        $nominal_clean = preg_replace('/[^0-9.]/', '', $nominal_pinjaman);
        if (!is_numeric($nominal_clean) || $nominal_clean <= 0) {
            $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> Nominal pinjaman harus berupa angka positif yang valid.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
            $action = $_POST['action_type'] === 'update' ? 'edit' : 'create';
        } else {
            
            $upload_error_flag = false;
            if (isset($_FILES['berkas']) && $_FILES['berkas']['error'] == 0) {
                $allowed_mime = 'application/pdf';
                $uploaded_mime = $_FILES['berkas']['type'];

                if ($uploaded_mime !== $allowed_mime) {
                    $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i> Gagal mengunggah. Berkas harus berformat PDF.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>';
                    $action = $_POST['action_type'] === 'update' ? 'edit' : 'create'; 
                    $upload_error_flag = true;
                } else {
                    $file_tmp = $_FILES['berkas']['tmp_name'];
                    $file_name = basename($_FILES['berkas']['name']);
                    $berkas_nama_new = time() . '_' . uniqid() . '_' . $file_name;
                    
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    if (move_uploaded_file($file_tmp, $upload_dir . $berkas_nama_new)) {
                        if (!empty($berkas_lama) && file_exists($upload_dir . $berkas_lama)) {
                            unlink($upload_dir . $berkas_lama);
                        }
                        $berkas_nama = $berkas_nama_new;
                    } else {
                        $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-triangle"></i> Gagal memindahkan berkas. Data mungkin tersimpan tanpa file baru.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>';
                    }
                }
            } else if ($_POST['action_type'] === 'update' && isset($_POST['hapus_berkas']) && $_POST['hapus_berkas'] == 1) {
                
                if (!empty($berkas_lama) && file_exists($upload_dir . $berkas_lama)) {
                    unlink($upload_dir . $berkas_lama);
                }
                $berkas_nama = null;
            }

            if (!$upload_error_flag) {
                
                if ($_POST['action_type'] == 'create') {
                    $check_stmt = $koneksi->prepare("SELECT nomer_kontrak FROM tb_pinjaman_modal WHERE nomer_kontrak = ?");
                    $check_stmt->bind_param("s", $nomer_kontrak);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-times-circle"></i> Nomor kontrak sudah digunakan. Gunakan nomor kontrak lain.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>';
                        $action = 'create';
                    } else {
                        $query = "INSERT INTO tb_pinjaman_modal (id_kategori, nomer_kontrak, dari, nominal_pinjaman, tanggal_pinjaman, berkas) 
                                  VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $koneksi->prepare($query);
                        $stmt->bind_param("issdss", $id_kategori, $nomer_kontrak, $dari, $nominal_clean, $tanggal_pinjaman, $berkas_nama);
                        
                        if ($stmt->execute()) {
                            header('Location: pinjaman_modal.php?status=sukses_tambah');
                            exit;
                        } else {
                            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="fas fa-times-circle"></i> Gagal menambahkan data: ' . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8') . '
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>';
                        }
                        $stmt->close();
                    }
                    $check_stmt->close();
                    $action = 'read';
                }

                if ($_POST['action_type'] == 'update') {
                    $query = "UPDATE tb_pinjaman_modal SET id_kategori=?, nomer_kontrak=?, dari=?, nominal_pinjaman=?, tanggal_pinjaman=?, berkas=? 
                              WHERE id_pinjaman=?";
                    $stmt = $koneksi->prepare($query);
                    $stmt->bind_param("issdssi", $id_kategori, $nomer_kontrak, $dari, $nominal_clean, $tanggal_pinjaman, $berkas_nama, $id_pinjaman);
                    
                    if ($stmt->execute()) {
                        header('Location: pinjaman_modal.php?status=sukses_edit');
                        exit;
                    } else {
                        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-times-circle"></i> Gagal memperbarui data: ' . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8') . '
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>';
                    }
                    $stmt->close();
                    $action = 'read';
                }
            }
        }
    }
}

if ($action == 'delete' && $is_admin) {
    if (isset($_GET['id']) && isset($_GET['csrf_token']) && validate_csrf_token($_GET['csrf_token'])) {
        $id_pinjaman_del = (int)$_GET['id'];
        
        $stmt_file = $koneksi->prepare("SELECT berkas FROM tb_pinjaman_modal WHERE id_pinjaman = ?");
        $stmt_file->bind_param("i", $id_pinjaman_del);
        $stmt_file->execute();
        $result_file = $stmt_file->get_result();
        
        if ($result_file->num_rows > 0) {
            $row_file = $result_file->fetch_assoc();
            $file_to_delete = 'uploads/pinjaman_modal/' . $row_file['berkas']; 
            
            $stmt_delete = $koneksi->prepare("DELETE FROM tb_pinjaman_modal WHERE id_pinjaman = ?");
            $stmt_delete->bind_param("i", $id_pinjaman_del);
            
            if ($stmt_delete->execute()) {
                if (!empty($row_file['berkas']) && file_exists($file_to_delete)) {
                    unlink($file_to_delete);
                }
                header('Location: pinjaman_modal.php?status=sukses_hapus');
                exit;
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-times-circle"></i> Gagal menghapus data: ' . htmlspecialchars($stmt_delete->error, ENT_QUOTES, 'UTF-8') . '
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
            }
            $stmt_delete->close();
        }
        $stmt_file->close();
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
.nominal-besar {
    font-weight: bold;
    color: #d9534f;
}
</style>
';

?>

<div class="container-fluid">
    
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-money-bill-wave"></i> Data Pinjaman Modal
        </h1>
        <?php if ($action == 'read' && $is_admin): ?>
            <a href="pinjaman_modal.php?action=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Pinjaman Modal
            </a>
        <?php endif; ?>
    </div>
    
    <?php 
    if (isset($_GET['status'])) {
        if ($_GET['status'] == 'sukses_tambah') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Data Pinjaman Modal baru berhasil ditambahkan!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } elseif ($_GET['status'] == 'sukses_edit') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Data Pinjaman Modal berhasil diperbarui!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } elseif ($_GET['status'] == 'sukses_hapus') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Data Pinjaman Modal berhasil dihapus!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        }
    }
    
    echo $message;
    ?>
    
    <?php 
    if (($action == 'create' && $is_admin) || ($action == 'edit' && $is_admin && $id_pinjaman > 0)): 
        
        $data = [
            'id_pinjaman' => 0, 'id_kategori' => '', 'nomer_kontrak' => '', 'dari' => '', 
            'nominal_pinjaman' => '', 'tanggal_pinjaman' => '', 'berkas' => ''
        ];
        $form_title = "Tambah Pinjaman Modal";
        $action_type = "create";
        
        if ($action == 'edit') {
            $stmt_edit = $koneksi->prepare("SELECT * FROM tb_pinjaman_modal WHERE id_pinjaman = ?");
            $stmt_edit->bind_param("i", $id_pinjaman);
            $stmt_edit->execute();
            $result_edit = $stmt_edit->get_result();
            
            if ($result_edit->num_rows == 1) {
                $data = $result_edit->fetch_assoc();
                $form_title = "Edit Pinjaman Modal (ID: " . $id_pinjaman . ")";
                $action_type = "update";
            } else {
                $message = '<div class="alert alert-danger">Data tidak ditemukan.</div>';
                $action = 'read';
            }
            $stmt_edit->close();
        }
        
        $kategori_result = $koneksi->query("SELECT id_kategori, nama_kategori FROM tb_kategori ORDER BY nama_kategori ASC");

        if ($action != 'read'): 
    ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-<?php echo $action_type == 'create' ? 'plus-circle' : 'edit'; ?>"></i> 
                <?php echo htmlspecialchars($form_title, ENT_QUOTES, 'UTF-8'); ?>
            </h6>
        </div>
        <div class="card-body">
            <form action="pinjaman_modal.php?action=read&id=<?php echo $data['id_pinjaman']; ?>" method="POST" enctype="multipart/form-data">
                
                <input type="hidden" name="action_type" value="<?php echo htmlspecialchars($action_type, ENT_QUOTES, 'UTF-8'); ?>">
                <?php if ($action_type == 'update'): ?>
                    <input type="hidden" name="id_pinjaman" value="<?php echo $data['id_pinjaman']; ?>">
                    <input type="hidden" name="berkas_lama" value="<?php echo htmlspecialchars($data['berkas'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
                
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Kategori <span class="text-danger">*</span></label>
                        <select name="id_kategori" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php while ($k = $kategori_result->fetch_assoc()): ?>
                                <option value="<?php echo $k['id_kategori']; ?>" 
                                    <?php echo ($k['id_kategori'] == $data['id_kategori']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($k['nama_kategori'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nomor Kontrak <span class="text-danger">*</span></label>
                        <input type="text" name="nomer_kontrak" class="form-control" 
                               value="<?php echo htmlspecialchars($data['nomer_kontrak'], ENT_QUOTES, 'UTF-8'); ?>" 
                               <?php echo $action_type == 'update' ? 'readonly' : ''; ?> required>
                        <?php if ($action_type == 'create'): ?>
                            <small class="text-muted">Contoh: PM/2025/001</small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Dari (Pemberi Pinjaman) <span class="text-danger">*</span></label>
                        <input type="text" name="dari" class="form-control" 
                               value="<?php echo htmlspecialchars($data['dari'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nominal Pinjaman (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="nominal_pinjaman" class="form-control" 
                               value="<?php echo htmlspecialchars($data['nominal_pinjaman'], ENT_QUOTES, 'UTF-8'); ?>" 
                               step="0.01" min="0" required>
                        <small class="text-muted">Masukkan angka tanpa titik atau koma</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Pinjaman <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_pinjaman" class="form-control" 
                               value="<?php echo htmlspecialchars($data['tanggal_pinjaman'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Unggah Berkas Kontrak (PDF Only)</label>
                    <input type="file" name="berkas" class="form-control" accept=".pdf">
                    <?php if ($action_type == 'update' && !empty($data['berkas'])): ?>
                        <small class="form-text text-muted">
                            Berkas saat ini: <a href="uploads/pinjaman_modal/<?php echo rawurlencode($data['berkas']); ?>" target="_blank"><?php echo htmlspecialchars($data['berkas'], ENT_QUOTES, 'UTF-8'); ?></a>
                        </small><br>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="hapus_berkas" value="1" id="hapus_berkas_check">
                            <label class="form-check-label" for="hapus_berkas_check">
                                Centang untuk Hapus Berkas lama
                            </label>
                        </div>
                    <?php else: ?>
                        <small class="form-text text-muted">Maksimal 5 MB, format PDF</small>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Data
                    </button>
                    <a href="pinjaman_modal.php" class="btn btn-secondary">
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
        
        $query_read = "SELECT pm.*, k.nama_kategori FROM tb_pinjaman_modal pm 
                         JOIN tb_kategori k ON pm.id_kategori = k.id_kategori WHERE 1=1";
                         
        $bind_types = '';
        $bind_params = [];
    
        if (!empty($search)) {
            $search_param = '%' . $search . '%';
            $query_read .= " AND (pm.nomer_kontrak LIKE ? OR pm.dari LIKE ?)";
            $bind_types .= 'ss';
            $bind_params[] = &$search_param;
            $bind_params[] = &$search_param;
        }
        
        if (!empty($search_tgl_dari)) {
            $query_read .= " AND pm.tanggal_pinjaman >= ?";
            $bind_types .= 's';
            $bind_params[] = &$search_tgl_dari;
        }
        
        if (!empty($search_tgl_sampai)) {
            $query_read .= " AND pm.tanggal_pinjaman <= ?";
            $bind_types .= 's';
            $bind_params[] = &$search_tgl_sampai;
        }
        
        if (!empty($filter_kategori)) {
            $query_read .= " AND pm.id_kategori = ?";
            $bind_types .= 'i';
            $bind_params[] = &$filter_kategori;
        }
        
        $allowed_sort = ['tanggal_pinjaman', 'nomer_kontrak', 'dari', 'nominal_pinjaman', 'nama_kategori'];
        $allowed_order = ['ASC', 'DESC'];
        
        if (in_array($sort_by, $allowed_sort) && in_array($sort_order, $allowed_order)) {
            $query_read .= " ORDER BY " . $sort_by . " " . $sort_order;
        } else {
            $query_read .= " ORDER BY pm.tanggal_pinjaman DESC";
            $sort_by = 'tanggal_pinjaman';
            $sort_order = 'DESC';
        }
        
        $stmt_read = $koneksi->prepare($query_read);
        
        if (!empty($bind_types)) {
            array_unshift($bind_params, $bind_types);
            call_user_func_array(array($stmt_read, 'bind_param'), $bind_params);
        }
        
        $stmt_read->execute();
        $result_read = $stmt_read->get_result();
        
        $kategori_list = $koneksi->query("SELECT id_kategori, nama_kategori FROM tb_kategori ORDER BY nama_kategori ASC");
    ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter"></i> Filter dan Pencarian
            </h6>
        </div>
        <div class="card-body">
            <form action="pinjaman_modal.php" method="GET">
                <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sort_by, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="sort_order" value="<?php echo htmlspecialchars($sort_order, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><i class="fas fa-search"></i> Cari</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="No. Kontrak atau Pemberi..." 
                               value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label"><i class="fas fa-calendar"></i> Tgl Dari</label>
                        <input type="date" name="search_tgl_dari" class="form-control" 
                               value="<?php echo htmlspecialchars($search_tgl_dari, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label"><i class="fas fa-calendar-alt"></i> Tgl Sampai</label>
                        <input type="date" name="search_tgl_sampai" class="form-control" 
                               value="<?php echo htmlspecialchars($search_tgl_sampai, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label"><i class="fas fa-filter"></i> Kategori</label>
                        <select name="filter_kategori" class="form-select">
                            <option value="">-- Semua --</option>
                            <?php while ($kat = $kategori_list->fetch_assoc()): ?>
                                <option value="<?php echo $kat['id_kategori']; ?>" 
                                    <?php echo ($filter_kategori == $kat['id_kategori']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kat['nama_kategori'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <?php if (!empty($search) || !empty($filter_kategori) || !empty($search_tgl_dari) || !empty($search_tgl_sampai)): ?>
                                <a href="pinjaman_modal.php" class="btn btn-secondary" title="Reset Filter">
                                    <i class="fas fa-sync-alt"></i> Reset
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Daftar Pinjaman Modal
                <?php if (!$is_admin): ?>
                <span class="badge bg-info text-white ms-2">Mode: Read Only</span>
                <?php endif; ?>
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover data-table" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">NO</th>
                            <th width="12%">No. Kontrak</th>
                            <th width="15%">Dari (Pemberi)</th>
                            <th width="15%">Nominal Pinjaman</th>
                            <th width="12%">Tanggal Pinjaman</th>
                            <th width="10%">Kategori</th>
                            <th width="8%" class="text-center">Berkas</th>
                            <?php if ($is_admin): ?>
                                <th width="13%" class="text-center">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result_read->num_rows > 0):
                            $no = 1;
                            $total_pinjaman = 0;
                            while ($row = $result_read->fetch_assoc()): 
                                $total_pinjaman += $row['nominal_pinjaman'];
                                $nominal_class = $row['nominal_pinjaman'] >= 10000000 ? 'nominal-besar' : '';
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($row['nomer_kontrak'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['dari'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="<?php echo $nominal_class; ?>">
                                <?php echo format_rupiah($row['nominal_pinjaman']); ?>
                            </td>
                            <td><?php echo date('d-m-Y', strtotime($row['tanggal_pinjaman'])); ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo htmlspecialchars($row['nama_kategori'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if (!empty($row['berkas'])): ?>
                                    <a href="uploads/pinjaman_modal/<?php echo rawurlencode($row['berkas']); ?>" target="_blank" 
                                       class="btn btn-info btn-sm" title="Lihat PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-danger">Kosong</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($is_admin): ?>
                                <td class="text-center">
                                    <a href="pinjaman_modal.php?action=edit&id=<?php echo $row['id_pinjaman']; ?>" 
                                       class="btn btn-warning btn-sm" title="Edit Data">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="pinjaman_modal.php?action=delete&id=<?php echo $row['id_pinjaman']; ?>&csrf_token=<?php echo generate_csrf_token(); ?>" 
                                       class="btn btn-danger btn-sm" title="Hapus Data" 
                                       onclick="return confirm('Yakin ingin menghapus data pinjaman ini?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php 
                            endwhile; 
                        ?>
                        <tr class="table-info font-weight-bold">
                            <td colspan="3" class="text-end"><strong>TOTAL PINJAMAN:</strong></td>
                            <td colspan="<?php echo $is_admin ? '5' : '4'; ?>">
                                <strong><?php echo format_rupiah($total_pinjaman); ?></strong>
                            </td>
                        </tr>
                        <?php
                        else:
                        ?>
                        <tr>
                            <td colspan="<?php echo $is_admin ? '8' : '7'; ?>" class="text-center">
                                Tidak ada data pinjaman modal yang sesuai dengan kriteria.
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