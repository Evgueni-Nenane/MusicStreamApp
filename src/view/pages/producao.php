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

/* ---------- Acções (POST) ---------- */
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

/* ---------- Estado da página ---------- */
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
$paginaAtiva = 'producao';
$subtituloPagina = 'Gestão de produção';
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produção | Sistema de Gestão</title>
    <link rel="stylesheet" href="../css/producao.css">
</head>

<body>

    <div class="app-container">
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>

        <div class="containermain">
            <?php require __DIR__ . '/../partials/header.php'; ?>

            <main>
                <div class="card">
                    <form method="get" action="producao.php" id="form-pesq"></form>

                    <input type="radio" name="tabs" id="tab-prod" class="tab-toggle" <?= $tab === 'prod' ? 'checked' : '' ?>>
                    <input type="radio" name="tabs" id="tab-grav" class="tab-toggle" <?= $tab === 'grav' ? 'checked' : '' ?>>
                    <input type="radio" name="tabs" id="tab-edit" class="tab-toggle" <?= $tab === 'edit' ? 'checked' : '' ?>>

                    <div class="card-header">
                        <div class="tabs">
                            <label for="tab-prod" class="tab-label">Produtores</label>
                            <label for="tab-grav" class="tab-label">Gravadoras</label>
                            <label for="tab-edit" class="tab-label">Editoras</label>
                        </div>
                        <input type="search" class="search-input" name="q" form="form-pesq"
                            value="<?= htmlspecialchars($termoPesquisa) ?>" placeholder="Pesquisar...">
                    </div>

                    <?php if ($msg): ?>
                        <div class="alert alert-ok"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>
                    <?php if ($erro): ?>
                        <div class="alert alert-erro"><?= htmlspecialchars($erro) ?></div>
                    <?php endif; ?>

                    <!-- ===== PRODUTORES ===== -->
                    <section class="panel panel-prod">
                        <form method="post" id="form-prod">
                            <input type="hidden" name="tipo" value="produtor">
                            <div class="table-wrapper">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="col-sel"></th>
                                            <th>Nome</th>
                                            <th>Contacto</th>
                                            <th>E-mail</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($produtores as $produtor): ?>
                                            <tr>
                                                <td><input type="radio" name="sel" value="<?= $produtor->getCodigoProdutor() ?>"></td>
                                                <td><?= htmlspecialchars($produtor->getNomeCompleto()) ?></td>
                                                <td><?= htmlspecialchars($produtor->getContactoProdutor()) ?></td>
                                                <td><?= htmlspecialchars($produtor->getEmailProdutor()) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (!$produtores): ?>
                                            <tr>
                                                <td colspan="4">
                                                    <?= $termoPesquisa !== '' ? 'Nenhum produtor encontrado.' : 'Nenhum produtor cadastrado.' ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="table-actions">
                                <?php if ($podeRemover): ?><label for="m-rem-prod" class="btn btn-secondary btn-sm">Remover</label><?php endif; ?>
                                <?php if ($podeEditar): ?>
                                    <button type="submit" name="acao" value="editar" formmethod="get" class="btn btn-secondary btn-sm">Editar</button>
                                <?php endif; ?>
                                <?php if ($podeAdicionar): ?><label for="m-add-prod" class="btn btn-primary btn-sm">Adicionar</label><?php endif; ?>
                            </div>
                        </form>
                    </section>

                    <!-- ===== GRAVADORAS ===== -->
                    <section class="panel panel-grav">
                        <form method="post" id="form-grav">
                            <input type="hidden" name="tipo" value="gravadora">
                            <div class="table-wrapper">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="col-sel"></th>
                                            <th>Nome</th>
                                            <th>Contacto</th>
                                            <th>E-mail</th>
                                            <th>Endereço</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($gravadoras as $gravadora): ?>
                                            <tr>
                                                <td><input type="radio" name="sel" value="<?= $gravadora->getCodigoGravadora() ?>"></td>
                                                <td><?= htmlspecialchars($gravadora->getNomeGravadora()) ?></td>
                                                <td><?= htmlspecialchars($gravadora->getContactoGravadora()) ?></td>
                                                <td><?= htmlspecialchars($gravadora->getEmailGravadora()) ?></td>
                                                <td><?= htmlspecialchars($gravadora->getEnderecoGravadora()) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (!$gravadoras): ?>
                                            <tr>
                                                <td colspan="5">
                                                    <?= $termoPesquisa !== '' ? 'Nenhuma gravadora encontrada.' : 'Nenhuma gravadora cadastrada.' ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="table-actions">
                                <?php if ($podeRemover): ?><label for="m-rem-grav" class="btn btn-secondary btn-sm">Remover</label><?php endif; ?>
                                <?php if ($podeEditar): ?>
                                    <button type="submit" name="acao" value="editar" formmethod="get" class="btn btn-secondary btn-sm">Editar</button>
                                <?php endif; ?>
                                <?php if ($podeAdicionar): ?><label for="m-add-grav" class="btn btn-primary btn-sm">Adicionar</label><?php endif; ?>
                            </div>
                        </form>
                    </section>

                    <!-- ===== EDITORAS ===== -->
                    <section class="panel panel-edit">
                        <form method="post" id="form-edit">
                            <input type="hidden" name="tipo" value="editora">
                            <div class="table-wrapper">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="col-sel"></th>
                                            <th>Nome</th>
                                            <th>Contacto</th>
                                            <th>E-mail</th>
                                            <th>Endereço</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($editoras as $editora): ?>
                                            <tr>
                                                <td><input type="radio" name="sel" value="<?= $editora->getCodigoEditora() ?>"></td>
                                                <td><?= htmlspecialchars($editora->getNomeEditora()) ?></td>
                                                <td><?= htmlspecialchars($editora->getContactoEditora()) ?></td>
                                                <td><?= htmlspecialchars($editora->getEmailEditora()) ?></td>
                                                <td><?= htmlspecialchars($editora->getEndereco()) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (!$editoras): ?>
                                            <tr>
                                                <td colspan="5">
                                                    <?= $termoPesquisa !== '' ? 'Nenhuma editora encontrada.' : 'Nenhuma editora cadastrada.' ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="table-actions">
                                <?php if ($podeRemover): ?><label for="m-rem-edit" class="btn btn-secondary btn-sm">Remover</label><?php endif; ?>
                                <?php if ($podeEditar): ?>
                                    <button type="submit" name="acao" value="editar" formmethod="get" class="btn btn-secondary btn-sm">Editar</button>
                                <?php endif; ?>
                                <?php if ($podeAdicionar): ?><label for="m-add-edit" class="btn btn-primary btn-sm">Adicionar</label><?php endif; ?>
                            </div>
                        </form>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <!-- ================= MODAIS: PRODUTOR ================= -->
    <input type="checkbox" id="m-add-prod" class="modal-toggle">
    <div class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Cadastrar Produtor</h2>
                <label for="m-add-prod" class="modal-close">&times;</label>
            </div>
            <form method="post" action="producao.php">
                <input type="hidden" name="tipo" value="produtor">
                <input type="hidden" name="acao" value="adicionar">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="label-group">
                            <label for="ap-nome">Nome *</label>
                            <input id="ap-nome" name="nome" type="text" maxlength="50" required>
                        </div>
                        <div class="label-group">
                            <label for="ap-apelido">Apelido *</label>
                            <input id="ap-apelido" name="apelido" type="text" maxlength="50" required>
                        </div>
                        <div class="label-group full">
                            <label for="ap-contacto">Contacto</label>
                            <input id="ap-contacto" name="contacto" type="tel" maxlength="20">
                        </div>
                        <div class="label-group full">
                            <label for="ap-email">E-mail</label>
                            <input id="ap-email" name="email" type="email" maxlength="100">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <label for="m-add-prod" class="btn btn-secondary">Cancelar</label>
                    <button class="btn btn-primary" type="submit">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>

    <input type="checkbox" id="m-edit-prod" class="modal-toggle" <?= $produtorEdicao ? 'checked' : '' ?>>
    <div class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Editar Produtor</h2>
                <label for="m-edit-prod" class="modal-close">&times;</label>
            </div>
            <form method="post" action="producao.php">
                <input type="hidden" name="tipo" value="produtor">
                <input type="hidden" name="acao" value="atualizar">
                <input type="hidden" name="id" value="<?= $produtorEdicao ? $produtorEdicao->getCodigoProdutor() : '' ?>">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="label-group">
                            <label for="ep-nome">Nome</label>
                            <input id="ep-nome" name="nome" type="text" maxlength="50" value="<?= htmlspecialchars($produtorEdicao ? $produtorEdicao->getNomeProdutor() : '') ?>" required>
                        </div>
                        <div class="label-group">
                            <label for="ep-apelido">Apelido</label>
                            <input id="ep-apelido" name="apelido" type="text" maxlength="50" value="<?= htmlspecialchars($produtorEdicao ? $produtorEdicao->getApelidoProdutor() : '') ?>" required>
                        </div>
                        <div class="label-group full">
                            <label for="ep-contacto">Contacto</label>
                            <input id="ep-contacto" name="contacto" type="tel" maxlength="20" value="<?= htmlspecialchars($produtorEdicao ? $produtorEdicao->getContactoProdutor() : '') ?>">
                        </div>
                        <div class="label-group full">
                            <label for="ep-email">E-mail</label>
                            <input id="ep-email" name="email" type="email" maxlength="100" value="<?= htmlspecialchars($produtorEdicao ? $produtorEdicao->getEmailProdutor() : '') ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <label for="m-edit-prod" class="btn btn-secondary">Cancelar</label>
                    <button class="btn btn-primary" type="submit">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <input type="checkbox" id="m-rem-prod" class="modal-toggle">
    <div class="modal-overlay">
        <div class="modal confirm">
            <div class="modal-header">
                <h2>Confirmar remoção</h2>
                <label for="m-rem-prod" class="modal-close">&times;</label>
            </div>
            <div class="modal-body">Tem certeza que deseja remover este produtor?</div>
            <div class="modal-footer">
                <label for="m-rem-prod" class="btn btn-secondary">Não</label>
                <button class="btn btn-primary" type="submit" form="form-prod" name="acao" value="remover">Sim</button>
            </div>
        </div>
    </div>

    <!-- ================= MODAIS: GRAVADORA ================= -->
    <input type="checkbox" id="m-add-grav" class="modal-toggle">
    <div class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Cadastrar Gravadora</h2>
                <label for="m-add-grav" class="modal-close">&times;</label>
            </div>
            <form method="post" action="producao.php">
                <input type="hidden" name="tipo" value="gravadora">
                <input type="hidden" name="acao" value="adicionar">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="label-group full">
                            <label for="ag-nome">Nome *</label>
                            <input id="ag-nome" name="nome" type="text" maxlength="100" required>
                        </div>
                        <div class="label-group full">
                            <label for="ag-contacto">Contacto</label>
                            <input id="ag-contacto" name="contacto" type="tel" maxlength="20">
                        </div>
                        <div class="label-group full">
                            <label for="ag-email">E-mail</label>
                            <input id="ag-email" name="email" type="email" maxlength="100">
                        </div>
                        <div class="label-group full">
                            <label for="ag-endereco">Endereço</label>
                            <input id="ag-endereco" name="endereco" type="text" maxlength="150">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <label for="m-add-grav" class="btn btn-secondary">Cancelar</label>
                    <button class="btn btn-primary" type="submit">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>

    <input type="checkbox" id="m-edit-grav" class="modal-toggle" <?= $gravadoraEdicao ? 'checked' : '' ?>>
    <div class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Editar Gravadora</h2>
                <label for="m-edit-grav" class="modal-close">&times;</label>
            </div>
            <form method="post" action="producao.php">
                <input type="hidden" name="tipo" value="gravadora">
                <input type="hidden" name="acao" value="atualizar">
                <input type="hidden" name="id" value="<?= $gravadoraEdicao ? $gravadoraEdicao->getCodigoGravadora() : '' ?>">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="label-group full">
                            <label for="eg-nome">Nome</label>
                            <input id="eg-nome" name="nome" type="text" maxlength="100" value="<?= htmlspecialchars($gravadoraEdicao ? $gravadoraEdicao->getNomeGravadora() : '') ?>" required>
                        </div>
                        <div class="label-group full">
                            <label for="eg-contacto">Contacto</label>
                            <input id="eg-contacto" name="contacto" type="tel" maxlength="20" value="<?= htmlspecialchars($gravadoraEdicao ? $gravadoraEdicao->getContactoGravadora() : '') ?>">
                        </div>
                        <div class="label-group full">
                            <label for="eg-email">E-mail</label>
                            <input id="eg-email" name="email" type="email" maxlength="100" value="<?= htmlspecialchars($gravadoraEdicao ? $gravadoraEdicao->getEmailGravadora() : '') ?>">
                        </div>
                        <div class="label-group full">
                            <label for="eg-endereco">Endereço</label>
                            <input id="eg-endereco" name="endereco" type="text" maxlength="150" value="<?= htmlspecialchars($gravadoraEdicao ? $gravadoraEdicao->getEnderecoGravadora() : '') ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <label for="m-edit-grav" class="btn btn-secondary">Cancelar</label>
                    <button class="btn btn-primary" type="submit">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <input type="checkbox" id="m-rem-grav" class="modal-toggle">
    <div class="modal-overlay">
        <div class="modal confirm">
            <div class="modal-header">
                <h2>Confirmar remoção</h2>
                <label for="m-rem-grav" class="modal-close">&times;</label>
            </div>
            <div class="modal-body">Tem certeza que deseja remover esta gravadora?</div>
            <div class="modal-footer">
                <label for="m-rem-grav" class="btn btn-secondary">Não</label>
                <button class="btn btn-primary" type="submit" form="form-grav" name="acao" value="remover">Sim</button>
            </div>
        </div>
    </div>

    <!-- ================= MODAIS: EDITORA ================= -->
    <input type="checkbox" id="m-add-edit" class="modal-toggle">
    <div class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Cadastrar Editora</h2>
                <label for="m-add-edit" class="modal-close">&times;</label>
            </div>
            <form method="post" action="producao.php">
                <input type="hidden" name="tipo" value="editora">
                <input type="hidden" name="acao" value="adicionar">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="label-group full">
                            <label for="ae-nome">Nome *</label>
                            <input id="ae-nome" name="nome" type="text" maxlength="100" required>
                        </div>
                        <div class="label-group full">
                            <label for="ae-contacto">Contacto</label>
                            <input id="ae-contacto" name="contacto" type="tel" maxlength="20">
                        </div>
                        <div class="label-group full">
                            <label for="ae-email">E-mail</label>
                            <input id="ae-email" name="email" type="email" maxlength="100">
                        </div>
                        <div class="label-group full">
                            <label for="ae-endereco">Endereço</label>
                            <input id="ae-endereco" name="endereco" type="text" maxlength="150">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <label for="m-add-edit" class="btn btn-secondary">Cancelar</label>
                    <button class="btn btn-primary" type="submit">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>

    <input type="checkbox" id="m-edit-edit" class="modal-toggle" <?= $editoraEdicao ? 'checked' : '' ?>>
    <div class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Editar Editora</h2>
                <label for="m-edit-edit" class="modal-close">&times;</label>
            </div>
            <form method="post" action="producao.php">
                <input type="hidden" name="tipo" value="editora">
                <input type="hidden" name="acao" value="atualizar">
                <input type="hidden" name="id" value="<?= $editoraEdicao ? $editoraEdicao->getCodigoEditora() : '' ?>">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="label-group full">
                            <label for="ee-nome">Nome</label>
                            <input id="ee-nome" name="nome" type="text" maxlength="100" value="<?= htmlspecialchars($editoraEdicao ? $editoraEdicao->getNomeEditora() : '') ?>" required>
                        </div>
                        <div class="label-group full">
                            <label for="ee-contacto">Contacto</label>
                            <input id="ee-contacto" name="contacto" type="tel" maxlength="20" value="<?= htmlspecialchars($editoraEdicao ? $editoraEdicao->getContactoEditora() : '') ?>">
                        </div>
                        <div class="label-group full">
                            <label for="ee-email">E-mail</label>
                            <input id="ee-email" name="email" type="email" maxlength="100" value="<?= htmlspecialchars($editoraEdicao ? $editoraEdicao->getEmailEditora() : '') ?>">
                        </div>
                        <div class="label-group full">
                            <label for="ee-endereco">Endereço</label>
                            <input id="ee-endereco" name="endereco" type="text" maxlength="150" value="<?= htmlspecialchars($editoraEdicao ? $editoraEdicao->getEndereco() : '') ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <label for="m-edit-edit" class="btn btn-secondary">Cancelar</label>
                    <button class="btn btn-primary" type="submit">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <input type="checkbox" id="m-rem-edit" class="modal-toggle">
    <div class="modal-overlay">
        <div class="modal confirm">
            <div class="modal-header">
                <h2>Confirmar remoção</h2>
                <label for="m-rem-edit" class="modal-close">&times;</label>
            </div>
            <div class="modal-body">Tem certeza que deseja remover esta editora?</div>
            <div class="modal-footer">
                <label for="m-rem-edit" class="btn btn-secondary">Não</label>
                <button class="btn btn-primary" type="submit" form="form-edit" name="acao" value="remover">Sim</button>
            </div>
        </div>
    </div>

</body>

</html>
