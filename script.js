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
        const wallet = document.getElementById('wallet-address').innerText;

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

    // Lógica de "Copiar Endereço"
    document.querySelector('.btn-icon').addEventListener('click', () => {
        const addr = document.getElementById('wallet-address').innerText;
        navigator.clipboard.writeText(addr);
        alert('Endereço copiado!');
    });
});
