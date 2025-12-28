<?php
require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// remover login
unset($_SESSION['user']);

// remover estado de definição/reset de password (importante)
unset($_SESSION['pending_set_password']);

// regenerar sessão
session_regenerate_id(true);

header('Location: ' . $BASE_URL . '/login.php?success=' . urlencode('Sessão terminada'));
exit;
