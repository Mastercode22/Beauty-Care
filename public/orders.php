<?php
// public/orders.php
$page_title = 'My Orders - Glowing';
include_once '../includes/auth_guard.php'; // Protect this page
include_once '../includes/header.php';
include_once '../includes/navbar.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
?>

<style>
.orders-container { max-width: 960px; margin: 40px auto; padding: 20px; }
.order-list-table { width: 100%; border-collapse: collapse; }
.order-list-table th, .order-list-table td { padding: 15px; border-bottom: 1px solid var(--cultured); text-align: left; }
.order-list-table th { background-color: #f9f9f9; }
.order-list-table a { color: var(--black); text-decoration: underline; }
.status { padding: 5px 10px; border-radius: 20px; font-size: 12px; text-transform: capitalize; }
.status-pending_payment { background-color: #fef2e2; color: #b55f0a; }
.status-payment_submitted { background-color: #e2f2fe; color: #0a6fb5; }
.status-payment_confirmed { background-color: #e4f8e4; color: #3d8b3d; }
.status-shipped { background-color: #e3e4fa; color: #4046b6; }
.order-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 20px; }
.order-detail-box { padding: 20px; border: 1px solid var(--cultured); border-radius: var(--radius-8); }
.order-items-list .item { display: flex; align-items: center; gap: 15px; padding: 10px 0; border-bottom: 1px solid var(--cultured); }
.order-items-list .item:last-child { border-bottom: none; }
.order-items-list img { width: 60px; height: 60px; border-radius: var(--radius-8); object-fit: cover;}
</style>

<div class="orders-container" id="orders-page-content">
    <?php if ($order_id): ?>
        <!-- Detailed Order View -->
        <div id="order-detail-view"> 
             <a href="orders.php" class="btn-link" style="margin-bottom: 20px; display: inline-block;">&larr; Back to All Orders</a>
            <h1 class="h2">Order Details</h1>
            <div id="order-detail-data" style="margin-top: 20px;">Loading...</div>
        </div>
    <?php else: ?>
        <!-- Order List View -->
        <h1 class="h2" style="margin-bottom: 30px;">My Orders</h1>
        <div id="order-list-view">
            <table class="order-list-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="orders-tbody">
                    <!-- Orders will be loaded here -->
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const orderId = <?php echo json_encode($order_id); ?>;

    if (orderId) {
        // --- Fetch and display a single order's details ---
        fetch(`../api/orders.php?id=${orderId}`)
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('order-detail-data');
                if(data.status === 'success') {
                    const order = data.data;
                    let itemsHtml = '';
                    order.items.forEach(item => {
                        itemsHtml += `
                            <div class="item">
                                <img src="../assets/images/${item.image}" alt="${item.name}">
                                <div>
                                    <strong>${item.name}</strong><br>
                                    <span>Quantity: ${item.quantity}</span> | <span>Price: $${parseFloat(item.price).toFixed(2)}</span>
                                </div>
                            </div>
                        `;
                    });

                    container.innerHTML = `
                        <div class="order-detail-grid">
                            <div class="order-detail-box">
                                <h3 class="h4">Order #${order.id}</h3>
                                <p><strong>Date:</strong> ${new Date(order.created_at).toLocaleDateString()}</p>
                                <p><strong>Status:</strong> <span class="status status-${order.status.replace('_', '-')}">
${order.status.replace('_', ' ')}</span></p>
                                <p><strong>Total:</strong> <span class="h4">$${parseFloat(order.total_amount).toFixed(2)}</span></p>
                            </div>
                             <div class="order-detail-box">
                                <h3 class="h4">Shipping Address</h3>
                                <p>${order.shipping_address.replace(/\n/g, '<br>')}</p>
                            </div>
                        </div>
                        <div class="order-detail-box" style="margin-top: 20px;">
                             <h3 class="h4">Items</h3>
                            <div class="order-items-list">${itemsHtml}</div>
                        </div>
                    `;
                } else {
                    container.innerHTML = `<p>${data.message}</p>`;
                }
            });
    } else {
        // --- Fetch and display the list of all orders ---
        fetch('../api/orders.php')
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById('orders-tbody');
                if(data.status === 'success' && data.data.length > 0) {
                    tbody.innerHTML = '';
                     data.data.forEach(order => {
                        tbody.innerHTML += `
                            <tr>
                                <td>#${order.id}</td>
                                <td>${new Date(order.created_at).toLocaleDateString()}</td>
                                <td><span class="status status-${order.status.replace('_', '-')}">
${order.status.replace('_', ' ')}</span></td>
                                <td>$${parseFloat(order.total_amount).toFixed(2)}</td>
                                <td><a href="orders.php?id=${order.id}">View Details</a></td>
                            </tr>
                        `;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 40px;">You have not placed any orders yet.</td></tr>';
                }
            });
    }
});
</script>

<?php
include_once '../includes/footer.php';
?>
