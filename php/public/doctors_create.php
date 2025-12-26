<?php
require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../../includes/auth.php';
require_admin();

// Inicializar lista de doctors na sessão (se ainda não existir)
if (!isset($_SESSION['doctors'])) {
  $_SESSION['doctors'] = [];
}

// Se o form foi submetido, processar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name  = trim($_POST['full_name'] ?? '');
  $license_no = trim($_POST['license_no'] ?? '');
  $specialty  = trim($_POST['specialty'] ?? '');
  $phone      = trim($_POST['phone'] ?? '');
  $email      = trim($_POST['email'] ?? '');

  // Validação mínima
  if ($full_name === '' || $license_no === '' || $specialty === '' || $email === '') {
    header('Location: ' . $BASE_URL . '/doctors_create.php?error=Preenche+nome,+n%C3%BAmero+de+ordem,+especialidade+e+email');
    exit;
  }

  // Validar duplicados (license_no e email)
  foreach ($_SESSION['doctors'] as $d) {
    if ((string)($d['license_no'] ?? '') === (string)$license_no) {
      header('Location: ' . $BASE_URL . '/doctors_create.php?error=J%C3%A1+existe+um+m%C3%A9dico+com+esse+n%C3%BAmero+de+ordem');
      exit;
    }
    if (!empty($d['email']) && strtolower($d['email']) === strtolower($email)) {
      header('Location: ' . $BASE_URL . '/doctors_create.php?error=J%C3%A1+existe+um+m%C3%A9dico+com+esse+email');
      exit;
    }
  }

  // Gerar novo ID
  $maxId = 0;
  foreach ($_SESSION['doctors'] as $d) {
    $maxId = max($maxId, (int)($d['doctor_id'] ?? 0));
  }
  $newId = $maxId + 1;

  // Guardar (password será definida no primeiro acesso)
  $_SESSION['doctors'][] = [
    'doctor_id'     => $newId,
    'full_name'     => $full_name,
    'license_no'    => $license_no,
    'specialty'     => $specialty,
    'phone'         => $phone,
    'email'         => $email,
    'password_hash' => null,
  ];

  header('Location: ' . $BASE_URL . '/doctors.php?success=M%C3%A9dico+criado.+A+password+%C3%A9+definida+no+primeiro+acesso');
  exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Criar médico</h1>

  <p style="opacity:.85; margin-top:6px;">
    O administrador cria a conta do médico. A password é definida pelo médico no primeiro acesso (via “Definir password”).
  </p>


  <form method="POST" action="<?= $BASE_URL ?>/doctors_create.php">
    <div class="field">
      <label for="full_name">Nome completo</label>
      <input id="full_name" name="full_name" required>
    </div>

    <div class="field">
      <label for="license_no">Número de ordem</label>
      <input id="license_no" name="license_no" required>
    </div>

    <div class="field">
      <label for="specialty">Especialidade</label>
      <input id="specialty" name="specialty" required>
    </div>

    <div class="field">
      <label for="phone">Telefone</label>
      <input id="phone" name="phone" placeholder="9xxxxxxxx">
    </div>

    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" required>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn" href="<?= $BASE_URL ?>/doctors.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
