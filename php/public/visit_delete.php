<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['visits'])) $_SESSION['visits'] = [];
if (!isset($_SESSION['consultations'])) $_SESSION['consultations'] = [];
if (!isset($_SESSION['administrations'])) $_SESSION['administrations'] = [];
if (!isset($_SESSION['adverse_events'])) $_SESSION['adverse_events'] = [];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /visits.php?error=' . urlencode('ID inválido')); exit; }

// encontrar visit + tipo (para mensagem e consistência)
$visitIndex = null;
$visitType = null;

for ($i = 0; $i < count($_SESSION['visits']); $i++) {
  if ((int)($_SESSION['visits'][$i]['visit_id'] ?? 0) === $id) {
    $visitIndex = $i;
    $visitType = (string)($_SESSION['visits'][$i]['visit_type'] ?? '');
    break;
  }
}

if ($visitIndex === null) { header('Location: /visits.php?error=' . urlencode('Visita não encontrada')); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // 1) remover da superclasse visits
  array_splice($_SESSION['visits'], $visitIndex, 1);

  // 2) remover da subclasse correspondente (disjoint)
  if ($visitType === 'consultation') {
    $_SESSION['consultations'] = array_values(array_filter(
      $_SESSION['consultations'],
      fn($c) => (int)($c['visit_id'] ?? 0) !== $id
    ));
  }

  if ($visitType === 'administration') {
    $_SESSION['administrations'] = array_values(array_filter(
      $_SESSION['administrations'],
      fn($a) => (int)($a['visit_id'] ?? 0) !== $id
    ));

    // 3) adverse event só faz sentido em administration (0..1)
    $_SESSION['adverse_events'] = array_values(array_filter(
      $_SESSION['adverse_events'],
      fn($ae) => (int)($ae['visit_id'] ?? 0) !== $id
    ));
  }

  header('Location: /visits.php?success=' . urlencode('Visita apagada com sucesso'));
  exit;
}

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar visita</h1>
  <p>Tens a certeza que queres apagar esta visita?</p>

  <form method="POST" action="/visit_delete.php?id=<?= urlencode((string)$id) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="/visits.php">Cancelar</a>
    </div>
  </form>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
