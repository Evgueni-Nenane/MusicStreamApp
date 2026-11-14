<?php
require_once __DIR__ . '/../../model/sessao.php';
require_once __DIR__ . '/../../model/permissao.php';
require_once __DIR__ . '/../../model/utilizador.php';
require_once __DIR__ . '/../../model/nivelacesso.php';
require_once __DIR__ . '/../../controller/artistasController.php';

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

function renderizarAcoes($tipo, $podeRemover, $podeEditar, $podeAdicionar)
{
    $html = '<div class="d-flex justify-content-end gap-2 border-top pt-3 mt-3">';

    if ($podeRemover) {
        $html .= '<button type="button" class="btn btn-outline-secondary btn-sm" data-modal-target="#m-rem-' . $tipo . '">Remover</button>';
    }

    if ($podeEditar) {
        $html .= '<button type="submit" name="acao" value="editar" formmethod="get" class="btn btn-outline-secondary btn-sm">Editar</button>';
    }

    if ($podeAdicionar) {
        $html .= '<button type="button" class="btn btn-primary btn-sm" data-modal-target="#modal-adicionar-artista">Adicionar</button>';
    }

    return $html . '</div>';
}

$artistasController = new ArtistasController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $artistasController->processarPost($utilizador, $_POST);
    $destino = 'artistas.php?tab=' . urlencode($resultado['aba']);

    if (!empty($resultado['msg'])) {
        $destino .= '&msg=' . urlencode($resultado['msg']);
    } elseif (!empty($resultado['erro'])) {
        $destino .= '&erro=' . urlencode($resultado['erro']);
    }

    header('Location: ' . $destino);
    exit;
}

$estado = $artistasController->prepararEcra($_GET);

$msg = $estado['msg'];
$erro = $estado['erro'];
$tab = $estado['tab'];
$artistaEdicao = $estado['artistaEdicao'];
$tipoArtistaEdicao = $estado['tipoArtistaEdicao'];
$instrumentosSelecionados = $estado['instrumentosSelecionados'];
$artistaEdicaoId = $estado['artistaEdicaoId'];
$artistaEdicaoNome = $estado['artistaEdicaoNome'];
$artistaEdicaoApelido = $estado['artistaEdicaoApelido'];
$artistaEdicaoContacto = $estado['artistaEdicaoContacto'];
$artistaEdicaoEmail = $estado['artistaEdicaoEmail'];
$artistaEdicaoTitulo = $estado['artistaEdicaoTitulo'];
$musicos = $estado['musicos'];
$cantores = $estado['cantores'];
$compositores = $estado['compositores'];
$instrumentos = $estado['instrumentos'];
$termoPesquisa = $estado['termoPesquisa'];
$autoModal = $artistaEdicao ? '#modal-editar-artista' : '';
$paginaAtiva = 'artistas';
$subtituloPagina = 'Gestão de artistas';
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Artistas | Sistema de Gestão</title>
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
                                        <a class="nav-link <?= $tab === 'musicos' ? 'active' : '' ?>" href="artistas.php?tab=musicos">Listar Músicos</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $tab === 'compositores' ? 'active' : '' ?>" href="artistas.php?tab=compositores">Listar Compositores</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $tab === 'cantores' ? 'active' : '' ?>" href="artistas.php?tab=cantores">Listar Cantores</a>
                                    </li>
                                </ul>

                                <form method="get" action="artistas.php" class="col-12 col-md-4">
                                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                    <input type="search" class="form-control" name="q"
                                        value="<?= htmlspecialchars($termoPesquisa) ?>" placeholder="Pesquisar artista...">
                                </form>
                            </div>

                            <?php if ($msg): ?>
                                <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
                            <?php endif; ?>
                            <?php if ($erro): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                            <?php endif; ?>

                            <?php if ($tab === 'musicos'): ?>
                                <form method="post" action="artistas.php" id="form-musico">
                                    <input type="hidden" name="tipo" value="musico">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead>
                                                <tr><th></th><th>Nome Completo</th><th>Instrumento</th><th>E-mail</th><th>Telefone</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($musicos as $musico): ?>
                                                    <?php
                                                    $nomes = array_map(fn($i) => $i->getNome(), $musico->getInstrumento() ?? []);
                                                    $idSelecao = 'sel-musico-' . (int) $musico->getCodigoMusico();
                                                    $nomeMusico = $musico->getNomeMusico() . ' ' . $musico->getApelidoMusico();
                                                    ?>
                                                    <tr>
                                                        <td><input type="radio" class="form-check-input" name="sel_musico" id="<?= $idSelecao ?>" value="<?= (int) $musico->getCodigoMusico() ?>" aria-label="Selecionar músico <?= htmlspecialchars($nomeMusico) ?>"></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($nomeMusico) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars(implode(', ', $nomes)) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($musico->getEmailMusico()) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($musico->getContactoMusico()) ?></label></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (!$musicos): ?>
                                                    <tr><td colspan="5" class="text-center text-muted"><?= $termoPesquisa !== '' ? 'Nenhum músico encontrado.' : 'Nenhum músico cadastrado.' ?></td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?= renderizarAcoes('musico', $podeRemover, $podeEditar, $podeAdicionar) ?>
                                </form>
                            <?php elseif ($tab === 'compositores'): ?>
                                <form method="post" action="artistas.php" id="form-compositor">
                                    <input type="hidden" name="tipo" value="compositor">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead>
                                                <tr><th></th><th>Nome Completo</th><th>E-mail</th><th>Telefone</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($compositores as $compositor): ?>
                                                    <?php
                                                    $idSelecao = 'sel-compositor-' . (int) $compositor->getCodigoCompositor();
                                                    $nomeCompositor = $compositor->getNomeCompositor() . ' ' . $compositor->getApelidoCompositor();
                                                    ?>
                                                    <tr>
                                                        <td><input type="radio" class="form-check-input" name="sel_compositor" id="<?= $idSelecao ?>" value="<?= (int) $compositor->getCodigoCompositor() ?>" aria-label="Selecionar compositor <?= htmlspecialchars($nomeCompositor) ?>"></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($nomeCompositor) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($compositor->getEmailCompositor()) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($compositor->getContactoCompositor()) ?></label></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (!$compositores): ?>
                                                    <tr><td colspan="4" class="text-center text-muted"><?= $termoPesquisa !== '' ? 'Nenhum compositor encontrado.' : 'Nenhum compositor cadastrado.' ?></td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?= renderizarAcoes('compositor', $podeRemover, $podeEditar, $podeAdicionar) ?>
                                </form>
                            <?php else: ?>
                                <form method="post" action="artistas.php" id="form-cantor">
                                    <input type="hidden" name="tipo" value="cantor">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead>
                                                <tr><th></th><th>Nome Completo</th><th>E-mail</th><th>Telefone</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($cantores as $cantor): ?>
                                                    <?php
                                                    $idSelecao = 'sel-cantor-' . (int) $cantor->getCodigoCantor();
                                                    $nomeCantor = $cantor->getNomeCantor() . ' ' . $cantor->getApelidoCantor();
                                                    ?>
                                                    <tr>
                                                        <td><input type="radio" class="form-check-input" name="sel_cantor" id="<?= $idSelecao ?>" value="<?= (int) $cantor->getCodigoCantor() ?>" aria-label="Selecionar cantor <?= htmlspecialchars($nomeCantor) ?>"></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($nomeCantor) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($cantor->getEmailCantor()) ?></label></td>
                                                        <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= htmlspecialchars($cantor->getContactoCantor()) ?></label></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (!$cantores): ?>
                                                    <tr><td colspan="4" class="text-center text-muted"><?= $termoPesquisa !== '' ? 'Nenhum cantor encontrado.' : 'Nenhum cantor cadastrado.' ?></td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?= renderizarAcoes('cantor', $podeRemover, $podeEditar, $podeAdicionar) ?>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-adicionar-artista" tabindex="-1" aria-labelledby="modal-adicionar-artista-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="post" action="artistas.php?tab=<?= $tab ?>">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="modal-adicionar-artista-label">Cadastrar Artista</h2>
                        <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="cadastrar">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="add-nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="add-nome" name="nome" maxlength="50" required>
                            </div>
                            <div class="col-md-6">
                                <label for="add-apelido" class="form-label">Apelido</label>
                                <input type="text" class="form-control" id="add-apelido" name="apelido" maxlength="50" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Tipo de Artista</label>
                                <div class="btn-group w-100" role="group" aria-label="Tipo de artista">
                                    <input type="radio" class="btn-check" name="tipo" value="musico" id="add-tipo-musico" checked>
                                    <label class="btn btn-outline-primary" for="add-tipo-musico">Músico</label>

                                    <input type="radio" class="btn-check" name="tipo" value="compositor" id="add-tipo-compositor">
                                    <label class="btn btn-outline-primary" for="add-tipo-compositor">Compositor</label>

                                    <input type="radio" class="btn-check" name="tipo" value="cantor" id="add-tipo-cantor">
                                    <label class="btn btn-outline-primary" for="add-tipo-cantor">Cantor</label>
                                </div>
                            </div>

                            <div class="col-12" id="add-instrumentos">
                                <label class="form-label">Instrumentos Musicais</label>
                                <div class="row row-cols-1 row-cols-md-2 g-2 border rounded p-3 bg-light">
                                    <?php foreach ($instrumentos as $instrumento): ?>
                                        <div class="col">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="instrumentos[]" value="<?= $instrumento->getCodigo() ?>" id="inst<?= $instrumento->getCodigo() ?>">
                                                <label class="form-check-label" for="inst<?= $instrumento->getCodigo() ?>"><?= htmlspecialchars($instrumento->getNome()) ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="add-contacto" class="form-label">Contacto</label>
                                <input type="text" class="form-control" id="add-contacto" name="contacto" maxlength="20">
                            </div>
                            <div class="col-12">
                                <label for="add-email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="add-email" name="email" maxlength="100">
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

    <div class="modal fade" id="modal-editar-artista" tabindex="-1" aria-labelledby="modal-editar-artista-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="post" action="artistas.php?tab=<?= $tab ?>">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="modal-editar-artista-label">Editar <?= $artistaEdicaoTitulo ?></h2>
                        <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="atualizar">
                        <input type="hidden" name="tipo" value="<?= $artistaEdicao ? htmlspecialchars($tipoArtistaEdicao) : 'musico' ?>">
                        <input type="hidden" name="id" value="<?= $artistaEdicao ? $artistaEdicaoId : '' ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="ed-nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="ed-nome" name="nome" maxlength="50" required value="<?= htmlspecialchars($artistaEdicaoNome) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="ed-apelido" class="form-label">Apelido</label>
                                <input type="text" class="form-control" id="ed-apelido" name="apelido" maxlength="50" required value="<?= htmlspecialchars($artistaEdicaoApelido) ?>">
                            </div>

                            <?php if ($tipoArtistaEdicao !== 'compositor' && $tipoArtistaEdicao !== 'cantor'): ?>
                                <div class="col-12">
                                    <label class="form-label">Instrumentos Musicais</label>
                                    <div class="row row-cols-1 row-cols-md-2 g-2 border rounded p-3 bg-light">
                                        <?php foreach ($instrumentos as $instrumento): ?>
                                            <div class="col">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" name="instrumentos[]" value="<?= $instrumento->getCodigo() ?>" id="edinst<?= $instrumento->getCodigo() ?>"
                                                        <?= in_array($instrumento->getCodigo(), $instrumentosSelecionados) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="edinst<?= $instrumento->getCodigo() ?>"><?= htmlspecialchars($instrumento->getNome()) ?></label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="col-12">
                                <label for="ed-contacto" class="form-label">Contacto</label>
                                <input type="text" class="form-control" id="ed-contacto" name="contacto" maxlength="20" value="<?= htmlspecialchars($artistaEdicaoContacto) ?>">
                            </div>
                            <div class="col-12">
                                <label for="ed-email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="ed-email" name="email" maxlength="100" value="<?= htmlspecialchars($artistaEdicaoEmail) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php foreach (['musico' => 'músico', 'compositor' => 'compositor', 'cantor' => 'cantor'] as $tipo => $nomeTipo): ?>
        <div class="modal fade" id="m-rem-<?= $tipo ?>" tabindex="-1" aria-labelledby="m-rem-<?= $tipo ?>-label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="m-rem-<?= $tipo ?>-label">Confirmar remoção</h2>
                        <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">Tem certeza que deseja remover este <?= $nomeTipo ?>?</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-modal-close>Não</button>
                        <button type="submit" class="btn btn-primary" form="form-<?= $tipo ?>" name="acao" value="remover">Sim</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script>
        const tiposArtista = document.querySelectorAll('#modal-adicionar-artista input[name="tipo"]');
        const grupoInstrumentos = document.getElementById('add-instrumentos');

        function atualizarInstrumentos() {
            const musical = document.getElementById('add-tipo-musico').checked;
            grupoInstrumentos.classList.toggle('d-none', !musical);
        }

        tiposArtista.forEach(function (tipo) {
            tipo.addEventListener('change', atualizarInstrumentos);
        });

        atualizarInstrumentos();
    </script>
</body>

</html>
