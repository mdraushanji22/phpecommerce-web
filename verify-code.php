<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (isUserLoggedIn()) {
    header('Location: ' . USER_URL . '/dashboard.php');
    exit;
}

if (!isset($_SESSION['reset_email'])) {
    header('Location: ' . SITE_URL . '/forgot-password.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = sanitize($_POST['code'] ?? '');
    $email = $_SESSION['reset_email'];

    if (empty($code)) {
        $error = 'Please enter the verification code';
    } elseif (strlen($code) !== 6 || !ctype_digit($code)) {
        $error = 'Please enter a valid 6-digit code';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM password_resets WHERE email = ? AND code = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$email, $code]);
        $reset = $stmt->fetch();

        if ($reset) {
            $_SESSION['reset_verified'] = true;
            setFlashMessage('success', 'Code verified successfully. Please set your new password.');
            header('Location: ' . SITE_URL . '/reset-password.php');
            exit;
        } else {
            $error = 'Invalid or expired verification code';
        }
    }
}

$pageTitle = 'Verify Code';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-2">Verify Code</h2>
                    <p class="text-center text-muted mb-4">Enter the 6-digit verification code sent to <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong></p>

                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="code" class="form-label">Verification Code</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                <input type="text" class="form-control text-center fw-bold" id="code" name="code"
                                       maxlength="6" pattern="[0-9]{6}" required
                                       placeholder="Enter 6-digit code"
                                       style="font-size: 1.5rem; letter-spacing: 0.5rem;"
                                       value="<?php echo isset($_POST['code']) ? htmlspecialchars($_POST['code']) : ''; ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle"></i> Verify Code
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <p><a href="<?php echo SITE_URL; ?>/forgot-password.php">Back to Forgot Password</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
