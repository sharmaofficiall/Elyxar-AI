<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Elyxar AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        nexus: { 50: '#f0fdf4', 100: '#dcfce7', 500: '#10a37f', 600: '#158f6e' }
                    }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body {
            background: linear-gradient(135deg, #0d0d0f 0%, #17171a 50%, #0d0d0f 100%);
            min-height: 100vh;
        }
        
        /* Animated background */
        .bg-animate {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }
        .bg-animate::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 30%, rgba(16, 163, 127, 0.08) 0%, transparent 50%),
                        radial-gradient(circle at 70% 70%, rgba(21, 143, 110, 0.06) 0%, transparent 50%);
            animation: bgFloat 20s ease-in-out infinite;
        }
        @keyframes bgFloat {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(2%, 2%) rotate(2deg); }
            66% { transform: translate(-1%, 1%) rotate(-1deg); }
        }
        
        .glass {
            background: rgba(30, 30, 36, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .input-field {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            border-color: #10a37f;
            box-shadow: 0 0 0 3px rgba(16, 163, 127, 0.15), 0 4px 20px rgba(0,0,0,0.3);
            background: rgba(255, 255, 255, 0.06);
        }
        
        .input-field::placeholder {
            color: rgba(255, 255, 255, 0.35);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #10a37f 0%, #158f6e 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(16, 163, 127, 0.4);
        }
        
        .logo-glow {
            animation: logoGlow 3s ease-in-out infinite;
        }
        @keyframes logoGlow {
            0%, 100% { box-shadow: 0 0 30px rgba(16, 163, 127, 0.4); }
            50% { box-shadow: 0 0 50px rgba(16, 163, 127, 0.6); }
        }
        
        .float-particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(16, 163, 127, 0.3);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); opacity: 0.3; }
            50% { transform: translateY(-100px) scale(1.5); opacity: 0.6; }
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 relative">
    <!-- Animated Background -->
    <div class="bg-animate">
        <div class="float-particle" style="top: 20%; left: 10%; animation-delay: 0s;"></div>
        <div class="float-particle" style="top: 60%; left: 80%; animation-delay: 2s;"></div>
        <div class="float-particle" style="top: 40%; left: 30%; animation-delay: 4s;"></div>
    </div>

    <div class="relative w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="w-18 h-18 mx-auto mb-5 rounded-2xl bg-gradient-to-br from-[#10a37f] to-[#158f6e] flex items-center justify-center logo-glow" style="width: 72px; height: 72px;">
                <i class="fas fa-brain text-3xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-white">Elyxar AI</h1>
            <p class="text-gray-500 mt-2">Sign in to continue</p>
        </div>

        <!-- Login Card -->
        <div class="glass rounded-2xl p-8 shadow-2xl" style="border-radius: 20px;">
            <h2 class="text-2xl font-semibold text-white mb-6 text-center">Welcome Back</h2>
            
            <!-- Error Message -->
            <div id="errorMsg" class="hidden mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-sm"></div>

            <form id="loginForm" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-2">Email</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                        <input type="email" id="email" 
                            class="input-field w-full pl-12 pr-4 py-3.5 rounded-xl text-white" 
                            placeholder="you@example.com" required>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-2">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                        <input type="password" id="password" 
                            class="input-field w-full pl-12 pr-4 py-3.5 rounded-xl text-white" 
                            placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" 
                    class="w-full btn-primary text-white font-semibold py-3.5 px-6 rounded-xl flex items-center justify-center gap-2 shadow-lg">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-[#3f3f46]"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-[#1e1e24] text-gray-500">or continue with</span>
                    </div>
                </div>

                <!-- Google Login -->
                <a href="google_callback.php"
                    class="w-full flex items-center justify-center gap-3 bg-white text-gray-800 font-medium py-3.5 px-6 rounded-xl hover:bg-gray-100 transition shadow-lg">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Google
                </a>
            </form>

            <!-- Register Link -->
            <p class="text-center text-gray-500 mt-6">
                Don't have an account? 
                <a href="register.php" class="text-[#10a37f] hover:text-[#12b390] font-medium transition">Sign up</a>
            </p>
        </div>

        <!-- Back to Home -->
        <p class="text-center mt-6">
            <a href="home.php" class="inline-flex items-center gap-2 text-gray-500 hover:text-white transition">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </p>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorMsg = document.getElementById('errorMsg');
            const submitBtn = e.target.querySelector('button[type="submit"]');

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';

            try {
                const response = await fetch('auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'login', email, password })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = 'index.php';
                } else {
                    errorMsg.textContent = data.error;
                    errorMsg.classList.remove('hidden');
                }
            } catch (error) {
                errorMsg.textContent = 'An error occurred. Please try again.';
                errorMsg.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
            }
        });
    </script>
</body>
</html>