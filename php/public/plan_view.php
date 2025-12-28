<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/plans.php?error=' . urlencode('ID inválido'));
  exit;
}

try {
  // Plano + paciente + produto + fabricante
  $stmt = $pdo->prepare('
    SELECT
      p."id",
      p."data_início",
      p."data_fim",
      p."via",
      p."protocolo_build_up",
      p."protocolo_maintenance",
      p."estado",
      p."notas",
      pa."id" AS paciente_id,
      pa."nome_completo" AS paciente_nome,
      pr."id" AS produto_id,
      pr."nome" AS produto_nome,
      f."nome" AS fabricante_nome
    FROM "Planos AIT" p
    JOIN "Pacientes" pa ON pa."id" = p."paciente_id"
    JOIN "Produtos" pr ON pr."id" = p."produto_id"
    LEFT JOIN "Fabricantes" f ON f."id" = pr."fabricante_id"
    WHERE p."id" = ?
  ');
  $stmt->execute([$id]);
  $plan = $stmt->fetch();

  if (!$plan) {
    header('Location: ' . $BASE_URL . '/plans.php?error=' . urlencode('Plano não encontrado'));
    exit;
  }

  // alergénios associados
  $aStmt = $pdo->prepare('
    SELECT a."código_who_iuis", a."nome_comum", a."categoria"
    FROM "Plano AIT - Alergénios" pa
    JOIN "Alergénios" a ON a."código_who_iuis" = pa."alergénio_código"
    WHERE pa."plano_id" = ?
    ORDER BY a."nome_comum"
  ');
  $aStmt->execute([$id]);
  $allergens = $aStmt->fetchAll();

} catch (Throwable $e) {
  header('Location: ' . $BASE_URL . '/plans.php?error=' . urlencode('Erro: ' . $e->getMessage()));
  exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Plano AIT #<?= htmlspecialchars((string)$plan['id']) ?></h1>

  <div style="display:grid; gap:10px; max-width:820px;">
    <div class="field">
      <label>Paciente</label>
      <div class="input-like"><?= htmlspecialchars($plan['paciente_nome']) ?></div>
    </div>

    <div class="field">
      <label>Produto</label>
      <div class="input-like">
        <?= htmlspecialchars($plan['produto_nome']) ?>
        <?php if (!empty($plan['fabricante_nome'])): ?>
          <span style="opacity:.75;">(<?= htmlspecialchars($plan['fabricante_nome']) ?>)</span>
        <?php endif; ?>
      </div>
    </div>

    <div style="display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:10px;">
      <div class="field">
        <label>Data de início</label>
        <div class="input-like"><?= htmlspecialchars($plan['data_início']) ?></div>
      </div>
      <div class="field">
        <label>Data de fim</label>
        <div class="input-like"><?= htmlspecialchars($plan['data_fim'] ?: '—') ?></div>
      </div>
    </div>

    <div style="display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:10px;">
      <div class="field">
        <label>Via</label>
        <div class="input-like"><?= htmlspecialchars($plan['via']) ?></div>
      </div>
      <div class="field">
        <label>Estado</label>
        <div class="input-like"><?= htmlspecialchars($plan['estado']) ?></div>
      </div>
    </div>

    <div style="display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:10px;">
      <div class="field">
        <label>Protocolo build_up</label>
        <div class="input-like"><?= htmlspecialchars($plan['protocolo_build_up']) ?></div>
      </div>
      <div class="field">
        <label>Protocolo maintenance</label>
        <div class="input-like"><?= htmlspecialchars($plan['protocolo_maintenance']) ?></div>
      </div>
    </div>

    <div class="field">
      <label>Notas</label>
      <div class="input-like"><?= htmlspecialchars($plan['notas'] ?: '—') ?></div>
    </div>
  </div>

  <div style="display:flex; gap:10px; margin-top:14px; flex-wrap:wrap;">
    <a class="btn" href="<?= $BASE_URL ?>/plans.php">Voltar</a>
    <a class="btn btn-soft" href="<?= $BASE_URL ?>/plan_allergens.php?id=<?= urlencode((string)$id) ?>">Gerir alergénios</a>
    <a class="btn btn-soft" href="<?= $BASE_URL ?>/plan_edit.php?id=<?= urlencode((string)$id) ?>">Editar</a>
  </div>
</section>

<section class="card">
  <h2>Alergénios do plano</h2>

  <table class="table table-compact">
    <thead>
      <tr>
        <th>Código</th>
        <th>Nome comum</th>
        <th>Categoria</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($allergens as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['código_who_iuis']) ?></td>
          <td><?= htmlspecialchars($a['nome_comum']) ?></td>
          <td><?= htmlspecialchars($a['categoria']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($allergens)): ?>
        <tr><td colspan="3" style="opacity:.75;">Sem alergénios associados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
