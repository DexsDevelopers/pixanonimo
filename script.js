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
                    if (modalAmount) modalAmount.innerText = `R$ ${parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;

                    if (qrPlaceholder) {
                        qrPlaceholder.innerHTML = `<img src="${data.qrCodeImage}" alt="QR Code Pix" style="width:100%">`;
                    }

                    if (modalQr) modalQr.classList.remove('hidden');
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

    if (closeModal && modalQr) {
        closeModal.addEventListener('click', () => {
            modalQr.classList.add('hidden');
        });

        window.addEventListener('click', (e) => {
            if (e.target === modalQr) {
                modalQr.classList.add('hidden');
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
                alert('Endereço copiado!');
            } else {
                alert('Nenhuma carteira para copiar.');
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
