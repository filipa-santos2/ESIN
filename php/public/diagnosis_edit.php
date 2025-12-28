<?php
require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin', 'doctor']);

function go_error(int $id, string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/diagnosis_edit.php?id=' . urlencode((string)$id) . '&error=' . urlencode($msg));
  exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/diagnoses.php?error=' . urlencode('ID inválido'));
  exit;
}

// Carregar diagnóstico
$stmt = $pdo->prepare('
  SELECT "id", "paciente_id", "doença_código", "data_início", "data_fim", "estado", "notas"
  FROM "Diagnósticos"
  WHERE "id" = :id
');
$stmt->execute([':id' => $id]);
$diagnosis = $stmt->fetch();

if (!$diagnosis) {
  header('Location: ' . $BASE_URL . '/diagnoses.php?error=' . urlencode('Diagnóstico não encontrado'));
  exit;
}

// Carregar pacientes e doenças (para os selects)
try {
  $patients = $pdo->query('
    SELECT "id", "nome_completo"
    FROM "Pacientes"
    ORDER BY "nome_completo" ASC
  ')->fetchAll();

  $diseases = $pdo->query('
    SELECT "código", "designação"
    FROM "Doenças"
    ORDER BY "designação" ASC
  ')->fetchAll();
} catch (PDOException $e) {
  go_error($id, 'Erro ao carregar dados base: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $paciente_id   = (int)($_POST['paciente_id'] ?? 0);
  $doenca_codigo = trim($_POST['doenca_codigo'] ?? '');
  $data_inicio   = trim($_POST['data_inicio'] ?? '');
  $data_fim      = trim($_POST['data_fim'] ?? '');
  $estado        = trim($_POST['estado'] ?? '');
  $notas         = trim($_POST['notas'] ?? '');

  if ($paciente_id <= 0 || $doenca_codigo === '' || $data_inicio === '' || $estado === '') {
    go_error($id, 'Preenche paciente, doença, data de início e estado.');
  }

  $allowed = ['active', 'inactive', 'resolved'];
  if (!in_array($estado, $allowed, true)) {
    go_error($id, 'Estado inválido.');
  }

  $data_fim_db = ($data_fim === '') ? null : $data_fim;
  if ($data_fim_db !== null && $data_fim_db < $data_inicio) {
    go_error($id, 'A data de fim tem de ser igual ou posterior à data de início.');
  }

  // Validar FKs
  try {
    $st = $pdo->prepare('SELECT 1 FROM "Pacientes" WHERE "id" = :id');
    $st->execute([':id' => $paciente_id]);
    if (!$st->fetchColumn()) go_error($id, 'Paciente inválido.');

    $st = $pdo->prepare('SELECT 1 FROM "Doenças" WHERE "código" = :c');
    $st->execute([':c' => $doenca_codigo]);
    if (!$st->fetchColumn()) go_error($id, 'Doença inválida.');
  } catch (PDOException $e) {
    go_error($id, 'Erro a validar FKs: ' . $e->getMessage());
  }

  // Update
  try {
    $stmt = $pdo->prepare('
      UPDATE "Diagnósticos"
      SET
        "paciente_id"  = :paciente_id,
        "doença_código" = :doenca_codigo,
        "data_início"  = :data_inicio,
        "data_fim"     = :data_fim,
        "estado"       = :estado,
        "notas"        = :notas
      WHERE "id" = :id
    ');

    $stmt->execute([
      ':paciente_id'   => $paciente_id,
      ':doenca_codigo' => $doenca_codigo,
      ':data_inicio'   => $data_inicio,
      ':data_fim'      => $data_fim_db,
      ':estado'        => $estado,
      ':notas'         => ($notas === '' ? null : $notas),
      ':id'            => $id,
    ]);

    header('Location: ' . $BASE_URL . '/diagnoses.php?success=' . urlencode('Diagnóstico atualizado com sucesso.'));
    exit;
  } catch (PDOException $e) {
    go_error($id, 'Erro ao atualizar diagnóstico: ' . $e->getMessage());
  }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar diagnóstico</h1>
  <p><small>ID: <?= htmlspecialchars((string)$id) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/diagnosis_edit.php?id=<?= urlencode((string)$id) ?>">

    <div class="field">
      <label for="paciente_id">Paciente</label>
      <select id="paciente_id" name="paciente_id" required>
        <?php foreach ($patients as $p): ?>
          <option value="<?= htmlspecialchars((string)$p['id']) ?>"
            <?= ((int)$p['id'] === (int)$diagnosis['paciente_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['nome_completo']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="doenca_codigo">Doença</label>
      <select id="doenca_codigo" name="doenca_codigo" required>
        <?php foreach ($diseases as $d): ?>
          <option value="<?= htmlspecialchars($d['código']) ?>"
            <?= ((string)$d['código'] === (string)$diagnosis['doença_código']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['código'] . ' — ' . $d['designação']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="data_inicio">Data de início</label>
      <input id="data_inicio" name="data_inicio" type="date"
             value="<?= htmlspecialchars((string)$diagnosis['data_início']) ?>" required>
    </div>

    <div class="field">
      <label for="data_fim">Data de fim (opcional)</label>
      <input id="data_fim" name="data_fim" type="date"
             value="<?= htmlspecialchars((string)($diagnosis['data_fim'] ?? '')) ?>">
    </div>

    <div class="field">
      <label for="estado">Estado</label>
      <select id="estado" name="estado" required>
        <option value="active"   <?= ((string)$diagnosis['estado'] === 'active') ? 'selected' : '' ?>>ativo</option>
        <option value="inactive" <?= ((string)$diagnosis['estado'] === 'inactive') ? 'selected' : '' ?>>inativo</option>
        <option value="resolved" <?= ((string)$diagnosis['estado'] === 'resolved') ? 'selected' : '' ?>>resolvido</option>
      </select>
    </div>

    <div class="field">
      <label for="notas">Notas (opcional)</label>
      <textarea id="notas" name="notas" rows="3"><?= htmlspecialchars((string)($diagnosis['notas'] ?? '')) ?></textarea>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/diagnoses.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
