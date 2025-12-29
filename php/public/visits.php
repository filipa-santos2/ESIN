<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();

try {
  $stmt = $pdo->query('
    SELECT
      v."id",
      v."tipo",
      v."data_hora_agendada",
      v."data_hora_início",
      v."data_hora_fim",
      p."nome_completo" AS paciente_nome,
      m."nome_completo" AS medico_nome,

      a."produto_id",
      a."dose_nº",
      a."fase",
      a."local_administração",
      a."dose_ml",
      a."minutos_observação",

      pr."nome" AS produto_nome,

      CASE WHEN ea."visita_id" IS NULL THEN 0 ELSE 1 END AS tem_evento_adverso

    FROM "Visitas" v
    LEFT JOIN "Pacientes" p ON p."id" = v."paciente_id"
    LEFT JOIN "Médicos"   m ON m."id" = v."médico_id"
    LEFT JOIN "Administração" a ON a."visita_id" = v."id"
    LEFT JOIN "Produtos" pr ON pr."id" = a."produto_id"
    LEFT JOIN "Evento adverso" ea ON ea."visita_id" = v."id"
    ORDER BY v."id" DESC
  ');
  $visits = $stmt->fetchAll();
} catch (Throwable $e) {
  header('Location: ' . $BASE_URL . '/index.php?error=' . urlencode('Erro: ' . $e->getMessage()));
  exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Visitas</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
    <p style="margin:0;">Lista de visitas (consulta / administração).</p>
    <a class="btn btn-primary" href="<?= $BASE_URL ?>/visit_create.php">Criar visita</a>
  </div>
</section>

<section class="card">
 <div class="table-clip">
  <div class="table-scroll">
   <table class="table table-compact visits-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Tipo</th>
        <th>Paciente</th>
        <th>Médico</th>
        <th>Agendada</th>
        <th>Início</th>
        <th>Fim</th>
        <th>Detalhes</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($visits as $v): ?>
        <?php
          $detalhes = '—';

          if (($v['tipo'] ?? '') === 'administração') {
            $prod = $v['produto_nome'] ?? '—';
            $detalhes = 'Produto: ' . $prod;

            $detalhes .= ' | Dose nº: ' . (string)($v['dose_nº'] ?? '—');
            $detalhes .= ' | Fase: ' . (string)($v['fase'] ?? '—');
            $detalhes .= ' | Dose: ' . (string)($v['dose_ml'] ?? '—') . ' mL';
            $detalhes .= ' | Local: ' . (string)($v['local_administração'] ?? '—');
            $detalhes .= ' | Obs: ' . (string)($v['minutos_observação'] ?? '—') . ' min';
            $detalhes .= ' | EA: ' . ((int)($v['tem_evento_adverso'] ?? 0) === 1 ? 'sim' : 'não');
          }
        ?>

        <tr>
          <td><?= htmlspecialchars((string)$v['id']) ?></td>
          <td><?= htmlspecialchars((string)$v['tipo']) ?></td>
          <td><?= htmlspecialchars($v['paciente_nome'] ?? '—') ?></td>
          <td><?= htmlspecialchars($v['medico_nome'] ?? '—') ?></td>
          <td><?= htmlspecialchars((string)$v['data_hora_agendada']) ?></td>
          <td><?= htmlspecialchars((string)$v['data_hora_início']) ?></td>
          <td><?= htmlspecialchars(($v['data_hora_fim'] ?? '') ?: '—') ?></td>
          <td><?= htmlspecialchars($detalhes) ?></td>
          <td>
            <div class="actions">
              <?php if (($v['tipo'] ?? '') === 'administração'): ?>
                <a class="btn btn-soft" href="<?= $BASE_URL ?>/adverse_event.php?visita_id=<?= urlencode((string)$v['id']) ?>">Evento adverso</a>
              <?php endif; ?>

              <a class="btn btn-soft" href="<?= $BASE_URL ?>/visit_edit.php?id=<?= urlencode((string)$v['id']) ?>">Editar</a>
              <a class="btn btn-danger" href="<?= $BASE_URL ?>/visit_delete.php?id=<?= urlencode((string)$v['id']) ?>">Apagar</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (empty($visits)): ?>
        <tr><td colspan="9" style="opacity:.75;">Ainda não existem visitas.</td></tr>
      <?php endif; ?>
    </tbody>
   </table>
  </div>
 </div>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
