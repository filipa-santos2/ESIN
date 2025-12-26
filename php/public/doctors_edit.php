<?php
require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/doctors.php?error=ID+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['doctors'])) {
  $_SESSION['doctors'] = [];
}

// Procurar médico pelo id
$index = null;
for ($i = 0; $i < count($_SESSION['doctors']); $i++) {
  if ((int)($_SESSION['doctors'][$i]['doctor_id'] ?? 0) === $id) {
    $index = $i;
    break;
  }
}

if ($index === null) {
  header('Location: ' . $BASE_URL . '/doctors.php?error=M%C3%A9dico+n%C3%A3o+encontrado');
  exit;
}

// Referência ao médico atual
$doctor = $_SESSION['doctors'][$index];

// Ação: repor password (força primeiro acesso)
if (isset($_GET['action']) && $_GET['action'] === 'reset_password') {
  $_SESSION['doctors'][$index]['password_hash'] = null;

  header(
    'Location: ' . $BASE_URL .
    '/doctors_edit.php?id=' . urlencode((string)$id) .
    '&success=Password+reposta.+O+m%C3%A9dico+deve+definir+uma+nova+password+no+primeiro+acesso'
  );
  exit;
}

// POST: guardar alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name  = trim($_POST['full_name'] ?? '');
  $license_no = trim($_POST['license_no'] ?? '');
  $specialty  = trim($_POST['specialty'] ?? '');
  $phone      = trim($_POST['phone'] ?? '');
  $email      = trim($_POST['email'] ?? '');

  if ($full_name === '' || $license_no === '' || $specialty === '' || $email === '') {
    header(
      'Location: ' . $BASE_URL .
      '/doctors_edit.php?id=' . urlencode((string)$id) .
      '&error=Preenche+nome,+n%C3%BAmero+de+ordem,+especialidade+e+email'
    );
    exit;
  }

  // Validar duplicados (license_no e email) ignorando o próprio
  foreach ($_SESSION['doctors'] as $d) {
    $otherId = (int)($d['doctor_id'] ?? 0);

    if ($otherId !== $id && (string)($d['license_no'] ?? '') === (string)$license_no) {
      header(
        'Location: ' . $BASE_URL .
        '/doctors_edit.php?id=' . urlencode((string)$id) .
        '&error=J%C3%A1+existe+um+m%C3%A9dico+com+esse+n%C3%BAmero+de+ordem'
      );
      exit;
    }

    if ($otherId !== $id && !empty($d['email']) && strtolower($d['email']) === strtolower($email)) {
      header(
        'Location: ' . $BASE_URL .
        '/doctors_edit.php?id=' . urlencode((string)$id) .
        '&error=J%C3%A1+existe+um+m%C3%A9dico+com+esse+email'
      );
      exit;
    }
  }

  // Atualizar (não mexe na password aqui)
  $_SESSION['doctors'][$index]['full_name']  = $full_name;
  $_SESSION['doctors'][$index]['license_no'] = $license_no;
  $_SESSION['doctors'][$index]['specialty']  = $specialty;
  $_SESSION['doctors'][$index]['phone']      = $phone;
  $_SESSION['doctors'][$index]['email']      = $email;

  header('Location: ' . $BASE_URL . '/doctors.php?success=M%C3%A9dico+atualizado+com+sucesso');
  exit;
}

// GET: valores atuais (após possíveis mudanças)
$doctor = $_SESSION['doctors'][$index];
$hasPassword = !empty($doctor['password_hash']);

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar médico</h1>
  <p><small>ID: <?= htmlspecialchars((string)$id) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if (!empty($_GET['success'])): ?>
    <div class="msg msg-success"><?= htmlspecialchars($_GET['success']) ?></div>
  <?php endif; ?>

  <div class="msg msg-info" style="margin-bottom:12px;">
    Estado da conta:
    <strong><?= $hasPassword ? 'Password definida' : 'Primeiro acesso pendente' ?></strong>
  </div>

  <form method="POST" action="<?= $BASE_URL ?>/doctors_edit.php?id=<?= urlencode((string)$id) ?>">
    <div class="field">
      <label for="full_name">Nome completo</label>
      <input id="full_name" name="full_name" value="<?= htmlspecialchars($doctor['full_name'] ?? '') ?>" required>
    </div>

    <div class="field">
      <label for="license_no">Número de ordem</label>
      <input id="license_no" name="license_no" value="<?= htmlspecialchars($doctor['license_no'] ?? '') ?>" required>
    </div>

    <div class="field">
      <label for="specialty">Especialidade</label>
      <input id="specialty" name="specialty" value="<?= htmlspecialchars($doctor['specialty'] ?? '') ?>" required>
    </div>

    <div class="field">
      <label for="phone">Telefone</label>
      <input id="phone" name="phone" value="<?= htmlspecialchars($doctor['phone'] ?? '') ?>">
    </div>

    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" value="<?= htmlspecialchars($doctor['email'] ?? '') ?>" required>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/doctors.php">Cancelar</a>

      <!-- Repor password (força primeiro acesso) -->
      <a class="btn"
         href="<?= $BASE_URL ?>/doctors_edit.php?id=<?= urlencode((string)$id) ?>&action=reset_password"
         onclick="return confirm('Repor password? O médico terá de definir uma nova password no próximo login.');">
        Repor password
      </a>
    </div>
  </form>

  <p style="opacity:.85; margin-top:14px;">
    Nota: o administrador não define passwords. O médico define a password no primeiro acesso (ou após “Repor password”).
  </p>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
