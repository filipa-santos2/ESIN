<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['diseases'])) {
  $_SESSION['diseases'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $icd11_code = strtoupper(trim($_POST['icd11_code'] ?? ''));
  $name       = trim($_POST['name'] ?? '');

  if ($icd11_code === '' || $name === '') {
    header('Location: /disease_create.php?error=Preenche+c%C3%B3digo+e+nome');
    exit;
  }

  // UNIQUE(icd11_code)
  foreach ($_SESSION['diseases'] as $d) {
    if ((string)$d['icd11_code'] === (string)$icd11_code) {
      header('Location: /disease_create.php?error=J%C3%A1+existe+uma+doen%C3%A7a+com+esse+c%C3%B3digo');
      exit;
    }
  }

  // UNIQUE(name) (como no teu modelo)
  foreach ($_SESSION['diseases'] as $d) {
    if (strtolower((string)$d['name']) === strtolower((string)$name)) {
      header('Location: /disease_create.php?error=J%C3%A1+existe+uma+doen%C3%A7a+com+esse+nome');
      exit;
    }
  }

  $_SESSION['diseases'][] = [
    'icd11_code' => $icd11_code,
    'name' => $name,
  ];

  header('Location: /diseases.php?success=Doen%C3%A7a+adicionada+com+sucesso');
  exit;
}

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Adicionar doença</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="/disease_create.php">
    <div class="field">
      <label for="icd11_code">Código ICD-11</label>
      <input id="icd11_code" name="icd11_code" placeholder="Ex: CA23" required>
    </div>

    <div class="field">
      <label for="name">Nome</label>
      <input id="name" name="name" placeholder="Ex: Asma" required>
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn" href="/diseases.php">Cancelar</a>
    </div>
  </form>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>