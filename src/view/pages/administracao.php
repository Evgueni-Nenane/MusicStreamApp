<?php
require_once __DIR__ . '/../../model/sessao.php';
require_once __DIR__ . '/../../model/permissao.php';
require_once __DIR__ . '/../../model/utilizador.php';
require_once __DIR__ . '/../../model/nivelacesso.php';
require_once __DIR__ . '/../../controller/administracaoController.php';

$utilizador = Sessao::getUtilizadorLogado();
if (!$utilizador) {
    header('Location: Login.php');
    exit;
}

if (!Permissao::pode($utilizador, 'utilizadores')) {
    header('Location: listagemdiscos.php');
    exit;
}

$podeUtilizadores = true;
$podeLogs = Permissao::pode($utilizador, 'logs');

function formatarSimNao($valor)
{
    return $valor ? 'Sim' : 'Não';
}

$administracaoController = new AdministracaoController();

$tab = in_array($_GET['tab'] ?? '', ['cad', 'lista', 'perfis'], true) ? $_GET['tab'] : 'cad';
$resultadoPost = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultadoPost = $administracaoController->processarPost($utilizador, $_POST, $tab);
    $tab = $resultadoPost['tab'];
}

$estado = $administracaoController->prepararEcra($utilizador, $_GET, $tab, $resultadoPost);

$msg = $estado['msg'];
$erro = $estado['erro'];
$tab = $estado['tab'];
$utilizadorEdicao = $estado['utilizadorEdicao'];
$perfilEdicao = $estado['perfilEdicao'];
$lista = $estado['lista'];
$meuCodigo = $estado['meuCodigo'];
$niveis = $estado['niveis'];
$perfis = $estado['perfis'];
$termoPesquisa = $estado['termoPesquisa'];
$autoModal = $utilizadorEdicao ? '#m-edit-user' : ($perfilEdicao ? '#m-edit-perfil' : '');
$paginaAtiva = 'administracao';
$subtituloPagina = 'Administração de utilizadores';
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Administração | Sistema de Gestão</title>
    <script src="../js/foto-preview.js" defer></script>
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
                                        <a class="nav-link <?= $tab === 'cad' ? 'active' : '' ?>" href="administracao.php?tab=cad">Cadastrar Utilizador</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $tab === 'lista' ? 'active' : '' ?>" href="administracao.php?tab=lista">Informações de Utilizador</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $tab === 'perfis' ? 'active' : '' ?>" href="administracao.php?tab=perfis">Perfis</a>
                                    </li>
                                </ul>

                                <form method="get" action="administracao.php" class="col-12 col-md-4">
                                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                    <input type="search" class="form-control" name="q"
                                        value="<?= htmlspecialchars($termoPesquisa) ?>" placeholder="Pesquisar utilizador ou perfil...">
                                </form>
                            </div>

                            <?php if ($msg): ?>
                                <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
                            <?php endif; ?>
                            <?php if ($erro): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                            <?php endif; ?>

                            <?php if ($tab === 'cad'): ?>
                                <form method="post" action="administracao.php?tab=cad" enctype="multipart/form-data">
                                    <input type="hidden" name="acao" value="salvar">
                                    <div class="row g-4">
                                        <div class="col-12 col-md-3">
                                            <div class="text-center">
                                                <img id="cad-foto-preview" src="../resources/user.png" alt="Foto do Utilizador"
                                                    class="rounded-circle border object-fit-cover mb-3" width="140" height="140">
                                                <input type="file" class="form-control" id="cad-foto" name="foto" accept="image/*"
                                                    data-preview="cad-foto-preview">
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-9">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="cad-nome" class="form-label">Primeiro Nome *</label>
                                                    <input type="text" class="form-control" id="cad-nome" name="nome" maxlength="50" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="cad-apelido" class="form-label">Último Nome *</label>
                                                    <input type="text" class="form-control" id="cad-apelido" name="apelido" maxlength="50" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="cad-genero" class="form-label">Género</label>
                                                    <select class="form-select" id="cad-genero" name="genero">
                                                        <option value="MASCULINO">Masculino</option>
                                                        <option value="FEMININO">Feminino</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="cad-perfil" class="form-label">Perfil do Utilizador</label>
                                                    <select class="form-select" id="cad-perfil" name="perfil">
                                                        <?php foreach ($niveis as $nivel): ?>
                                                            <option value="<?= $nivel->getCodigoNivel() ?>"><?= htmlspecialchars($nivel->getNome()) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label for="cad-username" class="form-label">Nome de Utilizador *</label>
                                                    <input type="text" class="form-control" id="cad-username" name="username" maxlength="50" required>
                                                </div>
                                                <div class="col-12">
                                                    <label for="cad-email" class="form-label">E-mail Corporativo</label>
                                                    <input type="email" class="form-control" id="cad-email" name="email" maxlength="100">
                                                </div>
                                                <div class="col-12">
                                                    <label for="cad-tel" class="form-label">Número de Telemóvel</label>
                                                    <input type="tel" class="form-control" id="cad-tel" name="telefone" maxlength="20">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end border-top pt-3 mt-4">
                                        <button type="submit" class="btn btn-primary">Salvar Utilizador</button>
                                    </div>
                                </form>
                            <?php elseif ($tab === 'lista'): ?>
                                <form method="post" action="administracao.php">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Código</th>
                                                    <th>Nome Completo</th>
                                                    <th>E-mail Corporativo</th>
                                                    <th>Género</th>
                                                    <th>Perfil</th>
                                                    <th>Telefone</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($lista as $utilizadorLista): ?>
                                                    <?php
                                                    $idSelecao = 'sel-utilizador-' . (int) $utilizadorLista->getCodigo();
                                                    $nomeUtilizador = $utilizadorLista->getNomeCompleto();
                                                    ?>
                                                    <tr>
                                                        <td><input type="radio" class="form-check-input" name="sel" id="<?= $idSelecao ?>" value="<?= (int) $utilizadorLista->getCodigo() ?>" aria-label="Selecionar utilizador <?= htmlspecialchars($nomeUtilizador) ?>"></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= $utilizadorLista->getCodigo() ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($nomeUtilizador) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($utilizadorLista->getEmail()) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($utilizadorLista->getGenero()) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($utilizadorLista->getPerfil()->getNome()) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($utilizadorLista->getContacto()) ?></label></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (!$lista): ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center text-muted"><?= $termoPesquisa !== '' ? 'Nenhum utilizador encontrado.' : 'Nenhum utilizador cadastrado.' ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-3">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm" name="acao" value="remover">Remover Seleccionado</button>
                                        <button type="submit" class="btn btn-outline-secondary btn-sm" name="acao" value="resetar">Resetar Senha</button>
                                        <button type="submit" class="btn btn-primary btn-sm" name="acao" value="editar" formmethod="get">Editar Utilizador</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <form method="post" action="administracao.php?tab=perfis" class="mb-4">
                                    <input type="hidden" name="acao" value="criar_perfil">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="perfil-nome" class="form-label">Nome do Novo Perfil *</label>
                                            <input type="text" class="form-control" id="perfil-nome" name="nome_perfil" maxlength="50" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Permissões</label>
                                            <div class="row row-cols-1 row-cols-md-3 g-2 border rounded p-3 bg-light">
                                                <div class="col">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" checked disabled>
                                                        <label class="form-check-label">Listar</label>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" name="perm[adicionar]" value="1">
                                                        <label class="form-check-label">Criar</label>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" name="perm[editar]" value="1">
                                                        <label class="form-check-label">Editar</label>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" name="perm[remover]" value="1">
                                                        <label class="form-check-label">Remover</label>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" name="perm[utilizadores]" value="1">
                                                        <label class="form-check-label">Gerir utilizadores</label>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" name="perm[logs]" value="1">
                                                        <label class="form-check-label">Ver logs</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end border-top pt-3 mt-3">
                                        <button type="submit" class="btn btn-primary btn-sm">Salvar Perfil</button>
                                    </div>
                                </form>

                                <form method="post" action="administracao.php">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Código</th>
                                                    <th>Perfil</th>
                                                    <th>Listar</th>
                                                    <th>Criar</th>
                                                    <th>Editar</th>
                                                    <th>Remover</th>
                                                    <th>Gerir Utilizadores</th>
                                                    <th>Logs</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($perfis as $perfilLista): ?>
                                                    <?php
                                                    $idSelecao = 'sel-perfil-' . (int) $perfilLista['CodigoNivel'];
                                                    $nomePerfil = $perfilLista['NomeNivel'];
                                                    ?>
                                                    <tr>
                                                        <td><input type="radio" class="form-check-input" name="sel_perfil" id="<?= $idSelecao ?>" value="<?= (int) $perfilLista['CodigoNivel'] ?>" aria-label="Selecionar perfil <?= htmlspecialchars($nomePerfil) ?>"></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= (int) $perfilLista['CodigoNivel'] ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($nomePerfil) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= formatarSimNao($perfilLista['PodeLer']) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= formatarSimNao($perfilLista['PodeAdicionar']) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= formatarSimNao($perfilLista['PodeEditar']) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= formatarSimNao($perfilLista['PodeRemover']) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= formatarSimNao($perfilLista['PodeUtilizadores']) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= formatarSimNao($perfilLista['PodeLogs']) ?></label></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (!$perfis): ?>
                                                    <tr>
                                                        <td colspan="9" class="text-center text-muted"><?= $termoPesquisa !== '' ? 'Nenhum perfil encontrado.' : 'Nenhum perfil cadastrado.' ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-3">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm" name="acao" value="remover_perfil">Remover Perfil</button>
                                        <button type="submit" class="btn btn-primary btn-sm" name="acao" value="editar_perfil" formmethod="get">Editar Perfil</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <div class="modal fade" id="m-edit-user" tabindex="-1" aria-labelledby="m-edit-user-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="post" action="administracao.php" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="m-edit-user-label">Editar Utilizador</h2>
                        <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="atualizar">
                        <input type="hidden" name="id" value="<?= $utilizadorEdicao ? $utilizadorEdicao->getCodigo() : '' ?>">
                        <div class="row g-4">
                            <div class="col-12 col-md-4">
                                <div class="text-center">
                                    <img id="edit-foto-preview" src="<?= $utilizadorEdicao ? 'foto.php?u=' . (int) $utilizadorEdicao->getCodigo() : '../resources/user.png' ?>" alt="Foto do Utilizador"
                                        class="rounded-circle border object-fit-cover mb-3" width="100" height="100">
                                    <input type="file" class="form-control" id="edit-foto" name="foto" accept="image/*"
                                        data-preview="edit-foto-preview">
                                </div>
                            </div>
                            <div class="col-12 col-md-8">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Primeiro Nome</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($utilizadorEdicao ? $utilizadorEdicao->getNome() : '') ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Último Nome</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($utilizadorEdicao ? $utilizadorEdicao->getApelido() : '') ?>" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label for="eu-perfil" class="form-label">Perfil</label>
                                        <select class="form-select" id="eu-perfil" name="perfil">
                                            <?php foreach ($niveis as $nivel): ?>
                                                <option value="<?= $nivel->getCodigoNivel() ?>" <?= ($utilizadorEdicao && $utilizadorEdicao->getPerfil()->getCodigoNivel() == $nivel->getCodigoNivel()) ? 'selected' : '' ?>><?= htmlspecialchars($nivel->getNome()) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="eu-email" class="form-label">E-mail Corporativo</label>
                                        <input type="email" class="form-control" id="eu-email" name="email" maxlength="100" value="<?= htmlspecialchars($utilizadorEdicao ? $utilizadorEdicao->getEmail() : '') ?>">
                                    </div>
                                    <div class="col-12">
                                        <label for="eu-tel" class="form-label">Número de Telemóvel</label>
                                        <input type="tel" class="form-control" id="eu-tel" name="telefone" maxlength="20" value="<?= htmlspecialchars($utilizadorEdicao ? $utilizadorEdicao->getContacto() : '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                        <button type="submit" class="btn btn-primary">Atualizar Utilizador</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="m-edit-perfil" tabindex="-1" aria-labelledby="m-edit-perfil-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="administracao.php">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="m-edit-perfil-label">Editar Perfil</h2>
                        <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="atualizar_perfil">
                        <input type="hidden" name="id_perfil" value="<?= $perfilEdicao ? $perfilEdicao['CodigoNivel'] : '' ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="ep-nome" class="form-label">Nome do Perfil *</label>
                                <input type="text" class="form-control" id="ep-nome" name="nome_perfil" maxlength="50" required value="<?= htmlspecialchars($perfilEdicao ? $perfilEdicao['NomeNivel'] : '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Permissões</label>
                                <div class="row row-cols-1 row-cols-md-3 g-2 border rounded p-3 bg-light">
                                    <div class="col">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" checked disabled>
                                            <label class="form-check-label">Listar</label>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="perm[adicionar]" value="1" <?= ($perfilEdicao && $perfilEdicao['PodeAdicionar']) ? 'checked' : '' ?>>
                                            <label class="form-check-label">Criar</label>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="perm[editar]" value="1" <?= ($perfilEdicao && $perfilEdicao['PodeEditar']) ? 'checked' : '' ?>>
                                            <label class="form-check-label">Editar</label>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="perm[remover]" value="1" <?= ($perfilEdicao && $perfilEdicao['PodeRemover']) ? 'checked' : '' ?>>
                                            <label class="form-check-label">Remover</label>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="perm[utilizadores]" value="1" <?= ($perfilEdicao && $perfilEdicao['PodeUtilizadores']) ? 'checked' : '' ?>>
                                            <label class="form-check-label">Gerir utilizadores</label>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="perm[logs]" value="1" <?= ($perfilEdicao && $perfilEdicao['PodeLogs']) ? 'checked' : '' ?>>
                                            <label class="form-check-label">Ver logs</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                        <button type="submit" class="btn btn-primary">Atualizar Perfil</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>
