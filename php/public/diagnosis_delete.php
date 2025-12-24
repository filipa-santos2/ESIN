<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/diagnoses.php?error=ID+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['diagnoses'])) $_SESSION['diagnoses'] = [];

$index = null;
for ($i = 0; $i < count($_SESSION['diagnoses']); $i++) {
  if ((int)$_SESSION['diagnoses'][$i]['diagnosis_id'] === $id) { $index = $i; break; }
}
if ($index === null) {
  header('Location: ' . $BASE_URL . '/diagnoses.php?error=Diagn%C3%B3stico+n%C3%A3o+encontrado');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  array_splice($_SESSION['diagnoses'], $index, 1);
  header('Location: ' . $BASE_URL . '/diagnoses.php?success=Diagn%C3%B3stico+apagado+com+sucesso');
  exit;
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar diagnóstico</h1>

  <p>Tens a certeza que queres apagar este diagnóstico?</p>

  <form method="POST" action="<?= $BASE_URL ?>/diagnosis_delete.php?id=<?= urlencode((string)$id) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="<?= $BASE_URL ?>/diagnoses.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
