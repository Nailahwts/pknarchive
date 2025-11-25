<?php
include 'koneksi.php';

// Simulate POST data
$_POST['tambah'] = '1';
$_POST['id_kategori'] = '1';
$_POST['nomer_kontrak'] = 'TEST123';
$_POST['dari'] = 'Test Pemberi';
$_POST['nominal_pinjaman'] = '1000000';
$_POST['tanggal_pinjaman'] = '2023-11-20';

// Simulate no file upload
$_FILES['berkas'] = [
    'name' => '',
    'type' => '',
    'tmp_name' => '',
    'error' => UPLOAD_ERR_NO_FILE,
    'size' => 0
];

// Simulate role
$role = 'admin';

// Simulate the insertion logic from pinjaman_modal.php
if (isset($_POST['tambah']) && $role == 'admin') {
    $id_kategori = (int)$_POST['id_kategori'];
    $nomer_kontrak = htmlspecialchars(trim($_POST['nomer_kontrak']));
    $dari = htmlspecialchars(trim($_POST['dari']));
    $nominal_pinjaman = (float)str_replace(['.', ','], '', $_POST['nominal_pinjaman']);
    $tanggal_pinjaman = $_POST['tanggal_pinjaman'];

    // No file upload
    $upload_result = ['status' => 'success', 'message' => null];

    if ($upload_result['status'] == 'success') {
        $berkas_nama = $upload_result['message'];

        $query = "INSERT INTO tb_pinjaman_modal (id_kategori, nomer_kontrak, dari, nominal_pinjaman, tanggal_pinjaman, berkas)
                  VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($koneksi, $query);

        if ($stmt === false) {
            echo "Gagal mempersiapkan statement SQL: " . mysqli_error($koneksi) . "\n";
        } else {
            mysqli_stmt_bind_param($stmt, "issdss", $id_kategori, $nomer_kontrak, $dari, $nominal_pinjaman, $tanggal_pinjaman, $berkas_nama);

            if (mysqli_stmt_execute($stmt)) {
                echo "Data berhasil ditambahkan.\n";
            } else {
                echo "Gagal menyimpan data: " . mysqli_stmt_error($stmt) . "\n";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        echo "Upload error: " . $upload_result['message'] . "\n";
    }
} else {
    echo "Role not admin or POST not set.\n";
}
?>
