<?php
require_once __DIR__ . '/../../model/sessao.php';
require_once __DIR__ . '/../../model/permissao.php';
require_once __DIR__ . '/../../model/utilizador.php';
require_once __DIR__ . '/../../model/nivelacesso.php';
require_once __DIR__ . '/../../controller/logsController.php';

$utilizador = Sessao::getUtilizadorLogado();
if (!$utilizador) {
    header('Location: Login.php');
    exit;
}

if (!Permissao::pode($utilizador, 'logs')) {
    header('Location: listagemdiscos.php');
    exit;
}

$podeUtilizadores = Permissao::pode($utilizador, 'utilizadores');
$podeLogs = true;

$logsController = new LogsController();
$termoPesquisa = trim($_GET['q'] ?? '');
$logs = $logsController->listarComPesquisa($termoPesquisa);
$paginaAtiva = 'logs';
$subtituloPagina = 'Auditoria do sistema';
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Logs | Sistema de Gestão</title>
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row min-vh-100">
            <?php require __DIR__ . '/../partials/sidebar.php'; ?>

            <div class="col-12 col-md-9 col-lg-10 p-4">
                <?php require __DIR__ . '/../partials/header.php'; ?>

                <main>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                                <h3 class="h5 mb-0">Logs do Sistema</h3>
                                <form method="get" class="col-12 col-md-4">
                                    <input type="search" name="q" class="form-control" placeholder="Pesquisar nos logs..."
                                        value="<?= htmlspecialchars($termoPesquisa) ?>">
                                </form>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nome Completo</th>
                                            <th>E-mail Corporativo</th>
                                            <th>Perfil</th>
                                            <th>Acção</th>
                                            <th>Data</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td><?= $log->getCodigo() ?></td>
                                                <td><?= htmlspecialchars(trim($log->getNome() . ' ' . $log->getApelido())) ?></td>
                                                <td><?= htmlspecialchars($log->getEmail()) ?></td>
                                                <td><?= htmlspecialchars($log->getPerfil()) ?></td>
                                                <td><?= htmlspecialchars($log->getAccao()) ?></td>
                                                <td><?= htmlspecialchars($log->getDataHora()) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (!$logs): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">Nenhum registo encontrado.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</body>

</html>
