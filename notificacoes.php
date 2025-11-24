<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['id'];
$hoje = date('Y-m-d');
$stmt = $pdo->prepare("SELECT titulo, inicio FROM eventos WHERE usuario_id = ? AND inicio = ?");
$stmt->execute([$usuario_id, $hoje]);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Notificações de Eventos</title>
</head>
<body>
    <h2>Eventos de hoje</h2>
    <?php if ($eventos): ?>
        <ul>
            <?php foreach ($eventos as $evento): ?>
                <li><?= htmlspecialchars($evento['titulo']) ?> (<?= htmlspecialchars($evento['inicio']) ?>)</li>
            <?php endforeach; ?>
        </ul>
        <a href="agenda.php?msg=notificacao">Você tem eventos importantes, verifique sua agenda</a>
    <?php else: ?>
        <p>Você não tem eventos para hoje.</p>
    <?php endif; ?>
</body>
</html>