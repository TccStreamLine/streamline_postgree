<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$titulo_header = 'Estoque > Importar Produtos';
$nome_empresa = $_SESSION['nome_empresa'] ?? 'Empresa';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Importar Produtos - Streamline</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/produto_formulario.css">
    <style>
        .import-box {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            border: 2px dashed #E5E7EB;
            transition: all 0.3s;
            cursor: pointer;
            margin: 20px 0;
        }
        .import-box:hover {
            border-color: #6D28D9;
            background-color: #F8F7FF;
            transform: scale(1.01);
        }
        .import-icon {
            font-size: 3.5rem;
            color: #9CA3AF;
            margin-bottom: 20px;
            transition: color 0.3s;
        }
        .info-steps {
            background-color: #F3F4F6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 5px solid #6D28D9;
        }
        .step-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
            color: #4B5563;
            font-size: 0.95rem;
        }
        .step-item i {
            color: #6D28D9;
            margin-right: 12px;
            margin-top: 3px;
        }
        .btn-download-model {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
            color: #6D28D9;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid #6D28D9;
            padding: 10px 20px;
            border-radius: 6px;
            transition: all 0.2s;
            background: white;
        }
        .btn-download-model:hover {
            background-color: #6D28D9;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'header.php'; ?>

        <div class="form-produto-container">
            <h3 class="form-produto-title">IMPORTAÇÃO INTELIGENTE (CSV)</h3>

            <?php if (isset($_SESSION['msg_erro'])): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px; padding: 15px; background: #FEE2E2; color: #DC2626; border-radius: 8px;">
                    <?= $_SESSION['msg_erro']; unset($_SESSION['msg_erro']); ?>
                </div>
            <?php endif; ?>

            <div class="info-steps">
                <h4 style="margin-bottom: 15px; color: #1F2937;">Instruções:</h4>
                <div class="step-item">
                    <i class="fas fa-download"></i>
                    <div>
                        <strong>1. Baixe o modelo:</strong> Clique no botão abaixo para obter o arquivo CSV padrão.
                    </div>
                </div>
                <div class="step-item">
                    <i class="fas fa-edit"></i>
                    <div>
                        <strong>2. Preencha os dados:</strong> Abra no Excel. 
                        <br><small>Dica: Digite o nome da Categoria. Se ela não existir, nós criamos para você!</small>
                    </div>
                </div>
                <div class="step-item">
                    <i class="fas fa-save"></i>
                    <div>
                        <strong>3. Salve como CSV:</strong> No Excel, escolha "Salvar Como" > "CSV (separado por ponto e vírgula)".
                    </div>
                </div>
                
                <a href="baixar_modelo.php" class="btn-download-model">
                    <i class="fas fa-file-download"></i> Baixar Modelo CSV
                </a>
            </div>

            <form action="processa_importacao.php" method="POST" enctype="multipart/form-data" id="form-import">
                <div class="import-box" onclick="document.getElementById('file-upload').click()">
                    <i class="fas fa-cloud-upload-alt import-icon"></i>
                    <h4 style="color: #374151; margin-bottom: 5px;">Clique aqui para selecionar o arquivo CSV</h4>
                    <p style="color: #9CA3AF; font-size: 0.9rem;">Ou arraste e solte o arquivo aqui</p>
                    <p id="file-name" style="margin-top: 15px; font-weight: bold; color: #6D28D9; font-size: 1.1rem;"></p>
                    <input type="file" name="arquivo_csv" id="file-upload" accept=".csv" style="display: none;" onchange="showFileName(this)" required>
                </div>

                <div class="form-produto-actions">
                    <a href="estoque.php" class="btn-cancel" style="text-decoration: none; color: #666; margin-right: 15px;">Cancelar</a>
                    <button type="submit" class="btn-produto-primary" id="btn-submit">
                        <i class="fas fa-magic"></i> Processar Importação
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script src="main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>

    <script>
        function showFileName(input) {
            const fileNameDisplay = document.getElementById('file-name');
            const icon = document.querySelector('.import-icon');
            if (input.files && input.files.length > 0) {
                fileNameDisplay.textContent = input.files[0].name;
                icon.style.color = '#10B981'; 
                icon.className = 'fas fa-file-csv import-icon';
            } else {
                fileNameDisplay.textContent = '';
                icon.style.color = '#9CA3AF';
            }
        }


        <?php if (isset($_SESSION['import_stats'])): ?>
            <?php 
                $stats = $_SESSION['import_stats'];
                unset($_SESSION['import_stats']); 
            ?>
            Swal.fire({
                title: 'Importação Concluída!',
                html: `
                    <div style="text-align: left; margin-top: 10px;">
                        <p style="color: #10B981; font-weight: bold; margin-bottom: 8px;">
                            <i class="fas fa-check-circle"></i> <?= $stats['cadastrados'] ?> produtos cadastrados.
                        </p>
                        <p style="color: #F59E0B; margin-bottom: 8px;">
                            <i class="fas fa-exclamation-circle"></i> <?= $stats['ignorados'] ?> ignorados (já existiam).
                        </p>
                        <?php if ($stats['erros'] > 0): ?>
                        <p style="color: #EF4444;">
                            <i class="fas fa-times-circle"></i> <?= $stats['erros'] ?> erros.
                        </p>
                        <?php endif; ?>
                    </div>
                `,
                icon: 'success',
                confirmButtonColor: '#6D28D9',
                confirmButtonText: 'Perfeito!'
            });
        <?php endif; ?>
        
        document.getElementById('form-import').addEventListener('submit', function() {
            const btn = document.getElementById('btn-submit');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
            btn.style.opacity = '0.7';
        });
    </script>
</body>
</html>