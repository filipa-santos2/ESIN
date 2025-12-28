<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();

function go_error(int $id, string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/plan_edit.php?id=' . urlencode((string)$id) . '&error=' . urlencode($msg));
  exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ' . $BASE_URL . '/plans.php?error=' . urlencode('ID inválido')); exit; }

try {
  $planStmt = $pdo->prepare('SELECT * FROM "Planos AIT" WHERE "id" = ?');
  $planStmt->execute([$id]);
  $plan = $planStmt->fetch();
  if (!$plan) { header('Location: ' . $BASE_URL . '/plans.php?error=' . urlencode('Plano não encontrado')); exit; }

  $patients = $pdo->query('SELECT "id","nome_completo" FROM "Pacientes" ORDER BY "nome_completo"')->fetchAll();
  $products = $pdo->query('SELECT "id","nome" FROM "Produtos" ORDER BY "nome"')->fetchAll();
} catch (Throwable $e) {
  header('Location: ' . $BASE_URL . '/plans.php?error=' . urlencode('Erro: ' . $e->getMessage()));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $paciente_id = (int)($_POST['paciente_id'] ?? 0);
  $produto_id  = (int)($_POST['produto_id'] ?? 0);

  $data_inicio = trim($_POST['data_início'] ?? '');
  $data_fim    = trim($_POST['data_fim'] ?? '');

  $via = trim($_POST['via'] ?? '');
  $prot_bu = trim($_POST['protocolo_build_up'] ?? '');
  $prot_m  = trim($_POST['protocolo_maintenance'] ?? '');
  $estado  = trim($_POST['estado'] ?? '');
  $notas   = trim($_POST['notas'] ?? '');

  if ($paciente_id <= 0 || $produto_id <= 0) go_error($id, 'Seleciona paciente e produto');
  if ($data_inicio === '' || $via === '' || $prot_bu === '' || $prot_m === '' || $estado === '') {
    go_error($id, 'Preenche os campos obrigatórios');
  }
  if ($data_fim !== '' && $data_fim < $data_inicio) {
    go_error($id, 'A data de fim tem de ser igual ou posterior à data de início');
  }

  try {
    $stmt = $pdo->prepare('
      UPDATE "Planos AIT"
      SET
        "paciente_id" = :paciente_id,
        "produto_id" = :produto_id,
        "data_início" = :data_inicio,
        "data_fim" = :data_fim,
        "via" = :via,
        "protocolo_build_up" = :bu,
        "protocolo_maintenance" = :m,
        "estado" = :estado,
        "notas" = :notas
      WHERE "id" = :id
    ');
    $stmt->execute([
      ':paciente_id' => $paciente_id,
      ':produto_id'  => $produto_id,
      ':data_inicio' => $data_inicio,
      ':data_fim'    => ($data_fim === '' ? null : $data_fim),
      ':via'         => $via,
      ':bu'          => $prot_bu,
      ':m'           => $prot_m,
      ':estado'      => $estado,
      ':notas'       => ($notas === '' ? null : $notas),
      ':id'          => $id,
    ]);

    header('Location: ' . $BASE_URL . '/plans.php?success=' . urlencode('Plano AIT atualizado com sucesso'));
    exit;
  } catch (Throwable $e) {
    go_error($id, 'Erro ao atualizar plano: ' . $e->getMessage());
  }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar Plano AIT</h1>
  <p><small>ID: <?= htmlspecialchars((string)$id) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/plan_edit.php?id=<?= urlencode((string)$id) ?>">
    <div class="field">
      <label for="paciente_id">Paciente</label>
      <select id="paciente_id" name="paciente_id" required>
        <?php foreach ($patients as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === (int)$plan['paciente_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['nome_completo']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="produto_id">Produto</label>
      <select id="produto_id" name="produto_id" required>
        <?php foreach ($products as $pr): ?>
          <option value="<?= (int)$pr['id'] ?>" <?= ((int)$pr['id'] === (int)$plan['produto_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($pr['nome']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="data_início">Data de início</label>
      <input id="data_início" name="data_início" type="date" value="<?= htmlspecialchars($plan['data_início']) ?>" required>
    </div>

    <div class="field">
      <label for="data_fim">Data de fim (opcional)</label>
      <input id="data_fim" name="data_fim" type="date" value="<?= htmlspecialchars($plan['data_fim'] ?? '') ?>">
    </div>

    <div class="field">
      <label for="via">Via</label>
      <input id="via" name="via" value="<?= htmlspecialchars($plan['via']) ?>" required>
    </div>

    <div class="field">
      <label for="protocolo_build_up">Protocolo build_up</label>
      <input id="protocolo_build_up" name="protocolo_build_up" value="<?= htmlspecialchars($plan['protocolo_build_up']) ?>" required>
    </div>

    <div class="field">
      <label for="protocolo_maintenance">Protocolo maintenance</label>
      <input id="protocolo_maintenance" name="protocolo_maintenance" value="<?= htmlspecialchars($plan['protocolo_maintenance']) ?>" required>
    </div>

    <div class="field">
      <label for="estado">Estado</label>
      <input id="estado" name="estado" value="<?= htmlspecialchars($plan['estado']) ?>" required>
    </div>

    <div class="field">
      <label for="notas">Notas (opcional)</label>
      <textarea id="notas" name="notas" rows="3"><?= htmlspecialchars($plan['notas'] ?? '') ?></textarea>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/plans.php">Cancelar</a>
      <a class="btn btn-soft" href="<?= $BASE_URL ?>/plan_allergens.php?id=<?= urlencode((string)$id) ?>">Gerir alergénios</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
