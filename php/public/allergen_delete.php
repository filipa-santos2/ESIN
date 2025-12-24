<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$code = trim($_GET['code'] ?? '');
if ($code === '') {
  header('Location: ' . $BASE_URL . '/allergens.php?error=C%C3%B3digo+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['allergens'])) {
  $_SESSION['allergens'] = [];
}

$index = null;
for ($i = 0; $i < count($_SESSION['allergens']); $i++) {
  if ((string)$_SESSION['allergens'][$i]['who_iuis_code'] === (string)$code) {
    $index = $i;
    break;
  }
}

if ($index === null) {
  header('Location: ' . $BASE_URL . '/allergens.php?error=Alerg%C3%A9nio+n%C3%A3o+encontrado');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  array_splice($_SESSION['allergens'], $index, 1);
  header('Location: ' . $BASE_URL . '/allergens.php?success=Alerg%C3%A9nio+apagado+com+sucesso');
  exit;
}

$allergen = $_SESSION['allergens'][$index];

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar alergénio</h1>

  <p>
    Tens a certeza que queres apagar:
    <strong><?= htmlspecialchars($allergen['common_name']) ?></strong>
    (<?= htmlspecialchars($allergen['who_iuis_code']) ?>)?
  </p>

  <form method="POST" action="<?= $BASE_URL ?>/allergen_delete.php?code=<?= urlencode($code) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="<?= $BASE_URL ?>/allergens.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
