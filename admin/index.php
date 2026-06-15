<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function validateCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCSRF($csrfToken)) {
        $message = 'Invalid security token';
        $messageType = 'error';
    } else {
        if (isset($_POST['delete_user_id'])) {
            $userIdToDelete = intval($_POST['delete_user_id']);
            if ($userIdToDelete !== intval($_SESSION['user_id'])) {
                $stmt = $conn->prepare("DELETE FROM chat_history WHERE user_id = ?");
                $stmt->bind_param("i", $userIdToDelete);
                $stmt->execute();
                $stmt->close();
                $stmt = $conn->prepare("DELETE FROM credit_usage WHERE user_id = ?");
                $stmt->bind_param("i", $userIdToDelete);
                $stmt->execute();
                $stmt->close();
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $userIdToDelete);
                $stmt->execute();
                $stmt->close();
                $message = 'User deleted successfully';
                $messageType = 'success';
            }
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'add_plan') {
            $name = sanitizeInput($_POST['plan_name']);
            $price = floatval($_POST['plan_price']);
            $credits = intval($_POST['plan_credits']);
            $duration = intval($_POST['plan_duration']);
            $features = sanitizeInput($_POST['plan_features']);
            $popular = isset($_POST['plan_popular']) ? 1 : 0;
            if ($name && $price >= 0 && $credits > 0 && $duration > 0) {
                $stmt = $conn->prepare("INSERT INTO plans (name, price, credits, duration_days, features, is_popular) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sdiisi", $name, $price, $credits, $duration, $features, $popular);
                $stmt->execute();
                $stmt->close();
                $message = 'Plan added successfully';
                $messageType = 'success';
            }
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'delete_plan') {
            $planId = intval($_POST['plan_id']);
            if ($planId > 0) {
                $stmt = $conn->prepare("DELETE FROM plans WHERE id = ?");
                $stmt->bind_param("i", $planId);
                $stmt->execute();
                $stmt->close();
                $message = 'Plan deleted successfully';
                $messageType = 'success';
            }
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'update_user_credits') {
            $userId = intval($_POST['user_id']);
            $credits = intval($_POST['credits']);
            if ($userId > 0 && $credits > 0 && $credits <= 10000) {
                $stmt = $conn->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
                $stmt->bind_param("ii", $credits, $userId);
                $stmt->execute();
                $stmt->close();
                $message = 'Credits added successfully';
                $messageType = 'success';
            }
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'update_user_plan') {
            $userId = intval($_POST['user_id']);
            $plan = sanitizeInput($_POST['plan']);
            if ($userId > 0 && in_array($plan, ['free', 'pro', 'enterprise'])) {
                $stmt = $conn->prepare("UPDATE users SET plan = ? WHERE id = ?");
                $stmt->bind_param("si", $plan, $userId);
                $stmt->execute();
                $stmt->close();
                $message = 'Plan updated successfully';
                $messageType = 'success';
            }
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'save_api_keys') {
            $newApiKeys = $_POST['api_keys'] ?? array();
            if (isset($newApiKeys['deepseek'])) {
                $keys = array_filter(array_map('trim', explode(',', $newApiKeys['deepseek'])));
                $newApiKeys['deepseek'] = array_values($keys);
            }
            if (isset($newApiKeys['groq'])) {
                $keys = array_filter(array_map('trim', explode(',', $newApiKeys['groq'])));
                $newApiKeys['groq'] = array_values($keys);
            }
            if (isset($newApiKeys['stability'])) {
                $keys = array_filter(array_map('trim', explode(',', $newApiKeys['stability'])));
                $newApiKeys['stability'] = array_values($keys);
            }
            $finalApiKeys = array_merge(API_KEYS, $newApiKeys);
            
            $content = "<?php\n/**\n * Configuration file for Chat Application\n */\n\n";
            $content .= "if (session_status() === PHP_SESSION_NONE) { session_start(); }\n\n";
            if (defined('GOOGLE_OAUTH_CONFIG')) {
                $content .= "define('GOOGLE_OAUTH_CONFIG', " . var_export(GOOGLE_OAUTH_CONFIG, true) . ");\n\n";
            }
            $content .= "define('API_KEYS', " . var_export($finalApiKeys, true) . ");\n\n";
            if (defined('APP_SETTINGS')) {
                $content .= "define('APP_SETTINGS', " . var_export(APP_SETTINGS, true) . ");\n\n";
            }
            if (defined('MODELS')) {
                $content .= "define('MODELS', " . var_export(MODELS, true) . ");\n\n";
            }
            $content .= "function getApiKey(\$provider) {\n    \$keys = API_KEYS[\$provider] ?? '';\n    if (is_array(\$keys) && !empty(\$keys)) { return \$keys[array_rand(\$keys)]; }\n    return \$keys;\n}\n";
            $content .= "function getModels(\$provider) { return MODELS[\$provider] ?? array(); }\n";
            
            if (file_put_contents(dirname(__DIR__) . '/config/config.php', $content)) {
                $message = 'API keys saved successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error saving config';
                $messageType = 'error';
            }
        }
    }
}

$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$totalCredits = $conn->query("SELECT COALESCE(SUM(credits), 0) as total FROM users")->fetch_assoc()['total'];
$totalPayments = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE payment_status = 'completed'")->fetch_assoc()['total'];
$totalConversations = $conn->query("SELECT COUNT(DISTINCT conversation_id) as count FROM chat_history")->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT id, username, email, role, credits, plan, reg_date FROM users ORDER BY reg_date DESC LIMIT 100");
$stmt->execute();
$result = $stmt->get_result();
$users = array();
while ($row = $result->fetch_assoc()) { $users[] = $row; }
$stmt->close();

$plans_result = $conn->query("SELECT * FROM plans ORDER BY price ASC");
$plans = array();
while ($plan = $plans_result->fetch_assoc()) { $plans[] = $plan; }

$stmt = $conn->prepare("SELECT p.*, u.username FROM payments p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 20");
$stmt->execute();
$result = $stmt->get_result();
$payments = array();
while ($payment = $result->fetch_assoc()) { $payments[] = $payment; }
$stmt->close();

$stmt = $conn->prepare("SELECT u.username, SUM(c.credits_used) as total, c.feature, MAX(c.created_at) as created_at FROM credit_usage c LEFT JOIN users u ON c.user_id = u.id GROUP BY c.user_id, c.feature, u.username ORDER BY total DESC LIMIT 20");
$stmt->execute();
$result = $stmt->get_result();
$usage = array();
while ($u = $result->fetch_assoc()) { $usage[] = $u; }
$stmt->close();

$page = $_GET['tab'] ?? 'dashboard';
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Elyxar AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { nexus: { 500: '#10a37f', 600: '#158f6e' } }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: linear-gradient(135deg, #0d0d0f 0%, #17171a 50%, #0d0d0f 100%); min-height: 100vh; }
        .glass { background: rgba(30, 30, 36, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.06); }
        .glass-card { background: linear-gradient(135deg, rgba(30,30,36,0.8) 0%, rgba(20,20,24,0.8) 100%); border: 1px solid rgba(255,255,255,0.08); transition: all 0.3s ease; }
        .glass-card:hover { transform: translateY(-5px); border-color: rgba(16, 163, 127, 0.3); box-shadow: 0 20px 50px rgba(16, 163, 127, 0.15); }
        .nav-item { transition: all 0.25s ease; border-radius: 12px; }
        .nav-item:hover { background: rgba(16, 163, 127, 0.1); transform: translateX(5px); }
        .nav-item.active { background: linear-gradient(135deg, rgba(16, 163, 127, 0.25), rgba(16, 163, 127, 0.1)); border: 1px solid rgba(16, 163, 127, 0.4); }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .pulse-glow { animation: pulseGlow 2s ease-in-out infinite; }
        @keyframes pulseGlow { 0%, 100% { box-shadow: 0 0 20px rgba(16, 163, 127, 0.3); } 50% { box-shadow: 0 0 40px rgba(16, 163, 127, 0.5); } }
        .float { animation: float 3s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-8px); } }
        .stat-gradient-1 { background: linear-gradient(135deg, #10a37f 0%, #158f6e 100%); }
        .stat-gradient-2 { background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%); }
        .stat-gradient-3 { background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%); }
        .stat-gradient-4 { background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%); }
        .data-table th { background: linear-gradient(135deg, rgba(16, 163, 127, 0.1) 0%, rgba(16, 163, 127, 0.05) 100%); }
        .data-table tr:hover { background: rgba(16, 163, 127, 0.05); }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.25); }
        .btn-primary { background: linear-gradient(135deg, #10a37f 0%, #158f6e 100%); transition: all 0.3s ease; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(16, 163, 127, 0.4); }
        .input-dark { background: rgba(20, 20, 24, 0.8); border: 1px solid rgba(255,255,255,0.1); transition: all 0.3s ease; }
        .input-dark:focus { border-color: #10a37f; box-shadow: 0 0 0 3px rgba(16, 163, 127, 0.15); }
        .page-content { animation: pageIn 0.4s ease-out; }
        @keyframes pageIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
    </style>
</head>
<body class="text-white">
    <div class="flex min-h-screen">
        <aside class="w-72 glass border-r border-[#3f3f46]/30 flex flex-col fixed h-full z-50">
            <div class="p-6 border-b border-[#3f3f46]/30">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#10a37f] to-[#158f6e] flex items-center justify-center pulse-glow">
                        <i class="fas fa-shield-halved text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold bg-gradient-to-r from-[#10a37f] to-[#158f6e] bg-clip-text text-transparent">Elyxar AI</h2>
                        <p class="text-xs text-gray-500">Admin Dashboard</p>
                    </div>
                </div>
            </div>
            
            <nav class="p-4 space-y-2 flex-1 overflow-y-auto">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 mb-2">Main Menu</p>
                <a href="?tab=dashboard" class="nav-item flex items-center gap-3 px-4 py-3 <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#10a37f] to-[#158f6e] flex items-center justify-center"><i class="fas fa-chart-line text-white text-sm"></i></div>
                    <span class="font-medium">Dashboard</span>
                </a>
                <a href="?tab=users" class="nav-item flex items-center gap-3 px-4 py-3 <?php echo $page === 'users' ? 'active' : ''; ?>">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center"><i class="fas fa-users text-white text-sm"></i></div>
                    <span class="font-medium">Users</span>
                </a>
                <a href="?tab=plans" class="nav-item flex items-center gap-3 px-4 py-3 <?php echo $page === 'plans' ? 'active' : ''; ?>">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center"><i class="fas fa-crown text-white text-sm"></i></div>
                    <span class="font-medium">Plans</span>
                </a>
                <a href="?tab=api" class="nav-item flex items-center gap-3 px-4 py-3 <?php echo $page === 'api' ? 'active' : ''; ?>">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center"><i class="fas fa-key text-white text-sm"></i></div>
                    <span class="font-medium">API Keys</span>
                </a>
                <a href="?tab=payments" class="nav-item flex items-center gap-3 px-4 py-3 <?php echo $page === 'payments' ? 'active' : ''; ?>">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center"><i class="fas fa-credit-card text-white text-sm"></i></div>
                    <span class="font-medium">Payments</span>
                </a>
                <a href="?tab=usage" class="nav-item flex items-center gap-3 px-4 py-3 <?php echo $page === 'usage' ? 'active' : ''; ?>">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center"><i class="fas fa-chart-pie text-white text-sm"></i></div>
                    <span class="font-medium">Credit Usage</span>
                </a>
            </nav>
            
            <div class="p-4 border-t border-[#3f3f46]/30">
                <a href="../index.php" class="nav-item flex items-center gap-3 px-4 py-3">
                    <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center"><i class="fas fa-arrow-left text-gray-400 text-sm"></i></div>
                    <span class="text-gray-400">Back to Chat</span>
                </a>
            </div>
        </aside>

        <main class="flex-1 ml-72 p-8 bg-[#0d0d0f] relative">
            <div class="fixed inset-0 pointer-events-none overflow-hidden">
                <div class="absolute top-0 right-0 w-96 h-96 bg-[#10a37f]/5 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-96 h-96 bg-[#158f6e]/5 rounded-full blur-3xl"></div>
            </div>

            <div class="relative z-10 page-content">
                <?php if ($message): ?>
                <div class="mb-8 p-4 rounded-2xl flex items-center gap-3 <?php echo $messageType === 'success' ? 'bg-[#10a37f]/10 border border-[#10a37f]/30' : 'bg-red-500/10 border border-red-500/30'; ?>">
                    <div class="w-10 h-10 rounded-full <?php echo $messageType === 'success' ? 'bg-[#10a37f]/20' : 'bg-red-500/20'; ?> flex items-center justify-center">
                        <i class="fas <?php echo $messageType === 'success' ? 'fa-check' : 'fa-times'; ?> <?php echo $messageType === 'success' ? 'text-[#10a37f]' : 'text-red-400'; ?>"></i>
                    </div>
                    <span class="<?php echo $messageType === 'success' ? 'text-green-400' : 'text-red-400'; ?>"><?php echo htmlspecialchars($message); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($page === 'dashboard'): ?>
                <div class="space-y-8 fade-in">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#10a37f] to-[#158f6e] flex items-center justify-center float">
                            <i class="fas fa-rocket text-3xl text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold">Welcome Back</h1>
                            <p class="text-gray-400">Here's what's happening with your platform</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="glass-card p-6 rounded-2xl relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-[#10a37f]/20 to-transparent rounded-full -mr-10 -mt-10"></div>
                            <div class="flex items-center justify-between relative z-10">
                                <div><p class="text-gray-400 text-sm">Total Users</p><p class="text-4xl font-bold mt-2"><?php echo number_format($totalUsers); ?></p></div>
                                <div class="w-14 h-14 rounded-2xl stat-gradient-1 flex items-center justify-center shadow-lg"><i class="fas fa-users text-2xl text-white"></i></div>
                            </div>
                        </div>
                        <div class="glass-card p-6 rounded-2xl relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-blue-500/20 to-transparent rounded-full -mr-10 -mt-10"></div>
                            <div class="flex items-center justify-between relative z-10">
                                <div><p class="text-gray-400 text-sm">Total Credits</p><p class="text-4xl font-bold mt-2"><?php echo number_format($totalCredits); ?></p></div>
                                <div class="w-14 h-14 rounded-2xl stat-gradient-2 flex items-center justify-center shadow-lg"><i class="fas fa-coins text-2xl text-white"></i></div>
                            </div>
                        </div>
                        <div class="glass-card p-6 rounded-2xl relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-amber-500/20 to-transparent rounded-full -mr-10 -mt-10"></div>
                            <div class="flex items-center justify-between relative z-10">
                                <div><p class="text-gray-400 text-sm">Total Revenue</p><p class="text-4xl font-bold mt-2">$<?php echo number_format($totalPayments, 2); ?></p></div>
                                <div class="w-14 h-14 rounded-2xl stat-gradient-3 flex items-center justify-center shadow-lg"><i class="fas fa-dollar-sign text-2xl text-white"></i></div>
                            </div>
                        </div>
                        <div class="glass-card p-6 rounded-2xl relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-purple-500/20 to-transparent rounded-full -mr-10 -mt-10"></div>
                            <div class="flex items-center justify-between relative z-10">
                                <div><p class="text-gray-400 text-sm">Conversations</p><p class="text-4xl font-bold mt-2"><?php echo number_format($totalConversations); ?></p></div>
                                <div class="w-14 h-14 rounded-2xl stat-gradient-4 flex items-center justify-center shadow-lg"><i class="fas fa-comments text-2xl text-white"></i></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="glass-card p-8 rounded-2xl">
                        <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#10a37f] to-[#158f6e] flex items-center justify-center"><i class="fas fa-server text-white"></i></div>
                            System Information
                        </h2>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="p-4 bg-[#17171a] rounded-xl border border-[#3f3f46]/50"><p class="text-gray-500 text-sm mb-1">PHP Version</p><p class="font-semibold text-white"><?php echo phpversion(); ?></p></div>
                            <div class="p-4 bg-[#17171a] rounded-xl border border-[#3f3f46]/50"><p class="text-gray-500 text-sm mb-1">Server</p><p class="font-semibold text-white">Apache/Nginx</p></div>
                            <div class="p-4 bg-[#17171a] rounded-xl border border-[#3f3f46]/50"><p class="text-gray-500 text-sm mb-1">Admin Version</p><p class="font-semibold text-white">2.0.0</p></div>
                            <div class="p-4 bg-[#17171a] rounded-xl border border-[#3f3f46]/50"><p class="text-gray-500 text-sm mb-1">Time</p><p class="font-semibold text-white"><?php echo date('H:i'); ?></p></div>
                        </div>
                    </div>
                </div>
                <?php elseif ($page === 'users'): ?>
                <div class="space-y-6 fade-in">
                    <div class="flex items-center justify-between">
                        <div><h1 class="text-3xl font-bold">Users Management</h1><p class="text-gray-400">Manage all registered users</p></div>
                        <div class="px-4 py-2 rounded-xl bg-[#10a37f]/10 border border-[#10a37f]/30"><span class="text-[#10a37f] font-semibold"><?php echo count($users); ?></span> Users</div>
                    </div>
                    <div class="glass-card rounded-2xl overflow-hidden">
                        <table class="w-full data-table">
                            <thead>
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">User</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Email</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Plan</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Credits</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#3f3f46]/50">
                                <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-white/5 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#10a37f] to-[#158f6e] flex items-center justify-center font-bold"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                                            <span class="font-medium"><?php echo htmlspecialchars($user['username']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-400"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="px-6 py-4">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="action" value="update_user_plan">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="plan" onchange="this.form.submit()" class="input-dark rounded-lg px-3 py-2 text-sm cursor-pointer">
                                                <option value="free" <?php echo $user['plan'] === 'free' ? 'selected' : ''; ?>>Free</option>
                                                <option value="pro" <?php echo $user['plan'] === 'pro' ? 'selected' : ''; ?>>Pro</option>
                                                <option value="enterprise" <?php echo $user['plan'] === 'enterprise' ? 'selected' : ''; ?>>Enterprise</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4">
                                        <form method="POST" class="inline flex items-center gap-2">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="action" value="update_user_credits">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="number" name="credits" value="100" class="input-dark rounded-lg px-3 py-2 w-24 text-sm" min="1" max="10000">
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-[#10a37f]/20 text-[#10a37f] hover:bg-[#10a37f] hover:text-white transition flex items-center justify-center"><i class="fas fa-plus text-xs"></i></button>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="delete_user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-red-500/20 text-red-400 hover:bg-red-500 hover:text-white transition flex items-center justify-center" onclick="return confirm('Delete this user?')"><i class="fas fa-trash text-xs"></i></button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php elseif ($page === 'plans'): ?>
                <div class="space-y-6 fade-in">
                    <h1 class="text-3xl font-bold">Plans Management</h1>
                    <div class="glass-card p-6 rounded-2xl">
                        <h2 class="text-xl font-bold mb-4">Create New Plan</h2>
                        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4 items-end">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="add_plan">
                            <div class="lg:col-span-2">
                                <label class="text-xs text-gray-500 mb-2 block">Plan Name</label>
                                <input type="text" name="plan_name" required class="input-dark w-full rounded-xl px-4 py-3" placeholder="Pro Plan">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-2 block">Price ($)</label>
                                <input type="number" name="plan_price" step="0.01" required class="input-dark w-full rounded-xl px-4 py-3" placeholder="9.99">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-2 block">Credits</label>
                                <input type="number" name="plan_credits" required class="input-dark w-full rounded-xl px-4 py-3" placeholder="1000">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-2 block">Days</label>
                                <input type="number" name="plan_duration" required class="input-dark w-full rounded-xl px-4 py-3" placeholder="30">
                            </div>
                            <label class="flex items-center gap-2 pb-3">
                                <input type="checkbox" name="plan_popular" class="rounded text-[#10a37f]"> <span class="text-sm text-gray-400">Popular</span>
                            </label>
                            <button type="submit" class="btn-primary px-6 py-3 rounded-xl font-semibold flex items-center justify-center gap-2"><i class="fas fa-plus"></i> Add</button>
                        </form>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($plans as $plan): ?>
                        <div class="glass-card p-6 rounded-2xl relative group">
                            <?php if ($plan['is_popular']): ?>
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-gradient-to-r from-[#10a37f] to-[#158f6e] rounded-full text-xs font-bold">POPULAR</div>
                            <?php endif; ?>
                            <h3 class="text-2xl font-bold"><?php echo htmlspecialchars($plan['name']); ?></h3>
                            <p class="text-4xl font-bold mt-4">$<?php echo number_format($plan['price'], 2); ?><span class="text-gray-400 text-base font-normal">/mo</span></p>
                            <div class="mt-4 space-y-2">
                                <p class="text-[#10a37f]"><i class="fas fa-coins mr-2"></i><?php echo number_format($plan['credits']); ?> credits</p>
                                <p class="text-gray-400 text-sm"><i class="fas fa-calendar mr-2"></i><?php echo $plan['duration_days']; ?> days</p>
                            </div>
                            <form method="POST" class="mt-6">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="action" value="delete_plan">
                                <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                <button type="submit" class="w-full py-2 rounded-lg border border-red-500/30 text-red-400 hover:bg-red-500 hover:text-white transition" onclick="return confirm('Delete this plan?')"><i class="fas fa-trash mr-2"></i>Delete Plan</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php elseif ($page === 'api'): ?>
                <div class="space-y-6 fade-in">
                    <h1 class="text-3xl font-bold">API Configuration</h1>
                    <form method="POST" class="glass-card p-6 rounded-2xl">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="save_api_keys">
                        <div class="flex items-center justify-between mb-6">
                            <div><h2 class="text-xl font-bold">API Keys</h2><p class="text-gray-400 text-sm">Configure your AI provider keys</p></div>
                            <button type="submit" class="btn-primary px-8 py-3 rounded-xl font-semibold flex items-center gap-2"><i class="fas fa-save"></i> Save Changes</button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php 
                            $providers = array(
                                array('key' => 'openai', 'name' => 'OpenAI', 'icon' => 'fa-bolt', 'color' => 'from-green-500 to-emerald-600'),
                                array('key' => 'gemini', 'name' => 'Google Gemini', 'icon' => 'fa-gem', 'color' => 'from-blue-500 to-cyan-600'),
                                array('key' => 'deepseek', 'name' => 'DeepSeek', 'icon' => 'fa-dragon', 'color' => 'from-purple-500 to-pink-600', 'multi' => true),
                                array('key' => 'groq', 'name' => 'Groq', 'icon' => 'fa-fire', 'color' => 'from-orange-500 to-red-600', 'multi' => true),
                                array('key' => 'sambanova', 'name' => 'SambaNova', 'icon' => 'fa-server', 'color' => 'from-cyan-500 to-blue-600'),
                                array('key' => 'openrouter', 'name' => 'OpenRouter', 'icon' => 'fa-network-wired', 'color' => 'from-pink-500 to-rose-600'),
                                array('key' => 'stability', 'name' => 'Stability AI', 'icon' => 'fa-stability', 'color' => 'from-red-500 to-orange-600', 'multi' => true),
                                array('key' => 'replicate', 'name' => 'Replicate', 'icon' => 'fa-robot', 'color' => 'from-amber-500 to-yellow-600'),
                                array('key' => 'nvidia', 'name' => 'NVIDIA NIM', 'icon' => 'fa-microchip', 'color' => 'from-indigo-500 to-purple-600')
                            );
                            foreach ($providers as $p):
                                $val = API_KEYS[$p['key']] ?? '';
                                $display = is_array($val) ? implode(', ', $val) : $val;
                            ?>
                            <div class="glass p-4 rounded-xl">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br <?php echo $p['color']; ?> flex items-center justify-center"><i class="fas <?php echo $p['icon']; ?> text-white"></i></div>
                                    <span class="font-semibold"><?php echo $p['name']; ?></span>
                                </div>
                                <?php if (!empty($p['multi'])): ?>
                                <input type="text" name="api_keys[<?php echo $p['key']; ?>]" value="<?php echo htmlspecialchars($display); ?>" placeholder="key1, key2, key3" class="input-dark w-full rounded-lg px-4 py-2 font-mono text-sm">
                                <p class="text-xs text-gray-500 mt-2">Separate multiple keys with commas</p>
                                <?php else: ?>
                                <input type="password" name="api_keys[<?php echo $p['key']; ?>]" value="<?php echo htmlspecialchars($display); ?>" class="input-dark w-full rounded-lg px-4 py-2 font-mono text-sm" placeholder="Enter API key">
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>
                <?php elseif ($page === 'payments'): ?>
                <div class="space-y-6 fade-in">
                    <h1 class="text-3xl font-bold">Payment History</h1>
                    <div class="glass-card rounded-2xl overflow-hidden">
                        <table class="w-full data-table">
                            <thead>
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Date</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">User</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Amount</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Credits</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#3f3f46]/50">
                                <?php foreach ($payments as $p): ?>
                                <tr class="hover:bg-white/5">
                                    <td class="px-6 py-4 text-gray-400"><?php echo date('M d, Y H:i', strtotime($p['created_at'])); ?></td>
                                    <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($p['username'] ?? 'Unknown'); ?></td>
                                    <td class="px-6 py-4"><span class="text-green-400 font-bold">$<?php echo number_format($p['amount'], 2); ?></span></td>
                                    <td class="px-6 py-4"><span class="text-[#10a37f] font-semibold">+<?php echo number_format($p['credits_added']); ?></span></td>
                                    <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $p['payment_status'] === 'completed' ? 'bg-green-500/20 text-green-400' : 'bg-amber-500/20 text-amber-400'; ?>"><?php echo $p['payment_status']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php elseif ($page === 'usage'): ?>
                <div class="space-y-6 fade-in">
                    <h1 class="text-3xl font-bold">Credit Usage</h1>
                    <div class="glass-card rounded-2xl overflow-hidden">
                        <table class="w-full data-table">
                            <thead>
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">User</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Feature</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Total Used</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Last Used</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#3f3f46]/50">
                                <?php foreach ($usage as $u): ?>
                                <tr class="hover:bg-white/5">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#10a37f] to-[#158f6e] flex items-center justify-center text-xs font-bold"><?php echo strtoupper(substr($u['username'] ?? 'U', 0, 1)); ?></div>
                                            <?php echo htmlspecialchars($u['username'] ?? 'Unknown'); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4"><span class="px-2 py-1 rounded-lg bg-[#10a37f]/10 text-[#10a37f] text-sm"><?php echo htmlspecialchars($u['feature']); ?></span></td>
                                    <td class="px-6 py-4"><span class="text-2xl font-bold text-[#10a37f]"><?php echo number_format($u['total']); ?></span></td>
                                    <td class="px-6 py-4 text-gray-400"><?php echo $u['created_at'] ? date('M d, Y', strtotime($u['created_at'])) : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>