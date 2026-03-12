<?php
$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/admin_header.php';

requireAdminLogin();

$db = getDB();

// Get all users
$users = $db->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders,
           (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE user_id = u.id AND order_status = 'completed') as total_spent
    FROM users u
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<div class="container-fluid my-4">
    <h2 class="mb-4">Manage Users</h2>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Total Orders</th>
                            <th>Total Spent</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['mobile']); ?></td>
                            <td><?php echo $user['total_orders']; ?></td>
                            <td><?php echo formatPrice($user['total_spent']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
