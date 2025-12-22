<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;
?>
<!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sistema de Gestão de Imunoterapia</title>

  <!-- CSS: atenção ao caminho -->
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/components.css">
  <link rel="stylesheet" href="/css/forms.css">
  <link rel="stylesheet" href="/css/theme.css">
  <link rel="stylesheet" href="/css/responsive.css">

</head>
<body>

<header class="topbar">
  <div class="brand">
    <a href="/index.php">Imunoterapia</a>
  </div>

  <nav class="nav">
    <a href="/login.php">Login</a>
    <a href="/doctors.php">Médicos</a>
    <a href="/patients.php">Pacientes</a>
    <a href="/diseases.php">Doenças</a>
    <a href="/allergens.php">Alergénios</a>
    <a href="/plans.php">Planos AIT</a>
    <a href="/visits.php">Visitas</a>
    <a href="/diagnoses.php">Diagnósticos</a>
    <a href="/products.php">Produtos</a>
    <a href="/manufacturers.php">Fabricantes</a>
    <a href="/tests.php">Testes</a>



  </nav>
</header>

<main class="container">

<?php if ($success): ?>
  <div class="msg msg-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>