<?php
include 'template/header.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

$id_user = $_SESSION['id_user'];
$pesan = '';
$upload_dir = 'uploads/profil/';

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

function handleUploadFoto($file_post, $target_dir) {
    if (empty($file_post['name'])) {
        return ['status' => 'success', 'message' => ''];
    }
    if ($file_post['error'] !== UPLOAD_ERR_OK) {
        return ['status' => 'error', 'message' => 'Error saat upload file. Kode: ' . $file_post['error']];
    }
    
    $file_tmp = $file_post['tmp_name'];
    $file_name = $file_post['name'];
    $file_size = $file_post['size'];
    
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $unique_file_name = time() . '_' . uniqid() . '.' . $file_ext;
    $target_file = $target_dir . $unique_file_name;

    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_ext, $allowed_ext)) {
        return ['status' => 'error', 'message' => 'Ekstensi file tidak diizinkan. Hanya JPG, JPEG, PNG, dan GIF yang boleh diupload.'];
    }

    if ($file_size > 2 * 1024 * 1024) {
        return ['status' => 'error', 'message' => 'Ukuran file terlalu besar. Maksimal 2 MB.'];
    }
    
    if (move_uploaded_file($file_tmp, $target_file)) {
        return ['status' => 'success', 'message' => $unique_file_name];
    } else {
        return ['status' => 'error', 'message' => 'Gagal memindahkan file.'];
    }
}

if (isset($_POST['update_profil'])) {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    
    if (empty($nama_lengkap) || empty($username)) {
        $pesan = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> Nama lengkap dan username tidak boleh kosong!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
    } else {
        $stmt_check = mysqli_prepare($koneksi, "SELECT id_user FROM tb_user WHERE username = ? AND id_user != ?");
        mysqli_stmt_bind_param($stmt_check, "si", $username, $id_user);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        
        if (mysqli_num_rows($result_check) > 0) {
            $pesan = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> Username sudah digunakan oleh user lain!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
        } else {
            $query = "UPDATE tb_user SET nama_lengkap = ?, username = ? WHERE id_user = ?";
            $stmt = mysqli_prepare($koneksi, $query);
            mysqli_stmt_bind_param($stmt, "ssi", $nama_lengkap, $username, $id_user);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['nama_lengkap'] = $nama_lengkap;
                $_SESSION['username'] = $username;
                $pesan = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Profil berhasil diperbarui!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                          </div>';
            } else {
                $pesan = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> Gagal memperbarui profil!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                          </div>';
            }
        }
    }
}

if (isset($_POST['upload_foto'])) {
    $stmt_get = mysqli_prepare($koneksi, "SELECT foto FROM tb_user WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt_get, "i", $id_user);
    mysqli_stmt_execute($stmt_get);
    $result_get = mysqli_stmt_get_result($stmt_get);
    $row_get = mysqli_fetch_assoc($result_get);
    $foto_lama = $row_get['foto'];
    
    $upload_result = handleUploadFoto($_FILES['foto'], $upload_dir);
    
    if ($upload_result['status'] == 'success' && !empty($upload_result['message'])) {
        $foto_baru = $upload_result['message'];
        
        $query = "UPDATE tb_user SET foto = ? WHERE id_user = ?";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "si", $foto_baru, $id_user);
        
        if (mysqli_stmt_execute($stmt)) {
            if (!empty($foto_lama) && file_exists($upload_dir . $foto_lama)) {
                unlink($upload_dir . $foto_lama);
            }
            
            $_SESSION['foto'] = $foto_baru;
            $pesan = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> Foto profil berhasil diperbarui!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
        } else {
            if (!empty($foto_baru) && file_exists($upload_dir . $foto_baru)) {
                unlink($upload_dir . $foto_baru);
            }
            $pesan = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> Gagal memperbarui foto profil!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
        }
    } else {
        $pesan = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> ' . $upload_result['message'] . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
    }
}

if (isset($_POST['hapus_foto'])) {
    $stmt_get = mysqli_prepare($koneksi, "SELECT foto FROM tb_user WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt_get, "i", $id_user);
    mysqli_stmt_execute($stmt_get);
    $result_get = mysqli_stmt_get_result($stmt_get);
    $row_get = mysqli_fetch_assoc($result_get);
    $foto_lama = $row_get['foto'];
    
    $query = "UPDATE tb_user SET foto = NULL WHERE id_user = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    
    if (mysqli_stmt_execute($stmt)) {
        if (!empty($foto_lama) && file_exists($upload_dir . $foto_lama)) {
            unlink($upload_dir . $foto_lama);
        }
        
        $_SESSION['foto'] = null;
        $pesan = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Foto profil berhasil dihapus!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
    } else {
        $pesan = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> Gagal menghapus foto profil!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
    }
}

if (isset($_POST['ganti_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $password_konfirmasi = $_POST['password_konfirmasi'];
    
    if (empty($password_lama) || empty($password_baru) || empty($password_konfirmasi)) {
        $pesan = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> Semua field password harus diisi!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
    } elseif ($password_baru !== $password_konfirmasi) {
        $pesan = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> Password baru dan konfirmasi tidak cocok!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
    } elseif (strlen($password_baru) < 6) {
        $pesan = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> Password baru minimal 6 karakter!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
    } else {
        $stmt_check = mysqli_prepare($koneksi, "SELECT password FROM tb_user WHERE id_user = ?");
        mysqli_stmt_bind_param($stmt_check, "i", $id_user);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $row_check = mysqli_fetch_assoc($result_check);
        
        if (password_verify($password_lama, $row_check['password'])) {
            $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            
            $query = "UPDATE tb_user SET password = ? WHERE id_user = ?";
            $stmt = mysqli_prepare($koneksi, $query);
            mysqli_stmt_bind_param($stmt, "si", $password_hash, $id_user);
            
            if (mysqli_stmt_execute($stmt)) {
                $pesan = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Password berhasil diubah!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                          </div>';
            } else {
                $pesan = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> Gagal mengubah password!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                          </div>';
            }
        } else {
            $pesan = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> Password lama tidak sesuai!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
        }
    }
}

$stmt_user = mysqli_prepare($koneksi, "SELECT * FROM tb_user WHERE id_user = ?");
mysqli_stmt_bind_param($stmt_user, "i", $id_user);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$user_data = mysqli_fetch_assoc($result_user);

$foto_path = !empty($user_data['foto']) && file_exists($upload_dir . $user_data['foto']) 
    ? $upload_dir . $user_data['foto'] 
    : 'https://ui-avatars.com/api/?name=' . urlencode($user_data['nama_lengkap']) . '&size=200&background=4e73df&color=fff';
?>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-user-circle"></i> Profil Pengguna
        </h1>
    </div>

    <?php echo $pesan; ?>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-camera"></i> Foto Profil
                    </h6>
                </div>
                <div class="card-body text-center">
                    <img src="<?php echo $foto_path; ?>" 
                         alt="Foto Profil" 
                         class="img-fluid rounded-circle mb-3" 
                         style="width: 200px; height: 200px; object-fit: cover; border: 4px solid #4e73df;">
                    
                    <h5 class="mt-3"><?php echo htmlspecialchars($user_data['nama_lengkap']); ?></h5>
                    <p class="text-muted">@<?php echo htmlspecialchars($user_data['username']); ?></p>
                    <span class="badge bg-<?php echo $user_data['role'] == 'admin' ? 'danger' : 'info'; ?> mb-3">
                        <?php echo strtoupper($user_data['role']); ?>
                    </span>
                    
                    <hr>
                    
                    <form method="POST" action="profil.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <input type="file" class="form-control" name="foto" accept="image/*" required>
                            <small class="form-text text-muted">Maksimal 2 MB (JPG, JPEG, PNG, GIF)</small>
                        </div>
                        <button type="submit" name="upload_foto" class="btn btn-primary btn-sm me-2">
                            <i class="fas fa-upload"></i> Upload Foto
                        </button>
                        <?php if (!empty($user_data['foto'])) : ?>
                        <button type="submit" name="hapus_foto" class="btn btn-danger btn-sm" 
                                onclick="return confirm('Apakah Anda yakin ingin menghapus foto profil?')">
                            <i class="fas fa-trash"></i> Hapus Foto
                        </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-edit"></i> Update Profil
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="profil.php">
                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                   value="<?php echo htmlspecialchars($user_data['nama_lengkap']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <input type="text" class="form-control" id="role" 
                                   value="<?php echo strtoupper($user_data['role']); ?>" readonly disabled>
                            <small class="form-text text-muted">Role tidak dapat diubah sendiri.</small>
                        </div>
                        
                        <button type="submit" name="update_profil" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-key"></i> Ganti Password
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="profil.php">
                        <div class="mb-3">
                            <label for="password_lama" class="form-label">Password Lama <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_lama" name="password_lama" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_baru" class="form-label">Password Baru <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_baru" name="password_baru" required>
                            <small class="form-text text-muted">Minimal 6 karakter</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_konfirmasi" class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_konfirmasi" name="password_konfirmasi" required>
                        </div>
                        
                        <button type="submit" name="ganti_password" class="btn btn-warning">
                            <i class="fas fa-lock"></i> Ganti Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
include 'template/footer.php';
?>