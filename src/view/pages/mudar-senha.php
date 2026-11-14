<?php
require_once __DIR__ . '/../../model/sessao.php';
require_once __DIR__ . '/../../controller/loginController.php';

Sessao::iniciar();

if (!Sessao::estaLogado()) {
    header('Location: Login.php');
    exit;
}

$utilizador = Sessao::getUtilizadorLogado();
$username = $utilizador->getUserName();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginController = new LoginController();
    $resultado = $loginController->alterarSenha(
        $username,
        $_POST['senha_atual'] ?? '',
        $_POST['nova_senha'] ?? '',
        $_POST['confirmar_senha'] ?? ''
    );

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Mudar Senha | DiscoGest</title>
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
                            <h1 class="h2 fw-bold mb-3">Primeiro Acesso</h1>
                            <p class="text-muted">Olá, <?= htmlspecialchars($utilizador->getNome()) ?>. Como é o teu primeiro acesso, define uma nova senha.</p>

                            <?php if ($erro !== ''): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                            <?php endif; ?>

                            <form method="post">
                                <div class="mb-3">
                                    <label for="senha_atual" class="form-label">Senha atual</label>
                                    <input type="text" class="form-control" id="senha_atual" name="senha_atual" placeholder="Senha atual" autocomplete="off" required>
                                </div>

                                <div class="mb-3">
                                    <label for="nova_senha" class="form-label">Nova senha</label>
                                    <input type="text" class="form-control" id="nova_senha" name="nova_senha" placeholder="Nova senha" autocomplete="off" minlength="6" required>
                                </div>

                                <div class="mb-3">
                                    <label for="confirmar_senha" class="form-label">Confirmar nova senha</label>
                                    <input type="text" class="form-control" id="confirmar_senha" name="confirmar_senha" placeholder="Repete a nova senha" autocomplete="off" minlength="6" required>
                                </div>

                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="mostrar">
                                    <label class="form-check-label" for="mostrar">Mostrar senhas</label>
                                </div>

                                <button type="submit" class="btn btn-dark w-100">Guardar nova senha</button>
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
