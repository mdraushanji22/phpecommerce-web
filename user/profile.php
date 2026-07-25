<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireUserLogin();

$pageTitle = 'Profile';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$userId = $_SESSION['user_id'];

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $mobile = sanitize($_POST['mobile'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $pincode = sanitize($_POST['pincode'] ?? '');

    if (empty($name) || empty($mobile)) {
        $error = 'Name and mobile are required';
    } else {
        $stmt = $db->prepare("
            UPDATE users 
            SET name = ?, mobile = ?, address = ?, city = ?, state = ?, pincode = ?
            WHERE id = ?
        ");
        if ($stmt->execute([$name, $mobile, $address, $city, $state, $pincode, $userId])) {
            $_SESSION['user_name'] = $name;
            $success = 'Profile updated successfully';
            // Refresh user data
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        } else {
            $error = 'Failed to update profile';
        }
    }
}
?>

<div class="container my-5">
    <h2 class="mb-4"><i class="bi bi-person"></i> My Profile</h2>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                <input type="text" class="form-control" id="name" name="name" required
                                       placeholder="Enter your full name"
                                       value="<?php echo htmlspecialchars($user['name']); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            </div>
                            <small class="text-muted">Email cannot be changed</small>
                        </div>

                        <div class="mb-3">
                            <label for="mobile" class="form-label">Mobile Number *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone-fill"></i></span>
                                <input type="text" class="form-control" id="mobile" name="mobile" required
                                       placeholder="Enter your mobile number"
                                       value="<?php echo htmlspecialchars($user['mobile']); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt-fill"></i></span>
                                <textarea class="form-control" id="address" name="address" rows="3"
                                          placeholder="Enter your full address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-building"></i></span>
                                    <input type="text" class="form-control" id="city" name="city"
                                           placeholder="Enter city"
                                           value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="state" class="form-label">State</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-map"></i></span>
                                    <input type="text" class="form-control" id="state" name="state"
                                           placeholder="Enter state"
                                           value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="pincode" class="form-label">Pincode</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-pin-map-fill"></i></span>
                                <input type="text" class="form-control" id="pincode" name="pincode"
                                       placeholder="Enter pincode"
                                       value="<?php echo htmlspecialchars($user['pincode'] ?? ''); ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Account Info</h5>
                </div>
                <div class="card-body">
                    <p><strong>Member Since:</strong><br><?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                    <p><strong>Last Updated:</strong><br><?php echo date('M d, Y', strtotime($user['updated_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
