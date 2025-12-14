<?php
include_once '../includes/admin_guard.php';
$page_title = 'Admin Dashboard - Glowing';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        .admin-nav { display: flex; gap: 20px; padding: 20px; background-color: #f9f9f9; border-radius: 8px; margin-bottom: 30px; justify-content: center; }
        .stat-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background-color: var(--ghost-white); padding: 25px; border-radius: 12px; }
        .stat-card h3 { font-size: var(--fs-6); color: var(--sonic-silver); margin-bottom: 10px; }
        .stat-card p { font-size: var(--fs-2); font-weight: var(--weight-bold); }
        .recent-orders-table { width: 100%; border-collapse: collapse; }
        .recent-orders-table th, .recent-orders-table td { padding: 15px; border-bottom: 1px solid var(--cultured); text-align: left; }
        .status { padding: 5px 10px; border-radius: 20px; font-size: 12px; text-transform: capitalize; background-color: #eee; }
        .status-pending_payment { background-color: #fef2e2; color: #b55f0a; }
        .status-payment_submitted { background-color: #e2f2fe; color: #0a6fb5; }
        .status-payment_confirmed { background-color: #e4f8e4; color: #3d8b3d; }
    </style>
</head>
<body>

<div class="admin-container">
    <h1 class="h2" style="text-align: center; margin-bottom: 20px;">Admin Dashboard</h1>
    <p style="text-align: center; margin-bottom: 30px;">Welcome, <?php echo htmlspecialchars($_SESSION['user_firstname']); ?>!</p>

    <nav class="admin-nav">
        <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
        <a href="products.php" class="btn btn-secondary">Manage Products</a>
        <a href="categories.php" class="btn btn-secondary">Manage Categories</a>
        <a href="orders.php" class="btn btn-secondary">Manage Orders</a>
        <a href="../public/logout.php" class="btn btn-secondary" style="background-color: var(--flame-pea); color: white;">Logout</a>
    </nav>

    <!-- Stats Cards -->
    <div class="stat-cards" id="stat-cards-container">
        <!-- Stats will be loaded here -->
    </div>

    <!-- Recent Orders -->
    <div>
        <h2 class="h3" style="margin-bottom: 20px;">Recent Orders</h2>
        <table class="recent-orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody id="recent-orders-tbody">
                <!-- Recent orders will be loaded here -->
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('../api/dashboard.php')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const stats = data.data;
                // Populate stat cards
                document.getElementById('stat-cards-container').innerHTML = `
                    <div class="stat-card">
                        <h3>Total Sales</h3>
                        <p>$${parseFloat(stats.total_sales).toFixed(2)}</p>
                    </div>
                    <div class="stat-card">
                        <h3>Total Orders</h3>
                        <p>${stats.total_orders}</p>
                    </div>
                    <div class="stat-card">
                        <h3>Total Customers</h3>
                        <p>${stats.total_users}</p>
                    </div>
                    <div class="stat-card">
                        <h3>Total Products</h3>
                        <p>${stats.total_products}</p>
                    </div>
                `;

                // Populate recent orders
                const ordersTbody = document.getElementById('recent-orders-tbody');
                ordersTbody.innerHTML = '';
                stats.recent_orders.forEach(order => {
                    ordersTbody.innerHTML += `
                        <tr>
                            <td>#${order.id}</td>
                            <td>${order.firstname} ${order.lastname}</td>
                            <td>${new Date(order.created_at).toLocaleDateString()}</td>
                            <td><span class="status status-${order.status.replace('_', '-')}">${order.status.replace('_', ' ')}</span></td>
                            <td>$${parseFloat(order.total_amount).toFixed(2)}</td>
                        </tr>
                    `;
                });
            }
        });
});
</script>

</body>
</html>
