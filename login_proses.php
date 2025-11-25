<?php
session_start();

include 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 4. Ambil data dari form login
    $username_input = $_POST['username'];
    $password_input = $_POST['password'];

    // 5. Buat query menggunakan PREPARED STATEMENT (Aman dari SQL Injection)
    // MODIFIKASI: Ambil kolom 'role' juga
    $query = "SELECT id_user, nama_lengkap, username, password, role FROM tb_user WHERE username = ?";
    
    // 6. Siapkan statement
    $stmt = mysqli_prepare($koneksi, $query);
    
    if ($stmt) {
        // 7. Bind parameter (tipe "s" untuk string) ke statement
        mysqli_stmt_bind_param($stmt, "s", $username_input);
        
        // 8. Eksekusi statement
        mysqli_stmt_execute($stmt);
        
        // 9. Ambil hasil query
        $result = mysqli_stmt_get_result($stmt);
        
        // 10. Cek apakah user-nya ditemukan (seharusnya hanya 1 baris)
        if ($row = mysqli_fetch_assoc($result)) {
            
            // 11. User ditemukan. Verifikasi password.
            $hash_password_db = $row['password'];
            
            if (password_verify($password_input, $hash_password_db)) {
                // === LOGIN BERHASIL ===
                
                // 12. Regenerasi session id (untuk keamanan tambahan)
                session_regenerate_id(true);
                
                // 13. Simpan data user yang penting ke dalam SESSION
                $_SESSION['id_user'] = $row['id_user'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
                
                // MODIFIKASI: Simpan role ke session
                $_SESSION['role'] = $row['role']; 
                
                // 14. Alihkan (redirect) user ke halaman dashboard
                header("Location: dashboard.php");
                exit; // Penting: Hentikan eksekusi script setelah redirect
                
            } else {
                // === LOGIN GAGAL (Password Salah) ===
                header("Location: login.php?error=1");
                exit;
            }
            
        } else {
            // === LOGIN GAGAL (Username tidak ditemukan) ===
            header("Location: login.php?error=1");
            exit;
        }
        
        // 15. Tutup statement
        mysqli_stmt_close($stmt);
        
    } else {
        // Gagal menyiapkan statement
        die("Query error (prepare): " . mysqli_error($koneksi));
    }
    
    // 16. Tutup koneksi
    mysqli_close($koneksi);

} else {
    // Jika file ini diakses langsung (bukan via POST), tendang ke halaman login
    header("Location: login.php");
    exit;
}
?>