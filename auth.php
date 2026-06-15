<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// Handle logout via GET request (from URL)
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: home.php');
    exit();
}

// Handle logout via POST/JSON
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'register') {
    $username = $conn->real_escape_string($input['username']);
    $email = $conn->real_escape_string($input['email']);
    $password = password_hash($input['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, password, credits) VALUES ('$username', '$email', '$password', 5000)";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
} elseif ($action === 'login') {
    $email = $conn->real_escape_string($input['email']);
    $password = $input['password'];

    $sql = "SELECT id, username, password, role FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role']; // Store role in session
            echo json_encode(['success' => true, 'role' => $row['role']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid password']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'User not found']);
    }
} elseif ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'redirect' => 'home.php']);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

$conn->close();
?>