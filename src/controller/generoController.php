<?php 
require_once __DIR__ . '/../model/genero.php';
require_once __DIR__ . '/../model/permissao.php';
require_once __DIR__ . '/../repository/generorepository.php';
require_once __DIR__ . '/logsController.php';

 class GeneroController {

    private $generoDAO;

   public function __construct()
   { 
    $this->generoDAO = new GeneroDAO();
     }

    public function adicionarGenero($genero) {
        return $this->generoDAO->inserir($genero);
    }
    
    public function listarGeneros() {
        return $this->generoDAO->listarTodos();
            }
    
    public function removerGenero($codigo) {
    		return $this->generoDAO->remover($codigo);
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
                return ['erro' => 'O nome do género é obrigatório.'];
            }

            $novo = $this->adicionarGenero(new Genero(null, $nome));
            if ($novo > 0) {
                LogsController::registar('Registou o género: ' . $nome);
            }
            return ['msg' => $novo > 0 ? 'Género adicionado com sucesso.' : 'Erro ao adicionar (já existe?).'];
        }

        if ($acao === 'remover') {
            if ($bloq = Permissao::bloquear($utilizador, 'remover')) {
                return ['erro' => $bloq];
            }

            $id = (int)($post['sel'] ?? 0);
            if ($id === 0) {
                return ['erro' => 'Seleccione um género.'];
            }

            $removido = $this->removerGenero($id);
            if ($removido) {
                LogsController::registar('Removeu o género ID ' . $id);
            }
            return ['msg' => $removido
                ? 'Género removido.'
                : 'Não foi possível remover (o género está associado a discos).'];
        }

        return [];
    }

    // Lista já filtrada pela pesquisa em memória.
    public function listarComPesquisa($q)
    {
        $generos = $this->listarGeneros();
        $q = trim($q);
        if ($q === '') return $generos;

        return array_values(array_filter($generos, function ($g) use ($q) {
            $alvo = mb_strtolower($g->getCodigoGenero() . ' ' . $g->getNomeGenero());
            return str_contains($alvo, mb_strtolower($q));
        }));
    }
}