<?php
session_start();
include_once('config.php');

// Apenas Fornecedores podem acessar
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'fornecedor') {
    header('Location: login.php');
    exit;
}

$fornecedor_id = $_SESSION['id'];
$pedido_id = filter_input(INPUT_GET, 'pedido_id', FILTER_VALIDATE_INT);

if (!$pedido_id) {
    $_SESSION['msg_erro'] = "Pedido inválido.";
    header('Location: gerenciar_fornecimento.php');
    exit;
}

// 1. Busca Detalhes do Pedido e do Cliente (Empresa)
$stmt_pedido = $pdo->prepare("
    SELECT p.*, u.nome_empresa, u.cnpj as cliente_cnpj
    FROM pedidos_fornecedor p
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.id = ? AND p.fornecedor_id = ?
");
$stmt_pedido->execute([$pedido_id, $fornecedor_id]);
$pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    $_SESSION['msg_erro'] = "Pedido não encontrado ou acesso negado.";
    header('Location: gerenciar_fornecimento.php');
    exit;
}

// 2. Busca Itens do Pedido
$stmt_itens = $pdo->prepare("
    SELECT i.*, p.nome as produto_nome 
    FROM pedido_fornecedor_itens i
    JOIN produtos p ON i.produto_id = p.id
    WHERE i.pedido_id = ?
");
$stmt_itens->execute([$pedido_id]);
$itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

$titulo_header = 'Fornecimento > Realizar Entrega';
$nome_empresa = $_SESSION['nome_empresa'] ?? 'Fornecedor';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Realizar Entrega - Streamline</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/venda_formulario.css"> 
    <style>
        /* Estilos específicos para a tela de entrega (Protótipo) */
        .detalhes-pedido-card {
            background-color: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            border-bottom: 1px dashed #eee;
            padding-bottom: 10px;
        }
        .info-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        .info-label {
            color: #6B7280;
            font-weight: 500;
        }
        .info-value {
            color: #1F2937;
            font-weight: 600;
        }
        
        /* Área de Upload de Foto */
        .upload-area {
            border: 2px dashed #D1D5DB;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            background-color: #F9FAFB;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 10px;
        }
        .upload-area:hover {
            border-color: var(--primary-color);
            background-color: #F3E8FF;
        }
        .upload-area i {
            font-size: 2rem;
            color: #9CA3AF;
            margin-bottom: 10px;
        }
        .upload-area p {
            color: #6B7280;
            margin: 0;
            font-size: 0.9rem;
        }
        #preview-img {
            max-width: 100%;
            max-height: 200px;
            margin-top: 15px;
            border-radius: 4px;
            display: none;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="form-produto-container">
            <h3 class="form-produto-title">REGISTRAR ENTREGA DO PEDIDO #<?= $pedido_id ?></h3>

            <div class="detalhes-pedido-card">
                <div class="info-row">
                    <span class="info-label">Cliente Solicitante:</span>
                    <span class="info-value"><?= htmlspecialchars($pedido['nome_empresa']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Data do Pedido:</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Valor Total:</span>
                    <span class="info-value" style="color: var(--primary-color);">R$ <?= number_format($pedido['valor_total_pedido'], 2, ',', '.') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status Atual:</span>
                    <span class="info-value"><?= htmlspecialchars($pedido['status_pedido']) ?></span>
                </div>
            </div>

            <form action="processa_entrega.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="pedido_id" value="<?= $pedido_id ?>">
                <input type="hidden" name="acao" value="confirmar_entrega">

                <div class="form-produto-grid">
                    <div class="form-produto-group">
                        <label>Fornecedor (Você)</label>
                        <input type="text" value="<?= htmlspecialchars($_SESSION['nome_empresa']) ?>" readonly style="background-color: #f3f4f6; cursor: not-allowed;">
                    </div>

                    <div class="form-produto-group">
                        <label>Data da Entrega</label>
                        <input type="date" name="data_entrega" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="lista-itens-wrapper" style="margin: 20px 0;">
                    <h4>Itens do Pedido</h4>
                    <table class="tabela-itens">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Qtd. Solicitada</th>
                                <th>Qtd. Entregue</th> </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $index => $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['produto_nome']) ?></td>
                                    <td><?= $item['quantidade_pedida'] ?></td>
                                    <td>
                                        <input type="number" name="itens[<?= $item['id'] ?>]" 
                                               value="<?= $item['quantidade_pedida'] ?>" 
                                               min="0" max="<?= $item['quantidade_pedida'] ?>"
                                               style="width: 80px; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="form-produto-group full-width">
                    <label>Nota Fiscal (Foto/Comprovante)</label>
                    <div class="upload-area" onclick="document.getElementById('input-foto').click()">
                        <i class="fas fa-camera"></i>
                        <p>Clique aqui para tirar uma foto ou enviar o arquivo da Nota Fiscal</p>
                        <input type="file" name="nota_fiscal" id="input-foto" accept="image/*,application/pdf" style="display: none;" onchange="previewImage(this)">
                    </div>
                    <img id="preview-img" alt="Pré-visualização da Nota">
                </div>

                <div class="venda-footer" style="margin-top: 30px; display: flex; justify-content: flex-end;">
                    <a href="gerenciar_fornecimento.php" class="btn-cancel" style="margin-right: 15px; text-decoration: none; padding: 12px 20px; color: #666;">Cancelar</a>
                    <button type="submit" class="btn-produto-primary">Confirmar Entrega</button>
                </div>
            </form>
        </div>
    </main>

    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
    <script>
        // Função para mostrar prévia da imagem selecionada
        function previewImage(input) {
            const preview = document.getElementById('preview-img');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Se for imagem, mostra. Se for PDF, mostra ícone genérico (opcional)
                    if(input.files[0].type.startsWith('image/')) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    } else {
                        preview.style.display = 'none';
                        alert('Arquivo selecionado: ' + input.files[0].name);
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>