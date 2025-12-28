<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();

function dt_from_input(string $v): string {
  return str_replace('T', ' ', trim($v));
}
function go_error(string $baseUrl, string $msg): void {
  header('Location: ' . $baseUrl . '/visit_create.php?error=' . urlencode($msg));
  exit;
}

try {
  $patients = $pdo->query('SELECT "id","nome_completo" FROM "Pacientes" ORDER BY "nome_completo"')->fetchAll();
  $doctors  = $pdo->query('SELECT "id","nome_completo" FROM "Médicos" ORDER BY "nome_completo"')->fetchAll();
  $products = $pdo->query('SELECT "id","nome" FROM "Produtos" ORDER BY "nome"')->fetchAll();
} catch (Throwable $e) {
  header('Location: ' . $BASE_URL . '/index.php?error=' . urlencode('Erro: ' . $e->getMessage()));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tipo = trim($_POST['tipo'] ?? '');
  $paciente_id = (int)($_POST['paciente_id'] ?? 0);
  $medico_id   = (int)($_POST['médico_id'] ?? 0);

  $agendada = dt_from_input($_POST['data_hora_agendada'] ?? '');
  $inicio   = dt_from_input($_POST['data_hora_início'] ?? '');
  $fim_raw  = trim($_POST['data_hora_fim'] ?? '');
  $fim      = ($fim_raw === '') ? null : dt_from_input($fim_raw);

  if ($tipo !== 'consulta' && $tipo !== 'administração') go_error($BASE_URL, 'Tipo inválido');
  if ($paciente_id <= 0 || $medico_id <= 0) go_error($BASE_URL, 'Seleciona paciente e médico');
  if ($agendada === '' || $inicio === '') go_error($BASE_URL, 'Preenche data/hora agendada e início');
  if ($fim !== null && strcmp($fim, $inicio) < 0) go_error($BASE_URL, 'Fim tem de ser >= início');

  // Campos extra (Administração)
  $produto_id = (int)($_POST['produto_id'] ?? 0);
  $dose_no = (int)($_POST['dose_nº'] ?? -1);
  $fase = trim($_POST['fase'] ?? '');
  $local = trim($_POST['local_administração'] ?? '');
  $dose_ml_raw = trim($_POST['dose_ml'] ?? '');
  $min_obs = (int)($_POST['minutos_observação'] ?? 0);

  $dose_ml = (float)str_replace(',', '.', $dose_ml_raw);

  if ($tipo === 'administração') {
    if ($produto_id <= 0) go_error($BASE_URL, 'Seleciona um produto');
    if ($dose_no < 0) go_error($BASE_URL, 'Dose nº tem de ser >= 0');
    if ($fase === '') go_error($BASE_URL, 'Preenche a fase');
    if ($local === '') go_error($BASE_URL, 'Preenche o local de administração');
    if (!($dose_ml > 0)) go_error($BASE_URL, 'Dose (mL) tem de ser > 0');
    if ($min_obs <= 0) go_error($BASE_URL, 'Minutos de observação tem de ser > 0');
  }

  try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
      INSERT INTO "Visitas"
        ("tipo","paciente_id","médico_id","data_hora_agendada","data_hora_início","data_hora_fim")
      VALUES (?,?,?,?,?,?)
    ');
    $stmt->execute([$tipo, $paciente_id, $medico_id, $agendada, $inicio, $fim]);

    $visitId = (int)$pdo->lastInsertId();

    if ($tipo === 'administração') {
      $stmt2 = $pdo->prepare('
        INSERT INTO "Administração"
          ("visita_id","produto_id","dose_nº","fase","local_administração","dose_ml","minutos_observação")
        VALUES (?,?,?,?,?,?,?)
      ');
      $stmt2->execute([$visitId, $produto_id, $dose_no, $fase, $local, $dose_ml, $min_obs]);
    }

    $pdo->commit();

    header('Location: ' . $BASE_URL . '/visits.php?success=' . urlencode('Visita criada com sucesso'));
    exit;
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    go_error($BASE_URL, 'Erro ao criar visita: ' . $e->getMessage());
  }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Criar visita</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if (empty($patients) || empty($doctors)): ?>
    <div class="msg msg-error">Precisas de pelo menos 1 paciente e 1 médico.</div>
  <?php else: ?>
    <form method="POST" action="<?= $BASE_URL ?>/visit_create.php">
      <div class="field">
        <label for="tipo">Tipo</label>
        <select id="tipo" name="tipo" required>
          <option value="consulta">consulta</option>
          <option value="administração">administração</option>
        </select>
      </div>

      <div class="field">
        <label for="paciente_id">Paciente</label>
        <select id="paciente_id" name="paciente_id" required>
          <?php foreach ($patients as $p): ?>
            <option value="<?= htmlspecialchars((string)$p['id']) ?>"><?= htmlspecialchars($p['nome_completo']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="médico_id">Médico</label>
        <select id="médico_id" name="médico_id" required>
          <?php foreach ($doctors as $d): ?>
            <option value="<?= htmlspecialchars((string)$d['id']) ?>"><?= htmlspecialchars($d['nome_completo']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="data_hora_agendada">Data/hora agendada</label>
        <input id="data_hora_agendada" name="data_hora_agendada" type="datetime-local" required>
      </div>

      <div class="field">
        <label for="data_hora_início">Data/hora de início</label>
        <input id="data_hora_início" name="data_hora_início" type="datetime-local" required>
      </div>

      <div class="field">
        <label for="data_hora_fim">Data/hora de fim (opcional)</label>
        <input id="data_hora_fim" name="data_hora_fim" type="datetime-local">
      </div>

      <!-- ADMINISTRAÇÃO -->
      <div id="sec_admin" class="card" style="margin-top:12px; display:none;">
        <h2>Administração</h2>

        <?php if (empty($products)): ?>
          <div class="msg msg-error">Não existem produtos. Cria um produto primeiro.</div>
        <?php else: ?>
          <div class="field">
            <label for="produto_id">Produto</label>
            <select id="produto_id" name="produto_id">
              <?php foreach ($products as $pr): ?>
                <option value="<?= htmlspecialchars((string)$pr['id']) ?>"><?= htmlspecialchars($pr['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="field">
            <label for="dose_nº">Dose nº</label>
            <input id="dose_nº" name="dose_nº" type="number" min="0" value="0">
          </div>

          <div class="field">
            <label for="fase">Fase</label>
            <input id="fase" name="fase" placeholder="Ex: build_up / maintenance">
          </div>

          <div class="field">
            <label for="local_administração">Local de administração</label>
            <input id="local_administração" name="local_administração" placeholder="Ex: braço esquerdo">
          </div>

          <div class="field">
            <label for="dose_ml">Dose (mL)</label>
            <input id="dose_ml" name="dose_ml" type="number" step="0.01" min="0.01" placeholder="Ex: 0.20">
          </div>

          <div class="field">
            <label for="minutos_observação">Minutos de observação</label>
            <input id="minutos_observação" name="minutos_observação" type="number" min="1" value="30">
          </div>
        <?php endif; ?>
      </div>

      <div style="display:flex; gap:10px; margin-top:12px;">
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn" href="<?= $BASE_URL ?>/visits.php">Cancelar</a>
      </div>
    </form>

    <script>
      const tipo = document.getElementById('tipo');
      const secAdmin = document.getElementById('sec_admin');

      function toggle() {
        secAdmin.style.display = (tipo.value === 'administração') ? '' : 'none';
      }
      tipo.addEventListener('change', toggle);
      toggle();
    </script>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
