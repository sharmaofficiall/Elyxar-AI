# Chat Application - PHP Version

## Overview

This is a fully functional chat application that has been converted from JavaScript to PHP. The application now uses a PHP backend for all API communications, providing better security and server-side control.

## Features

- **Multiple AI Providers**: Supports OpenAI, Google Gemini, DeepSeek, SambaNova, OpenRouter, Pollinations, and more
- **Image Upload**: Upload images for vision-capable models (Gemini, Pollinations)
- **Conversation History**: Persistent conversation management using PHP sessions
- **Model Selection**: Choose from various models for each provider
- **Secure API Keys**: API keys stored securely on the server side

## Requirements

- PHP 7.4 or higher
- PHP cURL extension enabled
- Web server (Apache, Nginx, or PHP built-in server)
- Modern web browser

## Installation

### Option 1: Using PHP Built-in Server (Quick Start)

1. Navigate to the project directory:
   ```bash
   cd "d:\Downloads\New folder (3)"
   ```

2. Start the PHP development server:
   ```bash
   php -S localhost:8000
   ```

3. Open your browser and visit:
   ```
   http://localhost:8000/index.php
   ```

### Option 2: Using XAMPP/WAMP

1. Copy the project folder to your web server directory:
   - XAMPP: `C:\xampp\htdocs\chat-app`
   - WAMP: `C:\wamp64\www\chat-app`

2. Start Apache from XAMPP/WAMP control panel

3. Open your browser and visit:
   ```
   http://localhost/chat-app/index.php
   ```

## Configuration

### API Keys

Edit `config.php` to add or update your API keys:

```php
define('API_KEYS', [
    'openai' => 'your-openai-key-here',
    'gemini' => 'your-gemini-key-here',
    'deepseek' => 'your-deepseek-key-here',
    'sambanova' => 'your-sambanova-key-here',
    'openrouter' => 'your-openrouter-key-here',
]);
```

### Application Settings

You can modify application settings in `config.php`:

```php
define('APP_SETTINGS', [
    'session_timeout' => 3600,              // Session timeout in seconds
    'max_conversations' => 100,             // Maximum number of conversations to store
    'max_messages_per_conversation' => 1000, // Maximum messages per conversation
    'upload_max_size' => 5242880,           // Max upload size (5MB)
]);
```

## File Structure

```
├── index.php                    # Main application page
├── api.php                      # API endpoint handler
├── config.php                   # Configuration and API keys
├── style.css                    # Application styles
├── js/
│   └── app.js                   # Frontend JavaScript
└── includes/
    ├── ApiHandler.php           # Base API handler class
    ├── GeminiHandler.php        # Google Gemini handler
    ├── OpenAIHandler.php        # OpenAI/DeepSeek/AgentRouter handler
    ├── SambaNovaHandler.php     # SambaNova handler
    ├── OpenRouterHandler.php    # OpenRouter handler
    ├── PollinationsHandler.php  # Pollinations AI handler
    ├── RevangeHandler.php       # Revange API handler
    └── ConversationManager.php  # Conversation management
```

## Usage

1. **Select a Provider**: Choose your preferred AI provider from the sidebar dropdown
2. **Select a Model** (if available): Some providers offer multiple models to choose from
3. **Upload an Image** (optional): Click the attachment icon to upload an image for vision models
4. **Type Your Message**: Enter your message in the text area
5. **Send**: Press Enter or click the send button

### Conversation Management

- **New Chat**: Click the "New chat" button to start a fresh conversation
- **Load Previous Chat**: Click on any conversation in the sidebar to load it
- **Delete Chat**: Hover over a conversation and click the X button to delete it

## Providers

### Requires API Key:
- **OpenAI**: GPT-3.5 Turbo
- **Google Gemini**: Multiple models including Gemini 2.0 Flash, Gemini Pro
- **DeepSeek**: DeepSeek Chat
- **SambaNova**: Llama models
- **OpenRouter**: Access to multiple models

### No API Key Required:
- **Pollinations AI**: Text, image, and video generation
- **Revange API**: Free AI access

## Troubleshooting

### "API Error" Messages
- Check that your API keys are correctly configured in `config.php`
- Verify that the API key has sufficient credits/quota
- Check your internet connection

### "Session Error"
- Ensure PHP sessions are enabled on your server
- Check that the server has write permissions for session files

### Images Not Uploading
- Verify that the upload size doesn't exceed the limit in `config.php`
- Check PHP's `upload_max_filesize` and `post_max_size` settings

### CORS Errors (if using different domain)
- Adjust CORS headers in `config.php` for your specific domain

## Security Notes

- API keys are stored server-side and never exposed to the client
- All API requests go through the PHP backend
- Session-based authentication prevents unauthorized access to conversations
- Input validation is performed on all user inputs

## Differences from JavaScript Version

### What Changed:
- ✅ All API calls now go through PHP backend (`api.php`)
- ✅ API keys stored securely in `config.php` (server-side only)
- ✅ Conversation history uses PHP sessions instead of localStorage
- ✅ Single consolidated JavaScript file (`js/app.js`)
- ✅ Removed all individual `api_*.js` files
- ✅ Removed client-side `config.js`

### What Stayed the Same:
- ✅ UI/UX remains identical
- ✅ All features work exactly as before
- ✅ Same provider support
- ✅ Same model selection options

## Development

To modify the application:

1. **Backend Changes**: Edit files in `includes/` directory or `api.php`
2. **Frontend Changes**: Edit `js/app.js` for JavaScript or `index.php` for HTML
3. **Styling**: Modify `style.css`
4. **Configuration**: Update `config.php`

## License

This project is provided as-is for educational and personal use.

## Support

For issues or questions, please refer to the implementation plan and walkthrough documents included with this project.
