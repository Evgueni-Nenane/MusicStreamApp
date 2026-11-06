<?php
require_once __DIR__ . '/../../model/sessao.php';
require_once __DIR__ . '/../../controller/loginController.php';

Sessao::iniciar();

// Só quem já fez login (mesmo que seja primeiro acesso) pode chegar aqui
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
    <link rel="stylesheet" href="../css/login.css">
    <title>Mudar Senha | DiscoGest</title>
</head>

<body>
    <div class="container">
        <div class="img">
            <img src="../resources/login.png" alt="Ilustração de um DJ">
        </div>
        <div class="card">
            <div class="title">
                <h1>Primeiro Acesso</h1>
            </div>
            <p class="intro">Olá, <?= htmlspecialchars($utilizador->getNome()) ?>. Como é o teu primeiro acesso, define uma nova senha.</p>

            <?php if ($erro !== ''): ?>
                <div class="alert alert-erro"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="campo">
                    <label for="senha_atual">Senha atual</label>
                    <input type="text" id="senha_atual" name="senha_atual" class="pw" placeholder="Senha atual" autocomplete="off" required>
                </div>

                <div class="campo">
                    <label for="nova_senha">Nova senha</label>
                    <input type="text" id="nova_senha" name="nova_senha" class="pw" placeholder="Nova senha" autocomplete="off" minlength="6" required>
                </div>

                <div class="campo">
                    <label for="confirmar_senha">Confirmar nova senha</label>
                    <input type="text" id="confirmar_senha" name="confirmar_senha" class="pw" placeholder="Repete a nova senha" autocomplete="off" minlength="6" required>
                </div>

                <div class="campo">
                    <label class="mostrar">
                        <input type="checkbox" id="mostrar"> Mostrar senhas
                    </label>
                </div>

                <div class="center">
                    <button type="submit">Guardar nova senha</button>
                </div>
            </form>
        </div>
    </div>
    <script src="../js/form-validation.js"></script>
</body>

</html>
