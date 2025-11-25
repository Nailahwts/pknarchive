<?php
require_once 'koneksi.php';
cek_login();

// HANYA ADMIN YANG BISA AKSES
if ($role_user !== 'admin') {
    header('Location: index.php?error=access_denied');
    exit;
}

$message = ''; 
$action = $_GET['action'] ?? 'read';
$id_kategori = (int)($_GET['id'] ?? 0);

function sanitize_data($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// PROSES CREATE & UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> Permintaan tidak valid (CSRF Token mismatch).
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        $action = 'read'; 
    } else {
        
        $nama_kategori = sanitize_data($_POST['nama_kategori'] ?? '');
        
        if (empty($nama_kategori)) {
            $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> Nama kategori tidak boleh kosong.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
            $action = $_POST['action_type'] === 'update' ? 'edit' : 'create';
        } else {
            
            if ($_POST['action_type'] == 'create') {
                // Cek duplikasi nama kategori
                $check_stmt = $koneksi->prepare("SELECT nama_kategori FROM tb_kategori WHERE nama_kategori = ?");
                $check_stmt->bind_param("s", $nama_kategori);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-times-circle"></i> Nama kategori sudah ada. Gunakan nama lain.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>';
                    $action = 'create';
                } else {
                    $query = "INSERT INTO tb_kategori (nama_kategori) VALUES (?)";
                    $stmt = $koneksi->prepare($query);
                    $stmt->bind_param("s", $nama_kategori);
                    
                    if ($stmt->execute()) {
                        header('Location: kategori.php?status=sukses_tambah');
                        exit;
                    } else {
                        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-times-circle"></i> Gagal menambahkan kategori: ' . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8') . '
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>';
                    }
                    $stmt->close();
                }
                $check_stmt->close();
                $action = 'read';
            }
            
            if ($_POST['action_type'] == 'update') {
                // Cek duplikasi nama kategori (kecuali dirinya sendiri)
                $check_stmt = $koneksi->prepare("SELECT id_kategori FROM tb_kategori WHERE nama_kategori = ? AND id_kategori != ?");
                $check_stmt->bind_param("si", $nama_kategori, $id_kategori);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-times-circle"></i> Nama kategori sudah digunakan oleh kategori lain.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>';
                    $action = 'edit';
                } else {
                    $query = "UPDATE tb_kategori SET nama_kategori=? WHERE id_kategori=?";
                    $stmt = $koneksi->prepare($query);
                    $stmt->bind_param("si", $nama_kategori, $id_kategori);
                    
                    if ($stmt->execute()) {
                        header('Location: kategori.php?status=sukses_edit');
                        exit;
                    } else {
                        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-times-circle"></i> Gagal memperbarui kategori: ' . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8') . '
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>';
                    }
                    $stmt->close();
                }
                $check_stmt->close();
                $action = 'read';
            }
        }
    }
}

// PROSES DELETE
if ($action == 'delete') {
    if (isset($_GET['id']) && isset($_GET['csrf_token']) && validate_csrf_token($_GET['csrf_token'])) {
        $id_kategori_del = (int)$_GET['id'];
        
        // Cek apakah kategori masih digunakan di tabel lain
        $check_usage_stmt = $koneksi->prepare("
            SELECT 
                (SELECT COUNT(*) FROM tb_kontrak_sopir WHERE id_kategori = ?) +
                (SELECT COUNT(*) FROM tb_kontrak_staf_administrasi WHERE id_kategori = ?) +
                (SELECT COUNT(*) FROM tb_memo_keluar WHERE id_kategori = ?) +
                (SELECT COUNT(*) FROM tb_memo_masuk WHERE id_kategori = ?) +
                (SELECT COUNT(*) FROM tb_pinjaman_modal WHERE id_kategori = ?) +
                (SELECT COUNT(*) FROM tb_surat_keluar WHERE id_kategori = ?) +
                (SELECT COUNT(*) FROM tb_surat_masuk WHERE id_kategori = ?) AS total_usage
        ");
        $check_usage_stmt->bind_param("iiiiiii", $id_kategori_del, $id_kategori_del, $id_kategori_del, $id_kategori_del, $id_kategori_del, $id_kategori_del, $id_kategori_del);
        $check_usage_stmt->execute();
        $usage_result = $check_usage_stmt->get_result();
        $usage_row = $usage_result->fetch_assoc();
        
        if ($usage_row['total_usage'] > 0) {
            $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> Kategori tidak dapat dihapus karena masih digunakan di ' . $usage_row['total_usage'] . ' data.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } else {
            $stmt_delete = $koneksi->prepare("DELETE FROM tb_kategori WHERE id_kategori = ?");
            $stmt_delete->bind_param("i", $id_kategori_del);
            
            if ($stmt_delete->execute()) {
                header('Location: kategori.php?status=sukses_hapus');
                exit;
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-times-circle"></i> Gagal menghapus kategori: ' . htmlspecialchars($stmt_delete->error, ENT_QUOTES, 'UTF-8') . '
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
            }
            $stmt_delete->close();
        }
        $check_usage_stmt->close();
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-times-circle"></i> Invalid ID atau CSRF Token.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
    }
    $action = 'read'; 
}

include 'template/header.php'; 
?>

<div class="container-fluid">
    
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-folder"></i> Manajemen Kategori
        </h1>
        <?php if ($action == 'read'): ?>
            <a href="kategori.php?action=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Kategori
            </a>
        <?php endif; ?>
    </div>
    
    <?php 
    if (isset($_GET['status'])) {
        if ($_GET['status'] == 'sukses_tambah') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Kategori baru berhasil ditambahkan!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } elseif ($_GET['status'] == 'sukses_edit') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Kategori berhasil diperbarui!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } elseif ($_GET['status'] == 'sukses_hapus') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Kategori berhasil dihapus!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        }
    }
    
    echo $message;
    ?>
    
    <?php 
    if (($action == 'create') || ($action == 'edit' && $id_kategori > 0)): 
        
        $data = ['id_kategori' => 0, 'nama_kategori' => ''];
        $form_title = "Tambah Kategori Baru";
        $action_type = "create";
        
        if ($action == 'edit') {
            $stmt_edit = $koneksi->prepare("SELECT * FROM tb_kategori WHERE id_kategori = ?");
            $stmt_edit->bind_param("i", $id_kategori);
            $stmt_edit->execute();
            $result_edit = $stmt_edit->get_result();
            
            if ($result_edit->num_rows == 1) {
                $data = $result_edit->fetch_assoc();
                $form_title = "Edit Kategori (ID: " . $id_kategori . ")";
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
                <?php echo htmlspecialchars($form_title, ENT_QUOTES, 'UTF-8'); ?>
            </h6>
        </div>
        <div class="card-body">
            <form action="kategori.php?action=read&id=<?php echo $data['id_kategori']; ?>" method="POST">
                
                <input type="hidden" name="action_type" value="<?php echo htmlspecialchars($action_type, ENT_QUOTES, 'UTF-8'); ?>">
                <?php if ($action_type == 'update'): ?>
                    <input type="hidden" name="id_kategori" value="<?php echo $data['id_kategori']; ?>">
                <?php endif; ?>
                
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="mb-3">
                    <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="nama_kategori" class="form-control" 
                           value="<?php echo htmlspecialchars($data['nama_kategori'], ENT_QUOTES, 'UTF-8'); ?>" 
                           required maxlength="100" placeholder="Contoh: KEUANGAN, SDM, UMUM">
                    <small class="text-muted">Maksimal 100 karakter</small>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                    <a href="kategori.php" class="btn btn-secondary">
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
        
        $query_read = "SELECT * FROM tb_kategori ORDER BY nama_kategori ASC";
        $result_read = $koneksi->query($query_read);
    ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Daftar Kategori
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th width="10%" class="text-center">NO</th>
                            <th width="55%">Nama Kategori</th>
                            <th width="20%" class="text-center">Aksi</th>
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
                            <td><?php echo htmlspecialchars($row['nama_kategori'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-center">
                                <a href="kategori.php?action=edit&id=<?php echo $row['id_kategori']; ?>" 
                                   class="btn btn-warning btn-sm" title="Edit Kategori">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="kategori.php?action=delete&id=<?php echo $row['id_kategori']; ?>&csrf_token=<?php echo generate_csrf_token(); ?>" 
                                   class="btn btn-danger btn-sm" title="Hapus Kategori" 
                                   onclick="return confirm('Yakin ingin menghapus kategori ini?\n\nPeringatan: Kategori yang masih digunakan tidak dapat dihapus.');">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        else:
                        ?>
                        <tr>
                            <td colspan="4" class="text-center">Belum ada data kategori.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php 
    endif; 
    ?>
</div>

<?php
include 'template/footer.php'; 
?>