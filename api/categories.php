<?php
// api/categories.php
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

try {
    switch ($method) {
        case 'GET':
            // --- FETCH CATEGORIES (Publicly available) ---
            $query = 'SELECT * FROM categories ORDER BY name ASC';
            $stmt = $db->prepare($query);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            json_response('success', 'Categories retrieved.', $categories);
            break;

        case 'POST':
            // --- CREATE CATEGORY (Admin only) ---
            if (!is_admin()) json_response('failed', 'Unauthorized', null, 403);
            
            $input = json_decode(file_get_contents('php://input'), true);
            $name = $input['name'] ?? '';
            $description = $input['description'] ?? '';

            if (empty($name)) json_response('failed', 'Category name is required.', null, 400);

            $query = 'INSERT INTO categories (name, description) VALUES (:name, :description)';
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            
            if ($stmt->execute()) {
                $new_id = $db->lastInsertId();
                json_response('success', 'Category created.', ['id' => $new_id, 'name' => $name, 'description' => $description]);
            } else {
                json_response('failed', 'Failed to create category.', null, 500);
            }
            break;

        case 'PUT':
            // --- UPDATE CATEGORY (Admin only) ---
            if (!is_admin()) json_response('failed', 'Unauthorized', null, 403);

            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            $name = $input['name'] ?? '';
            $description = $input['description'] ?? '';

            if (empty($id) || empty($name)) json_response('failed', 'Category ID and name are required.', null, 400);

            $query = 'UPDATE categories SET name = :name, description = :description WHERE id = :id';
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    json_response('success', 'Category updated.');
                } else {
                    json_response('failed', 'Category not found or no changes made.', null, 404);
                }
            } else {
                json_response('failed', 'Failed to update category.', null, 500);
            }
            break;

        case 'DELETE':
            // --- DELETE CATEGORY (Admin only) ---
            if (!is_admin()) json_response('failed', 'Unauthorized', null, 403);

            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;

            if (empty($id)) json_response('failed', 'Category ID is required.', null, 400);

            // Optional: Check if any products are using this category before deleting
            // For now, we rely on the database constraint (ON DELETE SET NULL)

            $query = 'DELETE FROM categories WHERE id = :id';
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    json_response('success', 'Category deleted.');
                } else {
                    json_response('failed', 'Category not found.', null, 404);
                }
            } else {
                json_response('failed', 'Failed to delete category.', null, 500);
            }
            break;

        default:
            json_response('failed', 'Method Not Allowed.', null, 405);
            break;
    }
} catch (PDOException $e) {
    json_response('failed', 'Database error: ' . $e->getMessage(), null, 500);
}
?>
