<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;


$BASE_URL = '/php/public';

/* ===== MENU ATIVO ===== */
$current_page = basename($_SERVER['PHP_SELF']);

function nav_active(array $pages): string {
  global $current_page;
  return in_array($current_page, $pages, true) ? 'active' : '';
}
?>
<!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sistema de Gestão de Imunoterapia</title>

  <!-- CSS -->
  <link rel="stylesheet" href="<?= $BASE_URL ?>/css/base.css?v=<?= filemtime(__DIR__ . '/../php/public/css/base.css') ?>">
  <link rel="stylesheet" href="<?= $BASE_URL ?>/css/components.css?v=<?= filemtime(__DIR__ . '/../php/public/css/components.css') ?>">
  <link rel="stylesheet" href="<?= $BASE_URL ?>/css/forms.css?v=<?= filemtime(__DIR__ . '/../php/public/css/forms.css') ?>">
  <link rel="stylesheet" href="<?= $BASE_URL ?>/css/theme.css?v=<?= filemtime(__DIR__ . '/../php/public/css/theme.css') ?>">
  <link rel="stylesheet" href="<?= $BASE_URL ?>/css/responsive.css?v=<?= filemtime(__DIR__ . '/../php/public/css/responsive.css') ?>">

</head>
<body>

<header class="topbar">
  <div class="brand">
    <a href="<?= $BASE_URL ?>/index.php">Imunoterapia</a>
  </div>

  <nav class="nav">
    <a class="<?= nav_active(['login.php','reset.php']) ?>" 
      href="<?= $BASE_URL ?>/login.php">Login</a>

    <a class="<?= nav_active(['doctors.php','doctors_create.php','doctors_edit.php']) ?>"
      href="<?= $BASE_URL ?>/doctors.php">Médicos</a>

    <a class="<?= nav_active(['patients.php','patients_create.php','patients_edit.php']) ?>"
      href="<?= $BASE_URL ?>/patients.php">Pacientes</a>

    <a class="<?= nav_active(['diseases.php','disease_create.php','disease_edit.php','disease_delete.php']) ?>"
      href="<?= $BASE_URL ?>/diseases.php">Doenças</a>

    <a class="<?= nav_active(['allergens.php','allergen_create.php','allergen_edit.php','allergen_delete.php']) ?>"
      href="<?= $BASE_URL ?>/allergens.php">Alergénios</a>

    <a class="<?= nav_active(['plans.php','plan_create.php','plan_edit.php','plan_delete.php','plan_allergens.php','plan_view.php']) ?>"
      href="<?= $BASE_URL ?>/plans.php">Planos AIT</a>

    <a class="<?= nav_active(['visits.php','visit_create.php','visit_edit.php','visit_delete.php']) ?>"
      href="<?= $BASE_URL ?>/visits.php">Visitas</a>

    <a class="<?= nav_active(['diagnoses.php','diagnosis_create.php','diagnosis_edit.php','diagnosis_delete.php']) ?>"
      href="<?= $BASE_URL ?>/diagnoses.php">Diagnósticos</a>

    <a class="<?= nav_active(['products.php','product_create.php','product_edit.php','product_delete.php']) ?>"
      href="<?= $BASE_URL ?>/products.php">Produtos</a>

    <a class="<?= nav_active(['manufacturers.php','manufacturer_create.php','manufacturer_edit.php','manufacturer_delete.php']) ?>"
      href="<?= $BASE_URL ?>/manufacturers.php">Fabricantes</a>

    <a class="<?= nav_active(['tests.php','test_create.php','test_edit.php','test_delete.php']) ?>"
      href="<?= $BASE_URL ?>/tests.php">Testes</a>

  </nav>
</header>

<main class="container">

<?php if ($success): ?>
  <div class="msg msg-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
