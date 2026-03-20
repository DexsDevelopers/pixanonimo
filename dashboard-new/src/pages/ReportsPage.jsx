import React, { useState } from 'react';
import {
    BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
    AreaChart, Area, PieChart, Pie, Cell
} from 'recharts';
import {
    TrendingUp, Users, DollarSign,
    ShoppingBag, ArrowUpRight,
    ArrowDownRight, Filter, Download, BarChart3, Monitor, Smartphone
} from 'lucide-react';

const salesData = [
    { name: '01/02', sales: 4000, orders: 120 },
    { name: '02/02', sales: 3000, orders: 98 },
    { name: '03/02', sales: 2000, orders: 86 },
    { name: '04/02', sales: 2780, orders: 99 },
    { name: '05/02', sales: 1890, orders: 75 },
    { name: '06/02', sales: 2390, orders: 110 },
    { name: '07/02', sales: 3490, orders: 145 },
];

const convData = [
    { name: '01/02', conv: 2.4 },
    { name: '02/02', conv: 1.8 },
    { name: '03/02', conv: 1.2 },
    { name: '04/02', conv: 2.1 },
    { name: '05/02', conv: 1.5 },
    { name: '06/02', conv: 1.9 },
    { name: '07/02', conv: 2.8 },
];

const productData = [
    { name: 'E-book Digital', value: 400, color: '#a78bfa' },
    { name: 'Mentoria Express', value: 300, color: '#818cf8' },
    { name: 'Pack de Ativos', value: 200, color: '#c084fc' },
    { name: 'Upgrade Premium', value: 100, color: '#f472b6' },
];

const CustomTooltip = ({ active, payload, label }) => {
    if (active && payload && payload.length) {
        return (
            <div className="bg-[#1a1a2e] border border-white/10 rounded-2xl px-4 py-3 shadow-xl shadow-black/40">
                <p className="text-white/50 text-xs font-semibold mb-1">{label}</p>
                {payload.map((entry, i) => (
                    <p key={i} className="text-white font-bold text-sm">
                        {entry.name === 'sales' ? `R$ ${entry.value.toLocaleString('pt-BR')}` : `${entry.value}%`}
                    </p>
                ))}
            </div>
        );
    }
    return null;
};

export default function ReportsPage() {
    const [dateRange, setDateRange] = useState('7d');

    return (
        <div className="max-w-7xl mx-auto space-y-8 animate-in fade-in duration-500">
            {/* Header */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 className="text-3xl font-black tracking-tight text-white flex items-center gap-3">
                        <BarChart3 className="text-primary" size={32} />
                        Relatórios <span className="text-primary italic">Analíticos</span>
                    </h1>
                    <p className="text-white/40 font-medium">Acompanhe o desempenho das suas vendas com precisão.</p>
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <div className="flex bg-white/[0.03] rounded-full border border-white/10 p-1">
                        {['7d', '30d', '90d', 'Anual'].map((range) => (
                            <button
                                key={range}
                                onClick={() => setDateRange(range)}
                                className={`px-4 py-1.5 text-sm font-bold rounded-full transition-all duration-200 ${
                                    dateRange === range
                                        ? 'bg-primary text-black shadow-lg shadow-primary/30'
                                        : 'text-white/50 hover:text-white/80'
                                }`}
                            >
                                {range}
                            </button>
                        ))}
                    </div>

                    <button className="bg-white/5 hover:bg-white/10 border border-white/10 rounded-full p-3 transition-all" title="Filtros">
                        <Filter size={18} className="text-white/60" />
                    </button>
                    <button className="bg-white/5 hover:bg-white/10 border border-white/10 rounded-full p-3 transition-all" title="Exportar">
                        <Download size={18} className="text-white/60" />
                    </button>
                </div>
            </div>

            {/* Metric Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                <MetricCard
                    title="Volume Transacionado"
                    value="R$ 19.540"
                    change="+12.5%"
                    isUp={true}
                    icon={<DollarSign size={22} />}
                />
                <MetricCard
                    title="Custo de Taxas"
                    value="R$ 586,20"
                    change="-2.1%"
                    isUp={false}
                    icon={<TrendingUp size={22} />}
                />
                <MetricCard
                    title="Taxa de Conversão"
                    value="2.4%"
                    change="+0.8%"
                    isUp={true}
                    icon={<Users size={22} />}
                />
                <MetricCard
                    title="Vendas Realizadas"
                    value="634"
                    change="+18.3%"
                    isUp={true}
                    icon={<ShoppingBag size={22} />}
                />
            </div>

            {/* Charts Row */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Main Area Chart */}
                <div className="lg:col-span-2 bg-[#0a0a0b]/50 rounded-[32px] border border-white/5 backdrop-blur-md p-6">
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <h3 className="text-lg font-black text-white">Histórico de Faturamento</h3>
                            <p className="text-white/30 text-sm font-medium">Desempenho financeiro diário</p>
                        </div>
                        <div className="flex items-center gap-2 text-xs font-bold text-white/40">
                            <div className="w-2.5 h-2.5 rounded-full bg-primary"></div>
                            Vendas (R$)
                        </div>
                    </div>

                    <div className="h-[320px] w-full">
                        <ResponsiveContainer width="100%" height="100%">
                            <AreaChart data={salesData}>
                                <defs>
                                    <linearGradient id="colorSales" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor="#a78bfa" stopOpacity={0.25} />
                                        <stop offset="95%" stopColor="#a78bfa" stopOpacity={0} />
                                    </linearGradient>
                                </defs>
                                <CartesianGrid vertical={false} strokeDasharray="3 3" stroke="rgba(255,255,255,0.04)" />
                                <XAxis
                                    dataKey="name"
                                    axisLine={false}
                                    tickLine={false}
                                    tick={{ fill: 'rgba(255,255,255,0.3)', fontSize: 12, fontWeight: 600 }}
                                    dy={10}
                                />
                                <YAxis
                                    axisLine={false}
                                    tickLine={false}
                                    tick={{ fill: 'rgba(255,255,255,0.3)', fontSize: 12, fontWeight: 600 }}
                                    dx={-10}
                                    tickFormatter={(v) => `R$ ${v}`}
                                />
                                <Tooltip content={<CustomTooltip />} />
                                <Area
                                    type="monotone"
                                    dataKey="sales"
                                    stroke="#a78bfa"
                                    strokeWidth={3}
                                    fillOpacity={1}
                                    fill="url(#colorSales)"
                                />
                            </AreaChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                {/* Conversion Chart */}
                <div className="bg-[#0a0a0b]/50 rounded-[32px] border border-white/5 backdrop-blur-md p-6 flex flex-col">
                    <div className="mb-6">
                        <h3 className="text-lg font-black text-white">Efetividade de Vendas</h3>
                        <p className="text-white/30 text-sm font-medium">Taxa de conversão por dia</p>
                    </div>

                    <div className="h-[220px] w-full">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={convData}>
                                <CartesianGrid vertical={false} strokeDasharray="3 3" stroke="rgba(255,255,255,0.04)" />
                                <XAxis
                                    dataKey="name"
                                    axisLine={false}
                                    tickLine={false}
                                    tick={{ fill: 'rgba(255,255,255,0.3)', fontSize: 11, fontWeight: 600 }}
                                />
                                <YAxis hide />
                                <Tooltip content={<CustomTooltip />} />
                                <Bar
                                    dataKey="conv"
                                    fill="#818cf8"
                                    radius={[6, 6, 6, 6]}
                                    barSize={18}
                                />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>

                    <div className="mt-auto pt-5 border-t border-white/5 grid grid-cols-2 gap-3">
                        <div className="p-3 bg-white/[0.03] rounded-2xl text-center border border-white/5">
                            <p className="text-[11px] font-bold text-white/40 mb-0.5">Total Pedidos</p>
                            <p className="text-xl font-black text-white">842</p>
                        </div>
                        <div className="p-3 bg-white/[0.03] rounded-2xl text-center border border-white/5">
                            <p className="text-[11px] font-bold text-white/40 mb-0.5">Ticket Médio</p>
                            <p className="text-xl font-black text-white">R$ 49</p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Bottom Row */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Pie Chart */}
                <div className="bg-[#0a0a0b]/50 rounded-[32px] border border-white/5 backdrop-blur-md p-6">
                    <h3 className="text-lg font-black text-white mb-4">Mix de Produtos</h3>
                    <div className="h-[250px] w-full relative">
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie
                                    data={productData}
                                    cx="50%"
                                    cy="50%"
                                    innerRadius={65}
                                    outerRadius={95}
                                    paddingAngle={6}
                                    dataKey="value"
                                    stroke="none"
                                >
                                    {productData.map((entry, index) => (
                                        <Cell key={`cell-${index}`} fill={entry.color} />
                                    ))}
                                </Pie>
                                <Tooltip
                                    contentStyle={{
                                        backgroundColor: '#1a1a2e',
                                        border: '1px solid rgba(255,255,255,0.1)',
                                        borderRadius: '16px',
                                    }}
                                    itemStyle={{ color: '#fff' }}
                                />
                            </PieChart>
                        </ResponsiveContainer>
                        <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div className="text-center">
                                <p className="text-[10px] font-black text-white/30 uppercase tracking-widest">Receita</p>
                                <p className="text-lg font-black text-white">100%</p>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-2.5 mt-3">
                        {productData.map((item, i) => (
                            <div key={i} className="flex items-center justify-between">
                                <div className="flex items-center gap-2.5">
                                    <div className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: item.color }}></div>
                                    <span className="text-sm font-semibold text-white/60">{item.name}</span>
                                </div>
                                <span className="text-sm font-black text-white">{((item.value / 1000) * 100).toFixed(0)}%</span>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Devices & Channels */}
                <div className="lg:col-span-2 bg-[#0a0a0b]/50 rounded-[32px] border border-white/5 backdrop-blur-md p-6">
                    <h3 className="text-lg font-black text-white mb-8">Dispositivos & Origem</h3>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <div className="space-y-5">
                            <h4 className="flex items-center gap-2 text-xs font-black text-white/30 uppercase tracking-widest">
                                <Monitor size={14} />
                                Dispositivo
                            </h4>
                            <div className="space-y-4">
                                <DeviceRow label="Celular (Mobile)" percentage={68} />
                                <DeviceRow label="Desktop / PC" percentage={24} />
                                <DeviceRow label="Tablet" percentage={8} />
                            </div>
                        </div>

                        <div className="space-y-5">
                            <h4 className="flex items-center gap-2 text-xs font-black text-white/30 uppercase tracking-widest">
                                <Users size={14} />
                                Canais
                            </h4>
                            <div className="space-y-3">
                                <ChannelRow label="Tráfego Direto" value="R$ 12.450" percent={64} />
                                <ChannelRow label="Social Media" value="R$ 4.230" percent={22} />
                                <ChannelRow label="E-mail" value="R$ 2.860" percent={14} />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

function MetricCard({ title, value, change, isUp, icon }) {
    return (
        <div className="bg-[#0a0a0b]/50 p-5 rounded-[24px] border border-white/5 backdrop-blur-md hover:border-white/10 transition-all duration-300 group">
            <div className="flex items-center justify-between mb-3">
                <div className="p-2.5 rounded-xl bg-white/[0.04] text-primary group-hover:scale-110 transition-transform duration-300">
                    {icon}
                </div>
                <div className={`flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold ${
                    isUp ? 'text-emerald-400 bg-emerald-500/10' : 'text-rose-400 bg-rose-500/10'
                }`}>
                    {isUp ? <ArrowUpRight size={13} /> : <ArrowDownRight size={13} />}
                    {change}
                </div>
            </div>
            <p className="text-white/40 text-xs font-bold uppercase tracking-wider">{title}</p>
            <h4 className="text-2xl font-black text-white mt-1">{value}</h4>
        </div>
    );
}

function DeviceRow({ label, percentage }) {
    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between">
                <span className="text-sm font-bold text-white/60">{label}</span>
                <span className="text-sm font-black text-white">{percentage}%</span>
            </div>
            <div className="h-1.5 w-full bg-white/[0.04] rounded-full overflow-hidden">
                <div
                    className="h-full bg-gradient-to-r from-primary to-purple-400 rounded-full transition-all duration-700"
                    style={{ width: `${percentage}%` }}
                ></div>
            </div>
        </div>
    );
}

function ChannelRow({ label, value, percent }) {
    return (
        <div className="flex items-center justify-between p-3.5 rounded-2xl bg-white/[0.03] border border-white/5 hover:border-white/10 transition-all">
            <div>
                <p className="text-sm font-bold text-white/80">{label}</p>
                <p className="text-xs font-bold text-primary/70">{percent}% de participação</p>
            </div>
            <p className="text-sm font-black text-white">{value}</p>
        </div>
    );
}
