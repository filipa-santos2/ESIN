<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/visits.php?error=' . urlencode('ID inválido'));
  exit;
}

try {
  $stmt = $pdo->prepare('
    SELECT v."id", v."tipo", v."data_hora_agendada",
           p."nome_completo" AS paciente_nome,
           m."nome_completo" AS medico_nome
    FROM "Visitas" v
    LEFT JOIN "Pacientes" p ON p."id" = v."paciente_id"
    LEFT JOIN "Médicos"   m ON m."id" = v."médico_id"
    WHERE v."id" = ?
  ');
  $stmt->execute([$id]);
  $visit = $stmt->fetch();

  if (!$visit) {
    header('Location: ' . $BASE_URL . '/visits.php?error=' . urlencode('Visita não encontrada'));
    exit;
  }
} catch (Throwable $e) {
  header('Location: ' . $BASE_URL . '/visits.php?error=' . urlencode('Erro: ' . $e->getMessage()));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $del = $pdo->prepare('DELETE FROM "Visitas" WHERE "id" = ?');
    $del->execute([$id]);

    header('Location: ' . $BASE_URL . '/visits.php?success=' . urlencode('Visita apagada com sucesso'));
    exit;
  } catch (Throwable $e) {
    header('Location: ' . $BASE_URL . '/visits.php?error=' . urlencode('Erro ao apagar: ' . $e->getMessage()));
    exit;
  }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar visita</h1>

  <p>
    Tens a certeza que queres apagar a visita
    <strong>#<?= htmlspecialchars((string)$visit['id']) ?></strong>
    (<?= htmlspecialchars($visit['tipo']) ?>) do paciente
    <strong><?= htmlspecialchars($visit['paciente_nome'] ?? '—') ?></strong>,
    médico <strong><?= htmlspecialchars($visit['medico_nome'] ?? '—') ?></strong>,
    agendada para <strong><?= htmlspecialchars($visit['data_hora_agendada']) ?></strong>?
  </p>

  <form method="POST" action="<?= $BASE_URL ?>/visit_delete.php?id=<?= urlencode((string)$id) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="<?= $BASE_URL ?>/visits.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
