<?php
require_once __DIR__ . '/ApiHandler.php';

/**
 * Revange API Handler
 */
class RevangeHandler extends ApiHandler
{

    public function generateResponse($userText, $imageBase64 = null, $imageMimeType = 'image/jpeg')
    {
        $model = $this->model ?: 'openai-gpt-oss-120b';
        $encodedPrompt = urlencode($userText);
        $targetUrl = "https://allmodels.revangeapi.workers.dev/revangeapi/{$model}/chat?prompt={$encodedPrompt}";
        $url = "https://api.allorigins.win/raw?url=" . urlencode($targetUrl);

        $result = $this->makeRequest($url, [], [], 'GET');

        if (!$result['success']) {
            return $this->handleError('Revange', $result['error']);
        }

        if ($result['http_code'] !== 200) {
            return $this->handleError('Revange', 'Failed to fetch response');
        }

        // Try to parse JSON response
        $responseData = json_decode($result['response'], true);

        if ($responseData) {
            if (isset($responseData['response'])) {
                return $responseData['response'];
            }
            if (isset($responseData['message'])) {
                return $responseData['message'];
            }
            if (isset($responseData['choices'][0]['message']['content'])) {
                return $responseData['choices'][0]['message']['content'];
            }
            return json_encode($responseData);
        }

        // Return raw text if not JSON
        return $result['response'];
    }
}
