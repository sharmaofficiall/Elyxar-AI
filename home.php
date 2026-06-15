<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elyxar AI : Cognitive Intelligence Engine</title>
    
<?php
// Check if user is already logged in - redirect to chat
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        nexus: { 50: '#f0fdf4', 100: '#dcfce7', 500: '#10a37f', 600: '#158f6e' },
                        dark: { 850: '#0d0d0f', 900: '#17171a', 950: '#0a0a0a' }
                    }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0d0d0f; }
        
        .gradient-text {
            background: linear-gradient(135deg, #10a37f, #158f6e, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #10a37f, #158f6e);
        }
        .glass {
            background: rgba(30, 30, 36, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(16, 163, 127, 0.25);
        }
        .float-animation {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 25px rgba(16, 163, 127, 0.5); }
            50% { box-shadow: 0 0 50px rgba(16, 163, 127, 0.8); }
        }
        .scroll-gradient {
            background: linear-gradient(180deg, transparent, #0d0d0f, transparent);
        }
        .logo-pulse {
            animation: logoPulse 3s ease-in-out infinite;
        }
        @keyframes logoPulse {
            0%, 100% { box-shadow: 0 0 20px rgba(16, 163, 127, 0.3); }
            50% { box-shadow: 0 0 40px rgba(16, 163, 127, 0.5); }
        }
    </style>
</head>
<body class="bg-darker text-white overflow-x-hidden">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center">
                        <i class="fas fa-brain text-white text-lg"></i>
                    </div>
                    <span class="text-xl font-bold">Elyxar AI</span>
                </div>
                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="text-gray-300 hover:text-white transition">Features</a>
                    <a href="#models" class="text-gray-300 hover:text-white transition">AI Models</a>
                    <a href="#pricing" class="text-gray-300 hover:text-white transition">Pricing</a>
                    <a href="login.php" class="flex items-center gap-2 text-gray-300 hover:text-white transition px-4 py-2 rounded-lg hover:bg-white/10">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="register.php" class="flex items-center gap-2 bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-2.5 rounded-lg font-semibold hover:from-purple-500 hover:to-pink-500 transition transform hover:scale-105 shadow-lg shadow-purple-500/30">
                        <i class="fas fa-rocket"></i> Get Started
                    </a>
                </div>
                <button class="md:hidden text-white" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden glass border-t border-gray-700">
            <div class="px-4 py-4 space-y-3">
                <a href="#features" class="block text-gray-300 hover:text-white">Features</a>
                <a href="#models" class="block text-gray-300 hover:text-white">AI Models</a>
                <a href="#pricing" class="block text-gray-300 hover:text-white">Pricing</a>
                <a href="login.php" class="flex items-center gap-2 text-gray-300 hover:text-white py-2">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="register.php" class="flex items-center justify-center gap-2 bg-gradient-to-r from-purple-600 to-pink-600 px-5 py-3 rounded-lg font-semibold text-center">
                    <i class="fas fa-rocket"></i> Get Started
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="min-h-screen flex items-center justify-center relative overflow-hidden pt-16">
        <!-- Animated Background -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-primary/20 rounded-full blur-3xl float-animation"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-secondary/20 rounded-full blur-3xl float-animation" style="animation-delay: -3s;"></div>
            <div class="absolute top-1/2 left-1/2 w-64 h-64 bg-accent/20 rounded-full blur-3xl float-animation" style="animation-delay: -1.5s;"></div>
        </div>
        
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 glass px-4 py-2 rounded-full mb-8">
                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                <span class="text-sm text-gray-300">Powered by 50+ AI Models</span>
            </div>
            
            <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">
                The Future of AI is<br>
                <span class="gradient-text">All in One Place</span>
            </h1>
            
            <p class="text-xl text-gray-400 mb-10 max-w-2xl mx-auto">
                Generate stunning images, create videos, chat with advanced AI models, 
                and access the most powerful AI tools - all in one unified platform.
            </p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
                <a href="register.php" class="w-full sm:w-auto gradient-bg px-8 py-4 rounded-xl font-semibold text-lg hover:opacity-90 transition pulse-glow">
                    Start Free Trial
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
                <a href="#features" class="w-full sm:w-auto glass px-8 py-4 rounded-xl font-semibold text-lg hover:bg-white/10 transition">
                    <i class="fas fa-play mr-2"></i>
                    See Features
                </a>
            </div>
            
            <!-- Demo Preview -->
            <div class="relative max-w-4xl mx-auto">
                <div class="glass rounded-2xl overflow-hidden shadow-2xl border border-gray-700">
                    <div class="bg-gray-900 px-4 py-3 flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="ml-4 text-gray-400 text-sm">Elyxar AI Demo</span>
                    </div>
                    <div class="p-6 bg-gray-800/50">
                        <div class="flex gap-4 mb-4">
                            <div class="w-10 h-10 rounded-full gradient-bg flex items-center justify-center">
                                <i class="fas fa-robot text-white"></i>
                            </div>
                            <div class="bg-gray-700 rounded-2xl px-6 py-4 max-w-md">
                                <p class="text-gray-300">Hello! I'm your AI assistant. I can help you with images, videos, and intelligent conversations. What would you like to create today?</p>
                            </div>
                        </div>
                        <div class="flex gap-4 mb-4 flex-row-reverse">
                            <div class="w-10 h-10 rounded-full bg-purple-500 flex items-center justify-center font-bold">U</div>
                            <div class="bg-primary/30 rounded-2xl px-6 py-4 max-w-md">
                                <p class="text-white">Create a futuristic city with flying cars and neon lights</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full gradient-bg flex items-center justify-center">
                                <i class="fas fa-robot text-white"></i>
                            </div>
                            <div class="bg-gray-700 rounded-2xl px-6 py-4">
                                <img src="https://image.pollinations.ai/prompt/futuristic%20city%20with%20flying%20cars%20neon%20lights%20cyberpunk" 
                                     alt="Generated" class="rounded-lg max-w-sm w-full">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 relative scroll-gradient">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4">Powerful AI Features</h2>
                <p class="text-gray-400 text-lg max-w-2xl mx-auto">
                    Everything you need to create, generate, and innovate with artificial intelligence
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="glass rounded-2xl p-8 card-hover">
                    <div class="w-14 h-14 gradient-bg rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-comments text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Smart AI Chat</h3>
                    <p class="text-gray-400">
                        Chat with advanced AI models including GPT, Claude, Gemini, DeepSeek, and many more. Get intelligent responses instantly.
                    </p>
                </div>
                
                <!-- Feature 2 -->
                <div class="glass rounded-2xl p-8 card-hover">
                    <div class="w-14 h-14 gradient-bg rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-image text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Image Generation</h3>
                    <p class="text-gray-400">
                        Create stunning visuals from text descriptions. Choose from Flux, Stable Diffusion, Midjourney, and more.
                    </p>
                </div>
                
                <!-- Feature 3 -->
                <div class="glass rounded-2xl p-8 card-hover">
                    <div class="w-14 h-14 gradient-bg rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-video text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Video Generation</h3>
                    <p class="text-gray-400">
                        Transform your ideas into videos using Luma, Veo, and Seedance AI. Create cinematic content effortlessly.
                    </p>
                </div>
                
                <!-- Feature 4 -->
                <div class="glass rounded-2xl p-8 card-hover">
                    <div class="w-14 h-14 gradient-bg rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-brain text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">50+ AI Models</h3>
                    <p class="text-gray-400">
                        Access over 50 cutting-edge AI models. From chat to image to video generation, we've got you covered.
                    </p>
                </div>
                
                <!-- Feature 5 -->
                <div class="glass rounded-2xl p-8 card-hover">
                    <div class="w-14 h-14 gradient-bg rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-bolt text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Lightning Fast</h3>
                    <p class="text-gray-400">
                        Optimized for speed with parallel processing and smart caching. Get results in seconds, not minutes.
                    </p>
                </div>
                
                <!-- Feature 6 -->
                <div class="glass rounded-2xl p-8 card-hover">
                    <div class="w-14 h-14 gradient-bg rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-shield-alt text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Secure & Private</h3>
                    <p class="text-gray-400">
                        Your data is encrypted and protected. We never share your conversations or generated content.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- AI Models Section -->
    <section id="models" class="py-24 bg-dark">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4">Supported AI Models</h2>
                <p class="text-gray-400 text-lg max-w-2xl mx-auto">
                    We integrate with the world's leading AI providers to give you the best experience
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Provider Cards -->
                <div class="glass rounded-xl p-6 text-center card-hover">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-green-600 rounded-xl mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-bolt text-2xl text-white"></i>
                    </div>
                    <h3 class="font-semibold mb-2">OpenAI</h3>
                    <p class="text-sm text-gray-400">GPT-4, GPT-4o</p>
                </div>
                
                <div class="glass rounded-xl p-6 text-center card-hover">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-gem text-2xl text-white"></i>
                    </div>
                    <h3 class="font-semibold mb-2">Google Gemini</h3>
                    <p class="text-sm text-gray-400">Gemini Pro, Ultra</p>
                </div>
                
                <div class="glass rounded-xl p-6 text-center card-hover">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-dragon text-2xl text-white"></i>
                    </div>
                    <h3 class="font-semibold mb-2">DeepSeek</h3>
                    <p class="text-sm text-gray-400">Chat, Reasoner</p>
                </div>
                
                <div class="glass rounded-xl p-6 text-center card-hover">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-fire text-2xl text-white"></i>
                    </div>
                    <h3 class="font-semibold mb-2">Groq</h3>
                    <p class="text-sm text-gray-400">Llama, Mixtral</p>
                </div>
                
                <div class="glass rounded-xl p-6 text-center card-hover">
                    <div class="w-16 h-16 bg-gradient-to-br from-pink-400 to-pink-600 rounded-xl mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-poll text-2xl text-white"></i>
                    </div>
                    <h3 class="font-semibold mb-2">Pollinations AI</h3>
                    <p class="text-sm text-gray-400">Image & Video</p>
                </div>
                
                <div class="glass rounded-xl p-6 text-center card-hover">
                    <div class="w-16 h-16 bg-gradient-to-br from-red-400 to-red-600 rounded-xl mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-stability text-2xl text-white"></i>
                    </div>
                    <h3 class="font-semibold mb-2">Stability AI</h3>
                    <p class="text-sm text-gray-400">Stable Diffusion</p>
                </div>
                
                <div class="glass rounded-xl p-6 text-center card-hover">
                    <div class="w-16 h-16 bg-gradient-to-br from-cyan-400 to-cyan-600 rounded-xl mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-network-wired text-2xl text-white"></i>
                    </div>
                    <h3 class="font-semibold mb-2">OpenRouter</h3>
                    <p class="text-sm text-gray-400">100+ Models</p>
                </div>

                <div class="glass rounded-xl p-6 text-center card-hover">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-green-600 rounded-xl mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-brain text-2xl text-white"></i>
                    </div>
                    <h3 class="font-semibold mb-2">OpenCode</h3>
                    <p class="text-sm text-gray-400">AI Coding Assistant</p>
                </div>
                
                <div class="glass rounded-xl p-6 text-center card-hover">
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-xl mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-server text-2xl text-white"></i>
                    </div>
                    <h3 class="font-semibold mb-2">SambaNova</h3>
                    <p class="text-sm text-gray-400">Llama 3.3 70B</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-24 relative scroll-gradient">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4">Simple, Transparent Pricing</h2>
                <p class="text-gray-400 text-lg max-w-2xl mx-auto">
                    Choose the plan that fits your needs. Upgrade or downgrade anytime.
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Free Plan -->
                <div class="glass rounded-2xl p-8 card-hover">
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold mb-2">Free</h3>
                        <div class="text-4xl font-bold">$0</div>
                        <p class="text-gray-400">forever</p>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3">
                            <i class="fas fa-check text-green-400"></i>
                            <span>5000 credits/month</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-check text-green-400"></i>
                            <span>Basic AI models</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-check text-green-400"></i>
                            <span>Image generation (5/day)</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-check text-green-400"></i>
                            <span>Chat history</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-times text-gray-500"></i>
                            <span class="text-gray-500">Video generation</span>
                        </li>
                    </ul>
                    <a href="register.php" class="block w-full glass text-center py-3 rounded-lg font-semibold hover:bg-white/10 transition">
                        Sign Up Free
                    </a>
                </div>
                
                <!-- Pro Plan -->
                <div class="glass rounded-2xl p-8 card-hover border-2 border-primary relative">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 gradient-bg px-4 py-1 rounded-full text-sm font-medium">
                        Most Popular
                    </div>
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold mb-2">Pro</h3>
                        <div class="text-4xl font-bold">$9.99</div>
                        <p class="text-gray-400">per month</p>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3">
                            <i class="fas fa-check text-green-400"></i>
                            <span>1,000,000 credits/month</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-check text-green-400"></i>
                            <span>All AI models</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-check text-green-400"></i>
                            <span>Unlimited image generation</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-check text-green-400"></i>
                            <span>Video generation</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-check text-green-400"></i>
                            <span>Priority support</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-check text-green-400"></i>
                            <span>Longer conversations</span>
                        </li>
                    </ul>
                    <a href="register.php?plan=pro" class="block w-full gradient-bg text-center py-3 rounded-lg font-semibold hover:opacity-90 transition">
                        Get Pro
                    </a>
                </div>
                
                <!-- Enterprise Plan -->
                <div class="glass rounded-2xl p-8 card-hover">
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold mb-2">Enterprise</h3>
                        <div class="text-4xl font-bold">$49.99</div>
                        <p class="text-gray-400">per month</p>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3">
                            <i class="fas fa-check text-green-400"></i>
                            <span>Unlimited credits/month</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-check text-green-400"></i>
                            <span>All AI models + Premium</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-check text-green-400"></i>
                            <span>Unlimited everything</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-check text-green-400"></i>
                            <span>API access</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-check text-green-400"></i>
                            <span>24/7 Priority support</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-check text-green-400"></i>
                            <span>Custom integrations</span>
                        </li>
                    </ul>
                    <a href="register.php?plan=enterprise" class="block w-full glass text-center py-3 rounded-lg font-semibold hover:bg-white/10 transition">
                        Contact Sales
                    </a>
                </div>
            </div>
            
            <!-- Payment Methods -->
            <div class="text-center mt-12">
                <p class="text-gray-400 mb-4">Accepted Payment Methods</p>
                <div class="flex items-center justify-center gap-6">
                    <div class="w-12 h-8 bg-white rounded flex items-center justify-center">
                        <i class="fab fa-cc-visa text-xl text-blue-600"></i>
                    </div>
                    <div class="w-12 h-8 bg-white rounded flex items-center justify-center">
                        <i class="fab fa-cc-mastercard text-xl text-red-600"></i>
                    </div>
                    <div class="w-12 h-8 bg-white rounded flex items-center justify-center">
                        <i class="fab fa-cc-amex text-xl text-blue-500"></i>
                    </div>
                    <div class="w-12 h-8 bg-white rounded flex items-center justify-center">
                        <i class="fab fa-paypal text-xl text-blue-700"></i>
                    </div>
                    <div class="w-12 h-8 bg-white rounded flex items-center justify-center">
                        <i class="fab fa-bitcoin text-xl text-orange-500"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-24 bg-dark">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h2 class="text-4xl font-bold mb-6">Ready to Start Creating?</h2>
            <p class="text-xl text-gray-400 mb-10">
                Join thousands of users who are already creating amazing content with Elyxar AI.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="register.php" class="w-full sm:w-auto gradient-bg px-8 py-4 rounded-xl font-semibold text-lg hover:opacity-90 transition">
                    Get Started Free
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
                <a href="index.php" class="w-full sm:w-auto glass px-8 py-4 rounded-xl font-semibold text-lg hover:bg-white/10 transition">
                    Try Demo
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-darker border-t border-gray-800 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center">
                            <i class="fas fa-brain text-white text-lg"></i>
                        </div>
                        <span class="text-xl font-bold">Elyxar AI</span>
                    </div>
                    <p class="text-gray-400 text-sm">
                        The all-in-one AI platform for chat, image generation, and video creation.
                    </p>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4">Product</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="#features" class="hover:text-white transition">Features</a></li>
                        <li><a href="#pricing" class="hover:text-white transition">Pricing</a></li>
                        <li><a href="#models" class="hover:text-white transition">AI Models</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4">Company</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="#" class="hover:text-white transition">About</a></li>
                        <li><a href="#" class="hover:text-white transition">Blog</a></li>
                        <li><a href="#" class="hover:text-white transition">Careers</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4">Support</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="#" class="hover:text-white transition">Help Center</a></li>
                        <li><a href="#" class="hover:text-white transition">Contact</a></li>
                        <li><a href="#" class="hover:text-white transition">Privacy</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-gray-400 text-sm">
                    © 2024 Elyxar AI. All rights reserved.
                </p>
                <div class="flex items-center gap-4">
                    <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-twitter text-xl"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-github text-xl"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-discord text-xl"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar background on scroll
        window.addEventListener('scroll', () => {
            const nav = document.querySelector('nav');
            if (window.scrollY > 50) {
                nav.classList.add('bg-darker/90');
            } else {
                nav.classList.remove('bg-darker/90');
            }
        });
    </script>
</body>
</html>
