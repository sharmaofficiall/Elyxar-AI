/**
 * Elyxar AI - Main Application JavaScript
 * Handles UI interactions and communicates with PHP backend
 */

// Global variables
var lastPrompt = '';
var lastImagePrompt = '';
var lastImageUrl = '';
var isTyping = false;
var currentTool = 'chat';
var currentFileBase64 = null;
var currentFileMimeType = null;
var currentConversationId = null;
var initialLoad = true;
var userScrolledUp = false;

document.addEventListener('DOMContentLoaded', function() {
    console.log('Elyxar AI initializing...');
    
    // Get all DOM elements
    var chatContainer = document.getElementById('chatContainer');
    var messagesContainer = document.getElementById('messagesContainer');
    var welcomeScreen = document.getElementById('welcomeScreen');
    var userInput = document.getElementById('userInput');
    var sendBtn = document.getElementById('sendBtn');
    var providerSelect = document.getElementById('providerSelect');
    var modelSelect = document.getElementById('modelSelect');
    var modelSelectionContainer = document.getElementById('modelSelectionContainer');
    var imageInput = document.getElementById('imageInput');
    var imagePreviewContainer = document.getElementById('imagePreviewContainer');
    var imagePreview = document.getElementById('imagePreview');
    var removeImageBtn = document.getElementById('removeImageBtn');
    var stopBtn = document.getElementById('stopBtn');
    var newChatBtn = document.getElementById('newChatBtn');
    var newChatMobile = document.getElementById('newChatMobile');
    var logoutBtn = document.getElementById('logoutBtn');
    var mobileMenuBtn = document.getElementById('mobileMenuBtn');
    var sidebarOverlay = document.getElementById('sidebarOverlay');
    var currentToolBadge = document.getElementById('currentToolBadge');
    var historyList = document.getElementById('historyList');
    var isStopping = false;

    // Models configuration
    var models = {
        'sambanova': ['Meta-Llama-3.3-70B-Instruct', 'Meta-Llama-3.1-8B-Instruct'],
        'openrouter': ['google/gemma-3-12b-it:free', 'google/gemma-3-27b-it:free', 'google/gemini-2.0-flash-exp:free', 'meta-llama/llama-3.3-70b-instruct:free', 'mistralai/mistral-7b-instruct:free'],
        'pollinations': ['openai'],
        'pollinations-image': ['flux', 'flux-realism', 'flux-cringe', 'flux-3d', 'flux-anime', 'turbo'],
        'pollinations-video': ['ltx-2'],
        'deepseek': ['deepseek-chat', 'deepseek-reasoner'],
        'groq': ['llama-3.1-8b-instant', 'llama-3.3-70b-versatile', 'meta-llama/llama-4-maverick-17b-128e-instruct', 'qwen/qwen3-32b'],
        'stability': ['stable-diffusion-xl-1024-v1-0'],
        'nvidia': [
            'nvidia/nemotron-mini-4b-instruct',
            'nvidia/llama-3.1-nemotron-70b-instruct',
            'nvidia/llama-3.1-nemotron-70b-instruct-hf',
            'nvidia/llama-3.1-nemotron-8b-instruct',
            'nvidia/aya-expanse-8b',
            'nvidia/aya-expanse-32b',
            'nvidia/nemotron-3-nano-30b-a3b',
            'nvidia/nemotron-3-super-120b-a12b',
            'deepseek-ai/deepseek-v3.2',
            'qwen/qwen3.5-122b-a10b',
            'mistralai/mistral-large-3-675b-instruct-2512',
            'mistralai/mistral-nemotron',
            'google/gemma-4-31b-it',
            'meta/llama-4-maverick-17b-128e-instruct',
            'microsoft/phi-4-mini-instruct',
            'microsoft/phi-4-multimodal-instruct',
            'minimaxai/minimax-m2.7'
        ],
        'opencode': ['minimax-m2.5-free','nemotron-3-super-free'],
    };

    // Tool Selection
    var toolCards = document.querySelectorAll('.tool-card[data-tool]');
    var toolSelectCards = document.querySelectorAll('.tool-card[data-tool-select]');
    var exampleBtns = document.querySelectorAll('.example-btn');

    function selectTool(tool, isAutoSwitch) {
        currentTool = tool;
        
        // Update tool cards with smooth animation
        toolCards.forEach(function(card) {
            if (card.dataset.tool === tool) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });

        // Update badge
        var toolInfo = {
            'chat': { icon: 'fa-comments', text: 'Chat Mode' },
            'image': { icon: 'fa-image', text: 'Image Mode' },
            'video': { icon: 'fa-video', text: 'Video Mode' }
        };
        var info = toolInfo[tool];
        currentToolBadge.innerHTML = '<i class="fas ' + info.icon + ' mr-1"></i> ' + info.text;

        // Update placeholder
        var placeholders = {
            'chat': 'Message Elyxar AI...',
            'image': 'Describe the image you want to generate...',
            'video': 'Describe the video you want to create...'
        };
        userInput.placeholder = placeholders[tool];

        // Filter providers based on tool
        filterProvidersByTool(tool);
        
        // Only auto-select provider if this is a tool selection (not provider change)
        var providerChanged = false;
        if (!isAutoSwitch) {
            if (tool === 'image') {
                providerSelect.value = 'pollinations-image';
            } else if (tool === 'video') {
                providerSelect.value = 'pollinations-video';
            } else {
                providerSelect.value = 'pollinations';
            }
            providerChanged = true;
        }
        
        // Update model dropdown when provider changes or when tool switches
        if (providerChanged || !isAutoSwitch) {
            updateModelDropdown(providerSelect.value);
        }
        
        // Save preferences when tool changes manually
        if (!isAutoSwitch) {
            savePreferences();
        }
        
        // Show model dropdown
        modelSelectionContainer.classList.remove('hidden');
    }
    
    // Filter providers dropdown based on selected tool
    function filterProvidersByTool(tool) {
        var optgroups = providerSelect.querySelectorAll('optgroup');
        
        optgroups.forEach(function(optgroup) {
            var label = optgroup.label || '';
            if (tool === 'chat' && (label.includes('Chat') || label.includes('💬'))) {
                optgroup.style.display = '';
            } else if (tool === 'image' && (label.includes('Image') || label.includes('🖼️'))) {
                optgroup.style.display = '';
            } else if (tool === 'video' && (label.includes('Video') || label.includes('🎬'))) {
                optgroup.style.display = '';
            } else {
                optgroup.style.display = 'none';
            }
        });
    }

    toolCards.forEach(function(card) {
        card.addEventListener('click', function() {
            selectTool(card.dataset.tool, false);
        });
    });

    toolSelectCards.forEach(function(card) {
        card.addEventListener('click', function() {
            selectTool(card.dataset.toolSelect, false);
            userInput.focus();
        });
    });

    exampleBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var prompt = btn.dataset.prompt;
            userInput.value = prompt;
            userInput.style.height = 'auto';
            userInput.style.height = userInput.scrollHeight + 'px';
            sendBtn.disabled = false;
            userInput.focus();
            
            if (prompt.toLowerCase().includes('image') || prompt.toLowerCase().includes('city') || prompt.toLowerCase().includes('cat')) {
                selectTool('image', false);
            } else if (prompt.toLowerCase().includes('video') || prompt.toLowerCase().includes('waterfall')) {
                selectTool('video', false);
            } else {
                selectTool('chat', false);
            }
        });
    });

    // Textarea auto-resize
    userInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 200) + 'px';
        var hasText = this.value.trim().length > 0;
        sendBtn.disabled = !hasText;
        console.log('Input changed, hasText:', hasText, 'button disabled:', sendBtn.disabled);
    });

    userInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (!sendBtn.disabled) {
                sendMessage();
            }
        }
    });

    // Send message
    sendBtn.addEventListener('click', sendMessage);
    
    // Expose send function globally
    window.sendMessage = sendMessage;
    window.triggerSendMessage = sendMessage;
    
    // Also expose state for testing
    window.getIsTyping = function() { return isTyping; };
    window.getUserInputValue = function() { return userInput ? userInput.value : ''; };
    window.getSendBtnDisabled = function() { return sendBtn ? sendBtn.disabled : 'no btn'; };
    window.testSend = function() { 
        console.log('Testing - isTyping:', isTyping, 'userInput:', userInput ? userInput.value : 'no input', 'sendBtn:', sendBtn ? 'exists' : 'no btn'); 
        if (userInput && sendBtn) {
            console.log('Clicking send button...');
            sendBtn.click();
        }
    };

async function sendMessage() {
        var text = userInput.value.trim();
        if (isTyping || !text) {
            if (!text) userInput.focus();
            return;
        }
        
        // Store prompt for regeneration
        lastPrompt = text;
        
        // Also store as image/video prompt if in those modes
        if (currentTool === 'image' || currentTool === 'video') {
            lastImagePrompt = text;
        }

        if (welcomeScreen.style.display !== 'none') {
            welcomeScreen.style.display = 'none';
            messagesContainer.classList.remove('hidden');
        }

        appendMessage('user', text, currentFileBase64, currentFileMimeType);
        userInput.value = '';
        userInput.style.height = 'auto';
        sendBtn.disabled = true;

        clearFile();
        isTyping = true;
        isStopping = false;
        
        // Show stop button, hide send button
        sendBtn.classList.add('hidden');
        sendBtn.style.display = 'none';
        stopBtn.classList.remove('hidden');
        stopBtn.style.display = 'flex';
        
        // Determine typing state based on current tool
        var typingState = 'chat';
        if (currentTool === 'image') {
            typingState = 'image';
        } else if (currentTool === 'video') {
            typingState = 'video';
        }
        showTypingIndicator(typingState);

        try {
            var provider = providerSelect.value;
            var model = modelSelect ? modelSelect.value : '';

            var response = await fetch('api.php', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'send_message',
                    message: text,
                    provider: provider,
                    model: model,
                    imageBase64: currentFileBase64,
                    imageMimeType: currentFileMimeType,
                    conversationId: currentConversationId
                })
            });

            var data;
            var responseText = await response.text();
            
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                hideTypingIndicator();
                var errorMsg = 'Network Error: Server returned invalid response';
                if (responseText.includes('<br')) {
                    errorMsg = 'Server Error: ' + responseText.replace(/<[^>]*>/g, ' ').trim().substring(0, 200);
                }
                appendMessage('ai', errorMsg, null, null, false, true);
                sendBtn.classList.remove('hidden');
                sendBtn.style.display = 'flex';
                stopBtn.classList.add('hidden');
                stopBtn.style.display = 'none';
                isTyping = false;
                return;
            }
            
            hideTypingIndicator();

            if (isStopping) return;

            if (data.success) {
                if (currentConversationId !== data.conversationId) {
                    currentConversationId = data.conversationId;
                    var url = new URL(window.location);
                    url.searchParams.set('chat', currentConversationId);
                    history.pushState({ conversationId: currentConversationId }, '', url);
                }
                // Use typewriter effect for AI response
                appendMessage('ai', data.response, null, null, true, false, true);
                loadConversations();
                
                // Update credits display
                if (data.creditsRemaining !== undefined) {
                    updateCreditsDisplay(data.creditsRemaining);
                }
            } else {
                // Check if it's an insufficient credits error
                if (data.error && data.error.includes('Insufficient credits')) {
                    if (confirm(data.error + ' Would you like to purchase more credits?')) {
                        window.location.href = 'payment.php';
                    }
                } else {
                    appendMessage('ai', data.error || 'An error occurred', null, null, false, true);
                }
            }
        } catch (error) {
            hideTypingIndicator();
            appendMessage('ai', 'Network Error: ' + error.message, null, null, false, true);
        } finally {
            // Hide stop button, show send button (only if not manually stopped)
            if (!isStopping) {
                stopBtn.classList.add('hidden');
                stopBtn.style.display = 'none';
                sendBtn.classList.remove('hidden');
                sendBtn.style.display = 'flex';
                sendBtn.disabled = false;
                isTyping = false;
            } else {
                // If stopped, ensure send button is visible
                stopBtn.classList.add('hidden');
                stopBtn.style.display = 'none';
                sendBtn.classList.remove('hidden');
                sendBtn.style.display = 'flex';
                sendBtn.disabled = false;
            }
            
            if (!isStopping) {
                isTyping = false;
            }
        }
    }

    function appendMessage(role, text, imageBase64, imageMimeType, isNewMessage, isError, useTypewriter) {
        var container = messagesContainer;
        
        // Default speed for typewriter effect (ms per character)
        var typeSpeed = 15;
        
        var wrapper = document.createElement('div');
        wrapper.className = (role === 'user' ? 'message-user-animate' : 'message-ai-animate') + ' flex gap-4 p-5 rounded-2xl hover:bg-[#1e1e24]/50 transition group';

        var avatarDiv = document.createElement('div');
        if (role === 'ai') {
            avatarDiv.className = 'w-10 h-10 rounded-full bg-gradient-to-r from-[#10a37f] to-[#158f6e] flex items-center justify-center flex-shrink-0 shadow-lg ai-avatar-pulse';
            var robotIcon = document.createElement('i');
            robotIcon.className = 'fas fa-robot text-white text-lg';
            avatarDiv.appendChild(robotIcon);
        } else {
            avatarDiv.className = 'w-10 h-10 rounded-full bg-gradient-to-br from-[#8b5cf6] to-[#ec4899] flex items-center justify-center flex-shrink-0 font-bold text-white text-base shadow-lg';
            avatarDiv.textContent = 'U';
        }

        var contentDiv = document.createElement('div');
        contentDiv.className = 'flex-1 min-w-0' + (isError ? ' text-red-400' : '');

        // Check for markdown image syntax: ![alt](url)
        var markdownImageRegex = /!\[([^\]]*)\]\(([^)]+)\)/;
        var markdownMatch = text.match(markdownImageRegex);
        
        // Check for image URL
        var isImageUrl = role === 'ai' && (
            (text.startsWith('https://image.pollinations.ai/')) ||
            (text.startsWith('http') && (text.includes('.jpg') || text.includes('.png') || text.includes('.jpeg') || text.includes('.webp') || text.includes('image'))) ||
            (text.includes('uploads/') && (text.includes('.png') || text.includes('.jpg') || text.includes('.jpeg')))
        );

        var isVideoUrl = role === 'ai' && (
            (text.startsWith('https://gen.pollinations.ai/video/')) ||
            (text.includes('gen.pollinations.ai/video')) ||
            (text.includes('uploads/videos/')) ||
            (text.includes('.mp4'))
        );

        if (isImageUrl || markdownMatch) {
            var imageUrl = markdownMatch ? markdownMatch[2] : text;
            var imagePrompt = '';
            
            if (!markdownMatch && imageUrl.includes('?')) {
                imageUrl = imageUrl.split('?')[0];
            }
            
            // Extract prompt from URL if available
            if (imageUrl.includes('prompt=')) {
                try {
                    var urlObj = new URL(imageUrl.split('?')[0]);
                    imagePrompt = urlObj.searchParams.get('prompt') || lastImagePrompt;
                } catch(e) {
                    imagePrompt = lastImagePrompt;
                }
            } else {
                imagePrompt = lastImagePrompt;
            }
            
            // Store for regeneration
            lastImageUrl = imageUrl;
            if (imagePrompt) {
                lastImagePrompt = imagePrompt;
            }
            
            var imageDiv = document.createElement('div');
            imageDiv.className = 'image-result mb-3 relative inline-block w-full';
            
            var img = document.createElement('img');
            img.src = imageUrl;
            img.alt = 'Generated Image';
            img.className = 'w-full max-w-xl mx-auto rounded-lg';
            img.loading = 'lazy';
            imageDiv.appendChild(img);
            
            // Add regenerate button
            if (role === 'ai') {
                var regenBtn = document.createElement('button');
                regenBtn.className = 'regenerate-btn absolute top-2 right-2 bg-white/90 hover:bg-white text-gray-800 rounded-full p-2 shadow-lg transition';
                regenBtn.title = 'Regenerate with new prompt';
                regenBtn.onclick = function() { window.regenerateImage(); };
                var regenIcon = document.createElement('i');
                regenIcon.className = 'fas fa-redo';
                regenBtn.appendChild(regenIcon);
                imageDiv.appendChild(regenBtn);
                
                // Add "Add More" button
                var addMoreBtn = document.createElement('button');
                addMoreBtn.className = 'regenerate-btn absolute top-2 right-12 bg-white/90 hover:bg-white text-gray-800 rounded-full p-2 shadow-lg transition';
                addMoreBtn.title = 'Generate more images with same prompt';
                addMoreBtn.onclick = function() { window.addMoreImages(); };
                var addMoreIcon = document.createElement('i');
                addMoreIcon.className = 'fas fa-plus';
                addMoreBtn.appendChild(addMoreIcon);
                imageDiv.appendChild(addMoreBtn);
            }
            contentDiv.appendChild(imageDiv);
        } else if (isVideoUrl) {
            var rawText = text.trim();
            var videoUrl = '';
            
            var match = rawText.match(/https:\/\/gen\.pollinations\.ai\/video\/[^\s\)\"\'\n]+/);
            if (match) {
                videoUrl = match[0];
            }
            
            var localMatch = rawText.match(/uploads\/videos\/[^\s\)\"\'\n]+/);
            if (localMatch) {
                videoUrl = localMatch[0];
            }
            
            if (!videoUrl && rawText.includes('.mp4')) {
                videoUrl = rawText.split('.mp4')[0] + '.mp4';
                videoUrl = videoUrl.replace(/<[^>]*>/g, '').trim();
            }
            
            if (!videoUrl) {
                videoUrl = rawText;
            }
            
            var videoDiv = document.createElement('div');
            videoDiv.className = 'video-result mb-3 relative inline-block w-full';
            
            var loadingDiv = document.createElement('div');
            loadingDiv.className = 'video-loading flex items-center justify-center p-8';
            loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin text-2xl text-green-400"></i> <span class="ml-2 text-gray-400">Generating video...</span>';
            videoDiv.appendChild(loadingDiv);
            
            var video = document.createElement('video');
            video.controls = true;
            video.loop = true;
            video.muted = true;
            video.className = 'w-full max-w-xl mx-auto hidden';
            
            video.oncanplay = function() {
                loadingDiv.classList.add('hidden');
                video.classList.remove('hidden');
                video.play();
            };
            
            video.onerror = function() {
                loadingDiv.innerHTML = '<span class="text-yellow-400">Processing... <a href="' + videoUrl + '" target="_blank" class="underline">Open video</a></span>';
            };
            
            video.src = videoUrl;
            videoDiv.appendChild(video);
            
            // Add regenerate button for video
            if (role === 'ai') {
                var regenBtn = document.createElement('button');
                regenBtn.className = 'regenerate-btn absolute top-2 right-2 bg-white/90 hover:bg-white text-gray-800 rounded-full p-2 shadow-lg transition';
                regenBtn.title = 'Regenerate with new prompt';
                regenBtn.onclick = function() { window.regenerateVideo(); };
                var regenIcon = document.createElement('i');
                regenIcon.className = 'fas fa-redo';
                regenBtn.appendChild(regenIcon);
                videoDiv.appendChild(regenBtn);
                
                // Add "Add More" button for video
                var addMoreBtn = document.createElement('button');
                addMoreBtn.className = 'regenerate-btn absolute top-2 right-12 bg-white/90 hover:bg-white text-gray-800 rounded-full p-2 shadow-lg transition';
                addMoreBtn.title = 'Generate more videos with same prompt';
                addMoreBtn.onclick = function() { window.addMoreVideos(); };
                var addMoreIcon = document.createElement('i');
                addMoreIcon.className = 'fas fa-plus';
                addMoreBtn.appendChild(addMoreIcon);
                videoDiv.appendChild(addMoreBtn);
            }
            contentDiv.appendChild(videoDiv);
        } else {
            // Regular text message
            var textDiv = document.createElement('div');
            textDiv.className = 'markdown-content text-base text-gray-200 leading-relaxed';
            
            // Apply typewriter effect for AI messages if enabled
            if (useTypewriter && role === 'ai' && text && text.length > 0) {
                // Use plain text for typewriter, then apply HTML
                textDiv.textContent = text;
                textDiv.className = 'prose prose-invert max-w-none text-base';
                
                // Apply typewriter animation
                var chars = textDiv.textContent.split('');
                textDiv.textContent = '';
                textDiv.dataset.fullText = text;
                
                var charIndex = 0;
                var typeInterval = setInterval(function() {
                    if (charIndex < chars.length) {
                        textDiv.textContent += chars[charIndex];
                        // Only auto-scroll if user hasn't scrolled up manually
                        if (!userScrolledUp) {
                            chatContainer.scrollTop = chatContainer.scrollHeight;
                        }
                        charIndex++;
                    } else {
                        clearInterval(typeInterval);
                        // Now apply HTML formatting after typewriter is done
                        textDiv.innerHTML = formatMessage(text);
                        // Reset scroll state after message is complete
                        userScrolledUp = false;
                    }
                }, typeSpeed);
                typewriterTimeouts.push(typeInterval);
            } else {
                textDiv.innerHTML = formatMessage(text);
            }
            contentDiv.appendChild(textDiv);
        }

        wrapper.appendChild(avatarDiv);
        wrapper.appendChild(contentDiv);
        container.appendChild(wrapper);
        
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    function formatMessage(text) {
        // First escape all HTML entities for security
        var escaped = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
        
        // Use marked.js for full Markdown support
        if (typeof marked !== 'undefined') {
            marked.setOptions({
                breaks: true,
                gfm: true
            });
            return marked.parse(escaped);
        }
        
        // Fallback: basic formatting
        var formatted = escaped;
        
        // Code blocks
        formatted = formatted.replace(/```(\w+)?\n([\s\S]*?)```/g, function(match, lang, code) {
            return '<pre class="bg-gray-900 rounded-lg p-4 overflow-x-auto my-3"><code class="font-mono text-sm text-green-400">' + code.trim() + '</code></pre>';
        });

        // Inline code
        formatted = formatted.replace(/`([^`]+)`/g, '<code class="bg-gray-800 px-1.5 py-0.5 rounded text-sm font-mono text-pink-400">$1</code>');
        
        // Bold
        formatted = formatted.replace(/\*\*([^*]+)\*\*/g, '<strong class="font-semibold text-white">$1</strong>');
        formatted = formatted.replace(/__([^_]+)__/g, '<strong class="font-semibold text-white">$1</strong>');
        
        // Italic
        formatted = formatted.replace(/\*([^*]+)\*/g, '<em class="text-gray-300">$1</em>');
        formatted = formatted.replace(/_([^_]+)_/g, '<em class="text-gray-300">$1</em>');
        
        // Headings
        formatted = formatted.replace(/^### (.+)$/gm, '<h3 class="text-lg font-semibold text-white mt-4 mb-2">$1</h3>');
        formatted = formatted.replace(/^## (.+)$/gm, '<h2 class="text-xl font-semibold text-white mt-4 mb-2">$1</h2>');
        formatted = formatted.replace(/^# (.+)$/gm, '<h1 class="text-2xl font-bold text-white mt-4 mb-2">$1</h1>');
        
        // Links
        formatted = formatted.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" class="text-green-400 hover:underline">$1</a>');
        
        // Lists
        formatted = formatted.replace(/^\* (.+)$/gm, '<li class="ml-4 text-gray-300">$1</li>');
        formatted = formatted.replace(/^- (.+)$/gm, '<li class="ml-4 text-gray-300">$1</li>');
        formatted = formatted.replace(/^\d+\. (.+)$/gm, '<li class="ml-4 text-gray-300">$1</li>');
        
        // Line breaks
        formatted = formatted.replace(/\n/g, '<br>');

        return formatted;
    }

    function showTypingIndicator(state) {
        var template = document.getElementById('typingTemplate');
        if (template) {
            var clone = template.content.cloneNode(true);
            var typingText = clone.querySelector('.typing-text');
            
            // States to cycle through
            var states = state || 'thinking';
            var stateIndex = 0;
            
            // Update text based on state
            function updateTypingText() {
                typingText.textContent = 'Thinking';
            }
            
            updateTypingText();
            
            // Cycle through states every 1.5 seconds
            var stateInterval = setInterval(updateTypingText, 1500);
            
            // Store interval on the element to clear it later
            clone.querySelector('.typing-dots').dataset.interval = stateInterval;
            
            messagesContainer.appendChild(clone);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    }

    function hideTypingIndicator() {
        var typing = messagesContainer.querySelector('.message-enter');
        if (typing) {
            var dots = typing.querySelector('.typing-dots');
            if (dots && dots.dataset.interval) {
                clearInterval(parseInt(dots.dataset.interval));
            }
            typing.remove();
        }
    }

    // Typewriter effect for AI responses
    var typewriterTimeouts = [];
    
    function typeWriter(element, text, speed, callback) {
        var i = 0;
        element.textContent = '';
        
        function type() {
            if (i < text.length) {
                element.textContent += text.charAt(i);
                i++;
                chatContainer.scrollTop = chatContainer.scrollHeight;
                
                var timeout = setTimeout(type, speed);
                typewriterTimeouts.push(timeout);
            } else if (callback) {
                callback();
            }
        }
        type();
    }
    
    function stopTypewriter() {
        typewriterTimeouts.forEach(function(timeout) {
            clearTimeout(timeout);
        });
        typewriterTimeouts = [];
    }

    // Function to update model dropdown based on selected provider
    function updateModelDropdown(provider) {
        console.log('Updating model dropdown for provider:', provider);
        
        if (models[provider]) {
            modelSelect.innerHTML = '';
            models[provider].forEach(function(m) {
                var option = document.createElement('option');
                option.value = m;
                option.textContent = m;
                modelSelect.appendChild(option);
            });
            
            // Auto-select first model if current model is not available for this provider
            var currentModel = modelSelect.value;
            var modelAvailable = false;
            for (var i = 0; i < modelSelect.options.length; i++) {
                if (modelSelect.options[i].value === currentModel) {
                    modelAvailable = true;
                    break;
                }
            }
            
            // If current model not available, select first available model
            if (!modelAvailable && modelSelect.options.length > 0) {
                modelSelect.selectedIndex = 0;
                console.log('Auto-selected first model:', modelSelect.value);
            }
            
            modelSelectionContainer.classList.remove('hidden');
            console.log('Model dropdown shown');
        } else {
            modelSelectionContainer.classList.add('hidden');
            console.log('Model dropdown hidden, no models for:', provider);
        }
    }

    // Save and Load preferences from localStorage
    function savePreferences() {
        localStorage.setItem('aiNexus_provider', providerSelect.value);
        localStorage.setItem('aiNexus_model', modelSelect.value);
        localStorage.setItem('aiNexus_tool', currentTool);
    }

    function loadPreferences() {
        var savedProvider = localStorage.getItem('aiNexus_provider');
        var savedModel = localStorage.getItem('aiNexus_model');
        var savedTool = localStorage.getItem('aiNexus_tool');
        
        console.log('Loading preferences:', savedProvider, savedModel, savedTool);
        
        // If no saved preferences, use defaults
        if (!savedProvider) {
            savedProvider = 'pollinations';
        }
        if (!savedTool) {
            savedTool = 'chat';
        }
        
        // Set provider and populate models
        providerSelect.value = savedProvider;
        updateModelDropdown(savedProvider);
        
        // Try to restore model if saved and available
        if (savedModel) {
            for (var i = 0; i < modelSelect.options.length; i++) {
                if (modelSelect.options[i].value === savedModel) {
                    modelSelect.value = savedModel;
                    break;
                }
            }
        }
        
        // Set tool
        selectTool(savedTool, true);
    }

    // Provider change handler
    if (providerSelect) {
        providerSelect.addEventListener('change', function() {
            console.log('Provider changed to:', providerSelect.value);
            updateModelDropdown(providerSelect.value);
            savePreferences();
            
// Auto-detect tool based on provider name
            var provider = providerSelect.value;
            if (provider === 'pollinations-image' || provider === 'stability' || provider === 'klingai') {
                setTool('image');
            } else if (provider === 'pollinations-video' || provider === 'klingai-video') {
                setTool('video');
            }
        });
    }

    // Save preferences when model changes
    if (modelSelect) {
        modelSelect.addEventListener('change', savePreferences);
    }

    // Initialize model dropdown - load saved preferences
    loadPreferences();
    
    // Track when user scrolls up manually
    if (chatContainer) {
        chatContainer.addEventListener('scroll', function() {
            var isAtBottom = chatContainer.scrollHeight - chatContainer.scrollTop <= chatContainer.clientHeight + 50;
            userScrolledUp = !isAtBottom;
        });
    }

    // Image upload
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    currentFileBase64 = e.target.result.split(',')[1];
                    currentFileMimeType = file.type;
                    imagePreview.src = e.target.result;
                    imagePreviewContainer.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });
    }

    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', clearFile);
    }
    
    // Stop button handler
    if (stopBtn) {
        stopBtn.addEventListener('click', function() {
            isStopping = true;
            isTyping = false;
            
            // Clear any typewriter timeouts
            typewriterTimeouts.forEach(function(timeout) {
                clearTimeout(timeout);
            });
            typewriterTimeouts = [];
            
            // Hide stop button, show send button only if not stopped
            if (!isStopping) {
                stopBtn.classList.add('hidden');
                stopBtn.style.display = 'none';
                sendBtn.classList.remove('hidden');
                sendBtn.style.display = 'flex';
                sendBtn.disabled = false;
            }
            
            // Hide typing indicator
            hideTypingIndicator();
            
            // Remove any partially generated message
            var lastMessage = messagesContainer.lastElementChild;
            if (lastMessage && lastMessage.querySelector('.typing-dots')) {
                lastMessage.remove();
            }
        });
    }

    function clearFile() {
        if (imageInput) imageInput.value = '';
        currentFileBase64 = null;
        currentFileMimeType = null;
        if (imagePreview) imagePreview.src = '';
        if (imagePreviewContainer) imagePreviewContainer.classList.add('hidden');
    }

    // New chat
    if (newChatBtn) {
        newChatBtn.addEventListener('click', startNewChat);
    }
    if (newChatMobile) {
        newChatMobile.addEventListener('click', startNewChat);
    }

    function startNewChat() {
        messagesContainer.innerHTML = '';
        messagesContainer.classList.add('hidden');
        welcomeScreen.style.display = 'block';
        currentConversationId = null;
        
        // Show a random example prompt in input
        var examplePrompts = [
            'Write a Python function to calculate fibonacci numbers with explanation',
            'Generate a beautiful landscape image with mountains and sunset',
            'Explain how machine learning and neural networks work in simple terms',
            'Write a short story about a time traveler who meets their younger self',
            'Solve this math problem: If a train travels 120km in 2 hours, what is its speed?',
            'Translate this to French: Hello, how are you today?',
            'Summarize the key benefits of exercise and healthy eating',
            'Write a beautiful poem about nature and seasons',
            'Help me create a responsive website with HTML and CSS',
            'What are the best practices for healthy sleeping habits?',
            'Explain the theory of relativity in simple words',
            'Write a creative advertisement for a new smartphone'
        ];
        var randomPrompt = examplePrompts[Math.floor(Math.random() * examplePrompts.length)];
        userInput.placeholder = randomPrompt;
        
        var url = new URL(window.location);
        url.searchParams.delete('chat');
        history.pushState({}, '', url);
        
        closeSidebar();
    }

    // Conversation Management
    async function loadConversations() {
        console.log('Loading conversations...');
        try {
            var response = await fetch('api.php', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'get_conversations'
                })
            });

            var data = await response.json();
            console.log('Conversations loaded:', data);

            if (data.success) {
                updateSidebarHistory(data.conversations, data.currentConversationId);
                if (initialLoad) {
                    initialLoad = false;
                    var urlParams = new URLSearchParams(window.location.search);
                    var chatId = urlParams.get('chat');
                    if (chatId) {
                        loadConversation(chatId);
                    }
                }
            }
        } catch (error) {
            console.error('Error loading conversations:', error);
        }
    }

    function updateSidebarHistory(conversations, currentId) {
        console.log('Updating sidebar history:', conversations);
        
        if (!historyList) {
            console.log('History list not found!');
            return;
        }

        historyList.innerHTML = '';

        if (conversations.length === 0) {
            historyList.innerHTML = '<li class="text-dark-400 text-sm p-4 text-center">No chats yet</li>';
            return;
        }

        conversations.slice(0, 15).forEach(function(conv) {
            var li = document.createElement('li');
            var isActive = conv.id === currentId;
            li.className = 'p-2.5 rounded-lg cursor-pointer transition flex items-center gap-2 ' + (isActive ? 'bg-green-500/20 text-green-400 border-l-2 border-green-500' : 'hover:bg-dark-700/50 text-dark-300 hover:text-dark-200');
            li.dataset.convId = conv.id;
            li.innerHTML = '<i class="fas fa-message text-sm opacity-60"></i><span class="truncate text-sm font-medium flex-1">' + (conv.title || 'New Chat') + '</span><button class="delete-conv-btn p-1 rounded hover:bg-red-500/20 text-dark-500 hover:text-red-400 transition opacity-0 group-hover:opacity-100" title="Delete"><i class="fas fa-trash text-xs"></i></button>';
            
            // Add click handler for conversation
            li.addEventListener('click', function(e) {
                // Don't load conversation if delete button was clicked
                if (e.target.closest('.delete-conv-btn')) {
                    return;
                }
                console.log('Clicked conversation:', conv.id);
                loadConversation(conv.id);
                closeSidebar();
            });
            
            // Add delete button handler
            var deleteBtn = li.querySelector('.delete-conv-btn');
            deleteBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                deleteConversation(conv.id);
            });
            
            // Show delete button on hover
            li.classList.add('group');
            
            historyList.appendChild(li);
        });
        
        console.log('Sidebar updated, items:', historyList.children.length);
    }
    
    // Delete conversation
    async function deleteConversation(conversationId) {
        if (!confirm('Are you sure you want to delete this chat?')) {
            return;
        }
        
        try {
            var response = await fetch('api.php', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete_conversation',
                    conversationId: conversationId
                })
            });

            var data = await response.json();
            console.log('Delete response:', data);

            if (data.success) {
                // Reload conversations
                loadConversations();
                
                // If deleted current conversation, show welcome screen
                if (conversationId === currentConversationId) {
                    messagesContainer.innerHTML = '';
                    messagesContainer.classList.add('hidden');
                    welcomeScreen.style.display = 'block';
                    currentConversationId = null;
                }
            } else {
                alert('Failed to delete conversation: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error deleting conversation:', error);
            alert('Error deleting conversation');
        }
    }

    async function loadConversation(id) {
        console.log('Loading conversation:', id);
        try {
            var response = await fetch('api.php', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'load_conversation',
                    conversationId: id
                })
            });

            var data = await response.json();
            console.log('Conversation loaded:', data);

            if (data.success && data.conversation) {
                currentConversationId = id;
                messagesContainer.innerHTML = '';
                messagesContainer.classList.remove('hidden');
                welcomeScreen.style.display = 'none';

                data.conversation.forEach(function(msg) {
                    appendMessage(msg.sender, msg.message, msg.image, msg.mime_type, false);
                });

                var url = new URL(window.location);
                url.searchParams.set('chat', id);
                history.pushState({ conversationId: id }, '', url);
            }
        } catch (error) {
            console.error('Error loading conversation:', error);
        }
    }

    // Mobile menu
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            var sidebar = document.querySelector('.sidebar');
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('active');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }

    function closeSidebar() {
        var sidebar = document.querySelector('.sidebar');
        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('active');
    }

    // Logout
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout' })
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        window.location.href = 'home.php';
                    }
                })
                .catch(function() {
                    window.location.href = 'home.php';
                });
            }
        });
    }

    // Initial load
    console.log('Calling loadConversations...');
    loadConversations();
    loadCredits();
    
    // Filter providers based on default tool (chat)
    filterProvidersByTool('chat');
    
    console.log('Elyxar AI initialized successfully');
    
    // Load user credits
    function loadCredits() {
        console.log('Loading credits...');
        fetch('api.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'get_credits'
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            console.log('Credits loaded:', data);
            if (data.success) {
                var creditsCount = document.getElementById('creditsCount');
                var headerCredits = document.getElementById('headerCredits');
                if (creditsCount) {
                    creditsCount.textContent = data.credits.toLocaleString();
                }
                if (headerCredits) {
                    headerCredits.textContent = data.credits.toLocaleString();
                }
            }
        })
        .catch(function(error) {
            console.error('Error loading credits:', error);
        });
    }
    
    // Update credits display after sending message
    function updateCreditsDisplay(remainingCredits) {
        var creditsCount = document.getElementById('creditsCount');
        var headerCredits = document.getElementById('headerCredits');
        if (creditsCount) {
            creditsCount.textContent = remainingCredits.toLocaleString();
        }
        if (headerCredits) {
            headerCredits.textContent = remainingCredits.toLocaleString();
        }
    }
});

// Regenerate image function
function regenerateImage() {
    var newPrompt = prompt('Enter a new prompt for regeneration:', lastImagePrompt || '');
    if (newPrompt && newPrompt.trim()) {
        lastImagePrompt = newPrompt;
        document.getElementById('userInput').value = newPrompt;
        document.getElementById('userInput').style.height = 'auto';
        document.getElementById('userInput').style.height = document.getElementById('userInput').scrollHeight + 'px';
        document.getElementById('sendBtn').disabled = false;
        document.getElementById('sendBtn').click();
    }
}

// Add more images function - generates more with same prompt
function addMoreImages() {
    var inputEl = document.getElementById('userInput');
    var btnEl = document.getElementById('sendBtn');
    
    if (lastImagePrompt) {
        // Directly generate with same prompt - no prompt dialog
        inputEl.value = lastImagePrompt;
        inputEl.style.height = 'auto';
        inputEl.style.height = inputEl.scrollHeight + 'px';
        btnEl.disabled = false;
        btnEl.click();
    } else if (lastPrompt) {
        // Fallback to lastPrompt if lastImagePrompt is empty
        inputEl.value = lastPrompt;
        inputEl.style.height = 'auto';
        inputEl.style.height = inputEl.scrollHeight + 'px';
        btnEl.disabled = false;
        btnEl.click();
    } else {
        alert('No previous image prompt found. Please generate an image first.');
    }
}

// Regenerate video function
function regenerateVideo() {
    var newVideoPrompt = prompt('Enter a new prompt for regeneration:', lastImagePrompt || '');
    if (newVideoPrompt && newVideoPrompt.trim()) {
        lastImagePrompt = newVideoPrompt;
        selectTool('video', false);
        document.getElementById('userInput').value = newVideoPrompt;
        document.getElementById('userInput').style.height = 'auto';
        document.getElementById('userInput').style.height = document.getElementById('userInput').scrollHeight + 'px';
        document.getElementById('sendBtn').disabled = false;
        document.getElementById('sendBtn').click();
    }
}

// Add more videos function - generates more with same prompt
function addMoreVideos() {
    var inputEl = document.getElementById('userInput');
    var btnEl = document.getElementById('sendBtn');
    
    if (lastImagePrompt) {
        // Switch to video tool and generate
        if (typeof selectTool === 'function') {
            selectTool('video', false);
        }
        inputEl.value = lastImagePrompt;
        inputEl.style.height = 'auto';
        inputEl.style.height = inputEl.scrollHeight + 'px';
        btnEl.disabled = false;
        btnEl.click();
    } else if (lastPrompt) {
        // Fallback to lastPrompt
        if (typeof selectTool === 'function') {
            selectTool('video', false);
        }
        inputEl.value = lastPrompt;
        inputEl.style.height = 'auto';
        inputEl.style.height = inputEl.scrollHeight + 'px';
        btnEl.disabled = false;
        btnEl.click();
    } else {
        alert('No previous video prompt found. Please generate a video first.');
    }
}

// Global send message handler
window.dispatchSendMessage = function() {
    var userInput = document.getElementById('userInput');
    var sendBtn = document.getElementById('sendBtn');
    console.log('dispatchSendMessage called, input:', userInput ? userInput.value : 'no input', 'btn:', sendBtn ? 'exists' : 'no btn');
    if (userInput && sendBtn) {
        var text = userInput.value.trim();
        if (text) {
            sendBtn.disabled = false;
            sendBtn.click();
        } else {
            console.log('No text to send');
        }
    }
};

// Expose sendMessage globally for inline onclick
window.sendMessage = function() {
    if (typeof window.triggerSendMessage === 'function') {
        window.triggerSendMessage();
    }
};
