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
$password = (string)($_POST['password'] ?? '');

if ($email === '' || $password === '') {
  header('Location: ' . $BASE_URL . '/login.php?error=' . urlencode('Preenche o e-mail e a senha'));
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
  header('Location: ' . $BASE_URL . '/index.php?success=' . urlencode('Bem-vindo'));
  exit;
}

// --- DOCTOR via SQLite ---
$stmt = $pdo->prepare('
  SELECT "id","nome_completo","email","password_hash"
  FROM "Médicos"
  WHERE LOWER("email") = LOWER(?)
  LIMIT 1
');
$stmt->execute([$email]);
$found = $stmt->fetch();

if (!$found) {
  header('Location: ' . $BASE_URL . '/login.php?error=' . urlencode('E-mail ou senha incorretos'));
  exit;
}

// Se é primeiro acesso (sem password definida)
if (empty($found['password_hash'])) {
  // guardar quem vai definir password (mais seguro do que passar email no URL)
  $_SESSION['pending_set_password'] = [
    'doctor_id' => (int)$found['id'],
    'email'     => (string)$found['email'],
    'full_name' => (string)$found['nome_completo'],
    'role'      => 'doctor',
  ];

  header('Location: ' . $BASE_URL . '/reset.php?info=' . urlencode('Primeiro acesso: define uma password'));
  exit;
}

// Validar password normal
if (!password_verify($password, (string)$found['password_hash'])) {
  header('Location: ' . $BASE_URL . '/login.php?error=' . urlencode('E-mail ou senha incorretos'));
  exit;
}

// Login OK
session_regenerate_id(true);
$_SESSION['user'] = [
  'doctor_id' => (int)$found['id'],
  'full_name' => (string)$found['nome_completo'],
  'email'     => (string)$found['email'],
  'role'      => 'doctor',
];

header('Location: ' . $BASE_URL . '/index.php?success=' . urlencode('Bem-vindo'));
exit;
