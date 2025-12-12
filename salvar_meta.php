<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    echo "erro";
    exit;
}

$nova_meta = filter_input(INPUT_POST, 'meta', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

if ($nova_meta) {
    $stmt = $pdo->prepare("UPDATE usuarios SET meta_mensal = ? WHERE id = ?");
    if ($stmt->execute([$nova_meta, $_SESSION['id']])) {
        echo "sucesso";
    } else {
        echo "erro_db";
    }
} else {
    echo "valor_invalido";
}
?>