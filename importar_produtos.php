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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sistema.css">
    <link rel="stylesheet" href="css/produto_formulario.css">
    <style>
        .import-box {
            background: #fff;
            padding: 30px;
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
        }
        .import-icon {
            font-size: 3rem;
            color: #9CA3AF;
            margin-bottom: 15px;
        }
        .info-steps {
            background-color: #F3F4F6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #6D28D9;
        }
        .step-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: #4B5563;
        }
        .step-item i {
            color: #6D28D9;
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .btn-download-model {
            display: inline-block;
            margin-top: 10px;
            color: #6D28D9;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid #6D28D9;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.2s;
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
            <h3 class="form-produto-title">IMPORTAÇÃO EM MASSA (CSV)</h3>

            <div class="message-container">
                <?php if (isset($_SESSION['msg_erro'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['msg_erro']; unset($_SESSION['msg_erro']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['msg_sucesso'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['msg_sucesso']; unset($_SESSION['msg_sucesso']); ?></div>
                <?php endif; ?>
            </div>

            <div class="info-steps">
                <h4 style="margin-bottom: 15px; color: #1F2937;">Siga os passos:</h4>
                <div class="step-item">
                    <i class="fas fa-download"></i>
                    <span>1. Baixe a planilha modelo padrão do sistema.</span>
                </div>
                <div class="step-item">
                    <i class="fas fa-edit"></i>
                    <span>2. Abra no Excel, preencha os dados e salve.</span>
                </div>
                <div class="step-item">
                    <i class="fas fa-save"></i>
                    <span>3. Importante: Ao salvar, escolha o formato <strong>CSV (Separado por ponto e vírgula)</strong>.</span>
                </div>
                
                <a href="baixar_modelo.php" class="btn-download-model">
                    <i class="fas fa-file-download"></i> Baixar Modelo CSV
                </a>
            </div>

            <form action="processa_importacao.php" method="POST" enctype="multipart/form-data" id="form-import">
                <div class="import-box" onclick="document.getElementById('file-upload').click()">
                    <i class="fas fa-cloud-upload-alt import-icon"></i>
                    <h4 style="color: #374151; margin-bottom: 5px;">Clique para selecionar o arquivo</h4>
                    <p style="color: #9CA3AF; font-size: 0.9rem;">Formatos aceitos: .csv</p>
                    <p id="file-name" style="margin-top: 10px; font-weight: bold; color: #6D28D9;"></p>
                    <input type="file" name="arquivo_csv" id="file-upload" accept=".csv" style="display: none;" onchange="showFileName(this)" required>
                </div>

                <div class="form-produto-actions">
                    <a href="estoque.php" class="btn-cancel" style="text-decoration: none; color: #666; margin-right: 15px;">Cancelar</a>
                    <button type="submit" class="btn-produto-primary">
                        <i class="fas fa-check"></i> Processar Importação
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script src="main.js"></script>
    <script src="notificacoes.js"></script>
    <script src="notificacoes_fornecedor.js"></script>
    <script>
        function showFileName(input) {
            const fileNameDisplay = document.getElementById('file-name');
            if (input.files && input.files.length > 0) {
                fileNameDisplay.textContent = 'Arquivo selecionado: ' + input.files[0].name;
                document.querySelector('.import-icon').style.color = '#6D28D9';
            } else {
                fileNameDisplay.textContent = '';
            }
        }
    </script>
</body>
</html>