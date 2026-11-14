<?php
require_once __DIR__ . '/../../model/sessao.php';
require_once __DIR__ . '/../../model/permissao.php';
require_once __DIR__ . '/../../model/utilizador.php';
require_once __DIR__ . '/../../model/nivelacesso.php';
require_once __DIR__ . '/../../controller/listagemDiscosController.php';

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
$podeSelFaixa = $podeEditar || $podeRemover;

$listagemController = new ListagemDiscosController();

$resultadoPost = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultadoPost = $listagemController->processarPost($utilizador, $_POST);
}

$estado = $listagemController->prepararEcra($utilizador, $_GET, $_POST);
if ($resultadoPost !== null) {
    foreach (['msg', 'erro'] as $chave) {
        if (isset($resultadoPost[$chave])) {
            $estado[$chave] = $resultadoPost[$chave];
        }
    }

    if (array_key_exists('discoDetalhe', $resultadoPost)) {
        $estado['discoDetalhe'] = $resultadoPost['discoDetalhe'];
    }
}

$msg = $estado['msg'];
$erro = $estado['erro'];
$discoDetalhe = $estado['discoDetalhe'];
$discoEdicao = $estado['discoEdicao'];
$discoNovaFaixa = $estado['discoNovaFaixa'];
$faixaEdicao = $estado['faixaEdicao'];
$faixaDiscoId = $estado['faixaDiscoId'];
$faixaCompositoresSelecionados = $estado['faixaCompositoresSelecionados'];
$faixaMusicosSelecionados = $estado['faixaMusicosSelecionados'];
$faixaCantoresSelecionados = $estado['faixaCantoresSelecionados'];
$faixaMinutos = $estado['faixaMinutos'];
$faixaSegundos = $estado['faixaSegundos'];
$discos = $estado['discos'];
$todosGeneros = $estado['todosGeneros'];
$generosDoDisco = $estado['generosDoDisco'];
$detalheDiscoId = $estado['detalheDiscoId'];
$detalheGeneros = $estado['detalheGeneros'];
$detalheFaixas = $estado['detalheFaixas'];
$detalheCompositores = $estado['detalheCompositores'];
$detalheMusicos = $estado['detalheMusicos'];
$detalheCantores = $estado['detalheCantores'];
$discoCompositoresDisponiveis = $estado['discoCompositoresDisponiveis'];
$discoMusicosDisponiveis = $estado['discoMusicosDisponiveis'];
$discoCantoresDisponiveis = $estado['discoCantoresDisponiveis'];
$novaFaixaCompositores = $estado['novaFaixaCompositores'];
$novaFaixaMusicos = $estado['novaFaixaMusicos'];
$novaFaixaCantores = $estado['novaFaixaCantores'];
$termoPesquisa = $estado['termoPesquisa'];
$autoModal = '';
$autoModalAfter = '';

if ($discoEdicao) {
    $autoModal = '#modal-editar-disco';
} elseif ($discoNovaFaixa) {
    $autoModal = '#modal-adicionar-faixa';
} elseif ($faixaEdicao && $discoDetalhe) {
    $autoModal = '#modal-detalhes';
    $autoModalAfter = '#modal-editar-faixa';
} elseif ($faixaEdicao) {
    $autoModal = '#modal-editar-faixa';
} elseif ($discoDetalhe) {
    $autoModal = '#modal-detalhes';
}

function escaparHtml($valor)
{
    return htmlspecialchars((string) $valor);
}

function juntarNomes($pessoas, $metodoNome, $metodoApelido)
{
    $nomes = [];
    foreach ($pessoas as $pessoa) {
        $nomes[] = $pessoa->$metodoNome() . ' ' . $pessoa->$metodoApelido();
    }

    return implode(', ', $nomes);
}

function formatarDuracao($duracao)
{
    $duracao = (string) $duracao;

    return $duracao === '' ? '' : substr($duracao, 0, 5);
}
$paginaAtiva = 'listagemdiscos';
$subtituloPagina = 'Listagem de discos cadastrados';
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Listagem de Discos | Sistema de Gestão</title>
</head>

<body class="bg-light"<?= $autoModal !== '' ? ' data-auto-modal="' . htmlspecialchars($autoModal) . '"' : '' ?><?= $autoModalAfter !== '' ? ' data-auto-modal-after="' . htmlspecialchars($autoModalAfter) . '"' : '' ?>>
    <div class="container-fluid">
        <div class="row min-vh-100">
            <?php require __DIR__ . '/../partials/sidebar.php'; ?>

            <div class="col-12 col-md-9 col-lg-10 p-4">
                <?php require __DIR__ . '/../partials/header.php'; ?>

                <main>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                                <h3 class="h5 mb-0">Discos Cadastrados no Sistema</h3>
                                <form method="get" action="listagemdiscos.php" class="col-12 col-md-5">
                                    <input type="search" class="form-control" name="q"
                                        value="<?= escaparHtml($termoPesquisa) ?>" placeholder="Pesquisar disco, género, ano..."
                                        aria-label="Pesquisar discos">
                                </form>
                            </div>

                            <?php if (!$discoDetalhe && $msg): ?>
                                <div class="alert alert-success"><?= escaparHtml($msg) ?></div>
                            <?php endif; ?>
                            <?php if (!$discoDetalhe && $erro): ?>
                                <div class="alert alert-danger"><?= escaparHtml($erro) ?></div>
                            <?php endif; ?>

                            <form method="post" action="listagemdiscos.php" id="form-discos">
                                <input type="hidden" name="q" value="<?= escaparHtml($termoPesquisa) ?>">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Título</th>
                                                <th>Género</th>
                                                <th>Preço</th>
                                                <th>Ano Edição</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($discos as $disco): ?>
                                                <?php
                                                $idSelecao = 'sel-disco-' . (int) $disco->getCodigoDisco();
                                                $tituloDisco = $disco->getTitulo();
                                                ?>
                                                <tr>
                                                    <td><input type="radio" class="form-check-input" name="sel" id="<?= $idSelecao ?>" value="<?= (int) $disco->getCodigoDisco() ?>" aria-label="Selecionar disco <?= escaparHtml($tituloDisco) ?>"></td>
                                                    <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= escaparHtml($tituloDisco) ?></label></td>
                                                    <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= escaparHtml($disco->getGeneroMusicalTxt()) ?></label></td>
                                                    <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= number_format($disco->getPreco(), 2, ',', '.') ?></label></td>
                                                    <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= escaparHtml($disco->getAnoEdicao()) ?></label></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (!$discos): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">
                                                        <?= $termoPesquisa !== ''
                                                            ? 'Nenhum disco encontrado para "' . escaparHtml($termoPesquisa) . '".'
                                                            : 'Ainda não há discos cadastrados.' ?>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-3">
                                    <?php if ($podeRemover): ?>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-modal-target="#modal-remover-disco">Remover</button>
                                    <?php endif; ?>
                                    <?php if ($podeAdicionar): ?>
                                        <button class="btn btn-outline-secondary btn-sm" type="submit" name="acao" value="faixas" formmethod="get">Adicionar Faixas</button>
                                    <?php endif; ?>
                                    <?php if ($podeEditar): ?>
                                        <button class="btn btn-primary btn-sm" type="submit" name="acao" value="editar" formmethod="get">Editar</button>
                                    <?php endif; ?>
                                    <button class="btn btn-primary btn-sm" type="submit" name="acao" value="detalhes" formmethod="get">Ver Detalhes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-detalhes" tabindex="-1" aria-labelledby="modal-detalhes-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <form method="post" action="listagemdiscos.php" id="form-detalhes">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="modal-detalhes-label">Detalhes do Disco</h2>
                        <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $discoDetalhe ? $detalheDiscoId : '' ?>">
                        <input type="hidden" name="q" value="<?= escaparHtml($termoPesquisa) ?>">

                        <?php if ($msg): ?>
                            <div class="alert alert-success"><?= escaparHtml($msg) ?></div>
                        <?php endif; ?>
                        <?php if ($erro): ?>
                            <div class="alert alert-danger"><?= escaparHtml($erro) ?></div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <h4 class="h6 text-uppercase text-muted">Informações do Disco</h4>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Título do Disco</span>
                                    <span><?= $discoDetalhe ? escaparHtml($discoDetalhe->getTitulo()) : '' ?></span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Género Musical</span>
                                    <span><?= $discoDetalhe ? escaparHtml($detalheGeneros) : '' ?></span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Preço</span>
                                    <span><?= $discoDetalhe ? number_format($discoDetalhe->getPreco(), 2, ',', '.') : '' ?></span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Tempo de Existência</span>
                                    <span><?= $discoDetalhe ? (date('Y') - (int) $discoDetalhe->getAnoEdicao()) . ' ano(s)' : '' ?></span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Ano de Edição</span>
                                    <span><?= $discoDetalhe ? escaparHtml($discoDetalhe->getAnoEdicao()) : '' ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h4 class="h6 text-uppercase text-muted">Participantes do Disco</h4>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Compositores</span>
                                    <span><?= $discoDetalhe ? escaparHtml(juntarNomes($detalheCompositores, 'getNomeCompositor', 'getApelidoCompositor')) : '' ?></span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Músicos</span>
                                    <span><?= $discoDetalhe ? escaparHtml(juntarNomes($detalheMusicos, 'getNomeMusico', 'getApelidoMusico')) : '' ?></span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Cantores</span>
                                    <span><?= $discoDetalhe ? escaparHtml(juntarNomes($detalheCantores, 'getNomeCantor', 'getApelidoCantor')) : '' ?></span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="h6 text-uppercase text-muted">Faixas do Disco</h4>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Nº</th>
                                            <th>Nome da Faixa</th>
                                            <th>Artista Principal</th>
                                            <th>Duração</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($discoDetalhe): foreach ($detalheFaixas as $faixa): ?>
                                                <?php
                                                $idSelecao = 'sel-faixa-' . (int) $faixa->getIdFaixa();
                                                $nomeFaixa = $faixa->getNomeFaixa();
                                                ?>
                                                <tr>
                                                    <td><input type="radio" class="form-check-input" name="sel_faixa" id="<?= $idSelecao ?>" value="<?= (int) $faixa->getIdFaixa() ?>" <?= $podeSelFaixa ? '' : 'disabled' ?> aria-label="Selecionar faixa <?= escaparHtml($nomeFaixa) ?>"></td>
                                                    <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= escaparHtml($faixa->getNumeroFaixa()) ?></label></td>
                                                    <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= escaparHtml($nomeFaixa) ?></label></td>
                                                    <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= escaparHtml($faixa->getArtistaPrincipal()) ?></label></td>
                                                    <td><label class="form-check-label" for="<?= $idSelecao ?>"><?= escaparHtml(formatarDuracao($faixa->getDuracao())) ?></label></td>
                                                </tr>
                                        <?php endforeach;
                                        endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-modal-close>Fechar</button>
                        <?php if ($podeEditar): ?>
                            <button class="btn btn-secondary" type="submit" name="acao" value="editarfaixa" formmethod="get">Editar Faixa</button>
                        <?php endif; ?>
                        <?php if ($podeRemover): ?>
                            <button type="button" class="btn btn-primary" data-modal-target="#modal-remover-faixa">Remover Faixa</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-editar-disco" tabindex="-1" aria-labelledby="modal-editar-disco-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="listagemdiscos.php">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="modal-editar-disco-label">Editar Disco</h2>
                        <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="atualizar">
                        <input type="hidden" name="id" value="<?= $discoEdicao ? $discoEdicao->getCodigoDisco() : '' ?>">
                        <input type="hidden" name="q" value="<?= escaparHtml($termoPesquisa) ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="edit-titulo" class="form-label">Título</label>
                                <input type="text" class="form-control" id="edit-titulo" name="titulo" maxlength="150" required value="<?= escaparHtml($discoEdicao ? $discoEdicao->getTitulo() : '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="edit-preco" class="form-label">Preço</label>
                                <input type="number" class="form-control" id="edit-preco" name="preco" min="0" max="99999999.99" step="0.01" value="<?= $discoEdicao ? $discoEdicao->getPreco() : '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="edit-ano" class="form-label">Data de Edição</label>
                                <input type="date" class="form-control" id="edit-ano" name="ano_edicao" required value="<?= $discoEdicao ? $discoEdicao->getAnoEdicao() . '-01-01' : '' ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Géneros</label>
                                <div class="row row-cols-1 row-cols-md-2 g-2 border rounded p-3 bg-light">
                                    <?php foreach ($todosGeneros as $genero): ?>
                                        <div class="col">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="generos[]" id="eg<?= $genero->getCodigoGenero() ?>" value="<?= $genero->getCodigoGenero() ?>"
                                                    <?= in_array($genero->getCodigoGenero(), $generosDoDisco) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="eg<?= $genero->getCodigoGenero() ?>"><?= escaparHtml($genero->getNomeGenero()) ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-editar-faixa" tabindex="-1" aria-labelledby="modal-editar-faixa-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <form method="post" action="listagemdiscos.php">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="modal-editar-faixa-label">Editar Faixa</h2>
                        <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="atualizarfaixa">
                        <input type="hidden" name="id" value="<?= $faixaEdicao ? $faixaDiscoId : '' ?>">
                        <input type="hidden" name="id_faixa" value="<?= $faixaEdicao ? $faixaEdicao->getIdFaixa() : '' ?>">
                        <input type="hidden" name="num" value="<?= $faixaEdicao ? $faixaEdicao->getNumeroFaixa() : '' ?>">
                        <input type="hidden" name="q" value="<?= escaparHtml($termoPesquisa) ?>">

                        <div class="mb-4">
                            <h4 class="h6 text-uppercase text-muted">Informações da Faixa</h4>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="edfaixa-nome" class="form-label">Nome da Faixa</label>
                                    <input type="text" class="form-control" id="edfaixa-nome" name="nome" maxlength="150" required value="<?= $faixaEdicao ? escaparHtml($faixaEdicao->getNomeFaixa()) : '' ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="edfaixa-artista" class="form-label">Artista Principal</label>
                                    <input type="text" class="form-control" id="edfaixa-artista" name="artista" maxlength="100" required value="<?= $faixaEdicao ? escaparHtml($faixaEdicao->getArtistaPrincipal()) : '' ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Duração (mm:ss)</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="number" class="form-control" name="min" min="0" max="99" value="<?= $faixaMinutos ?>">
                                        <span>:</span>
                                        <input type="number" class="form-control" name="seg" min="0" max="59" value="<?= $faixaSegundos ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="h6 text-uppercase text-muted">Participantes do disco</h4>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <h4 class="h6">Compositores</h4>
                                    <div class="border rounded p-3 bg-light">
                                        <?php if ($faixaDiscoId): foreach ($discoCompositoresDisponiveis as $compositor): ?>
                                            <div class="form-check mb-2">
                                                <input type="checkbox" class="form-check-input" name="compositores[]" id="efc<?= $compositor->getCodigoCompositor() ?>" value="<?= $compositor->getCodigoCompositor() ?>" <?= in_array($compositor->getCodigoCompositor(), $faixaCompositoresSelecionados) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="efc<?= $compositor->getCodigoCompositor() ?>"><?= escaparHtml($compositor->getNomeCompositor() . ' ' . $compositor->getApelidoCompositor()) ?></label>
                                            </div>
                                        <?php endforeach;
                                        endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <h4 class="h6">Músicos</h4>
                                    <div class="border rounded p-3 bg-light">
                                        <?php if ($faixaDiscoId): foreach ($discoMusicosDisponiveis as $musico): ?>
                                            <div class="form-check mb-2">
                                                <input type="checkbox" class="form-check-input" name="musicos[]" id="efm<?= $musico->getCodigoMusico() ?>" value="<?= $musico->getCodigoMusico() ?>" <?= in_array($musico->getCodigoMusico(), $faixaMusicosSelecionados) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="efm<?= $musico->getCodigoMusico() ?>"><?= escaparHtml($musico->getNomeMusico() . ' ' . $musico->getApelidoMusico()) ?></label>
                                            </div>
                                        <?php endforeach;
                                        endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <h4 class="h6">Cantores</h4>
                                    <div class="border rounded p-3 bg-light">
                                        <?php if ($faixaDiscoId): foreach ($discoCantoresDisponiveis as $cantor): ?>
                                            <div class="form-check mb-2">
                                                <input type="checkbox" class="form-check-input" name="cantores[]" id="efv<?= $cantor->getCodigoCantor() ?>" value="<?= $cantor->getCodigoCantor() ?>" <?= in_array($cantor->getCodigoCantor(), $faixaCantoresSelecionados) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="efv<?= $cantor->getCodigoCantor() ?>"><?= escaparHtml($cantor->getNomeCantor() . ' ' . $cantor->getApelidoCantor()) ?></label>
                                            </div>
                                        <?php endforeach;
                                        endif; ?>
                                    </div>
                                </div>
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

    <div class="modal fade" id="modal-adicionar-faixa" tabindex="-1" aria-labelledby="modal-adicionar-faixa-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <form method="post" action="listagemdiscos.php">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="modal-adicionar-faixa-label">Nova Faixa</h2>
                        <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="addfaixa">
                        <input type="hidden" name="id" value="<?= $discoNovaFaixa ? $discoNovaFaixa->getCodigoDisco() : '' ?>">
                        <input type="hidden" name="q" value="<?= escaparHtml($termoPesquisa) ?>">

                        <div class="mb-4">
                            <h4 class="h6 text-uppercase text-muted">Informações da Faixa<?= $discoNovaFaixa ? ' — ' . escaparHtml($discoNovaFaixa->getTitulo()) : '' ?></h4>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="faixa-nome" class="form-label">Nome da Faixa</label>
                                    <input type="text" class="form-control" id="faixa-nome" name="nome" maxlength="150" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="faixa-artista" class="form-label">Artista Principal</label>
                                    <input type="text" class="form-control" id="faixa-artista" name="artista" maxlength="100" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Duração (mm:ss)</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="number" class="form-control" name="min" min="0" max="99" value="0">
                                        <span>:</span>
                                        <input type="number" class="form-control" name="seg" min="0" max="59" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="h6 text-uppercase text-muted">Selecione entre os participantes já escolhidos no disco</h4>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <h4 class="h6">Compositores</h4>
                                    <div class="border rounded p-3 bg-light">
                                        <?php if ($discoNovaFaixa): foreach ($novaFaixaCompositores as $compositor): ?>
                                            <div class="form-check mb-2">
                                                <input type="checkbox" class="form-check-input" name="compositores[]" id="fc<?= $compositor->getCodigoCompositor() ?>" value="<?= $compositor->getCodigoCompositor() ?>">
                                                <label class="form-check-label" for="fc<?= $compositor->getCodigoCompositor() ?>"><?= escaparHtml($compositor->getNomeCompositor() . ' ' . $compositor->getApelidoCompositor()) ?></label>
                                            </div>
                                        <?php endforeach;
                                        endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <h4 class="h6">Músicos</h4>
                                    <div class="border rounded p-3 bg-light">
                                        <?php if ($discoNovaFaixa): foreach ($novaFaixaMusicos as $musico): ?>
                                            <div class="form-check mb-2">
                                                <input type="checkbox" class="form-check-input" name="musicos[]" id="fm<?= $musico->getCodigoMusico() ?>" value="<?= $musico->getCodigoMusico() ?>">
                                                <label class="form-check-label" for="fm<?= $musico->getCodigoMusico() ?>"><?= escaparHtml($musico->getNomeMusico() . ' ' . $musico->getApelidoMusico()) ?></label>
                                            </div>
                                        <?php endforeach;
                                        endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <h4 class="h6">Cantores</h4>
                                    <div class="border rounded p-3 bg-light">
                                        <?php if ($discoNovaFaixa): foreach ($novaFaixaCantores as $cantor): ?>
                                            <div class="form-check mb-2">
                                                <input type="checkbox" class="form-check-input" name="cantores[]" id="fv<?= $cantor->getCodigoCantor() ?>" value="<?= $cantor->getCodigoCantor() ?>">
                                                <label class="form-check-label" for="fv<?= $cantor->getCodigoCantor() ?>"><?= escaparHtml($cantor->getNomeCantor() . ' ' . $cantor->getApelidoCantor()) ?></label>
                                            </div>
                                        <?php endforeach;
                                        endif; ?>
                                    </div>
                                </div>
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

    <div class="modal fade" id="modal-remover-disco" tabindex="-1" aria-labelledby="modal-remover-disco-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="modal-remover-disco-label">Confirmar remoção</h2>
                    <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                </div>
                <div class="modal-body">Tem certeza que deseja remover o disco seleccionado e as suas faixas?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal-close>Não</button>
                    <button type="submit" class="btn btn-primary" form="form-discos" name="acao" value="remover">Sim</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-remover-faixa" tabindex="-1" aria-labelledby="modal-remover-faixa-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="modal-remover-faixa-label">Confirmar remoção</h2>
                    <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                </div>
                <div class="modal-body">Tem certeza que deseja remover a faixa seleccionada?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal-close>Não</button>
                    <button type="submit" class="btn btn-primary" form="form-detalhes" name="acao" value="removerfaixa">Sim</button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
