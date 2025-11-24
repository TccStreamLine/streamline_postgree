<?php
session_start();
include_once('config.php');

$pagina_ativa = 'servicos';
$titulo_header = 'Serviços Prestados';

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['id'];
// Consulta padrão compatível com PostgreSQL
$servicos = $pdo->prepare("SELECT * FROM servicos_prestados WHERE usuario_id = ? AND status = 'ativo' ORDER BY data_prestacao DESC");
$servicos->execute([$usuario_id]);
$lista_servicos = $servicos->fetchAll(PDO::FETCH_ASSOC);
$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serviços Prestados - Streamline</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/estoque.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="actions-container">
            <div class="search-bar"><i class="fas fa-search"></i><input type="text" placeholder="Pesquisar Serviço..."></div>
            <a href="servico_formulario.php" class="btn-primary"><i class="fas fa-plus"></i> Cadastrar Serviço</a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Valor (R$)</th>
                        <th>Produtos Usados</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_servicos as $servico): ?>
                        <tr>
                            <td><?= htmlspecialchars($servico['nome_servico']) ?></td>
                            <td><?= number_format($servico['valor_venda'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($servico['produtos_usados']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($servico['data_prestacao'])) ?></td>
                            <td class="actions">
                                <a href="servico_formulario.php?id=<?= $servico['id'] ?>" class="btn-action btn-edit"><i class="fas fa-pencil-alt"></i></a>
                                <a href="excluir_servico.php?id=<?= $servico['id'] ?>" class="btn-action btn-delete"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($lista_servicos)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Nenhum serviço cadastrado.</td>
                        </tr>
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

        function formatDate(dateString) {
            const options = {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            // Tratamento necessário para garantir que o JS interprete corretamente o formato ISO do Postgres
            const dateObj = new Date(dateString); 
            if (isNaN(dateObj)) return dateString; // Retorna string original se não puder formatar
            
            return dateObj.toLocaleDateString('pt-BR', options);
        }

        function formatCurrency(value) {
            return parseFloat(value).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function renderTable(servicos) {
            tableBody.innerHTML = '';
            if (servicos.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Nenhum serviço encontrado.</td></tr>';
                return;
            }

            servicos.forEach(servico => {
                const row = `
                    <tr>
                        <td>${servico.nome_servico}</td>
                        <td>R$ ${formatCurrency(servico.valor_venda)}</td>
                        <td>${servico.produtos_usados || ''}</td>
                        <td>${formatDate(servico.data_prestacao)}</td>
                        <td class="actions">
                            <a href="servico_formulario.php?id=${servico.id}" class="btn-action btn-edit"><i class="fas fa-pencil-alt"></i></a>
                            <a href="excluir_servico.php?id=${servico.id}" class="btn-action btn-delete"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });
        }

        searchInput.addEventListener('keyup', async () => {
            const termo = searchInput.value;
            try {
                // Chama o arquivo já corrigido (buscar_servicos.php)
                const response = await fetch(`buscar_servicos.php?termo=${encodeURIComponent(termo)}`);
                const servicos = await response.json();
                renderTable(servicos);
            } catch (error) {
                console.error('Erro ao buscar serviços:', error);
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Erro ao carregar os dados.</td></tr>';
            }
        });
    </script>
    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
</body>

</html>