<?php
require_once __DIR__ . '/../../model/sessao.php';
require_once __DIR__ . '/../../model/permissao.php';
require_once __DIR__ . '/../../model/utilizador.php';
require_once __DIR__ . '/../../model/nivelacesso.php';
require_once __DIR__ . '/../../controller/producaoController.php';

$utilizador = Sessao::getUtilizadorLogado();
if (!$utilizador) {
    header('Location: Login.php');
    exit;
}

$podeAdicionar = Permissao::pode($utilizador, 'adicionar');
$podeEditar = Permissao::pode($utilizador, 'editar');
$podeRemover = Permissao::pode($utilizador, 'remover');
$podeUtilizadores = Permissao::pode($utilizador, 'utilizadores');
$podeLogs = Permissao::pode($utilizador, 'logs');

$producaoController = new ProducaoController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $producaoController->processarPost($utilizador, $_POST);
    $destino = 'producao.php';

    if (!empty($resultado['msg'])) {
        $destino .= '?msg=' . urlencode($resultado['msg']);
    } elseif (!empty($resultado['erro'])) {
        $destino .= '?erro=' . urlencode($resultado['erro']);
    }

    header('Location: ' . $destino);
    exit;
}

$estado = $producaoController->prepararEcra($_GET);
$msg = $estado['msg'];
$erro = $estado['erro'];
$tab = $estado['tab'];
$produtorEdicao = $estado['produtorEdicao'];
$gravadoraEdicao = $estado['gravadoraEdicao'];
$editoraEdicao = $estado['editoraEdicao'];
$termoPesquisa = $estado['termoPesquisa'];
$produtores = $estado['produtores'];
$gravadoras = $estado['gravadoras'];
$editoras = $estado['editoras'];
$autoModal = $produtorEdicao ? '#m-edit-prod' : ($gravadoraEdicao ? '#m-edit-grav' : ($editoraEdicao ? '#m-edit-edit' : ''));
$paginaAtiva = 'producao';
$subtituloPagina = 'Gestão de produção';
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Produção | Sistema de Gestão</title>
</head>

<body class="bg-light"<?= $autoModal !== '' ? ' data-auto-modal="' . htmlspecialchars($autoModal) . '"' : '' ?>>
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
                                        <a class="nav-link <?= $tab === 'prod' ? 'active' : '' ?>" href="producao.php?tab=prod">Produtores</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $tab === 'grav' ? 'active' : '' ?>" href="producao.php?tab=grav">Gravadoras</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $tab === 'edit' ? 'active' : '' ?>" href="producao.php?tab=edit">Editoras</a>
                                    </li>
                                </ul>

                                <form method="get" action="producao.php" class="col-12 col-md-4">
                                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                    <input type="search" class="form-control" name="q"
                                        value="<?= htmlspecialchars($termoPesquisa) ?>" placeholder="Pesquisar...">
                                </form>
                            </div>

                            <?php if ($msg): ?>
                                <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
                            <?php endif; ?>
                            <?php if ($erro): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                            <?php endif; ?>

                            <?php if ($tab === 'prod'): ?>
                                <form method="post" id="form-prod">
                                    <input type="hidden" name="tipo" value="produtor">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Nome</th>
                                                    <th>Contacto</th>
                                                    <th>E-mail</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($produtores as $produtor): ?>
                                                    <?php
                                                    $idSelecao = 'sel-produtor-' . (int) $produtor->getCodigoProdutor();
                                                    $nomeProdutor = $produtor->getNomeCompleto();
                                                    ?>
                                                    <tr>
                                                        <td><input type="radio" class="form-check-input" name="sel" id="<?= $idSelecao ?>" value="<?= (int) $produtor->getCodigoProdutor() ?>" aria-label="Selecionar produtor <?= htmlspecialchars($nomeProdutor) ?>"></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($nomeProdutor) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($produtor->getContactoProdutor()) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($produtor->getEmailProdutor()) ?></label></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (!$produtores): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted"><?= $termoPesquisa !== '' ? 'Nenhum produtor encontrado.' : 'Nenhum produtor cadastrado.' ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-3">
                                        <?php if ($podeRemover): ?><button type="button" class="btn btn-outline-secondary btn-sm" data-modal-target="#m-rem-prod">Remover</button><?php endif; ?>
                                        <?php if ($podeEditar): ?>
                                            <button type="submit" name="acao" value="editar" formmethod="get" class="btn btn-outline-secondary btn-sm">Editar</button>
                                        <?php endif; ?>
                                        <?php if ($podeAdicionar): ?><button type="button" class="btn btn-primary btn-sm" data-modal-target="#m-add-prod">Adicionar</button><?php endif; ?>
                                    </div>
                                </form>
                            <?php elseif ($tab === 'grav'): ?>
                                <form method="post" id="form-grav">
                                    <input type="hidden" name="tipo" value="gravadora">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Nome</th>
                                                    <th>Contacto</th>
                                                    <th>E-mail</th>
                                                    <th>Endereço</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($gravadoras as $gravadora): ?>
                                                    <?php
                                                    $idSelecao = 'sel-gravadora-' . (int) $gravadora->getCodigoGravadora();
                                                    $nomeGravadora = $gravadora->getNomeGravadora();
                                                    ?>
                                                    <tr>
                                                        <td><input type="radio" class="form-check-input" name="sel" id="<?= $idSelecao ?>" value="<?= (int) $gravadora->getCodigoGravadora() ?>" aria-label="Selecionar gravadora <?= htmlspecialchars($nomeGravadora) ?>"></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($nomeGravadora) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($gravadora->getContactoGravadora()) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($gravadora->getEmailGravadora()) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($gravadora->getEnderecoGravadora()) ?></label></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (!$gravadoras): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted"><?= $termoPesquisa !== '' ? 'Nenhuma gravadora encontrada.' : 'Nenhuma gravadora cadastrada.' ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-3">
                                        <?php if ($podeRemover): ?><button type="button" class="btn btn-outline-secondary btn-sm" data-modal-target="#m-rem-grav">Remover</button><?php endif; ?>
                                        <?php if ($podeEditar): ?>
                                            <button type="submit" name="acao" value="editar" formmethod="get" class="btn btn-outline-secondary btn-sm">Editar</button>
                                        <?php endif; ?>
                                        <?php if ($podeAdicionar): ?><button type="button" class="btn btn-primary btn-sm" data-modal-target="#m-add-grav">Adicionar</button><?php endif; ?>
                                    </div>
                                </form>
                            <?php else: ?>
                                <form method="post" id="form-edit">
                                    <input type="hidden" name="tipo" value="editora">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Nome</th>
                                                    <th>Contacto</th>
                                                    <th>E-mail</th>
                                                    <th>Endereço</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($editoras as $editora): ?>
                                                    <?php
                                                    $idSelecao = 'sel-editora-' . (int) $editora->getCodigoEditora();
                                                    $nomeEditora = $editora->getNomeEditora();
                                                    ?>
                                                    <tr>
                                                        <td><input type="radio" class="form-check-input" name="sel" id="<?= $idSelecao ?>" value="<?= (int) $editora->getCodigoEditora() ?>" aria-label="Selecionar editora <?= htmlspecialchars($nomeEditora) ?>"></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($nomeEditora) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($editora->getContactoEditora()) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($editora->getEmailEditora()) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($editora->getEndereco()) ?></label></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (!$editoras): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted"><?= $termoPesquisa !== '' ? 'Nenhuma editora encontrada.' : 'Nenhuma editora cadastrada.' ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-3">
                                        <?php if ($podeRemover): ?><button type="button" class="btn btn-outline-secondary btn-sm" data-modal-target="#m-rem-edit">Remover</button><?php endif; ?>
                                        <?php if ($podeEditar): ?>
                                            <button type="submit" name="acao" value="editar" formmethod="get" class="btn btn-outline-secondary btn-sm">Editar</button>
                                        <?php endif; ?>
                                        <?php if ($podeAdicionar): ?><button type="button" class="btn btn-primary btn-sm" data-modal-target="#m-add-edit">Adicionar</button><?php endif; ?>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <div class="modal fade" id="m-add-prod" tabindex="-1" aria-labelledby="m-add-prod-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="producao.php">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="m-add-prod-label">Cadastrar Produtor</h2>
                        <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="tipo" value="produtor">
                        <input type="hidden" name="acao" value="adicionar">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="ap-nome" class="form-label">Nome *</label>
                                <input id="ap-nome" name="nome" type="text" class="form-control" maxlength="50" required>
                            </div>
                            <div class="col-md-6">
                                <label for="ap-apelido" class="form-label">Apelido *</label>
                                <input id="ap-apelido" name="apelido" type="text" class="form-control" maxlength="50" required>
                            </div>
                            <div class="col-12">
                                <label for="ap-contacto" class="form-label">Contacto</label>
                                <input id="ap-contacto" name="contacto" type="tel" class="form-control" maxlength="20">
                            </div>
                            <div class="col-12">
                                <label for="ap-email" class="form-label">E-mail</label>
                                <input id="ap-email" name="email" type="email" class="form-control" maxlength="100">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                        <button type="submit" class="btn btn-primary">Cadastrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="m-edit-prod" tabindex="-1" aria-labelledby="m-edit-prod-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="producao.php">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="m-edit-prod-label">Editar Produtor</h2>
                        <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="tipo" value="produtor">
                        <input type="hidden" name="acao" value="atualizar">
                        <input type="hidden" name="id" value="<?= $produtorEdicao ? $produtorEdicao->getCodigoProdutor() : '' ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="ep-nome" class="form-label">Nome</label>
                                <input id="ep-nome" name="nome" type="text" class="form-control" maxlength="50" value="<?= htmlspecialchars($produtorEdicao ? $produtorEdicao->getNomeProdutor() : '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="ep-apelido" class="form-label">Apelido</label>
                                <input id="ep-apelido" name="apelido" type="text" class="form-control" maxlength="50" value="<?= htmlspecialchars($produtorEdicao ? $produtorEdicao->getApelidoProdutor() : '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="ep-contacto" class="form-label">Contacto</label>
                                <input id="ep-contacto" name="contacto" type="tel" class="form-control" maxlength="20" value="<?= htmlspecialchars($produtorEdicao ? $produtorEdicao->getContactoProdutor() : '') ?>">
                            </div>
                            <div class="col-12">
                                <label for="ep-email" class="form-label">E-mail</label>
                                <input id="ep-email" name="email" type="email" class="form-control" maxlength="100" value="<?= htmlspecialchars($produtorEdicao ? $produtorEdicao->getEmailProdutor() : '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="m-rem-prod" tabindex="-1" aria-labelledby="m-rem-prod-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="m-rem-prod-label">Confirmar remoção</h2>
                    <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                </div>
                <div class="modal-body">Tem certeza que deseja remover este produtor?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal-close>Não</button>
                    <button type="submit" class="btn btn-primary" form="form-prod" name="acao" value="remover">Sim</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="m-add-grav" tabindex="-1" aria-labelledby="m-add-grav-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="producao.php">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="m-add-grav-label">Cadastrar Gravadora</h2>
                        <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="tipo" value="gravadora">
                        <input type="hidden" name="acao" value="adicionar">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="ag-nome" class="form-label">Nome *</label>
                                <input id="ag-nome" name="nome" type="text" class="form-control" maxlength="100" required>
                            </div>
                            <div class="col-12">
                                <label for="ag-contacto" class="form-label">Contacto</label>
                                <input id="ag-contacto" name="contacto" type="tel" class="form-control" maxlength="20">
                            </div>
                            <div class="col-12">
                                <label for="ag-email" class="form-label">E-mail</label>
                                <input id="ag-email" name="email" type="email" class="form-control" maxlength="100">
                            </div>
                            <div class="col-12">
                                <label for="ag-endereco" class="form-label">Endereço</label>
                                <input id="ag-endereco" name="endereco" type="text" class="form-control" maxlength="150">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                        <button type="submit" class="btn btn-primary">Cadastrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="m-edit-grav" tabindex="-1" aria-labelledby="m-edit-grav-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="producao.php">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="m-edit-grav-label">Editar Gravadora</h2>
                        <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="tipo" value="gravadora">
                        <input type="hidden" name="acao" value="atualizar">
                        <input type="hidden" name="id" value="<?= $gravadoraEdicao ? $gravadoraEdicao->getCodigoGravadora() : '' ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="eg-nome" class="form-label">Nome</label>
                                <input id="eg-nome" name="nome" type="text" class="form-control" maxlength="100" value="<?= htmlspecialchars($gravadoraEdicao ? $gravadoraEdicao->getNomeGravadora() : '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="eg-contacto" class="form-label">Contacto</label>
                                <input id="eg-contacto" name="contacto" type="tel" class="form-control" maxlength="20" value="<?= htmlspecialchars($gravadoraEdicao ? $gravadoraEdicao->getContactoGravadora() : '') ?>">
                            </div>
                            <div class="col-12">
                                <label for="eg-email" class="form-label">E-mail</label>
                                <input id="eg-email" name="email" type="email" class="form-control" maxlength="100" value="<?= htmlspecialchars($gravadoraEdicao ? $gravadoraEdicao->getEmailGravadora() : '') ?>">
                            </div>
                            <div class="col-12">
                                <label for="eg-endereco" class="form-label">Endereço</label>
                                <input id="eg-endereco" name="endereco" type="text" class="form-control" maxlength="150" value="<?= htmlspecialchars($gravadoraEdicao ? $gravadoraEdicao->getEnderecoGravadora() : '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="m-rem-grav" tabindex="-1" aria-labelledby="m-rem-grav-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="m-rem-grav-label">Confirmar remoção</h2>
                    <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                </div>
                <div class="modal-body">Tem certeza que deseja remover esta gravadora?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal-close>Não</button>
                    <button type="submit" class="btn btn-primary" form="form-grav" name="acao" value="remover">Sim</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="m-add-edit" tabindex="-1" aria-labelledby="m-add-edit-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="producao.php">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="m-add-edit-label">Cadastrar Editora</h2>
                        <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="tipo" value="editora">
                        <input type="hidden" name="acao" value="adicionar">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="ae-nome" class="form-label">Nome *</label>
                                <input id="ae-nome" name="nome" type="text" class="form-control" maxlength="100" required>
                            </div>
                            <div class="col-12">
                                <label for="ae-contacto" class="form-label">Contacto</label>
                                <input id="ae-contacto" name="contacto" type="tel" class="form-control" maxlength="20">
                            </div>
                            <div class="col-12">
                                <label for="ae-email" class="form-label">E-mail</label>
                                <input id="ae-email" name="email" type="email" class="form-control" maxlength="100">
                            </div>
                            <div class="col-12">
                                <label for="ae-endereco" class="form-label">Endereço</label>
                                <input id="ae-endereco" name="endereco" type="text" class="form-control" maxlength="150">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                        <button type="submit" class="btn btn-primary">Cadastrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="m-edit-edit" tabindex="-1" aria-labelledby="m-edit-edit-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="producao.php">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="m-edit-edit-label">Editar Editora</h2>
                        <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="tipo" value="editora">
                        <input type="hidden" name="acao" value="atualizar">
                        <input type="hidden" name="id" value="<?= $editoraEdicao ? $editoraEdicao->getCodigoEditora() : '' ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="ee-nome" class="form-label">Nome</label>
                                <input id="ee-nome" name="nome" type="text" class="form-control" maxlength="100" value="<?= htmlspecialchars($editoraEdicao ? $editoraEdicao->getNomeEditora() : '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="ee-contacto" class="form-label">Contacto</label>
                                <input id="ee-contacto" name="contacto" type="tel" class="form-control" maxlength="20" value="<?= htmlspecialchars($editoraEdicao ? $editoraEdicao->getContactoEditora() : '') ?>">
                            </div>
                            <div class="col-12">
                                <label for="ee-email" class="form-label">E-mail</label>
                                <input id="ee-email" name="email" type="email" class="form-control" maxlength="100" value="<?= htmlspecialchars($editoraEdicao ? $editoraEdicao->getEmailEditora() : '') ?>">
                            </div>
                            <div class="col-12">
                                <label for="ee-endereco" class="form-label">Endereço</label>
                                <input id="ee-endereco" name="endereco" type="text" class="form-control" maxlength="150" value="<?= htmlspecialchars($editoraEdicao ? $editoraEdicao->getEndereco() : '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="m-rem-edit" tabindex="-1" aria-labelledby="m-rem-edit-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="m-rem-edit-label">Confirmar remoção</h2>
                    <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                </div>
                <div class="modal-body">Tem certeza que deseja remover esta editora?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal-close>Não</button>
                    <button type="submit" class="btn btn-primary" form="form-edit" name="acao" value="remover">Sim</button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
