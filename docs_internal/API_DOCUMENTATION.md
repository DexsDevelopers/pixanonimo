# Documentação da API Ghost Pix

O Ghost Pix pode ser integrado ao seu sistema para processar pagamentos Pix com total anonimato e blindagem.

## 1. Gerar Cobrança (Pix)

**Endpoint:** `POST /api.php`  
**Autenticação:** Sessão de Usuário Ativa (ou API Key configurada via Header futuramente).

### Parâmetros (JSON)
| Campo | Tipo | Descrição |
| :--- | :--- | :--- |
| `amount` | float | Valor da cobrança (Mínimo R$ 10,00) |

### Resposta de Sucesso
```json
{
    "status": "success",
    "pix_id": "px_12345...",
    "pix_code": "00020126...",
    "amount": 50.00,
    "qr_image": "https://api.pixgo.org/..."
}
```

## 2. Receber Webhooks

Configure o seu endpoint de Webhook no painel da PixGo (ou repasse o recebimento do `webhook.php` interno).

### Formato do Payload (PixGo V1)
```json
{
    "event": "payment.completed",
    "data": {
        "payment_id": "px_12345",
        "status": "completed",
        "amount": 50.00
    }
}
```

## 3. Consultar Status

**Endpoint:** `GET /check_status.php?pix_id={ID_DA_TRANSACAO}`

### Resposta
```json
{
    "status": "paid" | "pending"
}
```

---
> [!IMPORTANT]
> A blindagem financeira é garantida através da nossa estrutura de liquidação. O CPF/CNPJ do recebedor final nunca é exposto ao pagador.
