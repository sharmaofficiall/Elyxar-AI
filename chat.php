<?php
require_once 'config.php';
require_once 'db_connect.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$username = $_SESSION['username'] ?? 'User';
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Get user plan
$stmt = $conn->prepare("SELECT plan FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();
$userPlan = ucfirst($userData['plan'] ?? 'Free');
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elyxar AI - Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'gpt': {
                            dark: '#0d0d0f',
                            darker: '#171717',
                            card: '#202123',
                            hover: '#2a2b32',
                            border: '#3f3f46',
                            input: '#343541',
                            user: '#343541',
                            ai: '#0d0d0f',
                            green: '#10a37f',
                            greenHover: '#1a7f64'
                        }
                    },
                    fontFamily: { sans: ['Sora', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { color-scheme: dark; }
        body { 
            font-family: 'Sora', sans-serif; 
            background: #0d0d0f; 
            color: #ececec;
            background-image: radial-gradient(ellipse at top, #1a1a2e 0%, #0d0d0f 50%);
        }
        
        /* Custom scrollbar */
        .scrollbar-thin::-webkit-scrollbar { width: 6px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 3px; }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: #525252; }
        
        /* Message animations */
        .message-enter { animation: slideUp 0.3s ease-out; }
        @keyframes slideUp { 
            from { opacity: 0; transform: translateY(10px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        
        /* Typing indicator */
        .typing-dots .dot {
            animation: bounce 1.4s infinite ease-in-out both;
        }
        .typing-dots .dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dots .dot:nth-child(2) { animation-delay: -0.16s; }
        .typing-dots .dot:nth-child(3) { animation-delay: 0s; }
        
        @keyframes bounce {
            0%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-4px); }
        }
        
        /* Tool cards */
        .tool-card { 
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); 
            background: linear-gradient(145deg, #1f1f23 0%, #17171a 100%);
        }
        .tool-card:hover { 
            transform: translateY(-4px); 
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
        }
        .tool-card.active { 
            border-color: #10a37f !important; 
            box-shadow: 0 0 25px rgba(16, 163, 127, 0.3);
            background: linear-gradient(145deg, rgba(16, 163, 127, 0.15) 0%, rgba(16, 163, 127, 0.05) 100%);
        }
        
        /* Chat items */
        .chat-item { transition: all 0.15s ease; }
        .chat-item:hover { background: rgba(255,255,255,0.05); }
        .chat-item.active { 
            background: rgba(16, 163, 127, 0.15); 
            border-left: 2px solid #10a37f; 
        }
        
        /* Input styling */
        .input-wrapper {
            background: linear-gradient(180deg, #202123 0%, #17171a 100%);
            border: 1px solid #3f3f46;
            transition: all 0.2s ease;
        }
        .input-wrapper:focus-within {
            border-color: #10a37f;
            box-shadow: 0 0 0 2px rgba(16, 163, 127, 0.15), 0 4px 20px rgba(0,0,0,0.3);
        }
        
        /* Markdown styling */
        .markdown-content { line-height: 1.7; }
        .markdown-content h1 { font-size: 1.5rem; font-weight: 700; margin: 1rem 0 0.5rem; color: #fff; }
        .markdown-content h2 { font-size: 1.25rem; font-weight: 600; margin: 0.875rem 0 0.5rem; color: #fff; }
        .markdown-content h3 { font-size: 1.1rem; font-weight: 600; margin: 0.75rem 0 0.5rem; color: #fff; }
        .markdown-content p { margin: 0.5rem 0; }
        .markdown-content ul, .markdown-content ol { margin: 0.5rem 0; padding-left: 1.5rem; }
        .markdown-content li { margin: 0.25rem 0; }
        .markdown-content code { background: #2d2d33; padding: 0.2rem 0.4rem; border-radius: 4px; font-family: 'JetBrains Mono', monospace; font-size: 0.875rem; color: #f472b6; }
        .markdown-content pre { background: #1a1a1e; padding: 1rem; border-radius: 8px; overflow-x: auto; margin: 0.75rem 0; border: 1px solid #3f3f46; }
        .markdown-content pre code { background: transparent; padding: 0; color: #10a37f; }
        .markdown-content a { color: #10a37f; text-decoration: none; }
        .markdown-content a:hover { text-decoration: underline; }
        .markdown-content blockquote { border-left: 3px solid #10a37f; padding-left: 1rem; margin: 0.75rem 0; color: #a3a3a4; font-style: italic; }
        .markdown-content hr { border: none; border-top: 1px solid #3f3f46; margin: 1rem 0; }
        .markdown-content table { width: 100%; border-collapse: collapse; margin: 0.75rem 0; }
        .markdown-content th, .markdown-content td { border: 1px solid #3f3f46; padding: 0.5rem; text-align: left; }
        .markdown-content th { background: #2d2d33; }
        
        /* Button effects */
        .btn-send {
            background: linear-gradient(135deg, #10a37f 0%, #158f6e 100%);
            transition: all 0.2s ease;
        }
        .btn-send:hover:not(:disabled) {
            background: linear-gradient(135deg, #12b390 0%, #1a9f7d 100%);
        }
        .btn-send:active:not(:disabled) {
            transform: scale(0.95);
        }
        
        /* Welcome glow */
        .welcome-glow {
            background: radial-gradient(circle at 50% 30%, rgba(16, 163, 127, 0.1) 0%, transparent 60%);
        }
        
        /* Example buttons */
        .example-btn {
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }
        .example-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
            transition: left 0.5s ease;
        }
        .example-btn:hover::before { left: 100%; }
        .example-btn:hover { transform: translateY(-2px); }
        
        /* Image result */
        .image-result img {
            max-width: 100%;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            transition: transform 0.3s ease;
        }
        .image-result img:hover { transform: scale(1.02); }
        
        /* Regenerate button */
        .regenerate-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(255,255,255,0.95);
            border-radius: 50%;
            padding: 8px;
            transition: all 0.2s;
        }
        .regenerate-btn:hover {
            background: white;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        /* Sidebar */
        .sidebar {
            background: #171717;
            border-right: 1px solid #3f3f46;
        }
        
        /* Message bubbles */
        .message-user-bubble {
            background: linear-gradient(135deg, #2d2d33 0%, #262629 100%);
            border: 1px solid #3f3f46;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col overflow-hidden">
    <!-- Header -->
    <header class="h-14 flex items-center justify-between px-4 border-b border-[#3f3f46]/50 bg-[#17171a]/90 backdrop-blur-xl shrink-0 z-50">
        <div class="flex items-center gap-3">
            <button id="mobileMenuBtn" class="lg:hidden p-2 hover:bg-[#2d2d33] rounded-lg transition" onclick="toggleSidebar()">
                <i class="fas fa-bars text-sm text-gray-300"></i>
            </button>
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#10a37f] to-[#158f6e] flex items-center justify-center shadow-lg shadow-[#10a37f]/20">
                    <i class="fas fa-brain text-white text-sm"></i>
                </div>
                <h1 class="text-lg font-semibold text-white">Elyxar AI</h1>
            </div>
            <span id="currentToolBadge" class="hidden px-2.5 py-1 rounded-lg text-xs font-medium bg-[#10a37f]/20 text-[#10a37f] ml-2"></span>
        </div>
        <div class="flex items-center gap-3">
            <a href="payment.php" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-[#2d2d33]/80 border border-[#3f3f46] hover:bg-[#3d3d42] transition text-sm font-medium text-gray-300">
                <i class="fas fa-coins text-[#fbbf24]"></i>
                <span id="headerCredits" class="font-semibold text-white">...</span>
            </a>
            <a href="payment.php" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-[#10a37f] to-[#158f6e] text-white text-sm font-semibold hover:from-[#12b390] hover:to-[#1a9f7d] transition shadow-lg shadow-[#10a37f]/20">
                <i class="fas fa-plus text-xs"></i>
                Upgrade
            </a>
        </div>
    </header>

    <!-- Main Layout -->
    <div class="flex flex-1 overflow-hidden">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar w-[280px] shrink-0 flex flex-col overflow-hidden hidden lg:flex max-h-[calc(100vh-56px)]">
            <!-- New Chat -->
            <div class="p-4 border-b border-[#3f3f46]/50">
                <button id="newChatBtn" class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-[#10a37f]/10 border border-[#10a37f]/30 hover:border-[#10a37f]/60 hover:bg-[#10a37f]/15 transition text-sm font-medium text-white">
                    <i class="fas fa-plus text-sm"></i>
                    New Chat
                </button>
            </div>
            
            <!-- Recent Chats -->
            <div class="flex-1 overflow-hidden flex flex-col p-3 min-h-0">
                <p class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Recent</p>
                <div class="flex-1 overflow-hidden rounded-xl bg-[#1e1e24]/50 border border-[#3f3f46]/30">
                    <ul id="historyList" class="h-full overflow-y-auto scrollbar-thin space-y-1 p-2">
                        <li class="text-gray-500 text-sm p-4 text-center">No conversations yet</li>
                    </ul>
                </div>
            </div>
            
            <!-- AI Tools -->
            <div class="p-4 border-t border-[#3f3f46]/50">
                <p class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">AI Tools</p>
                <div class="grid grid-cols-3 gap-2">
                    <button data-tool="chat" onclick="selectTool('chat')" class="tool-card active p-3 rounded-xl border border-[#3f3f46] hover:border-[#10a37f] hover:bg-[#10a37f]/5 text-center transition">
                        <div class="w-11 h-11 mx-auto rounded-xl bg-gradient-to-br from-[#10a37f] to-[#158f6e] flex items-center justify-center mb-2 shadow-lg shadow-[#10a37f]/20">
                            <i class="fas fa-message text-white text-sm"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-300">Chat</span>
                    </button>
                    <button data-tool="image" onclick="selectTool('image')" class="tool-card p-3 rounded-xl border border-[#3f3f46] hover:border-[#ec4899] hover:bg-[#ec4899]/5 text-center transition">
                        <div class="w-11 h-11 mx-auto rounded-xl bg-gradient-to-br from-[#ec4899] to-[#f472b6] flex items-center justify-center mb-2 shadow-lg shadow-[#ec4899]/20">
                            <i class="fas fa-image text-white text-sm"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-300">Image</span>
                    </button>
                    <button data-tool="video" onclick="selectTool('video')" class="tool-card p-3 rounded-xl border border-[#3f3f46] hover:border-[#06b6d4] hover:bg-[#06b6d4]/5 text-center transition">
                        <div class="w-11 h-11 mx-auto rounded-xl bg-gradient-to-br from-[#06b6d4] to-[#22d3ee] flex items-center justify-center mb-2 shadow-lg shadow-[#06b6d4]/20">
                            <i class="fas fa-video text-white text-sm"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-300">Video</span>
                    </button>
                </div>
            </div>
            
            <!-- Provider -->
            <div class="p-4 border-t border-[#3f3f46]/50 space-y-3">
                <div>
                    <label class="text-xs font-medium text-gray-500 mb-2 block">Model</label>
                    <select id="providerSelect" class="w-full px-3 py-2.5 rounded-xl bg-[#1e1e24] border border-[#3f3f46] text-sm text-white focus:outline-none focus:border-[#10a37f] transition appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%2212%22 viewBox=%220 0 12 12%22><path fill=%22%23a3a3a4%22 d=%22M6 8L1 3h10z%22/></svg>'); background-repeat: no-repeat; background-position: right 12px center;">
                        <optgroup label="💬 Chat Models" class="bg-[#1a1a1e]">
                            <option value="pollinations" selected>Pollinations AI</option>
                            <option value="opencode">OpenCode Zen</option>
                            <option value="nvidia">NVIDIA NIM</option>
                            <option value="groq">Groq</option>
                            <option value="deepseek">DeepSeek</option>
                            <option value="openrouter">OpenRouter</option>
                            <option value="sambanova">SambaNova</option>
                        </optgroup>
                        <optgroup label="🖼️ Image Models" class="bg-[#1a1a1e]">
                            <option value="pollinations-image">Polli Image</option>
                            <option value="stability">Stability AI</option>
                        </optgroup>
                        <optgroup label="🎬 Video Models" class="bg-[#1a1a1e]">
                            <option value="pollinations-video">Polli Video</option>
                        </optgroup>
                    </select>
                </div>
                <div id="modelSelectionContainer" class="hidden">
                    <select id="modelSelect" class="w-full px-3 py-2.5 rounded-xl bg-[#1e1e24] border border-[#3f3f46] text-sm text-white focus:outline-none focus:border-[#10a37f] transition appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%2212%22 viewBox=%220 0 12 12%22><path fill=%22%23a3a3a4%22 d=%22M6 8L1 3h10z%22/></svg>'); background-repeat: no-repeat; background-position: right 12px center;">
                    </select>
                </div>
            </div>
            
            <!-- User -->
            <div class="p-4 border-t border-[#3f3f46]/50">
                <div class="flex items-center gap-3 p-3 rounded-xl bg-[#1e1e24]/50 hover:bg-[#252529] transition">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#10a37f] to-[#1a7f64] flex items-center justify-center text-sm font-bold text-white shadow-lg">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate"><?php echo htmlspecialchars($username); ?></p>
                        <p class="text-xs text-gray-500"><?php echo $userPlan; ?> Plan</p>
                    </div>
                    <div class="flex items-center gap-1">
                        <?php if ($is_admin): ?>
                        <a href="admin/index.php" class="p-2 rounded-lg hover:bg-[#2d2d33] text-gray-400 hover:text-white transition">
                            <i class="fas fa-cog text-sm"></i>
                        </a>
                        <?php endif; ?>
                        <a href="auth.php?logout=1" onclick="return confirm('Are you sure you want to logout?');" class="p-2 rounded-lg hover:bg-[#2d2d33] text-gray-400 hover:text-white transition cursor-pointer block">
                            <i class="fas fa-sign-out-alt text-sm"></i>
                        </a>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden bg-[#0d0d0f] h-[calc(100vh-56px)] relative">
            <!-- Chat Area -->
            <div id="chatContainer" class="flex-1 overflow-y-auto min-h-0">
                <!-- Welcome Screen -->
                <div id="welcomeScreen" class="max-w-3xl mx-auto px-6 py-16 welcome-glow">
                    <div class="text-center mb-12">
                        <div class="w-24 h-24 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-[#10a37f] to-[#158f6e] flex items-center justify-center shadow-2xl shadow-[#10a37f]/30">
                            <i class="fas fa-robot text-4xl text-white"></i>
                        </div>
                        <h2 class="text-4xl font-bold text-white mb-4">Welcome to Elyxar AI</h2>
                        <p class="text-gray-400 text-lg">Your AI assistant is ready. Select a tool or start chatting.</p>
                    </div>
                    
                    <!-- Tools -->
                    <div class="grid grid-cols-3 gap-4 mb-12">
                        <div onclick="selectTool('chat')" class="tool-card cursor-pointer p-6 rounded-2xl border border-[#3f3f46] hover:border-[#10a37f]/50 hover:bg-[#10a37f]/5 transition" data-tool-select="chat">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-[#10a37f] to-[#158f6e] flex items-center justify-center mb-4 shadow-lg shadow-[#10a37f]/20">
                                <i class="fas fa-message text-white text-2xl"></i>
                            </div>
                            <h3 class="text-base font-semibold text-center text-white">AI Chat</h3>
                            <p class="text-xs text-gray-500 text-center mt-1">Smart conversations</p>
                        </div>
                        <div onclick="selectTool('image')" class="tool-card cursor-pointer p-6 rounded-2xl border border-[#3f3f46] hover:border-[#ec4899]/50 hover:bg-[#ec4899]/5 transition" data-tool-select="image">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-[#ec4899] to-[#f472b6] flex items-center justify-center mb-4 shadow-lg shadow-[#ec4899]/20">
                                <i class="fas fa-image text-white text-2xl"></i>
                            </div>
                            <h3 class="text-base font-semibold text-center text-white">Generate Image</h3>
                            <p class="text-xs text-gray-500 text-center mt-1">Create visuals</p>
                        </div>
                        <div onclick="selectTool('video')" class="tool-card cursor-pointer p-6 rounded-2xl border border-[#3f3f46] hover:border-[#06b6d4]/50 hover:bg-[#06b6d4]/5 transition" data-tool-select="video">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-[#06b6d4] to-[#22d3ee] flex items-center justify-center mb-4 shadow-lg shadow-[#06b6d4]/20">
                                <i class="fas fa-video text-white text-2xl"></i>
                            </div>
                            <h3 class="text-base font-semibold text-center text-white">Create Video</h3>
                            <p class="text-xs text-gray-500 text-center mt-1">Generate videos</p>
                        </div>
                    </div>
                    
                    <!-- Examples -->
                    <div>
                        <p class="text-sm font-medium text-gray-400 mb-5 text-center">Try these examples</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <button class="example-btn p-4 rounded-xl bg-[#1e1e24] border border-[#3f3f46] hover:border-[#10a37f]/50 text-left transition" data-prompt="Write a Python function to calculate fibonacci numbers with explanation">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-[#10a37f]/20 flex items-center justify-center">
                                        <i class="fas fa-code text-[#10a37f]"></i>
                                    </div>
                                    <span class="text-sm text-gray-200">Write code</span>
                                </div>
                            </button>
                            <button class="example-btn p-4 rounded-xl bg-[#1e1e24] border border-[#3f3f46] hover:border-[#ec4899]/50 text-left transition" data-prompt="Generate a beautiful landscape image with mountains and sunset">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-[#ec4899]/20 flex items-center justify-center">
                                        <i class="fas fa-image text-[#ec4899]"></i>
                                    </div>
                                    <span class="text-sm text-gray-200">Create image</span>
                                </div>
                            </button>
                            <button class="example-btn p-4 rounded-xl bg-[#1e1e24] border border-[#3f3f46] hover:border-[#06b6d4]/50 text-left transition" data-prompt="Explain how machine learning and neural networks work in simple terms">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-[#06b6d4]/20 flex items-center justify-center">
                                        <i class="fas fa-brain text-[#06b6d4]"></i>
                                    </div>
                                    <span class="text-sm text-gray-200">Explain AI</span>
                                </div>
                            </button>
                            <button class="example-btn p-4 rounded-xl bg-[#1e1e24] border border-[#3f3f46] hover:border-[#8b5cf6]/50 text-left transition" data-prompt="Write a short story about a time traveler who meets their younger self">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-[#8b5cf6]/20 flex items-center justify-center">
                                        <i class="fas fa-book-open text-[#8b5cf6]"></i>
                                    </div>
                                    <span class="text-sm text-gray-200">Write story</span>
                                </div>
                            </button>
                            <button class="example-btn p-4 rounded-xl bg-[#1e1e24] border border-[#3f3f46] hover:border-[#fbbf24]/50 text-left transition" data-prompt="Solve this math problem: If a train travels 120km in 2 hours, what's its speed?">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-[#fbbf24]/20 flex items-center justify-center">
                                        <i class="fas fa-calculator text-[#fbbf24]"></i>
                                    </div>
                                    <span class="text-sm text-gray-200">Math help</span>
                                </div>
                            </button>
                            <button class="example-btn p-4 rounded-xl bg-[#1e1e24] border border-[#3f3f46] hover:border-[#3b82f6]/50 text-left transition" data-prompt="Translate this to French: Hello, how are you today?">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-[#3b82f6]/20 flex items-center justify-center">
                                        <i class="fas fa-language text-[#3b82f6]"></i>
                                    </div>
                                    <span class="text-sm text-gray-200">Translate</span>
                                </div>
                            </button>
                            <button class="example-btn p-4 rounded-xl bg-[#1e1e24] border border-[#3f3f46] hover:border-[#ef4444]/50 text-left transition" data-prompt="Summarize the key benefits of exercise and healthy eating">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-[#ef4444]/20 flex items-center justify-center">
                                        <i class="fas fa-list-alt text-[#ef4444]"></i>
                                    </div>
                                    <span class="text-sm text-gray-200">Summarize</span>
                                </div>
                            </button>
                            <button class="example-btn p-4 rounded-xl bg-[#1e1e24] border border-[#3f3f46] hover:border-[#f97316]/50 text-left transition" data-prompt="Write a beautiful poem about nature and seasons">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-[#f97316]/20 flex items-center justify-center">
                                        <i class="fas fa-feather-alt text-[#f97316]"></i>
                                    </div>
                                    <span class="text-sm text-gray-200">Write poem</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Messages Container -->
                <div id="messagesContainer" class="hidden max-w-3xl mx-auto px-6 py-6 space-y-6">
                </div>
            </div>
            
            <!-- Input Area -->
            <div class="p-4 bg-gradient-to-t from-[#0d0d0f] via-[#0d0d0f] to-transparent">
                <div class="max-w-3xl mx-auto">
                    <div id="imagePreviewContainer" class="hidden mb-3 p-3 rounded-xl bg-[#1e1e24] border border-[#3f3f46] flex items-center gap-3 w-fit">
                        <img id="imagePreview" src="" class="w-14 h-14 object-cover rounded-lg">
                        <button id="removeImageBtn" class="p-2 hover:bg-[#2d2d33] rounded-lg text-red-400 transition">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>
                    
                    <div class="input-wrapper relative rounded-2xl">
                        <input type="file" id="imageInput" accept="image/*" class="hidden">
                        <label for="imageInput" class="absolute left-4 top-1/2 -translate-y-1/2 p-2 cursor-pointer text-gray-500 hover:text-[#10a37f] transition">
                            <i class="fas fa-paperclip text-lg"></i>
                        </label>
                        <textarea id="userInput" rows="1" placeholder="Send a message..." class="w-full bg-transparent border-none text-base text-white py-4 pl-14 pr-24 resize-none focus:outline-none max-h-48" style="field-sizing: content;"></textarea>
                        
                        <!-- Send Button -->
                        <button id="sendBtn" class="btn-send absolute right-4 top-1/2 -translate-y-1/2 p-2.5 rounded-xl text-white disabled:opacity-40 disabled:cursor-not-allowed transition shadow-lg" disabled>
                            <i class="fas fa-paper-plane text-sm"></i>
                        </button>
                        
                        <!-- Stop Button (replaces send button while generating) -->
                        <button id="stopBtn" class="hidden absolute right-4 top-1/2 -translate-y-1/2 p-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white transition shadow-lg">
                            <i class="fas fa-stop text-sm"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 text-center mt-3">AI can make mistakes. Verify important information.</p>
                </div>
            </div>
</main>
    </div>

    <!-- Mobile Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-40 lg:hidden hidden" onclick="closeSidebar()"></div>

    <!-- Typing Indicator Template -->
    <template id="typingTemplate">
        <div class="message-enter">
            <div class="flex gap-4">
                <div class="w-9 h-9 rounded-full bg-gradient-to-r from-[#8b5cf6] to-[#3b82f6] flex items-center justify-center flex-shrink-0 shadow-lg">
                    <i class="fas fa-robot text-white text-sm"></i>
                </div>
                <div class="bg-[#1e1e24] rounded-2xl px-4 py-3 max-w-[80%] border border-[#3f3f46]">
                    <div class="typing-dots">
                        <span class="typing-text text-gray-400 text-sm mr-1"></span>
                        <span class="dot text-[#10a37f]">.</span>
                        <span class="dot text-[#10a37f]">.</span>
                        <span class="dot text-[#10a37f]">.</span>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <link rel="stylesheet" href="assets/css/style.css">
    <script src="js/app.js"></script>
    <script>
        function selectTool(tool) {
            document.querySelectorAll('[data-tool]').forEach(el => el.classList.toggle('active', el.dataset.tool === tool));
            document.querySelectorAll('[data-tool-select]').forEach(el => el.classList.toggle('active', el.dataset.toolSelect === tool));
        }
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.remove('hidden');
            sidebar.classList.add('fixed', 'inset-0', 'z-50', 'bg-[#17171a]', 'open');
            overlay.classList.remove('hidden');
            overlay.classList.add('active');
        }
        
        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.add('hidden');
            sidebar.classList.remove('fixed', 'inset-0', 'z-50', 'bg-[#17171a]', 'open');
            overlay.classList.add('hidden');
            overlay.classList.remove('active');
        }
        
        function openSidebar() {
            toggleSidebar();
        }
    </script>
</body>
</html>