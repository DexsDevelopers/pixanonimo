import { useEffect, useState } from 'react';
import { Bell, BellOff, X } from 'lucide-react';
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
    const [subscribed, setSubscribed] = useState(false);
    const [denied, setDenied] = useState(false);

    useEffect(() => {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

        const init = async () => {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');
                const existing = await registration.pushManager.getSubscription();

                if (existing) {
                    setSubscribed(true);
                    return;
                }

                if (Notification.permission === 'denied') {
                    setDenied(true);
                    return;
                }

                if (Notification.permission === 'default') {
                    const dismissed = sessionStorage.getItem('push_prompt_dismissed');
                    if (!dismissed) {
                        setTimeout(() => setShowPrompt(true), 3000);
                    }
                }
            } catch (err) {
                console.warn('Push init error:', err);
            }
        };

        init();
    }, []);

    const subscribeToPush = async () => {
        try {
            const res = await fetch('/get_vapid_key.php');
            const data = await res.json();
            if (!data.success) return;

            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(data.publicKey)
            });

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

            setSubscribed(true);
            setShowPrompt(false);
        } catch (err) {
            console.error('Push subscribe error:', err);
            if (Notification.permission === 'denied') {
                setDenied(true);
            }
            setShowPrompt(false);
        }
    };

    const dismissPrompt = () => {
        setShowPrompt(false);
        sessionStorage.setItem('push_prompt_dismissed', '1');
    };

    if (subscribed || denied || !showPrompt) return null;

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
                            className="flex-1 h-10 bg-primary text-black rounded-xl font-black text-xs flex items-center justify-center gap-1.5 hover:scale-[1.02] active:scale-95 transition-all shadow-[0_10px_30px_rgba(74,222,128,0.2)]"
                        >
                            <Bell size={14} /> Ativar
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
