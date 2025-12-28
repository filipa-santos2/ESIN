<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();

function go_err(int $id, string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/plan_allergens.php?id=' . urlencode((string)$id) . '&error=' . urlencode($msg));
  exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ' . $BASE_URL . '/plans.php?error=' . urlencode('ID inválido')); exit; }

try {
  $planStmt = $pdo->prepare('SELECT p."id", pa."nome_completo" AS paciente_nome
                             FROM "Planos AIT" p
                             JOIN "Pacientes" pa ON pa."id" = p."paciente_id"
                             WHERE p."id" = ?');
  $planStmt->execute([$id]);
  $plan = $planStmt->fetch();
  if (!$plan) { header('Location: ' . $BASE_URL . '/plans.php?error=' . urlencode('Plano não encontrado')); exit; }

  $allergens = $pdo->query('SELECT "código_who_iuis","nome_comum" FROM "Alergénios" ORDER BY "nome_comum"')->fetchAll();

  $currentStmt = $pdo->prepare('
    SELECT a."código_who_iuis", a."nome_comum"
    FROM "Plano AIT - Alergénios" pa
    JOIN "Alergénios" a ON a."código_who_iuis" = pa."alergénio_código"
    WHERE pa."plano_id" = ?
    ORDER BY a."nome_comum"
  ');
  $currentStmt->execute([$id]);
  $current = $currentStmt->fetchAll();
} catch (Throwable $e) {
  header('Location: ' . $BASE_URL . '/plans.php?error=' . urlencode('Erro: ' . $e->getMessage()));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $code = trim($_POST['alergénio_código'] ?? '');

  if ($code === '') go_err($id, 'Seleciona um alergénio');

  try {
    if ($action === 'add') {
      $stmt = $pdo->prepare('INSERT INTO "Plano AIT - Alergénios" ("plano_id","alergénio_código") VALUES (?, ?)');
      $stmt->execute([$id, $code]);
      header('Location: ' . $BASE_URL . '/plan_allergens.php?id=' . urlencode((string)$id) . '&success=' . urlencode('Alergénio adicionado'));
      exit;
    }

    if ($action === 'remove') {
      $stmt = $pdo->prepare('DELETE FROM "Plano AIT - Alergénios" WHERE "plano_id" = ? AND "alergénio_código" = ?');
      $stmt->execute([$id, $code]);
      header('Location: ' . $BASE_URL . '/plan_allergens.php?id=' . urlencode((string)$id) . '&success=' . urlencode('Alergénio removido'));
      exit;
    }

    go_err($id, 'Ação inválida');
  } catch (Throwable $e) {
    go_err($id, 'Erro: ' . $e->getMessage());
  }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Alergénios do Plano AIT</h1>
  <p><small>Plano #<?= htmlspecialchars((string)$id) ?> | Paciente: <?= htmlspecialchars($plan['paciente_nome'] ?? '—') ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>
  <?php if (!empty($_GET['success'])): ?>
    <div class="msg msg-success"><?= htmlspecialchars($_GET['success']) ?></div>
  <?php endif; ?>

  <?php if (empty($allergens)): ?>
    <div class="msg msg-error">Não existem alergénios. Cria primeiro em Alergénios.</div>
  <?php else: ?>
    <form method="POST" action="<?= $BASE_URL ?>/plan_allergens.php?id=<?= urlencode((string)$id) ?>" style="display:flex; gap:10px; flex-wrap:wrap; align-items:end;">
      <div class="field" style="min-width:280px;">
        <label for="alergénio_código">Alergénio</label>
        <select id="alergénio_código" name="alergénio_código" required>
          <?php foreach ($allergens as $a): ?>
            <option value="<?= htmlspecialchars($a['código_who_iuis']) ?>">
              <?= htmlspecialchars(($a['nome_comum'] ?? '') . ' (' . $a['código_who_iuis'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button class="btn btn-primary" type="submit" name="action" value="add">Adicionar</button>
      <a class="btn" href="<?= $BASE_URL ?>/plans.php">Voltar</a>
    </form>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Alergénios associados</h2>

  <table class="table table-compact">
    <thead>
      <tr>
        <th>Código</th>
        <th>Nome comum</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($current as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['código_who_iuis']) ?></td>
          <td><?= htmlspecialchars($c['nome_comum']) ?></td>
          <td style="text-align:right;">
            <form method="POST" action="<?= $BASE_URL ?>/plan_allergens.php?id=<?= urlencode((string)$id) ?>" style="display:inline;">
              <input type="hidden" name="alergénio_código" value="<?= htmlspecialchars($c['código_who_iuis']) ?>">
              <button class="btn btn-danger" type="submit" name="action" value="remove"
                onclick="return confirm('Remover este alergénio do plano?');">
                Remover
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($current)): ?>
        <tr><td colspan="3" style="opacity:.75;">Sem alergénios associados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
