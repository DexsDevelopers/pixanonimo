<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE transactions ADD COLUMN pix_code TEXT NULL AFTER pix_id");
    $pdo->exec("ALTER TABLE transactions ADD COLUMN qr_image TEXT NULL AFTER pix_code");
    echo "Migration successful: Added pix_code and qr_image to transactions table.\n";
} catch (PDOException $e) {
    echo "Migration failed or already applied: " . $e->getMessage() . "\n";
}
?>
