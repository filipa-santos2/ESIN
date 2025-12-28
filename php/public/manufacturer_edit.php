<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function go_list_error(string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/manufacturers.php?error=' . urlencode($msg));
  exit;
}

function go_edit_error(int $id, string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/manufacturer_edit.php?id=' . urlencode((string)$id) . '&error=' . urlencode($msg));
  exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  go_list_error('ID inválido');
}

// Buscar fabricante
$stmt = $pdo->prepare('
  SELECT "id","nome","país","telefone","email"
  FROM "Fabricantes"
  WHERE "id" = ?
');
$stmt->execute([$id]);
$manufacturer = $stmt->fetch();

if (!$manufacturer) {
  go_list_error('Fabricante não encontrado');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name    = trim($_POST['name'] ?? '');
  $country = trim($_POST['country'] ?? '');
  $phone   = trim($_POST['phone'] ?? '');
  $email   = trim($_POST['email'] ?? '');

  if ($name === '' || $country === '') {
    go_edit_error($id, 'Preenche nome e país');
  }

  // Telefone opcional, mas se existir tem de ser telemóvel PT válido
  if ($phone !== '' && !preg_match('/^(91|92|93|96)\d{7}$/', $phone)) {
    go_edit_error($id, 'Telefone inválido (telemóvel português, ex: 91xxxxxxx)');
  }

  // UNIQUE(nome) ignorando o próprio (case-insensitive)
  $dupe = $pdo->prepare('
    SELECT 1
    FROM "Fabricantes"
    WHERE LOWER("nome") = LOWER(?)
      AND "id" <> ?
    LIMIT 1
  ');
  $dupe->execute([$name, $id]);
  if ($dupe->fetchColumn()) {
    go_edit_error($id, 'Já existe um fabricante com esse nome');
  }

  // Update
  $upd = $pdo->prepare('
    UPDATE "Fabricantes"
    SET "nome" = ?, "país" = ?, "telefone" = ?, "email" = ?
    WHERE "id" = ?
  ');
  $upd->execute([
    $name,
    $country,
    ($phone === '' ? null : $phone),
    ($email === '' ? null : $email),
    $id,
  ]);

  header('Location: ' . $BASE_URL . '/manufacturers.php?success=' . urlencode('Fabricante atualizado com sucesso'));
  exit;
}

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
      <input id="name" name="name" value="<?= htmlspecialchars($manufacturer['nome'] ?? '') ?>" required>
    </div>

    <div class="field">
      <label for="country">País</label>
      <input id="country" name="country" value="<?= htmlspecialchars($manufacturer['país'] ?? '') ?>" required>
    </div>

    <div class="field">
      <label for="phone">Telefone</label>
      <input
        id="phone"
        name="phone"
        type="tel"
        value="<?= htmlspecialchars($manufacturer['telefone'] ?? '') ?>"
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
      <input id="email" name="email" type="email" value="<?= htmlspecialchars($manufacturer['email'] ?? '') ?>">
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/manufacturers.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
