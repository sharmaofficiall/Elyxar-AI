<?php
/**
 * Conversation Manager
 * Handles conversation history using PHP sessions
 */
class ConversationManager
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Initialize conversations array if not exists
        if (!isset($_SESSION['conversations'])) {
            $_SESSION['conversations'] = [];
        }

        if (!isset($_SESSION['currentConversationId'])) {
            $_SESSION['currentConversationId'] = null;
        }
    }

    public function hydrateConversation($conversationId, $userId)
    {
        $conversationInSession = false;
        foreach ($_SESSION['conversations'] as $conv) {
            if ($conv['id'] === $conversationId) {
                $conversationInSession = true;
                break;
            }
        }

        if (!$conversationInSession) {
            // Check if image_data column exists
            $hasImageColumn = false;
            $result = $this->conn->query("SHOW COLUMNS FROM chat_history LIKE 'image_data'");
            if ($result && $result->num_rows > 0) {
                $hasImageColumn = true;
            }
            
            if ($hasImageColumn) {
                $stmt = $this->conn->prepare("SELECT sender, message, image_data, mime_type FROM chat_history WHERE user_id = ? AND conversation_id = ? ORDER BY created_at ASC");
            } else {
                $stmt = $this->conn->prepare("SELECT sender, message FROM chat_history WHERE user_id = ? AND conversation_id = ? ORDER BY created_at ASC");
            }
            $stmt->bind_param("is", $userId, $conversationId);
            $stmt->execute();
            $result = $stmt->get_result();

            $messages = [];
            $firstUserMessage = null;
            while ($row = $result->fetch_assoc()) {
                if ($hasImageColumn) {
                    $messages[] = [
                        'role' => $row['sender'],
                        'text' => $row['message'],
                        'image' => $row['image_data'],
                        'mime_type' => $row['mime_type'],
                        'timestamp' => date('c')
                    ];
                } else {
                    $messages[] = [
                        'role' => $row['sender'],
                        'text' => $row['message'],
                        'image' => null,
                        'mime_type' => null,
                        'timestamp' => date('c')
                    ];
                }
                if ($row['sender'] === 'user' && $firstUserMessage === null) {
                    $firstUserMessage = $row['message'];
                }
            }
            $stmt->close();

            if (!empty($messages)) {
                $title = 'Chat';
                if($firstUserMessage) {
                    $title = mb_substr($firstUserMessage, 0, 50) . (mb_strlen($firstUserMessage) > 50 ? '...' : '');
                }
                $conversation = [
                    'id' => $conversationId,
                    'title' => $title,
                    'timestamp' => date('c'), // Placeholder
                    'messages' => $messages
                ];
                array_unshift($_SESSION['conversations'], $conversation);
            }
        }
    }

    /**
     * Create a new conversation
     * @return string Conversation ID
     */
    public function createConversation()
    {
        $id = $this->generateId();
        $conversation = [
            'id' => $id,
            'title' => 'New Chat',
            'timestamp' => date('c'),
            'messages' => []
        ];

        array_unshift($_SESSION['conversations'], $conversation);
        $_SESSION['currentConversationId'] = $id;

        // Limit total conversations
        if (count($_SESSION['conversations']) > APP_SETTINGS['max_conversations']) {
            array_pop($_SESSION['conversations']);
        }

        return $id;
    }

    /**
     * Get conversation by ID
     * @param string $id Conversation ID
     * @return array|null Conversation data
     */
    public function getConversation($id)
    {
        foreach ($_SESSION['conversations'] as $conv) {
            if ($conv['id'] === $id) {
                return $conv;
            }
        }
        return null;
    }

    /**
     * Get all conversations
     * @return array All conversations
     */
    public function getAllConversations()
    {
        if (empty($_SESSION['conversations']) && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            $conversations = [];

            $stmt = $this->conn->prepare("
                SELECT
                    conversation_id,
                    (SELECT message FROM chat_history ch2 WHERE ch2.conversation_id = ch.conversation_id AND ch2.sender = 'user' ORDER BY ch2.created_at ASC LIMIT 1) as title,
                    MAX(created_at) as timestamp
                FROM chat_history ch
                WHERE user_id = ?
                GROUP BY conversation_id
                ORDER BY MAX(created_at) DESC
            ");

            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $title = $row['title'] ? (mb_substr($row['title'], 0, 50) . (mb_strlen($row['title']) > 50 ? '...' : '')) : 'New Chat';
                    $conversations[] = [
                        'id' => $row['conversation_id'],
                        'title' => $title,
                        'timestamp' => $row['timestamp'],
                        'messages' => [] // Messages will be loaded on demand
                    ];
                }
                $stmt->close();
                $_SESSION['conversations'] = $conversations;
            }
        }
        return $_SESSION['conversations'];
    }

    /**
     * Add message to conversation
     * @param string $conversationId Conversation ID
     * @param string $role Message role (user or ai)
     * @param string $text Message text
     * @param string|null $imageBase64 Base64 encoded image
     */
    public function addMessage($conversationId, $role, $text, $imageBase64 = null)
    {
        foreach ($_SESSION['conversations'] as &$conv) {
            if ($conv['id'] === $conversationId) {
                $message = [
                    'role' => $role,
                    'text' => $text,
                    'imageBase64' => $imageBase64,
                    'timestamp' => date('c')
                ];

                $conv['messages'][] = $message;

                // Update title from first user message
                if ($conv['title'] === 'New Chat' && $role === 'user') {
                    $conv['title'] = mb_substr($text, 0, 50) . (mb_strlen($text) > 50 ? '...' : '');
                }

                // Update timestamp
                $conv['timestamp'] = date('c');

                // Limit messages per conversation
                if (count($conv['messages']) > APP_SETTINGS['max_messages_per_conversation']) {
                    array_shift($conv['messages']);
                }

                break;
            }
        }
    }

    /**
     * Delete conversation
     * @param string $id Conversation ID
     * @return bool Success status
     */
    public function deleteConversation($id)
    {
        foreach ($_SESSION['conversations'] as $key => $conv) {
            if ($conv['id'] === $id) {
                unset($_SESSION['conversations'][$key]);
                $_SESSION['conversations'] = array_values($_SESSION['conversations']);

                // If current conversation was deleted, switch to first available
                if ($_SESSION['currentConversationId'] === $id) {
                    if (count($_SESSION['conversations']) > 0) {
                        $_SESSION['currentConversationId'] = $_SESSION['conversations'][0]['id'];
                    } else {
                        $this->createConversation();
                    }
                }

                return true;
            }
        }
        return false;
    }

    /**
     * Set current conversation
     * @param string $id Conversation ID
     */
    public function setCurrentConversation($id)
    {
        $_SESSION['currentConversationId'] = $id;
    }

    /**
     * Get current conversation ID
     * @return string|null Current conversation ID
     */
    public function getCurrentConversationId()
    {
        return $_SESSION['currentConversationId'];
    }

    /**
     * Generate unique ID
     * @return string Unique ID
     */
    private function generateId()
    {
        return base_convert(time(), 10, 36) . substr(md5(uniqid(rand(), true)), 0, 8);
    }
}
