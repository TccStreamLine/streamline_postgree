<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['id'];
// Aumenta tempo limite para arquivos grandes
set_time_limit(300);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['arquivo_csv'])) {
    $arquivo = $_FILES['arquivo_csv'];
    
    // Verifica extensão
    $ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        $_SESSION['msg_erro'] = "Por favor, envie um arquivo com extensão .csv";
        header('Location: importar_produtos.php');
        exit;
    }

    try {
        if (($handle = fopen($arquivo['tmp_name'], "r")) !== FALSE) {
            $pdo->beginTransaction();
            
            // Prepara queries
            $stmt_check = $pdo->prepare("SELECT id FROM produtos WHERE codigo_barras = ? AND usuario_id = ?");
            $stmt_insert = $pdo->prepare("INSERT INTO produtos (usuario_id, codigo_barras, nome, quantidade_estoque, quantidade_minima, valor_compra, valor_venda, especificacao, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ativo')");

            $cadastrados = 0;
            $ignorados = 0;
            $linha = 0;

            while (($dados = fgetcsv($handle, 2000, ";")) !== FALSE) {
                $linha++;
                
                // Pula cabeçalho ou linhas vazias
                if ($linha == 1 || empty($dados[1])) continue;

                // Mapeamento (Index 0 a 6 conforme modelo)
                $codigo = trim($dados[0]);
                // Gera código se vazio
                if (empty($codigo)) $codigo = 'IMP-' . uniqid(); 
                
                $nome = utf8_encode(trim($dados[1])); // Garante acentuação se vier do Excel antigo
                if(mb_check_encoding($nome, 'UTF-8')) $nome = $nome; // Se já for UTF8 mantém
                
                $qtd = (int)preg_replace('/[^0-9]/', '', $dados[2] ?? 0);
                $min = (int)preg_replace('/[^0-9]/', '', $dados[3] ?? 5);
                
                // Tratamento de Moeda (1.200,50 -> 1200.50)
                $compra = str_replace(['R$', ' ', '.'], '', $dados[4] ?? '0');
                $compra = (float)str_replace(',', '.', $compra);
                
                $venda = str_replace(['R$', ' ', '.'], '', $dados[5] ?? '0');
                $venda = (float)str_replace(',', '.', $venda);
                
                $desc = utf8_encode(trim($dados[6] ?? ''));

                // Verifica duplicidade
                $stmt_check->execute([$codigo, $usuario_id]);
                if ($stmt_check->fetch()) {
                    $ignorados++;
                    continue;
                }

                // Insere
                $stmt_insert->execute([$usuario_id, $codigo, $nome, $qtd, $min, $compra, $venda, $desc]);
                $cadastrados++;
            }

            fclose($handle);
            $pdo->commit();

            $_SESSION['msg_sucesso'] = "Importação Concluída!<br>✅ Novos cadastros: $cadastrados<br>⚠️ Ignorados (já existiam): $ignorados";

        } else {
            throw new Exception("Não foi possível ler o arquivo.");
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['msg_erro'] = "Erro na importação: " . $e->getMessage();
    }
}

header('Location: importar_produtos.php');
exit;
?>