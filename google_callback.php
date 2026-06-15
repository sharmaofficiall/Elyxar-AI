<?php
// Simple Google OAuth 2.0 implementation without external dependencies
// because Composer isn't available

require_once 'config.php';
require_once 'db_connect.php';

// Add google_id column if it doesn't exist to users table
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'google_id'");
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) DEFAULT NULL");
}

$code = $_GET['code'] ?? '';

if (empty($code)) {
    // Generate login URL
    $params = [
        'client_id' => GOOGLE_OAUTH_CONFIG['client_id'],
        'redirect_uri' => GOOGLE_OAUTH_CONFIG['redirect_uri'],
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online'
    ];
    $login_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    header('Location: ' . $login_url);
    exit();
} else {
    // Exchange code for token
    $token_url = 'https://oauth2.googleapis.com/token';
    $postData = [
        'code' => $code,
        'client_id' => GOOGLE_OAUTH_CONFIG['client_id'],
        'client_secret' => GOOGLE_OAUTH_CONFIG['client_secret'],
        'redirect_uri' => GOOGLE_OAUTH_CONFIG['redirect_uri'],
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Ignore SSL verification for local dev if needed, or point to a CA bundle
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $tokenInfo = json_decode($response, true);

    if (isset($tokenInfo['access_token'])) {
        // Get user info
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenInfo['access_token']]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $userResponse = curl_exec($ch);
        curl_close($ch);

        $userInfo = json_decode($userResponse, true);

        if (isset($userInfo['email'])) {
            $email = $conn->real_escape_string($userInfo['email']);
            $googleId = $conn->real_escape_string($userInfo['sub']);
            $name = $conn->real_escape_string($userInfo['name']);

            // Check if user exists
            $checkUser = $conn->query("SELECT * FROM users WHERE email='$email' OR google_id='$googleId'");

            if ($checkUser->num_rows > 0) {
                // User exists, log them in
                $user = $checkUser->fetch_assoc();

                // Update google_id if missing
                if (empty($user['google_id'])) {
                    $conn->query("UPDATE users SET google_id='$googleId' WHERE id=" . $user['id']);
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'user';

            } else {
                // Register new user
                // Generate a random password since they login with Google
                $randomPassword = bin2hex(random_bytes(16));
                $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
                $username = !empty($name) ? $conn->real_escape_string($name) : explode('@', $email)[0];

                // Ensure unique username
                $checkUsername = $conn->query("SELECT id FROM users WHERE username='$username'");
                if ($checkUsername->num_rows > 0) {
                    $username .= rand(1000, 9999);
                }

                $sql = "INSERT INTO users (username, email, password, google_id) VALUES ('$username', '$email', '$hashedPassword', '$googleId')";
                if ($conn->query($sql) === TRUE) {
                    $_SESSION['user_id'] = $conn->insert_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = 'user';
                } else {
                    die("Error creating user: " . $conn->error);
                }
            }

            // Redirect to home
            header('Location: index.php');
            exit();

        } else {
            die("Could not retrieve user info from Google.");
        }
    } else {
        die("Error fetching token: " . ($tokenInfo['error_description'] ?? 'Unknown error'));
    }
}
?>