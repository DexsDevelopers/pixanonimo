<?php
session_start();
require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="lp-body">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Ghost Pix - API de Pagamentos Blindada</title>
    <link rel="stylesheet" href="style.css?v=125.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <style>
        .code-block {
            background: rgba(0,0,0,0.5);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-size: 0.9rem;
            line-height: 1.6;
            color: #d1d5db;
            overflow-x: auto;
            position: relative;
            margin: 2rem 0;
        }
        .code-keyword { color: #f472b6; }
        .code-string { color: #4ade80; }
        .code-comment { color: #6b7280; }
        .api-hero {
            padding: 8rem 0 4rem;
            text-align: center;
        }
        .api-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 4rem 0;
        }
        .nav-link-side {
            padding: 0.8rem 1.2rem;
            color: var(--text-2);
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border-left: 2px solid transparent;
        }
        .nav-link-side:hover {
            background: rgba(255,255,255,0.05);
            color: var(--text);
        }
        .nav-link-side.active {
            color: var(--green);
            background: rgba(74, 222, 128, 0.05);
            border-left: 2px solid var(--green);
        }
        .mobile-hide {
            display: block;
        }
        @media (max-width: 768px) {
            .mobile-hide { display: none; }
            .lp-container { padding: 0 1.5rem; }
            main > div { grid-template-columns: 1fr !important; }
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        table td, table th {
            font-size: 0.85rem;
            color: var(--text);
        }
        /* Tab Buttons Styling */
        .tab-btn {
            background: rgba(255,255,255,0.05) !important;
            color: #fff !important;
            border: 1px solid var(--border) !important;
            transition: all 0.3s ease;
        }
        .tab-btn:hover {
            background: rgba(255,255,255,0.1) !important;
            border-color: var(--green) !important;
        }
        .tab-btn.active {
            background: var(--green) !important;
            color: #000 !important;
            border-color: var(--green) !important;
            font-weight: 700;
        }
    </style>
</head>
<body class="lp-body">
    <canvas id="canvas-3d" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none; opacity: 0.4;"></canvas>

    <nav class="lp-navbar">
        <div class="logo">
            <img src="logo_premium.png?v=107.0" class="logo-img" alt="Ghost Logo">
            <span class="logo-text">GHOST<span> PIX</span></span>
        </div>
        <div class="lp-nav-links">
            <a href="index.php" class="lp-nav-link">HOME</a>
            <a href="index.php#faq" class="lp-nav-link">FAQ</a>
            <a href="suporte.php" class="lp-nav-link">CONTATO</a>
        </div>
        <div class="lp-auth-buttons mobile-hide-links">
            <?php if(isLoggedIn()): ?>
                <a href="dashboard.php" class="btn-lp-primary">PAINEL</a>
            <?php else: ?>
                <a href="auth/login.php" class="btn-lp-outline-sm">ENTRAR</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="lp-container">
        <section class="api-hero" data-aos="fade-up">
            <div class="lp-hero-tag" style="margin-bottom: 1rem;">PARA DESENVOLVEDORES E LOJISTAS</div>
            <h1 class="lp-responsive-title">DOCUMENTAÇÃO <span class="lp-gradient-text">GHOST API</span></h1>
            <p style="max-width: 700px; margin: 1.5rem auto;">Tudo o que você precisa para integrar pagamentos blindados em seu checkout.</p>
        </section>

        <div style="display: grid; grid-template-columns: 250px 1fr; gap: 3rem; margin: 4rem 0;">
            <!-- Sidebar Docs -->
            <aside style="position: sticky; top: 100px; height: fit-content;" class="mobile-hide">
                <nav style="display: flex; flex-direction: column; gap: 0.8rem;">
                    <a href="#autenticacao" class="nav-link-side active">Autenticação</a>
                    <a href="#gerar-pix" class="nav-link-side">Gerar Pix</a>
                    <a href="#status-check" class="nav-link-side">Consultar Status</a>
                    <a href="#webhooks" class="nav-link-side">Webhooks</a>
                    <a href="#exemplos" class="nav-link-side">Exemplos de Código</a>
                    <a href="#telegram" class="nav-link-side">Telegram Bots</a>
                    <a href="#mobile" class="nav-link-side">Apps Mobile</a>
                    <a href="#seguranca" class="nav-link-side">Segurança e Dicas</a>
                    <a href="#erros" class="nav-link-side">Erros Comuns</a>
                </nav>
            </aside>

            <!-- Main Docs Content -->
            <div class="docs-content">
                <!-- Autenticação -->
                <section id="autenticacao" style="margin-bottom: 5rem;" data-aos="fade-up">
                    <h2 style="color: var(--green);">1. Autenticação</h2>
                    <p>Todas as chamadas à API devem incluir sua <strong>Ghost Key</strong> no header da requisição através do protocolo Bearer Auth.</p>
                    <div class="code-block">
                        Authorization: Bearer <span class="code-string">ghost_f2146406f1be1789ad472403...</span>
                    </div>
                </section>

                <!-- Gerar Pix -->
                <section id="gerar-pix" style="margin-bottom: 5rem;" data-aos="fade-up">
                    <h2 style="color: var(--green);">2. Gerar Cobrança (Pix)</h2>
                    <p>Endpoint para criar uma nova ordem de pagamento.</p>
                    <div style="display: flex; gap: 1rem; margin: 1rem 0;">
                        <span class="badge" style="background: var(--green); color: #000; font-weight: 800;">POST</span>
                        <code>/api.php</code>
                    </div>

                    <h4 style="margin-top: 2rem;">Parâmetros (Body JSON)</h4>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                        <tr style="border-bottom: 1px solid var(--border); text-align: left; color: var(--text-2);">
                            <th style="padding: 1rem;">Campo</th>
                            <th style="padding: 1rem;">Tipo</th>
                            <th style="padding: 1rem;">Obrigatório</th>
                            <th style="padding: 1rem;">Descrição</th>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;"><code>amount</code></td>
                            <td style="padding: 1rem;">float</td>
                            <td style="padding: 1rem;">Sim</td>
                            <td style="padding: 1rem;">Valor em BRL (Mínimo 10.00)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;"><code>callback_url</code></td>
                            <td style="padding: 1rem;">string</td>
                            <td style="padding: 1rem;">Não</td>
                            <td style="padding: 1rem;">Sua URL para receber o Webhook</td>
                        </tr>
                    </table>

                    <div class="code-block">
                        <span class="code-comment">// Resposta de Sucesso</span><br>
                        {<br>
                        &nbsp;&nbsp;<span class="code-keyword">"success"</span>: <span class="code-keyword">true</span>,<br>
                        &nbsp;&nbsp;<span class="code-keyword">"pix_id"</span>: <span class="code-string">"px_123..."</span>,<br>
                        &nbsp;&nbsp;<span class="code-keyword">"pix_code"</span>: <span class="code-string">"000201..."</span>,<br>
                        &nbsp;&nbsp;<span class="code-keyword">"qr_image"</span>: <span class="code-string">"https://..."</span><br>
                        }
                    </div>
                </section>

                <!-- Consultar Status -->
                <section id="status-check" style="margin-bottom: 5rem;" data-aos="fade-up">
                    <h2 style="color: var(--green);">3. Consultar Status</h2>
                    <p>Caso prefira fazer polling manual do status da transação.</p>
                    <div style="display: flex; gap: 1rem; margin: 1rem 0;">
                        <span class="badge" style="background: var(--blue); color: #fff; font-weight: 800;">GET</span>
                        <code>/check_status.php?pix_id=ID_AQUI</code>
                    </div>
                    <div class="code-block">
                        { <span class="code-keyword">"status"</span>: <span class="code-string">"paid"</span> | <span class="code-string">"pending"</span> }
                    </div>
                </section>

                <!-- Webhooks -->
                <section id="webhooks" style="margin-bottom: 5rem;" data-aos="fade-up">
                    <h2 style="color: var(--green);">4. Webhooks (Callbacks)</h2>
                    <p>O Ghost Pix enviará um POST JSON para a sua <code>callback_url</code> assim que a liquidação for confirmada.</p>
                    <div class="code-block">
                        <span class="code-comment">// Payload enviado ao seu servidor</span><br>
                        {<br>
                        &nbsp;&nbsp;<span class="code-keyword">"event"</span>: <span class="code-string">"payment.completed"</span>,<br>
                        &nbsp;&nbsp;<span class="code-keyword">"pix_id"</span>: <span class="code-string">"px_123..."</span>,<br>
                        &nbsp;&nbsp;<span class="code-keyword">"amount"</span>: <span class="code-string">50.00</span>,<br>
                        &nbsp;&nbsp;<span class="code-keyword">"status"</span>: <span class="code-string">"paid"</span><br>
                        }
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-3);"><i class="fas fa-info-circle"></i> Recomendamos retornar o código HTTP 200 para confirmar o recebimento.</p>
                </section>

                <!-- Erros -->
                <section id="erros" style="margin-bottom: 5rem;" data-aos="fade-up">
                    <h2 style="color: var(--red);">5. Códigos de Erro</h2>
                    <p>Lista de erros que a API pode retornar.</p>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                        <tr style="border-bottom: 1px solid var(--border); text-align: left; color: var(--text-2);">
                            <th style="padding: 1rem;">HTTP</th>
                            <th style="padding: 1rem;">Código</th>
                            <th style="padding: 1rem;">Descrição</th>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;"><code>401</code></td>
                            <td style="padding: 1rem;">Não autorizado</td>
                            <td style="padding: 1rem;">Ghost Key vazia ou inválida.</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;"><code>400</code></td>
                            <td style="padding: 1rem;">Valor mínimo</td>
                            <td style="padding: 1rem;">O montante enviado é menor que R$ 10,00.</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;"><code>403</code></td>
                            <td style="padding: 1rem;">CSRF Error</td>
                            <td style="padding: 1rem;">Chamada via browser sem token de segurança.</td>
                        </tr>
                    </table>
                </section>

                <!-- Exemplos de Integração -->
                <section id="exemplos" style="margin-bottom: 5rem;" data-aos="fade-up">
                    <h2 style="color: var(--green);">6. Exemplos de Integração</h2>
                    <p>Escolha sua linguagem e veja como é fácil integrar o Ghost Pix.</p>
                    
                    <div class="tabs-container" style="margin-top: 2rem;">
                        <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
                            <button class="tab-btn active" onclick="showTab('go')">Go</button>
                            <button class="tab-btn" onclick="showTab('node')">Node.js</button>
                            <button class="tab-btn" onclick="showTab('python')">Python</button>
                            <button class="tab-btn" onclick="showTab('php')">PHP / JS</button>
                            <button class="tab-btn" onclick="showTab('telegram')">Telegram</button>
                            <button class="tab-btn" onclick="showTab('mobile')">Mobile</button>
                        </div>

                        <!-- Go -->
                        <div id="tab-go" class="tab-content">
                            <p>Go é excelente pra quem quer velocidade e processamento em massa.</p>
                            <div class="code-block">
<span class="code-keyword">package</span> main<br><br>
<span class="code-keyword">import</span> (<br>
&nbsp;&nbsp;<span class="code-string">"bytes"</span><br>
&nbsp;&nbsp;<span class="code-string">"encoding/json"</span><br>
&nbsp;&nbsp;<span class="code-string">"fmt"</span><br>
&nbsp;&nbsp;<span class="code-string">"net/http"</span><br>
)<br><br>
<span class="code-keyword">func</span> main() {<br>
&nbsp;&nbsp;url := <span class="code-string">"https://pixghost.site/api.php"</span><br>
&nbsp;&nbsp;minhaChave := <span class="code-string">"ghost_SUA_CHAVE_AQUI"</span><br><br>
&nbsp;&nbsp;dados := <span class="code-keyword">map</span>[<span class="code-keyword">string</span>]<span class="code-keyword">interface</span>{}{ <span class="code-string">"amount"</span>: <span class="code-string">100.00</span> }<br>
&nbsp;&nbsp;corpo, _ := json.Marshal(dados)<br><br>
&nbsp;&nbsp;req, _ := http.NewRequest(<span class="code-string">"POST"</span>, url, bytes.NewBuffer(corpo))<br>
&nbsp;&nbsp;req.Header.Set(<span class="code-string">"Authorization"</span>, <span class="code-string">"Bearer "</span> + minhaChave)<br>
&nbsp;&nbsp;req.Header.Set(<span class="code-string">"Content-Type"</span>, <span class="code-string">"application/json"</span>)<br><br>
&nbsp;&nbsp;cliente := &http.Client{}<br>
&nbsp;&nbsp;resp, err := cliente.Do(req)<br>
&nbsp;&nbsp;...<br>
}
                            </div>
                        </div>

                        <!-- Node -->
                        <div id="tab-node" class="tab-content" style="display: none;">
                            <p>Use a biblioteca <strong>Axios</strong> para uma integração limpa e moderna.</p>
                            <div class="code-block">
<span class="code-keyword">const</span> axios = <span class="code-keyword">require</span>(<span class="code-string">'axios'</span>);<br><br>
<span class="code-keyword">async function</span> gerarPix(valor) {<br>
&nbsp;&nbsp;<span class="code-keyword">const</span> URL = <span class="code-string">'https://pixghost.site/api.php'</span>;<br>
&nbsp;&nbsp;<span class="code-keyword">const</span> KEY = <span class="code-string">'ghost_SUA_CHAVE_AQUI'</span>;<br><br>
&nbsp;&nbsp;<span class="code-keyword">try</span> {<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span class="code-keyword">const</span> res = <span class="code-keyword">await</span> axios.post(URL, { amount: valor }, {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;headers: { <span class="code-string">'Authorization'</span>: <span class="code-string">`Bearer ${KEY}`</span> }<br>
&nbsp;&nbsp;&nbsp;&nbsp;});<br>
&nbsp;&nbsp;&nbsp;&nbsp;...<br>
&nbsp;&nbsp;} <span class="code-keyword">catch</span> (err) { ... }<br>
}
                            </div>
                        </div>

                        <!-- Python -->
                        <div id="tab-python" class="tab-content" style="display: none;">
                            <p>Com a biblioteca <code>requests</code>, você resolve a integração em segundos.</p>
                            <div class="code-block">
<span class="code-keyword">import</span> requests<br><br>
<span class="code-keyword">def</span> criar_cobranca(valor):<br>
&nbsp;&nbsp;url = <span class="code-string">"https://pixghost.site/api.php"</span><br>
&nbsp;&nbsp;headers = { <span class="code-string">"Authorization"</span>: <span class="code-string">"Bearer ghost_SUA_CHAVE"</span> }<br><br>
&nbsp;&nbsp;res = requests.post(url, headers=headers, json={<span class="code-string">"amount"</span>: valor}).json()<br>
&nbsp;&nbsp;<span class="code-keyword">if</span> res.get(<span class="code-string">'success'</span>):<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span class="code-keyword">print</span>(<span class="code-string">"Link do QR Code:"</span>, res[<span class="code-string">'qr_image'</span>])
                            </div>
                        </div>

                        <!-- PHP / JS -->
                        <div id="tab-php" class="tab-content" style="display: none;">
                            <p>Use um arquivo PHP como ponte para proteger sua chave secreta.</p>
                            <div class="code-block">
<span class="code-comment">// pix.php (Ponte segura)</span><br>
<span class="code-keyword">$SUA_CHAVE</span> = <span class="code-string">"ghost_SUA_CHAVE_AQUI"</span>;<br>
<span class="code-keyword">$valor</span> = <span class="code-keyword">$_POST</span>[<span class="code-string">'v'</span>] ?? <span class="code-string">10.00</span>;<br><br>
<span class="code-keyword">$ch</span> = curl_init(<span class="code-string">"https://pixghost.site/api.php"</span>);<br>
curl_setopt(<span class="code-keyword">$ch</span>, CURLOPT_HTTPHEADER, [<br>
&nbsp;&nbsp;<span class="code-string">"Authorization: Bearer $SUA_CHAVE"</span>,<br>
&nbsp;&nbsp;<span class="code-string">"Content-Type: application/json"</span><br>
]);<br>
curl_exec(<span class="code-keyword">$ch</span>);
                            </div>
                        </div>

                        <!-- Telegram -->
                        <div id="tab-telegram" class="tab-content" style="display: none;">
                            <p>Crie bots que vendem sozinhos no Telegram usando Python e <code>telebot</code>.</p>
                            <div class="code-block">
<span class="code-keyword">import</span> telebot, requests<br><br>
bot = telebot.TeleBot(<span class="code-string">'TOKEN_DO_BOT'</span>)<br><br>
<span class="code-keyword">@bot.message_handler</span>(commands=[<span class="code-string">'comprar'</span>])<br>
<span class="code-keyword">def</span> comprar(message):<br>
&nbsp;&nbsp;res = requests.post(<span class="code-string">"https://pixghost.site/api.php"</span>, <br>
&nbsp;&nbsp;&nbsp;&nbsp;headers={<span class="code-string">"Authorization"</span>: <span class="code-string">"Bearer ghost_KEY"</span>},<br>
&nbsp;&nbsp;&nbsp;&nbsp;json={<span class="code-string">"amount"</span>: <span class="code-string">15.00</span>}).json()<br><br>
&nbsp;&nbsp;<span class="code-keyword">if</span> res.get(<span class="code-string">'success'</span>):<br>
&nbsp;&nbsp;&nbsp;&nbsp;bot.send_photo(message.chat.id, res[<span class="code-string">'qr_image'</span>])<br>
&nbsp;&nbsp;&nbsp;&nbsp;bot.send_message(message.chat.id, <span class="code-string">f"Pix:\n`{res['pix_code']}`"</span>, parse_mode=<span class="code-string">"Markdown"</span>)
                            </div>
                        </div>

                        <!-- Mobile -->
                        <div id="tab-mobile" class="tab-content" style="display: none;">
                            <p>Integração nativa para aplicativos Android/iOS (React Native / Flutter).</p>
                            <div class="code-block">
<span class="code-keyword">const</span> fazerPix = <span class="code-keyword">async</span> (valor) => {<br>
&nbsp;&nbsp;<span class="code-keyword">const</span> res = <span class="code-keyword">await</span> fetch(<span class="code-string">'https://pixghost.site/api.php'</span>, {<br>
&nbsp;&nbsp;&nbsp;&nbsp;method: <span class="code-string">'POST'</span>,<br>
&nbsp;&nbsp;&nbsp;&nbsp;headers: {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="code-string">'Authorization'</span>: <span class="code-string">'Bearer ghost_KEY'</span>,<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="code-string">'Content-Type'</span>: <span class="code-string">'application/json'</span><br>
&nbsp;&nbsp;&nbsp;&nbsp;},<br>
&nbsp;&nbsp;&nbsp;&nbsp;body: JSON.stringify({ amount: valor })<br>
&nbsp;&nbsp;});<br>
&nbsp;&nbsp;...<br>
};
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Telegram Bots Detalhado -->
                <section id="telegram" style="margin-bottom: 5rem;" data-aos="fade-up">
                    <h2 style="color: var(--green);">7. Bots do Telegram Dinâmicos</h2>
                    <p>Vá além do básico: aprenda a trocar seu token Ghost direto pelo celular através do bot.</p>
                    <div class="code-block">
<span class="code-keyword">@bot.message_handler</span>(commands=[<span class="code-string">'token'</span>])<br>
<span class="code-keyword">def</span> configurar(message):<br>
&nbsp;&nbsp;<span class="code-keyword">if</span> message.from_user.id != MEU_ID:<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span class="code-keyword">return</span><br>
&nbsp;nova_chave = message.text.replace(<span class="code-string">'/token '</span>, <span class="code-string">''</span>).strip()<br>
&nbsp;salvar_key(nova_chave) <span class="code-comment"># Salva em config.json</span><br>
&nbsp;bot.reply_to(message, <span class="code-string">"✅ Chave atualizada!"</span>)
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-2);"><i class="fas fa-lightbulb"></i> Use o bot <code>@userinfobot</code> para descobrir seu ID e travar o comando apenas para você.</p>
                </section>

                <!-- Apps Mobile Detalhado -->
                <section id="mobile" style="margin-bottom: 5rem;" data-aos="fade-up">
                    <h2 style="color: var(--green);">8. Boas Práticas para Mobile</h2>
                    <p>Foque na experiência do usuário em dispositivos móveis.</p>
                    <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                        <div class="card glass-card" style="padding: 1.5rem;">
                            <h4 style="color: var(--blue);"><i class="fas fa-mobile-alt"></i> UX Mobile</h4>
                            <p style="font-size: 0.85rem;">Ninguém gosta de escanear QR Code no próprio celular. <strong>Sempre destaque o botão de copiar código.</strong></p>
                        </div>
                        <div class="card glass-card" style="padding: 1.5rem;">
                            <h4 style="color: var(--red);"><i class="fas fa-server"></i> Segurança</h4>
                            <p style="font-size: 0.85rem;">Evite deixar o <code>ghost_token</code> no código do app. O ideal é usar seu servidor como ponte.</p>
                        </div>
                    </div>
                </section>

                <!-- Segurança e Boas Práticas -->
                <section id="seguranca" style="margin-bottom: 5rem;" data-aos="fade-up">
                    <h2 style="color: var(--blue);">7. Segurança e Boas Práticas</h2>
                    <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                        <div class="card glass-card" style="padding: 1.5rem;">
                            <h4 style="color: var(--green);"><i class="fas fa-shield-alt"></i> Proteção de Chaves</h4>
                            <p style="font-size: 0.85rem;">Jamais exponha sua <code>ghost_key</code> no frontend ou em repositórios públicos. Use variáveis de ambiente (<code>.env</code>).</p>
                        </div>
                        <div class="card glass-card" style="padding: 1.5rem;">
                            <h4 style="color: var(--blue);"><i class="fas fa-lock"></i> Webhook Seguro</h4>
                            <p style="font-size: 0.85rem;">Sempre utilize <strong>HTTPS</strong> para receber notificações. Webhooks não funcionam em <code>localhost</code>.</p>
                        </div>
                        <div class="card glass-card" style="padding: 1.5rem;">
                            <h4 style="color: var(--red);"><i class="fas fa-check-double"></i> Confirmação</h4>
                            <p style="font-size: 0.85rem;">Sempre responda com o código HTTP <strong>200</strong> após processar um Webhook para evitar reenvios.</p>
                        </div>
                    </div>
                    <div class="card glass-card" style="padding: 1.5rem; margin-top: 1.5rem; border-left: 4px solid var(--green);">
                        <p style="margin: 0;"><strong>Dica de Lucro:</strong> Deixe o código "Pix Copia e Cola" bem visível. A maioria dos usuários prefere copiar o código do que escanear o QR Code.</p>
                    </div>
                </section>

                <div class="card glass-card" style="padding: 2rem; margin-top: 4rem;">
                    <h3>Dúvidas Técnicas?</h3>
                    <p>Nossa equipe de engenharia está disponível via Telegram para apoiar sua implementação.</p>
                    <a href="suporte.php" class="btn-lp-primary" style="margin-top: 1rem;">Falar com Desenvolvedor</a>
                </div>
            </div>
        </div>
    </main>

    <footer class="lp-footer-v2">
        <div class="lp-footer-container">
            <div class="lp-footer-brand">
                <div class="logo">
                    <img src="logo_premium.png?v=107.0" class="logo-img" alt="Ghost Logo">
                    <span class="logo-text">GHOST<span> PIX</span></span>
                </div>
                <p class="lp-brand-tagline">Privacidade é um direito, não um privilégio.</p>
            </div>
            <div class="lp-footer-links-grid">
                <div class="lp-footer-col">
                    <h4>Páginas</h4>
                    <a href="index.php">Início</a>
                    <a href="suporte.php">Suporte</a>
                </div>
                <div class="lp-footer-col">
                    <h4>Legal</h4>
                    <a href="termos.php">Termos</a>
                    <a href="privacidade.php">Privacidade</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 1000, once: true });
        
        // Three.js Abstract Background (Simplified version of index.php for visual consistency)
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ canvas: document.getElementById('canvas-3d'), alpha: true });
        renderer.setSize(window.innerWidth, window.innerHeight);

        const particlesGeometry = new THREE.BufferGeometry();
        const counts = 800;
        const positions = new Float32Array(counts * 3);
        for(let i = 0; i < counts * 3; i++) positions[i] = (Math.random() - 0.5) * 10;
        particlesGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        const particlesMaterial = new THREE.PointsMaterial({ size: 0.015, color: 0x4ade80, transparent: true, opacity: 0.5 });
        const particles = new THREE.Points(particlesGeometry, particlesMaterial);
        scene.add(particles);
        camera.position.z = 2;

        function animate() {
            requestAnimationFrame(animate);
            particles.rotation.y += 0.0008;
            renderer.render(scene, camera);
        }
        animate();

        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });

        // Tab System for Examples
        function showTab(lang) {
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            
            document.getElementById('tab-' + lang).style.display = 'block';
            event.currentTarget.classList.add('active');
        }

        // Active link on scroll
        window.addEventListener('scroll', () => {
            let current = "";
            document.querySelectorAll('section').forEach(section => {
                const sectionTop = section.offsetTop;
                if (pageYOffset >= sectionTop - 150) {
                    current = section.getAttribute('id');
                }
            });

            document.querySelectorAll('.nav-link-side').forEach(a => {
                a.classList.remove('active');
                if (a.getAttribute('href').includes(current)) {
                    a.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>

