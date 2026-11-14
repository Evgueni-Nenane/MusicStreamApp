<?php
$paginaAtiva = $paginaAtiva ?? '';
$podeUtilizadores = $podeUtilizadores ?? false;
$podeLogs = $podeLogs ?? false;

$itensMenu = [
    ['secao' => 'Menu Principal'],
    ['chave' => 'registro', 'href' => 'registro.php', 'texto' => 'Registar'],
    ['chave' => 'listagemdiscos', 'href' => 'listagemdiscos.php', 'texto' => 'Listar Discos'],
    ['secao' => 'Intervenientes'],
    ['chave' => 'artistas', 'href' => 'artistas.php', 'texto' => 'Artistas'],
    ['chave' => 'producao', 'href' => 'producao.php', 'texto' => 'Produção'],
    ['secao' => 'Cadastros'],
    ['chave' => 'instrumentos', 'href' => 'instrumentos.php', 'texto' => 'Instrumentos'],
    ['chave' => 'generos', 'href' => 'generos.php', 'texto' => 'Géneros'],
    ['secao' => 'Acções'],
];

if ($podeUtilizadores) {
    $itensMenu[] = ['chave' => 'administracao', 'href' => 'administracao.php', 'texto' => 'Administração'];
}

if ($podeLogs) {
    $itensMenu[] = ['chave' => 'logs', 'href' => 'logs.php', 'texto' => 'Logs'];
}

$itensMenu[] = ['chave' => 'logout', 'href' => 'LogOut.php', 'texto' => 'Sair'];
?>
<aside class="col-12 col-md-3 col-lg-2 bg-white border-end p-3">
    <h1 class="fs-4 fw-bold mb-4 px-2">DiscoGest</h1>

    <nav class="nav nav-pills flex-column gap-1">
        <?php foreach ($itensMenu as $item): ?>
            <?php if (isset($item['secao'])): ?>
                <span class="text-uppercase text-muted small fw-semibold px-2 mt-3 mb-1"><?= htmlspecialchars($item['secao']) ?></span>
            <?php else: ?>
                <?php $classeAtiva = $item['chave'] === $paginaAtiva ? 'active' : 'text-dark'; ?>
                <a class="nav-link <?= $classeAtiva ?>" href="<?= htmlspecialchars($item['href']) ?>">
                    <?= htmlspecialchars($item['texto']) ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>
