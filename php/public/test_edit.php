<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin','doctor']);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/tests.php?error=' . urlencode('ID inválido'));
  exit;
}

function go_edit_error(int $id, string $msg, string $BASE_URL): void {
  header('Location: ' . $BASE_URL . '/test_edit.php?id=' . urlencode((string)$id) . '&error=' . urlencode($msg));
  exit;
}

// carregar teste
$stmt = $pdo->prepare('SELECT "id","paciente_id","tipo","data","resultado","notas" FROM "Testes" WHERE "id" = ?');
$stmt->execute([$id]);
$test = $stmt->fetch();
if (!$test) {
  header('Location: ' . $BASE_URL . '/tests.php?error=' . urlencode('Teste não encontrado'));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $paciente_id = (int)($_POST['paciente_id'] ?? 0);
  $tipo = trim($_POST['tipo'] ?? '');
  $data = trim($_POST['data'] ?? '');
  $resultado = trim($_POST['resultado'] ?? '');
  $notas = trim($_POST['notas'] ?? '');

  if ($paciente_id <= 0 || $tipo === '' || $data === '') {
    go_edit_error($id, 'Preenche paciente, tipo e data', $BASE_URL);
  }
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    go_edit_error($id, 'Data inválida (formato: YYYY-MM-DD)', $BASE_URL);
  }

  // confirmar paciente existe
  $stmt = $pdo->prepare('SELECT 1 FROM "Pacientes" WHERE "id" = ?');
  $stmt->execute([$paciente_id]);
  if (!$stmt->fetchColumn()) {
    go_edit_error($id, 'Paciente inválido', $BASE_URL);
  }

  $stmt = $pdo->prepare('
    UPDATE "Testes"
    SET "paciente_id"=?, "tipo"=?, "data"=?, "resultado"=?, "notas"=?
    WHERE "id"=?
  ');
  $stmt->execute([
    $paciente_id,
    $tipo,
    $data,
    ($resultado === '' ? null : $resultado),
    ($notas === '' ? null : $notas),
    $id
  ]);

  header('Location: ' . $BASE_URL . '/tests.php?success=' . urlencode('Teste atualizado com sucesso'));
  exit;
}

// GET: dropdown pacientes
require_once __DIR__ . '/../../includes/header.php';
$patients = $pdo->query('SELECT "id","nome_completo" FROM "Pacientes" ORDER BY "nome_completo"')->fetchAll();
?>

<section class="card">
  <h1>Editar teste</h1>
  <p><small>ID: <?= htmlspecialchars((string)$id) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/test_edit.php?id=<?= urlencode((string)$id) ?>">
    <div class="field">
      <label for="paciente_id">Paciente</label>
      <select id="paciente_id" name="paciente_id" required>
        <?php foreach ($patients as $p): ?>
          <option value="<?= htmlspecialchars((string)$p['id']) ?>"
            <?= ((int)$p['id'] === (int)$test['paciente_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['nome_completo']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="tipo">Tipo</label>
      <input id="tipo" name="tipo" value="<?= htmlspecialchars((string)$test['tipo']) ?>" required>
    </div>

    <div class="field">
      <label for="data">Data</label>
      <input id="data" name="data" type="date" value="<?= htmlspecialchars((string)$test['data']) ?>" required>
    </div>

    <div class="field">
      <label for="resultado">Resultado (opcional)</label>
      <input id="resultado" name="resultado" value="<?= htmlspecialchars((string)($test['resultado'] ?? '')) ?>">
    </div>

    <div class="field">
      <label for="notas">Notas (opcional)</label>
      <textarea id="notas" name="notas" rows="3"><?= htmlspecialchars((string)($test['notas'] ?? '')) ?></textarea>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/tests.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
