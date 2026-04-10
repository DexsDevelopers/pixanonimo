// Ghost Pix SPA v2.1 - Build for Auth & Checkout
import React, { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';
import { Routes, Route, Navigate, useLocation, Link } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import { LayoutDashboard, History, Wallet, Settings, Menu, Loader2 } from 'lucide-react';

// Components
import Sidebar from './components/Sidebar';
import Header from './components/Header';
import AnnouncementBar from './components/AnnouncementBar';
import StatCard from './components/StatCard';
import TransactionsTable from './components/TransactionsTable';
import GeneratePixCard from './components/GeneratePixCard';
import PixModal from './components/PixModal';
import PushManager from './components/PushManager';
import WhatsAppPopup from './components/WhatsAppPopup';

// Pages
import LandingPage from './pages/LandingPage';
import SalesPage from './pages/SalesPage';
import WithdrawalsPage from './pages/WithdrawalsPage';
import SettingsPage from './pages/SettingsPage';
import CheckoutPage from './pages/CheckoutPage';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import AdminPage from './pages/AdminPage';
import AdminApisPage from './pages/AdminApisPage';
import AdminTransactionsPage from './pages/AdminTransactionsPage';
import CheckoutsPage from './pages/CheckoutsPage';
import CheckoutBuilderPage from './pages/CheckoutBuilderPage';
import ApiDocsPage from './pages/ApiDocsPage';
import ReportsPage from './pages/ReportsPage';
import AffiliatePage from './pages/AffiliatePage';
import DemoPage from './pages/DemoPage';
import ForgotPasswordPage from './pages/ForgotPasswordPage';
import ResetPasswordPage from './pages/ResetPasswordPage';
import ProdutosPage from './pages/ProdutosPage';
import CriarProdutoPage from './pages/CriarProdutoPage';
import LojaPage from './pages/LojaPage';
import VitrinePage from './pages/VitrinePage';
import AdminProdutosPage from './pages/AdminProdutosPage';
import EntregaPage from './pages/EntregaPage';

// Proteção de Rota Admin
function AdminRoute({ children, userData }) {
  if (!userData?.is_admin) return <Navigate to="/dashboard" />;
  return children;
}

// Layout do Dashboard (Privado)
function DashboardLayout({ children, activeTab, setActiveTab, isSidebarOpen, setIsSidebarOpen, userData, balance, notifications, onMarkRead, onMarkAllRead }) {
  return (
    <div className="flex h-screen bg-black text-white font-['Outfit'] overflow-hidden">
      <AnimatePresence>
        {isSidebarOpen && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 bg-black/60 backdrop-blur-sm z-40"
            onClick={() => setIsSidebarOpen(false)}
          />
        )}
      </AnimatePresence>

      <Sidebar
        isOpen={isSidebarOpen}
        activeTab={activeTab}
        userData={userData}
        onTabChange={(tab) => {
          setActiveTab(tab);
          setIsSidebarOpen(false);
        }}
        onClose={() => setIsSidebarOpen(false)}
      />

      <div className="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <AnnouncementBar />
        <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-primary/5 rounded-full blur-[120px] -z-10 pointer-events-none" />

        <Header
          userData={userData}
          notifications={notifications}
          onMenuClick={() => setIsSidebarOpen(true)}
          onMarkRead={onMarkRead}
          onMarkAllRead={onMarkAllRead}
        />

        <main className="flex-1 overflow-y-auto p-4 lg:p-8 custom-scrollbar relative">
          {children}
        </main>
      </div>
      <PushManager />
    </div>
  );
}

function PrivateRoute({ children }) {
  const isAuthenticated = window.__AUTH__ === true;
  return isAuthenticated ? children : <Navigate to="/login" />;
}

export default function App() {
  const location = useLocation();
  const [activeTab, setActiveTab] = useState('dashboard');
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const [dashboardData, setDashboardData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [activePix, setActivePix] = useState(null);
  const [showWhatsAppPopup, setShowWhatsAppPopup] = useState(false);

  useEffect(() => {
    console.log("APP MOUNTED. Current path:", location.pathname);
    fetchDashboard();

    // Polling de notificações a cada 30s
    const notifInterval = setInterval(fetchNotifications, 30000);
    return () => clearInterval(notifInterval);
  }, []);

  const fetchNotifications = async () => {
    try {
      const res = await fetch('/get_dashboard_data.php');
      const data = await res.json();
      if (data.success) {
        setDashboardData(prev => prev ? { ...prev, notifications: data.notifications } : data);
      }
    } catch (err) {}
  };

  const handleMarkRead = async (id) => {
    try {
      await fetch('/mark_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      setDashboardData(prev => ({
        ...prev,
        notifications: prev.notifications.map(n => n.id === id ? { ...n, is_read: true } : n)
      }));
    } catch (err) {}
  };

  const handleMarkAllRead = async () => {
    const unread = notifications.filter(n => !n.is_read);
    try {
      await Promise.all(unread.map(n =>
        fetch('/mark_read.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: n.id })
        })
      ));
      setDashboardData(prev => ({
        ...prev,
        notifications: prev.notifications.map(n => ({ ...n, is_read: true }))
      }));
    } catch (err) {}
  };

  console.log("RENDERING APP. Path:", location.pathname, "dashboardData:", !!dashboardData);

  const fetchDashboard = async () => {
    console.log("Ghost Pix SPA v2.2 - Iniciando carga de dados...");
    try {
      const res = await fetch('/get_dashboard_data.php');
      const data = await res.json();
      console.log("Dashboard data user:", JSON.stringify(data?.user));
      if (data.success) {
        setDashboardData(data);
        if (!data.user?.whatsapp) setShowWhatsAppPopup(true);
      }
    } catch (err) {
      console.error("Erro ao carregar dashboard:", err);
    } finally {
      setLoading(false);
    }
  };

  // GeneratePixCard já chama o `/api.php` e retorna { id, amount, code, image }.
  // Aqui a gente só precisa abrir o modal e atualizar o dashboard.
  const handleManualPix = async (pixData) => {
    try {
      if (!pixData?.id) return;
      setActivePix({ ...pixData, createdAt: Date.now() });
      fetchDashboard();
    } catch (err) {
      console.error(err);
    }
  };

  const handleDeleteTransaction = async (id) => {
    if (!confirm('Deseja excluir esta transação?')) return;
    try {
      const res = await fetch(`/delete_transaction.php?id=${id}`);
      const data = await res.json();
      if (data.success) fetchDashboard();
    } catch (err) { console.error(err); }
  };

  const commonProps = {
    isSidebarOpen,
    setIsSidebarOpen,
    setActiveTab,
    userData: dashboardData?.user || { name: 'Usuário', email: '' },
    balance: dashboardData?.balance || '0,00',
    notifications: dashboardData?.notifications || [],
    onMarkRead: handleMarkRead,
    onMarkAllRead: handleMarkAllRead
  };

  const { userData, balance, notifications } = commonProps;

  return (
    <>
      {showWhatsAppPopup && <WhatsAppPopup onClose={() => setShowWhatsAppPopup(false)} />}
      <Routes>
        <Route path="/" element={<LandingPage />} />
        <Route path="/demo" element={<DemoPage />} />
        <Route path="/docs" element={<ApiDocsPage />} />
        <Route path="/login" element={<LoginPage onLogin={fetchDashboard} />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="/forgot-password" element={<ForgotPasswordPage />} />
        <Route path="/reset-password/:token" element={<ResetPasswordPage />} />
        <Route path="/entrega/:token" element={<EntregaPage />} />

        <Route path="/dashboard" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="dashboard">
              <div className="max-w-7xl mx-auto space-y-8 animate-in fade-in duration-500">
                  <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                      <h1 className="text-3xl font-black tracking-tight text-white">Olá, <span className="text-primary italic">{userData?.name?.split(' ')[0] || 'Ghost'}</span> 👋</h1>
                      <p className="text-white/40 font-medium">Aqui está o resumo do seu império hoje.</p>
                    </div>
                    <button onClick={fetchDashboard} className="lp-btn-primary py-2 px-6 text-sm">ATUALIZAR STATUS</button>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                    <StatCard label="Saldo Disponível" value={`R$ ${commonProps.balance}`} icon={<Wallet size={24} />} />
                    <StatCard label="Vendas Hoje" value={`R$ ${dashboardData?.stats?.today_volume || '0,00'}`} icon={<History size={24} />} />
                    <StatCard label="Volume Mensal" value={`R$ ${dashboardData?.stats?.month_volume || '0,00'}`} icon={<LayoutDashboard size={24} />} />
                    <StatCard label="Pendentes" value={dashboardData?.stats?.pending_count || '0'} icon={<History size={24} />} trend="Aguardando" />
                  </div>

                  <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div className="space-y-8 order-first lg:order-last">
                      <GeneratePixCard onGenerate={handleManualPix} />
                    </div>
                    <div className="lg:col-span-2 space-y-6">
                      <h2 className="text-xl font-black flex items-center gap-2 border-b border-white/5 pb-4">
                        <History className="text-primary" size={20} /> Minhas Vendas Recentes
                      </h2>
                      <TransactionsTable transactions={dashboardData?.transactions} loading={loading} onViewQr={setActivePix} onDelete={handleDeleteTransaction} />
                    </div>
                  </div>
                </div>
            </DashboardLayout>
          </PrivateRoute>
        } />

        <Route path="/vendas" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="vendas">
              <SalesPage transactions={dashboardData?.transactions} loading={loading} onViewQr={setActivePix} onDelete={handleDeleteTransaction} />
            </DashboardLayout>
          </PrivateRoute>
        } />

        <Route path="/relatorios" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="relatorios">
              <ReportsPage />
            </DashboardLayout>
          </PrivateRoute>
        } />

        <Route path="/saques" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="saques">
              <WithdrawalsPage balance={commonProps.balance} availableForWithdraw={dashboardData?.available_for_withdraw} pendingWithdrawals={dashboardData?.pending_withdrawals} transactions={dashboardData?.transactions} />
            </DashboardLayout>
          </PrivateRoute>
        } />

        <Route path="/afiliado" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="afiliado">
              <AffiliatePage />
            </DashboardLayout>
          </PrivateRoute>
        } />

        <Route path="/config" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="settings">
              <SettingsPage userData={commonProps.userData} />
            </DashboardLayout>
          </PrivateRoute>
        } />

        <Route path="/admin" element={
          <PrivateRoute>
            <AdminRoute userData={userData}>
              <DashboardLayout {...commonProps} activeTab="admin">
                <AdminPage />
              </DashboardLayout>
            </AdminRoute>
          </PrivateRoute>
        } />

        <Route path="/admin/vendas" element={
          <PrivateRoute>
            <AdminRoute userData={userData}>
              <DashboardLayout {...commonProps} activeTab="admin-vendas">
                <AdminTransactionsPage />
              </DashboardLayout>
            </AdminRoute>
          </PrivateRoute>
        } />

        <Route path="/admin/apis" element={
          <PrivateRoute>
            <AdminRoute userData={userData}>
              <DashboardLayout {...commonProps} activeTab="apis">
                <AdminApisPage />
              </DashboardLayout>
            </AdminRoute>
          </PrivateRoute>
        } />

        <Route path="/checkouts" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="checkouts">
              <CheckoutsPage />
            </DashboardLayout>
          </PrivateRoute>
        } />

        <Route path="/checkout-builder" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="checkout-builder">
              <CheckoutBuilderPage />
            </DashboardLayout>
          </PrivateRoute>
        } />

        <Route path="/admin/produtos" element={
          <PrivateRoute>
            <AdminRoute userData={userData}>
              <DashboardLayout {...commonProps} activeTab="admin-produtos">
                <AdminProdutosPage />
              </DashboardLayout>
            </AdminRoute>
          </PrivateRoute>
        } />

        <Route path="/vendedor/produtos" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="produtos">
              <ProdutosPage />
            </DashboardLayout>
          </PrivateRoute>
        } />
        <Route path="/vendedor/produtos/novo" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="produtos">
              <CriarProdutoPage />
            </DashboardLayout>
          </PrivateRoute>
        } />
        <Route path="/vendedor/produtos/editar/:id" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="produtos">
              <CriarProdutoPage />
            </DashboardLayout>
          </PrivateRoute>
        } />

        <Route path="/vendedor/loja" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="loja">
              <LojaPage />
            </DashboardLayout>
          </PrivateRoute>
        } />

        <Route path="/vitrine" element={
          <PrivateRoute>
            <DashboardLayout {...commonProps} activeTab="vitrine">
              <VitrinePage />
            </DashboardLayout>
          </PrivateRoute>
        } />

        <Route path="/p/:slug" element={<CheckoutPage />} />
        <Route path="*" element={<Navigate to="/" />} />
      </Routes>
      {activePix && createPortal(
        <PixModal
          pixData={activePix}
          onClose={() => setActivePix(null)}
          onPaymentSuccess={() => {
            setActivePix(null);
            fetchDashboard();
          }}
        />,
        document.body
      )}
    </>
  );
}
