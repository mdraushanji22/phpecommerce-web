<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isUserLoggedIn()) {
    header('Location: ' . USER_URL . '/dashboard.php');
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
        $stmt = $db->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            $_SESSION['show_location_prompt'] = true;
            setFlashMessage('success', 'Login successful! Welcome back, ' . $user['name']);
            header('Location: ' . SITE_URL . '/');
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    }
}

$pageTitle = t('login', 'Login');
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4"><?php echo t('login_to_account', 'Login to your account'); ?></h2>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label"><?php echo t('email', 'Email'); ?> Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       placeholder="Enter your email address"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required
                                       placeholder="Enter your password">
                            </div>
                        </div>
                        <div class="text-end mb-3">
                            <a href="<?php echo SITE_URL; ?>/forgot-password.php" class="text-decoration-none small"><?php echo t('forgot_password', 'Forgot Password?'); ?></a>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><?php echo t('login', 'Login'); ?></button>
                    </form>

                    <div class="text-center mt-3">
                        <p><?php echo t('dont_have_account', "Don't have an account?"); ?> <a href="<?php echo SITE_URL; ?>/signup.php"><?php echo t('sign_up', 'Sign Up'); ?></a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
