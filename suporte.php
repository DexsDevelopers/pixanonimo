<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Ghost Pix - Suporte & FAQ</title>
    <link rel="stylesheet" href="style.css?v=125.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <style>
        .faq-container { max-width: 800px; margin: 0 auto; }
        .faq-item { margin-bottom: 1rem; border: 1px solid var(--border); border-radius: 12px; overflow: hidden; transition: all 0.3s var(--ease); background: var(--bg-card); }
        .faq-question { padding: 1.2rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.01); font-weight: 600; color: var(--text); }
        .faq-question:hover { background: rgba(255,255,255,0.04); }
        .faq-answer { padding: 0 1.2rem; max-height: 0; overflow: hidden; transition: all 0.3s var(--ease); background: rgba(0,0,0,0.15); font-size: 0.9rem; line-height: 1.6; color: var(--text-2); }
        .faq-item.active { border-color: var(--border-h); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .faq-item.active .faq-answer { padding: 1.2rem; max-height: 600px; }
        .faq-item.active .faq-question i { transform: rotate(180deg); color: var(--text); }
        .support-contact-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-top: 2rem; }
        .contact-method { padding: 1.5rem; text-align: center; border-radius: 16px; border: 1px solid var(--border); background: var(--bg-card); transition: all 0.3s var(--ease); text-decoration: none; color: var(--text); display: flex; flex-direction: column; align-items: center; }
        .contact-method:hover { transform: translateY(-5px); border-color: var(--border-h); background: var(--bg-card-h); box-shadow: 0 15px 40px rgba(0,0,0,0.4); }
        .contact-method i { font-size: 2rem; margin-bottom: 1rem; color: var(--green); }
    </style>
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-header">
                <div>
                    <h1>Central de Suporte</h1>
                    <p style="color: var(--text-dim);">Dúvidas frequentes e atendimento humano</p>
                </div>
                <a href="dashboard.php" class="badge sent" style="text-decoration:none">Voltar ao Dashboard</a>
            </header>

            <div class="faq-container">
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <div class="card-title-group">
                            <div class="card-icon"><i class="fas fa-shield-halved"></i></div>
                            <h3 class="card-title">Sobre a Plataforma</h3>
                        </div>
                    </div>
                    <p style="color: var(--text-2); line-height: 1.7; margin-bottom: 1rem; font-size: 0.95rem;">
                        O **Ghost Pix** começou como uma solução de pagamentos focada 100% em **privacidade e segurança anti-rastreio**. Nossa plataforma foi originalmente projetada considerando o cenário atual onde muitas pessoas precisam **fugir de bloqueios judiciais, taxações abusivas ou apenas preservar seu patrimônio de forma totalmente anônima**.
                    </p>
                    <p style="color: var(--text-2); line-height: 1.7; font-size: 0.95rem;">
                        Asseguramos proteção completa da sua identidade e movimentação sem rastros corporativos, garantindo que você tenha o **controle total do seu dinheiro**.
                    </p>
                </div>

                <h3>Perguntas Frequentes (FAQ)</h3>
                
                <div class="faq-item">
                    <div class="faq-question">Como funciona a geração de Pix? <i class="fas fa-chevron-down"></i></div>
                    <div class="faq-answer">Basta inserir o valor desejado no Dashboard e clicar em "Gerar QR Code". O sistema criará um Pix dinâmico com expiração de 20 minutos. Assim que o pagamento for concluído, o saldo cairá na sua conta Ghost Pix.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">O recebimento é realmente anônimo? <i class="fas fa-chevron-down"></i></div>
                    <div class="faq-answer">Sim! Priorizamos o sigilo. Quem faz o pagamento do Pix gerado por você, não verá os seus dados pessoais diretos nem cruzamentos com seu CPF. Usamos contas de liquidação parceiras terceirizadas para garantir que o dinheiro chegue até você de forma limpa e sem amarras judiciais.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Posso usar a plataforma para fugir de problemas com fisco/bloqueios? <i class="fas fa-chevron-down"></i></div>
                    <div class="faq-answer">O Ghost Pix foi construído pensando na blindagem de patrimônio e fluxos de caixa paralelos. Entendemos a necessidade de operar sem a supervisão do Banco Central ou congelamentos de BACENJUD. Nós provemos as ferramentas para você fazer isso com segurança.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Qual o prazo e como é feito o saque? <i class="fas fa-chevron-down"></i></div>
                    <div class="faq-answer">O seu saldo Ghost Pix fica protegido conosco. Quando você solicitar o saque, a transferência será realizada por contas blindadas da nossa própria base de liquidez, em até 2 dias úteis, despistando qualquer análise na sua conta bancária de destino final.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Por que meu Pix foi rejeitado ou expirou? <i class="fas fa-chevron-down"></i></div>
                    <div class="faq-answer">Para garantir que a liquidação não deixe rastros e funcione via nosso proxy, cada chave copia e cola tem validade rigorosa de 20 minutos. Passado esse tempo, o intermediário descarta a cobrança para evitar falhas de conciliação. Em caso de expiração, basta gerar um novo QR Code idêntico.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Como configurar minha chave Pix? <i class="fas fa-chevron-down"></i></div>
                    <div class="faq-answer">Acesse o menu "Perfil" no painel lateral. Lá você poderá atualizar seu nome e sua chave Pix para recebimento de saques. Certifique-se de que a chave está correta para evitar atrasos.</div>
                </div>

                <div class="card" style="margin-top: 3rem; text-align: center;">
                    <div class="card-header" style="justify-content: center;">
                        <div class="card-title-group">
                            <div class="card-icon"><i class="fas fa-headset"></i></div>
                            <h3 class="card-title">Ainda precisa de ajuda?</h3>
                        </div>
                    </div>
                    <p style="color: var(--text-2); margin-bottom: 1.5rem; font-size: 0.95rem;">Fale diretamente com nossa equipe de suporte humano.</p>
                    
                    <div class="support-contact-grid">
                        <a href="https://wa.me/5551996148568" target="_blank" class="contact-method">
                            <i class="fab fa-whatsapp"></i>
                            <h4 style="margin-bottom: 0.3rem;">WhatsApp</h4>
                            <p style="font-size: 0.8rem; color: var(--text-2);">Atendimento Instantâneo</p>
                        </a>
                        <a href="mailto:empresatokio@gmail.com" class="contact-method">
                            <i class="far fa-envelope"></i>
                            <h4 style="margin-bottom: 0.3rem;">E-mail</h4>
                            <p style="font-size: 0.8rem; color: var(--text-2);">Suporte Corporativo</p>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="script.js?v=124.0"></script>
</body>
</html>

