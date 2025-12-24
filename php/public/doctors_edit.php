<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/doctors.php?error=ID+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['doctors'])) {
  $_SESSION['doctors'] = [];
}

// procurar o médico pelo id
$index = null;
for ($i = 0; $i < count($_SESSION['doctors']); $i++) {
  if ((int)$_SESSION['doctors'][$i]['doctor_id'] === $id) {
    $index = $i;
    break;
  }
}

if ($index === null) {
  header('Location: ' . $BASE_URL . '/doctors.php?error=M%C3%A9dico+n%C3%A3o+encontrado');
  exit;
}

// POST: guardar alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name  = trim($_POST['full_name'] ?? '');
  $license_no = trim($_POST['license_no'] ?? '');
  $specialty  = trim($_POST['specialty'] ?? '');
  $phone      = trim($_POST['phone'] ?? '');
  $email      = trim($_POST['email'] ?? '');

  if ($full_name === '' || $license_no === '' || $specialty === '') {
    header('Location: ' . $BASE_URL . '/doctors_edit.php?id=' . urlencode((string)$id) . '&error=Preenche+nome,+n%C3%BAmero+de+ordem+e+especialidade');
    exit;
  }

  // validar UNIQUE(license_no) (ignorando o próprio)
  foreach ($_SESSION['doctors'] as $d) {
    if ((int)$d['doctor_id'] !== $id && (string)$d['license_no'] === (string)$license_no) {
      header('Location: ' . $BASE_URL . '/doctors_edit.php?id=' . urlencode((string)$id) . '&error=J%C3%A1+existe+um+m%C3%A9dico+com+esse+n%C3%BAmero+de+ordem');
      exit;
    }
  }

  // atualizar
  $_SESSION['doctors'][$index]['full_name']  = $full_name;
  $_SESSION['doctors'][$index]['license_no'] = $license_no;
  $_SESSION['doctors'][$index]['specialty']  = $specialty;
  $_SESSION['doctors'][$index]['phone']      = $phone;
  $_SESSION['doctors'][$index]['email']      = $email;

  header('Location: ' . $BASE_URL . '/doctors.php?success=M%C3%A9dico+atualizado+com+sucesso');
  exit;
}

// GET: mostrar form com valores atuais
$doctor = $_SESSION['doctors'][$index];

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar médico</h1>
  <p><small>ID: <?= htmlspecialchars((string)$id) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/doctors_edit.php?id=<?= urlencode((string)$id) ?>">
    <div class="field">
      <label for="full_name">Nome completo</label>
      <input id="full_name" name="full_name" value="<?= htmlspecialchars($doctor['full_name']) ?>" required>
    </div>

    <div class="field">
      <label for="license_no">Número de ordem</label>
      <input id="license_no" name="license_no" value="<?= htmlspecialchars($doctor['license_no']) ?>" required>
    </div>

    <div class="field">
      <label for="specialty">Especialidade</label>
      <input id="specialty" name="specialty" value="<?= htmlspecialchars($doctor['specialty']) ?>" required>
    </div>

    <div class="field">
      <label for="phone">Telefone</label>
      <input id="phone" name="phone" value="<?= htmlspecialchars($doctor['phone']) ?>">
    </div>

    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" value="<?= htmlspecialchars($doctor['email']) ?>">
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/doctors.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>