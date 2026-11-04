<?php
/**
 * Menu lateral comum a todas as páginas autenticadas.
 *
 * Variáveis esperadas:
 * - $paginaAtiva      (string) chave da página atual, usada para marcar o link ativo
 * - $podeUtilizadores (bool)   mostra a entrada "Administração"
 * - $podeLogs         (bool)   mostra a entrada "Logs"
 */
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
<aside>
    <div class="title">
        <h1>DiscoGest</h1>
    </div>

    <nav>
        <ul>
            <?php foreach ($itensMenu as $item): ?>
                <?php if (isset($item['secao'])): ?>
                    <li class="secnav"><?= htmlspecialchars($item['secao']) ?></li>
                <?php else: ?>
                    <?php $classeAtiva = $item['chave'] === $paginaAtiva ? ' class="active"' : ''; ?>
                    <li>
                        <a href="<?= htmlspecialchars($item['href']) ?>"<?= $classeAtiva ?>>
                            <?= htmlspecialchars($item['texto']) ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>
