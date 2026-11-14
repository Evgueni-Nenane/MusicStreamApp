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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $registoDiscoController->processarPost($utilizador, $_POST);
    if (isset($resultado['redirect'])) {
        header('Location: ' . $resultado['redirect']);
        exit;
    }
    $erro = $resultado['erro'] ?? '';
}

$estado = $registoDiscoController->prepararEcra($_GET);
$msg = $estado['mensagem'];
$generos = $estado['generos'];
$compositores = $estado['compositores'];
$musicos = $estado['musicos'];
$cantores = $estado['cantores'];
$produtores = $estado['produtores'];
$gravadoras = $estado['gravadoras'];
$editoras = $estado['editoras'];

function renderizarItens($campo, $lista, $selecaoUnica = false)
{
    if ($lista === []) {
        return '<div class="text-muted">Sem registos.</div>';
    }

    $tipo = $selecaoUnica ? 'radio' : 'checkbox';
    $nome = $selecaoUnica ? $campo : $campo . '[]';
    $html = '';

    foreach ($lista as $codigo => $texto) {
        $id = $campo . $codigo;
        $textoEscapado = htmlspecialchars($texto);

        $html .= '<div class="form-check mb-2">'
            . '<input class="form-check-input" type="' . $tipo . '" name="' . $nome . '" id="' . $id . '" value="' . $codigo . '" form="form-registo">'
            . '<label class="form-check-label" for="' . $id . '">' . $textoEscapado . '</label>'
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Registar Disco | Sistema de Gestão</title>
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row min-vh-100">
            <?php require __DIR__ . '/../partials/sidebar.php'; ?>

            <div class="col-12 col-md-9 col-lg-10 p-4">
                <?php require __DIR__ . '/../partials/header.php'; ?>

                <main>
                    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
                    <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

                    <form method="post" action="registro.php" id="form-registo">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h3 class="h5 mb-3">Informações do Disco</h3>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="titulo" class="form-label">Título *</label>
                                        <input type="text" class="form-control" id="titulo" name="titulo" maxlength="150" required
                                            value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="ano_edicao" class="form-label">Data de Edição</label>
                                        <input type="date" class="form-control" id="ano_edicao" name="ano_edicao" min="1900-01-01" max="<?= date('Y-m-d') ?>"
                                            value="<?= htmlspecialchars($_POST['ano_edicao'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="preco" class="form-label">Preço</label>
                                        <input type="number" class="form-control" id="preco" name="preco" min="0" max="99999999.99" step="0.01"
                                            value="<?= htmlspecialchars($_POST['preco'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12 col-lg-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body d-flex flex-column">
                                        <h4 class="h6">Géneros *</h4>
                                        <p class="text-muted small">Pode escolher vários géneros.</p>
                                        <button type="button" class="btn btn-outline-primary mt-auto" data-modal-target="#modal-generos">Selecionar géneros</button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body d-flex flex-column">
                                        <h4 class="h6">Participantes</h4>
                                        <ul class="text-muted small mb-3">
                                            <li>Compositores</li>
                                            <li>Músicos</li>
                                            <li>Cantores</li>
                                        </ul>
                                        <button type="button" class="btn btn-outline-primary mt-auto" data-modal-target="#modal-participantes">Selecionar participantes</button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body d-flex flex-column">
                                        <h4 class="h6">Produção</h4>
                                        <ul class="text-muted small mb-3">
                                            <li>Produtores</li>
                                            <li>Gravadoras</li>
                                            <li>Editora (apenas uma)</li>
                                        </ul>
                                        <button type="button" class="btn btn-outline-primary mt-auto" data-modal-target="#modal-producao">Selecionar produção</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <?php if ($podeAdicionar): ?>
                                <button type="reset" class="btn btn-secondary">Limpar</button>
                                <button type="submit" class="btn btn-primary">Registar Disco</button>
                            <?php else: ?>
                                <span class="text-muted small">O seu perfil só pode listar.</span>
                            <?php endif; ?>
                        </div>
                    </form>
                </main>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-generos" tabindex="-1" aria-labelledby="modal-generos-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="modal-generos-label">Géneros</h2>
                    <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="border rounded p-3 bg-light"><?= renderizarItens('generos', $generos) ?></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-modal-close>Concluir</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-participantes" tabindex="-1" aria-labelledby="modal-participantes-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="modal-participantes-label">Participantes</h2>
                    <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <h4 class="h6">Compositores</h4>
                            <div class="border rounded p-3 bg-light"><?= renderizarItens('compositores', $compositores) ?></div>
                        </div>
                        <div class="col-md-4">
                            <h4 class="h6">Músicos</h4>
                            <div class="border rounded p-3 bg-light"><?= renderizarItens('musicos', $musicos) ?></div>
                        </div>
                        <div class="col-md-4">
                            <h4 class="h6">Cantores</h4>
                            <div class="border rounded p-3 bg-light"><?= renderizarItens('cantores', $cantores) ?></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-modal-close>Concluir</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-producao" tabindex="-1" aria-labelledby="modal-producao-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="modal-producao-label">Produção</h2>
                    <button type="button" class="btn-close" data-modal-close aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <h4 class="h6">Produtores</h4>
                            <div class="border rounded p-3 bg-light"><?= renderizarItens('produtores', $produtores) ?></div>
                        </div>
                        <div class="col-md-4">
                            <h4 class="h6">Gravadoras</h4>
                            <div class="border rounded p-3 bg-light"><?= renderizarItens('gravadoras', $gravadoras) ?></div>
                        </div>
                        <div class="col-md-4">
                            <h4 class="h6">Editora (uma só)</h4>
                            <div class="border rounded p-3 bg-light"><?= renderizarItens('editora', $editoras, true) ?></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-modal-close>Concluir</button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
