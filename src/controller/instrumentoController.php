<?php 
require_once __DIR__ . '/../model/instrumento.php';
require_once __DIR__ . '/../model/permissao.php';
require_once __DIR__ . '/../repository/instrumento.php';
require_once __DIR__ . '/logsController.php';

 class InstrumentoController {
	private $instrumentoDAO;
	
	public function __construct()
    {
        $this->instrumentoDAO = new InstrumentoDAO();
        }
	
	public function adicionarInstrumento($instrumento) {
		return $this->instrumentoDAO->inserir($instrumento);
		
	}
	
	public function listarInstrumentos() {
		return $this->instrumentoDAO->listarTodos();
	}
	
	public function buscarPorMusico($codigoMusico) {
		return $this->instrumentoDAO->listarPorMusico($codigoMusico);
	}
	
	public function remover($codigo) {
		return $this->instrumentoDAO->remover($codigo);
	}

	public function inserirRelacaoMusicoInstrumento($codigoMusico, $codigoInstrumento) {
		return $this->instrumentoDAO->inserirRelacaoMusicoInstrumento($codigoMusico, $codigoInstrumento);
	}

	public function removerRelacoesPorMusico($codigoMusico) {
		return $this->instrumentoDAO->removerRelacoesPorMusico($codigoMusico);
	}

	// Valida e executa as acções do formulário. Devolve ['msg'] ou ['erro'].
	public function processarPost($utilizador, $post)
	{
		$acao = $post['acao'] ?? '';

		if ($acao === 'salvar') {
			if ($bloq = Permissao::bloquear($utilizador, 'adicionar')) {
				return ['erro' => $bloq];
			}

			$nome = trim($post['nome'] ?? '');
			if ($nome === '') {
				return ['erro' => 'O nome do instrumento é obrigatório.'];
			}

			$novo = $this->adicionarInstrumento(new Instrumento(null, $nome));
			if ($novo > 0) {
				LogsController::registar('Registou o instrumento: ' . $nome);
			}
			return $novo > 0
				? ['msg' => 'Instrumento adicionado com sucesso.']
				: ['erro' => 'Erro ao adicionar (já existe?).'];
		}

		if ($acao === 'remover') {
			if ($bloq = Permissao::bloquear($utilizador, 'remover')) {
				return ['erro' => $bloq];
			}

			$id = (int)($post['sel'] ?? 0);
			if ($id === 0) {
				return ['erro' => 'Seleccione um instrumento.'];
			}

			$removido = $this->remover($id);
			if ($removido) {
				LogsController::registar('Removeu o instrumento ID ' . $id);
			}
			return $removido
				? ['msg' => 'Instrumento removido.']
				: ['erro' => 'Não foi possível remover (o instrumento está associado a músicos).'];
		}

		return [];
	}

	// Lista já filtrada pela pesquisa em memória.
	public function listarComPesquisa($q)
	{
		$instrumentos = $this->listarInstrumentos();
		$q = trim($q);
		if ($q === '') return $instrumentos;

		return array_values(array_filter($instrumentos, function ($i) use ($q) {
			$alvo = mb_strtolower($i->getCodigo() . ' ' . $i->getNome());
			return str_contains($alvo, mb_strtolower($q));
		}));
	}
}
