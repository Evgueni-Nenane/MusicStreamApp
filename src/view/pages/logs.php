<?php
require_once __DIR__ . '/../../model/sessao.php';
require_once __DIR__ . '/../../model/permissao.php';
require_once __DIR__ . '/../../model/utilizador.php';
require_once __DIR__ . '/../../model/nivelacesso.php';
require_once __DIR__ . '/../../controller/logsController.php';

$utilizador = Sessao::getUtilizadorLogado();
if (!$utilizador) { header('Location: Login.php'); exit; }

// Auditoria: Auditor vêem os logs.
if (!Permissao::pode($utilizador, 'logs')) {
    header('Location: listagemdiscos.php');
    exit;
}
$podeUtilizadores = Permissao::pode($utilizador, 'utilizadores');
$podeLogs  = true;

$logsController = new LogsController();
$termoPesquisa = trim($_GET['q'] ?? '');
$logs = $logsController->listarComPesquisa($termoPesquisa);
$paginaAtiva = 'logs';
$subtituloPagina = 'Auditoria do sistema';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logs | Sistema de Gestão</title>
<link rel="stylesheet" href="../css/logs.css">
</head>
<body>

<div class="app-container">
  <?php require __DIR__ . '/../partials/sidebar.php'; ?>

  <div class="containermain">
    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main>
      <div class="card">
        <div class="card-header">
          <h3>Logs do Sistema</h3>
          <form method="get"><input type="search" name="q" class="search-input" placeholder="Pesquisar nos logs..." value="<?= htmlspecialchars($termoPesquisa) ?>"></form>
        </div>
        <div class="table-wrapper">
          <table>
            <thead><tr><th>ID</th><th>Nome Completo</th><th>E-mail Corporativo</th><th>Perfil</th><th>Acção</th><th>Data</th></tr></thead>
            <tbody>
              <?php foreach ($logs as $l): ?>
                <tr>
                  <td><?= $l->getCodigo() ?></td>
                  <td><?= htmlspecialchars(trim($l->getNome() . ' ' . $l->getApelido())) ?></td>
                  <td><?= htmlspecialchars($l->getEmail()) ?></td>
                  <td><?= htmlspecialchars($l->getPerfil()) ?></td>
                  <td><?= htmlspecialchars($l->getAccao()) ?></td>
                  <td><?= htmlspecialchars($l->getDataHora()) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$logs): ?>
                <tr><td colspan="6">Nenhum registo encontrado.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

</body>
</html>
