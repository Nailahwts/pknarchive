<?php
require_once 'koneksi.php';
cek_login();

$id_user_session = $_SESSION['id_user'] ?? 0;
$role_user = $_SESSION['role'] ?? 'user';

if ($role_user !== 'admin') {
    header('Location: index.php?error=access_denied');
    exit;
}

$message = ''; 
$action = $_GET['action'] ?? 'read';
$id_user = (int)($_GET['id'] ?? 0);

$search = $_GET['search'] ?? '';
$filter_role = $_GET['filter_role'] ?? '';

$search = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
$filter_role = htmlspecialchars($filter_role, ENT_QUOTES, 'UTF-8');

function sanitize_data($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> Permintaan tidak valid (CSRF Token mismatch).
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        $action = 'read'; 
    } else {
        
        $nama_lengkap = sanitize_data($_POST['nama_lengkap'] ?? '');
        $username = sanitize_data($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = sanitize_data($_POST['role'] ?? 'user');
        $foto_lama = sanitize_data($_POST['foto_lama'] ?? '');
        $foto_nama = $foto_lama;
        $upload_dir = 'uploads/profil/';
        
        if (!in_array($role, ['admin', 'user'])) {
            $role = 'user';
        }
        
        if (empty($nama_lengkap) || empty($username)) {
            $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> Nama lengkap dan username tidak boleh kosong.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
            $action = $_POST['action_type'] === 'update' ? 'edit' : 'create';
        } elseif ($_POST['action_type'] == 'create' && empty($password)) {
            $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> Password tidak boleh kosong saat membuat user baru.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
            $action = 'create';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> Username hanya boleh berisi huruf, angka, dan underscore (3-50 karakter).
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
            $action = $_POST['action_type'] === 'update' ? 'edit' : 'create';
        } elseif (!empty($password) && strlen($password) < 6) {
            $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> Password minimal 6 karakter.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
            $action = $_POST['action_type'] === 'update' ? 'edit' : 'create';
        } else {
            
            $upload_error_flag = false;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                $allowed_mime = ['image/jpeg', 'image/png', 'image/jpg'];
                $uploaded_mime = $_FILES['foto']['type'];

                if (!in_array($uploaded_mime, $allowed_mime)) {
                    $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i> Foto harus berformat JPG, JPEG, atau PNG.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>';
                    $action = $_POST['action_type'] === 'update' ? 'edit' : 'create'; 
                    $upload_error_flag = true;
                } elseif ($_FILES['foto']['size'] > 5097152) {
                    $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i> Ukuran foto maksimal 5 MB.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>';
                    $action = $_POST['action_type'] === 'update' ? 'edit' : 'create';
                    $upload_error_flag = true;
                } else {
                    $file_tmp = $_FILES['foto']['tmp_name'];
                    $file_name = basename($_FILES['foto']['name']);
                    $foto_nama_new = time() . '_' . uniqid() . '.' . pathinfo($file_name, PATHINFO_EXTENSION);
                    
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    if (move_uploaded_file($file_tmp, $upload_dir . $foto_nama_new)) {
                        if (!empty($foto_lama) && file_exists($upload_dir . $foto_lama)) {
                            unlink($upload_dir . $foto_lama);
                        }
                        $foto_nama = $foto_nama_new;
                    } else {
                        $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-triangle"></i> Gagal mengunggah foto.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>';
                    }
                }
            } else if ($_POST['action_type'] === 'update' && isset($_POST['hapus_foto']) && $_POST['hapus_foto'] == 1) {
                if (!empty($foto_lama) && file_exists($upload_dir . $foto_lama)) {
                    unlink($upload_dir . $foto_lama);
                }
                $foto_nama = null;
            }

            if (!$upload_error_flag) {
                
                if ($_POST['action_type'] == 'create') {
                    $check_stmt = $koneksi->prepare("SELECT username FROM tb_user WHERE username = ?");
                    $check_stmt->bind_param("s", $username);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-times-circle"></i> Username sudah digunakan. Gunakan username lain.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>';
                        $action = 'create';
                    } else {
                        $password_hash = password_hash($password, PASSWORD_BCRYPT);
                        
                        $query = "INSERT INTO tb_user (nama_lengkap, username, password, role, foto) VALUES (?, ?, ?, ?, ?)";
                        $stmt = $koneksi->prepare($query);
                        $stmt->bind_param("sssss", $nama_lengkap, $username, $password_hash, $role, $foto_nama);
                        
                        if ($stmt->execute()) {
                            header('Location: user.php?status=sukses_tambah');
                            exit;
                        } else {
                            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="fas fa-times-circle"></i> Gagal menambahkan user: ' . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8') . '
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>';
                        }
                        $stmt->close();
                    }
                    $check_stmt->close();
                    $action = 'read';
                }
                
                if ($_POST['action_type'] == 'update') {
                    $check_stmt = $koneksi->prepare("SELECT id_user FROM tb_user WHERE username = ? AND id_user != ?");
                    $check_stmt->bind_param("si", $username, $id_user);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-times-circle"></i> Username sudah digunakan oleh user lain.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>';
                        $action = 'edit';
                    } else {
                        if (!empty($password)) {
                            $password_hash = password_hash($password, PASSWORD_BCRYPT);
                            $query = "UPDATE tb_user SET nama_lengkap=?, username=?, password=?, role=?, foto=? WHERE id_user=?";
                            $stmt = $koneksi->prepare($query);
                            $stmt->bind_param("sssssi", $nama_lengkap, $username, $password_hash, $role, $foto_nama, $id_user);
                        } else {
                            $query = "UPDATE tb_user SET nama_lengkap=?, username=?, role=?, foto=? WHERE id_user=?";
                            $stmt = $koneksi->prepare($query);
                            $stmt->bind_param("ssssi", $nama_lengkap, $username, $role, $foto_nama, $id_user);
                        }
                        
                        if ($stmt->execute()) {
                            header('Location: user.php?status=sukses_edit');
                            exit;
                        } else {
                            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="fas fa-times-circle"></i> Gagal memperbarui user: ' . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8') . '
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
}

if ($action == 'delete') {
    if (isset($_GET['id']) && isset($_GET['csrf_token']) && validate_csrf_token($_GET['csrf_token'])) {
        $id_user_del = (int)$_GET['id'];
        
        if ($id_user_del == $id_user_session) {
            $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> Anda tidak dapat menghapus akun Anda sendiri.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } else {
            $stmt_file = $koneksi->prepare("SELECT foto FROM tb_user WHERE id_user = ?");
            $stmt_file->bind_param("i", $id_user_del);
            $stmt_file->execute();
            $result_file = $stmt_file->get_result();
            
            if ($result_file->num_rows > 0) {
                $row_file = $result_file->fetch_assoc();
                $file_to_delete = 'uploads/profil/' . $row_file['foto']; 
                
                $stmt_delete = $koneksi->prepare("DELETE FROM tb_user WHERE id_user = ?");
                $stmt_delete->bind_param("i", $id_user_del);
                
                if ($stmt_delete->execute()) {
                    if (!empty($row_file['foto']) && file_exists($file_to_delete)) {
                        unlink($file_to_delete);
                    }
                    header('Location: user.php?status=sukses_hapus');
                    exit;
                } else {
                    $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-times-circle"></i> Gagal menghapus user: ' . htmlspecialchars($stmt_delete->error, ENT_QUOTES, 'UTF-8') . '
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>';
                }
                $stmt_delete->close();
            }
            $stmt_file->close();
        }
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
            <i class="fas fa-users"></i> Manajemen User
        </h1>
        <?php if ($action == 'read'): ?>
            <a href="user.php?action=create" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Tambah User
            </a>
        <?php endif; ?>
    </div>
    
    <?php 
    if (isset($_GET['status'])) {
        if ($_GET['status'] == 'sukses_tambah') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> User baru berhasil ditambahkan!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } elseif ($_GET['status'] == 'sukses_edit') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Data user berhasil diperbarui!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } elseif ($_GET['status'] == 'sukses_hapus') {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> User berhasil dihapus!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        }
    }
    
    echo $message;
    ?>
    
    <?php 
    if (($action == 'create') || ($action == 'edit' && $id_user > 0)): 
        
        $data = [
            'id_user' => 0, 'nama_lengkap' => '', 'username' => '', 
            'role' => 'user', 'foto' => ''
        ];
        $form_title = "Tambah User Baru";
        $action_type = "create";
        
        if ($action == 'edit') {
            $stmt_edit = $koneksi->prepare("SELECT * FROM tb_user WHERE id_user = ?");
            $stmt_edit->bind_param("i", $id_user);
            $stmt_edit->execute();
            $result_edit = $stmt_edit->get_result();
            
            if ($result_edit->num_rows == 1) {
                $data = $result_edit->fetch_assoc();
                $form_title = "Edit User (ID: " . $id_user . ")";
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
                <i class="fas fa-<?php echo $action_type == 'create' ? 'user-plus' : 'user-edit'; ?>"></i> 
                <?php echo htmlspecialchars($form_title, ENT_QUOTES, 'UTF-8'); ?>
            </h6>
        </div>
        <div class="card-body">
            <form action="user.php?action=read&id=<?php echo $data['id_user']; ?>" method="POST" enctype="multipart/form-data">
                
                <input type="hidden" name="action_type" value="<?php echo htmlspecialchars($action_type, ENT_QUOTES, 'UTF-8'); ?>">
                <?php if ($action_type == 'update'): ?>
                    <input type="hidden" name="id_user" value="<?php echo $data['id_user']; ?>">
                    <input type="hidden" name="foto_lama" value="<?php echo htmlspecialchars($data['foto'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
                
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lengkap" class="form-control" 
                               value="<?php echo htmlspecialchars($data['nama_lengkap'], ENT_QUOTES, 'UTF-8'); ?>" 
                               required maxlength="150">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" 
                               value="<?php echo htmlspecialchars($data['username'], ENT_QUOTES, 'UTF-8'); ?>" 
                               required maxlength="50" pattern="[a-zA-Z0-9_]{3,50}">
                        <small class="text-muted">3-50 karakter, hanya huruf, angka, dan underscore</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Password 
                            <?php if ($action_type == 'create'): ?>
                                <span class="text-danger">*</span>
                            <?php else: ?>
                                <small class="text-muted">(Kosongkan jika tidak ingin mengubah)</small>
                            <?php endif; ?>
                        </label>
                        <input type="password" name="password" class="form-control" 
                               <?php echo $action_type == 'create' ? 'required' : ''; ?> 
                               minlength="6" placeholder="<?php echo $action_type == 'update' ? 'Kosongkan jika tidak diubah' : 'Minimal 6 karakter'; ?>">
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="user" <?php echo ($data['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo ($data['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Foto Profil (JPG, PNG)</label>
                    <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png">
                    <?php if ($action_type == 'update' && !empty($data['foto'])): ?>
                        <div class="mt-2">
                            <img src="uploads/profil/<?php echo htmlspecialchars($data['foto'], ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="Foto User" class="img-thumbnail" style="max-width: 150px;">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="hapus_foto" value="1" id="hapus_foto_check">
                                <label class="form-check-label" for="hapus_foto_check">
                                    Centang untuk Hapus Foto
                                </label>
                            </div>
                        </div>
                    <?php else: ?>
                        <small class="form-text text-muted">Maksimal 5 MB, format JPG atau PNG</small>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                    <a href="user.php" class="btn btn-secondary">
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
        
        $query_read = "SELECT * FROM tb_user WHERE 1=1";
        $bind_types = '';
        $bind_params = [];
        
        if (!empty($search)) {
            $search_param = '%' . $search . '%';
            $query_read .= " AND (nama_lengkap LIKE ? OR username LIKE ?)";
            $bind_types .= 'ss';
            $bind_params[] = &$search_param;
            $bind_params[] = &$search_param;
        }
        
        if (!empty($filter_role)) {
            $query_read .= " AND role = ?";
            $bind_types .= 's';
            $bind_params[] = &$filter_role;
        }
        
        $query_read .= " ORDER BY id_user DESC";
        
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
            <form action="user.php" method="GET">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-search"></i> Cari User</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Nama atau Username..." 
                               value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><i class="fas fa-filter"></i> Filter Role</label>
                        <select name="filter_role" class="form-select">
                            <option value="">-- Semua --</option>
                            <option value="admin" <?php echo ($filter_role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="user" <?php echo ($filter_role == 'user') ? 'selected' : ''; ?>>User</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <?php if (!empty($search) || !empty($filter_role)): ?>
                                <a href="user.php" class="btn btn-secondary" title="Reset Filter">
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
                <i class="fas fa-list"></i> Daftar User
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">NO</th>
                            <th width="8%" class="text-center">Foto</th>
                            <th width="20%">Nama Lengkap</th>
                            <th width="15%">Username</th>
                            <th width="10%" class="text-center">Role</th>
                            <th width="12%" class="text-center">Aksi</th>
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
                            <td class="text-center">
                                <?php if (!empty($row['foto'])): ?>
                                    <img src="uploads/profil/<?php echo htmlspecialchars($row['foto'], ENT_QUOTES, 'UTF-8'); ?>" 
                                         alt="Foto" class="img-thumbnail" style="max-width: 50px; max-height: 50px;">
                                <?php else: ?>
                                    <i class="fas fa-user-circle fa-3x text-secondary"></i>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['nama_lengkap'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-center">
                                <?php if ($row['role'] == 'admin'): ?>
                                    <span class="badge bg-danger">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-info">User</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="user.php?action=edit&id=<?php echo $row['id_user']; ?>" 
                                   class="btn btn-warning btn-sm" title="Edit User">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($row['id_user'] != $id_user_session): ?>
                                <a href="user.php?action=delete&id=<?php echo $row['id_user']; ?>&csrf_token=<?php echo generate_csrf_token(); ?>" 
                                   class="btn btn-danger btn-sm" title="Hapus User" 
                                   onclick="return confirm('Yakin ingin menghapus user ini?');">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                                <?php else: ?>
                                <span class="badge bg-secondary">Akun Anda</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        else:
                        ?>
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada data user yang sesuai dengan kriteria.</td>
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