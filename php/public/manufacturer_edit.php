<?php
require_once __DIR__ . '/../../includes/config.php';
require_admin();
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/manufacturers.php?error=ID+inv%C3%A1lido');
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
  header('Location: ' . $BASE_URL . '/manufacturers.php?error=Fabricante+n%C3%A3o+encontrado');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name    = trim($_POST['name'] ?? '');
  $country = trim($_POST['country'] ?? '');
  $phone   = trim($_POST['phone'] ?? '');
  $email   = trim($_POST['email'] ?? '');

  if ($name === '' || $country === '') {
    header('Location: ' . $BASE_URL . '/manufacturer_edit.php?id=' . urlencode((string)$id) . '&error=Preenche+nome+e+pa%C3%ADs');
    exit;
  }

  // Telefone opcional, mas se existir tem de ser telemóvel PT válido
  if ($phone !== '' && !preg_match('/^(91|92|93|96)\d{7}$/', $phone)) {
    header('Location: ' . $BASE_URL . '/manufacturer_edit.php?id=' . urlencode((string)$id) . '&error=' . urlencode('Telefone inválido (telemóvel português, ex: 91xxxxxxx)'));
    exit;
  }


  // UNIQUE(name) (ignorando o próprio)
  foreach ($_SESSION['manufacturers'] as $m) {
    if ((int)$m['manufacturer_id'] !== $id &&
        strtolower((string)$m['name']) === strtolower((string)$name)) {
      header('Location: ' . $BASE_URL . '/manufacturer_edit.php?id=' . urlencode((string)$id) . '&error=J%C3%A1+existe+um+fabricante+com+esse+nome');
      exit;
    }
  }

  $_SESSION['manufacturers'][$index]['name'] = $name;
  $_SESSION['manufacturers'][$index]['country'] = $country;
  $_SESSION['manufacturers'][$index]['phone'] = $phone;
  $_SESSION['manufacturers'][$index]['email'] = $email;

  header('Location: ' . $BASE_URL . '/manufacturers.php?success=Fabricante+atualizado+com+sucesso');
  exit;
}

$manufacturer = $_SESSION['manufacturers'][$index];

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar fabricante</h1>
  <p><small>ID: <?= htmlspecialchars((string)$id) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/manufacturer_edit.php?id=<?= urlencode((string)$id) ?>">
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
      <input
        id="phone"
        name="phone"
        type="tel"
        value="<?= htmlspecialchars($manufacturer['phone']) ?>"
        inputmode="numeric"
        autocomplete="tel"
        pattern="^(91|92|93|96)[0-9]{7}$"
        minlength="9"
        maxlength="9"
        title="Introduz um telemóvel português válido (91, 92, 93 ou 96)"
      >
    </div>

    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" value="<?= htmlspecialchars($manufacturer['email']) ?>">
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/manufacturers.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
