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

/* ---------- Acções (POST) ---------- */
$resultadoPost = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultadoPost = $listagemController->processarPost($utilizador, $_POST);
}

/* ---------- Estado da página ---------- */
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

/* ---------- Helpers de apresentação ---------- */
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
    // O MySQL devolve TIME como HH:MM:SS; a interface mostra HH:MM.
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
    <title>Listagem de Discos | Sistema de Gestão</title>
    <link rel="stylesheet" href="../css/listagemdiscos.css">
    <link rel="stylesheet" href="../css/registro.css">
</head>

<body>

    <input type="checkbox" id="modal-detalhes-toggle" class="modal-toggle" <?= $discoDetalhe ? 'checked' : '' ?>>
    <input type="checkbox" id="modal-editar-disco-toggle" class="modal-toggle" <?= $discoEdicao ? 'checked' : '' ?>>
    <input type="checkbox" id="modal-editar-faixa-toggle" class="modal-toggle" <?= $faixaEdicao ? 'checked' : '' ?>>
    <input type="checkbox" id="modal-adicionar-faixa-toggle" class="modal-toggle" <?= $discoNovaFaixa ? 'checked' : '' ?>>
    <input type="checkbox" id="modal-remover-disco-toggle" class="modal-toggle">
    <input type="checkbox" id="modal-remover-faixa-toggle" class="modal-toggle">

    <div class="app-container">
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>

        <div class="containermain">
            <?php require __DIR__ . '/../partials/header.php'; ?>

            <main>
                <div class="card">
                    <!-- Formulário GET da pesquisa (o input liga-se a ele via form="form-pesq"). -->
                    <form method="get" action="listagemdiscos.php" id="form-pesq"></form>

                    <div class="card-header com-pesquisa">
                        <h3>Discos Cadastrados no Sistema</h3>
                        <input type="search" class="search-input" name="q" form="form-pesq"
                            value="<?= escaparHtml($termoPesquisa) ?>" placeholder="Pesquisar disco, género, ano..."
                            aria-label="Pesquisar discos">
                    </div>

                    <?php if (!$discoDetalhe && $msg): ?>
                        <div class="alert alert-ok"><?= escaparHtml($msg) ?></div>
                    <?php endif; ?>
                    <?php if (!$discoDetalhe && $erro): ?>
                        <div class="alert alert-erro"><?= escaparHtml($erro) ?></div>
                    <?php endif; ?>

                    <form method="post" action="listagemdiscos.php" id="form-discos">
                        <!-- Mantém a pesquisa activa depois de Editar / Remover / Ver Detalhes -->
                        <input type="hidden" name="q" value="<?= escaparHtml($termoPesquisa) ?>">
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="col-sel"></th>
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
                                            <td><input type="radio" name="sel" id="<?= $idSelecao ?>" value="<?= (int) $disco->getCodigoDisco() ?>" aria-label="Selecionar disco <?= escaparHtml($tituloDisco) ?>"></td>
                                            <td><label for="<?= $idSelecao ?>"><?= escaparHtml($tituloDisco) ?></label></td>
                                            <td><label for="<?= $idSelecao ?>"><?= escaparHtml($disco->getGeneroMusicalTxt()) ?></label></td>
                                            <td><label for="<?= $idSelecao ?>"><?= number_format($disco->getPreco(), 2, ',', '.') ?></label></td>
                                            <td><label for="<?= $idSelecao ?>"><?= escaparHtml($disco->getAnoEdicao()) ?></label></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (!$discos): ?>
                                        <tr>
                                            <td colspan="5">
                                                <?= $termoPesquisa !== ''
                                                    ? 'Nenhum disco encontrado para "' . escaparHtml($termoPesquisa) . '".'
                                                    : 'Ainda não há discos cadastrados.' ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="table-actions">
                            <?php if ($podeRemover): ?>
                            <label for="modal-remover-disco-toggle" class="btn btn-secondary btn-sm">Remover</label>
                            <?php endif; ?>
                            <?php if ($podeAdicionar): ?>
                            <button class="btn btn-secondary btn-sm" type="submit" name="acao" value="faixas" formmethod="get">Adicionar Faixas</button>
                            <?php endif; ?>
                            <?php if ($podeEditar): ?>
                            <button class="btn btn-primary btn-sm" type="submit" name="acao" value="editar" formmethod="get">Editar</button>
                            <?php endif; ?>
                            <button class="btn btn-primary btn-sm" type="submit" name="acao" value="detalhes" formmethod="get">Ver Detalhes</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <!-- ============ MODAL: DETALHES DO DISCO ============ -->
    <div class="modal-overlay modal-detalhes">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h2>Detalhes do Disco</h2>
                <label for="modal-detalhes-toggle" class="modal-close">&times;</label>
            </div>
            <form method="post" action="listagemdiscos.php" id="form-detalhes">
                <input type="hidden" name="id" value="<?= $discoDetalhe ? $detalheDiscoId : '' ?>">
                <input type="hidden" name="q" value="<?= escaparHtml($termoPesquisa) ?>">
                <div class="modal-body">
                    <?php if ($msg): ?>
                        <div class="alert alert-ok"><?= escaparHtml($msg) ?></div>
                    <?php endif; ?>
                    <?php if ($erro): ?>
                        <div class="alert alert-erro"><?= escaparHtml($erro) ?></div>
                    <?php endif; ?>

                    <div class="detail-section">
                        <h4>Informações do Disco</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="label">Título do Disco</span>
                                <span class="value"><?= $discoDetalhe ? escaparHtml($discoDetalhe->getTitulo()) : '' ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Género Musical</span>
                                <span class="value"><?= $discoDetalhe ? escaparHtml($detalheGeneros) : '' ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Preço</span>
                                <span class="value"><?= $discoDetalhe ? number_format($discoDetalhe->getPreco(), 2, ',', '.') : '' ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Tempo de Existência</span>
                                <span class="value"><?= $discoDetalhe ? (date('Y') - (int) $discoDetalhe->getAnoEdicao()) . ' ano(s)' : '' ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Ano de Edição</span>
                                <span class="value"><?= $discoDetalhe ? escaparHtml($discoDetalhe->getAnoEdicao()) : '' ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h4>Participantes do Disco</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="label">Compositores</span>
                                <span class="value"><?= $discoDetalhe ? escaparHtml(juntarNomes($detalheCompositores, 'getNomeCompositor', 'getApelidoCompositor')) : '' ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Músicos</span>
                                <span class="value"><?= $discoDetalhe ? escaparHtml(juntarNomes($detalheMusicos, 'getNomeMusico', 'getApelidoMusico')) : '' ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Cantores</span>
                                <span class="value"><?= $discoDetalhe ? escaparHtml(juntarNomes($detalheCantores, 'getNomeCantor', 'getApelidoCantor')) : '' ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h4>Faixas do Disco</h4>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="col-sel"></th>
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
                                                <td><input type="radio" name="sel_faixa" id="<?= $idSelecao ?>" value="<?= (int) $faixa->getIdFaixa() ?>" <?= $podeSelFaixa ? '' : 'disabled' ?> aria-label="Selecionar faixa <?= escaparHtml($nomeFaixa) ?>"></td>
                                                <td><label for="<?= $idSelecao ?>"><?= escaparHtml($faixa->getNumeroFaixa()) ?></label></td>
                                                <td><label for="<?= $idSelecao ?>"><?= escaparHtml($nomeFaixa) ?></label></td>
                                                <td><label for="<?= $idSelecao ?>"><?= escaparHtml($faixa->getArtistaPrincipal()) ?></label></td>
                                                <td><label for="<?= $idSelecao ?>"><?= escaparHtml(formatarDuracao($faixa->getDuracao())) ?></label></td>
                                            </tr>
                                    <?php endforeach;
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <label for="modal-detalhes-toggle" class="btn btn-secondary">Fechar</label>
                    <?php if ($podeEditar): ?>
                    <button class="btn btn-secondary" type="submit" name="acao" value="editarfaixa" formmethod="get">Editar Faixa</button>
                    <?php endif; ?>
                    <?php if ($podeRemover): ?>
                    <label for="modal-remover-faixa-toggle" class="btn btn-primary">Remover Faixa</label>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- ============ MODAL: EDITAR DISCO ============ -->
    <div class="modal-overlay modal-editar-disco">
        <div class="modal">
            <div class="modal-header">
                <h2>Editar Disco</h2>
                <label for="modal-editar-disco-toggle" class="modal-close">&times;</label>
            </div>
            <form method="post" action="listagemdiscos.php">
                <input type="hidden" name="acao" value="atualizar">
                <input type="hidden" name="id" value="<?= $discoEdicao ? $discoEdicao->getCodigoDisco() : '' ?>">
                <input type="hidden" name="q" value="<?= escaparHtml($termoPesquisa) ?>">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="label-group form-grid-full">
                            <label for="edit-titulo">Título</label>
                            <input type="text" id="edit-titulo" name="titulo" maxlength="150" required value="<?= escaparHtml($discoEdicao ? $discoEdicao->getTitulo() : '') ?>">
                        </div>
                        <div class="label-group">
                            <label for="edit-preco">Preço</label>
                            <input type="number" id="edit-preco" name="preco" min="0" max="99999999.99" step="0.01" value="<?= $discoEdicao ? $discoEdicao->getPreco() : '' ?>">
                        </div>
                        <div class="label-group">
                            <label for="edit-ano">Data de Edição</label>
                            <input type="date" id="edit-ano" name="ano_edicao" required value="<?= $discoEdicao ? $discoEdicao->getAnoEdicao() . '-01-01' : '' ?>">
                        </div>
                        <div class="label-group form-grid-full">
                            <label>Géneros</label>
                            <div class="list-box list-box-tall">
                                <?php foreach ($todosGeneros as $genero): ?>
                                    <div class="list-item">
                                        <input type="checkbox" name="generos[]" id="eg<?= $genero->getCodigoGenero() ?>" value="<?= $genero->getCodigoGenero() ?>"
                                            <?= in_array($genero->getCodigoGenero(), $generosDoDisco) ? 'checked' : '' ?>>
                                        <label for="eg<?= $genero->getCodigoGenero() ?>"><?= escaparHtml($genero->getNomeGenero()) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <label for="modal-editar-disco-toggle" class="btn btn-secondary">Cancelar</label>
                    <button class="btn btn-primary" type="submit">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============ MODAL: EDITAR FAIXA ============ -->
    <div class="modal-overlay modal-editar-faixa">
        <div class="modal">
            <div class="modal-header">
                <h2>Editar Faixa</h2>
                <label for="modal-editar-faixa-toggle" class="modal-close">&times;</label>
            </div>
            <form method="post" action="listagemdiscos.php">
                <input type="hidden" name="acao" value="atualizarfaixa">
                <input type="hidden" name="id" value="<?= $faixaEdicao ? $faixaDiscoId : '' ?>">
                <input type="hidden" name="id_faixa" value="<?= $faixaEdicao ? $faixaEdicao->getIdFaixa() : '' ?>">
                <input type="hidden" name="num" value="<?= $faixaEdicao ? $faixaEdicao->getNumeroFaixa() : '' ?>">
                <input type="hidden" name="q" value="<?= escaparHtml($termoPesquisa) ?>">
                <div class="modal-body">
                    <div class="detail-section">
                        <h4>Informações da Faixa</h4>
                        <div class="form-grid">
                            <div class="label-group">
                                <label for="edfaixa-nome">Nome da Faixa</label>
                                <input type="text" id="edfaixa-nome" name="nome" maxlength="150" required value="<?= $faixaEdicao ? escaparHtml($faixaEdicao->getNomeFaixa()) : '' ?>">
                            </div>
                            <div class="label-group">
                                <label for="edfaixa-artista">Artista Principal</label>
                                <input type="text" id="edfaixa-artista" name="artista" maxlength="100" required value="<?= $faixaEdicao ? escaparHtml($faixaEdicao->getArtistaPrincipal()) : '' ?>">
                            </div>
                            <div class="label-group">
                                <label>Duração (mm:ss)</label>
                                <div class="duration-fields">
                                    <input type="number" name="min" min="0" max="99" value="<?= $faixaMinutos ?>" class="duration-input">
                                    <span>:</span>
                                    <input type="number" name="seg" min="0" max="59" value="<?= $faixaSegundos ?>" class="duration-input">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="detail-section">
                        <h4>Participantes do disco</h4>
                        <div class="participant-grid">
                            <div class="participant-col">
                                <h4>Compositores</h4>
                                <div class="list-box list-box-short">
                                    <?php if ($faixaDiscoId): foreach ($discoCompositoresDisponiveis as $compositor): ?>
                                        <div class="list-item">
                                            <input type="checkbox" name="compositores[]" id="efc<?= $compositor->getCodigoCompositor() ?>" value="<?= $compositor->getCodigoCompositor() ?>" <?= in_array($compositor->getCodigoCompositor(), $faixaCompositoresSelecionados) ? 'checked' : '' ?>>
                                            <label for="efc<?= $compositor->getCodigoCompositor() ?>"><?= escaparHtml($compositor->getNomeCompositor() . ' ' . $compositor->getApelidoCompositor()) ?></label>
                                        </div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                            <div class="participant-col">
                                <h4>Músicos</h4>
                                <div class="list-box list-box-short">
                                    <?php if ($faixaDiscoId): foreach ($discoMusicosDisponiveis as $musico): ?>
                                        <div class="list-item">
                                            <input type="checkbox" name="musicos[]" id="efm<?= $musico->getCodigoMusico() ?>" value="<?= $musico->getCodigoMusico() ?>" <?= in_array($musico->getCodigoMusico(), $faixaMusicosSelecionados) ? 'checked' : '' ?>>
                                            <label for="efm<?= $musico->getCodigoMusico() ?>"><?= escaparHtml($musico->getNomeMusico() . ' ' . $musico->getApelidoMusico()) ?></label>
                                        </div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                            <div class="participant-col">
                                <h4>Cantores</h4>
                                <div class="list-box list-box-short">
                                    <?php if ($faixaDiscoId): foreach ($discoCantoresDisponiveis as $cantor): ?>
                                        <div class="list-item">
                                            <input type="checkbox" name="cantores[]" id="efv<?= $cantor->getCodigoCantor() ?>" value="<?= $cantor->getCodigoCantor() ?>" <?= in_array($cantor->getCodigoCantor(), $faixaCantoresSelecionados) ? 'checked' : '' ?>>
                                            <label for="efv<?= $cantor->getCodigoCantor() ?>"><?= escaparHtml($cantor->getNomeCantor() . ' ' . $cantor->getApelidoCantor()) ?></label>
                                        </div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <label for="modal-editar-faixa-toggle" class="btn btn-secondary">Cancelar</label>
                    <button class="btn btn-primary" type="submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============ MODAL: ADICIONAR FAIXA ============ -->
    <div class="modal-overlay modal-adicionar-faixa">
        <div class="modal">
            <div class="modal-header">
                <h2>Nova Faixa</h2>
                <label for="modal-adicionar-faixa-toggle" class="modal-close">&times;</label>
            </div>
            <form method="post" action="listagemdiscos.php">
                <input type="hidden" name="acao" value="addfaixa">
                <input type="hidden" name="id" value="<?= $discoNovaFaixa ? $discoNovaFaixa->getCodigoDisco() : '' ?>">
                <input type="hidden" name="q" value="<?= escaparHtml($termoPesquisa) ?>">
                <div class="modal-body">
                    <div class="detail-section">
                        <h4>Informações da Faixa<?= $discoNovaFaixa ? ' — ' . escaparHtml($discoNovaFaixa->getTitulo()) : '' ?></h4>
                        <div class="form-grid">
                            <div class="label-group">
                                <label for="faixa-nome">Nome da Faixa</label>
                                <input type="text" id="faixa-nome" name="nome" maxlength="150" required>
                            </div>
                            <div class="label-group">
                                <label for="faixa-artista">Artista Principal</label>
                                <input type="text" id="faixa-artista" name="artista" maxlength="100" required>
                            </div>
                            <div class="label-group">
                                <label>Duração (mm:ss)</label>
                                <div class="duration-fields">
                                    <input type="number" name="min" min="0" max="99" value="0" class="duration-input">
                                    <span>:</span>
                                    <input type="number" name="seg" min="0" max="59" value="0" class="duration-input">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="detail-section">
                        <h4>Selecione entre os participantes já escolhidos no disco</h4>
                        <div class="participant-grid">
                            <div class="participant-col">
                                <h4>Compositores</h4>
                                <div class="list-box list-box-short">
                                    <?php if ($discoNovaFaixa): foreach ($novaFaixaCompositores as $compositor): ?>
                                            <div class="list-item">
                                                <input type="checkbox" name="compositores[]" id="fc<?= $compositor->getCodigoCompositor() ?>" value="<?= $compositor->getCodigoCompositor() ?>">
                                                <label for="fc<?= $compositor->getCodigoCompositor() ?>"><?= escaparHtml($compositor->getNomeCompositor() . ' ' . $compositor->getApelidoCompositor()) ?></label>
                                            </div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                            <div class="participant-col">
                                <h4>Músicos</h4>
                                <div class="list-box list-box-short">
                                    <?php if ($discoNovaFaixa): foreach ($novaFaixaMusicos as $musico): ?>
                                            <div class="list-item">
                                                <input type="checkbox" name="musicos[]" id="fm<?= $musico->getCodigoMusico() ?>" value="<?= $musico->getCodigoMusico() ?>">
                                                <label for="fm<?= $musico->getCodigoMusico() ?>"><?= escaparHtml($musico->getNomeMusico() . ' ' . $musico->getApelidoMusico()) ?></label>
                                            </div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                            <div class="participant-col">
                                <h4>Cantores</h4>
                                <div class="list-box list-box-short">
                                    <?php if ($discoNovaFaixa): foreach ($novaFaixaCantores as $cantor): ?>
                                            <div class="list-item">
                                                <input type="checkbox" name="cantores[]" id="fv<?= $cantor->getCodigoCantor() ?>" value="<?= $cantor->getCodigoCantor() ?>">
                                                <label for="fv<?= $cantor->getCodigoCantor() ?>"><?= escaparHtml($cantor->getNomeCantor() . ' ' . $cantor->getApelidoCantor()) ?></label>
                                            </div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <label for="modal-adicionar-faixa-toggle" class="btn btn-secondary">Cancelar</label>
                    <button class="btn btn-primary" type="submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============ MODAL: REMOVER DISCO ============ -->
    <div class="modal-overlay modal-remover-disco">
        <div class="modal confirm">
            <div class="modal-header">
                <h2>Confirmar remoção</h2>
                <label for="modal-remover-disco-toggle" class="modal-close">&times;</label>
            </div>
            <div class="modal-body">Tem certeza que deseja remover o disco seleccionado e as suas faixas?</div>
            <div class="modal-footer">
                <label for="modal-remover-disco-toggle" class="btn btn-secondary">Não</label>
                <button class="btn btn-primary" type="submit" form="form-discos" name="acao" value="remover">Sim</button>
            </div>
        </div>
    </div>

    <!-- ============ MODAL: REMOVER FAIXA ============ -->
    <div class="modal-overlay modal-remover-faixa">
        <div class="modal confirm">
            <div class="modal-header">
                <h2>Confirmar remoção</h2>
                <label for="modal-remover-faixa-toggle" class="modal-close">&times;</label>
            </div>
            <div class="modal-body">Tem certeza que deseja remover a faixa seleccionada?</div>
            <div class="modal-footer">
                <label for="modal-remover-faixa-toggle" class="btn btn-secondary">Não</label>
                <button class="btn btn-primary" type="submit" form="form-detalhes" name="acao" value="removerfaixa">Sim</button>
            </div>
        </div>
    </div>

</body>

</html>
