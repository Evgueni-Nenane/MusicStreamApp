<?php
require_once __DIR__ . '/../../model/sessao.php';
require_once __DIR__ . '/../../model/permissao.php';
require_once __DIR__ . '/../../model/utilizador.php';
require_once __DIR__ . '/../../model/nivelacesso.php';
require_once __DIR__ . '/../../controller/generoController.php';

$utilizador = Sessao::getUtilizadorLogado();
if (!$utilizador) {
    header('Location: Login.php');
    exit;
}

$podeAdicionar = Permissao::pode($utilizador, 'adicionar');
$podeRemover = Permissao::pode($utilizador, 'remover');
$podeUtilizadores = Permissao::pode($utilizador, 'utilizadores');
$podeLogs = Permissao::pode($utilizador, 'logs');

$generoController = new GeneroController();
$msg = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $generoController->processarPost($utilizador, $_POST);
    $msg = $resultado['msg'] ?? '';
    $erro = $resultado['erro'] ?? '';
}

$termoPesquisa = trim($_GET['q'] ?? '');
$generos = $generoController->listarComPesquisa($termoPesquisa);
$tab = ($_GET['tab'] ?? 'lista') === 'cad' ? 'cad' : 'lista';
$paginaAtiva = 'generos';
$subtituloPagina = 'Gestão de géneros musicais';
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Géneros | Sistema de Gestão</title>
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
                                <ul class="nav nav-tabs">
                                    <li class="nav-item">
                                        <a class="nav-link <?= $tab === 'lista' ? 'active' : '' ?>" href="generos.php?tab=lista">Géneros Cadastrados</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $tab === 'cad' ? 'active' : '' ?>" href="generos.php?tab=cad">Cadastrar Género</a>
                                    </li>
                                </ul>

                                <form method="get" action="generos.php" class="col-12 col-md-4">
                                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                    <input type="search" class="form-control" name="q"
                                        value="<?= htmlspecialchars($termoPesquisa) ?>" placeholder="Pesquisar género...">
                                </form>
                            </div>

                            <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
                            <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

                            <?php if ($tab === 'lista'): ?>
                                <form method="post" id="form-gen">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Código</th>
                                                    <th>Nome do Género</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($generos as $genero): ?>
                                                    <?php
                                                    $idSelecao = 'sel-genero-' . (int) $genero->getCodigoGenero();
                                                    $nomeGenero = $genero->getNomeGenero();
                                                    ?>
                                                    <tr>
                                                        <td><input type="radio" class="form-check-input" name="sel" id="<?= $idSelecao ?>" value="<?= (int) $genero->getCodigoGenero() ?>" aria-label="Selecionar género <?= htmlspecialchars($nomeGenero) ?>"></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= (int) $genero->getCodigoGenero() ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($nomeGenero) ?></label></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (!$generos): ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted">
                                                            <?= $termoPesquisa !== ''
                                                                ? 'Nenhum género encontrado para "' . htmlspecialchars($termoPesquisa) . '".'
                                                                : 'Nenhum género cadastrado.' ?>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-3">
                                        <?php if ($podeRemover): ?>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-modal-target="#m-rem-gen">Remover</button>
                                        <?php endif; ?>
                                        <?php if ($podeAdicionar): ?>
                                            <a href="generos.php?tab=cad" class="btn btn-primary btn-sm">Adicionar</a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            <?php else: ?>
                                <?php if ($podeAdicionar): ?>
                                    <form method="post" action="generos.php?tab=cad">
                                        <input type="hidden" name="acao" value="salvar">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label for="cad-nome" class="form-label">Nome do Género *</label>
                                                <input type="text" class="form-control" id="cad-nome" name="nome" placeholder="Ex.: Kizomba" maxlength="50" required>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end border-top pt-3 mt-3">
                                            <button type="submit" class="btn btn-primary">Salvar Género</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <p class="text-muted mb-0">O seu perfil não tem permissão para adicionar géneros.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <div class="modal fade" id="m-rem-gen" tabindex="-1" aria-labelledby="m-rem-gen-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="m-rem-gen-label">Confirmar remoção</h2>
                    <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                </div>
                <div class="modal-body">Tem certeza que deseja remover este género? Géneros associados a discos não podem ser removidos.</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal-close>Não</button>
                    <button type="submit" class="btn btn-primary" form="form-gen" name="acao" value="remover">Sim</button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
