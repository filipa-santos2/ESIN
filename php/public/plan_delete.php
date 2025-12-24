<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/plans.php?error=ID+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['aitplans'])) {
  $_SESSION['aitplans'] = [];
}

$index = null;
for ($i = 0; $i < count($_SESSION['aitplans']); $i++) {
  if ((int)$_SESSION['aitplans'][$i]['aitplan_id'] === $id) {
    $index = $i;
    break;
  }
}

if ($index === null) {
  header('Location: ' . $BASE_URL . '/plans.php?error=Plano+n%C3%A3o+encontrado');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  array_splice($_SESSION['aitplans'], $index, 1);
  header('Location: ' . $BASE_URL . '/plans.php?success=Plano+apagado+com+sucesso');
  exit;
}

$plan = $_SESSION['aitplans'][$index];

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar plano AIT</h1>

  <p>
    Tens a certeza que queres apagar o plano do paciente
    <strong><?= htmlspecialchars((string)$plan['patient_id']) ?></strong>?
  </p>

  <form method="POST" action="<?= $BASE_URL ?>/plan_delete.php?id=<?= urlencode((string)$id) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="<?= $BASE_URL ?>/plans.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
