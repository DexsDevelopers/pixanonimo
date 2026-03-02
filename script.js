document.addEventListener('DOMContentLoaded', () => {
    console.log('Dashboard Script Carregado v1.4');

    // Elementos de Geração de Pix
    const btnGenerate = document.getElementById('btn-generate');
    const modalQr = document.getElementById('modal-qr');
    const closeModal = document.querySelector('.close-modal');
    const amountInput = document.getElementById('amount');
    const modalAmount = document.getElementById('modal-amount');
    const qrPlaceholder = document.querySelector('.qr-placeholder');

    if (btnGenerate && amountInput) {
        btnGenerate.addEventListener('click', async () => {
            const rawValue = amountInput.value || "";
            const value = rawValue.toString().replace(',', '.');
            const walletInput = document.getElementById('wallet-input');
            const wallet = walletInput ? walletInput.value : "";

            if (!wallet || wallet.trim() === "") {
                alert('Por favor, configure sua chave PIX antes de gerar um Pix.');
                return;
            }

            if (!value || parseFloat(value) <= 0) {
                alert('Por favor, insira um valor válido.');
                return;
            }

            btnGenerate.innerText = 'Processando...';
            btnGenerate.disabled = true;

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ amount: value, wallet: wallet })
                });

                const data = await response.json();

                if (data.error) {
                    alert('Erro: ' + data.error);
                } else {
                    const finalAmount = data.amount || value;
                    if (modalAmount) modalAmount.innerText = `R$ ${parseFloat(finalAmount).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;

                    if (qrPlaceholder) {
                        qrPlaceholder.innerHTML = `<img src="${data.qrCodeImage}" alt="QR Code Pix" style="width:100%">`;
                    }

                    const pixCodeText = document.getElementById('pix-code-text');
                    if (pixCodeText) {
                        pixCodeText.value = data.pix_code || "";
                    }

                    if (modalQr) modalQr.classList.remove('hidden');

                    // Iniciar Polling de Status
                    startPixPolling(data.pix_id);
                }
            } catch (err) {
                alert('Erro de conexão com o servidor.');
                console.error(err);
            } finally {
                btnGenerate.innerText = 'Gerar QR Code Pix';
                btnGenerate.disabled = false;
            }
        });
    }

    let statusInterval = null;
    function startPixPolling(pixId) {
        if (statusInterval) clearInterval(statusInterval);

        const startTime = Date.now();
        const expirationTime = 20 * 60 * 1000; // 20 minutos em ms

        statusInterval = setInterval(async () => {
            const now = Date.now();
            if (now - startTime > expirationTime) {
                clearInterval(statusInterval);
                if (qrPlaceholder) {
                    qrPlaceholder.innerHTML = `
                        <div style="color: var(--danger); font-size: 1.2rem; font-weight: bold; padding: 2rem;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
                            QR Code Expirado<br>
                            <span style="font-size: 0.9rem; color: var(--text-dim);">O tempo limite de 20 minutos acabou. Gere um novo Pix.</span>
                        </div>
                    `;
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
                            <div style="color: var(--primary); font-size: 1.2rem; font-weight: bold; padding: 2rem;">
                                <div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>
                                Pagamento Confirmado!<br>
                                <span style="font-size: 0.9rem; color: var(--text-dim);">Seu saldo será atualizado em instantes...</span>
                            </div>
                        `;
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                }
            } catch (e) {
                console.error("Erro no polling:", e);
            }
        }, 3000);
    }

    if (closeModal && modalQr) {
        closeModal.addEventListener('click', () => {
            modalQr.classList.add('hidden');
            if (statusInterval) clearInterval(statusInterval);
        });

        window.addEventListener('click', (e) => {
            if (e.target === modalQr) {
                modalQr.classList.add('hidden');
                if (statusInterval) clearInterval(statusInterval);
            }
        });
    }

    // Elementos de Carteira
    const btnEditWallet = document.getElementById('btn-edit-wallet');
    const btnSaveWallet = document.getElementById('btn-save-wallet');
    const btnCopyWallet = document.getElementById('btn-copy-wallet');
    const walletInput = document.getElementById('wallet-input');

    if (btnEditWallet && walletInput) {
        btnEditWallet.addEventListener('click', () => {
            walletInput.readOnly = false;
            walletInput.focus();
            walletInput.style.borderBottom = "1px solid var(--primary)";
            btnEditWallet.classList.add('hidden');
            if (btnSaveWallet) btnSaveWallet.classList.remove('hidden');
        });
    }

    if (btnSaveWallet && walletInput) {
        btnSaveWallet.addEventListener('click', async () => {
            const newWallet = walletInput.value.trim();
            if (!newWallet) return alert("Endereço não pode ser vazio");

            btnSaveWallet.disabled = true;
            try {
                const res = await fetch('update_wallet.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ wallet: newWallet })
                });
                const data = await res.json();
                if (data.success) {
                    alert("Carteira atualizada!");
                    walletInput.readOnly = true;
                    walletInput.style.borderBottom = "none";
                    if (btnEditWallet) btnEditWallet.classList.remove('hidden');
                    btnSaveWallet.classList.add('hidden');
                } else {
                    alert(data.error || "Erro ao salvar");
                }
            } catch (err) {
                alert("Erro ao conectar ao servidor");
            } finally {
                btnSaveWallet.disabled = false;
            }
        });
    }

    if (btnCopyWallet && walletInput) {
        btnCopyWallet.addEventListener('click', () => {
            const addr = walletInput.value;
            if (addr) {
                navigator.clipboard.writeText(addr);
                alert('Chave PIX copiada!');
            } else {
                alert('Nenhuma chave para copiar.');
            }
        });
    }

    const btnCopyPix = document.getElementById('btn-copy-pix');
    const pixCodeText = document.getElementById('pix-code-text');
    if (btnCopyPix && pixCodeText) {
        btnCopyPix.addEventListener('click', () => {
            const code = pixCodeText.value;
            if (code) {
                navigator.clipboard.writeText(code);
                const originalText = btnCopyPix.innerText;
                btnCopyPix.innerText = 'Copiado!';
                btnCopyPix.style.background = '#22c55e'; // Verde Ghost
                setTimeout(() => {
                    btnCopyPix.innerText = originalText;
                    btnCopyPix.style.background = '';
                }, 2000);
            }
        });
    }
    // Mobile Menu Toggle
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (menuToggle && sidebar && overlay) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });

        // Close menu when clicking on nav items (on mobile)
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                }
            });
        });
    }
});
