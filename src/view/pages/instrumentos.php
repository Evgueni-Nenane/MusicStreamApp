<?php
require_once __DIR__ . '/../../model/sessao.php';
require_once __DIR__ . '/../../model/permissao.php';
require_once __DIR__ . '/../../model/utilizador.php';
require_once __DIR__ . '/../../model/nivelacesso.php';
require_once __DIR__ . '/../../controller/instrumentoController.php';

$utilizador = Sessao::getUtilizadorLogado();
if (!$utilizador) {
    header('Location: Login.php');
    exit;
}

$podeAdicionar = Permissao::pode($utilizador, 'adicionar');
$podeRemover = Permissao::pode($utilizador, 'remover');
$podeUtilizadores = Permissao::pode($utilizador, 'utilizadores');
$podeLogs = Permissao::pode($utilizador, 'logs');

$instrumentoController = new InstrumentoController();
$msg = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $instrumentoController->processarPost($utilizador, $_POST);
    $msg = $resultado['msg'] ?? '';
    $erro = $resultado['erro'] ?? '';
}

$termoPesquisa = trim($_GET['q'] ?? '');
$instrumentos = $instrumentoController->listarComPesquisa($termoPesquisa);
$tab = ($_GET['tab'] ?? 'lista') === 'cad' ? 'cad' : 'lista';
$paginaAtiva = 'instrumentos';
$subtituloPagina = 'Gestão de instrumentos musicais';
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Instrumentos | Sistema de Gestão</title>
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
                                        <a class="nav-link <?= $tab === 'lista' ? 'active' : '' ?>" href="instrumentos.php?tab=lista">Instrumentos Cadastrados</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $tab === 'cad' ? 'active' : '' ?>" href="instrumentos.php?tab=cad">Cadastrar Instrumento</a>
                                    </li>
                                </ul>

                                <form method="get" action="instrumentos.php" class="col-12 col-md-4">
                                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                    <input type="search" class="form-control" name="q"
                                        value="<?= htmlspecialchars($termoPesquisa) ?>" placeholder="Pesquisar instrumento...">
                                </form>
                            </div>

                            <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
                            <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

                            <?php if ($tab === 'lista'): ?>
                                <form method="post" id="form-instr">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Código</th>
                                                    <th>Nome do Instrumento</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($instrumentos as $instrumento): ?>
                                                    <?php
                                                    $idSelecao = 'sel-instrumento-' . (int) $instrumento->getCodigo();
                                                    $nomeInstrumento = $instrumento->getNome();
                                                    ?>
                                                    <tr>
                                                        <td><input type="radio" class="form-check-input" name="sel" id="<?= $idSelecao ?>" value="<?= (int) $instrumento->getCodigo() ?>" aria-label="Selecionar instrumento <?= htmlspecialchars($nomeInstrumento) ?>"></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= (int) $instrumento->getCodigo() ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($nomeInstrumento) ?></label></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (!$instrumentos): ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted">
                                                            <?= $termoPesquisa !== ''
                                                                ? 'Nenhum instrumento encontrado para "' . htmlspecialchars($termoPesquisa) . '".'
                                                                : 'Nenhum instrumento cadastrado.' ?>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-3">
                                        <?php if ($podeRemover): ?>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-modal-target="#m-rem-instr">Remover</button>
                                        <?php endif; ?>
                                        <?php if ($podeAdicionar): ?>
                                            <a href="instrumentos.php?tab=cad" class="btn btn-primary btn-sm">Adicionar</a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            <?php else: ?>
                                <form method="post" action="instrumentos.php?tab=cad">
                                    <input type="hidden" name="acao" value="salvar">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="cad-nome" class="form-label">Nome do Instrumento *</label>
                                            <input type="text" class="form-control" id="cad-nome" name="nome" placeholder="Ex.: Guitarra" maxlength="50" required>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end border-top pt-3 mt-3">
                                        <button type="submit" class="btn btn-primary">Salvar Instrumento</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <div class="modal fade" id="m-rem-instr" tabindex="-1" aria-labelledby="m-rem-instr-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="m-rem-instr-label">Confirmar remoção</h2>
                    <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                </div>
                <div class="modal-body">Tem certeza que deseja remover este instrumento? Esta ação não pode ser revertida.</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal-close>Não</button>
                    <button type="submit" class="btn btn-primary" form="form-instr" name="acao" value="remover">Sim</button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
