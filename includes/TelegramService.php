<?php
/**
 * TelegramService - Envia notificações via Telegram Bot API
 */
class TelegramService
{
    /**
     * Envia mensagem para o chat do admin
     */
    public static function send(string $message, string $parseMode = 'HTML'): bool
    {
        $token = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
        $chatId = defined('TELEGRAM_CHAT_ID') ? TELEGRAM_CHAT_ID : '';

        if (empty($token) || empty($chatId)) {
            return false;
        }

        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        $payload = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => true
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * Notifica venda confirmada
     */
    public static function notifySale(float $amount, string $customerName, string $merchantName, int $transactionId): bool
    {
        $amountFmt = number_format($amount, 2, ',', '.');
        $msg = "💰 <b>Venda Confirmada!</b>\n\n"
             . "💵 Valor: <b>R$ {$amountFmt}</b>\n"
             . "👤 Pagador: {$customerName}\n"
             . "🏪 Lojista: {$merchantName}\n"
             . "🔢 TX: #{$transactionId}\n"
             . "⏰ " . date('d/m/Y H:i:s');
        return self::send($msg);
    }

    /**
     * Notifica nova cobrança gerada
     */
    public static function notifyNewCharge(float $amount, string $merchantName, int $transactionId): bool
    {
        $amountFmt = number_format($amount, 2, ',', '.');
        $msg = "⚡ <b>Nova Cobrança Gerada</b>\n\n"
             . "💵 Valor: <b>R$ {$amountFmt}</b>\n"
             . "🏪 Lojista: {$merchantName}\n"
             . "🔢 TX: #{$transactionId}\n"
             . "⏰ " . date('d/m/Y H:i:s');
        return self::send($msg);
    }

    /**
     * Notifica novo cadastro
     */
    public static function notifyNewUser(string $name, string $email): bool
    {
        $msg = "👤 <b>Novo Cadastro!</b>\n\n"
             . "📛 Nome: {$name}\n"
             . "📧 Email: {$email}\n"
             . "⏰ " . date('d/m/Y H:i:s');
        return self::send($msg);
    }

    /**
     * Notifica solicitação de saque
     */
    public static function notifyWithdrawal(string $userName, float $amount, string $pixKey): bool
    {
        $amountFmt = number_format($amount, 2, ',', '.');
        $msg = "🏦 <b>Saque Solicitado!</b>\n\n"
             . "👤 Usuário: {$userName}\n"
             . "💵 Valor: <b>R$ {$amountFmt}</b>\n"
             . "🔑 Pix: <code>{$pixKey}</code>\n"
             . "⏰ " . date('d/m/Y H:i:s');
        return self::send($msg);
    }
}
