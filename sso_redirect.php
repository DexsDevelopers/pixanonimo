<?php
/**
 * SSO Redirect - Ghost Pix → Helmer Academy
 * Generates a signed token and redirects user to Academy
 */
require_once 'includes/db.php';

if (!isLoggedIn()) {
    header('Location: /login');
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: /dashboard');
    exit;
}

// Shared secret (must match on Academy side)
$ssoSecret = defined('SSO_SECRET') ? SSO_SECRET : 'ghostpix_helmer_sso_2026_secure_key';

$payload = [
    'email' => $user['email'],
    'name' => $user['full_name'],
    'from' => 'ghostpix',
    'ts' => time(),
    'nonce' => bin2hex(random_bytes(16))
];

$payloadEncoded = base64_encode(json_encode($payload));
$signature = hash_hmac('sha256', $payloadEncoded, $ssoSecret);
$token = $payloadEncoded . '.' . $signature;

$academyUrl = defined('ACADEMY_URL') ? ACADEMY_URL : 'https://helmer-mbs.site';
header('Location: ' . $academyUrl . '/sso_login.php?token=' . urlencode($token));
exit;
