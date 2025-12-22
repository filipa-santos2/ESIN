<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['diseases'])) {
  $_SESSION['diseases'] = [
    ['icd11_code' => 'CA23', 'name' => 'Asma'],
    ['icd11_code' => '4A00', 'name' => 'Rinite alérgica'],
  ];
}

$diseases = $_SESSION['diseases'];

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Doenças (ICD-11)</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between;">
    <p style="margin:0;">Catálogo de doenças (dados em sessão nesta fase).</p>
    <a class="btn btn-primary" href="/disease_create.php">Adicionar doença</a>
  </div>
</section>

<section class="card">
  <table>
    <thead>
      <tr>
        <th>Código ICD-11</th>
        <th>Nome</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($diseases as $d): ?>
        <tr>
          <td><?= htmlspecialchars($d['icd11_code']) ?></td>
          <td><?= htmlspecialchars($d['name']) ?></td>
          <td style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn" href="/disease_edit.php?code=<?= urlencode($d['icd11_code']) ?>">Editar</a>
            <a class="btn btn-danger" href="/disease_delete.php?code=<?= urlencode($d['icd11_code']) ?>">Apagar</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>