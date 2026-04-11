<?php
/**
 * TelegramService - Envia notificações via Telegram Bot API
 */
class TelegramService
{
    private static function divider(): string { return "━━━━━━━━━━━━━━━━━━━━"; }
    private static function footer(): string  { return "\n" . self::divider() . "\n🤖 <i>Ghost Pix • " . date('d/m/Y \à\s H:i') . "</i>"; }

    private static function token(): string  { return defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : ''; }
    private static function chatId(): string { return defined('TELEGRAM_CHAT_ID')   ? TELEGRAM_CHAT_ID   : ''; }

    private static function api(string $method, array $payload): array
    {
        $token = self::token();
        if (!$token) return ['ok' => false];
        $ch = curl_init("https://api.telegram.org/bot{$token}/{$method}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        return json_decode($raw ?: '{}', true) ?: ['ok' => false];
    }

    public static function send(string $message, string $parseMode = 'HTML'): bool
    {
        $token  = self::token();
        $chatId = self::chatId();
        if (empty($token) || empty($chatId)) return false;

        $res = self::api('sendMessage', [
            'chat_id'                  => $chatId,
            'text'                     => $message,
            'parse_mode'               => $parseMode,
            'disable_web_page_preview' => true,
        ]);
        return !empty($res['ok']);
    }

    public static function sendWithKeyboard(string $message, array $keyboard, string $chatId = '', string $parseMode = 'HTML'): ?int
    {
        $chatId = $chatId ?: self::chatId();
        if (!self::token() || !$chatId) return null;

        $res = self::api('sendMessage', [
            'chat_id'                  => $chatId,
            'text'                     => $message,
            'parse_mode'               => $parseMode,
            'disable_web_page_preview' => true,
            'reply_markup'             => ['inline_keyboard' => $keyboard],
        ]);
        return $res['ok'] ? ($res['result']['message_id'] ?? null) : null;
    }

    public static function editMessageText(string $text, int $messageId, string $chatId = '', array $keyboard = []): bool
    {
        $chatId = $chatId ?: self::chatId();
        $payload = [
            'chat_id'                  => $chatId,
            'message_id'               => $messageId,
            'text'                     => $text,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true,
        ];
        if ($keyboard) $payload['reply_markup'] = ['inline_keyboard' => $keyboard];
        $res = self::api('editMessageText', $payload);
        return !empty($res['ok']);
    }

    public static function answerCallback(string $callbackQueryId, string $text = '', bool $alert = false): bool
    {
        $res = self::api('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text'              => $text,
            'show_alert'        => $alert,
        ]);
        return !empty($res['ok']);
    }

    public static function replyTo(string $chatId, string $message, string $parseMode = 'HTML'): bool
    {
        if (!self::token()) return false;
        $res = self::api('sendMessage', [
            'chat_id'                  => $chatId,
            'text'                     => $message,
            'parse_mode'               => $parseMode,
            'disable_web_page_preview' => true,
        ]);
        return !empty($res['ok']);
    }

    // ─── VENDA CONFIRMADA ────────────────────────────────────────────
    public static function notifySale(
        float $amount, float $netAmount, string $customerName,
        string $merchantName, int $transactionId, string $source = 'PIX'
    ): bool {
        $gross    = number_format($amount,    2, ',', '.');
        $net      = number_format($netAmount, 2, ',', '.');
        $fee      = number_format($amount - $netAmount, 2, ',', '.');
        $msg =
            "� <b>VENDA CONFIRMADA</b>\n" . self::divider() . "\n\n"
          . "💵 <b>Valor Bruto:</b>   R$ {$gross}\n"
          . "💎 <b>Valor Líquido:</b> R$ {$net}\n"
          . "📉 <b>Taxa Total:</b>    R$ {$fee}\n\n"
          . "👤 <b>Pagador:</b>    {$customerName}\n"
          . "🏪 <b>Vendedor:</b>   {$merchantName}\n"
          . "🔗 <b>Origem:</b>     {$source}\n"
          . "🆔 <b>TX:</b>         <code>#{$transactionId}</code>"
          . self::footer();
        return self::send($msg);
    }

    // ─── NOVA COBRANÇA GERADA ────────────────────────────────────────
    public static function notifyNewCharge(
        float $amount, string $merchantName, int $transactionId,
        string $customerName = '', string $source = 'PIX'
    ): bool {
        $amountFmt = number_format($amount, 2, ',', '.');
        $payerLine = $customerName ? "\n👤 <b>Pagador:</b>  {$customerName}" : '';
        $msg =
            "⚡ <b>NOVA COBRANÇA GERADA</b>\n" . self::divider() . "\n\n"
          . "💵 <b>Valor:</b>    R$ {$amountFmt}"
          . $payerLine . "\n"
          . "🏪 <b>Vendedor:</b> {$merchantName}\n"
          . "🔗 <b>Origem:</b>   {$source}\n"
          . "🆔 <b>TX:</b>       <code>#{$transactionId}</code>"
          . self::footer();
        return self::send($msg);
    }

    // ─── NOVO CADASTRO ───────────────────────────────────────────────
    public static function notifyNewUser(string $name, string $email, string $ip = ''): bool
    {
        $ipLine = $ip ? "\n🌐 <b>IP:</b>      <code>{$ip}</code>" : '';
        $msg =
            "🆕 <b>NOVO CADASTRO</b>\n" . self::divider() . "\n\n"
          . "� <b>Nome:</b>    {$name}\n"
          . "📧 <b>E-mail:</b>  <code>{$email}</code>"
          . $ipLine
          . self::footer();
        return self::send($msg);
    }

    // ─── SAQUE SOLICITADO ────────────────────────────────────────────
    public static function notifyWithdrawal(string $userName, float $amount, string $pixKey, float $fee = 3.50): bool
    {
        $gross = number_format($amount, 2, ',', '.');
        $net   = number_format($amount - $fee, 2, ',', '.');
        $feeFmt= number_format($fee, 2, ',', '.');
        $msg =
            "🏦 <b>SAQUE SOLICITADO</b>\n" . self::divider() . "\n\n"
          . "👤 <b>Usuário:</b>       {$userName}\n"
          . "💵 <b>Valor Solicitado:</b> R$ {$gross}\n"
          . "📉 <b>Taxa de Saque:</b>    R$ {$feeFmt}\n"
          . "💎 <b>Valor a Receber:</b>  R$ {$net}\n"
          . "🔑 <b>Chave PIX:</b>   <code>{$pixKey}</code>\n\n"
          . "⚠️ <i>Aguardando aprovação manual.</i>"
          . self::footer();
        return self::send($msg);
    }

    // ─── SAQUE APROVADO ──────────────────────────────────────────────
    public static function notifyWithdrawalApproved(string $userName, float $amount, string $pixKey, string $txHash = ''): bool
    {
        $amountFmt = number_format($amount, 2, ',', '.');
        $hashLine  = $txHash ? "\n🧾 <b>Hash/Ref:</b> <code>{$txHash}</code>" : '';
        $msg =
            "✅ <b>SAQUE APROVADO</b>\n" . self::divider() . "\n\n"
          . "👤 <b>Usuário:</b> {$userName}\n"
          . "💸 <b>Valor:</b>   R$ {$amountFmt}\n"
          . "🔑 <b>Pix:</b>     <code>{$pixKey}</code>"
          . $hashLine
          . self::footer();
        return self::send($msg);
    }

    // ─── SAQUE REJEITADO ─────────────────────────────────────────────
    public static function notifyWithdrawalRejected(string $userName, float $amount): bool
    {
        $amountFmt = number_format($amount, 2, ',', '.');
        $msg =
            "❌ <b>SAQUE REJEITADO</b>\n" . self::divider() . "\n\n"
          . "👤 <b>Usuário:</b> {$userName}\n"
          . "💵 <b>Valor:</b>   R$ {$amountFmt}\n\n"
          . "ℹ️ <i>O saldo foi devolvido ao usuário automaticamente.</i>"
          . self::footer();
        return self::send($msg);
    }

    // ─── USUÁRIO APROVADO / BLOQUEADO ────────────────────────────────
    public static function notifyUserStatusChanged(string $userName, string $email, string $newStatus): bool
    {
        $icons  = ['approved' => '✅', 'blocked' => '🔴', 'pending' => '🕐', 'demo' => '🎭'];
        $labels = ['approved' => 'APROVADO',  'blocked' => 'BLOQUEADO', 'pending' => 'PENDENTE', 'demo' => 'DEMO'];
        $icon   = $icons[$newStatus]  ?? '🔄';
        $label  = $labels[$newStatus] ?? strtoupper($newStatus);
        $msg =
            "{$icon} <b>USUÁRIO {$label}</b>\n" . self::divider() . "\n\n"
          . "👤 <b>Nome:</b>   {$userName}\n"
          . "� <b>E-mail:</b> <code>{$email}</code>\n"
          . "🔖 <b>Status:</b> {$label}"
          . self::footer();
        return self::send($msg);
    }

    // ─── NOVO PRODUTO CRIADO (com botões aprovar/recusar) ───────────
    public static function notifyNewProduct(string $sellerName, string $productName, float $price, string $category = ''): bool
    {
        return (bool) self::notifyNewProductAdmin($sellerName, $productName, $price, 0, $category);
    }

    public static function notifyNewProductAdmin(
        string $sellerName, string $productName, float $price,
        int $productId = 0, string $category = ''
    ): ?int {
        $priceFmt = number_format($price, 2, ',', '.');
        $catLine  = $category ? "\n🏷️ <b>Categoria:</b> {$category}" : '';
        $idLine   = $productId ? "\n🆔 <b>ID:</b>        <code>#{$productId}</code>" : '';
        $msg =
            "🛍️ <b>NOVO PRODUTO CADASTRADO</b>\n" . self::divider() . "\n\n"
          . "📦 <b>Produto:</b>  {$productName}\n"
          . "💵 <b>Preço:</b>    R$ {$priceFmt}\n"
          . "🏪 <b>Vendedor:</b> {$sellerName}"
          . $catLine
          . $idLine . "\n\n"
          . "⚠️ <i>Aguardando revisão. Use os botões abaixo ou o painel.</i>"
          . self::footer();

        $keyboard = $productId ? [[
            ['text' => '✅ Aprovar', 'callback_data' => "prod_approve_{$productId}"],
            ['text' => '❌ Recusar', 'callback_data' => "prod_reject_{$productId}"],
        ]] : [];

        return $keyboard
            ? self::sendWithKeyboard($msg, $keyboard)
            : (self::send($msg) ? 1 : null);
    }

    // ─── PRODUTO APROVADO / RECUSADO ─────────────────────────────────
    public static function notifyProductStatus(int $productId, string $productName, string $sellerName, string $status, string $reason = ''): bool
    {
        $icon   = $status === 'active' ? '✅' : '❌';
        $label  = $status === 'active' ? 'APROVADO' : 'RECUSADO';
        $rLine  = $reason ? "\n💬 <b>Motivo:</b> {$reason}" : '';
        $msg =
            "{$icon} <b>PRODUTO {$label}</b>\n" . self::divider() . "\n\n"
          . "📦 <b>Produto:</b>  {$productName}\n"
          . "🏪 <b>Vendedor:</b> {$sellerName}\n"
          . "🆔 <b>ID:</b>       <code>#{$productId}</code>"
          . $rLine
          . self::footer();
        return self::send($msg);
    }

    // ─── PIX EXPIRADO (alto valor) ───────────────────────────────────
    public static function notifyPixExpired(float $amount, string $merchantName, int $transactionId): bool
    {
        if ($amount < 50) return false;
        $amountFmt = number_format($amount, 2, ',', '.');
        $msg =
            "⏰ <b>PIX EXPIRADO SEM PAGAMENTO</b>\n" . self::divider() . "\n\n"
          . "💵 <b>Valor:</b>    R$ {$amountFmt}\n"
          . "🏪 <b>Vendedor:</b> {$merchantName}\n"
          . "🆔 <b>TX:</b>       <code>#{$transactionId}</code>"
          . self::footer();
        return self::send($msg);
    }

    // ─── ALERTA DE ERRO / SISTEMA ────────────────────────────────────
    public static function notifySystemAlert(string $title, string $detail): bool
    {
        $msg =
            "🚨 <b>ALERTA DO SISTEMA</b>\n" . self::divider() . "\n\n"
          . "⚠️ <b>{$title}</b>\n\n"
          . "<code>{$detail}</code>"
          . self::footer();
        return self::send($msg);
    }
}
