<?php
require_once 'koneksi.php';
cek_login();

$is_admin = ($role_user === 'admin');
$message = ''; 
$action = $_GET['action'] ?? 'read';
$id_memo = (int)($_GET['id'] ?? 0);

$search = $_GET['search'] ?? '';
$filter_kategori = $_GET['filter_kategori'] ?? '';
$search_tgl = $_GET['search_tgl'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'tgl_memo';
$sort_order = $_GET['sort_order'] ?? 'DESC';

$search = htmlspecialchars($search);
$filter_kategori = (int)$filter_kategori;
$search_tgl = htmlspecialchars($search_tgl);
$sort_by = htmlspecialchars($sort_by);
$sort_order = htmlspecialchars(strtoupper($sort_order)); 

function sanitize_data_for_bind($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_admin) {

    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $tgl_memo = sanitize_data_for_bind($_POST['tgl_memo'] ?? '');
    $kepada = sanitize_data_for_bind($_POST['kepada'] ?? '');
    $no_memo = sanitize_data_for_bind($_POST['no_memo'] ?? '');
    $perihal = sanitize_data_for_bind($_POST['perihal'] ?? '');
    $berkas_lama = sanitize_data_for_bind($_POST['berkas_lama'] ?? ''); 
    $berkas_nama = $berkas_lama;
    $upload_dir = 'uploads/memo_keluar/';

    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> Permintaan tidak valid (CSRF Token mismatch).
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        $action = 'read'; 
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
                $query = "INSERT INTO tb_memo_keluar (id_kategori, tgl_memo, kepada, no_memo, perihal, berkas) 
                          VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $koneksi->prepare($query);
                $stmt->bind_param("isssss", $id_kategori, $tgl_memo, $kepada, $no_memo, $perihal, $berkas_nama);
                
                if ($stmt->execute()) {
                    header('Location: memo_keluar.php?status=sukses_tambah');
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
                $query = "UPDATE tb_memo_keluar SET id_kategori=?, tgl_memo=?, kepada=?, no_memo=?, perihal=?, berkas=? 
                          WHERE id_memo_keluar=?";
                $stmt = $koneksi->prepare($query);
                $stmt->bind_param("isssssi", $id_kategori, $tgl_memo, $kepada, $no_memo, $perihal, $berkas_nama, $id_memo);
                
                if ($stmt->execute()) {
                    header('Location: memo_keluar.php?status=sukses_edit');
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
}

if ($action == 'delete' && $is_admin) {
    if (isset($_GET['id']) && isset($_GET['csrf_token']) && validate_csrf_token($_GET['csrf_token'])) {
        $id_memo_del = (int)$_GET['id'];
        
        $stmt_file = $koneksi->prepare("SELECT berkas FROM tb_memo_keluar WHERE id_memo_keluar = ?");
        $stmt_file->bind_param("i", $id_memo_del);
        $stmt_file->execute();
        $result_file = $stmt_file->get_result();
        
        if ($result_file->num_rows > 0) {
            $row_file = $result_file->fetch_assoc();
            $file_to_delete = 'uploads/memo_keluar/' . $row_file['berkas']; 
            
            $stmt_delete = $koneksi->prepare("DELETE FROM tb_memo_keluar WHERE id_memo_keluar = ?");
            $stmt_delete->bind_param("i", $id_memo_del);
            
            if ($stmt_delete->execute()) {
                if (!empty($row_file['berkas']) && file_exists($file_to_delete)) {
                    unlink($file_to_delete);
                }
                header('Location: memo_keluar.php?status=sukses_hapus');
                exit;
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-times-circle"></i> Gagal menghapus data: ' . $stmt_delete->error . '
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
</style>
';

?>

<div class="container-fluid">
    
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-paper-plane"></i> Data Memo Keluar
        </h1>
        <?php if ($action == 'read' && $is_admin): ?>
            <a href="memo_keluar.php?action=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Memo Keluar
            </a>
        <?php endif; ?>
    </div>
    
    <?php 
    if (isset($_GET['status'])) {
        if ($_GET['status'] == 'sukses_tambah') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Memo Keluar baru berhasil ditambahkan!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } elseif ($_GET['status'] == 'sukses_edit') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Memo Keluar berhasil diperbarui!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } elseif ($_GET['status'] == 'sukses_hapus') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Data memo keluar berhasil dihapus!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        }
    }
    
    echo $message;
    ?>
    
    <?php 
    if (($action == 'create' && $is_admin) || ($action == 'edit' && $is_admin && $id_memo > 0)): 
        
        $data = [
            'id_memo_keluar' => 0, 'id_kategori' => '', 'tgl_memo' => '', 'kepada' => '', 
            'no_memo' => '', 'perihal' => '', 'berkas' => ''
        ];
        $form_title = "Tambah Memo Keluar";
        $action_type = "create";
        
        if ($action == 'edit') {
            $stmt_edit = $koneksi->prepare("SELECT * FROM tb_memo_keluar WHERE id_memo_keluar = ?");
            $stmt_edit->bind_param("i", $id_memo);
            $stmt_edit->execute();
            $result_edit = $stmt_edit->get_result();
            
            if ($result_edit->num_rows == 1) {
                $data = $result_edit->fetch_assoc();
                $form_title = "Edit Memo Keluar (ID: " . $id_memo . ")";
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
                <?php echo $form_title; ?>
            </h6>
        </div>
        <div class="card-body">
            <form action="memo_keluar.php?action=read&id=<?php echo $data['id_memo_keluar']; ?>" method="POST" enctype="multipart/form-data">
                
                <input type="hidden" name="action_type" value="<?php echo $action_type; ?>">
                <?php if ($action_type == 'update'): ?>
                    <input type="hidden" name="id_memo" value="<?php echo $data['id_memo_keluar']; ?>">
                    <input type="hidden" name="berkas_lama" value="<?php echo escape_html($data['berkas']); ?>">
                <?php endif; ?>
                
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Kategori <span class="text-danger">*</span></label>
                        <select name="id_kategori" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php while ($k = $kategori_result->fetch_assoc()): ?>
                                <option value="<?php echo $k['id_kategori']; ?>" 
                                    <?php echo ($k['id_kategori'] == $data['id_kategori']) ? 'selected' : ''; ?>>
                                    <?php echo escape_html($k['nama_kategori']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Tanggal Memo <span class="text-danger">*</span></label>
                        <input type="date" name="tgl_memo" class="form-control" value="<?php echo escape_html($data['tgl_memo']); ?>" required>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Kepada <span class="text-danger">*</span></label>
                        <input type="text" name="kepada" class="form-control" value="<?php echo escape_html($data['kepada']); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-2">
                        <label class="form-label">Nomor Memo <span class="text-danger">*</span></label>
                        <input type="text" name="no_memo" class="form-control" value="<?php echo escape_html($data['no_memo']); ?>" required>
                    </div>
                </div>
                
                <div class="mb-2">
                    <label class="form-label">Perihal <span class="text-danger">*</span></label>
                    <textarea name="perihal" class="form-control" rows="3" required><?php echo escape_html($data['perihal']); ?></textarea>
                </div>
                
                <div class="mb-2">
                    <label class="form-label">Unggah Berkas (PDF Only)</label>
                    <input type="file" name="berkas" class="form-control" accept=".pdf">
                    <?php if ($action_type == 'update' && !empty($data['berkas'])): ?>
                        <small class="form-text text-muted">
                            Berkas saat ini: <a href="uploads/memo_keluar/<?php echo rawurlencode($data['berkas']); ?>" target="_blank"><?php echo escape_html($data['berkas']); ?></a>
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
                    <a href="memo_keluar.php" class="btn btn-secondary">
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
        
        $query_read = "SELECT mk.*, k.nama_kategori FROM tb_memo_keluar mk 
                         JOIN tb_kategori k ON mk.id_kategori = k.id_kategori WHERE 1=1";
                         
        $bind_types = '';
        $bind_params = [];
    
        if (!empty($search)) {
            $search_param = '%' . $search . '%';
            $query_read .= " AND (mk.no_memo LIKE ? OR mk.kepada LIKE ? OR mk.perihal LIKE ?)";
            $bind_types .= 'sss';
            $bind_params[] = &$search_param;
            $bind_params[] = &$search_param;
            $bind_params[] = &$search_param;
        }
        
        if (!empty($search_tgl)) {
            $query_read .= " AND mk.tgl_memo = ?";
            $bind_types .= 's';
            $bind_params[] = &$search_tgl;
        }
        
        if (!empty($filter_kategori)) {
            $query_read .= " AND mk.id_kategori = ?";
            $bind_types .= 'i';
            $bind_params[] = &$filter_kategori;
        }
        
        $allowed_sort = ['tgl_memo', 'no_memo', 'kepada', 'nama_kategori'];
        $allowed_order = ['ASC', 'DESC'];
        
        if (in_array($sort_by, $allowed_sort) && in_array($sort_order, $allowed_order)) {
            $query_read .= " ORDER BY " . $sort_by . " " . $sort_order;
        } else {
            $query_read .= " ORDER BY mk.tgl_memo DESC";
            $sort_by = 'tgl_memo';
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
    
    <!-- Filter Section -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter"></i> Filter dan Pencarian
            </h6>
        </div>
        <div class="card-body">
            <form action="memo_keluar.php" method="GET">
                <input type="hidden" name="sort_by" value="<?php echo escape_html($sort_by); ?>">
                <input type="hidden" name="sort_order" value="<?php echo escape_html($sort_order); ?>">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label"><i class="fas fa-search"></i> Cari Memo</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="No. Memo, Kepada, atau Perihal..." 
                               value="<?php echo escape_html($search); ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label"><i class="fas fa-calendar"></i> Tgl Memo</label>
                        <input type="date" name="search_tgl" class="form-control" 
                               value="<?php echo escape_html($search_tgl); ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label"><i class="fas fa-filter"></i> Kategori</label>
                        <select name="filter_kategori" class="form-select">
                            <option value="">-- Semua Kategori --</option>
                            <?php while ($kat = $kategori_list->fetch_assoc()): ?>
                                <option value="<?php echo $kat['id_kategori']; ?>" 
                                    <?php echo ($filter_kategori == $kat['id_kategori']) ? 'selected' : ''; ?>>
                                    <?php echo escape_html($kat['nama_kategori']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-end">
                        <div>
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <?php if (!empty($search) || !empty($filter_kategori) || !empty($search_tgl)): ?>
                                <a href="memo_keluar.php" class="btn btn-secondary" title="Reset Filter">
                                    <i class="fas fa-sync-alt"></i> Reset
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Daftar Memo Keluar
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
                            <th width="12%">Tanggal Memo</th>
                            <th width="18%">No. Memo</th>
                            <th width="15%">Kepada</th>
                            <th width="25%">Perihal</th>
                            <th width="10%">Kategori</th>
                            <th width="8%" class="text-center">Berkas</th>
                            <?php if ($is_admin): ?>
                                <th width="7%" class="text-center">Aksi</th>
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
                            <td><?php echo date('d-m-Y', strtotime(escape_html($row['tgl_memo']))); ?></td>
                            <td><?php echo escape_html($row['no_memo']); ?></td>
                            <td><?php echo escape_html($row['kepada']); ?></td> 
                            <td><?php echo substr(escape_html($row['perihal']), 0, 60) . (strlen($row['perihal']) > 60 ? '...' : ''); ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo escape_html($row['nama_kategori']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if (!empty($row['berkas'])): ?>
                                    <a href="uploads/memo_keluar/<?php echo rawurlencode($row['berkas']); ?>" target="_blank" 
                                       class="btn btn-info btn-sm" title="Lihat PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-danger">Kosong</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($is_admin): ?>
                                <td class="text-center">
                                    <a href="memo_keluar.php?action=edit&id=<?php echo $row['id_memo_keluar']; ?>" 
                                       class="btn btn-warning btn-sm" title="Edit Data">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="memo_keluar.php?action=delete&id=<?php echo $row['id_memo_keluar']; ?>&csrf_token=<?php echo generate_csrf_token(); ?>" 
                                       class="btn btn-danger btn-sm" title="Hapus Data" 
                                       onclick="return confirm('Yakin ingin menghapus data ini?');">
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
                            <td colspan="<?php echo $is_admin ? '8' : '7'; ?>" class="text-center">
                                Tidak ada data memo keluar yang sesuai dengan kriteria.
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