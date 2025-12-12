<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['id'];
set_time_limit(300);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['arquivo_csv'])) {
    $arquivo = $_FILES['arquivo_csv'];
    $ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

    if ($ext !== 'csv') {
        $_SESSION['msg_erro'] = "Formato inválido. Use CSV.";
        header('Location: importar_produtos.php');
        exit;
    }

    try {
        if (($handle = fopen($arquivo['tmp_name'], "r")) !== FALSE) {
            $pdo->beginTransaction();


            $stmt_check_prod = $pdo->prepare("SELECT id FROM produtos WHERE codigo_barras = ? AND usuario_id = ?");
            

            $stmt_busca_cat = $pdo->prepare("SELECT id FROM categorias WHERE nome ILIKE ? AND usuario_id = ?");

            $stmt_cria_cat = $pdo->prepare("INSERT INTO categorias (nome, usuario_id) VALUES (?, ?) RETURNING id");
            
            $stmt_busca_forn = $pdo->prepare("SELECT id FROM fornecedores WHERE razao_social ILIKE ? AND usuario_id = ?");

            $stmt_insert = $pdo->prepare("INSERT INTO produtos (usuario_id, codigo_barras, nome, quantidade_estoque, quantidade_minima, valor_compra, valor_venda, especificacao, categoria_id, fornecedor_id, status) 
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativo')");

            $stats = ['cadastrados' => 0, 'ignorados' => 0, 'erros' => 0];
            $linha = 0;

            while (($dados = fgetcsv($handle, 2000, ";")) !== FALSE) {
                $linha++;
                if ($linha == 1 || empty(trim($dados[1] ?? ''))) continue; 

                $codigo = trim($dados[0]);
                if (empty($codigo)) $codigo = 'IMP-' . uniqid();

                $nome = trim($dados[1]); 
                $qtd = (int)preg_replace('/[^0-9]/', '', $dados[2] ?? 0);
                $min = (int)preg_replace('/[^0-9]/', '', $dados[3] ?? 5);
                
                $compra = (float)str_replace(',', '.', str_replace(['R$', ' ', '.'], '', $dados[4] ?? '0'));
                $venda = (float)str_replace(',', '.', str_replace(['R$', ' ', '.'], '', $dados[5] ?? '0'));
                $desc = trim($dados[6] ?? '');
                
                $nome_cat = trim($dados[7] ?? '');
                $nome_forn = trim($dados[8] ?? '');


                $categoria_id = null;
                if (!empty($nome_cat)) {
                    $stmt_busca_cat->execute([$nome_cat, $usuario_id]);
                    $cat_existente = $stmt_busca_cat->fetchColumn();
                    
                    if ($cat_existente) {
                        $categoria_id = $cat_existente;
                    } else {
                        $stmt_cria_cat->execute([$nome_cat, $usuario_id]);
                        $categoria_id = $stmt_cria_cat->fetchColumn();
                    }
                }


                $fornecedor_id = null;
                if (!empty($nome_forn)) {
                    $stmt_busca_forn->execute(["%$nome_forn%", $usuario_id]); 
                    $forn_existente = $stmt_busca_forn->fetchColumn();
                    if ($forn_existente) {
                        $fornecedor_id = $forn_existente;
                    }
                }


                $stmt_check_prod->execute([$codigo, $usuario_id]);
                if ($stmt_check_prod->fetch()) {
                    $stats['ignorados']++;
                    continue;
                }


                if ($stmt_insert->execute([$usuario_id, $codigo, $nome, $qtd, $min, $compra, $venda, $desc, $categoria_id, $fornecedor_id])) {
                    $stats['cadastrados']++;
                } else {
                    $stats['erros']++;
                }
            }

            fclose($handle);
            $pdo->commit();


            $_SESSION['import_stats'] = $stats;

        } else {
            throw new Exception("Erro ao abrir arquivo.");
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['msg_erro'] = "Falha: " . $e->getMessage();
    }
}

header('Location: importar_produtos.php');
exit;
?>