<?php

require_once __DIR__ . '/../model/permissao.php';
require_once __DIR__ . '/../model/utilizador.php';
require_once __DIR__ . '/../model/nivelacesso.php';
require_once __DIR__ . '/utilizadorController.php';
require_once __DIR__ . '/nivelController.php';
require_once __DIR__ . '/logsController.php';

/**
 * Controller da página de administração.
 *
 * Trata dos utilizadores (criar, editar, remover, repor senha), dos perfis e
 * do upload de fotos. A view recebe o estado já pronto a renderizar.
 */
class AdministracaoController
{
    private const SENHA_PROVISORIA = 'Maguinhas123';

    private $utilizadorController;
    private $nivelController;

    public function __construct()
    {
        $this->utilizadorController = new UtilizadorController();
        $this->nivelController      = new NivelController();
    }

    /**
     * Executa a acção submetida.
     * Devolve ['msg' => ...] ou ['erro' => ...], sempre com 'tab'.
     */
    public function processarPost($utilizador, $post, $tabAtual)
    {
        if ($bloqueio = Permissao::bloquear($utilizador, 'utilizadores')) {
            return ['erro' => $bloqueio, 'tab' => $tabAtual];
        }

        return match ($post['acao'] ?? '') {
            'salvar' => $this->criarUtilizador($post, $tabAtual),
            'atualizar' => $this->atualizarUtilizador($post),
            'remover' => $this->removerUtilizador($utilizador, $post),
            'resetar' => $this->reporSenha($post),
            'criar_perfil' => $this->criarPerfil($post),
            'atualizar_perfil' => $this->atualizarPerfil($post),
            'remover_perfil' => $this->removerPerfil($post),
            default => ['msg' => '', 'tab' => $tabAtual],
        };
    }

    /**
     * Prepara tudo o que a página precisa para ser desenhada.
     */
    public function prepararEcra($utilizador, $get, $tabAtual = null, $resultadoPost = [])
    {
        $msg = $resultadoPost['msg'] ?? $get['msg'] ?? '';
        $erro = $resultadoPost['erro'] ?? $get['erro'] ?? '';

        $tab = $tabAtual;
        if (!in_array($tab, ['cad', 'lista', 'perfis'], true)) {
            $tab = in_array($get['tab'] ?? '', ['cad', 'lista', 'perfis'], true) ? $get['tab'] : 'cad';
        }

        /* ---------- Botão Editar: abre o modal preenchido ---------- */
        $utilizadorEdicao = null;
        if (($get['acao'] ?? '') === 'editar') {
            $tab = 'lista';
            $utilizadorEdicao = $this->utilizadorController->listarPorId((int) ($get['sel'] ?? 0));

            if (!$utilizadorEdicao) {
                $erro = 'Seleccione um utilizador.';
            }
        }

        /* ---------- Botão Editar Perfil: abre o modal preenchido ---------- */
        $perfilEdicao = null;
        if (($get['acao'] ?? '') === 'editar_perfil') {
            $tab = 'perfis';
            $perfilEdicao = $this->nivelController->buscarPorCodigo((int) ($get['sel_perfil'] ?? 0));

            if (!$perfilEdicao) {
                $erro = 'Seleccione um perfil.';
            } elseif ($perfilEdicao['CodigoNivel'] <= 4) {
                $erro = 'Os perfis base não se editam.';
                $perfilEdicao = null;
            }
        }

        $lista = $this->utilizadorController->listarUtilizadores();

        $meuCodigo = 0;
        foreach ($lista as $utilizadorLista) {
            if ($utilizadorLista->getUserName() === $utilizador->getUserName()) {
                $meuCodigo = $utilizadorLista->getCodigo();
            }
        }

        $niveis = $this->nivelController->listarNiveis();
        $perfis = $this->nivelController->listarPerfis();

        /* ---------- Pesquisa simples (filtra em memória as duas listas) ---------- */
        $termoPesquisa = trim($get['q'] ?? '');
        if ($termoPesquisa !== '') {
            $termoMinusculo = mb_strtolower($termoPesquisa);

            $lista = array_values(array_filter($lista, function ($utilizadorLista) use ($termoMinusculo) {
                $textoPesquisa = mb_strtolower(
                    $utilizadorLista->getCodigo() . ' ' . $utilizadorLista->getNomeCompleto() . ' ' .
                    $utilizadorLista->getUserName() . ' ' . $utilizadorLista->getEmail() . ' ' .
                    $utilizadorLista->getGenero() . ' ' . $utilizadorLista->getPerfil()->getNome() . ' ' .
                    $utilizadorLista->getContacto()
                );

                return str_contains($textoPesquisa, $termoMinusculo);
            }));

            $perfis = array_values(array_filter($perfis, function ($perfil) use ($termoMinusculo) {
                // Inclui o nome das permissões que o perfil tem (ex.: "logs", "editar").
                $permissoesTexto = '';
                if ($perfil['PodeLer']) {
                    $permissoesTexto .= ' listar';
                }
                if ($perfil['PodeAdicionar']) {
                    $permissoesTexto .= ' criar adicionar';
                }
                if ($perfil['PodeEditar']) {
                    $permissoesTexto .= ' editar';
                }
                if ($perfil['PodeRemover']) {
                    $permissoesTexto .= ' remover';
                }
                if ($perfil['PodeUtilizadores']) {
                    $permissoesTexto .= ' utilizadores gerir';
                }
                if ($perfil['PodeLogs']) {
                    $permissoesTexto .= ' logs';
                }

                $textoPesquisa = mb_strtolower($perfil['CodigoNivel'] . ' ' . $perfil['NomeNivel'] . $permissoesTexto);

                return str_contains($textoPesquisa, $termoMinusculo);
            }));

            // Se a aba actual ficou sem resultados e a outra tem, muda para a outra.
            if (!$utilizadorEdicao && !$perfilEdicao) {
                if (($tab === 'lista' || $tab === 'cad') && !$lista && $perfis) {
                    $tab = 'perfis';
                } elseif (($tab === 'perfis' || $tab === 'cad') && !$perfis && $lista) {
                    $tab = 'lista';
                }
            }
        }

        return [
            'msg' => $msg,
            'erro' => $erro,
            'tab' => $tab,
            'utilizadorEdicao' => $utilizadorEdicao,
            'perfilEdicao' => $perfilEdicao,
            'lista' => $lista,
            'meuCodigo' => $meuCodigo,
            'niveis' => $niveis,
            'perfis' => $perfis,
            'termoPesquisa' => $termoPesquisa,
        ];
    }

    private function criarUtilizador($post, $tabAtual)
    {
        $foto = $this->lerFoto('foto');
        $novo = new Utilizador(
            null,
            trim($post['nome'] ?? ''),
            trim($post['apelido'] ?? ''),
            trim($post['username'] ?? ''),
            $post['genero'] ?? null,
            new NivelAcesso((int) ($post['perfil'] ?? 0), ''),
            trim($post['email'] ?? ''),
            trim($post['telefone'] ?? ''),
            self::SENHA_PROVISORIA,
            true,   // primeiro acesso
            $foto['dados'] ?? null,
            $foto['tipo'] ?? null
        );

        $criado = $this->utilizadorController->cadastrarUtilizador($novo);

        if ($criado) {
            $nomeCompleto = trim($post['nome'] ?? '') . ' ' . trim($post['apelido'] ?? '');
            LogsController::registar('Criou o utilizador: ' . $nomeCompleto);
        }

        return [
            'msg' => $criado ? 'Utilizador criado.' : '',
            'erro' => $criado ? '' : 'Erro ao criar (o nome de utilizador já existe?).',
            'tab' => $tabAtual,
        ];
    }

    private function atualizarUtilizador($post)
    {
        $id = (int) ($post['id'] ?? 0);
        $utilizadorAtual = $this->utilizadorController->listarPorId($id);
        $foto = $this->lerFoto('foto');

        $utilizadorEditado = new Utilizador(
            null,
            null,
            null,
            null,
            null,
            new NivelAcesso((int) ($post['perfil'] ?? 0), ''),
            trim($post['email'] ?? ''),
            trim($post['telefone'] ?? ''),
            null,
            null,
            $foto['dados'] ?? null,   // null = mantém a foto actual
            $foto['tipo'] ?? null
        );

        $this->utilizadorController->atualizarUtilizador($id, $utilizadorEditado);

        if ($utilizadorAtual) {
            LogsController::registar('Actualizou o utilizador: ' . $utilizadorAtual->getNomeCompleto());
        }

        return ['msg' => 'Utilizador atualizado.', 'tab' => 'lista'];
    }

    private function removerUtilizador($utilizadorSessao, $post)
    {
        $id = (int) ($post['sel'] ?? 0);

        if ($id === 0) {
            return ['erro' => 'Seleccione um utilizador.', 'tab' => 'lista'];
        }

        $utilizadorRemovido = $this->utilizadorController->listarPorId($id);

        if ($utilizadorRemovido && $utilizadorRemovido->getUserName() === $utilizadorSessao->getUserName()) {
            return ['erro' => 'Não pode remover o seu próprio utilizador.', 'tab' => 'lista'];
        }

        $this->utilizadorController->removerUtilizador($id);

        $descricao = $utilizadorRemovido ? $utilizadorRemovido->getNomeCompleto() : 'ID ' . $id;
        LogsController::registar('Removeu o utilizador: ' . $descricao);

        return ['msg' => 'Utilizador removido.', 'tab' => 'lista'];
    }

    private function reporSenha($post)
    {
        $id = (int) ($post['sel'] ?? 0);

        if ($id === 0) {
            return ['erro' => 'Seleccione um utilizador.', 'tab' => 'lista'];
        }

        $utilizador = $this->utilizadorController->listarPorId($id);
        $this->utilizadorController->resetarSenha($id, self::SENHA_PROVISORIA);

        $descricao = $utilizador ? $utilizador->getNomeCompleto() : 'ID ' . $id;
        LogsController::registar('Resetou a senha do utilizador: ' . $descricao);

        return ['msg' => 'Senha resetada.', 'tab' => 'lista'];
    }

    private function criarPerfil($post)
    {
        $nome = trim($post['nome_perfil'] ?? '');

        if ($nome === '') {
            return ['erro' => 'O nome do perfil é obrigatório.', 'tab' => 'perfis'];
        }

        if (!$this->nivelController->criar($nome, $post['perm'] ?? [])) {
            return ['erro' => 'Erro ao criar o perfil.', 'tab' => 'perfis'];
        }

        LogsController::registar('Criou o perfil: ' . $nome);

        return ['msg' => 'Perfil criado.', 'tab' => 'perfis'];
    }

    private function atualizarPerfil($post)
    {
        $id = (int) ($post['id_perfil'] ?? 0);
        $nome = trim($post['nome_perfil'] ?? '');

        if ($nome === '') {
            return ['erro' => 'O nome do perfil é obrigatório.', 'tab' => 'perfis'];
        }

        if (!$this->nivelController->atualizar($id, $nome, $post['perm'] ?? [])) {
            return ['erro' => 'Não foi possível atualizar (os perfis base não se editam).', 'tab' => 'perfis'];
        }

        LogsController::registar('Actualizou o perfil: ' . $nome);

        return ['msg' => 'Perfil atualizado.', 'tab' => 'perfis'];
    }

    private function removerPerfil($post)
    {
        $id = (int) ($post['sel_perfil'] ?? 0);

        if ($id === 0) {
            return ['erro' => 'Seleccione um perfil.', 'tab' => 'perfis'];
        }

        if (!$this->nivelController->remover($id)) {
            return ['erro' => 'Não foi possível remover (perfil base ou com utilizadores associados).', 'tab' => 'perfis'];
        }

        LogsController::registar('Removeu o perfil ID ' . $id);

        return ['msg' => 'Perfil removido.', 'tab' => 'perfis'];
    }

    /**
     * Lê a foto enviada (valida o conteúdo) e devolve ['dados' => binário, 'tipo' => mime] ou null.
     */
    private function lerFoto($campo)
    {
        if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmp = $_FILES[$campo]['tmp_name'];
        $tamanho = (int) $_FILES[$campo]['size'];
        $maximo = 2 * 1024 * 1024; // igual ao upload_max_filesize

        if ($tamanho <= 0 || $tamanho > $maximo) {
            return null;
        }

        $info = @getimagesize($tmp);
        if ($info === false) {
            return null; // não é uma imagem válida
        }

        $tipos = [
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_PNG  => 'image/png',
            IMAGETYPE_WEBP => 'image/webp',
        ];

        if (!isset($tipos[$info[2]])) {
            return null;
        }

        return ['dados' => file_get_contents($tmp), 'tipo' => $tipos[$info[2]]];
    }
}
