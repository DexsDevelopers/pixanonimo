<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

$checkoutId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$checkout = null;
$items = [];

// Load existing checkout
if ($checkoutId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM checkouts WHERE id = ? AND user_id = ?");
    $stmt->execute([$checkoutId, $userId]);
    $checkout = $stmt->fetch();

    if (!$checkout) {
        redirect('checkouts.php');
    }

    $stmt = $pdo->prepare("SELECT * FROM checkout_items WHERE checkout_id = ? ORDER BY id ASC");
    $stmt->execute([$checkoutId]);
    $items = $stmt->fetchAll();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    // Slugfy the slug
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '-', $slug));
    
    $primary_color = $_POST['primary_color'] ?? '#00ff88';
    $secondary_color = $_POST['secondary_color'] ?? '#111111';
    $custom_html_head = $_POST['custom_html_head'] ?? '';
    $custom_html_body = $_POST['custom_html_body'] ?? '';
    $active = isset($_POST['active']) ? 1 : 0;

    $item_names = $_POST['item_name'] ?? [];
    $item_prices = $_POST['item_price'] ?? [];
    $existing_item_images = $_POST['existing_item_image'] ?? [];
    
    // File upload logic for banner
    $checkout_banner_url = $_POST['checkout_banner_url'] ?? '';
    if (isset($_FILES['checkout_banner_file']) && $_FILES['checkout_banner_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/checkouts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = time() . '_banner_' . basename($_FILES['checkout_banner_file']['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['checkout_banner_file']['tmp_name'], $targetPath)) {
            $checkout_banner_url = $targetPath;
        }
    }

    if (empty($title) || empty($slug)) {
        $error = "Título e Slug são obrigatórios.";
    } elseif (empty($item_names)) {
        $error = "Você precisa adicionar pelo menos um produto ao checkout.";
    } else {
        try {
            $pdo->beginTransaction();

                if ($checkoutId > 0) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE checkouts SET title=?, slug=?, primary_color=?, secondary_color=?, custom_html_head=?, custom_html_body=?, active=?, checkout_banner_url=? WHERE id=? AND user_id=?");
                    $stmt->execute([$title, $slug, $primary_color, $secondary_color, $custom_html_head, $custom_html_body, $active, $checkout_banner_url, $checkoutId, $userId]);
                } else {
                    // Create
                    $stmt = $pdo->prepare("INSERT INTO checkouts (user_id, title, slug, primary_color, secondary_color, custom_html_head, custom_html_body, active, checkout_banner_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$userId, $title, $slug, $primary_color, $secondary_color, $custom_html_head, $custom_html_body, $active, $checkout_banner_url]);
                    $checkoutId = $pdo->lastInsertId();
                }

            // Refresh items
            $stmt = $pdo->prepare("DELETE FROM checkout_items WHERE checkout_id = ?");
            $stmt->execute([$checkoutId]);

            $stmt = $pdo->prepare("INSERT INTO checkout_items (checkout_id, name, price, image_url) VALUES (?, ?, ?, ?)");
            for ($i = 0; $i < count($item_names); $i++) {
                $name = trim($item_names[$i]);
                $price = str_replace(',', '.', trim($item_prices[$i])); 
                $price = (float) $price;
                
                // Determine item image
                $image = trim($_POST['item_image'][$i] ?? '');
                if (isset($_FILES['item_image_file']['name'][$i]) && $_FILES['item_image_file']['error'][$i] === UPLOAD_ERR_OK) {
                    $uploadDir = 'uploads/checkouts/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $fileName = time() . "_item_{$i}_" . basename($_FILES['item_image_file']['name'][$i]);
                    $targetPath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['item_image_file']['tmp_name'][$i], $targetPath)) {
                        $image = $targetPath;
                    }
                } else if (empty($image) && isset($existing_item_images[$i])) {
                    $image = $existing_item_images[$i];
                }
                
                if (!empty($name) && $price > 0) {
                    $stmt->execute([$checkoutId, $name, $price, $image]);
                }
            }

            $pdo->commit();
            $success = "Checkout salvo com sucesso!";
            
            // Reload data
            $stmt = $pdo->prepare("SELECT * FROM checkouts WHERE id = ?");
            $stmt->execute([$checkoutId]);
            $checkout = $stmt->fetch();

            $stmt = $pdo->prepare("SELECT * FROM checkout_items WHERE checkout_id = ? ORDER BY id ASC");
            $stmt->execute([$checkoutId]);
            $items = $stmt->fetchAll();

        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) { // Constraint violation (e.g., duplicate slug)
                $error = "Este slug já está em uso. Por favor, escolha outro.";
            } else {
                $error = "Erro ao salvar checkout: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="theme-color" content="#000000">
    <title><?php echo $checkoutId > 0 ? 'Editar Checkout' : 'Novo Checkout'; ?> - Ghost Pix</title>
    <link rel="stylesheet" href="style.css?v=125.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .builder-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
            margin-top: 1.5rem;
        }
        @media (max-width: 900px) {
            .builder-grid {
                grid-template-columns: 1fr;
            }
        }
        .form-section {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-2);
        }
        .form-input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-1);
            font-family: inherit;
            transition: all 0.3s;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255,255,255,0.05);
        }
        .color-pickers {
            display: flex;
            gap: 1.5rem;
        }
        .color-picker-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .color-picker-input {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            padding: 0;
            background: transparent;
        }
        
        /* Products list */
        .product-item {
            display: grid;
            grid-template-columns: 1fr 120px 40px;
            gap: 10px;
            background: rgba(255,255,255,0.02);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--border);
            margin-bottom: 10px;
            align-items: end;
        }
        @media (max-width: 500px) {
            .product-item {
                grid-template-columns: 1fr;
            }
        }
        .btn-remove-product {
            height: 44px;
            width: 40px;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-add-product {
            background: rgba(255,255,255,0.05);
            color: var(--text-1);
            border: 1px dashed var(--border);
            border-radius: 8px;
            padding: 12px;
            width: 100%;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-add-product:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--text-3);
        }
        
        .toggle-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        .btn-save {
            width: 100%;
            padding: 15px;
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        .btn-save:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="top-header">
            <div>
                <h1><?php echo $checkoutId > 0 ? 'Editar Checkout' : 'Novo Checkout'; ?> ⚙️</h1>
                <p>Configure a tela de pagamento do seu jeito.</p>
            </div>
            <a href="checkouts.php" class="btn-secondary" style="padding: 10px 20px; text-decoration: none; border-radius: 8px; border: 1px solid var(--border);">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </header>

        <?php if ($error): ?>
            <div class="alert error" style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 1.5rem;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert success" style="background: rgba(37, 211, 102, 0.1); border: 1px solid #25d366; color: #25d366; padding: 15px; border-radius: 8px; margin-bottom: 1.5rem;">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="builder-grid">
                <!-- Coluna Principal (Configurações base) -->
                <div>
                    <!-- Detalhes Base -->
                    <div class="form-section">
                        <h2 class="form-section-title"><i class="fas fa-info-circle"></i> Informações Principais</h2>
                        
                        <div class="form-group">
                            <label class="form-label">Título da Página (Meta Title)</label>
                            <input type="text" name="title" class="form-input" required placeholder="Ex: Pagamento - Meu Produto" value="<?php echo htmlspecialchars($checkout['title'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Slug (URL amigável)</label>
                            <div style="display: flex; align-items: center; gap: 8px; background: rgba(0,0,0,0.2); padding: 5px 10px; border-radius: 8px; border: 1px solid var(--border);">
                                <span style="color: var(--text-3); font-size: 0.9rem;">/c/</span>
                                <input type="text" name="slug" class="form-input" style="border: none; background: transparent; padding: 5px; flex: 1;" required placeholder="meu-produto" value="<?php echo htmlspecialchars($checkout['slug'] ?? ''); ?>">
                            </div>
                            <small style="color: var(--text-3); display: block; margin-top: 5px;">A url ficará: <?php echo $_SERVER['HTTP_HOST']; ?>/c/seu-slug</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Banner do Checkout (Upload de Imagem ou Link)</label>
                            
                            <div style="display:flex; flex-direction:column; gap:10px; background: rgba(0,0,0,0.2); padding: 10px; border-radius:8px;">
                                <?php if(!empty($checkout['checkout_banner_url'])): ?>
                                    <div style="margin-bottom: 5px;">
                                        <img src="<?php echo htmlspecialchars($checkout['checkout_banner_url']); ?>" alt="Banner Atual" style="max-height: 80px; border-radius: 4px; border: 1px solid var(--border);">
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <small style="color: var(--text-3); margin-bottom: 5px; display:block;">Fazer envio do seu computador:</small>
                                    <input type="file" name="checkout_banner_file" class="form-input" accept="image/*" style="padding: 8px;">
                                </div>
                                <div style="text-align:center; color: var(--text-3); font-size: 0.8rem;">OU</div>
                                <div>
                                    <small style="color: var(--text-3); margin-bottom: 5px; display:block;">Colar URL da imagem:</small>
                                    <input type="url" name="checkout_banner_url" class="form-input" placeholder="https://exemplo.com/banner.png" value="<?php echo htmlspecialchars($checkout['checkout_banner_url'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Produtos -->
                    <div class="form-section">
                        <h2 class="form-section-title"><i class="fas fa-box"></i> Produtos Inclusos</h2>
                        <p style="color: var(--text-2); font-size: 0.85rem; margin-bottom: 1rem;">Adicione os itens que vão aparecer no resumo de compra deste checkout.</p>
                        
                        <div id="products-container">
                            <?php if (empty($items)): ?>
                                <!-- Empty starting row -->
                                <div class="product-item">
                                    <div class="form-group" style="margin: 0;">
                                        <label class="form-label">Nome do Produto</label>
                                        <input type="text" name="item_name[]" class="form-input" required placeholder="Ex: Mentoria VIP">
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label class="form-label">Preço (R$)</label>
                                        <input type="number" step="0.01" name="item_price[]" class="form-input" required placeholder="97,00">
                                    </div>
                                    <div class="form-group" style="grid-column: span 2; margin: 0; margin-top: 10px; background: rgba(0,0,0,0.1); padding: 10px; border-radius: 8px;">
                                        <label class="form-label">Imagem do Produto (Opcional)</label>
                                        <div style="display:flex; flex-direction:column; gap:8px;">
                                            <input type="file" name="item_image_file[]" class="form-input" accept="image/*" style="padding: 8px; font-size:0.85rem;">
                                            <input type="url" name="item_image[]" class="form-input" placeholder="Ou cole a URL: https://exemplo.com/foto.png">
                                        </div>
                                    </div>
                                    <button type="button" class="btn-remove-product" title="Remover" onclick="this.parentElement.remove()">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            <?php else: ?>
                                <?php foreach ($items as $index => $item): ?>
                                    <div class="product-item">
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Nome do Produto</label>
                                            <input type="text" name="item_name[]" class="form-input" required value="<?php echo htmlspecialchars($item['name']); ?>">
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Preço (R$)</label>
                                            <input type="number" step="0.01" name="item_price[]" class="form-input" required value="<?php echo htmlspecialchars($item['price']); ?>">
                                        </div>
                                        <div class="form-group" style="grid-column: span 2; margin: 0; margin-top: 10px; background: rgba(0,0,0,0.1); padding: 10px; border-radius: 8px;">
                                            <label class="form-label">Imagem do Produto (Opcional)</label>
                                            <?php if(!empty($item['image_url'])): ?>
                                                <div style="margin-bottom: 5px;">
                                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="Atual" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                                    <input type="hidden" name="existing_item_image[]" value="<?php echo htmlspecialchars($item['image_url']); ?>">
                                                </div>
                                            <?php else: ?>
                                                <input type="hidden" name="existing_item_image[]" value="">
                                            <?php endif; ?>
                                            <div style="display:flex; flex-direction:column; gap:8px;">
                                                <input type="file" name="item_image_file[]" class="form-input" accept="image/*" style="padding: 8px; font-size:0.85rem;">
                                                <input type="url" name="item_image[]" class="form-input" placeholder="Ou cole a URL aqui..." value="<?php echo (strpos($item['image_url'], 'http') === 0) ? htmlspecialchars($item['image_url']) : ''; ?>">
                                            </div>
                                        </div>
                                        <button type="button" class="btn-remove-product" title="Remover" onclick="this.parentElement.remove()">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" id="btn-add-product" class="btn-add-product" style="margin-top: 10px;">
                            <i class="fas fa-plus"></i> Adicionar outro Produto
                        </button>
                    </div>

                    <!-- Scripts & HTML -->
                    <div class="form-section">
                        <h2 class="form-section-title"><i class="fas fa-code"></i> Injeção de Scripts (Avançado)</h2>
                        
                        <div class="form-group">
                            <label class="form-label">&lt;head&gt; Scripts (Pixel do Face, Google Analytics, CSS Custom)</label>
                            <textarea name="custom_html_head" class="form-input" rows="4" style="font-family: monospace; font-size: 0.85rem;" placeholder="<!-- Seu script aqui -->"><?php echo htmlspecialchars($checkout['custom_html_head'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">&lt;body&gt; Fim do documento (Scripts extras)</label>
                            <textarea name="custom_html_body" class="form-input" rows="4" style="font-family: monospace; font-size: 0.85rem;" placeholder="<!-- Seu script aqui -->"><?php echo htmlspecialchars($checkout['custom_html_body'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Coluna Lateral (Styling e Submit) -->
                <div>
                    <div class="form-section" style="position: sticky; top: 2rem;">
                        <h2 class="form-section-title"><i class="fas fa-paint-brush"></i> Aparência</h2>
                        
                        <div class="form-group">
                            <label class="form-label">Cor Principal (Botões e Destaques)</label>
                            <div class="color-picker-wrap">
                                <input type="color" name="primary_color" class="color-picker-input" value="<?php echo htmlspecialchars($checkout['primary_color'] ?? '#00ff88'); ?>">
                                <span style="color: var(--text-2); font-family: monospace;"><?php echo htmlspecialchars($checkout['primary_color'] ?? '#00ff88'); ?></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Cor Secundária (Fundo da Caixa)</label>
                            <div class="color-picker-wrap">
                                <input type="color" name="secondary_color" class="color-picker-input" value="<?php echo htmlspecialchars($checkout['secondary_color'] ?? '#111111'); ?>">
                                <span style="color: var(--text-2); font-family: monospace;"><?php echo htmlspecialchars($checkout['secondary_color'] ?? '#111111'); ?></span>
                            </div>
                        </div>

                        <hr style="border: 0; border-top: 1px solid var(--border); margin: 1.5rem 0;">

                        <div class="form-group">
                            <label class="toggle-wrap">
                                <input type="checkbox" name="active" value="1" <?php echo (!isset($checkout['active']) || $checkout['active']) ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: var(--accent);">
                                <span style="font-weight: 500;">Checkout Ativo</span>
                            </label>
                            <p style="font-size: 0.8rem; color: var(--text-3); margin-top: 5px; padding-left: 28px;">
                                Desative para bloquear novas vendas por este link temporariamente.
                            </p>
                        </div>

                        <button type="submit" class="btn-save" style="margin-top: 1.5rem;">
                            <i class="fas fa-save"></i> Salvar Checkout
                        </button>
                    </div>
                </div>
            </div>
        </form>

    </main>
    
    <script>
        document.getElementById('btn-add-product').addEventListener('click', function() {
            const container = document.getElementById('products-container');
            const itemHtml = `
                <div class="product-item">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Nome do Produto</label>
                        <input type="text" name="item_name[]" class="form-input" required placeholder="Ex: Produto X">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Preço (R$)</label>
                        <input type="number" step="0.01" name="item_price[]" class="form-input" required placeholder="0,00">
                    </div>
                    <div class="form-group" style="grid-column: span 2; margin: 0; margin-top: 10px; background: rgba(0,0,0,0.1); padding: 10px; border-radius: 8px;">
                        <label class="form-label">Imagem do Produto (Opcional)</label>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <input type="file" name="item_image_file[]" class="form-input" accept="image/*" style="padding: 8px; font-size:0.85rem;">
                            <input type="url" name="item_image[]" class="form-input" placeholder="Ou cole a URL da foto...">
                        </div>
                    </div>
                    <button type="button" class="btn-remove-product" title="Remover" onclick="this.parentElement.remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', itemHtml);
        });

        // Add real-time color update to hex text
        document.querySelectorAll('.color-picker-input').forEach(input => {
            input.addEventListener('input', function() {
                this.nextElementSibling.textContent = this.value;
            });
        });
    </script>
</body>
</html>
