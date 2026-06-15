<?php
require_once __DIR__ . '/ApiHandler.php';

class NvidiaVideoHandler extends ApiHandler
{
    private $baseUrl = 'https://integrate.api.nvidia.com/v1';

    public function __construct($apiKey = '', $model = '')
    {
        parent::__construct($apiKey, $model);
    }

    public function generateResponse($userText, $imageBase64 = null, $imageMimeType = 'image/jpeg')
    {
        $model = $this->model ?: 'nvidia/cosmos-predict1-5b-text2world';

        $data = [
            'prompt' => $userText,
            'duration' => 5
        ];

        if ($imageBase64 && $imageMimeType) {
            $data['image'] = $imageBase64;
        }

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        $endpoint = $this->baseUrl . '/video/generations';

        $data['model'] = $model;

        $result = $this->makeRequest($endpoint, $data, $headers);

        if (!$result['success']) {
            return $this->handleError('NVIDIA Video', $result['error']);
        }

        $responseData = json_decode($result['response'], true);

        if ($result['http_code'] !== 200) {
            return $this->handleError('NVIDIA Video', $responseData['message'] ?? $responseData['error'] ?? 'Failed to generate video. HTTP: ' . $result['http_code'] . ' Response: ' . json_encode($responseData));
        }

        if (isset($responseData['data'][0]['b64_video'])) {
            return 'data:video/mp4;base64,' . $responseData['data'][0]['b64_video'];
        }

        if (isset($responseData['data'][0]['url'])) {
            return $responseData['data'][0]['url'];
        }

        return $this->handleError('NVIDIA Video', 'Empty response from API: ' . json_encode($responseData));
    }
}