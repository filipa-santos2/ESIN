<?php include __DIR__ . '/../../includes/header.php'; ?>

<h1>Login</h1>
<form action="login_process.php" method="POST">
    <label for="email">E-mail:</label>
    <input type="email" id="email" name="email" required>
    
    <label for="password">Senha:</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Entrar</button>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
