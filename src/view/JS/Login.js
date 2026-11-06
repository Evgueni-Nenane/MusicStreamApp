const username = document.getElementById("username");
const password = document.getElementById("password");
const mostrar = document.getElementById("mostrar");
const erroUsername = document.getElementById("erroUsername");
const erroPassword = document.getElementById("erroPassword");
const form = document.getElementById("formLogin");

// Mostrar / esconder senha
mostrar.addEventListener("change", function () {
    if (mostrar.checked) {
        password.type = "text";
    } else {
        password.type = "password";
    }
});

// Validar campos do formulário
form.addEventListener("submit", function (e) {
    let valido = true;

    if (username.value.trim() === "") {
        erroUsername.textContent = "O nome de utilizador é obrigatório.";
        valido = false;
    } else {
        erroUsername.textContent = "";
    }

    if (password.value.trim() === "") {
        erroPassword.textContent = "A palavra-passe é obrigatória.";
        valido = false;
    } else {
        erroPassword.textContent = "";
    }

    if (!valido) {
        e.preventDefault(); // impede o envio do form
    }
});