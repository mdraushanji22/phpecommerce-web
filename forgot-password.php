<?php
$pageTitle = 'Forgot Password';
require_once __DIR__ . '/includes/header.php';

if (isUserLoggedIn()) {
    header('Location: ' . USER_URL . '/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', time() + 600);

            $stmt = $db->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$email]);

            $stmt = $db->prepare("INSERT INTO password_resets (email, code, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $code, $expiresAt]);

            $subject = SITE_NAME . ' - Password Reset Code';
            $message = "Hello " . htmlspecialchars($user['name']) . ",\n\n";
            $message .= "Your password reset verification code is:\n\n";
            $message .= $code . "\n\n";
            $message .= "This code is valid for 10 minutes.\n\n";
            $message .= "If you did not request a password reset, please ignore this email.\n\n";
            $message .= "Thanks,\n" . SITE_NAME . " Team";

            $headers = "From: " . SITE_NAME . " <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
            $headers .= "Reply-To: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            $mailSent = @mail($email, $subject, $message, $headers);

            $_SESSION['reset_email'] = $email;
            setFlashMessage('success', 'Verification code sent to your email address.');
            header('Location: ' . SITE_URL . '/verify-code.php');
            exit;
        } else {
            $error = 'No account found with that email address';
        }
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-2">Forgot Password?</h2>
                    <p class="text-center text-muted mb-4">Enter your email address and we'll send you a verification code to reset your password.</p>

                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   placeholder="Enter your registered email"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-send"></i> Send Verification Code
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <p>Remember your password? <a href="<?php echo SITE_URL; ?>/login.php">Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
