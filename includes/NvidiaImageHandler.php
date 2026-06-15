<?php
require_once __DIR__ . '/ApiHandler.php';

class NvidiaImageHandler extends ApiHandler
{
    private $baseUrl = 'https://integrate.api.nvidia.com/v1';

    public function __construct($apiKey = '', $model = '')
    {
        parent::__construct($apiKey, $model);
    }

    public function generateResponse($userText, $imageBase64 = null, $imageMimeType = 'image/jpeg')
    {
        $model = $this->model ?: 'black-forest-labs/flux-1-schnell';

        $data = [
            'prompt' => $userText,
            'seed' => rand(0, 999999),
            'steps' => 30,
            'aspect_ratio' => '1:1',
            'output_format' => 'base64'
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        if (strpos($model, 'flux-1-schnell') !== false) {
            $endpoint = $this->baseUrl . '/images/generations';
            $data['model'] = 'black-forest-labs/flux-1-schnell';
        } elseif (strpos($model, 'flux-1-dev') !== false) {
            $endpoint = $this->baseUrl . '/images/generations';
            $data['model'] = 'black-forest-labs/flux-1-dev';
        } elseif (strpos($model, 'flux-2-klein') !== false) {
            $endpoint = $this->baseUrl . '/images/generations';
            $data['model'] = 'black-forest-labs/flux-2-klein-4b';
        } elseif (strpos($model, 'stable-diffusion') !== false) {
            $endpoint = $this->baseUrl . '/images/generations';
            $data['model'] = 'stabilityai/stable-diffusion-3-5-medium';
        } else {
            $endpoint = $this->baseUrl . '/images/generations';
            $data['model'] = $model;
        }

        $result = $this->makeRequest($endpoint, $data, $headers);

        if (!$result['success']) {
            return $this->handleError('NVIDIA Image', $result['error']);
        }

        $responseData = json_decode($result['response'], true);

        if ($result['http_code'] !== 200) {
            return $this->handleError('NVIDIA Image', $responseData['message'] ?? $responseData['error'] ?? 'Failed to generate image. HTTP: ' . $result['http_code'] . ' Response: ' . json_encode($responseData));
        }

        if (isset($responseData['data'][0]['b64_json'])) {
            return 'data:image/png;base64,' . $responseData['data'][0]['b64_json'];
        }

        if (isset($responseData['data'][0]['url'])) {
            return $responseData['data'][0]['url'];
        }

        return $this->handleError('NVIDIA Image', 'Empty response from API: ' . json_encode($responseData));
    }
}