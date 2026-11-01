<?php
/*
 * Serve a foto de perfil guardada na base de dados (Utilizador.foto / foto_tipo).
 *
 *   foto.php          -> foto do utilizador com sessao
 *   foto.php?u=<id>   -> foto de outro utilizador (so o proprio ou quem tem a permissao "utilizadores")
 */
require_once __DIR__ . '/../../model/sessao.php';
require_once __DIR__ . '/../../model/permissao.php';
require_once __DIR__ . '/../../repository/utilizador.php';

$utilizador = Sessao::getUtilizadorLogado();
if (!$utilizador) {
    http_response_code(404);
    exit;
}

$proprioId = (int) $utilizador->getCodigo();
$id = isset($_GET['u']) ? (int) $_GET['u'] : $proprioId;

if ($id <= 0) {
    http_response_code(404);
    exit;
}

$proprio = ($id === $proprioId);
if (!$proprio && !Permissao::pode($utilizador, 'utilizadores')) {
    http_response_code(403);
    exit;
}

$utilizadorDAO = new UtilizadorDAO();
$foto = $utilizadorDAO->buscarFoto($id);

if ($foto === null) {
    http_response_code(404);
    exit;
}

$etag = '"' . md5($foto['dados']) . '"';
header('ETag: ' . $etag);
header('X-Content-Type-Options: nosniff');

if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
    http_response_code(304);
    exit;
}

$tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$tipo = in_array($foto['tipo'], $tiposPermitidos, true) ? $foto['tipo'] : 'application/octet-stream';

header('Content-Type: ' . $tipo);
header('Content-Length: ' . strlen($foto['dados']));
header('Cache-Control: private, max-age=300');
echo $foto['dados'];
