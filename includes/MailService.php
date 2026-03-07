<?php
/**
 * Ghost Pix — MailService
 * Gerencia o envio de e-mails transacionais via SMTP do Gmail.
 */

class MailService {
    /**
     * Envia um e-mail formatado.
     * Implementação robusta usando a função mail() do PHP como base, 
     * preparada para ser expandida para PHPMailer se necessário.
     */
    public static function send($to, $subject, $message) {
        if (empty(MAIL_USER) || empty(MAIL_PASS)) {
            write_log('WARNING', 'Envio de e-mail abortado: Credenciais não configuradas.');
            return false;
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . MAIL_FROM_NAME . ' <' . MAIL_USER . '>',
            'Reply-To: ' . MAIL_USER,
            'X-Mailer: PHP/' . phpversion()
        ];

        // Template básico de e-mail Luxury Ghost
        $htmlMessage = "
        <html>
        <body style='font-family: Arial, sans-serif; background-color: #000; color: #fff; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: #111; border: 1px solid #4ade80; border-radius: 15px; padding: 30px;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <h2 style='color: #4ade80; margin: 0;'>GHOST PIX</h2>
                </div>
                <div style='line-height: 1.6; font-size: 16px;'>
                    {$message}
                </div>
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #333; font-size: 12px; color: #777; text-align: center;'>
                    Este é um e-mail automático. Por favor, não responda.<br>
                    &copy; " . date('Y') . " Ghost Pix - Todos os direitos reservados.
                </div>
            </div>
        </body>
        </html>";

        try {
            $success = mail($to, $subject, $htmlMessage, implode("\r\n", $headers));
            if ($success) {
                write_log('INFO', 'E-mail enviado com sucesso', ['to' => $to, 'subject' => $subject]);
                return true;
            } else {
                write_log('ERROR', 'Falha ao enviar e-mail via mail()', ['to' => $to]);
                return false;
            }
        } catch (Exception $e) {
            write_log('ERROR', 'Exceção ao enviar e-mail', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public static function notifyApproval($userEmail, $userName) {
        $subject = "Sua conta Ghost Pix foi APROVADA! 🔥";
        $message = "
            <p>Olá, <strong>{$userName}</strong>!</p>
            <p>Temos ótimas notícias: sua conta foi verificada e <strong>aprovada</strong> pela nossa equipe.</p>
            <p>Você já pode acessar seu painel e começar a receber pagamentos com total blindagem e anonimato.</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='https://pixghost.site/auth/login.php' style='background: #4ade80; color: #000; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: bold;'>ACESSAR MEU PAINEL</a>
            </div>
        ";
        return self::send($userEmail, $subject, $message);
    }

    public static function notifySale($userEmail, $userName, $amount) {
        $val = number_format($amount, 2, ',', '.');
        $subject = "💰 Venda Confirmada: R$ {$val}";
        $message = "
            <p>Boas vendas, <strong>{$userName}</strong>!</p>
            <p>Um novo pagamento via PIX foi confirmado na sua conta.</p>
            <p style='font-size: 24px; color: #4ade80; font-weight: bold;'>R$ {$val}</p>
            <p>O saldo já foi creditado na sua carteira e está disponível para consulta no dashboard.</p>
        ";
        return self::send($userEmail, $subject, $message);
    }

    public static function notifyWithdrawalPaid($userEmail, $userName, $amount) {
        $val = number_format($amount, 2, ',', '.');
        $subject = "💸 Seu saque foi PAGO!";
        $message = "
            <p>Olá, <strong>{$userName}</strong>!</p>
            <p>Seu pedido de saque no valor de <strong>R$ {$val}</strong> foi processado e enviado com sucesso para sua chave PIX cadastrada.</p>
            <p>Obrigado por utilizar o sistema Ghost Pix!</p>
        ";
        return self::send($userEmail, $subject, $message);
    }
}
