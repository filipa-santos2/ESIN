<?php
require_once __DIR__ . '/../../includes/config.php';

require_once __DIR__ . '/../../includes/auth.php';
require_admin();

try {
  $stmt = $pdo->query('
    SELECT "id","nome_completo","num_ordem","especialidade","telefone","email","password_hash"
    FROM "Médicos"
    ORDER BY "nome_completo"
  ');
  $doctors = $stmt->fetchAll();
} catch (Throwable $e) {
  header('Location: ' . $BASE_URL . '/index.php?error=' . urlencode('Erro a carregar médicos: ' . $e->getMessage()));
  exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Médicos</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
    <p style="margin:0;">Lista de médicos registados.</p>
    <a class="btn btn-primary" href="<?= $BASE_URL ?>/doctors_create.php">Criar médico</a>
  </div>
</section>

<section class="card">
  <?php if (empty($doctors)): ?>
    <p>Sem médicos registados.</p>
  <?php else: ?>
    <div class="table-clip table-clip--static">
     <table class="table table-doctors">
      <thead>
      <tr>
       <th>Nome</th>
       <th>Número de ordem</th>
       <th>Especialidade</th>
       <th>Telefone</th>
       <th>Email</th>
       <th>Conta</th>
       <th>Ações</th>
      </tr>
      </thead>

      <tbody>
       <?php foreach ($doctors as $d): ?>
        <?php $hasPassword = !empty($d['password_hash']); ?>
        <tr>
         <td><?= htmlspecialchars($d['nome_completo']) ?></td>
         <td><?= htmlspecialchars($d['num_ordem']) ?></td>
         <td><?= htmlspecialchars($d['especialidade']) ?></td>
         <td><?= htmlspecialchars($d['telefone'] ?? '') ?></td>
         <td><?= htmlspecialchars($d['email'] ?? '') ?></td>
         <td>
           <?= $hasPassword ? 'Password definida' : 'Primeiro acesso pendente' ?>
         </td>
         <td class="actions-cell">
           <div class="actions-wrap">
            <a class="btn"
               href="<?= $BASE_URL ?>/doctors_edit.php?id=<?= urlencode((string)$d['id']) ?>">
              Editar
            </a>

            <a class="btn btn-danger"
               href="<?= $BASE_URL ?>/doctor_delete.php?id=<?= urlencode((string)$d['id']) ?>">
              Apagar
            </a>
          </div>
         </td>

           
        </tr>
       <?php endforeach; ?>
      </tbody>
     </table>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
