<?php
/**
 * Cabeçalho comum: título da página e utilizador autenticado.
 *
 * Variáveis esperadas:
 * - $utilizador      (Utilizador) utilizador com sessão iniciada
 * - $subtituloPagina (string)     descrição curta apresentada sob o título
 * - $tituloPagina    (string)     título genérico da aplicação (opcional)
 */
$tituloPagina = $tituloPagina ?? 'Sistema de Gestão de Discos';
$subtituloPagina = $subtituloPagina ?? '';
$codigoFoto = (int) $utilizador->getCodigo();
?>
<header>
    <div class="pagetitle">
        <h2><?= htmlspecialchars($tituloPagina) ?></h2>
        <p><?= htmlspecialchars($subtituloPagina) ?></p>
    </div>

    <div class="userdetails">
        <div class="userdetailstxt">
            <p><?= htmlspecialchars($utilizador->getNomeCompleto()) ?></p>
            <p><?= htmlspecialchars($utilizador->getPerfil()->getNome()) ?></p>
        </div>
        <img
            src="foto.php<?= $codigoFoto > 0 ? '?u=' . $codigoFoto : '' ?>"
            alt="Foto de perfil"
        >
    </div>
</header>
