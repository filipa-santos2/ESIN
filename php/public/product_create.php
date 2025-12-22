<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['products'])) {
  $_SESSION['products'] = [];
}
if (!isset($_SESSION['manufacturers'])) {
  $_SESSION['manufacturers'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $manufacturer_id = (int)($_POST['manufacturer_id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $type = trim($_POST['type'] ?? '');
  $concentration = trim($_POST['concentration'] ?? '');
  $unit = trim($_POST['unit'] ?? '');
  $notes = trim($_POST['notes'] ?? '');

  if ($manufacturer_id <= 0 || $name === '' || $type === '' || $concentration === '' || $unit === '') {
    header('Location: /product_create.php?error=Preenche+todos+os+campos+obrigat%C3%B3rios');
    exit;
  }

  // confirmar se manufacturer_id existe mesmo
  $manufacturerExists = false;
  foreach ($_SESSION['manufacturers'] as $m) {
    if ((int)$m['manufacturer_id'] === $manufacturer_id) {
      $manufacturerExists = true;
      break;
    }
  }
  if (!$manufacturerExists) {
    header('Location: /product_create.php?error=Fabricante+inv%C3%A1lido');
    exit;
  }

  // gerar id novo
  $maxId = 0;
  foreach ($_SESSION['products'] as $p) {
    $maxId = max($maxId, (int)$p['product_id']);
  }
  $newId = $maxId + 1;

  $_SESSION['products'][] = [
    'product_id' => $newId,
    'manufacturer_id' => $manufacturer_id,
    'name' => $name,
    'type' => $type,
    'concentration' => $concentration,
    'unit' => $unit,
    'notes' => $notes,
  ];

  header('Location: /products.php?success=Produto+adicionado+com+sucesso');
  exit;
}

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Adicionar produto</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if (empty($_SESSION['manufacturers'])): ?>
    <div class="msg msg-error">
      Não podes criar produtos sem fabricantes.
      Vai a <a href="/manufacturers.php">Fabricantes</a> e cria pelo menos um.
    </div>
  <?php else: ?>
    <form method="POST" action="/product_create.php">
      <div class="field">
        <label for="manufacturer_id">Fabricante</label>
        <select id="manufacturer_id" name="manufacturer_id" required>
          <?php foreach ($_SESSION['manufacturers'] as $m): ?>
            <option value="<?= htmlspecialchars((string)$m['manufacturer_id']) ?>">
              <?= htmlspecialchars($m['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="name">Nome do produto</label>
        <input id="name" name="name" required>
      </div>

      <div class="field">
        <label for="type">Tipo</label>
        <input id="type" name="type" placeholder="Ex: extract / tablet / injection" required>
      </div>

      <div class="field">
        <label for="concentration">Concentração</label>
        <input id="concentration" name="concentration" placeholder="Ex: 10 000 SQ-U/mL" required>
      </div>

      <div class="field">
        <label for="unit">Unidade</label>
        <input id="unit" name="unit" placeholder="Ex: mL / mg / tablet" required>
      </div>

      <div class="field">
        <label for="notes">Notas (opcional)</label>
        <textarea id="notes" name="notes" rows="3"></textarea>
      </div>

      <div style="display:flex; gap:10px;">
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn" href="/products.php">Cancelar</a>
      </div>
    </form>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
