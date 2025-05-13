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

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $order_id = $_POST['order_id'];
                $status = $_POST['status'];
                $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->execute([$status, $order_id]);
                break;

            case 'assign_driver':
                $order_id = $_POST['order_id'];
                $driver_id = $_POST['driver_id'];
                $stmt = $db->prepare("UPDATE orders SET driver_id = ? WHERE id = ?");
                $stmt->execute([$driver_id, $order_id]);
                break;
        }
    }
}

// Get all orders with user and driver information
try {
    $stmt = $db->query("
        SELECT o.*, 
               u.name as user_name, 
               u.phone as user_phone,
               u.location as user_location,
               d.name as driver_name,
               d.phone as driver_phone
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN drivers d ON o.driver_id = d.id
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If tables don't exist, set empty array
    $orders = [];
}

// Get all drivers for assignment
try {
    $stmt = $db->query("SELECT * FROM drivers WHERE is_available = 1");
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If drivers table doesn't exist, set empty array
    $drivers = [];
}

// Check if required tables exist
$tables_exist = true;
try {
    $db->query("SELECT 1 FROM orders LIMIT 1");
    $db->query("SELECT 1 FROM users LIMIT 1");
    $db->query("SELECT 1 FROM drivers LIMIT 1");
} catch (PDOException $e) {
    $tables_exist = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biryo Byihuse - Order Management</title>
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
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="orders.php">Orders</a>
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
        <?php if (!$tables_exist): ?>
        <div class="alert alert-warning">
            <h4 class="alert-heading">Database Setup Required</h4>
            <p>Some required database tables are missing. Please run the following SQL commands to set up the database:</p>
            <pre class="mb-0">
-- Create drivers table
CREATE TABLE IF NOT EXISTS `drivers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `vehicle_type` varchar(50) NOT NULL,
  `vehicle_number` varchar(20) NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample drivers
INSERT INTO `drivers` (`name`, `phone`, `vehicle_type`, `vehicle_number`, `is_available`) VALUES
('John Doe', '+250788123456', 'Motorcycle', 'MOTO123', 1),
('Jane Smith', '+250788234567', 'Bicycle', 'BIKE456', 1),
('Robert Johnson', '+250788345678', 'Motorcycle', 'MOTO789', 1);
            </pre>
            <hr>
            <p class="mb-0">You can run these commands in phpMyAdmin or using the MySQL command line.</p>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Orders</h5>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                <div class="alert alert-info">
                    No orders found.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Driver</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    #<?php echo $order['id']; ?>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['user_name']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($order['user_phone']); ?>
                                        <br>
                                        <?php echo htmlspecialchars($order['user_location']); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php
                                    try {
                                        $stmt = $db->prepare("
                                            SELECT oi.*, m.name as item_name 
                                            FROM order_items oi 
                                            JOIN menu_items m ON oi.menu_item_id = m.id 
                                            WHERE oi.order_id = ?
                                        ");
                                        $stmt->execute([$order['id']]);
                                        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($items as $item) {
                                            echo htmlspecialchars($item['item_name']) . ' x ' . $item['quantity'] . '<br>';
                                        }
                                    } catch (PDOException $e) {
                                        echo '<span class="text-muted">Items not available</span>';
                                    }
                                    ?>
                                </td>
                                <td>RWF <?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($order['status']) {
                                            'pending' => 'warning',
                                            'confirmed' => 'info',
                                            'preparing' => 'primary',
                                            'ready' => 'success',
                                            'delivered' => 'secondary',
                                            'cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($order['driver_name']): ?>
                                        <strong><?php echo htmlspecialchars($order['driver_name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($order['driver_phone']); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#updateStatusModal" 
                                                   data-order-id="<?php echo $order['id']; ?>"
                                                   data-current-status="<?php echo $order['status']; ?>">
                                                    Update Status
                                                </a>
                                            </li>
                                            <?php if ($order['status'] === 'ready' && !$order['driver_id']): ?>
                                            <li>
                                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#assignDriverModal"
                                                   data-order-id="<?php echo $order['id']; ?>">
                                                    Assign Driver
                                                </a>
                                            </li>
                                            <?php endif; ?>
                                            <li>
                                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewOrderModal"
                                                   data-order-id="<?php echo $order['id']; ?>">
                                                    View Details
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" id="statusOrderId">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="preparing">Preparing</option>
                                <option value="ready">Ready</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Driver Modal -->
    <div class="modal fade" id="assignDriverModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Driver</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="assign_driver">
                        <input type="hidden" name="order_id" id="driverOrderId">
                        <div class="mb-3">
                            <label class="form-label">Select Driver</label>
                            <select class="form-select" name="driver_id" required>
                                <option value="">Choose a driver...</option>
                                <?php foreach ($drivers as $driver): ?>
                                <option value="<?php echo $driver['id']; ?>">
                                    <?php echo htmlspecialchars($driver['name']); ?> 
                                    (<?php echo htmlspecialchars($driver['phone']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Assign Driver</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Order Modal -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="orderDetails">
                        <!-- Order details will be loaded here via AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle status update modal
        document.querySelectorAll('[data-bs-target="#updateStatusModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                const currentStatus = this.dataset.currentStatus;
                document.getElementById('statusOrderId').value = orderId;
                document.querySelector('#updateStatusModal select[name="status"]').value = currentStatus;
            });
        });

        // Handle driver assignment modal
        document.querySelectorAll('[data-bs-target="#assignDriverModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                document.getElementById('driverOrderId').value = orderId;
            });
        });

        // Handle view order modal
        document.querySelectorAll('[data-bs-target="#viewOrderModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                // Load order details via AJAX
                fetch(`get_order_details.php?id=${orderId}`)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('orderDetails').innerHTML = html;
                    });
            });
        });
    </script>
</body>
</html> 