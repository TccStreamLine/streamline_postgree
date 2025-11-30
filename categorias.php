<?php
session_start();
include_once('config.php');

$pagina_ativa = 'estoque';
$titulo_header = 'Estoque > Gerenciamento de Categorias';

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$sql = "SELECT * FROM categorias WHERE usuario_id = :usuario_id ORDER BY nome ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':usuario_id' => $_SESSION['id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias - Sistema de Gerenciamento</title>
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
                <div class="alert alert-success">
                    <?= $_SESSION['msg_sucesso']; unset($_SESSION['msg_sucesso']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['msg_erro'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['msg_erro']; unset($_SESSION['msg_erro']); ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="actions-container">
            <div class="search-bar"><i class="fas fa-search"></i><input type="text" placeholder="Pesquisar Categoria..."></div>
            <a href="categoria_formulario.php" class="btn-primary"><i class="fas fa-plus"></i> Cadastrar Categoria</a>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categorias)): ?>
                        <tr>
                            <td colspan="3" class="text-center">Nenhuma categoria cadastrada.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td><?= htmlspecialchars($categoria['nome']) ?></td>
                                <td class="actions">
                                    <a href="categoria_formulario.php?id=<?= $categoria['id'] ?>" class="btn-action btn-edit"><i class="fas fa-pencil-alt"></i></a>
                                    <a href="excluir_categoria.php?id=<?= $categoria['id'] ?>" class="btn-action btn-delete"><i class="fas fa-trash-alt"></i></a>
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

        function renderTable(categorias) {
            tableBody.innerHTML = '';
            if (categorias.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="3" class="text-center">Nenhuma categoria encontrada.</td></tr>';
                return;
            }
            categorias.forEach(categoria => {
                const row = `
                    <tr>
                        <td>${categoria.nome}</td>
                        <td class="actions">
                            <a href="categoria_formulario.php?id=${categoria.id}" class="btn-action btn-edit"><i class="fas fa-pencil-alt"></i></a>
                            <a href="excluir_categoria.php?id=${categoria.id}" class="btn-action btn-delete"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>`;
                tableBody.innerHTML += row;
            });
        }

        searchInput.addEventListener('keyup', async () => {
            const termo = searchInput.value;
            try {
                const response = await fetch(`buscar_categorias.php?termo=${encodeURIComponent(termo)}`);
                const categorias = await response.json();
                renderTable(categorias);
            } catch (error) {
                console.error('Erro ao buscar categorias:', error);
            }
        });
    </script>
</body>
</html>