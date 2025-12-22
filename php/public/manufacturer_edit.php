<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: /manufacturers.php?error=ID+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['manufacturers'])) {
  $_SESSION['manufacturers'] = [];
}

$index = null;
for ($i = 0; $i < count($_SESSION['manufacturers']); $i++) {
  if ((int)$_SESSION['manufacturers'][$i]['manufacturer_id'] === $id) {
    $index = $i;
    break;
  }
}

if ($index === null) {
  header('Location: /manufacturers.php?error=Fabricante+n%C3%A3o+encontrado');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name    = trim($_POST['name'] ?? '');
  $country = trim($_POST['country'] ?? '');
  $phone   = trim($_POST['phone'] ?? '');
  $email   = trim($_POST['email'] ?? '');

  if ($name === '' || $country === '') {
    header('Location: /manufacturer_edit.php?id=' . urlencode((string)$id) . '&error=Preenche+nome+e+pa%C3%ADs');
    exit;
  }

  // UNIQUE(name) (ignorando o próprio)
  foreach ($_SESSION['manufacturers'] as $m) {
    if ((int)$m['manufacturer_id'] !== $id &&
        strtolower((string)$m['name']) === strtolower((string)$name)) {
      header('Location: /manufacturer_edit.php?id=' . urlencode((string)$id) . '&error=J%C3%A1+existe+um+fabricante+com+esse+nome');
      exit;
    }
  }

  $_SESSION['manufacturers'][$index]['name'] = $name;
  $_SESSION['manufacturers'][$index]['country'] = $country;
  $_SESSION['manufacturers'][$index]['phone'] = $phone;
  $_SESSION['manufacturers'][$index]['email'] = $email;

  header('Location: /manufacturers.php?success=Fabricante+atualizado+com+sucesso');
  exit;
}

$manufacturer = $_SESSION['manufacturers'][$index];

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar fabricante</h1>
  <p><small>ID: <?= htmlspecialchars((string)$id) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="/manufacturer_edit.php?id=<?= urlencode((string)$id) ?>">
    <div class="field">
      <label for="name">Nome</label>
      <input id="name" name="name" value="<?= htmlspecialchars($manufacturer['name']) ?>" required>
    </div>

    <div class="field">
      <label for="country">País</label>
      <input id="country" name="country" value="<?= htmlspecialchars($manufacturer['country']) ?>" required>
    </div>

    <div class="field">
      <label for="phone">Telefone</label>
      <input id="phone" name="phone" value="<?= htmlspecialchars($manufacturer['phone']) ?>">
    </div>

    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" value="<?= htmlspecialchars($manufacturer['email']) ?>">
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="/manufacturers.php">Cancelar</a>
    </div>
  </form>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
