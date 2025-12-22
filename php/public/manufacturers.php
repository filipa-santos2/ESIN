<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['manufacturers'])) {
  $_SESSION['manufacturers'] = [
    ['manufacturer_id' => 1, 'name' => 'ALK-Abelló', 'country' => 'Dinamarca', 'phone' => '+45 12 34 56 78', 'email' => 'contacto@alk.example'],
    ['manufacturer_id' => 2, 'name' => 'Stallergenes Greer', 'country' => 'França', 'phone' => '+33 1 23 45 67 89', 'email' => 'info@stallergenes.example'],
  ];
}

$manufacturers = $_SESSION['manufacturers'];

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Fabricantes</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between;">
    <p style="margin:0;">Lista de fabricantes (dados em sessão nesta fase).</p>
    <a class="btn btn-primary" href="/manufacturer_create.php">Adicionar fabricante</a>
  </div>
</section>

<section class="card">
  <table>
    <thead>
      <tr>
        <th>Nome</th>
        <th>País</th>
        <th>Telefone</th>
        <th>Email</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($manufacturers as $m): ?>
        <tr>
          <td><?= htmlspecialchars($m['name']) ?></td>
          <td><?= htmlspecialchars($m['country']) ?></td>
          <td><?= htmlspecialchars($m['phone']) ?></td>
          <td><?= htmlspecialchars($m['email']) ?></td>
          <td style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn" href="/manufacturer_edit.php?id=<?= urlencode((string)$m['manufacturer_id']) ?>">Editar</a>
            <a class="btn btn-danger" href="/manufacturer_delete.php?id=<?= urlencode((string)$m['manufacturer_id']) ?>">Apagar</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>