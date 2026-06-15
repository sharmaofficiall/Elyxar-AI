<?php
require_once __DIR__ . '/ApiHandler.php';

/**
 * OpenAI API Handler (also works for DeepSeek and AgentRouter)
 */
class OpenAIHandler extends ApiHandler
{
    private $endpoint;
    private $providerName;

    public function __construct($apiKey = '', $model = '', $endpoint = 'https://api.openai.com/v1/chat/completions', $providerName = 'OpenAI')
    {
        parent::__construct($apiKey, $model);
        $this->endpoint = $endpoint;
        $this->providerName = $providerName;
    }

    public function generateResponse($userText, $imageBase64 = null, $imageMimeType = 'image/jpeg')
    {
        $model = $this->model ?: 'gpt-3.5-turbo';

        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $userText]
            ],
            'stream' => false
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        $result = $this->makeRequest($this->endpoint, $data, $headers);

        if (!$result['success']) {
            return $this->handleError($this->providerName, $result['error']);
        }

        $responseData = json_decode($result['response'], true);

        if ($result['http_code'] !== 200) {
            return $this->handleError($this->providerName, $responseData['error'] ?? 'Failed to fetch response');
        }

        // Extract response text
        if (isset($responseData['choices'][0]['message']['content'])) {
            return $responseData['choices'][0]['message']['content'];
        }

        return $this->handleError($this->providerName, 'Empty response from API');
    }
}
