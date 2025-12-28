<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();

function go_error(string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/plan_create.php?error=' . urlencode($msg));
  exit;
}

try {
  $patients = $pdo->query('SELECT "id","nome_completo" FROM "Pacientes" ORDER BY "nome_completo"')->fetchAll();
  $products = $pdo->query('SELECT "id","nome" FROM "Produtos" ORDER BY "nome"')->fetchAll();
} catch (Throwable $e) {
  $patients = [];
  $products = [];
  $loadError = 'Erro a carregar listas: ' . $e->getMessage();
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

  if ($paciente_id <= 0 || $produto_id <= 0) go_error('Seleciona paciente e produto');
  if ($data_inicio === '' || $via === '' || $prot_bu === '' || $prot_m === '' || $estado === '') {
    go_error('Preenche os campos obrigatórios');
  }
  if ($data_fim !== '' && $data_fim < $data_inicio) {
    go_error('A data de fim tem de ser igual ou posterior à data de início');
  }

  try {
    $stmt = $pdo->prepare('
      INSERT INTO "Planos AIT"
        ("paciente_id","produto_id","data_início","data_fim","via","protocolo_build_up","protocolo_maintenance","estado","notas")
      VALUES
        (:paciente_id,:produto_id,:data_inicio,:data_fim,:via,:bu,:m,:estado,:notas)
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
    ]);

    header('Location: ' . $BASE_URL . '/plans.php?success=' . urlencode('Plano AIT criado com sucesso'));
    exit;
  } catch (Throwable $e) {
    go_error('Erro ao criar plano: ' . $e->getMessage());
  }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Criar Plano AIT</h1>

  <?php if (!empty($loadError)): ?>
    <div class="msg msg-error"><?= htmlspecialchars($loadError) ?></div>
  <?php endif; ?>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if (empty($patients) || empty($products)): ?>
    <div class="msg msg-error">
      Precisas de ter pelo menos 1 paciente e 1 produto antes de criares um plano.
    </div>
  <?php else: ?>
    <form method="POST" action="<?= $BASE_URL ?>/plan_create.php">
      <div class="field">
        <label for="paciente_id">Paciente</label>
        <select id="paciente_id" name="paciente_id" required>
          <?php foreach ($patients as $p): ?>
            <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['nome_completo']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="produto_id">Produto</label>
        <select id="produto_id" name="produto_id" required>
          <?php foreach ($products as $pr): ?>
            <option value="<?= (int)$pr['id'] ?>"><?= htmlspecialchars($pr['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="data_início">Data de início</label>
        <input id="data_início" name="data_início" type="date" required>
      </div>

      <div class="field">
        <label for="data_fim">Data de fim (opcional)</label>
        <input id="data_fim" name="data_fim" type="date">
      </div>

      <div class="field">
        <label for="via">Via</label>
        <input id="via" name="via" placeholder="Ex: subcutânea / sublingual" required>
      </div>

      <div class="field">
        <label for="protocolo_build_up">Protocolo build_up</label>
        <input id="protocolo_build_up" name="protocolo_build_up" required>
      </div>

      <div class="field">
        <label for="protocolo_maintenance">Protocolo maintenance</label>
        <input id="protocolo_maintenance" name="protocolo_maintenance" required>
      </div>

      <div class="field">
        <label for="estado">Estado</label>
        <input id="estado" name="estado" placeholder="Ex: build_up / maintenance / terminado" required>
      </div>

      <div class="field">
        <label for="notas">Notas (opcional)</label>
        <textarea id="notas" name="notas" rows="3"></textarea>
      </div>

      <div style="display:flex; gap:10px;">
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn" href="<?= $BASE_URL ?>/plans.php">Cancelar</a>
      </div>
    </form>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
