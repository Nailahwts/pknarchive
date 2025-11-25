</div> <!-- End main-content -->

    <!-- Footer -->
    <footer style="background: #ffffff; padding: 15px; text-align: center; margin-top: 100px; border-top: 2px solid #e0e0e0; margin-left: 50px; margin-right: 50px;">
        <p style="margin: 0; color: #6c757d; font-size: 14px;">
            &copy; <?php echo date('Y'); ?> Sistem Arsip Surat. All rights reserved.
        </p>
    </footer>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script untuk Sidebar dan Dropdown -->
    <script>
        $(document).ready(function() {
            // Toggle Sidebar
            $('#hamburgerBtn').click(function() {
                $('#sidebar').toggleClass('active');
                $('#overlay').toggleClass('active');
                $(this).toggleClass('shifted');
            });

            // Close sidebar when overlay clicked
            $('#overlay').click(function() {
                $('#sidebar').removeClass('active');
                $(this).removeClass('active');
                $('#hamburgerBtn').removeClass('shifted');
            });

            // Dropdown Toggle
            $('.dropdown-toggle-custom').click(function(e) {
                e.preventDefault();
                
                // Close other dropdowns
                $('.dropdown-toggle-custom').not(this).removeClass('active');
                $('.submenu').not($(this).next('.submenu')).removeClass('active');
                
                // Toggle current dropdown
                $(this).toggleClass('active');
                $(this).next('.submenu').toggleClass('active');
            });

            // Auto-expand active menu
            $('.sidebar-menu li a').each(function() {
                var currentUrl = window.location.href;
                var linkUrl = $(this).attr('href');
                
                if (currentUrl.indexOf(linkUrl) !== -1 && linkUrl !== '#') {
                    // Highlight active link
                    $(this).css({
                        'background': 'rgba(13, 110, 253, 0.2)',
                        'border-left': '4px solid #0d6efd',
                        'padding-left': '28px'
                    });
                    
                    // If it's inside a submenu, open the parent dropdown
                    if ($(this).closest('.submenu').length) {
                        $(this).closest('.submenu').prev('.dropdown-toggle-custom').addClass('active');
                        $(this).closest('.submenu').addClass('active');
                    }
                }
            });

            // Prevent body scroll when sidebar is open
            $('#sidebar').on('show.bs.collapse', function() {
                $('body').css('overflow', 'hidden');
            });
            
            $('#sidebar').on('hide.bs.collapse', function() {
                $('body').css('overflow', 'auto');
            });
        });

        // Close sidebar on window resize
        $(window).resize(function() {
            if ($(window).width() > 768) {
                $('#sidebar').removeClass('active');
                $('#overlay').removeClass('active');
                $('#hamburgerBtn').removeClass('shifted');
            }
        });
    </script>

    <!-- Script khusus per halaman (opsional) -->
    <?php if (isset($additional_scripts)): ?>
        <?php echo $additional_scripts; ?>
    <?php endif; ?>
</body>
</html>