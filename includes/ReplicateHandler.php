<?php
require_once __DIR__ . '/ApiHandler.php';

/**
 * Replicate AI Handler
 * Handles interactions with the Replicate API for running models.
 * Replicate typically uses a two-step process: create a prediction, then poll for its result.
 */
class ReplicateHandler extends ApiHandler
{
    private $providerName;
    private const API_BASE_URL = 'https://api.replicate.com/v1';

    public function __construct($apiKey = '', $model = '')
    {
        parent::__construct($apiKey, $model);
        $this->providerName = 'Replicate';
    }

    /**
     * Generates a response from a Replicate model.
     *
     * @param string $userText The user's input, usually a prompt.
     * @param string|null $imageBase64 Not directly used by Replicate's typical text/image generation.
     * @param string $imageMimeType Not directly used.
     * @return string The AI response (e.g., generated text or image URL).
     */
    public function generateResponse($userText, $imageBase64 = null, $imageMimeType = 'image/jpeg')
    {
        if (empty($this->apiKey)) {
            return $this->handleError($this->providerName, 'API key is missing.');
        }
        if (empty($this->model)) {
            return $this->handleError($this->providerName, 'Model version is missing.');
        }

        // 1. Start a prediction
        $start_prediction_url = self::API_BASE_URL . '/predictions';
        $start_prediction_headers = [
            'Authorization: Token ' . $this->apiKey, // Replicate uses "Token" prefix
            'Content-Type: application/json',
        ];

        // Assuming userText is the main prompt for the model
        // For more complex models, input might need to be structured differently
        $input_data = [
            'prompt' => $userText,
            // Additional model-specific inputs (e.g., width, height for image models)
            // would need to be passed here, potentially through $model parameter or a separate UI field.
        ];
        
        // This is a basic way to handle image generation for Replicate, 
        // assuming the model parameter might indicate a specific image model and we can add default image generation parameters
        if (strpos($this->model, 'stable-diffusion') !== false || strpos($this->model, 'img') !== false) {
             $input_data['width'] = 768; // Default value
             $input_data['height'] = 768; // Default value
             // If imageBase64 is provided, it might be used as an initial image for img2img models
             // This would require a specific model supporting img2img and further API integration details.
             // For now, focusing on text-to-image.
        }

        $start_prediction_body = json_encode([
            'version' => $this->model, // In Replicate, 'model' here refers to the model version
            'input' => $input_data,
        ]);

        $start_response = $this->makeRequest(
            $start_prediction_url,
            json_decode($start_prediction_body, true), // makeRequest expects array for data
            $start_prediction_headers,
            'POST'
        );

        if (!$start_response['success']) {
            return $this->handleError($this->providerName, $start_response['error']);
        }

        $responseData = json_decode($start_response['response'], true);

        if ($start_response['http_code'] !== 201) {
            $errorMsg = $responseData['detail'] ?? json_encode($responseData);
            return $this->handleError($this->providerName, 'Failed to start prediction: ' . $errorMsg);
        }

        $prediction_id = $responseData['id'] ?? null;
        if (!$prediction_id) {
            return $this->handleError($this->providerName, 'Prediction ID not found in start response.');
        }

        // 2. Poll for the prediction result
        $get_prediction_url = self::API_BASE_URL . '/predictions/' . $prediction_id;
        $get_prediction_headers = [
            'Authorization: Token ' . $this->apiKey,
        ];

        $max_retries = 30; // Max 30 retries (e.g., 30 * 2 seconds = 1 minute total polling time)
        $retry_delay_seconds = 2; // Wait 2 seconds between polls

        for ($i = 0; $i < $max_retries; $i++) {
            sleep($retry_delay_seconds);

            $get_response = $this->makeRequest(
                $get_prediction_url,
                [], // GET request has no body
                $get_prediction_headers,
                'GET'
            );

            if (!$get_response['success']) {
                return $this->handleError($this->providerName, $get_response['error']);
            }

            $currentData = json_decode($get_response['response'], true);

            if ($get_response['http_code'] !== 200) {
                $errorMsg = $currentData['detail'] ?? json_encode($currentData);
                return $this->handleError($this->providerName, 'Failed to fetch prediction status: ' . $errorMsg);
            }

            $current_status = $currentData['status'] ?? 'unknown';

            if ($current_status === 'succeeded') {
                $output = $currentData['output'] ?? null;
                if ($output) {
                    // Replicate output can be an array of URLs (for image models) or text
                    if (is_array($output)) {
                        // Return the first URL for now, or concatenate for multiple images
                        return $output[0] ?? json_encode($output); // Return first image URL or full output array
                    } else {
                        return $output; // Direct text output
                    }
                } else {
                    return $this->handleError($this->providerName, 'Empty output from successful prediction.');
                }
            } elseif (in_array($current_status, ['failed', 'canceled', 'global_failure'])) {
                $errorMsg = $currentData['error'] ?? json_encode($currentData);
                return $this->handleError($this->providerName, "Prediction {$current_status}: " . $errorMsg);
            }
        }

        return $this->handleError($this->providerName, 'Prediction timed out.');
    }
}
