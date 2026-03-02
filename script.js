document.addEventListener('DOMContentLoaded', () => {
    console.log('Ghost Pix Dashboard Loaded v3.3');

    // --- ELEMENTOS GLOBAIS ---
    const btnGenerate = document.getElementById('btn-generate');
    const modalQr = document.getElementById('modal-qr');
    const closeModal = document.querySelector('.close-modal');
    const amountInput = document.getElementById('amount');
    const modalAmount = document.getElementById('modal-amount');
    const qrPlaceholder = document.querySelector('.qr-placeholder');
    const pixCodeText = document.getElementById('pix-code-text');
    const btnCopyPix = document.getElementById('btn-copy-pix');
    const btnEditWallet = document.getElementById('btn-edit-wallet');
    const btnSaveWallet = document.getElementById('btn-save-wallet');
    const btnCopyWallet = document.getElementById('btn-copy-wallet');
    const walletInput = document.getElementById('wallet-input');
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    let statusInterval = null;

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
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ amount: val })
                });

                const data = await response.json();
                console.log('Dados do Pix Recebidos:', data);

                if (data.error) {
                    alert('Erro: ' + data.error);
                } else if (data.success || data.status === 'success' || data.pix_id) {
                    // Preencher modal
                    const amountDisp = data.amount || val;
                    if (modalAmount) modalAmount.innerText = `R$ ${parseFloat(amountDisp).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;

                    // Mapeamento extra robusto
                    const code = data.pix_code || data.qr_code || data.payload || "";
                    if (pixCodeText) {
                        pixCodeText.value = code;
                        console.log('Código Pix atribuído ao sistema:', code ? 'SIM' : 'NÃO (Vazio)');
                    }

                    if (qrPlaceholder) {
                        const img = data.qr_image || data.qr_code_url || "";
                        qrPlaceholder.innerHTML = `<img src="${img}" alt="QR" style="width:100%; display:block; border-radius: 8px;">`;
                    }

                    // Mostrar modal
                    if (modalQr) {
                        modalQr.classList.remove('hidden');
                        modalQr.style.display = 'flex';
                    }

                    startPixPolling(data.pix_id);
                }
            } catch (err) {
                console.error(err);
                alert('Erro ao gerar PIX. Verifique sua conexão.');
            } finally {
                btnGenerate.innerText = 'Gerar QR Code Pix';
                btnGenerate.disabled = false;
            }
        };
    }

    // --- MODAL CLOSE ---

    if (closeModal && modalQr) {
        closeModal.onclick = () => {
            modalQr.classList.add('hidden');
            modalQr.style.display = 'none';
            if (statusInterval) clearInterval(statusInterval);
        };
        window.onclick = (e) => {
            if (e.target === modalQr) {
                modalQr.classList.add('hidden');
                modalQr.style.display = 'none';
                if (statusInterval) clearInterval(statusInterval);
            }
        };
    }

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
                const res = await fetch('update_wallet.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
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

    if (menuToggle && sidebar && overlay) {
        menuToggle.onclick = () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        };
        overlay.onclick = () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        };
    }

    // --- AÇÕES DO HISTÓRICO ---

    window.initHistoryActions = () => {
        document.querySelectorAll('.btn-view-qr').forEach(btn => {
            btn.onclick = function () {
                const qr = this.getAttribute('data-qr');
                const code = this.getAttribute('data-code');
                const amount = this.getAttribute('data-amount');

                if (qr && qr !== "") {
                    qrPlaceholder.innerHTML = `<img src="${qr}" alt="QR" style="width:100%; display:block; border-radius: 8px;">`;
                } else {
                    qrPlaceholder.innerHTML = `<div style="padding: 2rem; color: var(--text-dim); text-align: center;">QR indisponível (Legado)</div>`;
                }

                if (pixCodeText) pixCodeText.value = code || "";
                if (modalAmount) modalAmount.innerText = `R$ ${amount}`;
                if (modalQr) {
                    modalQr.classList.remove('hidden');
                    modalQr.style.display = 'flex';
                }
            };
        });

        document.querySelectorAll('.btn-copy-pix-row').forEach(btn => {
            btn.onclick = async function () {
                const code = this.getAttribute('data-code');
                if (code && await copyToClipboard(code)) {
                    const icon = this.querySelector('i');
                    const old = icon.className;
                    icon.className = 'fas fa-check';
                    this.style.color = '#22c55e';
                    setTimeout(() => { icon.className = old; this.style.color = ''; }, 2000);
                }
            };
        });

        document.querySelectorAll('.btn-delete-row').forEach(btn => {
            btn.onclick = async function () {
                if (!confirm('Excluir transação?')) return;
                const id = this.getAttribute('data-id');
                const row = this.closest('tr');
                try {
                    const res = await fetch('delete_transaction.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    });
                    const d = await res.json();
                    if (d.success) {
                        row.style.opacity = '0';
                        row.style.transform = 'scale(0.9)';
                        setTimeout(() => row.remove(), 300);
                    }
                } catch (e) { alert("Erro de conexão"); }
            };
        });
    };

    initHistoryActions();
});
