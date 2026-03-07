<?php
/**
 * Ghost Pix — MailService
 * Gerencia o envio de e-mails transacionais via SMTP do Gmail.
 */

class MailService {
    /**
     * Envia um e-mail formatado.
     */
    public static function send($to, $subject, $message) {
        if (!defined('MAIL_USER') || !defined('MAIL_PASS') || empty(MAIL_USER) || empty(MAIL_PASS)) {
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

    private static function getTemplate($slug, $replacements = []) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT subject, message FROM email_templates WHERE slug = ?");
            $stmt->execute([$slug]);
            $template = $stmt->fetch();
            
            if (!$template) return null;

            $subject = $template['subject'];
            $message = $template['message'];

            foreach ($replacements as $key => $value) {
                // Converter quebras de linha simples em <br> para campos de texto
                $val = ($key === 'message' || $key === 'reason') ? nl2br(trim($value)) : $value;
                $subject = str_replace("{{$key}}", $val, $subject);
                $message = str_replace("{{$key}}", $val, $message);
            }

            return ['subject' => $subject, 'message' => $message];
        } catch (Exception $e) {
            write_log('ERROR', 'Erro ao carregar template de e-mail', ['slug' => $slug, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public static function notifyApproval($userEmail, $userName) {
        $tpl = self::getTemplate('account_approved', ['name' => $userName]);
        if (!$tpl) return false;
        return self::send($userEmail, $tpl['subject'], $tpl['message']);
    }

    public static function notifySale($userEmail, $userName, $amount) {
        $val = number_format($amount, 2, ',', '.');
        $tpl = self::getTemplate('sale_confirmed', ['name' => $userName, 'amount' => $val]);
        if (!$tpl) return false;
        return self::send($userEmail, $tpl['subject'], $tpl['message']);
    }

    public static function notifyWithdrawalPaid($userEmail, $userName, $amount) {
        $val = number_format($amount, 2, ',', '.');
        $tpl = self::getTemplate('withdrawal_paid', ['name' => $userName, 'amount' => $val]);
        if (!$tpl) return false;
        return self::send($userEmail, $tpl['subject'], $tpl['message']);
    }

    public static function notifyGlobal($userEmail, $userName, $title, $message) {
        $tpl = self::getTemplate('global_announcement', [
            'name' => $userName, 
            'title' => $title, 
            'message' => $message
        ]);
        if (!$tpl) return false;
        return self::send($userEmail, $tpl['subject'], $tpl['message']);
    }
}
