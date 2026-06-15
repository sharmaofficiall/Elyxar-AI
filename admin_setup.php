<?php
require_once 'db_connect.php';

// Add role column if it doesn't exist
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($checkColumn->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN role VARCHAR(10) DEFAULT 'user'";
    if ($conn->query($sql) === TRUE) {
        echo "Role column added successfully.<br>";
    } else {
        echo "Error adding role column: " . $conn->error . "<br>";
    }
} else {
    echo "Role column already exists.<br>";
}

// Create admin user if not exists
$adminUsername = 'admin';
$adminEmail = 'admin@example.com';
$adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
$adminRole = 'admin';

$checkAdmin = $conn->query("SELECT * FROM users WHERE email='$adminEmail'");
if ($checkAdmin->num_rows == 0) {
    $sql = "INSERT INTO users (username, email, password, role) VALUES ('$adminUsername', '$adminEmail', '$adminPassword', '$adminRole')";
    if ($conn->query($sql) === TRUE) {
        echo "Admin user created successfully. Email: admin@example.com, Password: admin123<br>";
    } else {
        echo "Error creating admin user: " . $conn->error . "<br>";
    }
} else {
    echo "Admin user already exists.<br>";
}

$conn->close();
?>