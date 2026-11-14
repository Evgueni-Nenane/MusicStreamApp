<?php
$tituloPagina = $tituloPagina ?? 'Sistema de Gestão de Discos';
$subtituloPagina = $subtituloPagina ?? '';
$codigoFoto = (int) $utilizador->getCodigo();
?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="../js/modals.js" defer></script>
<script src="../js/form-validation.js" defer></script>
<header class="d-flex flex-wrap justify-content-between align-items-center bg-white rounded-3 shadow-sm p-3 mb-4">
    <div>
        <h2 class="h4 mb-0"><?= htmlspecialchars($tituloPagina) ?></h2>
        <p class="text-muted mb-0"><?= htmlspecialchars($subtituloPagina) ?></p>
    </div>

    <div class="d-flex align-items-center gap-3">
        <div class="text-end">
            <p class="fw-semibold mb-0"><?= htmlspecialchars($utilizador->getNomeCompleto()) ?></p>
            <p class="text-muted text-uppercase small mb-0"><?= htmlspecialchars($utilizador->getPerfil()->getNome()) ?></p>
        </div>
        <img
            src="foto.php<?= $codigoFoto > 0 ? '?u=' . $codigoFoto : '' ?>"
            alt="Foto de perfil"
            class="rounded-circle border object-fit-cover"
            width="42"
            height="42"
        >
    </div>
</header>
