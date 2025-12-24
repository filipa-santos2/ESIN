<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$code = strtoupper(trim($_GET['code'] ?? ''));
if ($code === '') {
  header('Location: ' . $BASE_URL . '/diseases.php?error=C%C3%B3digo+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['diseases'])) {
  $_SESSION['diseases'] = [];
}

$index = null;
for ($i = 0; $i < count($_SESSION['diseases']); $i++) {
  if ((string)$_SESSION['diseases'][$i]['icd11_code'] === (string)$code) {
    $index = $i;
    break;
  }
}

if ($index === null) {
  header('Location: ' . $BASE_URL . '/diseases.php?error=Doen%C3%A7a+n%C3%A3o+encontrada');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  array_splice($_SESSION['diseases'], $index, 1);
  header('Location: ' . $BASE_URL . '/diseases.php?success=Doen%C3%A7a+apagada+com+sucesso');
  exit;
}

$disease = $_SESSION['diseases'][$index];

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar doença</h1>

  <p>
    Tens a certeza que queres apagar:
    <strong><?= htmlspecialchars($disease['name']) ?></strong>
    (<?= htmlspecialchars($disease['icd11_code']) ?>)?
  </p>

  <form method="POST" action="<?= $BASE_URL ?>/disease_delete.php?code=<?= urlencode($code) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="<?= $BASE_URL ?>/diseases.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>