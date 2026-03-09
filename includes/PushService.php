<?php
/**
 * GHOST PIX - Push Service
 * Implementação nativa simplificada do protocolo Web Push (VAPID)
 */

class PushService {
    
    private static function ensureTableExists() {
        global $pdo;
        try {
            $pdo->query("SELECT 1 FROM push_subscriptions LIMIT 1");
        } catch (Exception $e) {
            // Se a tabela não existe, tenta criar
            $sql = "CREATE TABLE IF NOT EXISTS push_subscriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                endpoint TEXT NOT NULL,
                p256dh VARCHAR(255) NOT NULL,
                auth VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $pdo->exec($sql);
        }
    }

    public static function send($subscription, $title, $body, $icon = 'logo_premium.png') {
        try {
            $endpoint = $subscription['endpoint'];
            $p256dh = $subscription['p256dh'];
            $auth = $subscription['auth'];

            if ($endpoint === 'browser_native' || empty($p256dh)) {
                write_log('INFO', 'Inscrição push incompleta ou legado.');
                return false;
            }

            // Configuração VAPID
            $auth_config = [
                'VAPID' => [
                    'subject' => VAPID_SUBJECT,
                    'publicKey' => VAPID_PUBLIC_KEY,
                    'privateKey' => VAPID_PRIVATE_KEY,
                ],
            ];

            $webPush = new \Minishlink\WebPush\WebPush($auth_config);

            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'icon' => 'assets/' . $icon,
                'badge' => 'assets/logo_premium.png',
                'data' => [
                    'url' => 'dashboard.php'
                ]
            ]);

            $report = $webPush->sendOneNotification(
                \Minishlink\WebPush\Subscription::create([
                    'endpoint' => $endpoint,
                    'publicKey' => $p256dh,
                    'authToken' => $auth,
                ]),
                $payload
            );

            if ($report->isSuccess()) {
                write_log('INFO', 'Push Real Enviado com Sucesso', ['endpoint' => $endpoint]);
                return true;
            } else {
                write_log('ERROR', 'Falha ao enviar Push Real', ['reason' => $report->getReason()]);
                return false;
            }
        } catch (Exception $e) {
            write_log('ERROR', 'Erro Crítico no PushService', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public static function notifyUser($userId, $title, $body) {
        global $pdo;
        try {
            self::ensureTableExists();
            $stmt = $pdo->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $subscriptions = $stmt->fetchAll();

            foreach ($subscriptions as $sub) {
                self::send($sub, $title, $body);
            }
        } catch (Exception $e) {
            write_log('ERROR', 'Erro ao processar notifyUser Push', ['error' => $e->getMessage()]);
        }
    }

    public static function notifyAll($title, $body) {
        global $pdo;
        try {
            self::ensureTableExists();
            $stmt = $pdo->query("SELECT * FROM push_subscriptions");
            $subscriptions = $stmt->fetchAll();

            foreach ($subscriptions as $sub) {
                self::send($sub, $title, $body);
            }
        } catch (Exception $e) {
            write_log('ERROR', 'Erro ao processar notifyAll Push', ['error' => $e->getMessage()]);
        }
    }
}
?>
