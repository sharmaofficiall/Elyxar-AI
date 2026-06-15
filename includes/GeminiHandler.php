<?php
require_once __DIR__ . '/ApiHandler.php';

/**
 * Google Gemini API Handler
 */
class GeminiHandler extends ApiHandler
{

    public function generateResponse($userText, $imageBase64 = null, $imageMimeType = 'image/jpeg')
    {
        $model = $this->model ?: 'gemini-2.0-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}";

        // Build content parts
        $contentParts = [
            ['text' => $userText]
        ];

        // Add image if provided
        if ($imageBase64) {
            $contentParts[] = [
                'inlineData' => [
                    'mimeType' => $imageMimeType,
                    'data' => $imageBase64
                ]
            ];
        }

        $data = [
            'contents' => [
                [
                    'parts' => $contentParts
                ]
            ]
        ];

        $headers = [
            'Content-Type: application/json'
        ];

        $result = $this->makeRequest($url, $data, $headers);

        if (!$result['success']) {
            return $this->handleError('Gemini', $result['error']);
        }

        $responseData = json_decode($result['response'], true);

        if ($result['http_code'] !== 200) {
            return $this->handleError('Gemini', $responseData['error'] ?? 'Failed to fetch response');
        }

        // Extract response text
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            return $responseData['candidates'][0]['content']['parts'][0]['text'];
        }

        return $this->handleError('Gemini', 'Empty response from API');
    }
}
