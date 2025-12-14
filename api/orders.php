<?php
// api/orders.php
header('Content-Type: application/json');
include_once '../config/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function json_response($status = 'failed', $message = 'An error occurred.', $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

function is_admin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$user_id = $_SESSION['user_id'] ?? null;

// All endpoints require a logged-in user
if (!$user_id) {
    json_response('failed', 'Authentication required.', null, 401);
}

try {
    if ($method === 'POST') {
        // --- CREATE ORDER ---
        $input = json_decode(file_get_contents('php://input'), true);
        $shipping_address = $input['shipping_address'] ?? null;

        if (empty($shipping_address)) {
            json_response('failed', 'Shipping address is required.', null, 400);
        }

        // Start transaction
        $db->beginTransaction();

        // 1. Get cart items for the user
        $stmt = $db->prepare('SELECT ci.product_id, ci.quantity, p.price, p.stock_quantity FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = ?');
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cart_items)) {
            json_response('failed', 'Your cart is empty.', null, 400);
        }

        // 2. Calculate total amount and check stock
        $total_amount = 0;
        foreach ($cart_items as $item) {
            if ($item['quantity'] > $item['stock_quantity']) {
                $db->rollBack();
                json_response('failed', "Not enough stock for product ID {$item['product_id']}. Available: {$item['stock_quantity']}", null, 400);
            }
            $total_amount += $item['price'] * $item['quantity'];
        }

        // 3. Create order in `orders` table
        $stmt = $db->prepare('INSERT INTO orders (user_id, total_amount, shipping_address) VALUES (?, ?, ?)');
        $stmt->execute([$user_id, $total_amount, $shipping_address]);
        $order_id = $db->lastInsertId();

        // 4. Insert into `order_items` and update stock
        $stmt_order_items = $db->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
        $stmt_update_stock = $db->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?');
        
        foreach ($cart_items as $item) {
            $stmt_order_items->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            $stmt_update_stock->execute([$item['quantity'], $item['product_id']]);
        }
        
        // 5. Clear user's cart
        $stmt = $db->prepare('DELETE FROM cart_items WHERE user_id = ?');
        $stmt->execute([$user_id]);

        // Commit transaction
        $db->commit();
        
        json_response('success', 'Order placed successfully.', ['order_id' => $order_id]);

    } elseif ($method === 'GET') {
        // --- FETCH ORDERS ---
        if (isset($_GET['id'])) {
            // Fetch single order
            $order_id = (int)$_GET['id'];
            $query = 'SELECT * FROM orders WHERE id = ? AND user_id = ?';
            if (is_admin()) { // Admin can see any order
                 $query = 'SELECT * FROM orders WHERE id = ?';
            }
            $stmt = $db->prepare($query);
            $params = is_admin() ? [$order_id] : [$order_id, $user_id];
            $stmt->execute($params);
            
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$order) json_response('failed', 'Order not found.', null, 404);

            // Fetch order items
            $stmt = $db->prepare('SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
            $stmt->execute([$order_id]);
            $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            json_response('success', 'Order details retrieved.', $order);

        } else {
            // Fetch all orders for the user
            $query = 'SELECT id, total_amount, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC';
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            json_response('success', 'Orders retrieved.', $orders);
        }
    } elseif ($method === 'PUT' && is_admin()) {
        // --- UPDATE ORDER STATUS (Admin only) ---
        $input = json_decode(file_get_contents('php://input'), true);
        $order_id = $input['order_id'] ?? null;
        $status = $input['status'] ?? null;

        if (empty($order_id) || empty($status)) {
            json_response('failed', 'Order ID and status are required.', null, 400);
        }

        $stmt = $db->prepare('UPDATE orders SET status = ? WHERE id = ?');
        if ($stmt->execute([$status, $order_id])) {
            if ($stmt->rowCount() > 0) {
                 json_response('success', 'Order status updated.');
            } else {
                 json_response('failed', 'Order not found or status is already the same.', null, 404);
            }
        } else {
            json_response('failed', 'Failed to update order status.', null, 500);
        }
    } else {
        json_response('failed', 'Method Not Allowed or Unauthorized.', null, 405);
    }
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    json_response('failed', 'Database error: ' . $e->getMessage(), null, 500);
}
?>
