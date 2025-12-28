<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function go_error(string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/manufacturer_create.php?error=' . urlencode($msg));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name    = trim($_POST['name'] ?? '');
  $country = trim($_POST['country'] ?? '');
  $phone   = trim($_POST['phone'] ?? '');
  $email   = trim($_POST['email'] ?? '');

  if ($name === '' || $country === '') {
    go_error('Preenche nome e país');
  }

  // Telefone opcional (mantive a tua regra de telemóvel PT)
  if ($phone !== '' && !preg_match('/^(91|92|93|96)\d{7}$/', $phone)) {
    go_error('Telefone inválido (telemóvel português, ex: 91xxxxxxx)');
  }

  // UNIQUE nome (case-insensitive) — em SQLite fazemos validação antes
  $stmt = $pdo->prepare('SELECT 1 FROM "Fabricantes" WHERE LOWER("nome") = LOWER(?) LIMIT 1');
  $stmt->execute([$name]);
  if ($stmt->fetchColumn()) {
    go_error('Já existe um fabricante com esse nome');
  }

  // Insert
  $ins = $pdo->prepare('
    INSERT INTO "Fabricantes" ("nome","país","telefone","email")
    VALUES (?,?,?,?)
  ');
  $ins->execute([
    $name,
    $country,
    ($phone === '' ? null : $phone),
    ($email === '' ? null : $email),
  ]);

  header('Location: ' . $BASE_URL . '/manufacturers.php?success=' . urlencode('Fabricante adicionado com sucesso'));
  exit;
}

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
