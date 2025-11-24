<?php
session_start();
include_once('config.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil.php');
    exit;
}

$id_to_edit = null;
$user_type = $_POST['user_type'] ?? null;

if ($user_type === 'empresa' && isset($_SESSION['id'])) {
    $id_to_edit = $_SESSION['id'];
} elseif ($user_type === 'fornecedor' && isset($_SESSION['id_fornecedor'])) {
    $id_to_edit = $_SESSION['id_fornecedor'];
}

if (!$id_to_edit || !$user_type) {
    $_SESSION['msg_erro_edit'] = "Erro: Usuário não identificado ou tipo inválido.";
    header('Location: perfil_editar.php');
    exit;
}

$profile_pic_path = null;
$uploadOk = 1;
$target_file = '';

// Lógica de Upload (Funciona independente do banco)
if (isset($_FILES["profile_pic"]) && $_FILES["profile_pic"]["error"] == 0) {
    $target_dir = "uploads/profile_pics/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $imageFileType = strtolower(pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid($user_type . '_' . $id_to_edit . '_', true) . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;

    $check = getimagesize($_FILES["profile_pic"]["tmp_name"]);
    if($check === false) {
        $_SESSION['msg_erro_edit'] = "Erro: O arquivo enviado não é uma imagem válida.";
        $uploadOk = 0;
    }

    if ($_FILES["profile_pic"]["size"] > 5000000) {
        $_SESSION['msg_erro_edit'] = "Erro: A imagem é muito grande (máximo 5MB).";
        $uploadOk = 0;
    }

    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        $_SESSION['msg_erro_edit'] = "Erro: Apenas arquivos JPG, JPEG, PNG e GIF são permitidos.";
        $uploadOk = 0;
    }

    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
            $profile_pic_path = $target_file;
        } else {
            $_SESSION['msg_erro_edit'] = "Erro ao fazer upload da imagem.";
            $uploadOk = 0;
        }
    }

    if ($uploadOk == 0) {
        header('Location: perfil_editar.php');
        exit;
    }
}

try {
    $pdo->beginTransaction();

    $old_pic_path = null;
    // Busca a foto antiga para apagar depois (SELECT padrão)
    if ($profile_pic_path) {
        if ($user_type === 'empresa') {
            $stmt_old_pic = $pdo->prepare("SELECT profile_pic FROM usuarios WHERE id = ?");
        } else {
            $stmt_old_pic = $pdo->prepare("SELECT profile_pic FROM fornecedores WHERE id = ?");
        }
        $stmt_old_pic->execute([$id_to_edit]);
        $old_pic_path = $stmt_old_pic->fetchColumn();
    }

    $sql_update = "";
    $params = [];

    // Montagem dinâmica do UPDATE (Compatível com Postgres)
    if ($user_type === 'empresa') {
        $sql_update = "UPDATE usuarios SET nome_empresa = :nome, email = :email, telefone = :telefone, cnpj = :cnpj, ramo_atuacao = :ramo, quantidade_funcionarios = :qtd_func, natureza_juridica = :natureza";
        $params = [
            ':nome' => $_POST['nome_exibicao'],
            ':email' => $_POST['email'],
            ':telefone' => $_POST['telefone'],
            ':cnpj' => preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? ''),
            ':ramo' => $_POST['ramo_atuacao'],
            ':qtd_func' => $_POST['quantidade_funcionarios'],
            ':natureza' => $_POST['natureza_juridica']
        ];
        if ($profile_pic_path) {
            $sql_update .= ", profile_pic = :pic";
            $params[':pic'] = $profile_pic_path;
        }
        $sql_update .= " WHERE id = :id";
        $params[':id'] = $id_to_edit;

    } elseif ($user_type === 'fornecedor') {
        $sql_update = "UPDATE fornecedores SET razao_social = :nome, email = :email, telefone = :telefone, cnpj = :cnpj";
         $params = [
            ':nome' => $_POST['nome_exibicao'],
            ':email' => $_POST['email'],
            ':telefone' => $_POST['telefone'],
            ':cnpj' => preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? '')
        ];
        if ($profile_pic_path) {
            $sql_update .= ", profile_pic = :pic";
            $params[':pic'] = $profile_pic_path;
        }
        $sql_update .= " WHERE id = :id";
        $params[':id'] = $id_to_edit;
    }

    if ($sql_update) {
        $stmt_update = $pdo->prepare($sql_update);
        if ($stmt_update->execute($params)) {

             if ($profile_pic_path && $old_pic_path && file_exists($old_pic_path) && $old_pic_path !== $profile_pic_path) {
                 @unlink($old_pic_path);
             }

            $pdo->commit();
            
            // Atualiza a sessão com a nova foto
            if ($profile_pic_path) {
                if ($user_type === 'empresa') unset($_SESSION['empresa_profile_pic']);
                if ($user_type === 'fornecedor') unset($_SESSION['fornecedor_profile_pic']);
            }
            
            $_SESSION['msg_sucesso_edit'] = "Perfil atualizado com sucesso!";
            header('Location: perfil.php');
            exit;
        } else {
             $pdo->rollBack();
             throw new Exception("Erro ao executar a atualização no banco de dados.");
        }
    } else {
        $pdo->rollBack();
        throw new Exception("Tipo de usuário inválido para atualização.");
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($profile_pic_path && file_exists($profile_pic_path)) {
        @unlink($profile_pic_path);
    }
    $_SESSION['msg_erro_edit'] = "Erro de Banco de Dados: " . $e->getMessage();
    header('Location: perfil_editar.php');
    exit;
} catch (Exception $e) {
     if ($pdo->inTransaction()) $pdo->rollBack();
    if ($profile_pic_path && file_exists($profile_pic_path)) {
        @unlink($profile_pic_path);
    }
    $_SESSION['msg_erro_edit'] = "Erro: " . $e->getMessage();
    header('Location: perfil_editar.php');
    exit;
}
?>