<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/patients.php?error=ID+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['patients'])) {
  $_SESSION['patients'] = [];
}

$index = null;
for ($i = 0; $i < count($_SESSION['patients']); $i++) {
  if ((int)$_SESSION['patients'][$i]['patient_id'] === $id) {
    $index = $i;
    break;
  }
}

if ($index === null) {
  header('Location: ' . $BASE_URL . '/patients.php?error=Paciente+n%C3%A3o+encontrado');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  array_splice($_SESSION['patients'], $index, 1);
  header('Location: ' . $BASE_URL . '/patients.php?success=Paciente+apagado+com+sucesso');
  exit;
}

$patient = $_SESSION['patients'][$index];

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar paciente</h1>

  <p>
    Tens a certeza que queres apagar:
    <strong><?= htmlspecialchars($patient['full_name']) ?></strong>?
  </p>

  <form method="POST" action="<?= $BASE_URL ?>/patient_delete.php?id=<?= urlencode((string)$id) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="<?= $BASE_URL ?>/patients.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>