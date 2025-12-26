<?php
require_once __DIR__ . '/../../includes/config.php';
require_admin();
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['manufacturers'])) {
  $_SESSION['manufacturers'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name    = trim($_POST['name'] ?? '');
  $country = trim($_POST['country'] ?? '');
  $phone   = trim($_POST['phone'] ?? '');
  $email   = trim($_POST['email'] ?? '');

  if ($name === '' || $country === '') {
    header('Location: ' . $BASE_URL . '/manufacturer_create.php?error=Preenche+nome+e+pa%C3%ADs');
    exit;
  }

  // Telefone opcional, mas se existir tem de ser telemóvel PT válido
  if ($phone !== '' && !preg_match('/^(91|92|93|96)\d{7}$/', $phone)) {
    header('Location: ' . $BASE_URL . '/manufacturer_create.php?error=' . urlencode('Telefone inválido (telemóvel português, ex: 91xxxxxxx)'));
    exit;
  }

  // UNIQUE(name) (faz sentido para catálogo)
  foreach ($_SESSION['manufacturers'] as $m) {
    if (strtolower((string)$m['name']) === strtolower((string)$name)) {
      header('Location: ' . $BASE_URL . '/manufacturer_create.php?error=J%C3%A1+existe+um+fabricante+com+esse+nome');
      exit;
    }
  }

  // gerar novo ID
  $maxId = 0;
  foreach ($_SESSION['manufacturers'] as $m) {
    $maxId = max($maxId, (int)$m['manufacturer_id']);
  }
  $newId = $maxId + 1;

  $_SESSION['manufacturers'][] = [
    'manufacturer_id' => $newId,
    'name' => $name,
    'country' => $country,
    'phone' => $phone,
    'email' => $email,
  ];

  header('Location: ' . $BASE_URL . '/manufacturers.php?success=Fabricante+adicionado+com+sucesso');
  exit;
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Adicionar fabricante</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/manufacturer_create.php">
    <div class="field">
      <label for="name">Nome</label>
      <input id="name" name="name" required>
    </div>

    <div class="field">
      <label for="country">País</label>
      <input id="country" name="country" required>
    </div>

    <div class="field">
      <label for="phone">Telefone</label>
      <input
        id="phone"
        name="phone"
        type="tel"
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
      <input id="email" name="email" type="email">
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn" href="<?= $BASE_URL ?>/manufacturers.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
