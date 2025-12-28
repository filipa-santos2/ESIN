<?php
require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin', 'doctor']);

function go_error(string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/diagnosis_create.php?error=' . urlencode($msg));
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
  go_error('Erro ao carregar dados base: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $paciente_id  = (int)($_POST['paciente_id'] ?? 0);
  $doenca_codigo = trim($_POST['doenca_codigo'] ?? '');
  $data_inicio  = trim($_POST['data_inicio'] ?? '');
  $data_fim     = trim($_POST['data_fim'] ?? '');
  $estado       = trim($_POST['estado'] ?? '');
  $notas        = trim($_POST['notas'] ?? '');

  // Validar obrigatórios
  if ($paciente_id <= 0 || $doenca_codigo === '' || $data_inicio === '' || $estado === '') {
    go_error('Preenche paciente, doença, data de início e estado.');
  }

  // Validar estado pelo CHECK da tabela
  $allowed = ['active', 'inactive', 'resolved'];
  if (!in_array($estado, $allowed, true)) {
    go_error('Estado inválido.');
  }

  // Normalizar data_fim: '' -> NULL
  $data_fim_db = ($data_fim === '') ? null : $data_fim;

  // CHECK: data_fim >= data_início
  if ($data_fim_db !== null && $data_fim_db < $data_inicio) {
    go_error('A data de fim tem de ser igual ou posterior à data de início.');
  }

  // Confirmar que paciente existe (FK)
  try {
    $st = $pdo->prepare('SELECT 1 FROM "Pacientes" WHERE "id" = :id');
    $st->execute([':id' => $paciente_id]);
    if (!$st->fetchColumn()) go_error('Paciente inválido.');

    // Confirmar que doença existe (FK)
    $st = $pdo->prepare('SELECT 1 FROM "Doenças" WHERE "código" = :c');
    $st->execute([':c' => $doenca_codigo]);
    if (!$st->fetchColumn()) go_error('Doença inválida.');
  } catch (PDOException $e) {
    go_error('Erro a validar FKs: ' . $e->getMessage());
  }

  // Inserir
  try {
    $stmt = $pdo->prepare('
      INSERT INTO "Diagnósticos"
        ("paciente_id", "doença_código", "data_início", "data_fim", "estado", "notas")
      VALUES
        (:paciente_id, :doenca_codigo, :data_inicio, :data_fim, :estado, :notas)
    ');

    $stmt->execute([
      ':paciente_id'   => $paciente_id,
      ':doenca_codigo' => $doenca_codigo,
      ':data_inicio'   => $data_inicio,
      ':data_fim'      => $data_fim_db,
      ':estado'        => $estado,
      ':notas'         => ($notas === '' ? null : $notas),
    ]);

    header('Location: ' . $BASE_URL . '/diagnoses.php?success=' . urlencode('Diagnóstico criado com sucesso.'));
    exit;
  } catch (PDOException $e) {
    go_error('Erro ao criar diagnóstico: ' . $e->getMessage());
  }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Criar diagnóstico</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if (empty($patients) || empty($diseases)): ?>
    <div class="msg msg-error">
      Para criar diagnósticos precisas de ter pelo menos um paciente e uma doença registados.
    </div>
  <?php else: ?>
    <form method="POST" action="<?= $BASE_URL ?>/diagnosis_create.php">

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
        <label for="doenca_codigo">Doença</label>
        <select id="doenca_codigo" name="doenca_codigo" required>
          <?php foreach ($diseases as $d): ?>
            <option value="<?= htmlspecialchars($d['código']) ?>">
              <?= htmlspecialchars($d['código'] . ' — ' . $d['designação']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="data_inicio">Data de início</label>
        <input id="data_inicio" name="data_inicio" type="date" required>
      </div>

      <div class="field">
        <label for="data_fim">Data de fim (opcional)</label>
        <input id="data_fim" name="data_fim" type="date">
      </div>

      <div class="field">
        <label for="estado">Estado</label>
        <select id="estado" name="estado" required>
          <option value="active">ativo</option>
          <option value="inactive">inativo</option>
          <option value="resolved">resolvido</option>
        </select>
      </div>

      <div class="field">
        <label for="notas">Notas (opcional)</label>
        <textarea id="notas" name="notas" rows="3"></textarea>
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn" href="<?= $BASE_URL ?>/diagnoses.php">Cancelar</a>
      </div>
    </form>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
