<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['patients'])) {
  $_SESSION['patients'] = [
    ['patient_id' => 1, 'full_name' => 'Maria Silva', 'birth_date' => '2001-04-12', 'sex' => 'F', 'phone' => '912345678'],
    ['patient_id' => 2, 'full_name' => 'João Costa', 'birth_date' => '1998-09-30', 'sex' => 'M', 'phone' => '934567890'],
  ];
}

$patients = $_SESSION['patients'];

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Pacientes</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between;">
    <p style="margin:0;">Lista de pacientes registados no sistema.</p>
    <a class="btn btn-primary" href="/patient_create.php">Criar paciente</a>
  </div>
</section>

<section class="card">
  <table>
    <thead>
      <tr>
        <th>Nome</th>
        <th>Data de nascimento</th>
        <th>Sexo</th>
        <th>Telefone</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($patients as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['full_name']) ?></td>
          <td><?= htmlspecialchars($p['birth_date']) ?></td>
          <td><?= htmlspecialchars($p['sex']) ?></td>
          <td><?= htmlspecialchars($p['phone']) ?></td>
          <td style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn" href="/patient_edit.php?id=<?= urlencode((string)$p['patient_id']) ?>">Editar</a>
            <a class="btn btn-danger" href="/patient_delete.php?id=<?= urlencode((string)$p['patient_id']) ?>">Apagar</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>