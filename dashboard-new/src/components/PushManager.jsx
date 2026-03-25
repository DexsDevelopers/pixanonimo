import { useEffect, useState } from 'react';
import { Bell, BellOff, X, Loader2 } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

export default function PushManager() {
    const [showPrompt, setShowPrompt] = useState(false);
    const [loading, setLoading] = useState(false);
    const [hidden, setHidden] = useState(() => {
        return localStorage.getItem('push_subscribed') === '1' || localStorage.getItem('push_prompt_dismissed') === '1';
    });

    useEffect(() => {
        if (hidden) return;
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
        if (typeof Notification !== 'undefined' && Notification.permission === 'denied') return;
        if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
            localStorage.setItem('push_prompt_dismissed', '1');
            return;
        }

        const timer = setTimeout(() => setShowPrompt(true), 3000);
        return () => clearTimeout(timer);
    }, [hidden]);

    const subscribeToPush = async () => {
        setLoading(true);
        try {
            // 1. Registrar SW
            const registration = await navigator.serviceWorker.register('/sw.js');
            await navigator.serviceWorker.ready;

            // 2. Buscar chave VAPID
            const res = await fetch('/get_vapid_key.php');
            const data = await res.json();
            if (!data.success || !data.publicKey) {
                throw new Error('VAPID key not available');
            }

            // 3. Subscrever push
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(data.publicKey)
            });

            // 4. Salvar no backend
            const sub = subscription.toJSON();
            await fetch('/save_subscription.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    endpoint: sub.endpoint,
                    keys: {
                        p256dh: sub.keys.p256dh,
                        auth: sub.keys.auth
                    }
                })
            });

            // 5. Notificação de confirmação
            registration.showNotification('Ghost Pix', {
                body: 'Notificações ativadas! Você receberá alertas de pagamentos e avisos importantes.',
                icon: '/logo_premium.png',
                badge: '/logo_premium.png',
                vibrate: [100, 50, 100]
            });

            localStorage.setItem('push_subscribed', '1');
        } catch (err) {
            console.error('Push subscribe error:', err);
        } finally {
            // Sempre esconde o prompt, independente de sucesso ou erro
            localStorage.setItem('push_prompt_dismissed', '1');
            setShowPrompt(false);
            setHidden(true);
            setLoading(false);
        }
    };

    const dismissPrompt = () => {
        localStorage.setItem('push_prompt_dismissed', '1');
        setShowPrompt(false);
        setHidden(true);
    };

    if (hidden || !showPrompt) return null;

    return (
        <AnimatePresence>
            <motion.div
                initial={{ opacity: 0, y: 50, scale: 0.95 }}
                animate={{ opacity: 1, y: 0, scale: 1 }}
                exit={{ opacity: 0, y: 50, scale: 0.95 }}
                className="fixed bottom-6 right-6 z-[9999] w-[340px] bg-[#111113] border border-white/10 rounded-[24px] shadow-2xl shadow-black/60 overflow-hidden"
            >
                <div className="p-6 space-y-4">
                    <div className="flex items-start justify-between">
                        <div className="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center border border-primary/20">
                            <Bell size={22} className="text-primary" />
                        </div>
                        <button onClick={dismissPrompt} className="p-1 hover:bg-white/5 rounded-lg transition-colors">
                            <X size={16} className="text-white/40" />
                        </button>
                    </div>

                    <div>
                        <h3 className="text-sm font-black text-white mb-1">Ativar Notificações</h3>
                        <p className="text-[11px] text-white/40 leading-relaxed">
                            Receba alertas de pagamentos confirmados, saques e avisos importantes em tempo real no seu dispositivo.
                        </p>
                    </div>

                    <div className="flex gap-2">
                        <button
                            onClick={subscribeToPush}
                            disabled={loading}
                            className="flex-1 h-10 bg-primary text-black rounded-xl font-black text-xs flex items-center justify-center gap-1.5 hover:scale-[1.02] active:scale-95 transition-all shadow-[0_10px_30px_rgba(74,222,128,0.2)] disabled:opacity-50"
                        >
                            {loading ? <Loader2 size={14} className="animate-spin" /> : <Bell size={14} />}
                            {loading ? 'Ativando...' : 'Ativar'}
                        </button>
                        <button
                            onClick={dismissPrompt}
                            className="flex-1 h-10 bg-white/5 border border-white/10 rounded-xl font-black text-xs text-white/60 hover:bg-white/10 transition-all"
                        >
                            Agora não
                        </button>
                    </div>
                </div>
            </motion.div>
        </AnimatePresence>
    );
}
