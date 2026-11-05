/**
 * Mostra a imagem escolhida antes de o formulário ser enviado.
 *
 * Cada input de ficheiro aponta para a imagem de pré-visualização através
 * do atributo data-preview. Este é o único comportamento que necessita de
 * JavaScript nesta página.
 */
document.querySelectorAll('input[type="file"][data-preview]').forEach((input) => {
    const preview = document.getElementById(input.dataset.preview);

    if (!preview) {
        return;
    }

    let objectUrl = null;

    input.addEventListener('change', () => {
        const ficheiro = input.files[0];

        if (!ficheiro) {
            return;
        }

        if (objectUrl !== null) {
            URL.revokeObjectURL(objectUrl);
        }

        objectUrl = URL.createObjectURL(ficheiro);
        preview.src = objectUrl;
    });
});
