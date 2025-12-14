<?php
// api/payments.php
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

if ($method === 'POST') {
    // --- USER SUBMITS PAYMENT CONFIRMATION ---
    if (!$user_id) {
        json_response('failed', 'Authentication required.', null, 401);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = $input['order_id'] ?? null;
    $network = $input['network'] ?? null;
    $amount = $input['amount'] ?? null;
    
    if (empty($order_id) || empty($network) || empty($amount)) {
        json_response('failed', 'Order ID, network, and amount are required.', null, 400);
    }

    try {
        $db->beginTransaction();

        // Verify the order belongs to the user and is pending payment
        $stmt = $db->prepare('SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = "pending_payment"');
        $stmt->execute([$order_id, $user_id]);
        if ($stmt->rowCount() == 0) {
             $db->rollBack();
             json_response('failed', 'Invalid order or order is not pending payment.', null, 400);
        }

        // 1. Create payment record
        $reference = 'MOMO-' . $order_id . '-' . time();
        $stmt = $db->prepare('INSERT INTO payments (order_id, reference, network, amount) VALUES (?, ?, ?, ?)');
        $stmt->execute([$order_id, $reference, $network, $amount]);
        
        // 2. Update order status
        $stmt = $db->prepare('UPDATE orders SET status = "payment_submitted" WHERE id = ?');
        $stmt->execute([$order_id]);
        
        $db->commit();
        json_response('success', 'Payment confirmation received. Your order is now being processed.');

    } catch (PDOException $e) {
        $db->rollBack();
        json_response('failed', 'Database error: ' . $e->getMessage(), null, 500);
    }

} elseif ($method === 'PUT' && is_admin()) {
    // --- ADMIN CONFIRMS PAYMENT ---
    $input = json_decode(file_get_contents('php://input'), true);
    $payment_id = $input['payment_id'] ?? null;
    $order_id = $input['order_id'] ?? null;
    
    if (empty($payment_id) || empty($order_id)) {
        json_response('failed', 'Payment ID and Order ID are required.', null, 400);
    }

    try {
        $db->beginTransaction();
        
        // 1. Update payment status
        $stmt = $db->prepare('UPDATE payments SET status = "confirmed" WHERE id = ?');
        $stmt->execute([$payment_id]);

        // 2. Update order status
        $stmt = $db->prepare('UPDATE orders SET status = "payment_confirmed" WHERE id = ?');
        $stmt->execute([$order_id]);

        if ($stmt->rowCount() == 0) {
            $db->rollBack();
            json_response('failed', 'Order not found.', null, 404);
        }

        $db->commit();
        json_response('success', 'Payment has been confirmed.');

    } catch (PDOException $e) {
        $db->rollBack();
        json_response('failed', 'Database error: ' . $e->getMessage(), null, 500);
    }

} else {
    json_response('failed', 'Method Not Allowed or Unauthorized.', null, 405);
}
?>
