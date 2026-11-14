<?php
require_once __DIR__ . '/../../model/sessao.php';
require_once __DIR__ . '/../../controller/loginController.php';

Sessao::iniciar();

if (Sessao::estaLogado()) {
    header('Location: administracao.php');
    exit;
}

$erro = '';
$usernamePreenchido = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $usernamePreenchido = $username;

    $loginController = new LoginController();
    $sucesso = $loginController->login($username, $password);

    if ($sucesso) {
        if ($loginController->ehPrimeiroAcesso($username, $password)) {
            header('Location: mudar-senha.php');
            exit;
        }
        header('Location: administracao.php');
        exit;
    } else {
        $erro = 'Utilizador ou Palavra-passe incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Login | DiscoGest</title>
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-12 col-lg-10">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="row g-0">
                        <div class="col-md-5 d-none d-md-block">
                            <img src="../resources/login.png" alt="Ilustração de um DJ" class="w-100 h-100 object-fit-cover">
                        </div>
                        <div class="col-md-7 p-4 p-lg-5">
                            <h1 class="h2 fw-bold mb-4">Login</h1>

                            <?php if ($erro !== ''): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                            <?php endif; ?>

                            <form method="post" id="formLogin">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Nome de utilizador</label>
                                    <input type="text" class="form-control" id="username" name="username" placeholder="John Doe" maxlength="50" required>
                                    <small class="text-danger" id="erroUsername"></small>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Palavra-Passe</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Tua palavra-passe" autocomplete="off" required>
                                    <small class="text-danger" id="erroPassword"></small>
                                    <div class="form-check mt-2">
                                        <input type="checkbox" class="form-check-input" id="mostrar">
                                        <label class="form-check-label" for="mostrar">Mostrar senhas</label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-dark w-100">Login</button>
                                <hr>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/form-validation.js"></script>
</body>

</html>
