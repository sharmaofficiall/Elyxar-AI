<?php
/**
 * Configuration File
 * Safe Version for GitHub Upload
 */

// Start session for conversation management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Google OAuth Configuration
define('GOOGLE_OAUTH_CONFIG', array(
    'client_id' => '',
    'client_secret' => '',
    'redirect_uri' => '',
));

// API Keys Configuration
define('API_KEYS', array(
    'openai' => '',
    'gemini' => '',
    'deepseek' => array(),
    'groq' => array(),
    'agentrouter' => '',
    'revange' => '',
    'sambanova' => '',
    'openrouter' => '',
    'stability' => array(),
    'replicate' => '',
    'klingai' => array(),
    'klingai_secret' => array(),
    'nvidia' => '',
    'opencode' => '',
    'pollinations' => '',
));

// Application Settings
define('APP_SETTINGS', array(
    'session_timeout' => 3600,
    'max_conversations' => 100,
    'max_messages_per_conversation' => 1000,
    'upload_max_size' => 5242880,
    'allowed_image_types' => array(
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ),
));

// Model Configurations
define('MODELS', array(
    'klingai' => array(
        'kling-image-v1' => 'Kling Image V1',
        'kling-image-v1-1' => 'Kling Image V1.1',
    ),

    'klingai-video' => array(
        'kling-v1-5' => 'Kling Video V1.5',
        'kling-v1' => 'Kling Video V1',
    ),

    'stability' => array(
        'stable-diffusion-xl-1024-v1-0',
    ),

    'gemini' => array(
        'gemini-2.0-flash',
        'gemini-2.0-flash-lite',
        'gemini-2.5-flash',
        'gemini-2.5-pro',
        'gemma-3-1b-it',
        'gemma-3-4b-it',
        'gemma-3-12b-it',
        'gemma-3-27b-it',
        'gemini-exp-1206',
        'deep-research-pro-preview-12-2025',
    ),

    'sambanova' => array(
        'Meta-Llama-3.3-70B-Instruct',
        'Meta-Llama-3.1-8B-Instruct',
    ),

    'openrouter' => array(
        'nex-agi/deepseek-v3.1-nex-n1:free',
        'google/gemini-2.0-flash-exp:free',
        'meta-llama/llama-3.3-70b-instruct:free',
        'mistralai/mistral-7b-instruct:free',
        'tngtech/deepseek-r1t-chimera:free',
        'Custom Model',
    ),

    'pollinations' => array(
        'text' => array(
            'openai',
        ),

        'image' => array(
            'flux',
            'flux-realism',
            'flux-cringe',
            'flux-3d',
            'flux-anime',
            'turbo',
            'seedream',
            'majesty-diffusion',
            'midijourney',
        ),

        'video' => array(
            'luma',
            'veo',
            'seedance',
        ),
    ),

    'pollinations-video' => array(
        'luma',
        'veo',
        'seedance',
    ),

    'lmarena' => array(
        'gpt-4o',
        'claude-3-5-sonnet-20240620',
        'gemini-1.5-pro-latest',
        'llama-3-70b-instruct',
        'gpt-4-turbo',
        'claude-3-opus-20240229',
        'mistral-large-latest',
        'gemini-1.5-flash-latest',
    ),

    'deepseek' => array(
        'deepseek-reasoner',
    ),

    'replicate' => array(
        'stability-ai/sdxl:39ed52f2a78e934b3ba6e2a89f5b1c712de7dfea535525255b1aa35c5565e08b',
        'blackforestlabs/flux-1.1-pro',
        'meta/llama-2-70b-chat',
    ),

    'nvidia' => array(
        'nvidia/nemotron-mini-4b-instruct',
        'nvidia/llama-3.1-nemotron-70b-instruct',
        'nvidia/llama-3.1-nemotron-70b-instruct-hf',
        'nvidia/llama-3.1-nemotron-8b-instruct',
        'nvidia/aya-expanse-8b',
        'nvidia/aya-expanse-32b',
        'nvidia/nemotron-3-nano-30b-a3b',
        'nvidia/nemotron-3-super-120b-a12b',
        'nvidia/nemotron-4-mini-hindi-4b-instruct',
        'deepseek-ai/deepseek-v3.2',
        'deepseek-ai/deepseek-v3.1',
        'qwen/qwen3.5-122b-a10b',
        'mistralai/mistral-large-3-675b-instruct-2512',
        'mistralai/mistral-nemotron',
        'mistralai/mistral-small-3.1-24b-instruct-2503',
        'mistralai/mistral-medium-3-instruct',
        'google/gemma-3n-e4b-it',
        'google/gemma-3n-e2b-it',
        'google/gemma-3-27b-it',
        'google/gemma-4-31b-it',
        'meta/llama-4-maverick-17b-128e-instruct',
        'microsoft/phi-4-mini-instruct',
        'microsoft/phi-4-multimodal-instruct',
        'minimaxai/minimax-m2.7',
    ),

    'opencode' => array(
        'minimax-m2.5-free',
        'nemotron-3-super-free',
    ),
));

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS Headers
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';

header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');

// Helper function to get API key
function getApiKey($provider)
{
    $keys = API_KEYS[$provider] ?? '';

    if (is_array($keys)) {
        if (empty($keys)) {
            return '';
        }

        return $keys[array_rand($keys)];
    }

    return $keys;
}

// Helper function to get models for provider
function getModels($provider)
{
    return MODELS[$provider] ?? [];
}
?>