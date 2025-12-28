<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ' . $BASE_URL . '/plans.php?error=' . urlencode('ID inválido')); exit; }

try {
  $stmt = $pdo->prepare('SELECT "id" FROM "Planos AIT" WHERE "id" = ?');
  $stmt->execute([$id]);
  $plan = $stmt->fetch();
  if (!$plan) { header('Location: ' . $BASE_URL . '/plans.php?error=' . urlencode('Plano não encontrado')); exit; }
} catch (Throwable $e) {
  header('Location: ' . $BASE_URL . '/plans.php?error=' . urlencode('Erro: ' . $e->getMessage()));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // importante: limpar primeiro a tabela de ligação
    $pdo->prepare('DELETE FROM "Plano AIT - Alergénios" WHERE "plano_id" = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM "Planos AIT" WHERE "id" = ?')->execute([$id]);

    header('Location: ' . $BASE_URL . '/plans.php?success=' . urlencode('Plano apagado com sucesso'));
    exit;
  } catch (Throwable $e) {
    header('Location: ' . $BASE_URL . '/plans.php?error=' . urlencode('Erro ao apagar: ' . $e->getMessage()));
    exit;
  }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar Plano AIT</h1>

  <p>Tens a certeza que queres apagar o plano <strong>#<?= htmlspecialchars((string)$id) ?></strong>?</p>

  <form method="POST" action="<?= $BASE_URL ?>/plan_delete.php?id=<?= urlencode((string)$id) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="<?= $BASE_URL ?>/plans.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
