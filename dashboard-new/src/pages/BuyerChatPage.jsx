import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useParams } from 'react-router-dom';
import { MessageCircle, Send, CheckCheck, Package, Store, Clock, RefreshCw, Lock, Crown } from 'lucide-react';
import { cn } from '../lib/utils';

function formatTime(dateStr) {
    return new Date(dateStr).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: '2-digit' });
}

export default function BuyerChatPage() {
    const { token } = useParams();
    const [room, setRoom] = useState(null);
    const [messages, setMessages] = useState([]);
    const [msgInput, setMsgInput] = useState('');
    const [sending, setSending] = useState(false);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const messagesEndRef = useRef(null);
    const lastMsgId = useRef(0);
    const pollRef = useRef(null);

    const fetchChat = useCallback(async (after = 0) => {
        try {
            const res = await fetch(`../chat_api.php?action=buyer_get&token=${token}&after=${after}`);
            const data = await res.json();
            if (data.success) {
                setRoom(data.room);
                if (after > 0 && data.messages.length > 0) {
                    setMessages(prev => [...prev, ...data.messages]);
                } else if (after === 0) {
                    setMessages(data.messages);
                }
                if (data.messages.length > 0) {
                    lastMsgId.current = Math.max(...data.messages.map(m => m.id));
                }
                setError(null);
            } else {
                setError(data.error || 'Chat não encontrado');
            }
        } catch {
            setError('Erro de conexão');
        }
    }, [token]);

    useEffect(() => {
        setLoading(true);
        fetchChat().finally(() => setLoading(false));
        pollRef.current = setInterval(() => fetchChat(lastMsgId.current), 3000);
        return () => { if (pollRef.current) clearInterval(pollRef.current); };
    }, [fetchChat]);

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    const sendMessage = async () => {
        if (!msgInput.trim() || sending) return;
        setSending(true);
        try {
            const res = await fetch('../chat_api.php?action=buyer_send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token, message: msgInput.trim() }),
            });
            const data = await res.json();
            if (data.success) {
                setMsgInput('');
                await fetchChat(lastMsgId.current);
            } else {
                alert(data.error || 'Erro ao enviar');
            }
        } catch {}
        setSending(false);
    };

    if (loading) {
        return (
            <div className="min-h-screen bg-[#08080a] flex items-center justify-center">
                <RefreshCw className="animate-spin text-emerald-400" size={32} />
            </div>
        );
    }

    if (error) {
        return (
            <div className="min-h-screen bg-[#08080a] flex items-center justify-center text-white font-['Outfit']">
                <div className="text-center p-6">
                    <Lock size={48} className="text-white/10 mx-auto mb-4" />
                    <h2 className="text-xl font-black mb-2">Chat Indisponível</h2>
                    <p className="text-white/40 text-sm">{error}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-[#08080a] flex flex-col text-white font-['Outfit']">
            {/* Header */}
            <div className="bg-[#111] border-b border-white/5 p-4 flex items-center gap-3">
                <div className="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center shrink-0">
                    <Store size={18} className="text-emerald-400" />
                </div>
                <div className="flex-1 min-w-0">
                    <h1 className="text-sm font-bold truncate">{room?.store_name || room?.seller_name}</h1>
                    <p className="text-[11px] text-white/30 truncate flex items-center gap-1">
                        <Package size={10} />
                        {room?.product_name || 'Pedido'}
                    </p>
                </div>
                <span className={cn(
                    "px-2.5 py-1 rounded-lg text-[9px] font-black uppercase shrink-0",
                    room?.status === 'open' ? 'bg-emerald-500/15 text-emerald-400' : 'bg-white/5 text-white/30'
                )}>{room?.status === 'open' ? 'Online' : 'Encerrado'}</span>
            </div>

            {/* Buyer info badge */}
            <div className="px-4 py-2 bg-white/[0.02] border-b border-white/5">
                <div className="flex items-center gap-2 text-[11px] text-white/30">
                    <span className="text-white/50 font-bold">Você: {room?.buyer_name}</span>
                    <span>•</span>
                    <Clock size={10} />
                    <span>{room?.created_at && formatDate(room.created_at)}</span>
                </div>
            </div>

            {/* Messages */}
            <div className="flex-1 overflow-y-auto p-4 space-y-3">
                {messages.length === 0 && (
                    <div className="text-center py-16">
                        <MessageCircle size={40} className="text-white/5 mx-auto mb-3" />
                        <p className="text-white/20 font-bold text-sm">Nenhuma mensagem ainda</p>
                        <p className="text-white/10 text-xs mt-1">Envie uma mensagem para o vendedor</p>
                    </div>
                )}
                {messages.map((msg, i) => {
                    const isBuyer = msg.sender_type === 'buyer';
                    const isAdmin = msg.sender_type === 'admin';
                    const showDateSep = i === 0 || formatDate(messages[i-1].created_at) !== formatDate(msg.created_at);
                    return (
                        <React.Fragment key={msg.id}>
                            {showDateSep && (
                                <div className="text-center py-2">
                                    <span className="text-[10px] bg-white/5 text-white/25 px-3 py-1 rounded-full font-bold">{formatDate(msg.created_at)}</span>
                                </div>
                            )}
                            <div className={cn("flex", isBuyer ? "justify-end" : "justify-start")}>
                                <div className={cn(
                                    "max-w-[85%] rounded-2xl px-4 py-2.5",
                                    isBuyer
                                        ? "bg-emerald-500/15 border border-emerald-500/20"
                                        : isAdmin
                                            ? "bg-amber-500/15 border border-amber-500/20"
                                            : "bg-white/[0.06] border border-white/[0.08]"
                                )}>
                                    <div className="flex items-center gap-1.5 mb-0.5">
                                        {isAdmin && <Crown size={9} className="text-amber-400" />}
                                        <span className={cn(
                                            "text-[10px] font-black uppercase",
                                            isBuyer ? 'text-emerald-400' : isAdmin ? 'text-amber-400' : 'text-primary'
                                        )}>{msg.sender_name}</span>
                                    </div>
                                    <p className="text-[13px] text-white/90 leading-relaxed whitespace-pre-wrap break-words">{msg.message}</p>
                                    <div className={cn("flex items-center gap-1 mt-1", isBuyer ? "justify-end" : "justify-start")}>
                                        <span className="text-[9px] text-white/20">{formatTime(msg.created_at)}</span>
                                        {isBuyer && <CheckCheck size={10} className={msg.read_at ? "text-emerald-400" : "text-white/15"} />}
                                    </div>
                                </div>
                            </div>
                        </React.Fragment>
                    );
                })}
                <div ref={messagesEndRef} />
            </div>

            {/* Input */}
            {room?.status === 'open' ? (
                <div className="p-3 border-t border-white/5 bg-[#111]">
                    <form onSubmit={e => { e.preventDefault(); sendMessage(); }} className="flex gap-2">
                        <input
                            value={msgInput}
                            onChange={e => setMsgInput(e.target.value)}
                            placeholder="Escreva sua mensagem..."
                            maxLength={2000}
                            className="flex-1 bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-sm focus:outline-none focus:border-emerald-500/40 transition-colors"
                        />
                        <button
                            type="submit"
                            disabled={sending || !msgInput.trim()}
                            className="px-4 bg-emerald-500 text-black rounded-xl font-black text-sm flex items-center gap-2 hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-30"
                        >
                            <Send size={16} />
                        </button>
                    </form>
                </div>
            ) : (
                <div className="p-4 border-t border-white/5 text-center bg-[#111]">
                    <p className="text-white/25 text-xs font-bold">Este chat foi encerrado pelo vendedor.</p>
                </div>
            )}
        </div>
    );
}
