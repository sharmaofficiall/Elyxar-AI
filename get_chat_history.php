<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];
$conversationId = $_GET['conversation_id'] ?? null;

// If no conversation_id is provided, get the latest one for the user
if (empty($conversationId)) {
    $stmt = $conn->prepare("SELECT conversation_id FROM chat_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $conversationId = $row['conversation_id'];
    }
    $stmt->close();
}

$history = [];

if (!empty($conversationId)) {
    $_SESSION['currentConversationId'] = $conversationId;
    $stmt = $conn->prepare("SELECT conversation_id, sender, message, created_at FROM chat_history WHERE user_id = ? AND conversation_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("is", $userId, $conversationId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Remap sender to role for frontend compatibility
        $history[] = [
            'role' => $row['sender'],
            'text' => $row['message']
        ];
    }
    $stmt->close();
}

$conn->close();

echo json_encode(['success' => true, 'conversation_id' => $conversationId, 'conversation' => ['messages' => $history]]);
?>