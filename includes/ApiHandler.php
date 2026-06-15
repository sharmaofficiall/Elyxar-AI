<?php
/**
 * Base API Handler Class
 * Provides common functionality for all API provider handlers
 */

abstract class ApiHandler
{
    protected $apiKey;
    protected $model;

    public function __construct($apiKey = '', $model = '')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    /**
     * Abstract method to be implemented by each provider
     * @param string $userText The user's message
     * @param string|null $imageBase64 Base64 encoded image data
     * @param string $imageMimeType MIME type of the image
     * @return string The AI response
     */
    abstract public function generateResponse($userText, $imageBase64 = null, $imageMimeType = 'image/jpeg');

    /**
     * Make HTTP request using cURL
     * @param string $url The endpoint URL
     * @param array $data POST data
     * @param array $headers HTTP headers
     * @param string $method HTTP method (GET or POST)
     * @return array Response data and HTTP code
     */
    protected function makeRequest($url, $data = [], $headers = [], $method = 'POST')
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'http_code' => $httpCode
            ];
        }

        $isHtmlResponse = (strip_tags($response) !== $response);

        return [
            'success' => !$isHtmlResponse,
            'response' => $response,
            'http_code' => $httpCode,
            'is_html' => $isHtmlResponse
        ];
    }

    /**
     * Handle errors consistently
     * @param string $providerName Name of the provider
     * @param mixed $error Error message or data
     * @return string Formatted error message
     */
    protected function handleError($providerName, $error)
    {
        if (is_array($error)) {
            $errorMsg = $error['error'] ?? $error['message'] ?? json_encode($error);
        } else {
            $errorMsg = $error;
        }

        return "{$providerName} Error: {$errorMsg}";
    }

    /**
     * Validate required parameters
     * @param array $params Parameters to validate
     * @param array $required Required parameter names
     * @return bool|string True if valid, error message otherwise
     */
    protected function validateParams($params, $required)
    {
        foreach ($required as $param) {
            if (!isset($params[$param]) || empty($params[$param])) {
                return "Missing required parameter: {$param}";
            }
        }
        return true;
    }
}
