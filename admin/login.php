<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill all fields';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, email, password FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            
            header('Location: ' . ADMIN_URL . '/index.php');
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <script>
    (function() {
        var theme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', theme);
        if (theme === 'dark') document.documentElement.classList.add('dark-mode');
    })();
    </script>
    <style>
    html.dark-mode .bg-light { background-color: #1e1e1e !important; }
    html.dark-mode body { background-color: #121212; color: #e0e0e0; }
    html.dark-mode .card { background-color: #1e1e1e; border-color: #2d2d2d; color: #e0e0e0; }
    html.dark-mode .form-control, html.dark-mode .form-select { background-color: #1e1e1e; border-color: #3d3d3d; color: #e0e0e0; }
    html.dark-mode .form-control::placeholder, html.dark-mode .form-select::placeholder { color: #888; opacity: 1; }
    html.dark-mode .form-control:focus, html.dark-mode .form-select:focus { background-color: #252525; border-color: #0d6efd; color: #e0e0e0; box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.35); }
    html.dark-mode .form-label { color: #ccc; }
    html.dark-mode .input-group-text { background-color: #2a2a2a; border-color: #3d3d3d; color: #aaa; }
    html.dark-mode .text-muted { color: #999 !important; }
    html.dark-mode .alert-info { background-color: #1a2a3a; border-color: #1e4976; color: #8ec5fc; }
    html.dark-mode a { color: #6ea8fe; }
    html.dark-mode .theme-toggle { color: #e0e0e0; }
    html.dark-mode .theme-toggle:hover { background-color: rgba(255,255,255,0.15); }
    </style>
</head>
<body class="bg-light">
    <div style="position:fixed;top:1rem;right:1rem;z-index:9999;">
        <button class="btn btn-outline-secondary rounded-circle theme-toggle" id="themeToggle" type="button" title="Toggle Dark Mode" style="width:40px;height:40px;display:inline-flex;align-items:center;justify-content:center;background:transparent;border-color:#6c757d;">
            <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
        </button>
    </div>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-shield-lock fs-1 text-primary"></i>
                            <h2 class="mt-3">Admin Login</h2>
                            <p class="text-muted">Access Admin Panel</p>
                        </div>

                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           placeholder="Enter admin email"
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required
                                           placeholder="Enter admin password">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="<?php echo SITE_URL; ?>/" class="text-decoration-none">
                                <i class="bi bi-arrow-left"></i> Back to Website
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function() {
        var toggle = document.getElementById('themeToggle');
        var icon = document.getElementById('themeIcon');
        if (!toggle || !icon) return;

        function updateIcon() {
            var isDark = document.documentElement.classList.contains('dark-mode');
            icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
            toggle.title = isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        }
        updateIcon();

        toggle.addEventListener('click', function() {
            var isDark = document.documentElement.classList.toggle('dark-mode');
            var theme = isDark ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            updateIcon();
        });
    })();
    </script>
</body>
</html>