<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// carregar fabricantes para o select
$fabricantes = $pdo->query('SELECT "id","nome" FROM "Fabricantes" ORDER BY "nome"')->fetchAll();

function go_error(string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/product_create.php?error=' . urlencode($msg));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fabricante_id = (int)($_POST['fabricante_id'] ?? 0);
  $nome = trim($_POST['nome'] ?? '');
  $tipo = trim($_POST['tipo'] ?? '');
  $conc_raw = trim($_POST['concentração'] ?? '');
  $unidade = trim($_POST['unidade'] ?? '');

  if ($fabricante_id <= 0 || $nome === '' || $tipo === '') {
    go_error('Preenche os campos obrigatórios (nome, tipo, fabricante).');
  }

  $concentracao = null;
  if ($conc_raw !== '') {
    $concentracao = (float)str_replace(',', '.', $conc_raw);
  }

  // confirmar se fabricante existe
  $chk = $pdo->prepare('SELECT 1 FROM "Fabricantes" WHERE "id" = ?');
  $chk->execute([$fabricante_id]);
  if (!$chk->fetchColumn()) {
    go_error('Fabricante inválido.');
  }

  $ins = $pdo->prepare('
    INSERT INTO "Produtos" ("nome","tipo","concentração","unidade","fabricante_id")
    VALUES (?,?,?,?,?)
  ');
  $ins->execute([$nome, $tipo, $concentracao, ($unidade === '' ? null : $unidade), $fabricante_id]);

  header('Location: ' . $BASE_URL . '/products.php?success=' . urlencode('Produto adicionado com sucesso'));
  exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Adicionar produto</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if (empty($fabricantes)): ?>
    <div class="msg msg-error">
      Não podes criar produtos sem fabricantes.
      Vai a <a href="<?= $BASE_URL ?>/manufacturers.php">Fabricantes</a> e cria pelo menos um.
    </div>
  <?php else: ?>
    <form method="POST" action="<?= $BASE_URL ?>/product_create.php">
      <div class="field">
        <label for="fabricante_id">Fabricante</label>
        <select id="fabricante_id" name="fabricante_id" required>
          <?php foreach ($fabricantes as $f): ?>
            <option value="<?= htmlspecialchars((string)$f['id']) ?>"><?= htmlspecialchars($f['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="nome">Nome</label>
        <input id="nome" name="nome" required>
      </div>

      <div class="field">
        <label for="tipo">Tipo</label>
        <input id="tipo" name="tipo" placeholder="Ex: tablet / injection / extract" required>
      </div>

      <div class="field">
        <label for="concentração">Concentração (opcional)</label>
        <input id="concentração" name="concentração" inputmode="decimal" placeholder="Ex: 12.0">
      </div>

      <div class="field">
        <label for="unidade">Unidade (opcional)</label>
        <input id="unidade" name="unidade" placeholder="Ex: mL / mg / tablet">
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn" href="<?= $BASE_URL ?>/products.php">Cancelar</a>
      </div>
    </form>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
