<?php
// Garantir sessão (mesmo que o header já faça isto, não custa)
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Lista inicial (fake) só na 1ª vez
if (!isset($_SESSION['doctors'])) {
  $_SESSION['doctors'] = [
    ['doctor_id' => 1, 'full_name' => 'Dra. Ana Lima', 'license_no' => '12345', 'specialty' => 'Imunoalergologia', 'phone' => '912000111', 'email' => 'ana.lima@exemplo.pt'],
    ['doctor_id' => 2, 'full_name' => 'Dr. Rui Costa', 'license_no' => '67890', 'specialty' => 'Medicina Geral', 'phone' => '913222333', 'email' => 'rui.costa@exemplo.pt'],
  ];
}

$doctors = $_SESSION['doctors'];

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Médicos</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between;">
    <p style="margin:0;">Lista de médicos registados no sistema.</p>
    <a class="btn btn-primary" href="/doctors_create.php">Criar médico</a>
  </div>
</section>

<section class="card">
  <table>
    <thead>
      <tr>
        <th>Nome</th>
        <th>Número de ordem</th>
        <th>Especialidade</th>
        <th>Telefone</th>
        <th>Email</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($doctors as $d): ?>
        <tr>
          <td><?= htmlspecialchars($d['full_name']) ?></td>
          <td><?= htmlspecialchars($d['license_no']) ?></td>
          <td><?= htmlspecialchars($d['specialty']) ?></td>
          <td><?= htmlspecialchars($d['phone']) ?></td>
          <td><?= htmlspecialchars($d['email']) ?></td>
          <td style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn" href="/doctors_edit.php?id=<?= urlencode($d['doctor_id']) ?>">Editar</a>
            <a class="btn btn-danger" href="/doctor_delete.php?id=<?= urlencode($d['doctor_id']) ?>">Apagar</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>