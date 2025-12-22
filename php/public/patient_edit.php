<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: /patients.php?error=ID+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['patients'])) {
  $_SESSION['patients'] = [];
}

$index = null;
for ($i = 0; $i < count($_SESSION['patients']); $i++) {
  if ((int)$_SESSION['patients'][$i]['patient_id'] === $id) {
    $index = $i;
    break;
  }
}

if ($index === null) {
  header('Location: /patients.php?error=Paciente+n%C3%A3o+encontrado');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name  = trim($_POST['full_name'] ?? '');
  $birth_date = trim($_POST['birth_date'] ?? '');
  $sex        = trim($_POST['sex'] ?? '');
  $phone      = trim($_POST['phone'] ?? '');

  $validSex = ['M', 'F', 'X'];
  if ($full_name === '' || $birth_date === '' || !in_array($sex, $validSex, true)) {
    header('Location: /patient_edit.php?id=' . urlencode((string)$id) . '&error=Preenche+nome,+data+de+nascimento+e+sexo+v%C3%A1lido');
    exit;
  }

  $_SESSION['patients'][$index]['full_name']  = $full_name;
  $_SESSION['patients'][$index]['birth_date'] = $birth_date;
  $_SESSION['patients'][$index]['sex']        = $sex;
  $_SESSION['patients'][$index]['phone']      = $phone;

  header('Location: /patients.php?success=Paciente+atualizado+com+sucesso');
  exit;
}

$patient = $_SESSION['patients'][$index];

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar paciente</h1>
  <p><small>ID: <?= htmlspecialchars((string)$id) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="/patient_edit.php?id=<?= urlencode((string)$id) ?>">
    <div class="field">
      <label for="full_name">Nome completo</label>
      <input id="full_name" name="full_name" value="<?= htmlspecialchars($patient['full_name']) ?>" required>
    </div>

    <div class="field">
      <label for="birth_date">Data de nascimento</label>
      <input id="birth_date" name="birth_date" type="date" value="<?= htmlspecialchars($patient['birth_date']) ?>" required>
    </div>

    <div class="field">
      <label for="sex">Sexo</label>
      <select id="sex" name="sex" required>
        <option value="F" <?= $patient['sex']==='F' ? 'selected' : '' ?>>F</option>
        <option value="M" <?= $patient['sex']==='M' ? 'selected' : '' ?>>M</option>
        <option value="X" <?= $patient['sex']==='X' ? 'selected' : '' ?>>X</option>
      </select>
    </div>

    <div class="field">
      <label for="phone">Telefone</label>
      <input id="phone" name="phone" value="<?= htmlspecialchars($patient['phone']) ?>">
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="/patients.php">Cancelar</a>
    </div>
  </form>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
