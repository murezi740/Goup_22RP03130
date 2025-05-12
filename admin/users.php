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

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_location':
                $userId = $_POST['user_id'];
                $location = $_POST['location'];
                $stmt = $db->prepare("UPDATE users SET location = ? WHERE id = ?");
                $stmt->execute([$location, $userId]);
                break;
            
            case 'update_name':
                $userId = $_POST['user_id'];
                $name = $_POST['name'];
                $stmt = $db->prepare("UPDATE users SET name = ? WHERE id = ?");
                $stmt->execute([$name, $userId]);
                break;
        }
    }
}

// Get all users with their order counts
$stmt = $db->query("
    SELECT u.*, 
           COUNT(o.id) as total_orders,
           MAX(o.created_at) as last_order_date
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biryo Byihuse - User Management</title>
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
                        <a class="nav-link" href="orders.php">Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php">Users</a>
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
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">User Management</h5>
                <div class="input-group" style="width: 300px;">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search users...">
                    <button class="btn btn-outline-secondary" type="button">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Phone Number</th>
                                <th>Location</th>
                                <th>Total Orders</th>
                                <th>Last Order</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <span class="editable" data-type="name" data-id="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['phone_number']; ?></td>
                                <td>
                                    <span class="editable" data-type="location" data-id="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['location'] ?? 'Not set'); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['total_orders']; ?></td>
                                <td>
                                    <?php 
                                    if ($user['last_order_date']) {
                                        echo date('M d, Y H:i', strtotime($user['last_order_date']));
                                    } else {
                                        echo 'No orders';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary view-orders" data-id="<?php echo $user['id']; ?>">
                                        <i class="bi bi-list-ul"></i> Orders
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="POST">
                        <input type="hidden" name="action" id="editAction">
                        <input type="hidden" name="user_id" id="editUserId">
                        <div class="mb-3">
                            <label class="form-label" id="editLabel"></label>
                            <input type="text" class="form-control" id="editValue" name="editValue" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Make editable fields clickable
        document.querySelectorAll('.editable').forEach(element => {
            element.addEventListener('click', function() {
                const type = this.dataset.type;
                const id = this.dataset.id;
                const value = this.textContent.trim();
                
                document.getElementById('editAction').value = 'update_' + type;
                document.getElementById('editUserId').value = id;
                document.getElementById('editLabel').textContent = 'Edit ' + type.charAt(0).toUpperCase() + type.slice(1);
                document.getElementById('editValue').value = value;
                
                new bootstrap.Modal(document.getElementById('editModal')).show();
            });
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });
    </script>
</body>
</html> 