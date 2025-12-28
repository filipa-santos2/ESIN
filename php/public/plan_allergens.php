<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();

$planId = (int)($_GET['id'] ?? 0); // usa ?id=...
if ($planId <= 0) {
  header('Location: ' . $BASE_URL . '/plans.php?error=' . urlencode('ID de plano inválido'));
  exit;
}

// confirmar que o plano existe
$stmt = $pdo->prepare('SELECT "id" FROM "Planos AIT" WHERE "id" = ?');
$stmt->execute([$planId]);
$plan = $stmt->fetch();
if (!$plan) {
  header('Location: ' . $BASE_URL . '/plans.php?error=' . urlencode('Plano não encontrado'));
  exit;
}

function go_error(string $baseUrl, int $planId, string $msg): void {
  header('Location: ' . $baseUrl . '/plan_allergens.php?id=' . urlencode((string)$planId) . '&error=' . urlencode($msg));
  exit;
}

function go_success(string $baseUrl, int $planId, string $msg): void {
  header('Location: ' . $baseUrl . '/plan_allergens.php?id=' . urlencode((string)$planId) . '&success=' . urlencode($msg));
  exit;
}

/* ===== POST: ADD / REMOVE ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'add') {
    $code = trim($_POST['alergenio_codigo'] ?? '');

    if ($code === '') {
      go_error($BASE_URL, $planId, 'Escolhe um alergénio');
    }

    // confirmar que alergénio existe
    $a = $pdo->prepare('SELECT 1 FROM "Alergénios" WHERE "código_who_iuis" = ?');
    $a->execute([$code]);
    if (!$a->fetchColumn()) {
      go_error($BASE_URL, $planId, 'Alergénio inválido');
    }

    // impedir duplicados
    $dup = $pdo->prepare('
      SELECT 1
      FROM "Plano AIT - Alergénios"
      WHERE "plano_id" = ? AND "alergénio_código" = ?
    ');
    $dup->execute([$planId, $code]);
    if ($dup->fetchColumn()) {
      go_error($BASE_URL, $planId, 'Esse alergénio já está associado ao plano');
    }

    // inserir
    $ins = $pdo->prepare('
      INSERT INTO "Plano AIT - Alergénios" ("plano_id", "alergénio_código")
      VALUES (?, ?)
    ');
    $ins->execute([$planId, $code]);

    go_success($BASE_URL, $planId, 'Alergénio associado com sucesso');
  }

  if ($action === 'remove') {
    $code = trim($_POST['alergenio_codigo'] ?? '');
    if ($code === '') {
      go_error($BASE_URL, $planId, 'Alergénio inválido');
    }

    $del = $pdo->prepare('
      DELETE FROM "Plano AIT - Alergénios"
      WHERE "plano_id" = ? AND "alergénio_código" = ?
    ');
    $del->execute([$planId, $code]);

    go_success($BASE_URL, $planId, 'Alergénio removido do plano');
  }

  go_error($BASE_URL, $planId, 'Ação inválida');
}

/* ===== GET: carregar dados ===== */

// alergénios já associados ao plano
$linkedStmt = $pdo->prepare('
  SELECT a."código_who_iuis", a."nome_comum", a."categoria"
  FROM "Plano AIT - Alergénios" pa
  JOIN "Alergénios" a ON a."código_who_iuis" = pa."alergénio_código"
  WHERE pa."plano_id" = ?
  ORDER BY a."nome_comum"
');
$linkedStmt->execute([$planId]);
$linked = $linkedStmt->fetchAll();

// todos os alergénios
$allStmt = $pdo->query('
  SELECT "código_who_iuis", "nome_comum", "categoria"
  FROM "Alergénios"
  ORDER BY "nome_comum"
');
$allAllergens = $allStmt->fetchAll();

// filtrar só os disponíveis (não associados)
$linkedCodes = [];
foreach ($linked as $x) $linkedCodes[(string)$x['código_who_iuis']] = true;

$available = [];
foreach ($allAllergens as $a) {
  $c = (string)$a['código_who_iuis'];
  if (!isset($linkedCodes[$c])) $available[] = $a;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Plano AIT #<?= htmlspecialchars((string)$planId) ?> — Alergénios</h1>

  <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
    <a class="btn" href="<?= $BASE_URL ?>/plans.php">Voltar</a>
    <a class="btn btn-soft" href="<?= $BASE_URL ?>/plan_view.php?id=<?= urlencode((string)$planId) ?>">Ver plano</a>
    <a class="btn btn-soft" href="<?= $BASE_URL ?>/plan_edit.php?id=<?= urlencode((string)$planId) ?>">Editar plano</a>
  </div>
</section>

<section class="card">
  <h2>Alergénios associados</h2>

  <table class="table table-compact">
    <thead>
      <tr>
        <th>Código</th>
        <th>Nome comum</th>
        <th>Categoria</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($linked as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['código_who_iuis']) ?></td>
          <td><?= htmlspecialchars($a['nome_comum']) ?></td>
          <td><?= htmlspecialchars($a['categoria']) ?></td>
          <td>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Remover este alergénio do plano?');">
              <input type="hidden" name="action" value="remove">
              <input type="hidden" name="alergenio_codigo" value="<?= htmlspecialchars($a['código_who_iuis']) ?>">
              <button class="btn btn-danger" type="submit">Remover</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (empty($linked)): ?>
        <tr><td colspan="4" style="opacity:.75;">Ainda não há alergénios associados a este plano.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<section class="card">
  <h2>Associar novo alergénio</h2>

  <?php if (empty($allAllergens)): ?>
    <div class="msg msg-error">
      Ainda não existem alergénios.
      Vai a <a href="<?= $BASE_URL ?>/allergens.php">Alergénios</a> e cria pelo menos um.
    </div>

  <?php elseif (empty($available)): ?>
    <p>Já tens todos os alergénios disponíveis associados a este plano.</p>

  <?php else: ?>
    <form method="POST">
      <input type="hidden" name="action" value="add">

      <div class="field">
        <label for="alergenio_codigo">Alergénio</label>
        <select id="alergenio_codigo" name="alergenio_codigo" required>
          <?php foreach ($available as $a): ?>
            <option value="<?= htmlspecialchars($a['código_who_iuis']) ?>">
              <?= htmlspecialchars($a['código_who_iuis'] . ' — ' . $a['nome_comum'] . ' (' . $a['categoria'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button class="btn btn-primary" type="submit">Associar</button>
    </form>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
