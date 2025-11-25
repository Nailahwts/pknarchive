<?php
include 'koneksi.php';

echo "Testing database connection...\n";

if ($koneksi) {
    echo "Connection successful.\n";

    // Test query
    $result = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_pinjaman_modal");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "Total records in tb_pinjaman_modal: " . $row['total'] . "\n";
    } else {
        echo "Query failed: " . mysqli_error($koneksi) . "\n";
    }

    // Check if table exists
    $result = mysqli_query($koneksi, "SHOW TABLES LIKE 'tb_pinjaman_modal'");
    if (mysqli_num_rows($result) > 0) {
        echo "Table tb_pinjaman_modal exists.\n";
    } else {
        echo "Table tb_pinjaman_modal does not exist.\n";
    }

    // Check unique key
    $result = mysqli_query($koneksi, "SHOW INDEX FROM tb_pinjaman_modal WHERE Column_name = 'nomer_kontrak'");
    if (mysqli_num_rows($result) > 0) {
        echo "Unique key on nomer_kontrak exists.\n";
    } else {
        echo "No unique key on nomer_kontrak.\n";
    }

} else {
    echo "Connection failed: " . mysqli_connect_error() . "\n";
}
?>
