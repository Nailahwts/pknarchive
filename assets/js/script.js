document.addEventListener("DOMContentLoaded", function() {
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    
    // Fungsi untuk toggle sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        hamburgerBtn.classList.toggle('shifted');
    }

    // Klik tombol hamburger
    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', toggleSidebar);
    }

    // Klik overlay (untuk menutup sidebar)
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }

    // --- Logika untuk Dropdown Menu ---
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle-custom');

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault(); // Mencegah link pindah halaman
            
            // Tutup semua submenu lain
            document.querySelectorAll('.submenu.active').forEach(openSubmenu => {
                if (openSubmenu !== this.nextElementSibling) {
                    openSubmenu.classList.remove('active');
                    openSubmenu.previousElementSibling.classList.remove('active');
                }
            });

            // Buka/tutup submenu yang di-klik
            const submenu = this.nextElementSibling;
            submenu.classList.toggle('active');
            this.classList.toggle('active');
        });
    });
});