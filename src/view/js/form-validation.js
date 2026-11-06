/**
 * Valida todos os formulários com a API nativa do browser.
 *
 * Os campos usam as regras já definidas no HTML (required, type="email",
 * minlength, min, max, maxlength, ...). Este ficheiro acrescenta apenas o
 * que o HTML não exprime: escolher pelo menos um item nas listas.
 */
document.addEventListener('submit', (evento) => {
    const formulario = evento.target;

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    if (!temEscolhasValidas(formulario)) {
        evento.preventDefault();
        return;
    }

    if (!formulario.checkValidity()) {
        evento.preventDefault();
        formulario.reportValidity();
    }
});

/**
 * Confirma os grupos de escolha que precisam de pelo menos uma opção marcada:
 * os rádios de seleção das listagens e os géneros do registo de discos.
 */
function temEscolhasValidas(formulario) {
    const mensagens = {
        sel: 'Seleciona um registo da lista.',
        sel_perfil: 'Seleciona um perfil da lista.',
        sel_faixa: 'Seleciona uma faixa.',
        sel_musico: 'Seleciona um músico.',
        sel_compositor: 'Seleciona um compositor.',
        sel_cantor: 'Seleciona um cantor.',
    };
    const controlos = Array.from(formulario.elements);
    const grupos = new Set(
        controlos
            .filter((controlo) => controlo.type === 'radio' && controlo.name.startsWith('sel'))
            .map((controlo) => controlo.name)
    );

    for (const grupo of grupos) {
        const marcado = controlos.some(
            (controlo) => controlo.type === 'radio' && controlo.name === grupo && controlo.checked
        );

        if (!marcado) {
            alert(mensagens[grupo] || 'Seleciona uma opção da lista.');
            return false;
        }
    }

    const generos = controlos.filter(
        (controlo) => controlo.type === 'checkbox' && controlo.name === 'generos[]'
    );

    if (generos.length > 0 && !generos.some((controlo) => controlo.checked)) {
        alert('Seleciona pelo menos um género.');
        return false;
    }

    return true;
}
