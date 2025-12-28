<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/products.php?error=' . urlencode('ID inválido'));
  exit;
}

$productStmt = $pdo->prepare('SELECT * FROM "Produtos" WHERE "id" = ?');
$productStmt->execute([$id]);
$product = $productStmt->fetch();

if (!$product) {
  header('Location: ' . $BASE_URL . '/products.php?error=' . urlencode('Produto não encontrado'));
  exit;
}

$fabricantes = $pdo->query('SELECT "id","nome" FROM "Fabricantes" ORDER BY "nome"')->fetchAll();

function go_error(int $id, string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/product_edit.php?id=' . urlencode((string)$id) . '&error=' . urlencode($msg));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fabricante_id = (int)($_POST['fabricante_id'] ?? 0);
  $nome = trim($_POST['nome'] ?? '');
  $tipo = trim($_POST['tipo'] ?? '');
  $conc_raw = trim($_POST['concentração'] ?? '');
  $unidade = trim($_POST['unidade'] ?? '');

  if ($fabricante_id <= 0 || $nome === '' || $tipo === '') {
    go_error($id, 'Preenche os campos obrigatórios (nome, tipo, fabricante).');
  }

  $concentracao = null;
  if ($conc_raw !== '') {
    $concentracao = (float)str_replace(',', '.', $conc_raw);
  }

  $chk = $pdo->prepare('SELECT 1 FROM "Fabricantes" WHERE "id" = ?');
  $chk->execute([$fabricante_id]);
  if (!$chk->fetchColumn()) {
    go_error($id, 'Fabricante inválido.');
  }

  $upd = $pdo->prepare('
    UPDATE "Produtos"
    SET "nome"=?, "tipo"=?, "concentração"=?, "unidade"=?, "fabricante_id"=?
    WHERE "id"=?
  ');
  $upd->execute([$nome, $tipo, $concentracao, ($unidade === '' ? null : $unidade), $fabricante_id, $id]);

  header('Location: ' . $BASE_URL . '/products.php?success=' . urlencode('Produto atualizado com sucesso'));
  exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar produto</h1>
  <p><small>ID: <?= htmlspecialchars((string)$id) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/product_edit.php?id=<?= urlencode((string)$id) ?>">
    <div class="field">
      <label for="fabricante_id">Fabricante</label>
      <select id="fabricante_id" name="fabricante_id" required>
        <?php foreach ($fabricantes as $f): ?>
          <option value="<?= htmlspecialchars((string)$f['id']) ?>"
            <?= ((int)$f['id'] === (int)$product['fabricante_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($f['nome']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="nome">Nome</label>
      <input id="nome" name="nome" value="<?= htmlspecialchars($product['nome']) ?>" required>
    </div>

    <div class="field">
      <label for="tipo">Tipo</label>
      <input id="tipo" name="tipo" value="<?= htmlspecialchars($product['tipo']) ?>" required>
    </div>

    <div class="field">
      <label for="concentração">Concentração (opcional)</label>
      <input id="concentração" name="concentração"
             value="<?= htmlspecialchars((string)($product['concentração'] ?? '')) ?>">
    </div>

    <div class="field">
      <label for="unidade">Unidade (opcional)</label>
      <input id="unidade" name="unidade" value="<?= htmlspecialchars((string)($product['unidade'] ?? '')) ?>">
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/products.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
