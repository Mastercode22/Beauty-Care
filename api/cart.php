<?php
// api/cart.php
header('Content-Type: application/json');
include_once '../config/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function json_response($status = 'failed', $message = 'An error occurred.', $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    // Before sending response, let's get cart summary to send back
    $cart_summary = get_cart_summary_data();
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data, 'cart' => $cart_summary]);
    exit;
}

function get_cart_summary_data() {
    $database = new Database();
    $db = $database->getConnection();
    $user_id = $_SESSION['user_id'] ?? null;
    $total_items = 0;
    $total_price = 0;

    $cart_items = [];
    if ($user_id) {
        $stmt = $db->prepare('SELECT p.id, p.name, p.price, p.image, ci.quantity FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = ?');
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isset($_SESSION['cart'])) {
        $product_ids = array_keys($_SESSION['cart']);
        if (!empty($product_ids)) {
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $stmt = $db->prepare("SELECT id, name, price, image FROM products WHERE id IN ($placeholders)");
            $stmt->execute($product_ids);
            $products = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // id => product
            foreach ($_SESSION['cart'] as $id => $item) {
                 if (isset($products[$id])) {
                    $product = $products[$id];
                    $product['quantity'] = $item['quantity'];
                    $cart_items[] = $product;
                 }
            }
        }
    }
    
    foreach ($cart_items as $item) {
        $total_items += $item['quantity'];
        $total_price += $item['price'] * $item['quantity'];
    }

    return ['items' => $cart_items, 'total_items' => $total_items, 'total_price' => number_format($total_price, 2)];
}


$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$user_id = $_SESSION['user_id'] ?? null;


if ($method === 'GET') {
    json_response('success', 'Cart data retrieved.');
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $product_id = $input['product_id'] ?? 0;
    $quantity = $input['quantity'] ?? 1;

    if (empty($action) || empty($product_id)) {
        json_response('failed', 'Action and Product ID are required.', null, 400);
    }
    
    try {
        if ($action === 'add') {
            if ($user_id) {
                // Logged-in user
                $stmt = $db->prepare('SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?');
                $stmt->execute([$user_id, $product_id]);
                if ($stmt->fetch()) {
                    $stmt = $db->prepare('UPDATE cart_items SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?');
                    $stmt->execute([$quantity, $user_id, $product_id]);
                } else {
                    $stmt = $db->prepare('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)');
                    $stmt->execute([$user_id, $product_id, $quantity]);
                }
            } else {
                // Guest user
                if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id]['quantity'] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = ['product_id' => $product_id, 'quantity' => $quantity];
                }
            }
            json_response('success', 'Product added to cart.');

        } elseif ($action === 'update') {
            if ($quantity < 1) { // If quantity is 0 or less, treat as remove
                 if ($user_id) {
                    $stmt = $db->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
                    $stmt->execute([$user_id, $product_id]);
                } else {
                    unset($_SESSION['cart'][$product_id]);
                }
                json_response('success', 'Product removed from cart.');
            } else {
                 if ($user_id) {
                    $stmt = $db->prepare('UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?');
                    $stmt->execute([$quantity, $user_id, $product_id]);
                } else {
                    if (isset($_SESSION['cart'][$product_id])) {
                        $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                    }
                }
                json_response('success', 'Cart updated.');
            }

        } elseif ($action === 'remove') {
             if ($user_id) {
                $stmt = $db->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
                $stmt->execute([$user_id, $product_id]);
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
            json_response('success', 'Product removed from cart.');
        
        } elseif ($action === 'merge' && $user_id) {
            if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) {
                    $stmt = $db->prepare('SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?');
                    $stmt->execute([$user_id, $item['product_id']]);
                    if ($stmt->fetch()) {
                        $stmt = $db->prepare('UPDATE cart_items SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?');
                        $stmt->execute([$item['quantity'], $user_id, $item['product_id']]);
                    } else {
                        $stmt = $db->prepare('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)');
                        $stmt->execute([$user_id, $item['product_id'], $item['quantity']]);
                    }
                }
                unset($_SESSION['cart']); // Clear session cart after merging
            }
            json_response('success', 'Cart merged.');
        }
        else {
            json_response('failed', 'Invalid cart action.', null, 400);
        }
    } catch (PDOException $e) {
        json_response('failed', 'Database error: ' . $e->getMessage(), null, 500);
    }
}
?>
