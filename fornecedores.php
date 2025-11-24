<?php
session_start();
include_once('config.php');

$pagina_ativa = 'fornecedores';
$titulo_header = 'Gerenciamento de Fornecedores';

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

// Consulta padrão, funciona em ambos os bancos
$sql = "SELECT * FROM fornecedores WHERE status = 'ativo' ORDER BY razao_social ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fornecedores - Sistema de Gerenciamento</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <input type="text" placeholder="Pesquisar Fornecedor...">
            </div>

            <div class="actions-buttons">
                <a href="historico_pedidos.php" class="btn-secondary"><i class="fas fa-history"></i> Histórico de Pedidos</a>
                <a href="fornecedor_formulario.php" class="btn-primary"><i class="fas fa-plus"></i> Cadastrar Fornecedor</a>
            </div>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Razão Social</th>
                        <th>CNPJ</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fornecedores)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Nenhum fornecedor cadastrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fornecedores as $fornecedor): ?>
                            <tr>
                                <td><?= htmlspecialchars($fornecedor['razao_social']) ?></td>
                                <td><?= htmlspecialchars($fornecedor['cnpj']) ?></td>
                                <td><?= htmlspecialchars($fornecedor['email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($fornecedor['telefone'] ?? '') ?></td>
                                <td class="actions">
                                    <a href="pedido_formulario.php?fornecedor_id=<?= $fornecedor['id'] ?>" class="btn-action btn-pedido" title="Realizar Pedido">
                                        <i class="fas fa-cart-plus"></i>
                                    </a>
                                    <a href="editar_pedidos.php?fornecedor_id=<?= $fornecedor['id'] ?>" class="btn-action btn-edit-pedido" title="Editar Pedidos Pendentes">
                                        <i class="fas fa-tasks"></i>
                                    </a>
                                    <a href="fornecedor_formulario.php?id=<?= $fornecedor['id'] ?>" class="btn-action btn-edit" title="Editar Fornecedor">
                                        <i class="fas fa-pencil-alt"></i> 
                                    </a>
                                    <a href="excluir_fornecedor.php?id=<?= $fornecedor['id'] ?>" class="btn-action btn-delete" title="Excluir Fornecedor">
                                        <i class="fas fa-trash-alt"></i> 
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <script>
        const deleteButtons = document.querySelectorAll('.btn-delete');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const url = this.href;
                Swal.fire({
                    title: 'Tem certeza?',
                    text: "O fornecedor será inativado e não aparecerá mais nas listas.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Sim, inativar!',
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

        function renderTable(fornecedores) {
            tableBody.innerHTML = '';
            if (fornecedores.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Nenhum fornecedor encontrado.</td></tr>';
                return;
            }
            fornecedores.forEach(fornecedor => {
                const row = `
                    <tr>
                        <td>${fornecedor.razao_social}</td>
                        <td>${fornecedor.cnpj}</td>
                        <td>${fornecedor.email || ''}</td>
                        <td>${fornecedor.telefone || ''}</td>
                        <td class="actions">
                            <a href="pedido_formulario.php?fornecedor_id=${fornecedor.id}" class="btn-action btn-pedido" title="Realizar Pedido"><i class="fas fa-cart-plus"></i></a>
                            <a href="editar_pedidos.php?fornecedor_id=${fornecedor.id}" class="btn-action btn-edit-pedido" title="Editar Pedidos Pendentes"><i class="fas fa-tasks"></i></a>
                            <a href="fornecedor_formulario.php?id=${fornecedor.id}" class="btn-action btn-edit" title="Editar Fornecedor"><i class="fas fa-pencil-alt"></i></a>
                            <a href="excluir_fornecedor.php?id=${fornecedor.id}" class="btn-action btn-delete" title="Excluir Fornecedor"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>`;
                tableBody.innerHTML += row;
            });
        }

        searchInput.addEventListener('keyup', async () => {
            const termo = searchInput.value;
            try {
                // A URL buscar_fornecedores.php já foi corrigida para usar ILIKE
                const response = await fetch(`buscar_fornecedores.php?termo=${encodeURIComponent(termo)}`);
                const fornecedores = await response.json();
                renderTable(fornecedores);
            } catch (error) {
                console.error('Erro ao buscar fornecedores:', error);
            }
        });
    </script>
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
</body>
</html>