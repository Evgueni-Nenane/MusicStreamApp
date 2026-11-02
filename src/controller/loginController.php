<?php 
require_once __DIR__ . '/../repository/login.php';
require_once __DIR__ . '/logsController.php';

 class LoginController {
	private $loginDAO;
	private $logController;
	
	public function __construct()
    {
        $this->loginDAO = new LoginDAO();
        $this->logController = new LogsController();
    }


	public function login($nome, $senha) {
        return $this->loginDAO->login($nome,$senha,$this->logController);
	}

	/**
	 * Valida as credenciais e decide para onde redireccionar.
	 * Devolve ['redirect' => ...] em caso de sucesso ou ['erro' => ..., 'username' => ...].
	 */
	public function autenticar($username, $password) {
		$username = trim($username);
		$password = trim($password);

		if ($username === '' || $password === '') {
			return ['erro' => 'Preenche todos campos corretamente.', 'username' => $username];
		}

		if (!$this->login($username, $password)) {
			return ['erro' => 'Utilizador ou Palavra-passe incorretos.', 'username' => $username];
		}

		// Se for o primeiro acesso, obriga a pessoa a mudar a senha antes de continuar
		$destino = $this->isPrimeiroAcesso($username, $password)
			? 'mudar-senha.php'
			: 'administracao.php';

		return ['redirect' => $destino, 'username' => $username];
	}

	/**
	 * Valida a alteração da própria senha.
	 * Devolve ['redirect' => ...] em caso de sucesso ou ['erro' => ...].
	 */
	public function alterarSenha($username, $senhaAtual, $novaSenha, $confirmarSenha) {
		if ($senhaAtual === '' || $novaSenha === '' || $confirmarSenha === '') {
			return ['erro' => 'Preenche todos os campos.'];
		}
		if ($novaSenha !== $confirmarSenha) {
			return ['erro' => 'A nova senha e a confirmação não coincidem.'];
		}
		if (strlen($novaSenha) < 6) {
			return ['erro' => 'A nova senha deve ter pelo menos 6 caracteres.'];
		}
		if ($novaSenha === $senhaAtual) {
			return ['erro' => 'A nova senha não pode ser igual à senha atual.'];
		}

		if (!$this->atualizarSenha($username, $senhaAtual, $novaSenha)) {
			return ['erro' => 'Não foi possível atualizar a senha. Confirma a senha atual.'];
		}

		return ['redirect' => 'administracao.php'];
	}
	
	public function isPrimeiroAcesso($username, $senha) {
		return $this->loginDAO->isPrimeiroAcesso($username,$senha);
	}
	
	public function atualizarSenha($username,$senhaAntiga, $senhaNova) {
		return $this->loginDAO->atualizarSenha($username,$senhaAntiga,$senhaNova);
	}
}
