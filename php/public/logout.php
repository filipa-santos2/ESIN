<?php include __DIR__ . '/../../includes/header.php'; ?>
// Finaliza a sessão e redireciona para a página de login
session_start();
session_unset();
session_destroy();
header('Location: login.php');

<?php include __DIR__ . '/../../includes/footer.php'; ?>
