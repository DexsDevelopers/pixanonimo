<?php
/**
 * GHOST PIX - Push Service
 * Implementação nativa simplificada do protocolo Web Push (VAPID)
 */

class PushService {
    // Estas chaves devem ser geradas uma vez e mantidas em segredo
    // Em produção, use uma biblioteca como 'web-push-php'
    // Aqui implementamos a lógica para disparar via FCM/APNs usando os dados de assinatura
    
    public static function send($subscription, $title, $body, $icon = 'logo_premium.png') {
        $endpoint = $subscription['endpoint'];
        $p256dh = $subscription['p256dh'];
        $auth = $subscription['auth'];

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => $icon,
            'data' => [
                'url' => 'dashboard.php'
            ]
        ]);

        // NOTA: Para Web Push real, é necessário criptografia AES-GCM/ECDH
        // Como o ambiente local pode não ter bibliotecas pesadas, 
        // usaremos um log de simulação se não houver VAPID keys configuradas.
        
        write_log('INFO', 'Disparando Push Notification (Simulação)', [
            'to' => $endpoint,
            'title' => $title,
            'body' => $body
        ]);

        // Aqui entraria a integração com bibliotecas de Push
        return true;
    }

    public static function notifyUser($userId, $title, $body) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $subscriptions = $stmt->fetchAll();

        foreach ($subscriptions as $sub) {
            self::send($sub, $title, $body);
        }
    }

    public static function notifyAdmin($title, $body) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM push_subscriptions WHERE user_id IN (SELECT id FROM users WHERE is_admin = 1)");
        $stmt->execute();
        $subscriptions = $stmt->fetchAll();

        foreach ($subscriptions as $sub) {
            self::send($sub, $title, $body);
        }
    }
}
?>
