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
$podeEditar    = Permissao::pode($utilizador, 'editar');
$podeRemover   = Permissao::pode($utilizador, 'remover');
$podeUsers     = Permissao::pode($utilizador, 'utilizadores');
$podeLogs      = Permissao::pode($utilizador, 'logs');
$podeSelFaixa  = $podeEditar || $podeRemover;

$listagemController = new ListagemDiscosController();

/* ---------- Acções (POST) ---------- */
$resultadoPost = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultadoPost = $listagemController->processarPost($utilizador, $_POST);
}

/* ---------- Estado da página ---------- */
$estado = $listagemController->prepararEcra($utilizador, $_GET, $_POST);
if ($resultadoPost !== null) {
    $estado['msg'] = $resultadoPost['msg'];
    if (array_key_exists('det', $resultadoPost)) {
        $estado['det'] = $resultadoPost['det'];
    }
}

$msg            = $estado['msg'];
$det            = $estado['det'];
$ed             = $estado['ed'];
$fx             = $estado['fx'];
$edF            = $estado['edF'];
$edFDisco       = $estado['edFDisco'];
$edFComp        = $estado['edFComp'];
$edFMus         = $estado['edFMus'];
$edFCant        = $estado['edFCant'];
$edFMin         = $estado['edFMin'];
$edFSeg         = $estado['edFSeg'];
$discos         = $estado['discos'];
$todosGeneros   = $estado['todosGeneros'];
$generosDoDisco = $estado['generosDoDisco'];
$dId            = $det ? $det->getCodigoDisco() : 0;
$dGeneros       = $estado['dGeneros'];
$dFaixas        = $estado['dFaixas'];
$dCompositores  = $estado['dCompositores'];
$dMusicos       = $estado['dMusicos'];
$dCantores      = $estado['dCantores'];
$edFDiscoCompositores = $estado['edFDiscoCompositores'];
$edFDiscoMusicos      = $estado['edFDiscoMusicos'];
$edFDiscoCantores     = $estado['edFDiscoCantores'];
$fxCompositores       = $estado['fxCompositores'];
$fxMusicos            = $estado['fxMusicos'];
$fxCantores           = $estado['fxCantores'];
$q              = $estado['q'];

/* ---------- Cabeçalho: foto do utilizador com sessão ---------- */
function h($v)
{
    return htmlspecialchars((string)$v);
}
function nomes($lista, $g1, $g2)
{   // "Nome Apelido, Nome Apelido"
    $r = [];
    foreach ($lista as $p) $r[] = $p->$g1() . ' ' . $p->$g2();
    return implode(', ', $r);
}
function duracaoCurta($valor)
{   // O MySQL devolve TIME como HH:MM:SS; a interface mostra HH:MM.
    $valor = (string)$valor;
    return $valor === '' ? '' : substr($valor, 0, 5);
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagem de Discos | Sistema de Gestão</title>
    <link rel="stylesheet" href="../css/listagemdiscos.css">
    <link rel="stylesheet" href="../css/registro.css">
    <style>
        /* Barra de pesquisa ao lado do título do cartão */
        .card-header.com-pesquisa {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .card-header.com-pesquisa .search-input {
            width: 100%;
            max-width: 320px;
            padding: 0.6rem 0.9rem;
            border: 1px solid #e4e4e7;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: inherit;
            background: #fff;
        }

        .card-header.com-pesquisa .search-input:focus {
            outline: none;
            border-color: #18181b;
        }
    </style>
</head>

<body>

    <input type="checkbox" id="modal-detalhes-toggle" class="modal-toggle" <?= $det ? 'checked' : '' ?>>
    <input type="checkbox" id="modal-editar-disco-toggle" class="modal-toggle" <?= $ed ? 'checked' : '' ?>>
    <input type="checkbox" id="modal-editar-faixa-toggle" class="modal-toggle" <?= $edF ? 'checked' : '' ?>>
    <input type="checkbox" id="modal-adicionar-faixa-toggle" class="modal-toggle" <?= $fx ? 'checked' : '' ?>>

    <div class="app-container">
        <aside>
            <div class="title" id="listar">
                <h1>DiscoGest</h1>
            </div>
            <nav>
                <ul>
                    <li class="secnav">Menu Principal</li>
                    <li><a href="registro.php">Registar</a></li>
                    <li><a href="listagemdiscos.php" class="active">Listar Discos</a></li>
                    <li class="secnav">Intervenientes</li>
                    <li><a href="artistas.php">Artistas</a></li>
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
                    <p>Listagem de discos cadastrados</p>
                </div>
                <div class="userdetails">
                    <div class="userdetailstxt">
                        <p><?= h($utilizador->getNomeCompleto()) ?></p>
                        <p><?= h($utilizador->getPerfil()->getNome()) ?></p>
                    </div>
                    <img src="foto.php" onerror="this.onerror=null;this.src='../resources/user.png'" alt="Foto de Perfil">
                </div>
            </header>

            <main>
                <div class="card">
                    <!-- Formulário GET da pesquisa (o input liga-se a ele via form="form-pesq"). -->
                    <form method="get" action="listagemdiscos.php" id="form-pesq"></form>

                    <div class="card-header com-pesquisa">
                        <h3>Discos Cadastrados no Sistema</h3>
                        <input type="search" class="search-input" name="q" form="form-pesq"
                            value="<?= h($q) ?>" placeholder="Pesquisar disco, género, ano..."
                            aria-label="Pesquisar discos">
                    </div>

                    <?php if ($msg): ?>
                        <div class="alert alert-ok"><?= h($msg) ?></div>
                    <?php endif; ?>

                    <form method="post" action="listagemdiscos.php">
                        <!-- Mantém a pesquisa activa depois de Editar / Remover / Ver Detalhes -->
                        <input type="hidden" name="q" value="<?= h($q) ?>">
                        <div class="table-wrapper">
                            <table>
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
                                    <?php foreach ($discos as $d): ?>
                                        <tr>
                                            <td><input type="radio" name="sel" value="<?= $d->getCodigoDisco() ?>"></td>
                                            <td><?= h($d->getTitulo()) ?></td>
                                            <td><?= h($d->getGeneroMusicalTxt()) ?></td>
                                            <td><?= number_format($d->getPreco(), 2, ',', '.') ?></td>
                                            <td><?= h($d->getAnoEdicao()) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($discos) === 0): ?>
                                        <tr>
                                            <td colspan="5">
                                                <?= $q !== '' ? 'Nenhum disco encontrado para "' . h($q) . '".' : 'Ainda não há discos cadastrados.' ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="table-actions">
                            <?php if ($podeRemover): ?>
                            <button class="btn btn-secondary btn-sm" type="submit" name="acao" value="remover"
                                onclick="return confirm('Remover o disco seleccionado e as suas faixas?')">Remover</button>
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
                <input type="hidden" name="acao" value="removerfaixa" id="acao-detalhes">
                <input type="hidden" name="id" value="<?= $det ? $dId : '' ?>">
                <input type="hidden" name="q" value="<?= h($q) ?>">
                <div class="modal-body">
                    <div class="detail-section">
                        <h4>Informações do Disco</h4>
                        <div class="detail-grid">
                            <div class="detail-item"><span class="label">Título do Disco</span><span class="value"><?= $det ? h($det->getTitulo()) : '' ?></span></div>
                            <div class="detail-item"><span class="label">Género Musical</span><span class="value"><?= $det ? h($dGeneros) : '' ?></span></div>
                            <div class="detail-item"><span class="label">Preço</span><span class="value"><?= $det ? number_format($det->getPreco(), 2, ',', '.') : '' ?></span></div>
                            <div class="detail-item"><span class="label">Tempo de Existência</span><span class="value"><?= $det ? (date('Y') - (int)$det->getAnoEdicao()) . ' ano(s)' : '' ?></span></div>
                            <div class="detail-item"><span class="label">Ano de Edição</span><span class="value"><?= $det ? h($det->getAnoEdicao()) : '' ?></span></div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h4>Participantes do Disco</h4>
                        <div class="detail-grid">
                            <div class="detail-item"><span class="label">Compositores</span><span class="value"><?= $det ? h(nomes($dCompositores, 'getNomeCompositor', 'getApelidoCompositor')) : '' ?></span></div>
                            <div class="detail-item"><span class="label">Músicos</span><span class="value"><?= $det ? h(nomes($dMusicos, 'getNomeMusico', 'getApelidoMusico')) : '' ?></span></div>
                            <div class="detail-item"><span class="label">Cantores</span><span class="value"><?= $det ? h(nomes($dCantores, 'getNomeCantor', 'getApelidoCantor')) : '' ?></span></div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h4>Faixas do Disco</h4>
                        <div class="table-wrapper">
                            <table>
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
                                    <?php if ($det): foreach ($dFaixas as $f): ?>
                                            <tr>
                                                <td><input type="radio" name="sel_faixa" value="<?= $f->getIdFaixa() ?>" <?= $podeSelFaixa ? '' : 'disabled' ?>></td>
                                                <td><?= h($f->getNumeroFaixa()) ?></td>
                                                <td><?= h($f->getNomeFaixa()) ?></td>
                                                <td><?= h($f->getArtistaPrincipal()) ?></td>
                                                <td><?= h(duracaoCurta($f->getDuracao())) ?></td>
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
                    <button class="btn btn-secondary" type="submit" name="acao" value="editarfaixa" formmethod="get"
                        onclick="return !!document.querySelector('#form-detalhes input[name=sel_faixa]:checked') || (alert('Seleccione uma faixa.'), false)">Editar Faixa</button>
                    <?php endif; ?>
                    <?php if ($podeRemover): ?>
                    <button class="btn btn-primary" type="submit"
                        onclick="return !!document.querySelector('#form-detalhes input[name=sel_faixa]:checked') || (alert('Seleccione uma faixa.'), false) ? confirm('Remover a faixa seleccionada?') : false">Remover Faixa</button>
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
                <input type="hidden" name="id" value="<?= $ed ? $ed->getCodigoDisco() : '' ?>">
                <input type="hidden" name="q" value="<?= h($q) ?>">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="label-group form-grid-full">
                            <label for="edit-titulo">Título</label>
                            <input type="text" id="edit-titulo" name="titulo" required value="<?= h($ed ? $ed->getTitulo() : '') ?>">
                        </div>
                        <div class="label-group">
                            <label for="edit-preco">Preço</label>
                            <input type="number" id="edit-preco" name="preco" min="0" step="0.01" value="<?= $ed ? $ed->getPreco() : '' ?>">
                        </div>
                        <div class="label-group">
                            <label for="edit-ano">Data de Edição</label>
                            <input type="date" id="edit-ano" name="ano_edicao" required value="<?= $ed ? $ed->getAnoEdicao() . '-01-01' : '' ?>">
                        </div>
                        <div class="label-group form-grid-full">
                            <label>Géneros</label>
                            <div class="list-box" style="max-height:140px;">
                                <?php foreach ($todosGeneros as $g): ?>
                                    <div class="list-item">
                                        <input type="checkbox" name="generos[]" id="eg<?= $g->getCodigoGenero() ?>" value="<?= $g->getCodigoGenero() ?>"
                                            <?= in_array($g->getCodigoGenero(), $generosDoDisco) ? 'checked' : '' ?>>
                                        <label for="eg<?= $g->getCodigoGenero() ?>"><?= h($g->getNomeGenero()) ?></label>
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
                <input type="hidden" name="id" value="<?= $edF ? $edFDisco : '' ?>">
                <input type="hidden" name="id_faixa" value="<?= $edF ? $edF->getIdFaixa() : '' ?>">
                <input type="hidden" name="num" value="<?= $edF ? $edF->getNumeroFaixa() : '' ?>">
                <input type="hidden" name="q" value="<?= h($q) ?>">
                <div class="modal-body">
                    <div class="detail-section">
                        <h4>Informações da Faixa</h4>
                        <div class="form-grid">
                            <div class="label-group">
                                <label for="edfaixa-nome">Nome da Faixa</label>
                                <input type="text" id="edfaixa-nome" name="nome" required value="<?= $edF ? h($edF->getNomeFaixa()) : '' ?>">
                            </div>
                            <div class="label-group">
                                <label for="edfaixa-artista">Artista Principal</label>
                                <input type="text" id="edfaixa-artista" name="artista" required value="<?= $edF ? h($edF->getArtistaPrincipal()) : '' ?>">
                            </div>
                            <div class="label-group">
                                <label>Duração (mm:ss)</label>
                                <div style="display:flex; gap:0.5rem; align-items:center;">
                                    <input type="number" name="min" min="0" max="99" value="<?= $edFMin ?>" style="width:70px; text-align:center;">
                                    <span>:</span>
                                    <input type="number" name="seg" min="0" max="59" value="<?= $edFSeg ?>" style="width:70px; text-align:center;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="detail-section">
                        <h4>Participantes do disco</h4>
                        <div class="participant-grid">
                            <div class="participant-col">
                                <h4>Compositores</h4>
                                <div class="list-box" style="max-height:120px;">
                                    <?php if ($edFDisco): foreach ($edFDiscoCompositores as $c): ?>
                                        <div class="list-item"><input type="checkbox" name="compositores[]" id="efc<?= $c->getCodigoCompositor() ?>" value="<?= $c->getCodigoCompositor() ?>" <?= in_array($c->getCodigoCompositor(), $edFComp) ? 'checked' : '' ?>><label for="efc<?= $c->getCodigoCompositor() ?>"><?= h($c->getNomeCompositor() . ' ' . $c->getApelidoCompositor()) ?></label></div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                            <div class="participant-col">
                                <h4>Músicos</h4>
                                <div class="list-box" style="max-height:120px;">
                                    <?php if ($edFDisco): foreach ($edFDiscoMusicos as $m): ?>
                                        <div class="list-item"><input type="checkbox" name="musicos[]" id="efm<?= $m->getCodigoMusico() ?>" value="<?= $m->getCodigoMusico() ?>" <?= in_array($m->getCodigoMusico(), $edFMus) ? 'checked' : '' ?>><label for="efm<?= $m->getCodigoMusico() ?>"><?= h($m->getNomeMusico() . ' ' . $m->getApelidoMusico()) ?></label></div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                            <div class="participant-col">
                                <h4>Cantores</h4>
                                <div class="list-box" style="max-height:120px;">
                                    <?php if ($edFDisco): foreach ($edFDiscoCantores as $c): ?>
                                        <div class="list-item"><input type="checkbox" name="cantores[]" id="efv<?= $c->getCodigoCantor() ?>" value="<?= $c->getCodigoCantor() ?>" <?= in_array($c->getCodigoCantor(), $edFCant) ? 'checked' : '' ?>><label for="efv<?= $c->getCodigoCantor() ?>"><?= h($c->getNomeCantor() . ' ' . $c->getApelidoCantor()) ?></label></div>
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
                <input type="hidden" name="id" value="<?= $fx ? $fx->getCodigoDisco() : '' ?>">
                <input type="hidden" name="q" value="<?= h($q) ?>">
                <div class="modal-body">
                    <div class="detail-section">
                        <h4>Informações da Faixa<?= $fx ? ' — ' . h($fx->getTitulo()) : '' ?></h4>
                        <div class="form-grid">
                            <div class="label-group">
                                <label for="faixa-nome">Nome da Faixa</label>
                                <input type="text" id="faixa-nome" name="nome" required>
                            </div>
                            <div class="label-group">
                                <label for="faixa-artista">Artista Principal</label>
                                <input type="text" id="faixa-artista" name="artista" required>
                            </div>
                            <div class="label-group">
                                <label>Duração (mm:ss)</label>
                                <div style="display:flex; gap:0.5rem; align-items:center;">
                                    <input type="number" name="min" min="0" max="99" value="0" style="width:70px; text-align:center;">
                                    <span>:</span>
                                    <input type="number" name="seg" min="0" max="59" value="0" style="width:70px; text-align:center;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="detail-section">
                        <h4>Selecione entre os participantes já escolhidos no disco</h4>
                        <div class="participant-grid">
                            <div class="participant-col">
                                <h4>Compositores</h4>
                                <div class="list-box" style="max-height:120px;">
                                    <?php if ($fx): foreach ($fxCompositores as $c): ?>
                                            <div class="list-item"><input type="checkbox" name="compositores[]" id="fc<?= $c->getCodigoCompositor() ?>" value="<?= $c->getCodigoCompositor() ?>"><label for="fc<?= $c->getCodigoCompositor() ?>"><?= h($c->getNomeCompositor() . ' ' . $c->getApelidoCompositor()) ?></label></div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                            <div class="participant-col">
                                <h4>Músicos</h4>
                                <div class="list-box" style="max-height:120px;">
                                    <?php if ($fx): foreach ($fxMusicos as $m): ?>
                                            <div class="list-item"><input type="checkbox" name="musicos[]" id="fm<?= $m->getCodigoMusico() ?>" value="<?= $m->getCodigoMusico() ?>"><label for="fm<?= $m->getCodigoMusico() ?>"><?= h($m->getNomeMusico() . ' ' . $m->getApelidoMusico()) ?></label></div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                            <div class="participant-col">
                                <h4>Cantores</h4>
                                <div class="list-box" style="max-height:120px;">
                                    <?php if ($fx): foreach ($fxCantores as $c): ?>
                                            <div class="list-item"><input type="checkbox" name="cantores[]" id="fv<?= $c->getCodigoCantor() ?>" value="<?= $c->getCodigoCantor() ?>"><label for="fv<?= $c->getCodigoCantor() ?>"><?= h($c->getNomeCantor() . ' ' . $c->getApelidoCantor()) ?></label></div>
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

</body>

</html>