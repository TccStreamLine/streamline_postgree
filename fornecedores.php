<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$pagina_ativa = 'fornecedores';
$titulo_header = 'Fornecimento > Gerenciar Fornecedores';
$fornecedores = [];
$erro_busca = null;
$usuario_id = $_SESSION['id'];

try {
    $sql = "SELECT * FROM fornecedores 
            WHERE usuario_id = :usuario_id AND status = 'ativo' 
            ORDER BY razao_social ASC";
            
    $stmt_fornecedores = $pdo->prepare($sql);
    $stmt_fornecedores->execute([':usuario_id' => $usuario_id]);
    $fornecedores = $stmt_fornecedores->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro_busca = "Erro ao buscar dados: " . $e->getMessage();
}

$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Gerenciar Fornecedores - Streamline</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/estoque.css">
    <style>
        /* Estilo para o botão de pedido ficar roxo (Padrão Streamline) */
        .btn-pedido {
            background-color: #6D28D9; /* Roxo padrão */
            color: white;
            border: none;
        }
        .btn-pedido:hover {
            background-color: #5b21b6; /* Roxo mais escuro no hover */
            color: white;
        }
        /* Garante que o ícone fique centralizado como os outros */
        .btn-action i {
            pointer-events: none;
        }
    </style>
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

            <?php if (isset($_SESSION['fornecedor_link_manual'])): ?>
                <div class="alert alert-warning" style="word-break: break-all; margin-top: 20px;">
                    <h3>🚨 ATENÇÃO: Link de Ativação Manual 🚨</h3>
                    <p>Durante a demonstração, use este link para que o fornecedor crie a senha. O envio por e-mail foi desativado por restrições de hospedagem.</p>
                    <a href="<?= htmlspecialchars($_SESSION['fornecedor_link_manual']) ?>" target="_blank" style="color: #6D28D9; font-weight: bold; text-decoration: underline;">
                        <?= htmlspecialchars($_SESSION['fornecedor_link_manual']); ?>
                    </a>
                </div>
            <?php unset($_SESSION['fornecedor_link_manual']); endif; ?>
        </div>

        <div class="actions-container">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Pesquisar Fornecedor...">
            </div>
            <div class="actions-buttons">
                <a href="historico_pedidos.php" class="btn-secondary">Histórico de Pedidos</a>
                <a href="fornecedor_formulario.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Cadastrar Fornecedor
                </a>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Razão Social</th>
                        <th>CNPJ</th>
                        <th>Telefone</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($erro_busca): ?>
                        <tr><td colspan="4" class="text-center"><?= htmlspecialchars($erro_busca) ?></td></tr>
                    <?php elseif (empty($fornecedores)): ?>
                        <tr><td colspan="4" class="text-center">Nenhum fornecedor cadastrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($fornecedores as $fornecedor): ?>
                            <tr>
                                <td><?= htmlspecialchars($fornecedor['razao_social']) ?></td>
                                <td><?= htmlspecialchars($fornecedor['cnpj']) ?></td>
                                <td><?= htmlspecialchars($fornecedor['telefone'] ?? 'N/A') ?></td>
                                <td class="actions">
                                    <a href="pedido_formulario.php?fornecedor_id=<?= $fornecedor['id'] ?>" class="btn-action btn-pedido" title="Fazer Pedido">
                                        <i class="fas fa-shopping-cart"></i>
                                    </a>
                                    <a href="fornecedor_formulario.php?id=<?= $fornecedor['id'] ?>" class="btn-action btn-edit" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                                    <a href="excluir_fornecedor.php?id=<?= $fornecedor['id'] ?>" class="btn-action btn-delete" title="Excluir"><i class="fas fa-trash-alt"></i></a>
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
    </script>
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
</body>
</html>