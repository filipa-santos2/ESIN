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
  // não editar código (PK)
  $species     = trim($_POST['species'] ?? '');
  $common_name = trim($_POST['common_name'] ?? '');
  $category    = trim($_POST['category'] ?? '');

  if ($species === '' || $common_name === '' || $category === '') {
    header('Location: ' . $BASE_URL . '/allergen_edit.php?code=' . urlencode($code) . '&error=Preenche+todos+os+campos');
    exit;
  }

  $_SESSION['allergens'][$index]['species'] = $species;
  $_SESSION['allergens'][$index]['common_name'] = $common_name;
  $_SESSION['allergens'][$index]['category'] = $category;

  header('Location: ' . $BASE_URL . '/allergens.php?success=Alerg%C3%A9nio+atualizado+com+sucesso');
  exit;
}

$allergen = $_SESSION['allergens'][$index];

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar alergénio</h1>
  <p><small>Código WHO/IUIS: <?= htmlspecialchars($code) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/allergen_edit.php?code=<?= urlencode($code) ?>">
    <div class="field">
      <label for="who_iuis_code">Código WHO/IUIS</label>
      <input id="who_iuis_code" value="<?= htmlspecialchars($allergen['who_iuis_code']) ?>" disabled>
      <small>O código é identificador e não é editável.</small>
    </div>

    <div class="field">
      <label for="species">Espécie</label>
      <input id="species" name="species" value="<?= htmlspecialchars($allergen['species']) ?>" required>
    </div>

    <div class="field">
      <label for="common_name">Nome comum</label>
      <input id="common_name" name="common_name" value="<?= htmlspecialchars($allergen['common_name']) ?>" required>
    </div>

    <div class="field">
      <label for="category">Categoria</label>
      <input id="category" name="category" value="<?= htmlspecialchars($allergen['category']) ?>" required>
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/allergens.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
