<?php
require_once __DIR__ . '/../../model/sessao.php';
require_once __DIR__ . '/../../model/permissao.php';
require_once __DIR__ . '/../../model/utilizador.php';
require_once __DIR__ . '/../../model/nivelacesso.php';
require_once __DIR__ . '/../../controller/registroDiscoController.php';

$utilizador = Sessao::getUtilizadorLogado();
if (!$utilizador) {
    header('Location: Login.php');
    exit;
}

$podeAdicionar = Permissao::pode($utilizador, 'adicionar');
$podeUtilizadores = Permissao::pode($utilizador, 'utilizadores');
$podeLogs = Permissao::pode($utilizador, 'logs');

$registoDiscoController = new RegistoDiscoController();

$msg = '';
$erro = '';

/* ---------- Registar o disco (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $registoDiscoController->processarPost($utilizador, $_POST);
    if (isset($resultado['redirect'])) {
        header('Location: ' . $resultado['redirect']);
        exit;
    }
    $erro = $resultado['erro'] ?? '';
}

/* ---------- Estado da página (listas dos modais) ---------- */
$estado = $registoDiscoController->prepararEcra($_GET);
$msg = $estado['mensagem'];
$generos = $estado['generos'];
$compositores = $estado['compositores'];
$musicos = $estado['musicos'];
$cantores = $estado['cantores'];
$produtores = $estado['produtores'];
$gravadoras = $estado['gravadoras'];
$editoras = $estado['editoras'];

// Desenha as linhas de uma lista. Os inputs usam form="form-registo" porque
// os modais ficam fora do <form>, mas os valores têm de ir com ele.
function renderizarItens($campo, $lista, $selecaoUnica = false)
{
    if ($lista === []) {
        return '<div class="list-item text-muted">Sem registos.</div>';
    }

    $tipo = $selecaoUnica ? 'radio' : 'checkbox';
    $nome = $selecaoUnica ? $campo : $campo . '[]';
    $html = '';

    foreach ($lista as $codigo => $texto) {
        $id = $campo . $codigo;
        $textoEscapado = htmlspecialchars($texto);

        $html .= '<div class="list-item">'
            . '<input type="' . $tipo . '" name="' . $nome . '" id="' . $id . '" value="' . $codigo . '"'
            . ' form="form-registo">'
            . '<label for="' . $id . '">' . $textoEscapado . '</label>'
            . '</div>';
    }

    return $html;
}

$paginaAtiva = 'registro';
$subtituloPagina = 'Registar disco';
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar Disco | Sistema de Gestão</title>
    <link rel="stylesheet" href="../css/registro.css">
</head>

<body>

    <!-- Controlos dos modais (checkbox hack) -->
    <input type="checkbox" id="modal-generos-toggle" class="modal-toggle">
    <input type="checkbox" id="modal-participantes-toggle" class="modal-toggle">
    <input type="checkbox" id="modal-producao-toggle" class="modal-toggle">

    <div class="app-container">
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>

        <div class="containermain">
            <?php require __DIR__ . '/../partials/header.php'; ?>

            <main>
                <?php if ($msg): ?><div class="alert alert-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
                <?php if ($erro): ?><div class="alert alert-erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

                <form method="post" action="registro.php" id="form-registo" class="maincontent">

                    <!-- ===== Dados do disco ===== -->
                    <div class="card">
                        <h3>Informações do Disco</h3>
                        <div class="form-grid">
                            <div class="label-group form-grid-full">
                                <label for="titulo">Título *</label>
                                <input type="text" id="titulo" name="titulo" maxlength="150" required
                                    value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>">
                            </div>
                            <div class="label-group">
                                <label for="ano_edicao">Data de Edição</label>
                                <input type="date" id="ano_edicao" name="ano_edicao" min="1900-01-01" max="<?= date('Y-m-d') ?>"
                                    value="<?= htmlspecialchars($_POST['ano_edicao'] ?? '') ?>">
                            </div>
                            <div class="label-group">
                                <label for="preco">Preço</label>
                                <input type="number" id="preco" name="preco" min="0" step="0.01"
                                    value="<?= htmlspecialchars($_POST['preco'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- ===== Painéis: abrem os modais ===== -->
                    <div class="credits-grid">

                        <div class="credit-panel">
                            <h4>Géneros *</h4>
                            <label for="modal-generos-toggle" class="btn-select">Selecionar géneros</label>
                            <p class="selection-info">Pode escolher vários géneros.</p>
                        </div>

                        <div class="credit-panel">
                            <h4>Participantes</h4>
                            <label for="modal-participantes-toggle" class="btn-select">Selecionar participantes</label>
                            <ul>
                                <li>Compositores</li>
                                <li>Músicos</li>
                                <li>Cantores</li>
                            </ul>
                        </div>

                        <div class="credit-panel">
                            <h4>Produção</h4>
                            <label for="modal-producao-toggle" class="btn-select">Selecionar produção</label>
                            <ul>
                                <li>Produtores</li>
                                <li>Gravadoras</li>
                                <li>Editora (apenas uma)</li>
                            </ul>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <?php if ($podeAdicionar): ?>
                            <button class="btn btn-secondary" type="reset">Limpar</button>
                            <button class="btn btn-primary" type="submit">Registar Disco</button>
                        <?php else: ?>
                            <span class="text-muted text-sm">O seu perfil só pode listar.</span>
                        <?php endif; ?>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <!-- ============ MODAL: GÉNEROS ============ -->
    <div class="modal-overlay modal-generos">
        <div class="modal modal-sm">
            <div class="modal-header">
                <h2>Géneros</h2>
                <label for="modal-generos-toggle" class="modal-close">&times;</label>
            </div>
            <div class="modal-body">
                <div class="list-box"><?= renderizarItens('generos', $generos) ?></div>
            </div>
            <div class="modal-footer">
                <label for="modal-generos-toggle" class="btn btn-primary">Concluir</label>
            </div>
        </div>
    </div>

    <!-- ============ MODAL: PARTICIPANTES ============ -->
    <div class="modal-overlay modal-participantes">
        <div class="modal">
            <div class="modal-header">
                <h2>Participantes</h2>
                <label for="modal-participantes-toggle" class="modal-close">&times;</label>
            </div>
            <div class="modal-body">
                <div class="participant-grid">
                    <div class="participant-col">
                        <h4>Compositores</h4>
                        <div class="list-box"><?= renderizarItens('compositores', $compositores) ?></div>
                    </div>
                    <div class="participant-col">
                        <h4>Músicos</h4>
                        <div class="list-box"><?= renderizarItens('musicos', $musicos) ?></div>
                    </div>
                    <div class="participant-col">
                        <h4>Cantores</h4>
                        <div class="list-box"><?= renderizarItens('cantores', $cantores) ?></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <label for="modal-participantes-toggle" class="btn btn-primary">Concluir</label>
            </div>
        </div>
    </div>

    <!-- ============ MODAL: PRODUÇÃO ============ -->
    <div class="modal-overlay modal-producao">
        <div class="modal">
            <div class="modal-header">
                <h2>Produção</h2>
                <label for="modal-producao-toggle" class="modal-close">&times;</label>
            </div>
            <div class="modal-body">
                <div class="participant-grid">
                    <div class="participant-col">
                        <h4>Produtores</h4>
                        <div class="list-box"><?= renderizarItens('produtores', $produtores) ?></div>
                    </div>
                    <div class="participant-col">
                        <h4>Gravadoras</h4>
                        <div class="list-box"><?= renderizarItens('gravadoras', $gravadoras) ?></div>
                    </div>
                    <div class="participant-col">
                        <h4>Editora (uma só)</h4>
                        <div class="list-box"><?= renderizarItens('editora', $editoras, true) ?></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <label for="modal-producao-toggle" class="btn btn-primary">Concluir</label>
            </div>
        </div>
    </div>

</body>

</html>
