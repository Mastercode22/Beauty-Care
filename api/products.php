<?php
// api/products.php
header('Content-Type: application/json');
include_once '../config/database.php';

// --- Utility function for JSON responses ---
function json_response($status = 'failed', $message = 'An error occurred.', $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

// --- Session and Auth Check ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function is_admin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// --- Handle GET requests (Public) ---
if ($method === 'GET') {
    try {
        $query = 'SELECT p.id, p.name, p.description, p.price, p.image, p.stock_quantity, c.name as category_name, p.category_id FROM products p LEFT JOIN categories c ON p.category_id = c.id';
        $params = [];

        if (isset($_GET['id'])) {
            $query .= ' WHERE p.id = :id';
            $params[':id'] = (int)$_GET['id'];
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($product) json_response('success', 'Product found.', $product);
            else json_response('failed', 'Product not found.', null, 404);
        }
        elseif (isset($_GET['category_id'])) {
            $query .= ' WHERE p.category_id = :category_id';
            $params[':category_id'] = (int)$_GET['category_id'];
        }
        elseif (isset($_GET['search'])) {
            $query .= ' WHERE p.name LIKE :search OR p.description LIKE :search';
            $params[':search'] = '%' . htmlspecialchars(strip_tags($_GET['search'])) . '%';
        }
        
        $query .= ' ORDER BY p.created_at DESC';

        if (isset($_GET['limit'])) {
            $query .= ' LIMIT :limit';
            $params[':limit'] = (int)$_GET['limit'];
        }

        $stmt = $db->prepare($query);
        foreach ($params as $key => &$val) {
            $type = (is_int($val) || ctype_digit($val)) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindParam($key, $val, $type);
        }
        
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        json_response('success', 'Products retrieved.', $products);

    } catch (PDOException $e) {
        json_response('failed', 'Database error: ' . $e->getMessage(), null, 500);
    }
}

// --- Handle POST, PUT, DELETE requests (Admin Only) ---
if (!is_admin()) {
    if ($method !== 'GET') {
        json_response('failed', 'Unauthorized', null, 403);
    }
    // For GET requests, no auth is needed, so we let it pass if method is GET
}

// Since file uploads are involved, we won't be using a JSON payload for POST/PUT
// We'll use multipart/form-data
$action = $_POST['action'] ?? '';

if ($method === 'POST' && $action === 'create') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $image_name = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/images/";
        $image_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        // Simple validation
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
            json_response('failed', 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.', null, 400);
        }
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            json_response('failed', 'Sorry, there was an error uploading your file.', null, 500);
        }
    } else {
        json_response('failed', 'Product image is required.', null, 400);
    }

    try {
        $query = 'INSERT INTO products (name, description, price, stock_quantity, category_id, image) VALUES (:name, :description, :price, :stock, :category_id, :image)';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
        $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->bindParam(':image', $image_name);
        
        if ($stmt->execute()) {
            json_response('success', 'Product created successfully.');
        } else {
            json_response('failed', 'Failed to create product.', null, 500);
        }
    } catch (PDOException $e) {
        json_response('failed', 'Database error: ' . $e->getMessage(), null, 500);
    }
}

if ($method === 'POST' && $action === 'update') { // Using POST for update to easily handle multipart/form-data
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $image_name = $_POST['existing_image'] ?? ''; // Keep old image if new one isn't uploaded

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/images/";
        $image_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
            json_response('failed', 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.', null, 400);
        }
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            json_response('failed', 'Sorry, there was an error uploading your file.', null, 500);
        }
    }

    try {
        $query = 'UPDATE products SET name = :name, description = :description, price = :price, stock_quantity = :stock, category_id = :category_id, image = :image WHERE id = :id';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
        $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->bindParam(':image', $image_name);

        if ($stmt->execute()) {
            json_response('success', 'Product updated successfully.');
        } else {
            json_response('failed', 'Failed to update product.', null, 500);
        }
    } catch (PDOException $e) {
        json_response('failed', 'Database error: ' . $e->getMessage(), null, 500);
    }
}

if ($method === 'POST' && $action === 'delete') {
    $id = $_POST['id'] ?? '';

    if (empty($id)) json_response('failed', 'Product ID is required.', null, 400);

    try {
        // You might want to delete the associated image file from the server as well
        $query = 'DELETE FROM products WHERE id = :id';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                json_response('success', 'Product deleted successfully.');
            } else {
                json_response('failed', 'Product not found.', null, 404);
            }
        } else {
            json_response('failed', 'Failed to delete product.', null, 500);
        }
    } catch (PDOException $e) {
        // Foreign key constraint might fail if product is in an order
        json_response('failed', 'Cannot delete product. It might be part of an existing order.', null, 409);
    }
}

?>