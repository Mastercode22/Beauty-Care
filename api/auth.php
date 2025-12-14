<?php
// api/auth.php
header('Content-Type: application/json');
include_once '../config/database.php';

// We need a function to send a JSON response
function json_response($status = 'error', $message = 'An error occurred.', $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'register') {
        // --- REGISTRATION LOGIC ---
        $first_name = $input['first_name'] ?? '';
        $last_name = $input['last_name'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            json_response('failed', 'All fields are required.', null, 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response('failed', 'Invalid email format.', null, 400);
        }

        try {
            // Check if user already exists
            $query = 'SELECT id FROM users WHERE email = :email LIMIT 1';
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                json_response('failed', 'An account with this email already exists.', null, 409);
            }

            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert new user
            $query = 'INSERT INTO users (first_name, last_name, email, password) VALUES (:first_name, :last_name, :email, :password)';
            $stmt = $db->prepare($query);

            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);

            if ($stmt->execute()) {
                json_response('success', 'Registration successful. You can now log in.', ['user_id' => $db->lastInsertId()]);
            } else {
                json_response('failed', 'Registration failed. Please try again.', null, 500);
            }
        } catch (PDOException $e) {
            json_response('failed', 'Database error: ' . $e->getMessage(), null, 500);
        }

    } elseif ($action === 'login') {
        // --- LOGIN LOGIC ---
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            json_response('failed', 'Email and password are required.', null, 400);
        }

        try {
            $query = 'SELECT id, first_name, last_name, email, password, role FROM users WHERE email = :email LIMIT 1';
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                json_response('failed', 'Invalid credentials.', null, 401);
            }

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($password, $user['password'])) {
                // Password is correct, start session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'];
                $_SESSION['user_role'] = $user['role'];

                json_response('success', 'Login successful.', [
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['first_name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ]
                ]);
            } else {
                json_response('failed', 'Invalid credentials.', null, 401);
            }
        } catch (PDOException $e) {
            json_response('failed', 'Database error: ' . $e->getMessage(), null, 500);
        }
    } else {
        json_response('failed', 'Invalid action specified.', null, 400);
    }

} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'logout') {
        // --- LOGOUT LOGIC ---
        session_unset();
        session_destroy();
        json_response('success', 'Logout successful.');
    } elseif ($action === 'check_auth') {
        // --- CHECK AUTH STATUS ---
        if (isset($_SESSION['user_id'])) {
            json_response('success', 'User is authenticated.', [
                'isAuthenticated' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'name' => $_SESSION['user_name'],
                    'role' => $_SESSION['user_role']
                ]
            ]);
        } else {
            json_response('success', 'User is not authenticated.', ['isAuthenticated' => false]);
        }
    } else {
        json_response('failed', 'Invalid action or method.', null, 405);
    }
} else {
    json_response('failed', 'Method Not Allowed.', null, 405);
}
?>
