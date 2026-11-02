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
    private $utilizadorController;
    private $nivelController;

    public function __construct()
    {
        $this->utilizadorController = new UtilizadorController();
        $this->nivelController      = new NivelController();
    }

    /**
     * Executa a acção submetida.
     * Devolve ['msg' => ..., 'tab' => ...].
     */
    public function processarPost($utilizador, $post, $tabAtual)
    {
        if ($bloq = Permissao::bloquear($utilizador, 'utilizadores')) {
            return ['msg' => $bloq, 'tab' => $tabAtual];
        }

        $msg = '';
        $tab = $tabAtual;
        $acao = $post['acao'] ?? '';

        if ($acao === 'salvar') {
            $foto = $this->lerFoto('foto');
            $novo = new Utilizador(
                null,
                trim($post['nome'] ?? ''),
                trim($post['apelido'] ?? ''),
                trim($post['username'] ?? ''),
                $post['genero'] ?? null,
                new NivelAcesso((int)($post['perfil'] ?? 0), ''),
                trim($post['email'] ?? ''),
                trim($post['telefone'] ?? ''),
                "PeixeFrito",
                true,   // primeiro acesso
                $foto['dados'] ?? null,
                $foto['tipo'] ?? null
            );
            $criado = $this->utilizadorController->cadastrarUtilizador($novo);
            if ($criado) {
                LogsController::registar('Criou o utilizador: ' . trim($post['nome'] ?? '') . " " . trim($post['apelido'] ?? ''));
            }
            $msg = $criado
                ? 'Utilizador criado. '
                : 'Erro ao criar (o nome de utilizador já existe?).';
        }

        if ($acao === 'atualizar') {
            $tab   = 'lista';
            $id    = (int)($post['id'] ?? 0);
            $atual = $this->utilizadorController->listarPorId($id);
            $foto  = $this->lerFoto('foto');
            $edit  = new Utilizador(
                null,
                null,
                null,
                null,
                null,
                new NivelAcesso((int)($post['perfil'] ?? 0), ''),
                trim($post['email'] ?? ''),
                trim($post['telefone'] ?? ''),
                null,
                null,
                $foto['dados'] ?? null,   // null = mantem a foto actual
                $foto['tipo'] ?? null
            );
            $this->utilizadorController->atualizarUser($id, $edit);
            LogsController::registar('Actualizou o utilizador: ' . $atual->getNomeCompleto());
            $msg = 'Utilizador atualizado.';
        }

        if ($acao === 'remover') {
            $tab = 'lista';
            $id  = (int)($post['sel'] ?? 0);
            if ($id === 0) {
                $msg = 'Seleccione um utilizador.';
            } elseif (($alvo = $this->utilizadorController->listarPorId($id)) && $alvo->getUser_name() === $utilizador->getUser_name()) {
                $msg = 'Não pode remover o seu próprio utilizador.';
            } else {
                $this->utilizadorController->suspenderUtilizador($id);
                LogsController::registar('Removeu o utilizador: ' . ($alvo ? $alvo->getNomeCompleto() : 'ID ' . $id));
                $msg = 'Utilizador removido.';
            }
        }

        if ($acao === 'resetar') {
            $tab = 'lista';
            $id  = (int)($post['sel'] ?? 0);
            if ($id === 0) {
                $msg = 'Seleccione um utilizador.';
            } else {
                $alvo = $this->utilizadorController->listarPorId($id);
                $nova = "Maguinhas123"; //VOU TROCAR
                $this->utilizadorController->resetarSenha($id, $nova);
                LogsController::registar('Resetou a senha do utilizador: ' . ($alvo ? $alvo->getNomeCompleto() : 'ID ' . $id));
                $msg = 'Senha resetada.';
            }
        }

        /* ----- Perfis ----- */
        if ($acao === 'criar_perfil') {
            $tab  = 'perfis';
            $nome = trim($post['nome_perfil'] ?? '');
            if ($nome === '') {
                $msg = 'O nome do perfil é obrigatório.';
            } elseif ($this->nivelController->criar($nome, $post['perm'] ?? [])) {
                LogsController::registar('Criou o perfil: ' . $nome);
                $msg = 'Perfil criado.';
            } else {
                $msg = 'Erro ao criar o perfil.';
            }
        }

        if ($acao === 'atualizar_perfil') {
            $tab  = 'perfis';
            $id   = (int)($post['id_perfil'] ?? 0);
            $nome = trim($post['nome_perfil'] ?? '');
            if ($nome === '') {
                $msg = 'O nome do perfil é obrigatório.';
            } elseif ($this->nivelController->atualizar($id, $nome, $post['perm'] ?? [])) {
                LogsController::registar('Actualizou o perfil: ' . $nome);
                $msg = 'Perfil atualizado.';
            } else {
                $msg = 'Não foi possível atualizar (os perfis base não se editam).';
            }
        }

        if ($acao === 'remover_perfil') {
            $tab = 'perfis';
            $id  = (int)($post['sel_perfil'] ?? 0);
            if ($id === 0) {
                $msg = 'Seleccione um perfil.';
            } elseif ($this->nivelController->remover($id)) {
                LogsController::registar('Removeu o perfil ID ' . $id);
                $msg = 'Perfil removido.';
            } else {
                $msg = 'Não foi possível remover (perfil base ou com utilizadores associados).';
            }
        }

        return ['msg' => $msg, 'tab' => $tab];
    }

    /**
     * Prepara tudo o que a página precisa para ser desenhada.
     */
    public function prepararEcra($utilizador, $get, $tabAtual = null, $msgAtual = null)
    {
        $msg = $msgAtual ?? '';

        $tab = $tabAtual;
        if (!in_array($tab, ['cad', 'lista', 'perfis'], true)) {
            $tab = in_array($get['tab'] ?? '', ['cad', 'lista', 'perfis'], true) ? $get['tab'] : 'cad';
        }

        /* ---------- Botão Editar: abre o modal preenchido ---------- */
        $ed = null;
        if (($get['acao'] ?? '') === 'editar') {
            $tab = 'lista';
            $ed  = $this->utilizadorController->listarPorId((int)($get['sel'] ?? 0));
            if (!$ed) $msg = 'Seleccione um utilizador.';
        }

        /* ---------- Botão Editar Perfil: abre o modal preenchido ---------- */
        $edPerfil = null;
        if (($get['acao'] ?? '') === 'editar_perfil') {
            $tab = 'perfis';
            $edPerfil = $this->nivelController->buscarPorCodigo((int)($get['sel_perfil'] ?? 0));
            if (!$edPerfil) {
                $msg = 'Seleccione um perfil.';
            } elseif ($edPerfil['CodigoNivel'] <= 4) {
                $msg = 'Os perfis base não se editam.';
                $edPerfil = null;
            }
        }

        $lista = $this->utilizadorController->listarUtilizador();
        $meuCodigo = 0;
        foreach ($lista as $u) {
            if ($u->getUser_name() === $utilizador->getUser_name()) $meuCodigo = $u->getCodigo();
        }
        $niveis = $this->nivelController->listarNiveis();
        $perfis = $this->nivelController->listarPerfis();

        /* ---------- Pesquisa simples (filtra em memória as duas listas) ---------- */
        $q = trim($get['q'] ?? '');
        if ($q !== '') {
            $qMin = mb_strtolower($q);

            $lista = array_values(array_filter($lista, function ($u) use ($qMin) {
                $alvo = mb_strtolower(
                    $u->getCodigo() . ' ' . $u->getNomeCompleto() . ' ' . $u->getUser_name() . ' ' .
                    $u->getEmail() . ' ' . $u->getGenero() . ' ' . $u->getPerfil()->getNome() . ' ' .
                    $u->getContacto()
                );
                return str_contains($alvo, $qMin);
            }));

            $perfis = array_values(array_filter($perfis, function ($p) use ($qMin) {
                // Inclui o nome das permissões que o perfil tem (ex.: "logs", "editar")
                $perms = '';
                if ($p['PodeLer'])          $perms .= ' listar';
                if ($p['PodeAdicionar'])    $perms .= ' criar adicionar';
                if ($p['PodeEditar'])       $perms .= ' editar';
                if ($p['PodeRemover'])      $perms .= ' remover';
                if ($p['PodeUtilizadores']) $perms .= ' utilizadores gerir';
                if ($p['PodeLogs'])         $perms .= ' logs';
                $alvo = mb_strtolower($p['CodigoNivel'] . ' ' . $p['NomeNivel'] . $perms);
                return str_contains($alvo, $qMin);
            }));

            // Se a aba actual ficou sem resultados e a outra tem, muda para a outra
            if (!$ed && !$edPerfil) {
                if (($tab === 'lista' || $tab === 'cad') && !$lista && $perfis) $tab = 'perfis';
                elseif (($tab === 'perfis' || $tab === 'cad') && !$perfis && $lista) $tab = 'lista';
            }
        }

        return compact('msg', 'tab', 'ed', 'edPerfil', 'lista', 'meuCodigo', 'niveis', 'perfis', 'q');
    }

    /**
     * Lê a foto enviada (valida o conteúdo) e devolve ['dados' => binário, 'tipo' => mime] ou null.
     */
    private function lerFoto($campo)
    {
        if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] !== UPLOAD_ERR_OK) return null;

        $tmp     = $_FILES[$campo]['tmp_name'];
        $tamanho = (int) $_FILES[$campo]['size'];
        $maximo  = 2 * 1024 * 1024; // igual ao upload_max_filesize

        if ($tamanho <= 0 || $tamanho > $maximo) return null;

        $info = @getimagesize($tmp);
        if ($info === false) return null; // não é uma imagem válida

        $tipos = [
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_PNG  => 'image/png',
            IMAGETYPE_WEBP => 'image/webp',
        ];
        if (!isset($tipos[$info[2]])) return null;

        return ['dados' => file_get_contents($tmp), 'tipo' => $tipos[$info[2]]];
    }
}
