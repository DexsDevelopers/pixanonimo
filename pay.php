<?php
require_once 'includes/db.php';

$slug = $_GET['c'] ?? '';

if (empty($slug)) {
    die("Página não encontrada.");
}

// Buscar o checkout
$stmt = $pdo->prepare("SELECT * FROM checkouts WHERE slug = ? AND active = 1");
$stmt->execute([$slug]);
$checkout = $stmt->fetch();

if (!$checkout) {
    die("Página não encontrada ou desativada.");
}

$checkoutId = $checkout['id'];

// Buscar os itens
$stmt = $pdo->prepare("SELECT * FROM checkout_items WHERE checkout_id = ?");
$stmt->execute([$checkoutId]);
$items = $stmt->fetchAll();

$total = 0;
foreach ($items as $it) {
    $total += $it['price'];
}

// Primary e Secondary Colors
$primary = $checkout['primary_color'];
$secondary = $checkout['secondary_color'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title><?php echo htmlspecialchars($checkout['title']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: <?php echo $primary; ?>;
            --secondary: <?php echo $secondary; ?>;
            --bg-color: #08080a;
            --text-main: #ffffff;
            --text-muted: #9ca3af;
            --border: rgba(255, 255, 255, 0.1);
            --card-bg: rgba(<?php echo hexdec(substr($secondary, 1, 2)); ?>, <?php echo hexdec(substr($secondary, 3, 2)); ?>, <?php echo hexdec(substr($secondary, 5, 2)); ?>, 0.4);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(<?php echo hexdec(substr($primary, 1, 2)); ?>, <?php echo hexdec(substr($primary, 3, 2)); ?>, <?php echo hexdec(substr($primary, 5, 2)); ?>, 0.15), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(<?php echo hexdec(substr($primary, 1, 2)); ?>, <?php echo hexdec(substr($primary, 3, 2)); ?>, <?php echo hexdec(substr($primary, 5, 2)); ?>, 0.15), transparent 25%);
        }

        .checkout-container {
            width: 100%;
            max-width: 900px;
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 2rem;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
                padding: 1.5rem;
                gap: 1.5rem;
            }
        }
        
        .checkout-wrapper {
            width: 100%;
            max-width: 900px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .checkout-banner {
            width: 100%;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border);
        }
        
        .checkout-banner img {
            width: 100%;
            display: block;
            object-fit: cover;
            max-height: 250px;
        }

        .order-summary {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .order-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .items-list {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .item-name {
            font-weight: 500;
            color: var(--text-main);
        }

        .item-price {
            font-weight: 600;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
            font-size: 1.2rem;
            font-weight: 700;
        }

        .total-price {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .payment-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .input-label {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .input-field {
            width: 100%;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.03);
            color: white;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .input-field:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.06);
            box-shadow: 0 0 0 4px rgba(<?php echo hexdec(substr($primary, 1, 2)); ?>, <?php echo hexdec(substr($primary, 3, 2)); ?>, <?php echo hexdec(substr($primary, 5, 2)); ?>, 0.1);
        }

        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--text-muted);
            font-size: 0.85rem;
            margin: 10px 0;
        }

        .btn-pay {
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            border: none;
            background: var(--primary);
            color: #000;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -10px var(--primary);
        }

        .btn-pay:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .pix-logo {
            height: 24px;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s;
            padding: 20px;
        }
        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-content {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            width: 100%;
            max-width: 420px;
            padding: 2rem;
            text-align: center;
            transform: translateY(20px);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }

        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }

        .qr-wrapper {
            background: white;
            padding: 15px;
            border-radius: 16px;
            display: inline-block;
            margin: 1.5rem 0;
            width: 250px;
            height: 250px;
        }

        .qr-wrapper img {
            width: 100%;
            height: 100%;
            display: block;
        }

        .copy-group {
            display: flex;
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .copy-input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-muted);
            padding: 12px 15px;
            font-family: monospace;
            font-size: 0.85rem;
        }

        .btn-copy {
            background: var(--primary);
            color: #000;
            border: none;
            padding: 0 20px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-copy:hover {
            opacity: 0.9;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0,0,0,0.3);
            border-top-color: #000;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .status-pulse {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin: 0 auto;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background: #f59e0b;
            border-radius: 50%;
            animation: pulse-dot 1.5s infinite;
        }

        @keyframes pulse-dot {
            0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(245, 158, 11, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }

        .success-screen {
            display: none;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .success-icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

    </style>

    <!-- Custom User HTML (Head) -->
    <?php echo $checkout['custom_html_head']; ?>
</head>
<body>

    <div class="checkout-wrapper">
        <?php if (!empty($checkout['checkout_banner_url'])): ?>
        <div class="checkout-banner">
            <img src="<?php echo htmlspecialchars($checkout['checkout_banner_url']); ?>" alt="Banner do Checkout">
        </div>
        <?php endif; ?>
        
        <div class="checkout-container">
            <!-- Resumo do Pedido -->
            <div class="order-summary">
                <h2 class="order-title"><i class="fas fa-shopping-bag" style="color: var(--primary);"></i> Resumo da Compra</h2>
                
                <div class="items-list">
                    <?php foreach ($items as $item): ?>
                    <div class="item-row">
                        <div style="display:flex; align-items:center; gap: 10px;">
                            <?php if (!empty($item['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" style="width:40px; height:40px; border-radius:8px; object-fit:cover;">
                            <?php endif; ?>
                            <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                        </div>
                        <span class="item-price">R$ <?php echo number_format($item['price'], 2, ',', '.'); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="total-row">
                    <span>Total</span>
                    <span class="total-price">R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
                </div>
            </div>

        <!-- Formulário de Pagamento -->
        <div>
            <form id="checkout-form" class="payment-form">
                <h2 class="form-title">Dados de Pagamento</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
                    Preencha os dados abaixo para gerar o seu QR Code Pix.
                </p>

                <div class="input-group">
                    <label class="input-label">Nome Completo</label>
                    <input type="text" id="customer_name" class="input-field" placeholder="Ex: João Silva" required>
                </div>

                <div class="input-group">
                    <label class="input-label">CPF (Opcional)</label>
                    <input type="text" id="customer_document" class="input-field" placeholder="000.000.000-00">
                </div>

                <div class="secure-badge">
                    <i class="fas fa-lock"></i> Pagamento processado de forma segura
                </div>

                <button type="submit" id="btn-submit" class="btn-pay">
                    <span>Pagar R$ <?php echo number_format($total, 2, ',', '.'); ?> com Pix</span>
                    <img src="https://logopng.com.br/logos/pix-106.png" class="pix-logo" alt="Pix" style="filter: brightness(0); mix-blend-mode: multiply;">
                </button>
            </form>
        </div>
    </div>

    </div>

    <!-- Modal Pagamento PIX -->
    <div class="modal-overlay" id="pix-modal">
        <div class="modal-content" id="pix-content">
            <h3 style="margin-bottom: 1rem; font-size: 1.4rem;">Pague via Pix</h3>
            
            <div class="status-pulse">
                <div class="pulse-dot"></div> Aguardando Pagamento...
            </div>

            <div class="qr-wrapper" id="qr-container">
                <!-- Loader -->
                <div style="display:flex; justify-content:center; align-items:center; height:100%;"><div class="spinner" style="border-top-color:var(--primary); width:40px; height:40px; border-width:4px;"></div></div>
            </div>

            <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 8px;">Ou use o Copia e Cola:</p>
            
            <div class="copy-group">
                <input type="text" id="pix-code" class="copy-input" readonly value="Aguarde...">
                <button type="button" id="btn-copy" class="btn-copy">
                    <i class="far fa-copy"></i> Copiar
                </button>
            </div>
            
            <p style="font-size: 0.8rem; color: var(--text-muted);"><i class="fas fa-shield-alt"></i> Transação criptografada</p>
        </div>

        <!-- Tela de Sucesso (Oculta por padrão) -->
        <div class="modal-content success-screen" id="success-content">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 style="font-size: 1.8rem; margin-bottom: 0.5rem; color: var(--primary);">Pagamento Aprovado!</h2>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Seu pedido foi confirmado e o pagamento foi recebido com sucesso.</p>
            
            <!-- We could redirect or just show success -->
            <button onclick="location.reload()" class="btn-pay" style="margin-top:0;">Fazer Novo Pedido</button>
        </div>
    </div>


    <script>
        const form = document.getElementById('checkout-form');
        const btnSubmit = document.getElementById('btn-submit');
        const modal = document.getElementById('pix-modal');
        const qrContainer = document.getElementById('qr-container');
        const pixCodeInput = document.getElementById('pix-code');
        const btnCopy = document.getElementById('btn-copy');
        const pixContent = document.getElementById('pix-content');
        const successContent = document.getElementById('success-content');
        
        let pollingInterval = null;

        // CPF Mask simple
        document.getElementById('customer_document').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, "");
            if (value.length > 11) value = value.slice(0,11);
            if (value.length > 9) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
            } else if (value.length > 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})/, "$1.$2.$3");
            } else if (value.length > 3) {
                value = value.replace(/(\d{3})(\d{3})/, "$1.$2");
            }
            e.target.value = value;
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const name = document.getElementById('customer_name').value;
            const documentVal = document.getElementById('customer_document').value;
            
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<div class="spinner"></div> Gerando...';

            try {
                const response = await fetch('process_checkout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        checkout_id: <?php echo $checkoutId; ?>,
                        customer_name: name,
                        customer_document: documentVal
                    })
                });

                const data = await response.json();

                if (data.success) {
                    modal.classList.add('active');
                    
                    // Show QR
                    qrContainer.innerHTML = `<img src="${data.qr_image}" alt="QR Code PIX">`;
                    pixCodeInput.value = data.pix_code;
                    
                    // Start polling
                    startPolling(data.pix_id);
                } else {
                    alert(data.message || 'Erro ao gerar pagamento.');
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = `<span>Pagar R$ <?php echo number_format($total, 2, ',', '.'); ?> com Pix</span><img src="https://logopng.com.br/logos/pix-106.png" class="pix-logo" alt="Pix" style="filter: brightness(0); mix-blend-mode: multiply;">`;
                }

            } catch (err) {
                console.error(err);
                alert("Erro de conexão com o servidor.");
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = `<span>Pagar R$ <?php echo number_format($total, 2, ',', '.'); ?> com Pix</span><img src="https://logopng.com.br/logos/pix-106.png" class="pix-logo" alt="Pix" style="filter: brightness(0); mix-blend-mode: multiply;">`;
            }
        });

        btnCopy.addEventListener('click', () => {
            pixCodeInput.select();
            document.execCommand('copy');
            
            const origHtml = btnCopy.innerHTML;
            btnCopy.innerHTML = '<i class="fas fa-check"></i> Copiado!';
            setTimeout(() => {
                btnCopy.innerHTML = origHtml;
            }, 2000);
        });

        function startPolling(pixId) {
            if (pollingInterval) clearInterval(pollingInterval);
            
            pollingInterval = setInterval(async () => {
                try {
                    const res = await fetch(`check_checkout_status.php?pix_id=${pixId}`);
                    const data = await res.json();
                    
                    if (data.status === 'paid') {
                        clearInterval(pollingInterval);
                        showSuccess();
                    }
                } catch (err) {
                    console.error('Polling error', err);
                }
            }, 3000);
        }

        function showSuccess() {
            pixContent.style.display = 'none';
            successContent.style.display = 'flex';
            
            // Optionally trigger any custom JS success callbacks
            if (typeof window.onCheckoutSuccess === 'function') {
                window.onCheckoutSuccess();
            }
        }
    </script>
    
    <!-- Custom User HTML (Body) -->
    <?php echo $checkout['custom_html_body']; ?>
</body>
</html>
