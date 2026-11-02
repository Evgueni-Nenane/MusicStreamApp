<?php
require_once __DIR__ . '/../../model/sessao.php';
require_once __DIR__ . '/../../model/permissao.php';
require_once __DIR__ . '/../../model/utilizador.php';
require_once __DIR__ . '/../../model/nivelacesso.php';
require_once __DIR__ . '/../../controller/artistasController.php';

$utilizador = Sessao::getUtilizadorLogado();
if (!$utilizador) { header('Location: Login.php'); exit; }
$podeAdicionar = Permissao::pode($utilizador, 'adicionar');
$podeEditar    = Permissao::pode($utilizador, 'editar');
$podeRemover   = Permissao::pode($utilizador, 'remover');
$podeUsers     = Permissao::pode($utilizador, 'utilizadores');
$podeLogs      = Permissao::pode($utilizador, 'logs');

// Botões de acção de uma aba. Cada aba tem o seu formulário (form-musico,
// form-compositor, form-cantor), por isso o Editar envia o rádio seleccionado
// por GET e o Remover abre o modal de confirmação dessa aba.
function acoes($tipo, $podeRemover, $podeEditar, $podeAdicionar)
{
    echo '<div class="table-actions">';
    if ($podeRemover) {
        echo '<label for="m-rem-' . $tipo . '" class="btn btn-secondary btn-sm" style="cursor:pointer; margin:0;">Remover</label>';
    }
    if ($podeEditar) {
        echo '<button type="submit" name="acao" value="editar" formmethod="get" class="btn btn-secondary btn-sm">Editar</button>';
    }
    if ($podeAdicionar) {
        echo '<label for="modal-adicionar-artista-toggle" class="btn btn-primary btn-sm" style="cursor:pointer; margin:0;">Adicionar</label>';
    }
    echo '</div>';
}

$artistasController = new ArtistasController();

/* ---------- Cadastrar / Atualizar / Remover (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $artistasController->processarPost($utilizador, $_POST);
    header('Location: artistas.php?tab=' . $resultado['aba'] . '&msg=' . urlencode($resultado['msg']));
    exit;
}

/* ---------- Estado da página ---------- */
$estado = $artistasController->prepararEcra($_GET);

$msg            = $estado['msg'];
$tab            = $estado['tab'];
$ed             = $estado['ed'];
$edTipo         = $estado['edTipo'];
$edInstrumentos = $estado['edInstrumentos'];
$edId           = $estado['edId'];
$edNome         = $estado['edNome'];
$edApelido      = $estado['edApelido'];
$edContacto     = $estado['edContacto'];
$edEmail        = $estado['edEmail'];
$edTitulo       = $estado['edTitulo'];
$musicos        = $estado['musicos'];
$cantores       = $estado['cantores'];
$compositores   = $estado['compositores'];
$instrumentos   = $estado['instrumentos'];
$q              = $estado['q'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artistas | Sistema de Gestão</title>
    <link rel="stylesheet" href="../css/artistas.css">
    <link rel="stylesheet" href="../css/registro.css">
</head>
<body>

    <input type="radio" name="tabs" id="tab-musicos" class="tab-toggle" <?= $tab === 'musicos' ? 'checked' : '' ?>>
    <input type="radio" name="tabs" id="tab-compositores" class="tab-toggle" <?= $tab === 'compositores' ? 'checked' : '' ?>>
    <input type="radio" name="tabs" id="tab-cantores" class="tab-toggle" <?= $tab === 'cantores' ? 'checked' : '' ?>>

    <input type="checkbox" id="modal-adicionar-artista-toggle" class="modal-toggle">
    <input type="checkbox" id="modal-editar-artista-toggle" class="modal-toggle" <?= $ed ? 'checked' : '' ?>>

    <div class="app-container">
        <aside>
            <div class="title" id="artistas"><h1>DiscoGest</h1></div>
            <nav>
                <ul>
                    <li class="secnav">Menu Principal</li>
                    <li><a href="registro.php">Registar</a></li>
                    <li><a href="listagemdiscos.php">Listar Discos</a></li>
                    <li class="secnav">Intervenientes</li>
                    <li><a href="artistas.php" class="active">Artistas</a></li>
                    <li><a href="producao.php">Produção</a></li>
                    <li class="secnav">Cadastros</li>
                    <li><a href="instrumentos.php">Instrumentos</a></li>
                    <li><a href="generos.php">Géneros</a></li>
                    <li class="secnav">Acções</li>
                    <!-- <li><a href="#">Exportar</a></li> -->
                    <?php if ($podeUsers): ?><li><a href="administracao.php">Administração</a></li><?php endif; ?>
                    <?php if ($podeLogs): ?><li><a href="logs.php">Logs</a></li><?php endif; ?>
                    <li><a href="LogOut.php">Sair</a></li>
                </ul>
            </nav>
        </aside>

        <div class="containermain">
            <header>
                <div class="pagetitle">
                    <h2>Sistema de Gestão de Discos</h2>
                    <p>Gestão de artistas</p>
                </div>
                <div class="userdetails">
                    <div class="userdetailstxt">
                        <p><?= htmlspecialchars($utilizador->getNomeCompleto()) ?></p>
                        <p><?= htmlspecialchars($utilizador->getPerfil()->getNome()) ?></p>
                    </div>
                    <img src="foto.php" onerror="this.onerror=null;this.src='../resources/user.png'" alt="Foto de Perfil">
                </div>
            </header>

            <main>
                <div class="card">
                    
                    <form method="get" action="artistas.php" id="form-pesq">
                        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                    </form>

                    <div class="card-header">
                        <div class="tabs">
                            <label for="tab-musicos" class="tab-label">Listar Músicos</label>
                            <label for="tab-compositores" class="tab-label">Listar Compositores</label>
                            <label for="tab-cantores" class="tab-label">Listar Cantores</label>
                        </div>
                        <input type="search" class="search-input" name="q" form="form-pesq"
                            value="<?= htmlspecialchars($q) ?>" placeholder="Pesquisar artista...">
                    </div>

                    <?php if ($msg): ?>
                        <div class="alert alert-ok"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>

                    <!-- Músicos -->
                    <form method="post" action="artistas.php" id="form-musico">
                        <input type="hidden" name="tipo" value="musico">
                        <div class="tab-content tab-content-musicos">
                            <div class="table-wrapper">
                                <table>
                                    <thead>
                                        <tr><th class="col-sel"></th><th>Nome Completo</th><th>Instrumento</th><th>E-mail</th><th>Telefone</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($musicos as $m): ?>
                                            <?php $nomes = array_map(fn($i) => $i->getNome(), $m->getInstrumento() ?? []); ?>
                                            <tr>
                                                <td><input type="radio" name="sel_musico" value="<?= $m->getCodigoMusico() ?>"></td>
                                                <td><?= htmlspecialchars($m->getNomeMusico() . ' ' . $m->getApelidoMusico()) ?></td>
                                                <td><?= htmlspecialchars(implode(', ', $nomes)) ?></td>
                                                <td><?= htmlspecialchars($m->getEmailMusico()) ?></td>
                                                <td><?= htmlspecialchars($m->getContactoMusico()) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (!$musicos): ?>
                                            <tr><td colspan="5"><?= $q !== '' ? 'Nenhum músico encontrado.' : 'Nenhum músico cadastrado.' ?></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php acoes('musico', $podeRemover, $podeEditar, $podeAdicionar); ?>
                        </div>
                    </form>

                    <!-- Compositores -->
                    <form method="post" action="artistas.php" id="form-compositor">
                        <input type="hidden" name="tipo" value="compositor">
                        <div class="tab-content tab-content-compositores">
                            <div class="table-wrapper">
                                <table>
                                    <thead>
                                        <tr><th class="col-sel"></th><th>Nome Completo</th><th>E-mail</th><th>Telefone</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($compositores as $c): ?>
                                            <tr>
                                                <td><input type="radio" name="sel_compositor" value="<?= $c->getCodigoCompositor() ?>"></td>
                                                <td><?= htmlspecialchars($c->getNomeCompositor() . ' ' . $c->getApelidoCompositor()) ?></td>
                                                <td><?= htmlspecialchars($c->getEmailCompositor()) ?></td>
                                                <td><?= htmlspecialchars($c->getContactoCompositor()) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (!$compositores): ?>
                                            <tr><td colspan="4"><?= $q !== '' ? 'Nenhum compositor encontrado.' : 'Nenhum compositor cadastrado.' ?></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php acoes('compositor', $podeRemover, $podeEditar, $podeAdicionar); ?>
                        </div>
                    </form>

                    <!-- Cantores -->
                    <form method="post" action="artistas.php" id="form-cantor">
                        <input type="hidden" name="tipo" value="cantor">
                        <div class="tab-content tab-content-cantores">
                            <div class="table-wrapper">
                                <table>
                                    <thead>
                                        <tr><th class="col-sel"></th><th>Nome Completo</th><th>E-mail</th><th>Telefone</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cantores as $c): ?>
                                            <tr>
                                                <td><input type="radio" name="sel_cantor" value="<?= $c->getCodigoCantor() ?>"></td>
                                                <td><?= htmlspecialchars($c->getNomeCantor() . ' ' . $c->getApelidoCantor()) ?></td>
                                                <td><?= htmlspecialchars($c->getEmailCantor()) ?></td>
                                                <td><?= htmlspecialchars($c->getContactoCantor()) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (!$cantores): ?>
                                            <tr><td colspan="4"><?= $q !== '' ? 'Nenhum cantor encontrado.' : 'Nenhum cantor cadastrado.' ?></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php acoes('cantor', $podeRemover, $podeEditar, $podeAdicionar); ?>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <!-- MODAL: ADICIONAR ARTISTA -->
    <div class="modal-overlay modal-adicionar-artista">
        <div class="modal modal-sm">
            <div class="modal-header">
                <h2>Cadastrar Artista</h2>
                <label for="modal-adicionar-artista-toggle" class="modal-close">&times;</label>
            </div>
            <form method="post" action="artistas.php?tab=<?= $tab ?>">
                <input type="hidden" name="acao" value="cadastrar">
                <div class="modal-body">
                    <input type="radio" name="tipo" value="musico" id="add-tipo-musico" class="tipo-toggle" checked>
                    <input type="radio" name="tipo" value="compositor" id="add-tipo-compositor" class="tipo-toggle">
                    <input type="radio" name="tipo" value="cantor" id="add-tipo-cantor" class="tipo-toggle">

                    <div class="form-grid">
                        <div class="label-group">
                            <label for="add-nome">Nome</label>
                            <input type="text" id="add-nome" name="nome" required>
                        </div>
                        <div class="label-group">
                            <label for="add-apelido">Apelido</label>
                            <input type="text" id="add-apelido" name="apelido" required>
                        </div>

                        <div class="label-group form-grid-full">
                            <label>Tipo de Artista</label>
                            <div class="tipo-selector">
                                <label for="add-tipo-musico" class="tipo-btn">Músico</label>
                                <label for="add-tipo-compositor" class="tipo-btn">Compositor</label>
                                <label for="add-tipo-cantor" class="tipo-btn">Cantor</label>
                            </div>
                        </div>

                        <!-- Só aparece para Músico -->
                        <div class="instrumento-group">
                            <label>Instrumentos Musicais</label>
                            <div class="list-box">
                                <?php foreach ($instrumentos as $i): ?>
                                    <div class="list-item">
                                        <input type="checkbox" name="instrumentos[]" value="<?= $i->getCodigo() ?>" id="inst<?= $i->getCodigo() ?>">
                                        <label for="inst<?= $i->getCodigo() ?>"><?= htmlspecialchars($i->getNome()) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="label-group form-grid-full">
                            <label for="add-contacto">Contacto</label>
                            <input type="text" id="add-contacto" name="contacto">
                        </div>
                        <div class="label-group form-grid-full">
                            <label for="add-email">E-mail</label>
                            <input type="email" id="add-email" name="email">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <label for="modal-adicionar-artista-toggle" class="btn btn-secondary">Cancelar</label>
                    <button class="btn btn-primary" type="submit">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: EDITAR ARTISTA (abre pré-preenchido pelo botão "Editar") -->
    <div class="modal-overlay modal-editar-artista">
        <div class="modal modal-sm">
            <div class="modal-header">
                <h2>Editar <?= $edTitulo ?></h2>
                <label for="modal-editar-artista-toggle" class="modal-close">&times;</label>
            </div>
            <form method="post" action="artistas.php?tab=<?= $tab ?>">
                <input type="hidden" name="acao" value="atualizar">
                <input type="hidden" name="tipo" value="<?= $ed ? htmlspecialchars($edTipo) : 'musico' ?>">
                <input type="hidden" name="id" value="<?= $ed ? $edId : '' ?>">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="label-group">
                            <label for="ed-nome">Nome</label>
                            <input type="text" id="ed-nome" name="nome" required value="<?= htmlspecialchars($edNome) ?>">
                        </div>
                        <div class="label-group">
                            <label for="ed-apelido">Apelido</label>
                            <input type="text" id="ed-apelido" name="apelido" required value="<?= htmlspecialchars($edApelido) ?>">
                        </div>

                        <!-- Só para Músico (os outros tipos não têm instrumentos) -->
                        <?php if ($edTipo !== 'compositor' && $edTipo !== 'cantor'): ?>
                        <div class="instrumento-group sempre">
                            <label>Instrumentos Musicais</label>
                            <div class="list-box">
                                <?php foreach ($instrumentos as $i): ?>
                                    <div class="list-item">
                                        <input type="checkbox" name="instrumentos[]" value="<?= $i->getCodigo() ?>" id="edinst<?= $i->getCodigo() ?>"
                                            <?= in_array($i->getCodigo(), $edInstrumentos) ? 'checked' : '' ?>>
                                        <label for="edinst<?= $i->getCodigo() ?>"><?= htmlspecialchars($i->getNome()) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="label-group form-grid-full">
                            <label for="ed-contacto">Contacto</label>
                            <input type="text" id="ed-contacto" name="contacto" value="<?= htmlspecialchars($edContacto) ?>">
                        </div>
                        <div class="label-group form-grid-full">
                            <label for="ed-email">E-mail</label>
                            <input type="email" id="ed-email" name="email" value="<?= htmlspecialchars($edEmail) ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <label for="modal-editar-artista-toggle" class="btn btn-secondary">Cancelar</label>
                    <button class="btn btn-primary" type="submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach (['musico' => 'músico', 'compositor' => 'compositor', 'cantor' => 'cantor'] as $t => $nomeTipo): ?>
    <input type="checkbox" id="m-rem-<?= $t ?>" class="modal-toggle">
    <div class="modal-overlay"><div class="modal confirm">
        <div class="modal-header"><h2>Confirmar remoção</h2><label for="m-rem-<?= $t ?>" class="modal-close">&times;</label></div>
        <div class="modal-body">Tem certeza que deseja remover este <?= $nomeTipo ?>?</div>
        <div class="modal-footer">
            <label for="m-rem-<?= $t ?>" class="btn btn-secondary">Não</label>
            <button class="btn btn-primary" type="submit" form="form-<?= $t ?>" name="acao" value="remover">Sim</button>
        </div>
    </div></div>
    <?php endforeach; ?>

</body>
</html>