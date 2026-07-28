    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>

    <!-- Theme Toggle -->
    <script>
    (function() {
        var toggle = document.getElementById('themeToggle');
        var icon = document.getElementById('themeIcon');
        if (!toggle || !icon) return;

        function updateIcon() {
            var isDark = document.body.classList.contains('dark-mode');
            icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
            toggle.title = isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        }
        updateIcon();

        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            var isDark = document.body.classList.toggle('dark-mode');
            document.documentElement.classList.toggle('dark-mode', isDark);
            var theme = isDark ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            updateIcon();
        });

        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                if (!localStorage.getItem('theme')) {
                    var theme = e.matches ? 'dark' : 'light';
                    document.body.classList.toggle('dark-mode', e.matches);
                    document.documentElement.classList.toggle('dark-mode', e.matches);
                    document.documentElement.setAttribute('data-theme', theme);
                    updateIcon();
                }
            });
        }
    })();
    </script>
</body>
</html>
