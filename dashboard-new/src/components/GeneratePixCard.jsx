import React, { useState } from 'react';
import { QrCode, Bolt, CheckCircle } from 'lucide-react';
import { motion } from 'framer-motion';

export default function GeneratePixCard({ onGenerate, disabled = false }) {
    const [amount, setAmount] = useState('');
    const [loading, setLoading] = useState(false);

    const handleGenerate = async () => {
        const val = amount.toString().replace(',', '.');
        if (!val || parseFloat(val) < 10) {
            alert('Valor mínimo: R$ 10,00');
            return;
        }

        setLoading(true);
        try {
            // O CSRF token pode ser passado via meta tag se necessário, 
            // ou apenas o fetch normal se o site não estiver usando validação rígida de origem no api.php
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch('../api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken || ''
                },
                body: JSON.stringify({ amount: val })
            });

            const data = await response.json();

            if (data.error) {
                alert('Erro: ' + data.error);
            } else if (data.success || data.status === 'success' || data.pix_id) {
                onGenerate({
                    id: data.pix_id,
                    amount: data.amount || val,
                    code: data.pix_code || data.qr_code || data.payload || data.qrcodepix || '',
                    image: data.qr_image || data.qr_code_url || ''
                });
            }
        } catch (err) {
            console.error('Fetch Error:', err);
            alert('Falha ao gerar Pix. Verifique sua conexão.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="glass p-6 rounded-2xl border border-white/5 relative overflow-hidden"
        >
            <div className="flex items-center gap-3 mb-6">
                <div className="p-2.5 rounded-xl bg-primary/10 text-primary border border-primary/20">
                    <QrCode size={20} />
                </div>
                <h3 className="font-bold text-white">Gerar Cobrança Pix</h3>
            </div>

            <div className="relative mb-4">
                <span className="absolute left-4 top-1/2 -translate-y-1/2 text-white/40 font-medium">R$</span>
                <input
                    type="number"
                    value={amount}
                    onChange={(e) => setAmount(e.target.value)}
                    placeholder="0,00"
                    step="0.01"
                    min="10"
                    disabled={disabled || loading}
                    className="w-full bg-white/5 border border-white/10 rounded-xl py-4 pl-12 pr-4 text-white font-bold text-2xl focus:outline-none focus:border-primary/50 transition-all placeholder:text-white/10"
                />
            </div>

            <p className="text-white/40 text-xs mb-6 px-1">Valor mínimo sugerido: R$ 10,00</p>

            <button
                onClick={handleGenerate}
                disabled={disabled || loading || !amount}
                className="w-full bg-primary hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed text-black font-bold py-4 rounded-xl flex items-center justify-center gap-2 transition-all active:scale-[0.98]"
            >
                {loading ? (
                    <div className="w-5 h-5 border-2 border-black/20 border-t-black rounded-full animate-spin" />
                ) : (
                    <>
                        <Bolt size={18} fill="currentColor" />
                        Gerar Agora
                    </>
                )}
            </button>

            <div className="mt-6 space-y-2 border-t border-white/5 pt-6">
                <div className="flex items-center gap-2 text-white/30 text-xs justify-center">
                    <CheckCircle size={14} className="text-primary/50" />
                    <span>Confirmado pelo Banco Central</span>
                </div>
                <p className="text-white/20 text-[10px] text-center">Crédito imediato após confirmação do pagamento.</p>
            </div>
        </motion.div>
    );
}
