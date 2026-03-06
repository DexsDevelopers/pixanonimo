<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="theme-color" content="#000000">
    <link rel="manifest" href="manifest.json">
    <title>Ghost Pix - Perfil (V2)</title>
    <link rel="stylesheet" href="style.css?v=117.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-header">
                <h1>Meu Perfil <span style="font-size: 0.8rem; color: var(--green);">[GATEWAY ATIVO]</span></h1>
                <a href="dashboard.php" class="badge sent" style="text-decoration:none">Voltar</a>
            </header>

            <div style="max-width: 600px; margin: 0 auto;">
                <div class="card">
                    <form id="profile-form">
                        <div class="card-header" style="margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                            <div class="card-title-group">
                                <div class="card-icon"><i class="fas fa-user-edit"></i></div>
                                <h3 class="card-title">Dados Pessoais</h3>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 1.2rem;">
                            <label class="stat-label" style="display:block; margin-bottom: 0.5rem;">Email (Protegido)</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled 
                                   style="width: 100%; background: rgba(255,255,255,0.02); border: 1px solid var(--border); padding: 0.8rem 1rem; border-radius: 10px; color: var(--text-2); cursor: not-allowed; font-family: var(--font);">
                        </div>

                        <div style="margin-bottom: 1.2rem;">
                            <label class="stat-label" style="display:block; margin-bottom: 0.5rem;">Nome Completo</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required
                                   style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 0.8rem 1rem; border-radius: 10px; color: var(--text); font-family: var(--font); transition: border-color 0.2s;">
                        </div>

                        <div style="margin-bottom: 1.2rem;">
                            <label class="stat-label" style="display:block; margin-bottom: 0.5rem;">Chave PIX</label>
                            <input type="text" name="pix_key" value="<?php echo htmlspecialchars($user['pix_key']); ?>" required
                                   style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 0.8rem 1rem; border-radius: 10px; color: var(--text); font-family: 'SF Mono', monospace; transition: border-color 0.2s;">
                        </div>

                        <div class="card-header" style="margin-top: 3rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                            <div class="card-title-group">
                                <div class="card-icon"><i class="fas fa-shield-alt"></i></div>
                                <h3 class="card-title">Segurança</h3>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 1.2rem;">
                            <label class="stat-label" style="display:block; margin-bottom: 0.5rem;">Senha Atual</label>
                            <input type="password" name="current_password" placeholder="Para validar alterações"
                                   style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 0.8rem 1rem; border-radius: 10px; color: var(--text); font-family: var(--font);">
                        </div>
                        <div style="margin-bottom: 1.2rem;">
                            <label class="stat-label" style="display:block; margin-bottom: 0.5rem;">Nova Senha (Opcional)</label>
                            <input type="password" name="new_password" placeholder="Mínimo 6 caracteres"
                                   style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 0.8rem 1rem; border-radius: 10px; color: var(--text); font-family: var(--font);">
                        </div>

                        <div class="card-header" style="margin-top: 3rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                            <div class="card-title-group">
                                <div class="card-icon"><i class="fas fa-code"></i></div>
                                <h3 class="card-title">Desenvolvedor (API)</h3>
                            </div>
                        </div>

                        <div style="margin-bottom: 1.2rem;">
                            <label class="stat-label" style="display:block; margin-bottom: 0.5rem;">Sua Ghost Key (Privada)</label>
                            <div style="display:flex; gap: 0.5rem;">
                                <input type="password" id="api-key-input" value="<?php echo htmlspecialchars($user['api_key'] ?? ''); ?>" readonly
                                       placeholder="Nenhuma chave gerada"
                                       style="flex:1; background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 0.8rem 1rem; border-radius: 10px; color: var(--text); font-family: 'SF Mono', monospace;">
                                <button type="button" id="btn-toggle-key" class="btn-icon-sm" style="min-height: 44px; width: 44px;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <p style="font-size: 0.72rem; color: var(--text-3); margin-top: 0.5rem; line-height: 1.4;">
                                Use esta chave para integrar o Ghost Pix em seu próprio site. 
                                <a href="docs/API_DOCUMENTATION.md" target="_blank" style="color: var(--green); text-decoration: none;">Ver documentação</a>.
                            </p>
                        </div>

                        <button type="button" id="btn-generate-key" class="btn-primary" style="background: transparent; border: 1px solid var(--border); color: var(--text); margin-top: 0.5rem; min-height: 44px;">
                            <i class="fas fa-sync"></i> Gerar Nova Chave
                        </button>

                        <button type="submit" class="btn-primary" id="btn-save-profile" style="margin-top: 2rem;">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="script.js?v=107.0"></script>
    <script>
    // Toggle visibility of API Key
    document.getElementById('btn-toggle-key').addEventListener('click', function() {
        const input = document.getElementById('api-key-input');
        const icon = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });

    // Generate New API Key
    document.getElementById('btn-generate-key').addEventListener('click', async function() {
        if (!confirm('Deseja gerar uma nova chave de API? A chave antiga parará de funcionar imediatamente.')) return;
        
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando...';

        try {
            const res = await fetch('generate_key.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ action: 'generate' })
            });

            const result = await res.json();
            if (result.success) {
                document.getElementById('api-key-input').value = result.api_key;
                alert(result.message);
            } else {
                alert(result.error);
            }
        } catch (err) {
            alert('Erro ao gerar chave: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync"></i> Gerar Nova Chave';
        }
    });

    document.getElementById('profile-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const btn = document.getElementById('btn-save-profile');
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        // A senha atual só será validada no backend se o usuário tentar mudar a senha
        btn.innerText = 'Salvando...';
        btn.disabled = true;

        try {
            const res = await fetch('update_profile.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            });

            const text = await res.text();
            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                throw new Error('Resposta inválida do servidor: ' + text.substring(0, 100));
            }

            if (result.success) {
                alert('Perfil atualizado com sucesso!');
                location.reload();
            } else {
                alert(result.error || 'Erro ao atualizar perfil.');
            }
        } catch (err) {
            alert('Erro: ' + err.message);
        } finally {
            btn.innerText = 'Salvar Alterações';
            btn.disabled = false;
        }
    });
    <script src="script.js?v=107.0"></script>
</body>
</html>

