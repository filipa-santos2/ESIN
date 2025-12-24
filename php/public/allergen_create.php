<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['allergens'])) {
  $_SESSION['allergens'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $who_iuis_code = trim($_POST['who_iuis_code'] ?? '');
  $species       = trim($_POST['species'] ?? '');
  $common_name   = trim($_POST['common_name'] ?? '');
  $category      = trim($_POST['category'] ?? '');

  if ($who_iuis_code === '' || $species === '' || $common_name === '' || $category === '') {
    header('Location: ' . $BASE_URL . '/allergen_create.php?error=Preenche+todos+os+campos');
    exit;
  }

  // UNIQUE(who_iuis_code)
  foreach ($_SESSION['allergens'] as $a) {
    if ((string)$a['who_iuis_code'] === (string)$who_iuis_code) {
      header('Location: ' . $BASE_URL . '/allergen_create.php?error=J%C3%A1+existe+um+alerg%C3%A9nio+com+esse+c%C3%B3digo');
      exit;
    }
  }

  $_SESSION['allergens'][] = [
    'who_iuis_code' => $who_iuis_code,
    'species' => $species,
    'common_name' => $common_name,
    'category' => $category,
  ];

  header('Location: ' . $BASE_URL . '/allergens.php?success=Alerg%C3%A9nio+adicionado+com+sucesso');
  exit;
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Adicionar alergénio</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/allergen_create.php">
    <div class="field">
      <label for="who_iuis_code">Código WHO/IUIS</label>
      <input id="who_iuis_code" name="who_iuis_code" placeholder="Ex: t1" required>
    </div>

    <div class="field">
      <label for="species">Espécie</label>
      <input id="species" name="species" placeholder="Ex: Dermatophagoides pteronyssinus" required>
    </div>

    <div class="field">
      <label for="common_name">Nome comum</label>
      <input id="common_name" name="common_name" placeholder="Ex: Ácaro do pó" required>
    </div>

    <div class="field">
      <label for="category">Categoria</label>
      <input id="category" name="category" placeholder="Ex: pollen / mite / animal / mold" required>
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn" href="<?= $BASE_URL ?>/allergens.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
