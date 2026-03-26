<?php
/**
 * SSO Login - Receives token from Helmer Academy → logs into Ghost Pix
 */
require_once 'includes/db.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: /login');
    exit;
}

$ssoSecret = defined('SSO_SECRET') ? SSO_SECRET : 'ghostpix_helmer_sso_2026_secure_key';

// Parse token
$parts = explode('.', $token);
if (count($parts) !== 2) {
    header('Location: /login?error=invalid_token');
    exit;
}

[$payloadEncoded, $signature] = $parts;

// Verify signature
$expectedSig = hash_hmac('sha256', $payloadEncoded, $ssoSecret);
if (!hash_equals($expectedSig, $signature)) {
    header('Location: /login?error=invalid_signature');
    exit;
}

$payload = json_decode(base64_decode($payloadEncoded), true);
if (!$payload || !isset($payload['email']) || !isset($payload['ts'])) {
    header('Location: /login?error=invalid_payload');
    exit;
}

// Token expires after 120 seconds
if (time() - $payload['ts'] > 120) {
    header('Location: /login?error=token_expired');
    exit;
}

// Must come from academy
if (($payload['from'] ?? '') !== 'academy') {
    header('Location: /login?error=invalid_source');
    exit;
}

$email = $payload['email'];
$name = $payload['name'] ?? '';

// Find or create user
$stmt = $pdo->prepare("SELECT id, email, full_name, status FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // Auto-create account
    $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, status, commission_rate) VALUES (?, ?, ?, 'approved', 5.00)");
    $stmt->execute([$email, $randomPass, $name]);
    $userId = $pdo->lastInsertId();
} else {
    if ($user['status'] === 'blocked') {
        header('Location: /login?error=blocked');
        exit;
    }
    $userId = $user['id'];
}

// Log user in
session_regenerate_id(true);
$_SESSION['user_id'] = $userId;

header('Location: /dashboard');
exit;
