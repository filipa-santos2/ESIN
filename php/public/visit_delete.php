<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['visits'])) $_SESSION['visits'] = [];
if (!isset($_SESSION['consultations'])) $_SESSION['consultations'] = [];
if (!isset($_SESSION['administrations'])) $_SESSION['administrations'] = [];
if (!isset($_SESSION['adverse_events'])) $_SESSION['adverse_events'] = [];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /visits.php?error=' . urlencode('ID inválido')); exit; }

$visitIndex = null;
for ($i = 0; $i < count($_SESSION['visits']); $i++) {
  if ((int)$_SESSION['visits'][$i]['visit_id'] === $id) { $visitIndex = $i; break; }
}
if ($visitIndex === null) { header('Location: /visits.php?error=' . urlencode('Visita não encontrada')); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // remover do visits
  array_splice($_SESSION['visits'], $visitIndex, 1);

  // remover da consultation/admin
  $_SESSION['consultations'] = array_values(array_filter($_SESSION['consultations'], fn($c) => (int)$c['visit_id'] !== $id));
  $_SESSION['administrations'] = array_values(array_filter($_SESSION['administrations'], fn($a) => (int)$a['visit_id'] !== $id));

  // remover adverse event (0..1)
  $_SESSION['adverse_events'] = array_values(array_filter($_SESSION['adverse_events'], fn($ae) => (int)$ae['visit_id'] !== $id));

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
