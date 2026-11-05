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

// Painel de utilizadores: só quem tem a permissão "utilizadores" entra aqui.
if (!Permissao::pode($utilizador, 'utilizadores')) {
    header('Location: listagemdiscos.php');
    exit;
}

$podeUtilizadores = true;
$podeLogs = Permissao::pode($utilizador, 'logs');

// Mostra "Sim" ou "Não" na tabela de perfis
function formatarSimNao($valor)
{
  return $valor ? 'Sim' : 'Não';
}

$administracaoController = new AdministracaoController();

$msg = '';
$tab = 'cad';

// Aba pedida na URL (a pesquisa envia a aba actual)
if (in_array($_GET['tab'] ?? '', ['cad', 'lista', 'perfis'], true)) {
  $tab = $_GET['tab'];
}

/* ---------- Cadastrar / Atualizar / Remover ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $administracaoController->processarPost($utilizador, $_POST, $tab);
    $tab = $resultado['tab'];
    $msg = $resultado['msg'];
}

/* ---------- Estado da página ---------- */
$estado = $administracaoController->prepararEcra($utilizador, $_GET, $tab, $msg);

$msg = $estado['msg'];
$tab = $estado['tab'];
$utilizadorEdicao = $estado['utilizadorEdicao'];
$perfilEdicao = $estado['perfilEdicao'];
$lista = $estado['lista'];
$meuCodigo = $estado['meuCodigo'];
$niveis = $estado['niveis'];
$perfis = $estado['perfis'];
$termoPesquisa = $estado['termoPesquisa'];
$paginaAtiva = 'administracao';
$subtituloPagina = 'Administração de utilizadores';
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração | Sistema de Gestão</title>
    <link rel="stylesheet" href="../css/administracao.css">
    <link rel="stylesheet" href="../css/registro.css">
</head>

<body>

    <div class="app-container">
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>

        <div class="containermain">
            <?php require __DIR__ . '/../partials/header.php'; ?>

            <main>
                <div class="card">
                    <!-- Formulário GET da pesquisa (o input liga-se a ele via form="form-pesq"). -->
                    <form method="get" action="administracao.php" id="form-pesq">
                        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                    </form>

                    <input type="radio" name="tabs" id="tab-cad" class="tab-toggle" <?= $tab === 'cad' ? 'checked' : '' ?>>
                    <input type="radio" name="tabs" id="tab-lista" class="tab-toggle" <?= $tab === 'lista' ? 'checked' : '' ?>>
                    <input type="radio" name="tabs" id="tab-perfis" class="tab-toggle" <?= $tab === 'perfis' ? 'checked' : '' ?>>

                    <div class="card-header">
                        <div class="tabs">
                            <label for="tab-cad" class="tab-label">Cadastrar Utilizador</label>
                            <label for="tab-lista" class="tab-label">Informações de Utilizador</label>
                            <label for="tab-perfis" class="tab-label">Perfis</label>
                        </div>
                        <input type="search" class="search-input" name="q" form="form-pesq"
                            value="<?= htmlspecialchars($termoPesquisa) ?>" placeholder="Pesquisar utilizador ou perfil...">
                    </div>

                    <?php if ($msg): ?>
                        <div class="alert alert-ok"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>

                    <!-- ===== CADASTRAR ===== -->
                    <section class="panel panel-cad">
                        <form method="post" action="administracao.php" enctype="multipart/form-data">
                            <input type="hidden" name="acao" value="salvar">
                            <div class="cadastro-layout">
                                <div class="foto-user">
                                    <div class="foto-preview"><img src="../resources/user.png" alt="Foto do Utilizador"></div>
                                    <div class="foto-actions">
                                        <input type="file" id="cad-foto" name="foto" accept="image/*" class="file-hidden">
                                        <label for="cad-foto" class="btn btn-primary btn-sm">Escolher Foto</label>
                                    </div>
                                </div>

                                <div class="form-grid">
                                    <div class="label-group">
                                        <label for="cad-nome">Primeiro Nome *</label>
                                        <input type="text" id="cad-nome" name="nome" required>
                                    </div>
                                    <div class="label-group">
                                        <label for="cad-apelido">Último Nome *</label>
                                        <input type="text" id="cad-apelido" name="apelido" required>
                                    </div>
                                    <div class="label-group">
                                        <label for="cad-genero">Género</label>
                                        <select id="cad-genero" name="genero">
                                            <option value="MASCULINO">Masculino</option>
                                            <option value="FEMININO">Feminino</option>
                                        </select>
                                    </div>
                                    <div class="label-group">
                                        <label for="cad-perfil">Perfil do Utilizador</label>
                                        <select id="cad-perfil" name="perfil">
                                            <?php foreach ($niveis as $nivel): ?>
                                                <option value="<?= $nivel->getCodigoNivel() ?>"><?= htmlspecialchars($nivel->getNome()) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="label-group full">
                                        <label for="cad-username">Nome de Utilizador *</label>
                                        <input type="text" id="cad-username" name="username" required>
                                    </div>
                                    <div class="label-group full">
                                        <label for="cad-email">E-mail Corporativo</label>
                                        <input type="email" id="cad-email" name="email">
                                    </div>
                                    <div class="label-group full">
                                        <label for="cad-tel">Número de Telemóvel</label>
                                        <input type="tel" id="cad-tel" name="telefone">
                                    </div>
                                </div>
                            </div>
                            <div class="table-actions">
                                <button class="btn btn-primary" type="submit">Salvar Utilizador</button>
                            </div>
                        </form>
                    </section>

                    <!-- ===== LISTA ===== -->
                    <section class="panel panel-lista">
                        <form method="post" action="administracao.php">
                            <div class="table-wrapper">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="col-sel"></th>
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
                                            <tr>
                                                <td><input type="radio" name="sel" value="<?= $utilizadorLista->getCodigo() ?>"></td>
                                                <td><?= $utilizadorLista->getCodigo() ?></td>
                                                <td><?= htmlspecialchars($utilizadorLista->getNomeCompleto()) ?></td>
                                                <td><?= htmlspecialchars($utilizadorLista->getEmail()) ?></td>
                                                <td><?= htmlspecialchars($utilizadorLista->getGenero()) ?></td>
                                                <td><?= htmlspecialchars($utilizadorLista->getPerfil()->getNome()) ?></td>
                                                <td><?= htmlspecialchars($utilizadorLista->getContacto()) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (!$lista): ?>
                                            <tr>
                                                <td colspan="7"><?= $termoPesquisa !== '' ? 'Nenhum utilizador encontrado.' : 'Nenhum utilizador cadastrado.' ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="table-actions">
                                <button class="btn btn-secondary btn-sm" type="submit" name="acao" value="remover">Remover Seleccionado</button>
                                <button class="btn btn-secondary btn-sm" type="submit" name="acao" value="resetar">Resetar Senha</button>
                                <button class="btn btn-primary btn-sm" type="submit" name="acao" value="editar" formmethod="get">Editar Utilizador</button>
                            </div>
                        </form>
                    </section>

                    <!-- ===== PERFIS ===== -->
                    <section class="panel panel-perfis">
                        <!-- Criar perfil novo -->
                        <form method="post" action="administracao.php">
                            <input type="hidden" name="acao" value="criar_perfil">
                            <div class="form-grid">
                                <div class="label-group full">
                                    <label for="perfil-nome">Nome do Novo Perfil *</label>
                                    <input type="text" id="perfil-nome" name="nome_perfil" required>
                                </div>
                                <div class="label-group full">
                                    <label>Permissões</label>
                                    <div class="perm-grid">
                                        <label class="perm-check">
                                            <input type="checkbox" checked disabled>
                                            Listar
                                        </label>
                                        <label class="perm-check">
                                            <input type="checkbox" name="perm[adicionar]" value="1">
                                            Criar
                                        </label>
                                        <label class="perm-check">
                                            <input type="checkbox" name="perm[editar]" value="1">
                                            Editar
                                        </label>
                                        <label class="perm-check">
                                            <input type="checkbox" name="perm[remover]" value="1">
                                            Remover
                                        </label>
                                        <label class="perm-check">
                                            <input type="checkbox" name="perm[utilizadores]" value="1">
                                            Gerir utilizadores
                                        </label>
                                        <label class="perm-check">
                                            <input type="checkbox" name="perm[logs]" value="1">
                                            Ver logs
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="table-actions">
                                <button class="btn btn-primary btn-sm" type="submit">Salvar Perfil</button>
                            </div>
                        </form>

                        <!-- Lista de perfis -->
                        <form method="post" action="administracao.php">
                            <div class="table-wrapper">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="col-sel"></th>
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
                                            <tr>
                                                <td><input type="radio" name="sel_perfil" value="<?= $perfilLista['CodigoNivel'] ?>"></td>
                                                <td><?= $perfilLista['CodigoNivel'] ?></td>
                                                <td><?= htmlspecialchars($perfilLista['NomeNivel']) ?></td>
                                                <td><?= formatarSimNao($perfilLista['PodeLer']) ?></td>
                                                <td><?= formatarSimNao($perfilLista['PodeAdicionar']) ?></td>
                                                <td><?= formatarSimNao($perfilLista['PodeEditar']) ?></td>
                                                <td><?= formatarSimNao($perfilLista['PodeRemover']) ?></td>
                                                <td><?= formatarSimNao($perfilLista['PodeUtilizadores']) ?></td>
                                                <td><?= formatarSimNao($perfilLista['PodeLogs']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (!$perfis): ?>
                                            <tr>
                                                <td colspan="9"><?= $termoPesquisa !== '' ? 'Nenhum perfil encontrado.' : 'Nenhum perfil cadastrado.' ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="table-actions">
                                <button class="btn btn-secondary btn-sm" type="submit" name="acao" value="remover_perfil">Remover Perfil</button>
                                <button class="btn btn-primary btn-sm" type="submit" name="acao" value="editar_perfil" formmethod="get">Editar Perfil</button>
                            </div>
                        </form>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <!-- ============ MODAL: EDITAR UTILIZADOR ============ -->
    <input type="checkbox" id="m-edit-user" class="modal-toggle" <?= $utilizadorEdicao ? 'checked' : '' ?>>
    <div class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Editar Utilizador</h2>
                <label for="m-edit-user" class="modal-close">&times;</label>
            </div>
            <form method="post" action="administracao.php" enctype="multipart/form-data">
                <input type="hidden" name="acao" value="atualizar">
                <input type="hidden" name="id" value="<?= $utilizadorEdicao ? $utilizadorEdicao->getCodigo() : '' ?>">
                <div class="modal-body">
                    <div class="cadastro-layout sm">
                        <div class="foto-user">
                            <div class="foto-preview">
                                <img src="<?= $utilizadorEdicao ? 'foto.php?u=' . (int) $utilizadorEdicao->getCodigo() : '../resources/user.png' ?>" alt="Foto do Utilizador">
                            </div>
                            <div class="foto-actions">
                                <input type="file" id="edit-foto" name="foto" accept="image/*" class="file-hidden">
                                <label for="edit-foto" class="btn btn-primary btn-sm">Escolher Foto</label>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="label-group">
                                <label>Primeiro Nome</label>
                                <input type="text" value="<?= htmlspecialchars($utilizadorEdicao ? $utilizadorEdicao->getNome() : '') ?>" readonly>
                            </div>
                            <div class="label-group">
                                <label>Último Nome</label>
                                <input type="text" value="<?= htmlspecialchars($utilizadorEdicao ? $utilizadorEdicao->getApelido() : '') ?>" readonly>
                            </div>
                            <div class="label-group full">
                                <label for="eu-perfil">Perfil</label>
                                <select id="eu-perfil" name="perfil">
                                    <?php foreach ($niveis as $nivel): ?>
                                        <option value="<?= $nivel->getCodigoNivel() ?>" <?= ($utilizadorEdicao && $utilizadorEdicao->getPerfil()->getCodigoNivel() == $nivel->getCodigoNivel()) ? 'selected' : '' ?>><?= htmlspecialchars($nivel->getNome()) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="label-group full">
                                <label for="eu-email">E-mail Corporativo</label>
                                <input type="email" id="eu-email" name="email" value="<?= htmlspecialchars($utilizadorEdicao ? $utilizadorEdicao->getEmail() : '') ?>">
                            </div>
                            <div class="label-group full">
                                <label for="eu-tel">Número de Telemóvel</label>
                                <input type="tel" id="eu-tel" name="telefone" value="<?= htmlspecialchars($utilizadorEdicao ? $utilizadorEdicao->getContacto() : '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <label for="m-edit-user" class="btn btn-secondary">Cancelar</label>
                    <button class="btn btn-primary" type="submit">Atualizar Utilizador</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============ MODAL: EDITAR PERFIL ============ -->
    <input type="checkbox" id="m-edit-perfil" class="modal-toggle" <?= $perfilEdicao ? 'checked' : '' ?>>
    <div class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Editar Perfil</h2>
                <label for="m-edit-perfil" class="modal-close">&times;</label>
            </div>
            <form method="post" action="administracao.php">
                <input type="hidden" name="acao" value="atualizar_perfil">
                <input type="hidden" name="id_perfil" value="<?= $perfilEdicao ? $perfilEdicao['CodigoNivel'] : '' ?>">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="label-group full">
                            <label for="ep-nome">Nome do Perfil *</label>
                            <input type="text" id="ep-nome" name="nome_perfil" required value="<?= htmlspecialchars($perfilEdicao ? $perfilEdicao['NomeNivel'] : '') ?>">
                        </div>
                        <div class="label-group full">
                            <label>Permissões</label>
                            <div class="perm-grid">
                                <label class="perm-check">
                                    <input type="checkbox" checked disabled>
                                    Listar
                                </label>
                                <label class="perm-check">
                                    <input type="checkbox" name="perm[adicionar]" value="1" <?= ($perfilEdicao && $perfilEdicao['PodeAdicionar']) ? 'checked' : '' ?>>
                                    Criar
                                </label>
                                <label class="perm-check">
                                    <input type="checkbox" name="perm[editar]" value="1" <?= ($perfilEdicao && $perfilEdicao['PodeEditar']) ? 'checked' : '' ?>>
                                    Editar
                                </label>
                                <label class="perm-check">
                                    <input type="checkbox" name="perm[remover]" value="1" <?= ($perfilEdicao && $perfilEdicao['PodeRemover']) ? 'checked' : '' ?>>
                                    Remover
                                </label>
                                <label class="perm-check">
                                    <input type="checkbox" name="perm[utilizadores]" value="1" <?= ($perfilEdicao && $perfilEdicao['PodeUtilizadores']) ? 'checked' : '' ?>>
                                    Gerir utilizadores
                                </label>
                                <label class="perm-check">
                                    <input type="checkbox" name="perm[logs]" value="1" <?= ($perfilEdicao && $perfilEdicao['PodeLogs']) ? 'checked' : '' ?>>
                                    Ver logs
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <label for="m-edit-perfil" class="btn btn-secondary">Cancelar</label>
                    <button class="btn btn-primary" type="submit">Atualizar Perfil</button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>
