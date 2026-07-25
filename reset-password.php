<?php
$pageTitle = 'Reset Password';
require_once __DIR__ . '/includes/header.php';

if (isUserLoggedIn()) {
    header('Location: ' . USER_URL . '/dashboard.php');
    exit;
}

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_verified'])) {
    header('Location: ' . SITE_URL . '/forgot-password.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $email = $_SESSION['reset_email'];

    if (empty($password) || empty($confirmPassword)) {
        $error = 'Please fill all fields';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $db = getDB();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);

        $stmt = $db->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);

        unset($_SESSION['reset_email'], $_SESSION['reset_verified']);

        setFlashMessage('success', 'Password reset successful! Please login with your new password.');
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-2">Reset Password</h2>
                    <p class="text-center text-muted mb-4">Create a new password for <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong></p>

                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required
                                       placeholder="Enter new password (min 6 characters)">
                            </div>
                            <small class="form-text text-muted">Minimum 6 characters</small>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-shield-lock-fill"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                                       placeholder="Re-enter new password">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-shield-lock"></i> Reset Password
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <p><a href="<?php echo SITE_URL; ?>/login.php">Back to Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
