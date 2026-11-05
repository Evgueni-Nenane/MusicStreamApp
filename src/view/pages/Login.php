<?php
require_once __DIR__ . '/../../model/sessao.php';
require_once __DIR__ . '/../../controller/loginController.php';

Sessao::iniciar();

// Se já estiver logado, não faz sentido ver o login outra vez — manda para a administração
if (Sessao::estaLogado()) {
    header('Location: administracao.php');
    exit;
}

$erro = '';
$usernamePreenchido = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginController = new LoginController();
    $resultado = $loginController->autenticar($_POST['username'] ?? '', $_POST['password'] ?? '');
    $usernamePreenchido = $resultado['username'];

    if (isset($resultado['redirect'])) {
        header('Location: ' . $resultado['redirect']);
        exit;
    }

    $erro = $resultado['erro'];
}
?>


<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/login.css">
    <title>Login | DiscoGest</title>
</head>

<body>
    <div class="container">
        <div class="img">
            <img src="../resources/login.png" alt="Ilustração de um DJ">
        </div>
        <div class="card">
            <div class="title">
                <h1>Login</h1>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="alert alert-erro"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="campo">
                    <label for="username">Nome de utilizador</label>
                    <input type="text" id="username" name="username" placeholder="John Doe">
                </div>

                <div class="campo">
                    <label for="password">Palavra-Passe</label>
                    <input type="text" id="password" name="password" class="pw" placeholder="Tua palavra-passe" autocomplete="off">
                    <label class="mostrar">
                        <input type="checkbox" id="mostrar"> Mostrar senhas</label>
                </div>

                <div class="campo">
                    <div class="center">
                        <button type="submit">Login</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
