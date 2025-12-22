<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['patients'])) {
  $_SESSION['patients'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name  = trim($_POST['full_name'] ?? '');
  $birth_date = trim($_POST['birth_date'] ?? '');
  $sex        = trim($_POST['sex'] ?? '');
  $phone      = trim($_POST['phone'] ?? '');

  // validação mínima
  $validSex = ['M', 'F', 'X'];
  if ($full_name === '' || $birth_date === '' || !in_array($sex, $validSex, true)) {
    header('Location: /patient_create.php?error=Preenche+nome,+data+de+nascimento+e+sexo+v%C3%A1lido');
    exit;
  }

  // gerar novo ID
  $maxId = 0;
  foreach ($_SESSION['patients'] as $p) {
    $maxId = max($maxId, (int)$p['patient_id']);
  }
  $newId = $maxId + 1;

  $_SESSION['patients'][] = [
    'patient_id' => $newId,
    'full_name' => $full_name,
    'birth_date' => $birth_date,
    'sex' => $sex,
    'phone' => $phone,
  ];

  header('Location: /patients.php?success=Paciente+criado+com+sucesso');
  exit;
}

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Criar paciente</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="/patient_create.php">
    <div class="field">
      <label for="full_name">Nome completo</label>
      <input id="full_name" name="full_name" required>
    </div>

    <div class="field">
      <label for="birth_date">Data de nascimento</label>
      <input id="birth_date" name="birth_date" type="date" required>
    </div>

    <div class="field">
      <label for="sex">Sexo</label>
      <select id="sex" name="sex" required>
        <option value="F">F</option>
        <option value="M">M</option>
        <option value="X">X</option>
      </select>
    </div>

    <div class="field">
      <label for="phone">Telefone</label>
      <input id="phone" name="phone" placeholder="9xxxxxxxx">
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn" href="/patients.php">Cancelar</a>
    </div>
  </form>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
