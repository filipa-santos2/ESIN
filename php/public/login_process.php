<?php
require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . $BASE_URL . '/login.php');
  exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
  header('Location: ' . $BASE_URL . '/login.php?error=Preenche+o+e-mail+e+a+senha');
  exit;
}

// --- ADMIN seed (bootstrap) ---
$admin_email = 'admin@imunoterapia.pt';
$admin_hash  = password_hash('Admin123!', PASSWORD_DEFAULT); // demo

if (strtolower($email) === strtolower($admin_email) && password_verify($password, $admin_hash)) {
  session_regenerate_id(true);
  $_SESSION['user'] = [
    'doctor_id' => 0,
    'full_name' => 'Administrador',
    'email'     => $admin_email,
    'role'      => 'admin',
  ];
  header('Location: ' . $BASE_URL . '/index.php?success=Bem-vindo');
  exit;
}

// Garantir lista de doctors
if (!isset($_SESSION['doctors'])) {
  $_SESSION['doctors'] = [];
}

// Procurar médico por email
$found = null;
foreach ($_SESSION['doctors'] as $d) {
  if (!empty($d['email']) && strtolower($d['email']) === strtolower($email)) {
    $found = $d;
    break;
  }
}

// Se não existe médico com esse email
if (!$found) {
  header('Location: ' . $BASE_URL . '/login.php?error=E-mail+ou+senha+incorretos');
  exit;
}

// Se é primeiro acesso (sem password definida)
if (empty($found['password_hash'])) {
  header(
    'Location: ' . $BASE_URL .
    '/reset.php?email=' . urlencode($email) .
    '&info=Primeiro+acesso:+defina+uma+password'
  );
  exit;
}

// Validar password normal
if (!password_verify($password, $found['password_hash'])) {
  header('Location: ' . $BASE_URL . '/login.php?error=E-mail+ou+senha+incorretos');
  exit;
}

// Login OK
session_regenerate_id(true);
$_SESSION['user'] = [
  'doctor_id' => (int)($found['doctor_id'] ?? 0),
  'full_name' => $found['full_name'] ?? 'Médico',
  'email'     => $found['email'] ?? $email,
  'role'      => 'doctor',
];

header('Location: ' . $BASE_URL . '/index.php?success=Bem-vindo');
exit;
