<?php
/**
 * Main API Endpoint
 * Handles all AJAX requests from the frontend
 */

require_once 'config.php';
require_once 'db_connect.php';
require_once 'includes/ConversationManager.php';
require_once 'includes/GeminiHandler.php';
require_once 'includes/OpenAIHandler.php';
require_once 'includes/SambaNovaHandler.php';
require_once 'includes/OpenRouterHandler.php';
require_once 'includes/PollinationsHandler.php';
require_once 'includes/RevangeHandler.php';
require_once 'includes/DeepSeekHandler.php';
require_once 'includes/GroqHandler.php';
require_once 'includes/StabilityAIHandler.php';
require_once 'includes/ReplicateHandler.php';
require_once 'includes/NvidiaHandler.php';
require_once 'includes/OpenCodeHandler.php';

// Set JSON response header
header('Content-Type: application/json');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Invalid JSON input']);
    exit();
}

$action = $input['action'] ?? '';

// Initialize conversation manager
$conversationManager = new ConversationManager($conn);

try {
    switch ($action) {
        case 'send_message':
            handleSendMessage($input, $conversationManager, $conn);
            break;

        case 'get_conversations':
            handleGetConversations($conversationManager);
            break;

        case 'get_credits':
            handleGetCredits($conn);
            break;

        case 'create_conversation':
            handleCreateConversation($conversationManager);
            break;

        case 'delete_conversation':
            handleDeleteConversation($input, $conversationManager);
            break;

        case 'load_conversation':
            handleLoadConversation($input, $conversationManager);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Handle send message request
 */
function handleSendMessage($input, $conversationManager, $conn)
{
    $userText = $input['message'] ?? '';
    $provider = $input['provider'] ?? '';
    $model = $input['model'] ?? '';
    $imageBase64 = $input['imageBase64'] ?? null;
    $imageMimeType = $input['imageMimeType'] ?? 'image/jpeg';
    $conversationId = $input['conversationId'] ?? null;
    $userId = $_SESSION['user_id'];

    if (empty($userText)) {
        echo json_encode(['error' => 'Message is required']);
        return;
    }

    if (empty($provider)) {
        echo json_encode(['error' => 'Provider is required']);
        return;
    }

    // Determine credits cost based on provider and feature
    $creditsCost = getCreditsCost($provider);
    
    // Check user credits
    $stmt = $conn->prepare("SELECT credits, plan FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();
    
    $hasUnlimitedCredits = ($userData['credits'] < 0 || $userData['plan'] === 'enterprise');
    
    if (!$hasUnlimitedCredits && $userData['credits'] < $creditsCost) {
        echo json_encode([
            'success' => false,
            'error' => 'Insufficient credits. Please purchase more credits to continue.',
            'credits' => $userData['credits'],
            'required' => $creditsCost
        ]);
        return;
    }

    // Get or create conversation
    if (!$conversationId) {
        $conversationId = $conversationManager->createConversation();
    }

    // Hydrate conversation from DB if not in session
    $conversationManager->hydrateConversation($conversationId, $userId);

    // Save user message to session
    $conversationManager->addMessage($conversationId, 'user', $userText, $imageBase64);

    // Save user message to database
    // Check if image_data column exists, if not use basic insert
    $result = $conn->query("SHOW COLUMNS FROM chat_history LIKE 'image_data'");
    if ($result && $result->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO chat_history (user_id, conversation_id, sender, message, image_data, mime_type) VALUES (?, ?, 'user', ?, ?, ?)");
        $stmt->bind_param("issss", $userId, $conversationId, $userText, $imageBase64, $imageMimeType);
    } else {
        $stmt = $conn->prepare("INSERT INTO chat_history (user_id, conversation_id, sender, message) VALUES (?, ?, 'user', ?)");
        $stmt->bind_param("iss", $userId, $conversationId, $userText);
    }
    $stmt->execute();
    $stmt->close();

    // Get API key
    $apiKey = getApiKey($provider);

    // Generate response based on provider
    $response = '';

    try {
        switch ($provider) {
            case 'gemini':
                $handler = new GeminiHandler($apiKey, $model);
                $response = $handler->generateResponse($userText, $imageBase64, $imageMimeType);
                break;

            case 'openai':
                $handler = new OpenAIHandler($apiKey, $model ?: 'gpt-3.5-turbo');
                $response = $handler->generateResponse($userText);
                break;

            case 'deepseek':
                $handler = new DeepSeekHandler($apiKey, $model ?: 'deepseek-chat');
                $response = $handler->generateResponse($userText);
                break;

            case 'agentrouter':
                $handler = new OpenAIHandler($apiKey, 'gpt-3.5-turbo', 'https://agentrouter.org/v1/chat/completions', 'AgentRouter');
                $response = $handler->generateResponse($userText);
                break;

            case 'sambanova':
                $handler = new SambaNovaHandler($apiKey, $model);
                $response = $handler->generateResponse($userText);
                break;

            case 'openrouter':
                $handler = new OpenRouterHandler($apiKey, $model);
                $response = $handler->generateResponse($userText);
                break;

            case 'pollinations':
                $handler = new PollinationsHandler('', $model, 'text');
                $response = $handler->generateResponse($userText, $imageBase64, $imageMimeType);
                break;

            case 'pollinations-image':
                $handler = new PollinationsHandler('', $model, 'image');
                $response = $handler->generateResponse($userText);
                break;

            case 'pollinations-video':
                $handler = new PollinationsHandler(getApiKey('pollinations'), $model, 'video');
                $response = $handler->generateResponse($userText);
                break;

            case 'revange':
                $handler = new RevangeHandler('', $model);
                $response = $handler->generateResponse($userText);
                break;

            case 'groq':
                $handler = new GroqHandler($apiKey, $model);
                $response = $handler->generateResponse($userText);
                break;

            case 'stability':
                $handler = new StabilityAIHandler(getApiKey('stability'), $model);
                $response = $handler->generateResponse($userText);
                break;

            case 'replicate':
                $handler = new ReplicateHandler($apiKey, $model);
                $response = $handler->generateResponse($userText, $imageBase64, $imageMimeType);
                break;

            case 'nvidia':
                $handler = new NvidiaHandler($apiKey, $model);
                $response = $handler->generateResponse($userText, $imageBase64, $imageMimeType);
                break;

            case 'opencode':
                $handler = new OpenCodeHandler($apiKey, $model);
                $response = $handler->generateResponse($userText);
                break;

            default:
                $response = "Provider '{$provider}' is not supported";
                break;
        }

        // Save AI response to session
        $conversationManager->addMessage($conversationId, 'ai', $response);

        // Save AI response to database
        $stmt = $conn->prepare("INSERT INTO chat_history (user_id, conversation_id, sender, message) VALUES (?, ?, 'ai', ?)");
        $stmt->bind_param("iss", $userId, $conversationId, $response);
        $stmt->execute();
        $stmt->close();

        // Deduct credits (skip for unlimited/enterprise plans)
        if (!$hasUnlimitedCredits) {
            $newCredits = $userData['credits'] - $creditsCost;
            $stmt = $conn->prepare("UPDATE users SET credits = ? WHERE id = ?");
            $stmt->bind_param("ii", $newCredits, $userId);
            $stmt->execute();
            $stmt->close();
        } else {
            $newCredits = $userData['credits'];
        }

        // Log credit usage
        $stmt = $conn->prepare("INSERT INTO credit_usage (user_id, credits_used, feature) VALUES (?, ?, ?)");
        $feature = $provider . '_' . ($model ?: 'default');
        $stmt->bind_param("iis", $userId, $creditsCost, $feature);
        $stmt->execute();
        $stmt->close();

        if (empty($response)) {
            $response = "No response received from the AI model. Please try again.";
        }
        
        echo json_encode([
            'success' => true,
            'response' => $response,
            'conversationId' => $conversationId,
            'creditsRemaining' => $hasUnlimitedCredits ? 'Unlimited' : $newCredits
        ]);

    } catch (Exception $e) {
        $errorMsg = "Error: " . $e->getMessage();
        error_log("API Error: " . $errorMsg . " - Provider: " . $provider . " - Model: " . $model);
        $conversationManager->addMessage($conversationId, 'ai', $errorMsg);

        echo json_encode([
            'success' => false,
            'error' => $errorMsg,
            'conversationId' => $conversationId
        ]);
    }
}

/**
 * Handle get conversations request
 */
function handleGetConversations($conversationManager)
{
    $conversations = $conversationManager->getAllConversations();
    $currentId = $conversationManager->getCurrentConversationId();

    echo json_encode([
        'success' => true,
        'conversations' => $conversations,
        'currentConversationId' => $currentId
    ]);
}

/**
 * Handle get credits request
 */
function handleGetCredits($conn)
{
    $userId = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT credits, plan, plan_expires FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();
    
    $creditsDisplay = ($userData['plan'] === 'enterprise' || $userData['credits'] < 0) ? 'Unlimited' : $userData['credits'];
    $creditsRaw = ($userData['plan'] === 'enterprise' || $userData['credits'] < 0) ? -1 : $userData['credits'];

    echo json_encode([
        'success' => true,
        'credits' => $creditsDisplay,
        'credits_raw' => $creditsRaw,
        'plan' => $userData['plan'],
        'plan_expires' => $userData['plan_expires']
    ]);
}

/**
 * Get credits cost for a provider
 */
function getCreditsCost($provider)
{
    // Different providers have different costs
    $costs = [
        'gemini' => 5,
        'openai' => 10,
        'deepseek' => 3,
        'sambanova' => 5,
        'openrouter' => 5,
        'pollinations' => 3,
        'pollinations-image' => 15,
        'pollinations-video' => 50,
        'revange' => 5,
        'groq' => 3,
        'stability' => 20,
        'replicate' => 15,
        'agentrouter' => 5,
        'nvidia' => 3,
        'opencode' => 0
    ];

    return $costs[$provider] ?? 5; // Default cost is 5 credits
}

/**
 * Handle create conversation request
 */
function handleCreateConversation($conversationManager)
{
    $id = $conversationManager->createConversation();

    echo json_encode([
        'success' => true,
        'conversationId' => $id
    ]);
}

/**
 * Handle delete conversation request
 */
function handleDeleteConversation($input, $conversationManager)
{
    $id = $input['conversationId'] ?? '';

    if (empty($id)) {
        echo json_encode(['error' => 'Conversation ID is required']);
        return;
    }

    $success = $conversationManager->deleteConversation($id);

    echo json_encode([
        'success' => $success,
        'currentConversationId' => $conversationManager->getCurrentConversationId()
    ]);
}

/**
 * Handle load conversation request
 */
function handleLoadConversation($input, $conversationManager)
{
    global $conn;
    
    $id = $input['conversationId'] ?? '';

    if (empty($id)) {
        echo json_encode(['error' => 'Conversation ID is required']);
        return;
    }

    // Get messages from database
    $userId = $_SESSION['user_id'];
    
    // Check if image_data column exists
    $hasImageColumn = false;
    $resultCheck = $conn->query("SHOW COLUMNS FROM chat_history LIKE 'image_data'");
    if ($resultCheck && $resultCheck->num_rows > 0) {
        $hasImageColumn = true;
    }
    
    if ($hasImageColumn) {
        $stmt = $conn->prepare("SELECT sender, message, image_data, mime_type FROM chat_history WHERE user_id = ? AND conversation_id = ? ORDER BY created_at ASC");
    } else {
        $stmt = $conn->prepare("SELECT sender, message FROM chat_history WHERE user_id = ? AND conversation_id = ? ORDER BY created_at ASC");
    }
    $stmt->bind_param("is", $userId, $id);
    $stmt->execute();
    $result = $stmt->get_result();

    $conversation = [];
    while ($row = $result->fetch_assoc()) {
        if ($hasImageColumn) {
            $conversation[] = [
                'sender' => $row['sender'],
                'message' => $row['message'],
                'image' => $row['image_data'],
                'mime_type' => $row['mime_type']
            ];
        } else {
            $conversation[] = [
                'sender' => $row['sender'],
                'message' => $row['message'],
                'image' => null,
                'mime_type' => null
            ];
        }
    }
    $stmt->close();

    $conversationManager->setCurrentConversation($id);

    if (!empty($conversation)) {
        echo json_encode([
            'success' => true,
            'conversation' => $conversation
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Conversation not found'
        ]);
    }
}
