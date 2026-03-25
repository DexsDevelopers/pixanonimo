import { useEffect, useState } from 'react';
import { Bell, X, Loader2, CheckCircle } from 'lucide-react';
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

async function registerAndSubscribe() {
    // 1. Registrar Service Worker
    console.log('[Push] Registrando Service Worker...');
    const reg = await navigator.serviceWorker.register('/sw.js', { scope: '/' });

    // 2. Esperar SW ficar ativo
    const registration = await navigator.serviceWorker.ready;
    console.log('[Push] SW ativo:', registration.active?.state);

    // 3. Pedir permissão EXPLICITAMENTE
    const permission = await Notification.requestPermission();
    console.log('[Push] Permissão:', permission);
    if (permission !== 'granted') {
        throw new Error('Permissão negada pelo usuário');
    }

    // 4. Verificar subscription existente
    let subscription = await registration.pushManager.getSubscription();
    
    if (!subscription) {
        // 5. Buscar chave VAPID do servidor
        console.log('[Push] Buscando VAPID key...');
        const res = await fetch('/get_vapid_key.php');
        const vapidData = await res.json();
        console.log('[Push] VAPID response:', vapidData);
        
        if (!vapidData.success || !vapidData.publicKey) {
            throw new Error('Chave VAPID não disponível no servidor');
        }

        // 6. Criar subscription push
        console.log('[Push] Subscrevendo push...');
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidData.publicKey)
        });
    }

    // 7. Enviar subscription pro backend
    const subJson = subscription.toJSON();
    console.log('[Push] Salvando subscription no backend...');
    const saveRes = await fetch('/save_subscription.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            endpoint: subJson.endpoint,
            keys: {
                p256dh: subJson.keys.p256dh,
                auth: subJson.keys.auth
            }
        })
    });
    const saveData = await saveRes.json();
    console.log('[Push] Save response:', saveData);

    // 8. Enviar notificação de teste via backend (push real)
    console.log('[Push] Enviando push de teste...');
    await fetch('/send_test_push.php', { method: 'POST' });

    return true;
}

export default function PushManager() {
    const [showPrompt, setShowPrompt] = useState(false);
    const [loading, setLoading] = useState(false);
    const [success, setSuccess] = useState(false);
    const [hidden, setHidden] = useState(() => {
        return localStorage.getItem('push_subscribed') === '1' || 
               localStorage.getItem('push_prompt_dismissed') === '1';
    });

    useEffect(() => {
        if (hidden) return;
        if (!('serviceWorker' in navigator)) return;
        if (!('PushManager' in window) && !('Notification' in window)) return;

        // Se já deu permissão e tem subscription, registra SW silenciosamente
        if (Notification.permission === 'granted') {
            navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
            localStorage.setItem('push_prompt_dismissed', '1');
            setHidden(true);
            return;
        }

        if (Notification.permission === 'denied') {
            setHidden(true);
            return;
        }

        // Mostrar prompt após 3s
        const timer = setTimeout(() => setShowPrompt(true), 3000);
        return () => clearTimeout(timer);
    }, [hidden]);

    const handleActivate = async () => {
        setLoading(true);
        try {
            await registerAndSubscribe();
            setSuccess(true);
            localStorage.setItem('push_subscribed', '1');
            localStorage.setItem('push_prompt_dismissed', '1');
            // Esconder após mostrar sucesso
            setTimeout(() => {
                setShowPrompt(false);
                setHidden(true);
            }, 2500);
        } catch (err) {
            console.error('[Push] Erro:', err);
            // Esconde de qualquer forma para não irritar
            localStorage.setItem('push_prompt_dismissed', '1');
            setShowPrompt(false);
            setHidden(true);
        } finally {
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
                    {success ? (
                        <div className="flex flex-col items-center text-center gap-3 py-2">
                            <div className="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center border border-primary/20">
                                <CheckCircle size={22} className="text-primary" />
                            </div>
                            <div>
                                <h3 className="text-sm font-black text-white mb-1">Notificações Ativadas!</h3>
                                <p className="text-[11px] text-white/40">Você receberá uma notificação de teste agora.</p>
                            </div>
                        </div>
                    ) : (
                        <>
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
                                    onClick={handleActivate}
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
                        </>
                    )}
                </div>
            </motion.div>
        </AnimatePresence>
    );
}
