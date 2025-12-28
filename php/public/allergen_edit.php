<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_admin();

require_once __DIR__ . '/../../includes/config.php';


$code = trim($_GET['code'] ?? '');
if ($code === '') {
  header('Location: ' . $BASE_URL . '/allergens.php?error=' . urlencode('Código inválido'));
  exit;
}

function go_error(string $code, string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/allergen_edit.php?code=' . urlencode($code) . '&error=' . urlencode($msg));
  exit;
}

$CATEGORIES = ['mite', 'pollen', 'dander'];

// buscar existente
$stmt = $pdo->prepare('
  SELECT "código_who_iuis","espécie","nome_comum","nome_bioquímico","categoria"
  FROM "Alergénios"
  WHERE "código_who_iuis" = :code
');
$stmt->execute([':code' => $code]);
$allergen = $stmt->fetch();

if (!$allergen) {
  header('Location: ' . $BASE_URL . '/allergens.php?error=' . urlencode('Alergénio não encontrado'));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $species = trim($_POST['espécie'] ?? '');
  $common = trim($_POST['nome_comum'] ?? '');
  $biochem = trim($_POST['nome_bioquímico'] ?? '');
  $category = trim($_POST['categoria'] ?? '');

  if ($species === '' || $common === '' || $category === '') {
    go_error($code, 'Preenche os campos obrigatórios.');
  }
  if (!in_array($category, $CATEGORIES, true)) {
    go_error($code, 'Categoria inválida.');
  }

  $upd = $pdo->prepare('
    UPDATE "Alergénios"
    SET "espécie" = :species,
        "nome_comum" = :common,
        "nome_bioquímico" = :biochem,
        "categoria" = :category
    WHERE "código_who_iuis" = :code
  ');
  $upd->execute([
    ':species' => $species,
    ':common' => $common,
    ':biochem' => ($biochem === '' ? null : $biochem),
    ':category' => $category,
    ':code' => $code,
  ]);

  header('Location: ' . $BASE_URL . '/allergens.php?success=' . urlencode('Alergénio atualizado com sucesso'));
  exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar alergénio</h1>
  <p><small>Código: <?= htmlspecialchars($allergen['código_who_iuis']) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/allergen_edit.php?code=<?= urlencode($code) ?>">
    <div class="field">
      <label>Código WHO/IUIS</label>
      <div class="input-like"><?= htmlspecialchars($allergen['código_who_iuis']) ?></div>
    </div>

    <div class="field">
      <label for="espécie">Espécie</label>
      <input id="espécie" name="espécie" value="<?= htmlspecialchars($allergen['espécie']) ?>" required>
    </div>

    <div class="field">
      <label for="nome_comum">Nome comum</label>
      <input id="nome_comum" name="nome_comum" value="<?= htmlspecialchars($allergen['nome_comum']) ?>" required>
    </div>

    <div class="field">
      <label for="nome_bioquímico">Nome bioquímico (opcional)</label>
      <input id="nome_bioquímico" name="nome_bioquímico" value="<?= htmlspecialchars($allergen['nome_bioquímico'] ?? '') ?>">
    </div>

    <div class="field">
      <label for="categoria">Categoria</label>
      <select id="categoria" name="categoria" required>
          <option value="mite">ácaros</option>
          <option value="pollen">pólen</option>
          <option value="dander">epitélio animal</option>
        <?php foreach ($CATEGORIES as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>" <?= ($allergen['categoria'] === $c) ? 'selected' : '' ?>>
            <?= htmlspecialchars($c) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/allergens.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
