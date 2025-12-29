<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();

$viaPT = [
  'subcutaneous' => 'subcutânea',
  'sublingual'   => 'sublingual',
  'oral'         => 'oral',
  'intranasal'   => 'intranasal',
];

$estadoPT = [
  'not_started'  => 'não iniciado',
  'build_up'     => 'build-up',
  'maintenance'  => 'manutenção',
  'completed'    => 'concluído',
  'paused'       => 'pausado',
  'cancelled'    => 'cancelado',
];


try {
  $stmt = $pdo->query('
    SELECT
      p."id",
      p."paciente_id",
      p."produto_id",
      p."data_início",
      p."data_fim",
      p."via",
      p."estado",
      pa."nome_completo" AS paciente_nome,
      pr."nome" AS produto_nome
    FROM "Planos AIT" p
    LEFT JOIN "Pacientes" pa ON pa."id" = p."paciente_id"
    LEFT JOIN "Produtos"  pr ON pr."id" = p."produto_id"
    ORDER BY p."id" DESC
  ');
  $plans = $stmt->fetchAll();
} catch (Throwable $e) {
  header('Location: ' . $BASE_URL . '/index.php?error=' . urlencode('Erro: ' . $e->getMessage()));
  exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Planos AIT</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
    <p style="margin:0;">Lista de planos AIT.</p>
    <a class="btn btn-primary" href="<?= $BASE_URL ?>/plan_create.php">Criar plano</a>
  </div>
</section>

<section class="card">
  <table class="table table-compact">
    <thead>
      <tr>
        <th>ID</th>
        <th>Paciente</th>
        <th>Produto</th>
        <th>Início</th>
        <th>Fim</th>
        <th>Via</th>
        <th>Estado</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($plans as $p): ?>
        <tr>
          <td><?= htmlspecialchars((string)$p['id']) ?></td>
          <td><?= htmlspecialchars($p['paciente_nome'] ?? ('#' . $p['paciente_id'])) ?></td>
          <td><?= htmlspecialchars($p['produto_nome'] ?? ('#' . $p['produto_id'])) ?></td>
          <td><?= htmlspecialchars($p['data_início']) ?></td>
          <td><?= htmlspecialchars($p['data_fim'] ?: '—') ?></td>
          <td><?= htmlspecialchars($viaPT[strtolower(trim($p['via']))] ?? $p['via']) ?></td>
          <td><?= htmlspecialchars($estadoPT[strtolower(trim($p['estado']))] ?? $p['estado']) ?></td>

          <td class="actions-cell">
            <div class="actions-wrap">
              <a class="btn btn-soft"
                 href="<?= $BASE_URL ?>/plan_view.php?id=<?= urlencode((string)$p['id']) ?>">
                Ver
              </a>

              <a class="btn btn-soft"
                 href="<?= $BASE_URL ?>/plan_edit.php?id=<?= urlencode((string)$p['id']) ?>">
               Editar
              </a>

              <a class="btn btn-danger"
                 href="<?= $BASE_URL ?>/plan_delete.php?id=<?= urlencode((string)$p['id']) ?>">
                Apagar
              </a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (empty($plans)): ?>
        <tr><td colspan="8" style="opacity:.75;">Ainda não existem planos.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
