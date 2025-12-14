<?php
// api/dashboard.php
header('Content-Type: application/json');
include_once '../config/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Admin check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stats = [];

    // Total sales (sum of completed orders)
    $stmt = $db->query("SELECT SUM(total_amount) as total_sales FROM orders WHERE status IN ('payment_confirmed', 'shipped', 'completed')");
    $stats['total_sales'] = $stmt->fetchColumn() ?: 0;

    // Number of orders
    $stmt = $db->query("SELECT COUNT(id) as total_orders FROM orders");
    $stats['total_orders'] = $stmt->fetchColumn() ?: 0;

    // Number of users
    $stmt = $db->query("SELECT COUNT(id) as total_users FROM users WHERE role = 'user'");
    $stats['total_users'] = $stmt->fetchColumn() ?: 0;

    // Number of products
    $stmt = $db->query("SELECT COUNT(id) as total_products FROM products");
    $stats['total_products'] = $stmt->fetchColumn() ?: 0;

    // Recent orders
    $stmt = $db->query("SELECT o.id, o.status, o.total_amount, u.firstname, u.lastname, o.created_at 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.id 
                        ORDER BY o.created_at DESC 
                        LIMIT 5");
    $stats['recent_orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $stats]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'failed', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
