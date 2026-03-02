document.addEventListener('DOMContentLoaded', () => {
    const btnGenerate = document.getElementById('btn-generate');
    const modalQr = document.getElementById('modal-qr');
    const closeModal = document.querySelector('.close-modal');
    const amountInput = document.getElementById('amount');
    const modalAmount = document.getElementById('modal-amount');
    const transactionHistory = document.getElementById('transaction-history');

    // Gerar Pix via Backend PHP
    btnGenerate.addEventListener('click', async () => {
        const value = amountInput.value;
        const wallet = document.getElementById('wallet-input').value;

        if (!wallet || wallet.trim() === "") {
            alert('Por favor, configure sua carteira Liquid antes de gerar um Pix.');
            return;
        }

        if (!value || value <= 0) {
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
                modalAmount.innerText = `R$ ${parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;

                // Mostrar o QR Code retornado pela API
                const qrContainer = document.querySelector('.qr-placeholder');
                qrContainer.innerHTML = `<img src="${data.qrCodeImage}" alt="QR Code Pix" style="width:100%">`;

                modalQr.classList.remove('hidden');
            }
        } catch (err) {
            alert('Erro de conexão com o servidor.');
            console.error(err);
        } finally {
            btnGenerate.innerText = 'Gerar QR Code Pix';
            btnGenerate.disabled = false;
        }
    });

    closeModal.addEventListener('click', () => {
        modalQr.classList.add('hidden');
    });

    // Fechar ao clicar fora
    window.addEventListener('click', (e) => {
        if (e.target === modalQr) {
            modalQr.classList.add('hidden');
        }
    });

    // Lógica de Edição de Carteira
    const btnEditWallet = document.getElementById('btn-edit-wallet');
    const btnSaveWallet = document.getElementById('btn-save-wallet');
    const btnCopyWallet = document.getElementById('btn-copy-wallet');
    const walletInput = document.getElementById('wallet-input');

    if (btnEditWallet) {
        btnEditWallet.addEventListener('click', () => {
            walletInput.readOnly = false;
            walletInput.focus();
            walletInput.style.borderBottom = "1px solid var(--primary)";
            btnEditWallet.classList.add('hidden');
            btnSaveWallet.classList.remove('hidden');
        });
    }

    if (btnSaveWallet) {
        btnSaveWallet.addEventListener('click', async () => {
            const newWallet = walletInput.value.trim();
            if (!newWallet) return alert("Endereço não pode ser vazio");

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
                    btnEditWallet.classList.remove('hidden');
                    btnSaveWallet.classList.add('hidden');
                } else {
                    alert(data.error || "Erro ao salvar");
                }
            } catch (err) {
                alert("Erro ao conectar ao servidor");
            }
        });
    }

    if (btnCopyWallet) {
        btnCopyWallet.addEventListener('click', () => {
            const addr = walletInput.value;
            if (addr) {
                navigator.clipboard.writeText(addr);
                alert('Endereço copiado!');
            }
        });
    }
});
