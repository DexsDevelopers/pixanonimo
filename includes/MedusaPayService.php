<?php
/**
 * MedusaPay (HopySplit) API Client
 * Auth: Basic base64(secret_key:x)
 * Card checkout: POST /v1/checkouts → returns redirect URL
 */
class MedusaPayService
{
    private static string $baseUrl = 'https://api.v2.medusapay.com.br';

    private static function getSecretKey(): string
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT `value` FROM settings WHERE `key` = 'medusapay_secret_key' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? trim((string)$row['value']) : '';
    }

    private static function authHeader(string $secretKey): string
    {
        if ($secretKey === '') return '';
        return 'Basic ' . base64_encode($secretKey . ':x');
    }

    public static function request(string $method, string $path, ?array $payload = null): array
    {
        $secret = self::getSecretKey();
        if ($secret === '') {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'MedusaPay secret key não configurada.'];
        }

        $url = rtrim(self::$baseUrl, '/') . '/' . ltrim($path, '/');
        $method = strtoupper($method);

        $headers = [
            'Authorization: ' . self::authHeader($secret),
            'Accept: application/json',
        ];

        $body = null;
        if ($payload !== null) {
            $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headers[] = 'Content-Type: application/json';
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw    = (string)curl_exec($ch);
        $err    = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === '' && $err) {
            return ['ok' => false, 'status' => $status, 'data' => null, 'error' => 'cURL: ' . $err];
        }

        $data = json_decode($raw, true);
        $ok   = ($status >= 200 && $status < 300);

        if (!$ok) {
            $msg = '';
            if (is_array($data)) {
                $msg = (string)($data['message'] ?? $data['error'] ?? '');
                if ($msg === '' && isset($data['errors']) && is_array($data['errors'])) {
                    $msg = json_encode($data['errors'], JSON_UNESCAPED_UNICODE);
                }
            }
            if ($msg === '') $msg = 'Erro MedusaPay (HTTP ' . $status . ')';
            return ['ok' => false, 'status' => $status, 'data' => $data, 'error' => $msg];
        }

        return ['ok' => true, 'status' => $status, 'data' => $data, 'error' => ''];
    }

    /**
     * Create a card checkout link
     */
    public static function createCardCheckout(float $amountBrl, string $productName, string $postbackUrl): array
    {
        $amountCents = (int)round($amountBrl * 100);
        if ($amountCents < 500) {
            return ['ok' => false, 'checkout_url' => '', 'reference' => '', 'error' => 'Valor mínimo para cartão: R$ 5,00'];
        }

        $payload = [
            'amount'      => $amountCents,
            'postbackUrl' => $postbackUrl,
            'items'       => [[
                'title'     => $productName,
                'unitPrice' => $amountCents,
                'quantity'  => 1,
                'tangible'  => false,
            ]],
            'settings' => [
                'defaultPaymentMethod' => 'credit_card',
                'requestAddress'       => false,
                'requestPhone'         => false,
                'requestDocument'      => true,
                'traceable'            => false,
                'card'   => ['enabled' => true, 'maxInstallments' => 12, 'freeInstallments' => 1],
                'pix'    => ['enabled' => false, 'expiresInDays' => 1],
                'boleto' => ['enabled' => false, 'expiresInDays' => 1],
            ],
        ];

        $resp = self::request('POST', '/v1/checkouts', $payload);

        if (!$resp['ok']) {
            return ['ok' => false, 'checkout_url' => '', 'reference' => '', 'error' => $resp['error']];
        }

        $data = is_array($resp['data']) ? $resp['data'] : [];

        $reference = '';
        foreach (['id', 'checkoutId', 'checkout_id'] as $k) {
            if (!empty($data[$k])) { $reference = (string)$data[$k]; break; }
        }

        $urlKeys = ['url', 'checkoutUrl', 'redirectUrl', 'checkout_url', 'paymentUrl', 'payment_url', 'link', 'shortUrl'];
        $searchUrl = function(array $arr) use (&$searchUrl, $urlKeys): string {
            foreach ($urlKeys as $k) {
                if (!empty($arr[$k]) && is_string($arr[$k]) && strncmp($arr[$k], 'http', 4) === 0) {
                    return $arr[$k];
                }
            }
            foreach ($arr as $v) {
                if (is_array($v)) {
                    $found = $searchUrl($v);
                    if ($found !== '') return $found;
                }
                if (is_string($v) && strncmp($v, 'https://', 8) === 0 && (str_contains($v, 'checkout') || str_contains($v, 'payment'))) {
                    return $v;
                }
            }
            return '';
        };
        $checkoutUrl = $searchUrl($data);

        if ($checkoutUrl === '') {
            $rawPreview = mb_substr(json_encode($data, JSON_UNESCAPED_UNICODE), 0, 400);
            return ['ok' => false, 'checkout_url' => '', 'reference' => $reference, 'error' => 'URL não encontrada. Resposta: ' . $rawPreview];
        }

        return ['ok' => true, 'checkout_url' => $checkoutUrl, 'reference' => $reference, 'error' => ''];
    }
}
