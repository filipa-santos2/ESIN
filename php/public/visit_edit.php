<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();

function to_input_dt(?string $db): string {
  if (!$db) return '';
  return str_replace(' ', 'T', $db);
}
function dt_from_input(string $v): string {
  return str_replace('T', ' ', trim($v));
}
function go_error(string $baseUrl, int $id, string $msg): void {
  header('Location: ' . $baseUrl . '/visit_edit.php?id=' . urlencode((string)$id) . '&error=' . urlencode($msg));
  exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/visits.php?error=' . urlencode('ID inválido'));
  exit;
}

try {
  $patients = $pdo->query('SELECT "id","nome_completo" FROM "Pacientes" ORDER BY "nome_completo"')->fetchAll();
  $doctors  = $pdo->query('SELECT "id","nome_completo" FROM "Médicos" ORDER BY "nome_completo"')->fetchAll();
  $products = $pdo->query('SELECT "id","nome" FROM "Produtos" ORDER BY "nome"')->fetchAll();

  $stmt = $pdo->prepare('SELECT * FROM "Visitas" WHERE "id" = ?');
  $stmt->execute([$id]);
  $visit = $stmt->fetch();
  if (!$visit) {
    header('Location: ' . $BASE_URL . '/visits.php?error=' . urlencode('Visita não encontrada'));
    exit;
  }

  $admin = null;
  if (($visit['tipo'] ?? '') === 'administração') {
    $st2 = $pdo->prepare('SELECT * FROM "Administração" WHERE "visita_id" = ?');
    $st2->execute([$id]);
    $admin = $st2->fetch();
  }
} catch (Throwable $e) {
  header('Location: ' . $BASE_URL . '/visits.php?error=' . urlencode('Erro: ' . $e->getMessage()));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // NOTA: tipo não muda aqui (para não dar chatices com subclasse)
  $paciente_id = (int)($_POST['paciente_id'] ?? 0);
  $medico_id   = (int)($_POST['médico_id'] ?? 0);

  $agendada = dt_from_input($_POST['data_hora_agendada'] ?? '');
  $inicio   = dt_from_input($_POST['data_hora_início'] ?? '');
  $fim_raw  = trim($_POST['data_hora_fim'] ?? '');
  $fim      = ($fim_raw === '') ? null : dt_from_input($fim_raw);

  if ($paciente_id <= 0 || $medico_id <= 0) go_error($BASE_URL, $id, 'Seleciona paciente e médico');
  if ($agendada === '' || $inicio === '') go_error($BASE_URL, $id, 'Preenche data/hora agendada e início');
  if ($fim !== null && strcmp($fim, $inicio) < 0) go_error($BASE_URL, $id, 'Fim tem de ser >= início');

  // Administração (se aplicável)
  $produto_id = (int)($_POST['produto_id'] ?? 0);
  $dose_no = (int)($_POST['dose_nº'] ?? -1);
  $fase = trim($_POST['fase'] ?? '');
  $local = trim($_POST['local_administração'] ?? '');
  $dose_ml_raw = trim($_POST['dose_ml'] ?? '');
  $min_obs = (int)($_POST['minutos_observação'] ?? 0);

  $dose_ml = (float)str_replace(',', '.', $dose_ml_raw);

  $isAdmin = (($visit['tipo'] ?? '') === 'administração');
  if ($isAdmin) {
    if ($produto_id <= 0) go_error($BASE_URL, $id, 'Seleciona um produto');
    if ($dose_no < 0) go_error($BASE_URL, $id, 'Dose nº tem de ser >= 0');
    if ($fase === '') go_error($BASE_URL, $id, 'Preenche a fase');
    if ($local === '') go_error($BASE_URL, $id, 'Preenche o local de administração');
    if (!($dose_ml > 0)) go_error($BASE_URL, $id, 'Dose (mL) tem de ser > 0');
    if ($min_obs <= 0) go_error($BASE_URL, $id, 'Minutos de observação tem de ser > 0');
  }

  try {
    $pdo->beginTransaction();

    $upd = $pdo->prepare('
      UPDATE "Visitas"
      SET "paciente_id"=?, "médico_id"=?, "data_hora_agendada"=?, "data_hora_início"=?, "data_hora_fim"=?
      WHERE "id"=?
    ');
    $upd->execute([$paciente_id, $medico_id, $agendada, $inicio, $fim, $id]);

    if ($isAdmin) {
      // existe linha na subclasse?
      $exists = $pdo->prepare('SELECT 1 FROM "Administração" WHERE "visita_id"=?');
      $exists->execute([$id]);
      $has = (bool)$exists->fetchColumn();

      if ($has) {
        $u2 = $pdo->prepare('
          UPDATE "Administração"
          SET "produto_id"=?, "dose_nº"=?, "fase"=?, "local_administração"=?, "dose_ml"=?, "minutos_observação"=?
          WHERE "visita_id"=?
        ');
        $u2->execute([$produto_id, $dose_no, $fase, $local, $dose_ml, $min_obs, $id]);
      } else {
        $i2 = $pdo->prepare('
          INSERT INTO "Administração"
            ("visita_id","produto_id","dose_nº","fase","local_administração","dose_ml","minutos_observação")
          VALUES (?,?,?,?,?,?,?)
        ');
        $i2->execute([$id, $produto_id, $dose_no, $fase, $local, $dose_ml, $min_obs]);
      }
    }

    $pdo->commit();

    header('Location: ' . $BASE_URL . '/visits.php?success=' . urlencode('Visita atualizada com sucesso'));
    exit;
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    go_error($BASE_URL, $id, 'Erro ao atualizar visita: ' . $e->getMessage());
  }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar visita</h1>
  <p><small>ID: <?= htmlspecialchars((string)$id) ?> | Tipo: <?= htmlspecialchars((string)$visit['tipo']) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/visit_edit.php?id=<?= urlencode((string)$id) ?>">
    <div class="field">
      <label>Tipo</label>
      <div class="input-like"><?= htmlspecialchars((string)$visit['tipo']) ?></div>
    </div>

    <div class="field">
      <label for="paciente_id">Paciente</label>
      <select id="paciente_id" name="paciente_id" required>
        <?php foreach ($patients as $p): ?>
          <option value="<?= htmlspecialchars((string)$p['id']) ?>"
            <?= ((int)$p['id'] === (int)$visit['paciente_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['nome_completo']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="médico_id">Médico</label>
      <select id="médico_id" name="médico_id" required>
        <?php foreach ($doctors as $d): ?>
          <option value="<?= htmlspecialchars((string)$d['id']) ?>"
            <?= ((int)$d['id'] === (int)$visit['médico_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['nome_completo']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="data_hora_agendada">Data/hora agendada</label>
      <input id="data_hora_agendada" name="data_hora_agendada" type="datetime-local"
             value="<?= htmlspecialchars(to_input_dt($visit['data_hora_agendada'])) ?>" required>
    </div>

    <div class="field">
      <label for="data_hora_início">Data/hora de início</label>
      <input id="data_hora_início" name="data_hora_início" type="datetime-local"
             value="<?= htmlspecialchars(to_input_dt($visit['data_hora_início'])) ?>" required>
    </div>

    <div class="field">
      <label for="data_hora_fim">Data/hora de fim (opcional)</label>
      <input id="data_hora_fim" name="data_hora_fim" type="datetime-local"
             value="<?= htmlspecialchars(to_input_dt($visit['data_hora_fim'] ?? null)) ?>">
    </div>

    <?php if (($visit['tipo'] ?? '') === 'administração'): ?>
      <?php
        $admin = $admin ?: [
          'produto_id' => 0,
          'dose_nº' => 0,
          'fase' => '',
          'local_administração' => '',
          'dose_ml' => '',
          'minutos_observação' => 30,
        ];
      ?>

      <div class="card" style="margin-top:12px;">
        <h2>Administração</h2>

        <div class="field">
          <label for="produto_id">Produto</label>
          <select id="produto_id" name="produto_id" required>
            <?php foreach ($products as $pr): ?>
              <option value="<?= htmlspecialchars((string)$pr['id']) ?>"
                <?= ((int)$pr['id'] === (int)$admin['produto_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($pr['nome']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="dose_nº">Dose nº</label>
          <input id="dose_nº" name="dose_nº" type="number" min="0"
                 value="<?= htmlspecialchars((string)$admin['dose_nº']) ?>" required>
        </div>

        <div class="field">
          <label for="fase">Fase</label>
          <input id="fase" name="fase" value="<?= htmlspecialchars((string)$admin['fase']) ?>" required>
        </div>

        <div class="field">
          <label for="local_administração">Local de administração</label>
          <input id="local_administração" name="local_administração"
                 value="<?= htmlspecialchars((string)$admin['local_administração']) ?>" required>
        </div>

        <div class="field">
          <label for="dose_ml">Dose (mL)</label>
          <input id="dose_ml" name="dose_ml" type="number" step="0.01" min="0.01"
                 value="<?= htmlspecialchars((string)$admin['dose_ml']) ?>" required>
        </div>

        <div class="field">
          <label for="minutos_observação">Minutos de observação</label>
          <input id="minutos_observação" name="minutos_observação" type="number" min="1"
                 value="<?= htmlspecialchars((string)$admin['minutos_observação']) ?>" required>
        </div>

        <div style="margin-top:8px;">
          <a class="btn btn-soft" href="<?= $BASE_URL ?>/adverse_event.php?visita_id=<?= urlencode((string)$id) ?>">
            Gerir evento adverso
          </a>
        </div>
      </div>
    <?php endif; ?>

    <div style="display:flex; gap:10px; margin-top:12px;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/visits.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
