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
    <title>Sign Up - Elyxar AI</title>
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
            <p class="text-gray-500 mt-2">Create your account</p>
        </div>

        <!-- Register Card -->
        <div class="glass rounded-2xl p-8 shadow-2xl" style="border-radius: 20px;">
            <h2 class="text-2xl font-semibold text-white mb-6 text-center">Create Account</h2>
            
            <!-- Error Message -->
            <div id="errorMsg" class="hidden mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-sm"></div>

            <form id="registerForm" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-2">Username</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                        <input type="text" id="username" 
                            class="input-field w-full pl-12 pr-4 py-3.5 rounded-xl text-white" 
                            placeholder="johndoe" required>
                    </div>
                </div>
                
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
                            placeholder="••••••••" required minlength="6">
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Minimum 6 characters</p>
                </div>

                <button type="submit" 
                    class="w-full btn-primary text-white font-semibold py-3.5 px-6 rounded-xl flex items-center justify-center gap-2 shadow-lg">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </button>
            </form>

            <!-- Login Link -->
            <p class="text-center text-gray-500 mt-6">
                Already have an account? 
                <a href="login.php" class="text-[#10a37f] hover:text-[#12b390] font-medium transition">Sign in</a>
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
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorMsg = document.getElementById('errorMsg');
            const submitBtn = e.target.querySelector('button[type="submit"]');

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating account...';

            try {
                const response = await fetch('auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'register', username, email, password })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Account created successfully! Please log in.');
                    window.location.href = 'login.php';
                } else {
                    errorMsg.textContent = data.error || 'Registration failed';
                    errorMsg.classList.remove('hidden');
                }
            } catch (error) {
                errorMsg.textContent = 'An error occurred. Please try again.';
                errorMsg.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
            }
        });
    </script>
</body>
</html>