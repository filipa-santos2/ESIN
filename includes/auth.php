<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function require_login(): void {
  global $BASE_URL;

  $public_pages = ['login.php', 'login_process.php', 'reset.php'];

  $current = basename($_SERVER['SCRIPT_NAME']);
  if (in_array($current, $public_pages, true)) return;

  if (empty($_SESSION['user'])) {
    header('Location: ' . $BASE_URL . '/login.php?error=Precisas+de+iniciar+sess%C3%A3o');
    exit;
  }
}

function require_admin(): void {
  global $BASE_URL;

  if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: ' . $BASE_URL . '/index.php?error=Acesso+reservado+ao+administrador');
    exit;
  }
}

function require_doctor(): void {
  global $BASE_URL;

  if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'doctor') {
    header('Location: ' . $BASE_URL . '/index.php?error=Acesso+reservado+a+m%C3%A9dicos');
    exit;
  }
}

function require_role(array $roles): void {
  global $BASE_URL;

  $role = $_SESSION['user']['role'] ?? '';
  if (empty($_SESSION['user']) || !in_array($role, $roles, true)) {
    header('Location: ' . $BASE_URL . '/index.php?error=Acesso+negado');
    exit;
  }
}
