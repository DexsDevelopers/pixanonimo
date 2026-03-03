<?php
/**
 * GHOST PIX - Central de Notificações
 * Skill: @telegram-bot-builder
 */

function send_telegram_notification($message) {
    // Configurações do Bot (Podem ser movidas para o config.php)
    $bot_token = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
    $chat_id = defined('TELEGRAM_CHAT_ID') ? TELEGRAM_CHAT_ID : '';

    if (empty($bot_token) || empty($chat_id)) {
        write_log('WARNING', 'Tentativa de envio Telegram sem configuração', ['message' => $message]);
        return false;
    }

    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => "👻 *Ghost Pix Notification*\n\n" . $message,
        'parse_mode' => 'Markdown'
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) {
        write_log('ERROR', 'Falha ao enviar notificação Telegram', ['message' => $message]);
        return false;
    }

    return true;
}

/**
 * Envia notificação sobre novos pagamentos
 */
function notify_new_payment($amount, $user_id) {
    $msg = "💰 *Novo Pagamento Confirmado!*\n";
    $msg .= "----------------------------\n";
    $msg .= "💵 Valor: R$ " . number_format($amount, 2, ',', '.') . "\n";
    $msg .= "👤 User ID: #{$user_id}\n";
    $msg .= "⚡ Status: Liquidado";
    
    return send_telegram_notification($msg);
}

/**
 * Envia notificação sobre novo pedido de saque
 */
function notify_new_withdrawal($amount, $user_id) {
    $msg = "💸 *Novo Pedido de Saque!*\n";
    $msg .= "----------------------------\n";
    $msg .= "💵 Valor: R$ " . number_format($amount, 2, ',', '.') . "\n";
    $msg .= "👤 User ID: #{$user_id}\n";
    $msg .= "⏳ Status: Pendente de Aprovação";
    
    return send_telegram_notification($msg);
}
?>

