<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Get database connection
$db = getDBConnection();

// Get recent orders
$stmt = $db->query("SELECT o.*, u.phone_number, u.name 
                   FROM orders o 
                   JOIN users u ON o.user_id = u.id 
                   ORDER BY o.created_at DESC 
                   LIMIT 10");
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get menu statistics
$stmt = $db->query("SELECT COUNT(*) as total_items, 
                   SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as available_items 
                   FROM menu_items");
$menuStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get order statistics
$stmt = $db->query("SELECT COUNT(*) as total_orders,
                   SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders
                   FROM orders");
$orderStats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biryo Byihuse - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Biryo Byihuse Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Users</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders</h5>
                        <h2 class="card-text"><?php echo $orderStats['total_orders']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Delivered Orders</h5>
                        <h2 class="card-text"><?php echo $orderStats['delivered_orders']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Menu Items</h5>
                        <h2 class="card-text"><?php echo $menuStats['total_items']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Available Items</h5>
                        <h2 class="card-text"><?php echo $menuStats['available_items']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Orders</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['name']); ?><br>
                                    <small class="text-muted"><?php echo $order['phone_number']; ?></small>
                                </td>
                                <td>RWF <?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($order['status']) {
                                            'pending' => 'warning',
                                            'confirmed' => 'info',
                                            'preparing' => 'primary',
                                            'ready' => 'success',
                                            'delivering' => 'info',
                                            'delivered' => 'success',
                                            'cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 