# Roadmap: Checkout Transparente Ghost Pix 🚀

O objetivo é permitir que o lojista gere pagamentos PIX diretamente em seu site sem redirecionamentos, utilizando a infraestrutura do Ghost Pix como middleware de blindagem.

## 🏗️ Arquitetura Proposta

1.  **Frontend Hook**: Um pequeno script JS (`ghost-checkout.js`) que o lojista include no site.
2.  **API Silenciosa**: Endpoint no Ghost Pix que processa a criação da cobrança e retorna o QR Code/Copia e Cola.
3.  **Real-time Status**: Uso do `check_status.php` via polling ou WebSockets (futuro) para atualizar a tela do cliente assim que o pagamento for detectado.

## 📅 Fases de Implementação

### Fase 1: Endpoint de Domínio Permitido (Segurança)
- [ ] Criar tabela `merchant_domains` no banco de dados.
- [ ] Validar o header `Origin` nas chamadas da API para evitar uso não autorizado.

### Fase 2: Componente UI Embeddable
- [ ] Desenvolver um modal minimalista em Vanilla JS que possa ser injetado em qualquer site.
- [ ] Estilizar o modal para ser neutro e herdar fontes do site pai.

### Fase 3: Gateway de Notificação (Callback)
- [ ] Melhorar o suporte a `callback_url` no `webhook.php` para garantir retentativas em caso de falha no site do lojista.

---
> [!TIP]
> O segredo da conversão no Checkout Transparente é a velocidade: O QR Code deve aparecer em menos de 1 segundo após o clique do cliente.
