<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: /plans.php?error=ID+inv%C3%A1lido');
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
  header('Location: /plans.php?error=Plano+n%C3%A3o+encontrado');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  array_splice($_SESSION['aitplans'], $index, 1);
  header('Location: /plans.php?success=Plano+apagado+com+sucesso');
  exit;
}

$plan = $_SESSION['aitplans'][$index];

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar plano AIT</h1>

  <p>
    Tens a certeza que queres apagar o plano do paciente
    <strong><?= htmlspecialchars((string)$plan['patient_id']) ?></strong>?
  </p>

  <form method="POST" action="/plan_delete.php?id=<?= urlencode((string)$id) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="/plans.php">Cancelar</a>
    </div>
  </form>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
