<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$plans_result = $conn->query("SELECT * FROM plans ORDER BY price ASC");
$plans = [];
while ($plan = $plans_result->fetch_assoc()) {
    $plans[] = $plan;
}

$payments_result = $conn->query("SELECT p.*, pl.name as plan_name FROM payments p 
    LEFT JOIN plans pl ON p.plan_id = pl.id 
    WHERE p.user_id = $user_id 
    ORDER BY p.created_at DESC LIMIT 10");
$payments = [];
while ($payment = $payments_result->fetch_assoc()) {
    $payments[] = $payment;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_checkout_session') {
        $plan_id = $_POST['plan_id'] ?? 0;
        
        $stmt = $conn->prepare("SELECT * FROM plans WHERE id = ?");
        $stmt->bind_param("i", $plan_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $plan = $result->fetch_assoc();
        
        if ($plan && $plan['price'] > 0) {
            $stripe_price_id = 'price_' . uniqid();
            
            $stmt = $conn->prepare("INSERT INTO payments (user_id, plan_id, amount, credits_added, payment_status, transaction_id, payment_method) 
                VALUES (?, ?, ?, ?, 'pending', ?, 'stripe')");
            $stmt->bind_param("iidds", $user_id, $plan_id, $plan['price'], $plan['credits'], $stripe_price_id);
            $stmt->execute();
            
            $payment_id = $stmt->insert_id;
            
            $stmt = $conn->prepare("UPDATE payments SET payment_status = 'completed' WHERE id = ?");
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();
            
            $new_credits = $user['credits'] + $plan['credits'];
            $stmt = $conn->prepare("UPDATE users SET credits = ?, plan = ?, plan_expires = DATE_ADD(NOW(), INTERVAL ? DAY) WHERE id = ?");
            $stmt->bind_param("isii", $new_credits, $plan['name'], $plan['duration_days'], $user_id);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Payment successful! Credits added.']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid plan']);
            exit;
        }
    }
    
    if ($action === 'add_credits') {
        $credits = intval($_POST['credits'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        
        if ($credits > 0 && $price > 0) {
            $transaction_id = 'credit_' . uniqid();
            $stmt = $conn->prepare("INSERT INTO payments (user_id, plan_id, amount, credits_added, payment_status, transaction_id, payment_method) 
                VALUES (?, 0, ?, ?, 'completed', ?, 'stripe')");
            $stmt->bind_param("idis", $user_id, $price, $credits, $transaction_id);
            $stmt->execute();
            
            $new_credits = $user['credits'] + $credits;
            $stmt = $conn->prepare("UPDATE users SET credits = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_credits, $user_id);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Credits added successfully!', 'new_credits' => $new_credits]);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription & Credits - Elyxar AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        nexus: { 500: '#10a37f', 600: '#158f6e' }
                    }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        
        body {
            background: 
                radial-gradient(ellipse at 20% 20%, rgba(16, 163, 127, 0.12) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(21, 143, 110, 0.1) 0%, transparent 50%),
                linear-gradient(180deg, #0d0d0f 0%, #17171a 50%, #0d0d0f 100%);
            min-height: 100vh;
            background-attachment: fixed;
        }
        
        .text-gradient { 
            background: linear-gradient(135deg, #10a37f 0%, #158f6e 50%, #06b6d4 100%); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
        }
        
        .bg-gradient-nexus { 
            background: linear-gradient(135deg, #10a37f 0%, #158f6e 100%); 
        }
        
        .bg-gradient-cyan { 
            background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%); 
        }
        
        .bg-gradient-warm { 
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); 
        }
        
        .glass { 
            background: rgba(30, 30, 36, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .glass-card { 
            background: linear-gradient(135deg, rgba(30,30,36,0.8) 0%, rgba(20,20,24,0.8) 100%);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .card-hover { 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
        }
        
        .card-hover:hover { 
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 30px 60px -20px rgba(16, 163, 127, 0.3);
            border-color: rgba(16, 163, 127, 0.4);
        }
        
        .glow-nexus { 
            box-shadow: 0 0 40px rgba(16, 163, 127, 0.4), 0 0 80px rgba(16, 163, 127, 0.2);
        }
        
        .glow-cyan { 
            box-shadow: 0 0 40px rgba(34, 211, 238, 0.4), 0 0 80px rgba(34, 211, 238, 0.2);
        }
        
        .glow-warm { 
            box-shadow: 0 0 40px rgba(251, 191, 36, 0.4), 0 0 80px rgba(251, 191, 36, 0.2);
        }
        
        .float { animation: float 3s ease-in-out infinite; }
        
        @keyframes float { 
            0%, 100% { transform: translateY(0px); } 
            50% { transform: translateY(-15px); } 
        }
        
        .border-glow {
            border: 2px solid transparent;
            background: linear-gradient(#17171a, #17171a) padding-box,
                        linear-gradient(135deg, #10a37f, #158f6e, #06b6d4) border-box;
        }
        
        .bg-mesh {
            background-image: 
                radial-gradient(at 40% 20%, rgba(16, 163, 127, 0.12) 0px, transparent 50%),
                radial-gradient(at 80% 0%, rgba(34, 211, 238, 0.08) 0px, transparent 50%),
                radial-gradient(at 0% 50%, rgba(21, 143, 110, 0.08) 0px, transparent 50%),
                radial-gradient(at 80% 50%, rgba(251, 191, 36, 0.06) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(16, 163, 127, 0.08) 0px, transparent 50%);
        }
        
        .btn-glow {
            position: relative;
            overflow: hidden;
        }
        
        .btn-glow::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-glow:hover::before {
            left: 100%;
        }
        
        .logo-glow {
            animation: logoGlow 3s ease-in-out infinite;
        }
        @keyframes logoGlow {
            0%, 100% { box-shadow: 0 0 20px rgba(16, 163, 127, 0.4); }
            50% { box-shadow: 0 0 40px rgba(16, 163, 127, 0.6); }
        }
    </style>
</head>
<body class="text-white min-h-screen">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-2">
                    <a href="home.php" class="flex items-center gap-2 group">
                        <div class="w-10 h-10 bg-gradient-to-br from-[#10a37f] to-[#158f6e] rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform logo-glow">
                            <i class="fas fa-brain text-white text-lg"></i>
                        </div>
                        <span class="text-xl font-bold">Elyxar AI</span>
                    </a>
                </div>
                <div class="flex items-center gap-6">
                    <a href="index.php" class="text-gray-400 hover:text-[#10a37f] transition flex items-center gap-2">
                        <i class="fas fa-comments"></i> 
                        <span class="hidden sm:inline">Chat</span>
                    </a>
                    <div class="flex items-center gap-2 glass rounded-full px-4 py-2 border border-amber-400/30">
                        <i class="fas fa-coins text-amber-400"></i>
                        <span class="font-bold text-amber-400"><?php echo number_format($user['credits']); ?></span>
                    </div>
                    <div class="relative">
                        <button onclick="document.getElementById('userMenu').classList.toggle('hidden')" 
                            class="flex items-center gap-2 text-gray-300 hover:text-white transition">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#10a37f] to-[#158f6e] flex items-center justify-center ring-2 ring-[#10a37f]/50">
                                <span class="text-sm font-bold"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></span>
                            </div>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div id="userMenu" class="hidden absolute right-0 mt-3 w-60 glass-card rounded-2xl shadow-2xl border border-white/10 overflow-hidden">
                            <div class="p-4 bg-gradient-to-r from-[#10a37f]/20 to-[#158f6e]/20 border-b border-white/10">
                                <p class="font-bold"><?php echo htmlspecialchars($user['username']); ?></p>
                                <p class="text-xs text-gray-400"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <div class="py-2">
                                <a href="index.php" class="block px-4 py-3 hover:bg-white/5 transition flex items-center gap-3 text-gray-400 hover:text-[#10a37f]">
                                    <i class="fas fa-comments"></i>Chat
                                </a>
                                <a href="payment.php" class="block px-4 py-3 hover:bg-white/5 text-[#10a37f] flex items-center gap-3 bg-[#10a37f]/10">
                                    <i class="fas fa-credit-card"></i>Billing
                                </a>
                                <hr class="my-2 border-white/10">
                                <a href="login.php?logout=1" class="block px-4 py-3 hover:bg-white/5 text-red-400 transition flex items-center gap-3">
                                    <i class="fas fa-sign-out-alt"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="pt-24 pb-12 bg-mesh min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 glass px-4 py-2 rounded-full mb-6 border border-fuchsia-500/20">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                    <span class="text-sm text-[#10a37f]">Unlock Premium AI Features</span>
                </div>
                <h1 class="text-5xl md:text-6xl font-bold mb-6">
                    <span class="text-gradient">Upgrade Your Plan</span>
                </h1>
                <p class="text-xl text-gray-400 max-w-2xl mx-auto leading-relaxed">
                    Get unlimited access to all AI models, generate images & videos, and supercharge your productivity
                </p>
            </div>

            <!-- Current Plan Status -->
            <div class="glass-card rounded-3xl p-8 mb-16 max-w-4xl mx-auto border-glow">
                <div class="flex flex-col md:flex-row items-center justify-between gap-8">
                    <div class="flex items-center gap-6">
                        <div class="w-24 h-24 bg-gradient-to-br from-[#10a37f] to-[#158f6e] rounded-3xl flex items-center justify-center float glow-nexus shadow-lg">
                            <i class="fas fa-crown text-4xl text-white"></i>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm uppercase tracking-widest mb-1">Current Plan</p>
                            <h2 class="text-4xl font-bold text-gradient"><?php echo htmlspecialchars($user['plan']); ?></h2>
                            <?php if ($user['plan_expires']): ?>
                                <p class="text-sm text-gray-400 mt-2 flex items-center gap-2">
                                    <i class="fas fa-calendar-check text-fuchsia-400"></i>
                                    Renews on <?php echo date('F d, Y', strtotime($user['plan_expires'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-8">
                        <div class="text-center">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="fas fa-coins text-3xl text-amber-400"></i>
                                <span class="text-5xl font-bold text-amber-400"><?php echo number_format($user['credits']); ?></span>
                            </div>
                            <p class="text-gray-400 text-sm">Available Credits</p>
                        </div>
                        <a href="#buy-credits" class="bg-gradient-fuchsia px-6 py-3 rounded-xl font-semibold hover:opacity-90 transition btn-glow">
                            <i class="fas fa-plus mr-2"></i>Add More
                        </a>
                    </div>
                </div>
            </div>

            <!-- Subscription Plans -->
            <div class="mb-20">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold mb-4">
                        <span class="text-gradient">Choose Your Plan</span>
                    </h2>
                    <p class="text-gray-400">Select the perfect plan for your needs</p>
                </div>
                
                <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                    <?php foreach ($plans as $plan): ?>
                    <?php $glowClass = $plan['is_popular'] ? 'glow-fuchsia' : ''; ?>
                    <div class="glass-card rounded-3xl p-8 card-hover relative overflow-hidden group <?php echo $plan['is_popular'] ? 'border-2 border-fuchsia-500' : ''; ?>">
                        <?php if ($plan['is_popular']): ?>
                            <div class="absolute top-0 right-0">
                                <div class="bg-gradient-fuchsia text-xs font-bold px-6 py-3 rounded-bl-2xl">
                                    MOST POPULAR
                                </div>
                            </div>
                            <div class="absolute inset-0 bg-fuchsia-500/5"></div>
                        <?php endif; ?>
                        
                        <div class="text-center mb-8 relative z-10">
                            <h3 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($plan['name']); ?></h3>
                            <div class="flex items-baseline justify-center gap-1 mb-2">
                                <span class="text-6xl font-bold">$<?php echo number_format($plan['price'], 0); ?></span>
                                <span class="text-gray-400 text-lg"><?php echo $plan['price'] > 0 ? '/month' : ''; ?></span>
                            </div>
                            <?php if ($plan['price'] > 0): ?>
                                <p class="text-fuchsia-400 font-medium"><?php echo number_format($plan['credits']); ?> credits included</p>
                            <?php endif; ?>
                        </div>
                        
                        <ul class="space-y-4 mb-8 relative z-10">
                            <?php 
                            $features = explode('|', $plan['features']);
                            foreach ($features as $feature): 
                                $isIncluded = strpos($feature, '-') === false;
                            ?>
                            <li class="flex items-center gap-3 <?php echo $isIncluded ? 'text-green-400' : 'text-gray-600'; ?>">
                                <div class="w-6 h-6 rounded-full <?php echo $isIncluded ? 'bg-green-500/20 flex items-center justify-center' : 'bg-gray-800 flex items-center justify-center'; ?>">
                                    <i class="fas fa-<?php echo $isIncluded ? 'check' : 'times'; ?> text-xs"></i>
                                </div>
                                <span class="<?php echo $isIncluded ? '' : 'line-through opacity-50'; ?>"><?php echo htmlspecialchars(trim($feature)); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <?php if ($user['plan'] === $plan['name']): ?>
                            <button class="w-full glass py-4 rounded-2xl font-semibold cursor-default flex items-center justify-center gap-2" disabled>
                                <i class="fas fa-check-circle"></i>Current Plan
                            </button>
                        <?php elseif ($plan['price'] == 0): ?>
                            <button class="w-full glass py-4 rounded-2xl font-semibold opacity-50 cursor-not-allowed" disabled>
                                Free Forever
                            </button>
                        <?php else: ?>
                            <button onclick="subscribe(<?php echo $plan['id']; ?>, '<?php echo htmlspecialchars($plan['name']); ?>', <?php echo $plan['price']; ?>)" 
                                class="w-full bg-gradient-fuchsia py-4 rounded-2xl font-semibold transition flex items-center justify-center gap-2 btn-glow <?php echo $glowClass; ?>">
                                <i class="fas fa-rocket"></i>Get Started
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Buy Credits -->
            <div id="buy-credits" class="mb-20 scroll-mt-24">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold mb-4">
                        <span class="text-gradient">Quick Credit Top-Up</span>
                    </h2>
                    <p class="text-gray-400">Need more credits? Top up instantly</p>
                </div>
                
                <div class="grid md:grid-cols-4 gap-6 max-w-5xl mx-auto">
                    <?php 
                    $creditPacks = [
                        ['credits' => 500, 'price' => 4.99, 'bonus' => 0, 'from' => 'from-cyan-500', 'to' => 'to-blue-500', 'glow' => 'glow-cyan'],
                        ['credits' => 1000, 'price' => 8.99, 'bonus' => 50, 'from' => 'from-purple-500', 'to' => 'to-pink-500', 'glow' => 'glow-fuchsia'],
                        ['credits' => 2500, 'price' => 19.99, 'bonus' => 250, 'from' => 'from-amber-500', 'to' => 'to-orange-500', 'glow' => 'glow-warm'],
                        ['credits' => 5000, 'price' => 35.99, 'bonus' => 750, 'from' => 'from-green-500', 'to' => 'to-emerald-500', 'glow' => 'glow-cyan']
                    ];
                    foreach ($creditPacks as $index => $pack): 
                        $isBestValue = $index === 2;
                        $totalCredits = $pack['credits'] + $pack['bonus'];
                        $gradientClass = $pack['from'] . ' ' . $pack['to'];
                    ?>
                    <div class="glass-card rounded-3xl p-6 text-center card-hover relative overflow-hidden group <?php echo $isBestValue ? 'border-2 border-amber-400' : ''; ?>">
                        <?php if ($isBestValue): ?>
                            <div class="absolute top-0 left-0 right-0 bg-gradient-warm text-white text-xs font-bold py-2">
                                🎯 BEST VALUE
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-<?php echo $isBestValue ? 4 : 0; ?>">
                            <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-gradient-to-br <?php echo $gradientClass; ?> flex items-center justify-center float shadow-lg <?php echo $pack['glow']; ?>">
                                <i class="fas fa-coins text-3xl text-white"></i>
                            </div>
                            
                            <h3 class="text-2xl font-bold mb-1"><?php echo number_format($totalCredits); ?></h3>
                            <p class="text-gray-400 text-sm mb-4">Total Credits</p>
                            
                            <div class="text-3xl font-bold mb-2">
                                <span class="text-gradient">$<?php echo number_format($pack['price'], 2); ?></span>
                            </div>
                            
                            <?php if ($pack['bonus'] > 0): ?>
                                <div class="text-sm text-green-400 mb-4 flex items-center justify-center gap-1">
                                    <i class="fas fa-gift"></i> +<?php echo number_format($pack['bonus']); ?> bonus!
                                </div>
                            <?php else: ?>
                                <div class="mb-4"></div>
                            <?php endif; ?>
                            
                            <button onclick="buyCredits(<?php echo $pack['credits']; ?>, <?php echo $pack['price']; ?>, <?php echo $totalCredits; ?>)" 
                                class="w-full bg-gradient-to-br <?php echo $gradientClass; ?> hover:opacity-90 py-3 rounded-xl font-semibold transition flex items-center justify-center gap-2 btn-glow <?php echo $pack['glow']; ?>">
                                <i class="fas fa-shopping-cart"></i>Buy Now
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Payment History -->
            <div class="glass-card rounded-3xl overflow-hidden max-w-4xl mx-auto">
                <div class="p-6 border-b border-white/10 bg-gradient-to-r from-fuchsia-500/10 to-purple-500/10">
                    <h3 class="text-xl font-bold flex items-center gap-3">
                        <i class="fas fa-receipt text-fuchsia-400"></i>Payment History
                    </h3>
                </div>
                <table class="w-full">
                    <thead class="bg-black/30">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Date</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Description</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Amount</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Credits</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-receipt text-5xl text-gray-600 mb-4"></i>
                                    <p class="text-gray-500 text-lg">No payments yet</p>
                                    <p class="text-gray-600 text-sm">Your payment history will appear here</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 text-gray-400">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('M d, Y', strtotime($payment['created_at'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($payment['plan_name']): ?>
                                    <span class="bg-gradient-fuchsia px-4 py-2 rounded-full text-xs font-medium">
                                        <?php echo htmlspecialchars($payment['plan_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-amber-400 font-medium">
                                        <i class="fas fa-coins mr-2"></i>Credit Top-up
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-green-400 font-bold text-lg">$<?php echo number_format($payment['amount'], 2); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-amber-400 font-bold">+<?php echo number_format($payment['credits_added']); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($payment['payment_status'] === 'completed'): ?>
                                    <span class="bg-green-500/20 text-green-400 px-4 py-2 rounded-full text-xs font-medium flex items-center gap-1 w-fit">
                                        <i class="fas fa-check-circle"></i>Completed
                                    </span>
                                <?php else: ?>
                                    <span class="bg-amber-500/20 text-amber-400 px-4 py-2 rounded-full text-xs font-medium flex items-center gap-1 w-fit">
                                        <i class="fas fa-clock"></i>Pending
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Security & Support -->
            <div class="mt-16 grid md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                <div class="glass-card rounded-2xl p-6 text-center">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center glow-fuchsia">
                        <i class="fab fa-stripe text-2xl text-white"></i>
                    </div>
                    <h4 class="font-bold mb-2">Secure Payments</h4>
                    <p class="text-gray-400 text-sm">Powered by Stripe with bank-level security</p>
                </div>
                <div class="glass-card rounded-2xl p-6 text-center">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center glow-cyan">
                        <i class="fas fa-shield-alt text-2xl text-white"></i>
                    </div>
                    <h4 class="font-bold mb-2">SSL Encrypted</h4>
                    <p class="text-gray-400 text-sm">256-bit SSL encryption protects your data</p>
                </div>
                <div class="glass-card rounded-2xl p-6 text-center">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center glow-cyan">
                        <i class="fas fa-headset text-2xl text-white"></i>
                    </div>
                    <h4 class="font-bold mb-2">24/7 Support</h4>
                    <p class="text-gray-400 text-sm">Get help anytime with our support team</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function subscribe(planId, planName, price) {
            if (confirm('Subscribe to ' + planName + ' for $' + price + '/month?\n\nThis will charge your payment method.')) {
                const formData = new FormData();
                formData.append('action', 'create_checkout_session');
                formData.append('plan_id', planId);
                
                fetch('payment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ' + data.message);
                        location.reload();
                    } else {
                        alert('❌ ' + data.message);
                    }
                })
                .catch(error => {
                    alert('❌ Payment failed. Please try again.');
                });
            }
        }

        function buyCredits(credits, price, totalCredits) {
            if (confirm('Purchase ' + totalCredits.toLocaleString() + ' credits for $' + price + '?\n\nThis will charge your payment method.')) {
                const formData = new FormData();
                formData.append('action', 'add_credits');
                formData.append('credits', credits);
                formData.append('price', price);
                
                fetch('payment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Credits added successfully!');
                        location.reload();
                    } else {
                        alert('❌ ' + data.message);
                    }
                })
                .catch(error => {
                    alert('❌ Purchase failed. Please try again.');
                });
            }
        }

        document.addEventListener('click', function(e) {
            const userMenu = document.getElementById('userMenu');
            if (!e.target.closest('.relative')) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
