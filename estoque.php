<?php
session_start();
include_once('config.php');

$filtro = $_GET['filtro'] ?? '';
$where_clause = "p.status = 'ativo'";
$titulo_header = 'Estoque';

if ($filtro === 'estoque_baixo') {
    $where_clause .= " AND p.quantidade_estoque <= p.quantidade_minima";
    $titulo_header = 'Estoque Baixo';
}

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$pagina_ativa = 'estoque';
$produtos = [];
$erro_busca = null;

try {
    // Query compatível com PostgreSQL e MySQL
    $sql = "SELECT p.*, c.nome as categoria_nome 
            FROM produtos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            WHERE $where_clause 
            ORDER BY p.nome ASC";
            
    $stmt_produtos = $pdo->prepare($sql);
    $stmt_produtos->execute();
    $produtos = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro_busca = "Erro ao buscar dados: " . $e->getMessage();
}

$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo_header) ?> - Sistema de Gerenciamento</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/estoque.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="message-container">
            <?php if (isset($_SESSION['msg_sucesso'])): ?>
                <div class="alert alert-success"><?= $_SESSION['msg_sucesso']; unset($_SESSION['msg_sucesso']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['msg_erro'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['msg_erro']; unset($_SESSION['msg_erro']); ?></div>
            <?php endif; ?>
        </div>

        <div class="actions-container">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Pesquisar Produto...">
            </div>
            <div class="actions-buttons">
                <a href="categorias.php" class="btn-secondary">Gerenciar Categorias</a>
                <a href="produto_formulario.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Cadastrar Produto
                </a>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Cód. Barras</th>
                        <th>Nome</th>
                        <th>Estoque</th>
                        <th>Valor Venda</th>
                        <th>Categoria</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($erro_busca): ?>
                        <tr><td colspan="6" class="text-center"><?= htmlspecialchars($erro_busca) ?></td></tr>
                    <?php elseif (empty($produtos)): ?>
                        <tr><td colspan="6" class="text-center">Nenhum produto <?php echo ($filtro === 'estoque_baixo' ? 'com estoque baixo encontrado.' : 'cadastrado ainda.'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($produtos as $produto): ?>
                            <tr>
                                <td><?= htmlspecialchars($produto['codigo_barras'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($produto['nome']) ?></td>
                                <td><?= htmlspecialchars($produto['quantidade_estoque']) ?></td>
                                <td>R$ <?= number_format((float) $produto['valor_venda'], 2, ',', '.') ?></td>
                                <td><?= htmlspecialchars($produto['categoria_nome'] ?? 'N/A') ?></td>
                                <td class="actions">
                                    <a href="produto_formulario.php?id=<?= $produto['id'] ?>" class="btn-action btn-edit"><i class="fas fa-pencil-alt"></i></a>
                                    <a href="excluir_produto.php?id=<?= $produto['id'] ?>" class="btn-action btn-delete"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const url = this.href;
                Swal.fire({
                    title: 'Tem certeza?',
                    text: "Você não poderá reverter esta ação!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Sim',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });

        const searchInput = document.querySelector('.search-bar input');
        const tableBody = document.querySelector('table tbody');

        function formatCurrency(value) {
            return parseFloat(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        function renderTable(produtos) {
            tableBody.innerHTML = ''; 
            if (produtos.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Nenhum produto encontrado.</td></tr>';
                return;
            }
            produtos.forEach(produto => {
                const row = `
                    <tr>
                        <td>${produto.codigo_barras || 'N/A'}</td>
                        <td>${produto.nome}</td>
                        <td>${produto.quantidade_estoque}</td>
                        <td>${formatCurrency(produto.valor_venda)}</td>
                        <td>${produto.categoria_nome || 'N/A'}</td>
                        <td class="actions">
                            <a href="produto_formulario.php?id=${produto.id}" class="btn-action btn-edit"><i class="fas fa-pencil-alt"></i></a>
                            <a href="excluir_produto.php?id=${produto.id}" class="btn-action btn-delete"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });
        }

        searchInput.addEventListener('keyup', async () => {
            const termo = searchInput.value;
            const filtroAtual = '<?= $filtro ?>';
            try {
                // A URL buscar_produtos_estoque.php já foi ajustada por nós anteriormente para suportar ILIKE
                const response = await fetch(`buscar_produtos_estoque.php?termo=${encodeURIComponent(termo)}&filtro=${filtroAtual}`);
                const produtos = await response.json();
                renderTable(produtos);
            } catch (error) {
                console.error('Erro ao buscar produtos:', error);
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Erro ao carregar os dados.</td></tr>';
            }
        });
    </script>
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
</body>
</html>