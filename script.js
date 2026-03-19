document.addEventListener('DOMContentLoaded', () => {
    console.log('Ghost Pix Dashboard Loaded v8.0');

    // --- ELEMENTOS GLOBAIS ---
    const btnGenerate = document.getElementById('btn-generate');
    const modalQr = document.getElementById('modal-qr');
    const modalConfirm = document.getElementById('modal-confirm');
    const btnConfirmDelete = document.getElementById('btn-confirm-delete');
    const btnConfirmCancel = document.getElementById('btn-confirm-cancel');
    const closeModal = document.querySelectorAll('.close-modal');
    const amountInput = document.getElementById('amount');
    const modalAmount = document.getElementById('modal-amount');
    const qrPlaceholder = document.querySelector('.qr-placeholder');
    const pixCodeText = document.getElementById('pix-code-text');
    const btnCopyPix = document.getElementById('btn-copy-pix');
    const btnEditWallet = document.getElementById('btn-edit-wallet');
    const btnSaveWallet = document.getElementById('btn-save-wallet');
    const btnCopyWallet = document.getElementById('btn-copy-wallet');
    const walletInput = document.getElementById('wallet-input');
    const overlay = document.getElementById('sidebar-overlay');
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');

    // --- PUSH NOTIFICATION SETUP ---
    const PUBLIC_VAPID_KEY = 'BIgbMcD2y9lwIjmif8b21m-MdjyxYaAL5wWj0mu4tQeigYEV91Ajp-e3hVkaw5WRL2Zj19XIuV_lAqkq4h-dJ9o';

    const pushCard = document.getElementById('push-control-card');
    const pushStatusText = document.getElementById('push-status-text');
    const btnActivatePush = document.getElementById('btn-activate-push');
    const btnTestPush = document.getElementById('btn-test-push');

    async function registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('sw.js?v=8.3');
                return registration;
            } catch (error) {
                console.error('SW Error:', error);
            }
        }
        return null;
    }

    async function updatePushUI() {
        if (!pushCard) return;

        if (!('Notification' in window)) {
            pushCard.style.display = 'none';
            return;
        }

        pushCard.style.display = 'block';

        if (Notification.permission === 'granted') {
            btnActivatePush.style.display = 'none';
            btnTestPush.style.display = 'block';
            pushStatusText.innerText = 'Notificações ativas! Você receberá alertas de vendas.';
            pushStatusText.style.color = '#25d366';
        } else if (Notification.permission === 'denied') {
            btnActivatePush.innerText = 'BLOQUEADO NO NAVEGADOR';
            btnActivatePush.disabled = true;
            pushStatusText.innerText = 'Você bloqueou as notificações. Reative nas configurações do seu navegador.';
            pushStatusText.style.color = '#ef4444';
        }
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
    }

    async function subscribeUserToPush() {
        const registration = await registerServiceWorker();
        if (!registration) return;

        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                updatePushUI();
                return;
            }

            const subscribeOptions = {
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(PUBLIC_VAPID_KEY)
            };

            const subscription = await registration.pushManager.subscribe(subscribeOptions);
            const key = subscription.getKey('p256dh');
            const auth = subscription.getKey('auth');

            await fetch('save_subscription.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(key))),
                        auth: btoa(String.fromCharCode.apply(null, new Uint8Array(auth)))
                    }
                })
            });

            updatePushUI();
            alert('Sucesso! Notificações ativas neste dispositivo.');
        } catch (error) {
            console.error('Sub Error:', error);
            alert('Erro ao ativar push. Verifique se o site está em HTTPS e se você "Instalou" o app no iPhone.');
        }
    }

    if (btnActivatePush) btnActivatePush.addEventListener('click', subscribeUserToPush);
    if (btnTestPush) {
        btnTestPush.addEventListener('click', async () => {
            const registration = await navigator.serviceWorker.ready;
            registration.showNotification('Ghost Pix', {
                body: 'Teste de Notificação: Se você está vendo isso, seu sistema está pronto!',
                icon: 'logo_premium.png'
            });
        });
    }

    updatePushUI();

    let statusInterval = null;
    let countdownInterval = null;
    let deleteTarget = null; // Guardar ID e Linha para exclusão

    // --- FUNÇÕES AUXILIARES ---

    async function copyToClipboard(text) {
        if (!text) return false;
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return true;
            } else {
                const textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";
                textArea.style.left = "-9999px";
                textArea.style.top = "0";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                return successful;
            }
        } catch (err) {
            console.error('Falha ao copiar:', err);
            return false;
        }
    }

    function startPixPolling(pixId) {
        if (statusInterval) clearInterval(statusInterval);
        const startTime = Date.now();
        const expirationTime = 20 * 60 * 1000;

        statusInterval = setInterval(async () => {
            if (Date.now() - startTime > expirationTime) {
                clearInterval(statusInterval);
                if (qrPlaceholder) {
                    qrPlaceholder.innerHTML = `
                        <div style="color: var(--danger); font-size: 1.1rem; font-weight: bold; padding: 2rem; text-align: center;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
                            QR Code Expirado<br>
                            <span style="font-size: 0.85rem; color: var(--text-dim); font-weight: normal;">Gere uma nova cobrança.</span>
                        </div>`;
                }
                return;
            }

            try {
                const res = await fetch(`check_status.php?pix_id=${pixId}`);
                const data = await res.json();
                if (data.status === 'paid') {
                    clearInterval(statusInterval);
                    if (qrPlaceholder) {
                        qrPlaceholder.innerHTML = `
                            <div style="color: var(--primary); font-size: 1.2rem; font-weight: bold; padding: 2rem; text-align: center;">
                                <div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>
                                Pagamento Confirmado!
                            </div>`;
                    }
                    setTimeout(() => window.location.reload(), 2500);
                }
            } catch (e) { console.error("Polling error:", e); }
        }, 4000);
    }

    // --- LÓGICA PRINCIPAL: GERAR PIX ---

    if (btnGenerate) {
        btnGenerate.onclick = async () => {
            const val = (amountInput.value || "").toString().replace(',', '.');
            if (!val || parseFloat(val) < 10) return alert('Mínimo R$ 10,00.');

            btnGenerate.innerText = 'Processando...';
            btnGenerate.disabled = true;

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ amount: val })
                });

                const text = (await response.text()).trim();
                console.log('API Raw Response:', text);

                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON Parse Error:', e, 'Raw:', text);
                    return alert('Erro na resposta do servidor. Tente novamente.');
                }

                if (data.error) {
                    alert('Erro: ' + data.error);
                } else if (data.success || data.status === 'success' || data.pix_id) {
                    const amountDisp = data.amount || val;
                    if (modalAmount) modalAmount.innerText = `R$ ${parseFloat(amountDisp).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;

                    const code = data.pix_code || data.qr_code || data.payload || data.qrcodepix || "";
                    if (pixCodeText) {
                        pixCodeText.value = code;
                        pixCodeText.style.color = "white";
                    }

                    if (qrPlaceholder) {
                        const img = data.qr_image || data.qr_code_url || "";
                        const place = document.getElementById('qr-placeholder-v2') || qrPlaceholder;
                        place.innerHTML = `<img src="${img}" alt="QR" style="width:100%; display:block; border-radius: 8px;">`;
                    }

                    if (modalQr) {
                        modalQr.classList.remove('hidden');
                        modalQr.style.display = 'flex';
                        startCountdown(20 * 60);
                    }

                    if (data.pix_id) startPixPolling(data.pix_id);
                }
            } catch (err) {
                console.error('Fetch Error:', err);
                // Só mostra o alert se o modal não tiver carregado nada
                if (!modalQr || modalQr.classList.contains('hidden')) {
                    alert('Falha na conexão: ' + err.message);
                }
            }
            finally {
                btnGenerate.innerText = 'Gerar QR Code Pix';
                btnGenerate.disabled = false;
            }
        };
    }

    function startCountdown(duration) {
        if (countdownInterval) clearInterval(countdownInterval);
        const display = document.getElementById('pix-countdown');
        if (!display) return;

        let timer = duration, minutes, seconds;
        const update = () => {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            display.textContent = minutes + ":" + seconds;

            if (--timer < 0) {
                clearInterval(countdownInterval);
                display.textContent = "EXPIRADO";
                display.style.color = "#ef4444";
            }
        };
        update();
        countdownInterval = setInterval(update, 1000);
    }

    // --- MODAL CLOSE ---

    const closeAllModals = () => {
        if (modalQr) { modalQr.classList.add('hidden'); modalQr.style.display = 'none'; }
        if (modalConfirm) { modalConfirm.classList.add('hidden'); modalConfirm.style.display = 'none'; }
        if (statusInterval) clearInterval(statusInterval);
    };

    closeModal.forEach(btn => btn.onclick = closeAllModals);

    window.onclick = (e) => {
        if (e.target === modalQr || e.target === modalConfirm) {
            closeAllModals();
        }
    };

    // --- RECURSOS DE CÓPIA ---

    if (btnCopyPix && pixCodeText) {
        btnCopyPix.onclick = async () => {
            if (await copyToClipboard(pixCodeText.value)) {
                const old = btnCopyPix.innerText;
                btnCopyPix.innerText = 'Copiado!';
                btnCopyPix.style.background = '#22c55e';
                setTimeout(() => {
                    btnCopyPix.innerText = old;
                    btnCopyPix.style.background = '';
                }, 2000);
            }
        };
    }

    if (btnCopyWallet && walletInput) {
        btnCopyWallet.onclick = async () => {
            if (await copyToClipboard(walletInput.value)) {
                const old = btnCopyWallet.innerText;
                btnCopyWallet.innerText = '✅';
                setTimeout(() => btnCopyWallet.innerText = old, 1500);
            }
        };
    }

    // --- CARTEIRA / PERFIL ---

    if (btnEditWallet && walletInput) {
        btnEditWallet.onclick = () => {
            walletInput.readOnly = false;
            walletInput.focus();
            walletInput.style.borderBottom = "1px solid var(--primary)";
            btnEditWallet.classList.add('hidden');
            if (btnSaveWallet) btnSaveWallet.classList.remove('hidden');
        };
    }

    if (btnSaveWallet && walletInput) {
        btnSaveWallet.onclick = async () => {
            const wallet = walletInput.value.trim();
            if (!wallet) return alert("Chave não pode ser vazia");
            btnSaveWallet.disabled = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('update_wallet.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({ wallet })
                });
                const data = await res.json();
                if (data.success) {
                    alert("Sucesso!");
                    walletInput.readOnly = true;
                    walletInput.style.borderBottom = "none";
                    btnEditWallet.classList.remove('hidden');
                    btnSaveWallet.classList.add('hidden');
                } else alert(data.error);
            } catch (e) { alert("Erro de conexão"); }
            finally { btnSaveWallet.disabled = false; }
        };
    }

    // --- MENU MOBILE ---
    const sidebarClose = document.getElementById('sidebar-close');

    if (menuToggle && sidebar && overlay) {
        const closeMenu = () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        };

        menuToggle.onclick = () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        };

        if (sidebarClose) {
            sidebarClose.onclick = closeMenu;
        }

        // Delegação de evento como backup para o botão de fechar
        document.addEventListener('click', (e) => {
            if (e.target.closest('#sidebar-close')) {
                closeMenu();
            }
        });

        overlay.onclick = closeMenu;
    }

    // --- AÇÕES DO HISTÓRICO ---

    window.initHistoryActions = () => {
        console.log('Inicializando ações do histórico...');

        document.querySelectorAll('.btn-view-qr').forEach(btn => {
            btn.onclick = function () {
                const qr = this.getAttribute('data-qr');
                const code = this.getAttribute('data-code');
                const amount = this.getAttribute('data-amount');

                if (qr && qr !== "") {
                    const place = document.getElementById('qr-placeholder-v2') || qrPlaceholder;
                    if (place) place.innerHTML = `<img src="${qr}" alt="QR" style="width:100%; display:block; border-radius: 8px;">`;
                }

                if (pixCodeText) pixCodeText.value = code || "";
                if (modalAmount) modalAmount.innerText = `R$ ${amount}`;
                if (modalQr) {
                    modalQr.classList.remove('hidden');
                    modalQr.style.display = 'flex';
                    startCountdown(20 * 60);
                }
            };
        });

        document.querySelectorAll('.btn-copy-pix-row').forEach(btn => {
            btn.onclick = async function () {
                const code = this.getAttribute('data-code');
                if (code && await copyToClipboard(code)) {
                    const icon = this.querySelector('i');
                    if (icon) {
                        const old = icon.className;
                        icon.className = 'fas fa-check';
                        this.style.color = '#22c55e';
                        setTimeout(() => { icon.className = old; this.style.color = ''; }, 2000);
                    }
                }
            };
        });

        document.querySelectorAll('.btn-delete-row').forEach(btn => {
            btn.onclick = function (e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const row = this.closest('tr');
                deleteTarget = { id, row };
                if (modalConfirm) {
                    modalConfirm.classList.remove('hidden');
                    modalConfirm.style.display = 'flex';
                }
            };
        });
    };

    // --- LOGICA MODAL CONFIRMAÇÃO ---

    if (btnConfirmCancel) {
        btnConfirmCancel.onclick = closeAllModals;
    }

    if (btnConfirmDelete) {
        btnConfirmDelete.onclick = async () => {
            if (!deleteTarget) return;
            const { id, row } = deleteTarget;

            btnConfirmDelete.innerText = 'Excluindo...';
            btnConfirmDelete.disabled = true;

            try {
                const res = await fetch('delete_transaction.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ id })
                });
                const d = await res.json();
                if (d.success) {
                    row.style.opacity = '0';
                    row.style.transform = 'scale(0.9)';
                    setTimeout(() => row.remove(), 300);
                    closeAllModals();
                }
            } catch (e) { alert("Erro de conexão"); }
            finally {
                btnConfirmDelete.innerText = 'Excluir';
                btnConfirmDelete.disabled = false;
                deleteTarget = null;
            }
        };
    }

    // --- LIVE DASHBOARD (AUTO-REFRESH) ---

    async function refreshDashboard() {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const period = urlParams.get('p') || '7d';
            const res = await fetch(`get_dashboard_data.php?p=${period}`);
            const data = await res.json();
            console.log("Dashboard data received:", data);
            if (!data.success) return;

            // Atualizar Saldo e Stats
            const elBalance = document.getElementById('stat-balance');
            const elToday = document.getElementById('stat-today');
            const elMonth = document.getElementById('stat-month');
            const elTotal = document.getElementById('stat-total');
            const elPending = document.getElementById('stat-pending');

            if (elBalance) elBalance.innerText = `R$ ${data.balance}`;
            if (elToday) elToday.innerText = `R$ ${data.stats.today_volume}`;
            if (elMonth) elMonth.innerText = `R$ ${data.stats.month_volume}`;
            if (elTotal) elTotal.innerText = `R$ ${data.stats.total_paid}`;
            if (elPending) elPending.innerText = data.stats.pending_count;

            // Atualizar Tabela
            const tableBody = document.querySelector('#transactions-table tbody');
            if (tableBody && data.transactions.length > 0) {
                let html = '';
                data.transactions.forEach(t => {
                    html += `
                    <tr class="responsive-row">
                        <td data-label="Data">
                            ${t.date}
                            <br><small style="color: var(--text-3); font-size: 0.75rem;">${t.customer_name || 'Sem nome'}</small>
                        </td>
                        <td data-label="Bruto">R$ ${t.amount_brl}</td>
                        <td data-label="Líquido">R$ ${t.amount_net_brl}</td>
                        <td data-label="Status"><span class="badge ${t.badge}">${t.status}</span></td>
                        <td class="actions-cell" data-label="Ações">
                            <div class="action-row">
                                <button class="btn-history-action btn-view-qr" 
                                        data-qr="${t.qr_image}" 
                                        data-code="${t.pix_code}"
                                        data-amount="${t.amount_brl}"
                                        title="Ver QR Code">
                                    <i class="fas fa-qrcode"></i>
                                </button>
                                <button class="btn-history-action btn-copy-pix-row" 
                                        data-code="${t.pix_code}"
                                        title="Copiar Pix">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <button class="btn-history-action btn-delete-row" 
                                        data-id="${t.id}"
                                        title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>`;
                });
                tableBody.innerHTML = html;
                window.initHistoryActions(); // Re-vincular eventos
            }

            // Atualizar Feed de Atividade
            const feedBody = document.getElementById('activity-feed');
            if (feedBody && data.notifications) {
                if (data.notifications.length === 0) {
                    feedBody.innerHTML = '<div style="text-align: center; padding: 1.5rem; opacity: 0.5; font-size: 0.85rem;">Nenhuma atividade recente.</div>';
                } else {
                    let feedHtml = '';
                    data.notifications.forEach(n => {
                        const icon = n.type === 'success' ? 'fa-circle-check' : 'fa-bolt';
                        const color = n.type === 'success' ? '#22c55e' : '#f97316';
                        feedHtml += `
                        <div class="feed-item" style="display: flex; gap: 12px; padding: 12px; border-radius: 12px; background: rgba(255,255,255,0.02); margin-bottom: 8px; border: 1px solid rgba(255,255,255,0.05); transition: all 0.3s ease;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: ${color}15; color: ${color}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fas ${icon}" style="font-size: 0.9rem;"></i>
                            </div>
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
                                    <strong style="font-size: 0.85rem; color: #fff;">${n.title}</strong>
                                    <span style="font-size: 0.75rem; color: rgba(255,255,255,0.3);">${n.time}</span>
                                </div>
                                <p style="font-size: 0.82rem; color: rgba(255,255,255,0.5); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;">${n.message}</p>
                            </div>
                        </div>`;
                    });
                    console.log("Updating activity feed with", data.notifications.length, "items");
                    feedBody.innerHTML = feedHtml;
                }
            } else {
                console.log("Activity feed element or notifications data missing:", { feedBody: !!feedBody, hasNotifs: !!data.notifications });
            }
        } catch (e) {
            console.error("Live update failed:", e);
        }
    }

    // Iniciar auto-refresh a cada 15 seg
    if (document.querySelector('.main-content')) {
        setInterval(() => {
            refreshDashboard();
            checkNotifications();
        }, 15000);
    }

    // --- WIDGET DE SUPORTE FLUTUANTE ---

    const supportWidgetHTML = `
    <style>
        @media (max-width: 768px) {
            #support-widget { bottom: 15px !important; right: 15px !important; }
            #support-btn { width: 50px !important; height: 50px !important; }
            #support-balloon { bottom: 60px !important; width: 280px !important; }
        }
    </style>
    <div id="support-widget" style="position: fixed; bottom: 30px; right: 30px; z-index: 9999;">
        <div id="support-balloon" class="glass" style="display: none; position: absolute; bottom: 70px; right: 0; width: 320px; padding: 1.8rem; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 20px 50px rgba(0,0,0,0.6); background: rgba(10,10,10,0.98); backdrop-filter: blur(20px);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem;">
                <h4 style="margin: 0; display: flex; align-items: center; gap: 10px; color: white; font-weight: 700;"><i class="fas fa-ghost" style="color: var(--primary);"></i> Suporte Ghost</h4>
                <button id="close-support" style="background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 1.4rem;">&times;</button>
            </div>
            <div id="support-content" style="font-size: 0.9rem; color: rgba(255,255,255,0.6); line-height: 1.5;">
                <p style="margin-bottom: 1.2rem;">Olá! Especialista Ghost online. Como podemos ajudar?</p>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button class="support-opt glass" data-ans="Para gerar um Pix, vá ao Dashboard, digite o valor e clique em 'Gerar QR Code'." style="text-align: left; padding: 12px; border-radius: 10px; font-size: 0.8rem; cursor: pointer; border: 1px solid rgba(255,255,255,0.08); color: white; background: rgba(255,255,255,0.03); transition: all 0.3s ease;">💡 Como gerar um Pix?</button>
                    <button class="support-opt glass" data-ans="O prazo médio para saques é de até 2 dias úteis." style="text-align: left; padding: 12px; border-radius: 10px; font-size: 0.8rem; cursor: pointer; border: 1px solid rgba(255,255,255,0.08); color: white; background: rgba(255,255,255,0.03); transition: all 0.3s ease;">💰 Qual o prazo de saque?</button>
                    <a href="https://wa.me/5551996148568" target="_blank" style="text-align: center; padding: 12px; border-radius: 10px; font-size: 0.85rem; cursor: pointer; background: var(--primary); color: black; font-weight: 800; text-decoration: none; margin-top: 10px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fab fa-whatsapp"></i> Falar com um Consultor
                    </a>
                </div>
                <div id="support-answer" style="display: none; margin-top: 1.2rem; padding: 12px; background: rgba(34, 197, 94, 0.05); border-radius: 10px; border-left: 4px solid var(--primary); color: #fff; font-size: 0.82rem; animation: fadeIn 0.3s ease;"></div>
            </div>
        </div>
        <button id="support-toggle" style="width: 60px; height: 60px; border-radius: 50%; background: var(--primary); border: none; color: black; font-size: 1.6rem; cursor: pointer; box-shadow: 0 10px 25px rgba(34, 197, 94, 0.3); display: flex; align-items: center; justify-content: center; transition: all 0.4s var(--spring);">
            <i class="fas fa-headset"></i>
        </button>
    </div>
    `;

    document.body.insertAdjacentHTML('beforeend', supportWidgetHTML);

    const supportToggle = document.getElementById('support-toggle');
    const supportBalloon = document.getElementById('support-balloon');
    const closeSupport = document.getElementById('close-support');
    const supportAnswer = document.getElementById('support-answer');

    if (supportToggle) {
        supportToggle.onclick = () => {
            const isHidden = supportBalloon.style.display === 'none' || supportBalloon.style.display === '';
            supportBalloon.style.display = isHidden ? 'block' : 'none';
            supportToggle.style.transform = isHidden ? 'scale(1.1) rotate(15deg)' : 'scale(1)';
        };
    }

    if (closeSupport) {
        closeSupport.onclick = () => {
            supportBalloon.style.display = 'none';
            supportToggle.style.transform = 'scale(1)';
        };
    }

    document.querySelectorAll('.support-opt').forEach(opt => {
        opt.onclick = () => {
            const ans = opt.getAttribute('data-ans');
            supportAnswer.innerText = ans;
            supportAnswer.style.display = 'block';
        };
    });

    // --- FAQ TOGGLE ---
    document.querySelectorAll('.faq-question').forEach(q => {
        q.onclick = () => {
            const item = q.parentElement;
            item.classList.toggle('active');
        };
    });

    // --- SISTEMA DE NOTIFICAÇÕES ---
    async function checkNotifications() {
        try {
            const res = await fetch('get_notifications.php');
            const data = await res.json();
            if (data.success && data.notifications.length > 0) {
                for (const n of data.notifications) {
                    showNotificationModal(n);
                    // Marcar como lida imediatamente após mostrar (ou após o OK)
                    await fetch('mark_read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: n.id })
                    });
                }
            }
        } catch (e) { console.warn("Erro ao buscar notificações:", e); }
    }

    function showNotificationModal(n) {
        const modalId = 'modal-notification-' + n.id;
        const color = n.type === 'success' ? '#4ade80' : (n.type === 'danger' ? '#ef4444' : (n.type === 'warning' ? '#f59e0b' : '#3b82f6'));

        const html = `
        <div id="${modalId}" class="modal-notification active" style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px;">
            <div class="glass" style="width: 100%; max-width: 400px; padding: 2rem; border-radius: 24px; border: 1px solid ${color}44; text-align: center; animation: modalIn 0.5s var(--spring);">
                <div style="font-size: 3rem; margin-bottom: 1rem; color: ${color};">
                    <i class="fas ${n.type === 'success' ? 'fa-check-circle' : 'fa-bell'}"></i>
                </div>
                <h2 style="margin-bottom: 1rem; font-size: 1.5rem; color: #fff;">${n.title}</h2>
                <p style="color: var(--text-2); line-height: 1.6; margin-bottom: 2rem;">${n.message}</p>
                <button onclick="document.getElementById('${modalId}').remove()" class="btn-primary" style="width: 100%; background: ${color}; color: ${n.type === 'success' || n.type === 'danger' ? '#fff' : '#000'}; border: none; padding: 12px; border-radius: 12px; font-weight: 700; cursor: pointer;">Entendi</button>
            </div>
        </div>
        `;
        document.body.insertAdjacentHTML('beforeend', html);
    }

    // Iniciar verificação se não estiver no admin
    if (!window.location.pathname.includes('/admin/')) {
        checkNotifications();
    }

    initHistoryActions();
});
