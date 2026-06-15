<?php
require_once __DIR__ . '/ApiHandler.php';

/**
 * OpenCode Zen API Handler
 * Uses the official OpenCode Zen API endpoints
 */
class OpenCodeHandler extends ApiHandler
{
    private $baseUrl = 'https://opencode.ai/api/v1';
    protected $endpoint = '';
    private $altBaseUrls = [
        'https://opencode.ai/zen/v1',
        'https://api.opencode.ai/v1',
        'https://opencode.ai/v1',
        'https://opencode.ai/openai/v1',
        'https://zen.opencode.ai/v1'
    ];

    public function __construct($apiKey = '', $model = '', $endpoint = '')
    {
        parent::__construct($apiKey, $model);
        $this->endpoint = $endpoint ?: $this->baseUrl . '/chat/completions';
    }

    public function generateResponse($userText, $imageBase64 = null, $imageMimeType = 'image/jpeg')
    {
        $model = $this->model ?: 'minimax-m2.5-free';

        // Validate API key
        if (empty($this->apiKey)) {
            return $this->handleError('OpenCode', 'API key not configured. Please check your OpenCode API key.');
        }





        // Try multiple possible base URLs
        $baseUrls = array_merge([$this->baseUrl], $this->altBaseUrls);
        $result = null;

        foreach ($baseUrls as $baseUrl) {
            // Determine the correct endpoint based on model
            $endpoint = $this->getEndpointForModel($model, $baseUrl);

            $data = $this->prepareDataForModel($userText, $model);

            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
                'X-API-Key: ' . $this->apiKey, // Try alternative auth header
                'User-Agent: Elyxar-AI-Chat/1.0',
                'Accept: application/json'
            ];

            // Log the request for debugging
            error_log("Trying OpenCode API - BaseURL: $baseUrl, Model: $model, Endpoint: $endpoint");

            // Try the request with retry logic
            $maxRetries = 1; // Reduce retries per URL
            $retryCount = 0;

            while ($retryCount <= $maxRetries) {
                $result = $this->makeRequest($endpoint, $data, $headers);

                if ($result['success'] && !($result['is_html'] ?? false)) {
                    break 2;
                }

                $retryCount++;
                if ($retryCount <= $maxRetries) {
                    error_log("OpenCode API retry $retryCount for $baseUrl - HTTP {$result['http_code']}: " . ($result['error'] ?? 'unknown'));
                    sleep(1);
                }
            }

            // If this URL failed, continue to next one
            if (!$result['success'] || ($result['is_html'] ?? false)) {
                error_log("OpenCode API at $baseUrl failed - HTTP {$result['http_code']}: " . substr($result['response'] ?? '', 0, 100));
                continue;
            }

            // If we get here, we found a working endpoint
            break;
        }

        // If no base URL worked, show comprehensive error
        if (!$result || !$result['success'] || ($result['is_html'] ?? false)) {
            $errorMsg = '❌ OpenCode API Connection Failed<br><br>';
            $errorMsg .= 'The OpenCode Zen API appears to be unavailable or not publicly accessible.<br><br>';
            $errorMsg .= '🔍 Possible causes:<br>';
            $errorMsg .= '• OpenCode Zen may require enterprise/special access<br>';
            $errorMsg .= '• API endpoints may have changed or been deprecated<br>';
            $errorMsg .= '• Authentication method may be different<br>';
            $errorMsg .= '• Service may be temporarily unavailable<br><br>';
            $errorMsg .= '💡 Suggestions:<br>';
            $errorMsg .= '• Check OpenCode\'s official documentation<br>';
            $errorMsg .= '• Contact OpenCode support for API access<br><br>';
            $errorMsg .= '<strong>Please select a different AI provider from the dropdown.</strong>';
            return $this->handleError('OpenCode', $errorMsg);
        }

        $responseData = json_decode($result['response'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("OpenCode API Invalid JSON: " . substr($result['response'], 0, 500));
            return $this->handleError('OpenCode', 'Received invalid response from OpenCode API. Please try again.');
        }

        if ($result['http_code'] !== 200) {
            $errorMsg = isset($responseData['error'])
                ? (is_array($responseData['error']) ? ($responseData['error']['message'] ?? json_encode($responseData['error'])) : $responseData['error'])
                : "Request failed with HTTP code {$result['http_code']}";
            error_log("OpenCode API HTTP {$result['http_code']}: $errorMsg");
            return $this->handleError('OpenCode', $errorMsg);
        }

        return $this->extractResponse($responseData, $endpoint);
    }


    private function getEndpointForModel($model, $baseUrl = null)
    {
        $base = $baseUrl ?: $this->baseUrl;

        // Determine endpoint based on model type
        if (strpos($model, 'gpt-') === 0) {
            return $base . '/chat/completions'; // Try standard OpenAI format first
        } elseif (strpos($model, 'claude-') === 0) {
            return $base . '/chat/completions'; // Try standard format first
        } elseif (strpos($model, 'gemini-') === 0) {
            return $base . '/chat/completions'; // Try standard format first
        } else {
            return $base . '/chat/completions';
        }
    }

    private function prepareDataForModel($userText, $model)
    {
        // Use standard OpenAI-compatible format for all models
        return [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $userText]],
            'stream' => false,
            'temperature' => 0.7,
            'max_tokens' => 2048
        ];
    }

    private function extractResponse($responseData, $endpoint)
    {
        // Handle OpenAI-compatible response format
        if (isset($responseData['choices'][0]['message']['content'])) {
            return $responseData['choices'][0]['message']['content'];
        }

        // Check for error in response
        if (isset($responseData['error'])) {
            $errorMsg = is_array($responseData['error'])
                ? ($responseData['error']['message'] ?? json_encode($responseData['error']))
                : $responseData['error'];
            return $this->handleError('OpenCode', $errorMsg);
        }

        return $this->handleError('OpenCode', 'Unable to extract response from OpenCode API');
    }
}
