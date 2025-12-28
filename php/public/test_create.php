<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin','doctor']);

function go_error(string $msg, string $BASE_URL): void {
  header('Location: ' . $BASE_URL . '/test_create.php?error=' . urlencode($msg));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $paciente_id = (int)($_POST['paciente_id'] ?? 0);
  $tipo = trim($_POST['tipo'] ?? '');
  $data = trim($_POST['data'] ?? '');
  $resultado = trim($_POST['resultado'] ?? '');
  $notas = trim($_POST['notas'] ?? '');

  if ($paciente_id <= 0 || $tipo === '' || $data === '') {
    go_error('Preenche paciente, tipo e data', $BASE_URL);
  }

  // validação simples de data YYYY-MM-DD
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    go_error('Data inválida (formato: YYYY-MM-DD)', $BASE_URL);
  }

  // confirmar paciente existe
  $stmt = $pdo->prepare('SELECT 1 FROM "Pacientes" WHERE "id" = ?');
  $stmt->execute([$paciente_id]);
  if (!$stmt->fetchColumn()) {
    go_error('Paciente inválido', $BASE_URL);
  }

  $stmt = $pdo->prepare('
    INSERT INTO "Testes" ("paciente_id","tipo","data","resultado","notas")
    VALUES (?,?,?,?,?)
  ');
  $stmt->execute([
    $paciente_id,
    $tipo,
    $data,
    ($resultado === '' ? null : $resultado),
    ($notas === '' ? null : $notas),
  ]);

  header('Location: ' . $BASE_URL . '/tests.php?success=' . urlencode('Teste adicionado com sucesso'));
  exit;
}

// GET: carregar pacientes para dropdown
require_once __DIR__ . '/../../includes/header.php';
$patients = $pdo->query('SELECT "id","nome_completo" FROM "Pacientes" ORDER BY "nome_completo"')->fetchAll();
?>

<section class="card">
  <h1>Adicionar teste</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if (empty($patients)): ?>
    <div class="msg msg-error">
      Não podes criar testes sem pacientes.
      Vai a <a href="<?= $BASE_URL ?>/patients.php">Pacientes</a> e cria pelo menos um.
    </div>
  <?php else: ?>
    <form method="POST" action="<?= $BASE_URL ?>/test_create.php">
      <div class="field">
        <label for="paciente_id">Paciente</label>
        <select id="paciente_id" name="paciente_id" required>
          <?php foreach ($patients as $p): ?>
            <option value="<?= htmlspecialchars((string)$p['id']) ?>">
              <?= htmlspecialchars($p['nome_completo']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="tipo">Tipo</label>
        <input id="tipo" name="tipo" placeholder="Ex: prick / IgE" required>
      </div>

      <div class="field">
        <label for="data">Data</label>
        <input id="data" name="data" type="date" required>
      </div>

      <div class="field">
        <label for="resultado">Resultado (opcional)</label>
        <input id="resultado" name="resultado" placeholder="Ex: positivo, 3mm, 12 kUA/L">
      </div>

      <div class="field">
        <label for="notas">Notas (opcional)</label>
        <textarea id="notas" name="notas" rows="3"></textarea>
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn" href="<?= $BASE_URL ?>/tests.php">Cancelar</a>
      </div>
    </form>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
