<?php

require_once __DIR__ . '/../model/permissao.php';
require_once __DIR__ . '/../model/musico.php';
require_once __DIR__ . '/../model/cantor.php';
require_once __DIR__ . '/../model/compositor.php';
require_once __DIR__ . '/musicoController.php';
require_once __DIR__ . '/cantorController.php';
require_once __DIR__ . '/compositorController.php';
require_once __DIR__ . '/instrumentoController.php';
require_once __DIR__ . '/logsController.php';

/**
 * Controller da página de artistas.
 *
 * Agrega os três tipos (músico, compositor, cantor) e concentra validação,
 * permissões, gravação, logs e preparação do estado da página.
 */
class ArtistasController
{
    private $musicoController;
    private $cantorController;
    private $compositorController;
    private $instrumentoController;

    private const ABAS = [
        'musico'     => 'musicos',
        'compositor' => 'compositores',
        'cantor'     => 'cantores',
    ];

    public function __construct()
    {
        $this->musicoController      = new MusicoController();
        $this->cantorController      = new CantorController();
        $this->compositorController  = new CompositorController();
        $this->instrumentoController = new InstrumentoController();
    }

    /**
     * Executa a acção submetida e devolve ['aba' => ..., 'msg' => ...].
     */
    public function processarPost($utilizador, $post)
    {
        $acao = $post['acao'] ?? '';
        $tipo = $post['tipo'] ?? '';
        $msg = '';

        if ($acao === 'cadastrar') {
            $msg = $this->cadastrar($utilizador, $tipo, $post);
        } elseif ($acao === 'atualizar') {
            $msg = $this->atualizar($utilizador, $tipo, $post);
        } elseif ($acao === 'remover') {
            $msg = $this->remover($utilizador, $tipo, $post);
        }

        $aba = self::ABAS[$tipo] ?? 'musicos';
        return ['aba' => $aba, 'msg' => $msg];
    }

    /**
     * Prepara tudo o que a página precisa para ser desenhada.
     */
    public function prepararEcra($get)
    {
        $msg = $get['msg'] ?? '';

        /* Aba visível */
        $tab = $get['tab'] ?? 'musicos';
        if (!in_array($tab, ['musicos', 'compositores', 'cantores'], true)) {
            $tab = 'musicos';
        }

        /* Botão Editar (GET): carrega o artista seleccionado */
        $ed = null;
        $edTipo = '';
        $edInstrumentos = [];

        if (($get['acao'] ?? '') === 'editar') {
            $edTipo = $get['tipo'] ?? '';
            // fica na aba onde o utilizador estava, mesmo que não tenha seleccionado ninguém
            $tab = self::ABAS[$edTipo] ?? $tab;
            $edId = (int)($get['sel_' . $edTipo] ?? 0);

            if ($edId === 0) { // compatibilidade com o formato antigo "tipo:id"
                [$tipoAntigo, $idAntigo] = array_pad(explode(':', $get['sel'] ?? ''), 2, '');
                if ($edTipo === '' && $tipoAntigo !== '') $edTipo = $tipoAntigo;
                $edId = (int)$idAntigo;
            }

            if ($edId > 0 && $edTipo === 'musico') {
                $ed = $this->musicoController->buscarPorCodigo($edId);
                if ($ed->getCodigoMusico()) {
                    foreach ($this->musicoController->buscarInstrumentosDoMusico($edId) as $i) {
                        $edInstrumentos[] = $i->getCodigo();
                    }
                    $tab = 'musicos';
                } else {
                    $ed = null;
                }
            } elseif ($edId > 0 && $edTipo === 'compositor') {
                $ed = $this->compositorController->buscarPorCodigo($edId);
                if ($ed->getCodigoCompositor()) $tab = 'compositores';
                else $ed = null;
            } elseif ($edId > 0 && $edTipo === 'cantor') {
                $ed = $this->cantorController->buscarPorCodigo($edId);
                if ($ed->getCodigoCantor()) $tab = 'cantores';
                else $ed = null;
            } else {
                $edTipo = '';
            }

            if (!$ed && $msg === '') $msg = 'Seleccione um artista.';
        }

        $musicos      = $this->musicoController->listarMusicos();
        $cantores     = $this->cantorController->listarCantor();
        $compositores = $this->compositorController->listarCompositor();
        $instrumentos = $this->instrumentoController->listarInstrumentos();

        /* Pesquisa simples (filtra em memória as 3 listas) */
        $q = trim($get['q'] ?? '');
        if ($q !== '') {
            $qMin = mb_strtolower($q);

            $musicos = array_values(array_filter($musicos, function ($m) use ($qMin) {
                $nomesInstr = array_map(
                    fn($i) => $i->getNome(),
                    $this->musicoController->buscarInstrumentosDoMusico($m->getCodigoMusico())
                );
                $alvo = mb_strtolower(
                    $m->getCodigoMusico() . ' ' . $m->getNomeMusico() . ' ' . $m->getApelidoMusico() . ' ' .
                    implode(' ', $nomesInstr) . ' ' . $m->getEmailMusico() . ' ' . $m->getContactoMusico()
                );
                return str_contains($alvo, $qMin);
            }));

            $compositores = array_values(array_filter($compositores, function ($c) use ($qMin) {
                $alvo = mb_strtolower(
                    $c->getCodigoCompositor() . ' ' . $c->getNomeCompositor() . ' ' . $c->getApelidoCompositor() . ' ' .
                    $c->getEmailCompositor() . ' ' . $c->getContactoCompositor()
                );
                return str_contains($alvo, $qMin);
            }));

            $cantores = array_values(array_filter($cantores, function ($c) use ($qMin) {
                $alvo = mb_strtolower(
                    $c->getCodigoCantor() . ' ' . $c->getNomeCantor() . ' ' . $c->getApelidoCantor() . ' ' .
                    $c->getEmailCantor() . ' ' . $c->getContactoCantor()
                );
                return str_contains($alvo, $qMin);
            }));

            // Se a aba actual ficou sem resultados, abre a primeira que tenha
            if (($get['acao'] ?? '') !== 'editar') {
                $listasPorAba = ['musicos' => $musicos, 'compositores' => $compositores, 'cantores' => $cantores];
                if (empty($listasPorAba[$tab])) {
                    foreach ($listasPorAba as $nomeAba => $lista) {
                        if ($lista) { $tab = $nomeAba; break; }
                    }
                }
            }
        }

        /* Campos do modal de edição (normaliza os 3 tipos de artista) */
        $edId = $edNome = $edApelido = $edContacto = $edEmail = '';
        if ($ed) {
            if ($edTipo === 'musico') {
                $edId = $ed->getCodigoMusico();
                $edNome = $ed->getNomeMusico();
                $edApelido = $ed->getApelidoMusico();
                $edContacto = $ed->getContactoMusico();
                $edEmail = $ed->getEmailMusico();
            } elseif ($edTipo === 'compositor') {
                $edId = $ed->getCodigoCompositor();
                $edNome = $ed->getNomeCompositor();
                $edApelido = $ed->getApelidoCompositor();
                $edContacto = $ed->getContactoCompositor();
                $edEmail = $ed->getEmailCompositor();
            } else {
                $edId = $ed->getCodigoCantor();
                $edNome = $ed->getNomeCantor();
                $edApelido = $ed->getApelidoCantor();
                $edContacto = $ed->getContactoCantor();
                $edEmail = $ed->getEmailCantor();
            }
        }
        $edTitulo = ['musico' => 'Músico', 'compositor' => 'Compositor', 'cantor' => 'Cantor'][$edTipo] ?? 'Artista';

        return compact(
            'msg', 'tab', 'ed', 'edTipo', 'edInstrumentos', 'edId', 'edNome', 'edApelido',
            'edContacto', 'edEmail', 'edTitulo', 'musicos', 'cantores', 'compositores',
            'instrumentos', 'q'
        );
    }

    private function cadastrar($utilizador, $tipo, $post)
    {
        if ($bloq = Permissao::bloquear($utilizador, 'adicionar')) {
            return $bloq;
        }

        $nome     = trim($post['nome'] ?? '');
        $apelido  = trim($post['apelido'] ?? '');
        $contacto = trim($post['contacto'] ?? '');
        $email    = trim($post['email'] ?? '');

        if ($nome === '' || $apelido === '') {
            return 'Preencha pelo menos o nome e o apelido.';
        }

        $id = -1;
        if ($tipo === 'musico') {
            $id = $this->musicoController->cadastrarMusico(
                new Musico(0, $nome, $apelido, [], $contacto, $email)
            );
            if ($id > 0) {
                foreach (($post['instrumentos'] ?? []) as $cod) {
                    $this->instrumentoController->inserirRelacaoMusicoInstrumento($id, (int)$cod);
                }
            }
        } elseif ($tipo === 'cantor') {
            $id = $this->cantorController->cadastrarCantor(new Cantor(0, $nome, $apelido, $contacto, $email));
        } elseif ($tipo === 'compositor') {
            $id = $this->compositorController->cadastrarCompositor(new Compositor(0, $nome, $apelido, $contacto, $email));
        } else {
            return 'Escolha o tipo de artista.';
        }

        if ($id > 0) {
            LogsController::registar('Registou o artista (' . $tipo . '): ' . $nome . ' ' . $apelido);
            return 'Artista cadastrado.';
        }
        return 'Erro ao cadastrar.';
    }

    private function atualizar($utilizador, $tipo, $post)
    {
        if ($bloq = Permissao::bloquear($utilizador, 'editar')) {
            return $bloq;
        }

        $id       = (int)($post['id'] ?? 0);
        $nome     = trim($post['nome'] ?? '');
        $apelido  = trim($post['apelido'] ?? '');
        $contacto = trim($post['contacto'] ?? '');
        $email    = trim($post['email'] ?? '');

        if ($id === 0 || $nome === '' || $apelido === '') {
            return 'Dados inválidos para atualizar.';
        }

        if ($tipo === 'musico') {
            $ok = $this->musicoController->atualizarMusico(
                new Musico($id, $nome, $apelido, [], $contacto, $email)
            );
            $this->instrumentoController->removerRelacoesPorMusico($id);
            foreach (($post['instrumentos'] ?? []) as $cod) {
                $this->instrumentoController->inserirRelacaoMusicoInstrumento($id, (int)$cod);
            }
            return $ok ? 'Músico atualizado.' : 'Sem alterações.';
        }

        if ($tipo === 'cantor') {
            $ok = $this->cantorController->atualizarCantor(new Cantor($id, $nome, $apelido, $contacto, $email));
            return $ok ? 'Cantor atualizado.' : 'Sem alterações.';
        }

        if ($tipo === 'compositor') {
            $ok = $this->compositorController->atualizarCompositor(new Compositor($id, $nome, $apelido, $contacto, $email));
            return $ok ? 'Compositor atualizado.' : 'Sem alterações.';
        }

        return 'Dados inválidos para atualizar.';
    }

    private function remover($utilizador, $tipo, $post)
    {
        if ($bloq = Permissao::bloquear($utilizador, 'remover')) {
            return $bloq;
        }

        // Cada aba selecciona pelo seu próprio name; mantém-se o formato antigo "tipo:id".
        $id = (int)($post['sel_' . $tipo] ?? 0);
        if ($id === 0) {
            [$tipoAntigo, $idAntigo] = array_pad(explode(':', $post['sel'] ?? ''), 2, '');
            if ($tipo === '' && $tipoAntigo !== '') $tipo = $tipoAntigo;
            $id = (int)$idAntigo;
        }

        if ($tipo === 'musico')          $ok = $id > 0 && $this->musicoController->removerMusico($id);
        elseif ($tipo === 'cantor')      $ok = $id > 0 && $this->cantorController->removerCantor($id);
        elseif ($tipo === 'compositor')  $ok = $id > 0 && $this->compositorController->removerCompositor($id);
        else                             $ok = false;

        if ($ok) {
            LogsController::registar('Removeu o artista (' . $tipo . ') ID ' . $id);
        }

        return $ok ? 'Artista removido.' : 'Não foi possível remover (seleccione um artista que não esteja em discos).';
    }
}
