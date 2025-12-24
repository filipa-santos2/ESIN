<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;


$BASE_URL = '/php/public';
?>
<!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sistema de Gestão de Imunoterapia</title>

  <!-- CSS -->
  <link rel="stylesheet" href="<?= $BASE_URL ?>/css/base.css">
  <link rel="stylesheet" href="<?= $BASE_URL ?>/css/components.css">
  <link rel="stylesheet" href="<?= $BASE_URL ?>/css/forms.css">
  <link rel="stylesheet" href="<?= $BASE_URL ?>/css/theme.css">
  <link rel="stylesheet" href="<?= $BASE_URL ?>/css/responsive.css">
</head>
<body>

<header class="topbar">
  <div class="brand">
    <a href="<?= $BASE_URL ?>/index.php">Imunoterapia</a>
  </div>

  <nav class="nav">
    <a href="<?= $BASE_URL ?>/login.php">Login</a>
    <a href="<?= $BASE_URL ?>/doctors.php">Médicos</a>
    <a href="<?= $BASE_URL ?>/patients.php">Pacientes</a>
    <a href="<?= $BASE_URL ?>/diseases.php">Doenças</a>
    <a href="<?= $BASE_URL ?>/allergens.php">Alergénios</a>
    <a href="<?= $BASE_URL ?>/plans.php">Planos AIT</a>
    <a href="<?= $BASE_URL ?>/visits.php">Visitas</a>
    <a href="<?= $BASE_URL ?>/diagnoses.php">Diagnósticos</a>
    <a href="<?= $BASE_URL ?>/products.php">Produtos</a>
    <a href="<?= $BASE_URL ?>/manufacturers.php">Fabricantes</a>
    <a href="<?= $BASE_URL ?>/tests.php">Testes</a>
  </nav>
</header>

<main class="container">

<?php if ($success): ?>
  <div class="msg msg-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
