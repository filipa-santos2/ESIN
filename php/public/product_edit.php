<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: /products.php?error=ID+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['products'])) {
  $_SESSION['products'] = [];
}
if (!isset($_SESSION['manufacturers'])) {
  $_SESSION['manufacturers'] = [];
}

$index = null;
for ($i = 0; $i < count($_SESSION['products']); $i++) {
  if ((int)$_SESSION['products'][$i]['product_id'] === $id) {
    $index = $i;
    break;
  }
}

if ($index === null) {
  header('Location: /products.php?error=Produto+n%C3%A3o+encontrado');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $manufacturer_id = (int)($_POST['manufacturer_id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $type = trim($_POST['type'] ?? '');
  $concentration = trim($_POST['concentration'] ?? '');
  $unit = trim($_POST['unit'] ?? '');
  $notes = trim($_POST['notes'] ?? '');

  if ($manufacturer_id <= 0 || $name === '' || $type === '' || $concentration === '' || $unit === '') {
    header('Location: /product_edit.php?id=' . urlencode((string)$id) . '&error=Preenche+todos+os+campos+obrigat%C3%B3rios');
    exit;
  }

  $manufacturerExists = false;
  foreach ($_SESSION['manufacturers'] as $m) {
    if ((int)$m['manufacturer_id'] === $manufacturer_id) {
      $manufacturerExists = true;
      break;
    }
  }
  if (!$manufacturerExists) {
    header('Location: /product_edit.php?id=' . urlencode((string)$id) . '&error=Fabricante+inv%C3%A1lido');
    exit;
  }

  $_SESSION['products'][$index]['manufacturer_id'] = $manufacturer_id;
  $_SESSION['products'][$index]['name'] = $name;
  $_SESSION['products'][$index]['type'] = $type;
  $_SESSION['products'][$index]['concentration'] = $concentration;
  $_SESSION['products'][$index]['unit'] = $unit;
  $_SESSION['products'][$index]['notes'] = $notes;

  header('Location: /products.php?success=Produto+atualizado+com+sucesso');
  exit;
}

$product = $_SESSION['products'][$index];

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar produto</h1>
  <p><small>ID: <?= htmlspecialchars((string)$id) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="/product_edit.php?id=<?= urlencode((string)$id) ?>">
    <div class="field">
      <label for="manufacturer_id">Fabricante</label>
      <select id="manufacturer_id" name="manufacturer_id" required>
        <?php foreach ($_SESSION['manufacturers'] as $m): ?>
          <option value="<?= htmlspecialchars((string)$m['manufacturer_id']) ?>"
            <?= ((int)$m['manufacturer_id'] === (int)$product['manufacturer_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($m['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="name">Nome do produto</label>
      <input id="name" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
    </div>

    <div class="field">
      <label for="type">Tipo</label>
      <input id="type" name="type" value="<?= htmlspecialchars($product['type']) ?>" required>
    </div>

    <div class="field">
      <label for="concentration">Concentração</label>
      <input id="concentration" name="concentration" value="<?= htmlspecialchars($product['concentration']) ?>" required>
    </div>

    <div class="field">
      <label for="unit">Unidade</label>
      <input id="unit" name="unit" value="<?= htmlspecialchars($product['unit']) ?>" required>
    </div>

    <div class="field">
      <label for="notes">Notas (opcional)</label>
      <textarea id="notes" name="notes" rows="3"><?= htmlspecialchars($product['notes']) ?></textarea>
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="/products.php">Cancelar</a>
    </div>
  </form>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>