<?php
require_once 'koneksi.php';
cek_login();

$is_admin = ($role_user === 'admin');
$message = ''; 
$action = $_GET['action'] ?? 'read';
$id_peserta = (int)($_GET['id'] ?? 0);

$search = $_GET['search'] ?? '';
$filter_gender = $_GET['filter_gender'] ?? '';
$filter_bidang = $_GET['filter_bidang'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'nama';
$sort_order = $_GET['sort_order'] ?? 'ASC';

$search = htmlspecialchars($search);
$filter_gender = htmlspecialchars($filter_gender);
$filter_bidang = htmlspecialchars($filter_bidang);
$sort_by = htmlspecialchars($sort_by);
$sort_order = htmlspecialchars(strtoupper($sort_order)); 

function sanitize_data_for_bind($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_admin) {
    $nama = sanitize_data_for_bind($_POST['nama'] ?? '');
    $gender = sanitize_data_for_bind($_POST['gender'] ?? '');
    $bidang = sanitize_data_for_bind($_POST['bidang'] ?? '');
    $tgl_lahir = sanitize_data_for_bind($_POST['tgl_lahir'] ?? '');

    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> Permintaan tidak valid (CSRF Token mismatch).
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        $action = 'read'; 
    } else {
        if ($_POST['action_type'] == 'create') {
            $query = "INSERT INTO peserta (nama, gender, bidang, tgl_lahir) VALUES (?, ?, ?, ?)";
            $stmt = $koneksi->prepare($query);
            $stmt->bind_param("ssss", $nama, $gender, $bidang, $tgl_lahir);
            
            if ($stmt->execute()) {
                header('Location: peserta.php?status=sukses_tambah');
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
            $query = "UPDATE peserta SET nama=?, gender=?, bidang=?, tgl_lahir=?, updated_at=CURRENT_TIMESTAMP WHERE id_peserta=?";
            $stmt = $koneksi->prepare($query);
            $stmt->bind_param("ssssi", $nama, $gender, $bidang, $tgl_lahir, $id_peserta);
            
            if ($stmt->execute()) {
                header('Location: peserta.php?status=sukses_edit');
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
        $id_peserta_del = (int)$_GET['id'];
        
        $stmt_delete = $koneksi->prepare("DELETE FROM peserta WHERE id_peserta = ?");
        $stmt_delete->bind_param("i", $id_peserta_del);
        
        if ($stmt_delete->execute()) {
            header('Location: peserta.php?status=sukses_hapus');
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
            <i class="fas fa-users"></i> Data Peserta Kesehatan
        </h1>
        <?php if ($action == 'read' && $is_admin): ?>
            <a href="peserta.php?action=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Peserta
            </a>
        <?php endif; ?>
    </div>
    
    <?php 
    if (isset($_GET['status'])) {
        if ($_GET['status'] == 'sukses_tambah') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Peserta baru berhasil ditambahkan!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } elseif ($_GET['status'] == 'sukses_edit') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Data Peserta berhasil diperbarui!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } elseif ($_GET['status'] == 'sukses_hapus') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Data peserta berhasil dihapus!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        }
    }
    
    echo $message;
    ?>
    
    <?php 
    if (($action == 'create' && $is_admin) || ($action == 'edit' && $is_admin && $id_peserta > 0)): 
        $data = [
            'id_peserta' => 0, 'nama' => '', 'gender' => '', 'bidang' => '', 'tgl_lahir' => ''
        ];
        $form_title = "Tambah Peserta";
        $action_type = "create";
        
        if ($action == 'edit') {
            $stmt_edit = $koneksi->prepare("SELECT * FROM peserta WHERE id_peserta = ?");
            $stmt_edit->bind_param("i", $id_peserta);
            $stmt_edit->execute();
            $result_edit = $stmt_edit->get_result();
            
            if ($result_edit->num_rows == 1) {
                $data = $result_edit->fetch_assoc();
                $form_title = "Edit Peserta (ID: " . $id_peserta . ")";
                $action_type = "update";
            } else {
                $message = '<div class="alert alert-danger">Data tidak ditemukan.</div>';
                $action = 'read';
            }
            $stmt_edit->close();
        }

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
            <form action="peserta.php?action=read&id=<?php echo $data['id_peserta']; ?>" method="POST">
                <input type="hidden" name="action_type" value="<?php echo $action_type; ?>">
                <?php if ($action_type == 'update'): ?>
                    <input type="hidden" name="id_peserta" value="<?php echo $data['id_peserta']; ?>">
                <?php endif; ?>
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" value="<?php echo escape_html($data['nama']); ?>" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <option value="L" <?php echo ($data['gender'] == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="P" <?php echo ($data['gender'] == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                        <input type="date" name="tgl_lahir" class="form-control" value="<?php echo escape_html($data['tgl_lahir']); ?>" required>
                    </div>
                </div>
                
                <div class="mb-2">
                    <label class="form-label">Bidang <span class="text-danger">*</span></label>
                    <input type="text" name="bidang" class="form-control" value="<?php echo escape_html($data['bidang']); ?>" required>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Data
                    </button>
                    <a href="peserta.php" class="btn btn-secondary">
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
        $query_read = "SELECT * FROM peserta WHERE 1=1";
        $bind_types = '';
        $bind_params = [];
    
        if (!empty($search)) {
            $search_param = '%' . $search . '%';
            $query_read .= " AND (nama LIKE ? OR bidang LIKE ?)";
            $bind_types .= 'ss';
            $bind_params[] = &$search_param;
            $bind_params[] = &$search_param;
        }
        
        if (!empty($filter_gender)) {
            $query_read .= " AND gender = ?";
            $bind_types .= 's';
            $bind_params[] = &$filter_gender;
        }
        
        if (!empty($filter_bidang)) {
            $bidang_param = '%' . $filter_bidang . '%';
            $query_read .= " AND bidang LIKE ?";
            $bind_types .= 's';
            $bind_params[] = &$bidang_param;
        }
        
        $allowed_sort = ['nama', 'gender', 'bidang', 'tgl_lahir'];
        $allowed_order = ['ASC', 'DESC'];
        
        if (in_array($sort_by, $allowed_sort) && in_array($sort_order, $allowed_order)) {
            $query_read .= " ORDER BY " . $sort_by . " " . $sort_order;
        } else {
            $query_read .= " ORDER BY nama ASC";
            $sort_by = 'nama';
            $sort_order = 'ASC';
        }
        
        $stmt_read = $koneksi->prepare($query_read);
        
        if (!empty($bind_types)) {
            array_unshift($bind_params, $bind_types);
            call_user_func_array(array($stmt_read, 'bind_param'), $bind_params);
        }
        
        $stmt_read->execute();
        $result_read = $stmt_read->get_result();
    ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter"></i> Filter dan Pencarian
            </h6>
        </div>
        <div class="card-body">
            <form action="peserta.php" method="GET">
                <input type="hidden" name="sort_by" value="<?php echo escape_html($sort_by); ?>">
                <input type="hidden" name="sort_order" value="<?php echo escape_html($sort_order); ?>">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label"><i class="fas fa-search"></i> Cari Nama/Bidang</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Nama atau Bidang..." 
                               value="<?php echo escape_html($search); ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label"><i class="fas fa-venus-mars"></i> Gender</label>
                        <select name="filter_gender" class="form-select">
                            <option value="">-- Semua --</option>
                            <option value="L" <?php echo ($filter_gender == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="P" <?php echo ($filter_gender == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label"><i class="fas fa-briefcase"></i> Bidang</label>
                        <input type="text" name="filter_bidang" class="form-control" 
                               placeholder="Filter Bidang..." 
                               value="<?php echo escape_html($filter_bidang); ?>">
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-end">
                        <div>
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <?php if (!empty($search) || !empty($filter_gender) || !empty($filter_bidang)): ?>
                                <a href="peserta.php" class="btn btn-secondary" title="Reset Filter">
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
                Daftar Peserta
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
                            <th width="25%">Nama Lengkap</th>
                            <th width="10%" class="text-center">Gender</th>
                            <th width="20%">Bidang</th>
                            <th width="15%">Tanggal Lahir</th>
                            <th width="10%" class="text-center">Umur</th>
                            <?php if ($is_admin): ?>
                                <th width="15%" class="text-center">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result_read->num_rows > 0):
                            $no = 1; 
                            while ($row = $result_read->fetch_assoc()): 
                                $tgl_lahir = new DateTime($row['tgl_lahir']);
                                $today = new DateTime();
                                $umur = $today->diff($tgl_lahir)->y;
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $no++; ?></td>
                            <td><?php echo escape_html($row['nama']); ?></td>
                            <td class="text-center">
                                <?php if ($row['gender'] == 'L'): ?>
                                    <span class="badge bg-primary">Laki-laki</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Perempuan</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo escape_html($row['bidang']); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($row['tgl_lahir'])); ?></td>
                            <td class="text-center"><?php echo $umur; ?> tahun</td>
                            <?php if ($is_admin): ?>
                                <td class="text-center">
                                    <a href="peserta.php?action=edit&id=<?php echo $row['id_peserta']; ?>" 
                                       class="btn btn-warning btn-sm" title="Edit Data">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="peserta.php?action=delete&id=<?php echo $row['id_peserta']; ?>&csrf_token=<?php echo generate_csrf_token(); ?>" 
                                       class="btn btn-danger btn-sm" title="Hapus Data" 
                                       onclick="return confirm('Yakin ingin menghapus data ini? Data pemeriksaan peserta juga akan terhapus!');">
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
                            <td colspan="<?php echo $is_admin ? '7' : '6'; ?>" class="text-center">
                                Tidak ada data peserta yang sesuai dengan kriteria.
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