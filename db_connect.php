<?php
// $servername = "sql.freedb.tech";
// $username = "freedb_ai_chat_app";
// $password = "e2rAXxRBE@6w?nM";
// $dbname = "freedb_ai_chat_app";

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ai_chat_app";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    // Select the database
    $conn->select_db($dbname);

    // Create users table (or add new columns if table exists)
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(30) NOT NULL UNIQUE,
        email VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'user',
        credits INT(11) DEFAULT 5000,
        plan VARCHAR(20) DEFAULT 'free',
        plan_expires DATE,
        stripe_customer_id VARCHAR(255),
        reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    
    // Add credits column if it doesn't exist
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'credits'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD credits INT(11) DEFAULT 5000");
    }
    
    // Add plan column if it doesn't exist
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'plan'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD plan VARCHAR(20) DEFAULT 'free'");
    }
    
    // Add plan_expires column if it doesn't exist
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'plan_expires'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD plan_expires DATE");
    }
    
    // Add stripe_customer_id column if it doesn't exist
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'stripe_customer_id'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD stripe_customer_id VARCHAR(255)");
    }

    // Create chat_history table with image support
    $sql = "CREATE TABLE IF NOT EXISTS chat_history (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(6) UNSIGNED,
        conversation_id VARCHAR(255) NOT NULL,
        sender VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        image_data TEXT,
        mime_type VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $conn->query($sql);

    // Add index for faster conversation queries
    $result = $conn->query("SHOW INDEX FROM chat_history WHERE Key_name = 'idx_conversation'");
    if ($result->num_rows == 0) {
        $conn->query("CREATE INDEX idx_conversation ON chat_history(conversation_id, created_at)");
    }

    // Create subscription plans table
    $sql = "CREATE TABLE IF NOT EXISTS plans (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        credits INT(11) NOT NULL,
        duration_days INT(11) NOT NULL,
        features TEXT,
        is_popular TINYINT(1) DEFAULT 0
    )";
    $conn->query($sql);

    // Insert default plans if not exist
    $result = $conn->query("SELECT COUNT(*) as count FROM plans");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $conn->query("INSERT INTO plans (name, price, credits, duration_days, features, is_popular) VALUES
            ('Free', 0, 5000, 30, '5000 credits/month|Basic AI models|Image generation (5/day)|Community support', 0),
            ('Pro', 9.99, 100000, 30, '100000 credits/month|All AI models|Unlimited image generation|Video generation|Priority support', 1),
            ('Enterprise', 49.99, -1, 30, 'Unlimited credits/month|All AI models + Premium|Unlimited image & video|API access|24/7 Support', 0)");
    }

    // Create payments table
    $sql = "CREATE TABLE IF NOT EXISTS payments (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(6) UNSIGNED,
        plan_id INT(6) UNSIGNED,
        amount DECIMAL(10,2) NOT NULL,
        credits_added INT(11) NOT NULL,
        payment_status VARCHAR(20) DEFAULT 'pending',
        transaction_id VARCHAR(255),
        payment_method VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (plan_id) REFERENCES plans(id)
    )";
    $conn->query($sql);

    // Create credit_usage table to track usage
    $sql = "CREATE TABLE IF NOT EXISTS credit_usage (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(6) UNSIGNED,
        credits_used INT(11) NOT NULL,
        feature VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $conn->query($sql);
} else {
    // Fallback if DB creation fails (e.g. permission issues), though unlikely on XAMPP default
    error_log("Error creating database: " . $conn->error);
}
?>