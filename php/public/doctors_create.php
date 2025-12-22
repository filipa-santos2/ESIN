<?php
// php/public/doctors_create.php

// Garantir sessão (caso o header não tenha session_start por alguma razão)
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Inicializar lista de doctors na sessão (se ainda não existir)
if (!isset($_SESSION['doctors'])) {
  $_SESSION['doctors'] = [
    ['doctor_id' => 1, 'full_name' => 'Dra. Ana Lima', 'license_no' => '12345', 'specialty' => 'Imunoalergologia', 'phone' => '912000111', 'email' => 'ana.lima@exemplo.pt'],
    ['doctor_id' => 2, 'full_name' => 'Dr. Rui Costa', 'license_no' => '67890', 'specialty' => 'Medicina Geral', 'phone' => '913222333', 'email' => 'rui.costa@exemplo.pt'],
  ];
}

// Se o form foi submetido, processar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name  = trim($_POST['full_name'] ?? '');
  $license_no = trim($_POST['license_no'] ?? '');
  $specialty  = trim($_POST['specialty'] ?? '');
  $phone      = trim($_POST['phone'] ?? '');
  $email      = trim($_POST['email'] ?? '');

  // Validação mínima
  if ($full_name === '' || $license_no === '' || $specialty === '') {
    header('Location: /doctors_create.php?error=Preenche+nome,+n%C3%BAmero+de+ordem+e+especialidade');
    exit;
  }

  // Validar duplicados do license_no (UNIQUE no modelo)
  foreach ($_SESSION['doctors'] as $d) {
    if ((string)$d['license_no'] === (string)$license_no) {
      header('Location: /doctors_create.php?error=J%C3%A1+existe+um+m%C3%A9dico+com+esse+n%C3%BAmero+de+ordem');
      exit;
    }
  }

  // Gerar novo ID
  $maxId = 0;
  foreach ($_SESSION['doctors'] as $d) {
    $maxId = max($maxId, (int)$d['doctor_id']);
  }
  $newId = $maxId + 1;

  // Guardar
  $_SESSION['doctors'][] = [
    'doctor_id'  => $newId,
    'full_name'  => $full_name,
    'license_no' => $license_no,
    'specialty'  => $specialty,
    'phone'      => $phone,
    'email'      => $email,
  ];

  // Redirect para a lista
  header('Location: /doctors.php?success=M%C3%A9dico+criado+com+sucesso');
  exit;
}

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Criar médico</h1>
  <p>Formulário (a guardar em sessão nesta fase).</p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="/doctors_create.php">
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
      <input id="email" name="email" type="email">
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn" href="/doctors.php">Cancelar</a>
    </div>
  </form>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>