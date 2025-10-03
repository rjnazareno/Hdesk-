<?php
/**
 * Temporary Login Debug Version
 * Use this version on live server to debug login issues
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Log function for debugging
function debug_log($message) {
    error_log("[LOGIN DEBUG] " . $message);
    if (isset($_GET['debug'])) {
        echo "<script>console.log('DEBUG: " . addslashes($message) . "');</script>";
    }
}

debug_log("Starting login process");

try {
    require_once 'config/database.php';
    debug_log("Database config loaded successfully");
} catch (Exception $e) {
    debug_log("Failed to load database config: " . $e->getMessage());
    die("Configuration error. Please check server setup.");
}

try {
    require_once 'includes/security.php';
    debug_log("Security functions loaded successfully");
} catch (Exception $e) {
    debug_log("Failed to load security functions: " . $e->getMessage());
    die("Security configuration error. Please check server setup.");
}

session_start();
debug_log("Session started. Session ID: " . session_id());

$error = '';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debug_log("POST request received");
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $userType = $_POST['user_type'] ?? 'employee';
    
    debug_log("Login attempt - Username: $username, Type: $userType");
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
        debug_log("Empty username or password");
    } else {
        try {
            debug_log("Attempting database connection");
            $pdo = getDB();
            debug_log("Database connection successful");
            
            if ($userType === 'it_staff') {
                debug_log("Checking IT staff login");
                $stmt = $pdo->prepare("SELECT staff_id as id, name, username, email, password FROM it_staff WHERE username = ? AND is_active = 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    debug_log("IT staff user not found: $username");
                } else {
                    debug_log("IT staff user found: " . $user['name']);
                }
                
                // Check password - handle both hashed and plain text
                $passwordMatch = false;
                if ($user) {
                    debug_log("Checking password for IT staff user");
                    // First try password_verify for hashed passwords
                    if (password_verify($password, $user['password'])) {
                        $passwordMatch = true;
                        debug_log("Password verified with password_verify()");
                    }
                    // If that fails, try direct comparison for plain text passwords
                    elseif ($password === $user['password']) {
                        $passwordMatch = true;
                        debug_log("Password matched with direct comparison (plain text)");
                        
                        // Optionally update to hashed password for security
                        try {
                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                            $updateStmt = $pdo->prepare("UPDATE it_staff SET password = ? WHERE staff_id = ?");
                            $updateStmt->execute([$hashedPassword, $user['id']]);
                            debug_log("Password updated to hashed version");
                        } catch (Exception $e) {
                            debug_log("Password hash update failed: " . $e->getMessage());
                        }
                    } else {
                        debug_log("Password verification failed for IT staff");
                    }
                }
                
                if ($passwordMatch) {
                    debug_log("Setting session variables for IT staff");
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_type'] = 'it_staff';
                    $_SESSION['user_data'] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'name' => $user['name'],
                        'email' => $user['email']
                    ];
                    
                    debug_log("Session set, redirecting to dashboard");
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                debug_log("Checking employee login");
                $stmt = $pdo->prepare("SELECT id, fname, lname, username, email, password FROM employees WHERE username = ? AND status = 'active'");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    debug_log("Employee user not found: $username");
                } else {
                    debug_log("Employee user found: " . $user['fname'] . ' ' . $user['lname']);
                }
                
                // Verify password using password_verify for hashed passwords
                if ($user && password_verify($password, $user['password'])) {
                    debug_log("Setting session variables for employee");
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_type'] = 'employee';
                    $_SESSION['user_data'] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'name' => $user['fname'] . ' ' . $user['lname'],
                        'email' => $user['email']
                    ];
                    
                    debug_log("Session set, redirecting to dashboard");
                    header('Location: dashboard.php');
                    exit;
                } elseif ($user) {
                    debug_log("Password verification failed for employee");
                }
            }
            
            $error = 'Invalid username or password.';
            debug_log("Login failed - invalid credentials");
            
        } catch (Exception $e) {
            debug_log("Login exception: " . $e->getMessage());
            debug_log("Exception file: " . $e->getFile());
            debug_log("Exception line: " . $e->getLine());
            debug_log("Exception trace: " . $e->getTraceAsString());
            $error = 'Login system error. Please try again. Error: ' . $e->getMessage();
        }
    }
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    debug_log("User already logged in, redirecting to dashboard");
    header('Location: dashboard.php');
    exit;
}

debug_log("Displaying login form");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Support - Login (Debug Mode)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        <!-- Debug Info -->
        <?php if (isset($_GET['debug'])): ?>
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg mb-6 text-sm">
                <strong>Debug Mode Active</strong><br>
                Session ID: <?php echo session_id(); ?><br>
                PHP Version: <?php echo PHP_VERSION; ?><br>
                Check browser console for detailed logs.
            </div>
        <?php endif; ?>
        
        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-ticket text-blue-600 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">IT Support System</h2>
                <p class="text-gray-600 mt-2">Sign in to your account</p>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo escape($error); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="space-y-6">
                <!-- User Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Login as:</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="user_type" value="employee" class="sr-only peer" checked>
                            <div class="bg-gray-50 peer-checked:bg-blue-50 peer-checked:border-blue-200 border-2 border-gray-200 rounded-lg p-3 text-center transition">
                                <i class="fas fa-user text-gray-600 peer-checked:text-blue-600 mb-1"></i>
                                <div class="text-sm font-medium text-gray-700 peer-checked:text-blue-700">Employee</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="user_type" value="it_staff" class="sr-only peer">
                            <div class="bg-gray-50 peer-checked:bg-blue-50 peer-checked:border-blue-200 border-2 border-gray-200 rounded-lg p-3 text-center transition">
                                <i class="fas fa-cog text-gray-600 peer-checked:text-blue-600 mb-1"></i>
                                <div class="text-sm font-medium text-gray-700 peer-checked:text-blue-700">IT Staff</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <div class="relative">
                        <input type="text" id="username" name="username" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pl-11"
                               placeholder="Enter your username">
                        <i class="fas fa-user absolute left-3 top-3.5 text-gray-400"></i>
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pl-11"
                               placeholder="Enter your password">
                        <i class="fas fa-lock absolute left-3 top-3.5 text-gray-400"></i>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-150 transform hover:scale-105">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Sign In
                </button>
            </form>

            <!-- Debug Links -->
            <?php if (!isset($_GET['debug'])): ?>
                <div class="text-center mt-4">
                    <a href="?debug=1" class="text-blue-600 hover:text-blue-700 text-sm">
                        <i class="fas fa-bug mr-1"></i>
                        Enable Debug Mode
                    </a>
                </div>
            <?php endif; ?>

        <!-- Footer -->
        <div class="text-center mt-6 text-sm text-gray-500">
            <i class="fas fa-shield-alt mr-1"></i>
            Secure IT Support System
        </div>
    </div>
</body>
</html>