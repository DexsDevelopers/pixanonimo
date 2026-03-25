<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];

// Verificar constantes VAPID
if (!defined('VAPID_PUBLIC_KEY') || !defined('VAPID_PRIVATE_KEY') || !defined('VAPID_SUBJECT')) {
    echo json_encode([
        'success' => false, 
        'error' => 'Constantes VAPID não definidas no config.php do servidor. Adicione: VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY, VAPID_SUBJECT'
    ]);
    exit;
}

try {
    // 1. Verificar se existem subscriptions
    try {
        $stmt = $pdo->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $subs = $stmt->fetchAll();
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => 'Tabela push_subscriptions não existe. Ative as notificações primeiro.']);
        exit;
    }

    if (empty($subs)) {
        echo json_encode(['success' => false, 'error' => 'Nenhuma subscription encontrada. Ative as notificações primeiro (limpe o cache e recarregue).']);
        exit;
    }

    // 2. Tentar enviar push direto
    $autoloadFile = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($autoloadFile)) {
        echo json_encode(['success' => false, 'error' => 'Biblioteca WebPush não instalada (vendor/autoload.php ausente).']);
        exit;
    }
    require_once $autoloadFile;

    if (!class_exists('\Minishlink\WebPush\WebPush')) {
        echo json_encode(['success' => false, 'error' => 'Classe WebPush não encontrada. Execute: composer require minishlink/web-push']);
        exit;
    }

    $auth = [
        'VAPID' => [
            'subject' => VAPID_SUBJECT,
            'publicKey' => VAPID_PUBLIC_KEY,
            'privateKey' => VAPID_PRIVATE_KEY,
        ],
    ];

    $webPush = new \Minishlink\WebPush\WebPush($auth);

    $payload = json_encode([
        'title' => '🔔 Teste de Notificação',
        'body' => 'Suas notificações estão funcionando! Você receberá alertas em tempo real.',
        'icon' => '/logo_premium.png',
        'badge' => '/logo_premium.png',
        'data' => ['url' => '/dashboard']
    ]);

    $sent = 0;
    $errors = [];
    $skipped = 0;

    foreach ($subs as $sub) {
        if (empty($sub['p256dh']) || empty($sub['auth']) || $sub['endpoint'] === 'browser_native') {
            $skipped++;
            continue;
        }

        try {
            $report = $webPush->sendOneNotification(
                \Minishlink\WebPush\Subscription::create([
                    'endpoint' => $sub['endpoint'],
                    'publicKey' => $sub['p256dh'],
                    'authToken' => $sub['auth'],
                ]),
                $payload
            );

            if ($report->isSuccess()) {
                $sent++;
            } else {
                $errors[] = $report->getReason();
                // Remover subscription inválida (410 Gone = expirada)
                if ($report->isSubscriptionExpired()) {
                    $del = $pdo->prepare("DELETE FROM push_subscriptions WHERE id = ?");
                    $del->execute([$sub['id']]);
                }
            }
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    if ($sent > 0) {
        echo json_encode(['success' => true, 'sent' => $sent]);
    } else {
        $detail = count($subs) . ' subscription(s), ' . $skipped . ' com chaves vazias';
        if ($skipped === count($subs)) {
            // Limpar subscriptions inválidas
            $del = $pdo->prepare("DELETE FROM push_subscriptions WHERE user_id = ? AND (p256dh = '' OR p256dh IS NULL OR auth = '' OR auth IS NULL)");
            $del->execute([$userId]);
            $errorMsg = 'Suas subscriptions estão com chaves vazias (problema antigo). Foram limpas. Clique em "Reativar" para se inscrever novamente.';
        } else {
            $errorMsg = !empty($errors) ? implode('; ', array_slice($errors, 0, 2)) : 'Nenhum push enviado';
        }
        echo json_encode(['success' => false, 'error' => $errorMsg, 'detail' => $detail, 'needsResubscribe' => ($skipped === count($subs))]);
    }

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Erro: ' . $e->getMessage()]);
}
