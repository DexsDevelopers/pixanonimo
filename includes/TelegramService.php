<?php
/**
 * TelegramService - Envia notificações via Telegram Bot API
 */
class TelegramService
{
    private static function divider(): string { return "━━━━━━━━━━━━━━━━━━━━"; }
    private static function footer(): string  { return "\n" . self::divider() . "\n🤖 <i>Ghost Pix • " . date('d/m/Y \à\s H:i') . "</i>"; }

    public static function send(string $message, string $parseMode = 'HTML'): bool
    {
        $token  = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
        $chatId = defined('TELEGRAM_CHAT_ID')   ? TELEGRAM_CHAT_ID   : '';

        if (empty($token) || empty($chatId)) return false;

        $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'chat_id'                  => $chatId,
                'text'                     => $message,
                'parse_mode'               => $parseMode,
                'disable_web_page_preview' => true,
            ]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_exec($ch);
        curl_close($ch);
        return $httpCode === 200;
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

    // ─── NOVO PRODUTO CRIADO ─────────────────────────────────────────
    public static function notifyNewProduct(string $sellerName, string $productName, float $price, string $category = ''): bool
    {
        $priceFmt = number_format($price, 2, ',', '.');
        $catLine  = $category ? "\n🏷️ <b>Categoria:</b> {$category}" : '';
        $msg =
            "🛍️ <b>NOVO PRODUTO CADASTRADO</b>\n" . self::divider() . "\n\n"
          . "📦 <b>Produto:</b>  {$productName}\n"
          . "💵 <b>Preço:</b>    R$ {$priceFmt}\n"
          . "🏪 <b>Vendedor:</b> {$sellerName}"
          . $catLine
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
