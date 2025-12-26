<?php
require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

unset($_SESSION['user']); // remove só o login (mantém outras sessões, ex: doctors/patients)

session_regenerate_id(true);

header('Location: ' . $BASE_URL . '/login.php?success=Sess%C3%A3o+terminada');
exit;
