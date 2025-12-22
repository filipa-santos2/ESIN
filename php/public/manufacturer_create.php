<?php
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
    header('Location: /manufacturer_create.php?error=Preenche+nome+e+pa%C3%ADs');
    exit;
  }

  // UNIQUE(name) (faz sentido para catálogo)
  foreach ($_SESSION['manufacturers'] as $m) {
    if (strtolower((string)$m['name']) === strtolower((string)$name)) {
      header('Location: /manufacturer_create.php?error=J%C3%A1+existe+um+fabricante+com+esse+nome');
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

  header('Location: /manufacturers.php?success=Fabricante+adicionado+com+sucesso');
  exit;
}

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Adicionar fabricante</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="/manufacturer_create.php">
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
      <input id="phone" name="phone">
    </div>

    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email">
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn" href="/manufacturers.php">Cancelar</a>
    </div>
  </form>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
