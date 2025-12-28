<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';

$user = $_SESSION['user'] ?? null;
?>

<section class="card">
  <h1>Perfil</h1>

  <?php if (!$user): ?>
    <div class="msg msg-error">Sessão inválida.</div>
  <?php else: ?>

    <div style="display:grid; gap:14px; max-width:720px;">

      <div class="field">
        <label>Nome</label>
        <div class="input-like"><?= htmlspecialchars($user['full_name']) ?></div>
      </div>

      <div class="field">
        <label>E-mail</label>
        <div class="input-like"><?= htmlspecialchars($user['email']) ?></div>
      </div>

      <div class="field">
        <label>Perfil</label>
        <div class="input-like">
          <?= (($user['role'] ?? '') === 'admin') ? 'Administrador' : 'Médico' ?>
        </div>
      </div>

      <div class="field">
        <label>Permissões</label>
        <div class="input-like">
          <?php if (($user['role'] ?? '') === 'admin'): ?>
            Gestão completa do sistema (inclui médicos)
          <?php else: ?>
            Gestão de pacientes, visitas, diagnósticos e planos AIT
          <?php endif; ?>
        </div>
      </div>

      <div style="display:flex; gap:10px; margin-top:8px; flex-wrap:wrap;">
        <a class="btn" href="<?= $BASE_URL ?>/index.php">Voltar</a>

        <?php if (($user['role'] ?? '') === 'doctor'): ?>
          <a class="btn" href="<?= $BASE_URL ?>/reset.php?info=<?= urlencode('Alterar password') ?>">
            Alterar password
          </a>
        <?php endif; ?>

        <a class="btn btn-primary" href="<?= $BASE_URL ?>/logout.php">Terminar sessão</a>
      </div>

    </div>

  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
