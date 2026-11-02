<?php

require_once __DIR__ . '/../model/permissao.php';
require_once __DIR__ . '/../model/produtor.php';
require_once __DIR__ . '/../model/gravadora.php';
require_once __DIR__ . '/../model/editora.php';
require_once __DIR__ . '/produtorController.php';
require_once __DIR__ . '/gravadoraController.php';
require_once __DIR__ . '/editoraController.php';
require_once __DIR__ . '/logsController.php';

/**
 * Controller da página de produção.
 *
 * A página tem três abas (produtores, gravadoras, editoras), cada uma com o
 * mesmo conjunto de acções. Toda a validação, permissões e acesso aos dados
 * vive aqui; a view só recebe o estado pronto a renderizar.
 */
class ProducaoController
{
    private $produtorController;
    private $gravadoraController;
    private $editoraController;

    public function __construct()
    {
        $this->produtorController  = new ProdutorController();
        $this->gravadoraController = new GravadoraController();
        $this->editoraController   = new EditoraController();
    }

    /**
     * Executa a acção submetida e devolve a mensagem a apresentar.
     */
    public function processarPost($utilizador, $post)
    {
        $tipo = $post['tipo'] ?? '';
        $acao = $post['acao'] ?? '';

        $permissaoPedido = $acao === 'adicionar' ? 'adicionar'
            : ($acao === 'atualizar' ? 'editar'
            : ($acao === 'remover' ? 'remover' : ''));

        if ($permissaoPedido === '') {
            return 'Acção inválida.';
        }
        if ($bloq = Permissao::bloquear($utilizador, $permissaoPedido)) {
            return $bloq;
        }

        if ($tipo === 'produtor') {
            return $this->processarProdutor($acao, $post);
        }
        if ($tipo === 'gravadora') {
            return $this->processarGravadora($acao, $post);
        }
        if ($tipo === 'editora') {
            return $this->processarEditora($acao, $post);
        }

        return 'Tipo inválido.';
    }

    /**
     * Prepara tudo o que a página precisa para ser desenhada.
     */
    public function prepararEcra($get)
    {
        $msg = $get['msg'] ?? '';
        $tab = 'prod';
        $edProd = $edGrav = $edEdit = null;

        if (($get['acao'] ?? '') === 'editar') {
            $sel  = (int)($get['sel'] ?? 0);
            $tipo = $get['tipo'] ?? '';

            if ($tipo === 'produtor') {
                $edProd = $this->produtorController->buscarPorCodigo($sel);
                $tab = 'prod';
            } elseif ($tipo === 'gravadora') {
                $edGrav = $this->gravadoraController->buscarPorCodigo($sel);
                $tab = 'grav';
            } elseif ($tipo === 'editora') {
                $edEdit = $this->editoraController->buscarPorCodigo($sel);
                $tab = 'edit';
            }
            if (!$edProd && !$edGrav && !$edEdit) {
                $msg = 'Seleccione um registo para editar.';
            }
        }

        $produtores = $this->produtorController->listarProdutor();
        $gravadoras = $this->gravadoraController->listarGravadoras();
        $editoras   = $this->editoraController->listarEditoras();

        // Pesquisa simples (filtra em memória as 3 listas pelo texto introduzido)
        $q = trim($get['q'] ?? '');
        if ($q !== '') {
            $qMin = mb_strtolower($q);

            $produtores = array_values(array_filter($produtores, function ($p) use ($qMin) {
                $alvo = mb_strtolower(
                    $p->getCodigoProdutor() . ' ' . $p->getNomeCompleto() . ' ' .
                    $p->getContactoProdutor() . ' ' . $p->getEmailProdutor()
                );
                return str_contains($alvo, $qMin);
            }));

            $gravadoras = array_values(array_filter($gravadoras, function ($g) use ($qMin) {
                $alvo = mb_strtolower(
                    $g->getCodigoGravadora() . ' ' . $g->getNomeGravadora() . ' ' .
                    $g->getContactoGravadora() . ' ' . $g->getEmailGravadora() . ' ' .
                    $g->getEnderecoGravadora()
                );
                return str_contains($alvo, $qMin);
            }));

            $editoras = array_values(array_filter($editoras, function ($e) use ($qMin) {
                $alvo = mb_strtolower(
                    $e->getCodigoEditora() . ' ' . $e->getNomeEditora() . ' ' .
                    $e->getContactoEditora() . ' ' . $e->getEmailEditora() . ' ' .
                    $e->getEndereco()
                );
                return str_contains($alvo, $qMin);
            }));

            // Abre automaticamente a primeira aba que tenha resultados
            if (($get['acao'] ?? '') !== 'editar') {
                if ($produtores)      $tab = 'prod';
                elseif ($gravadoras)  $tab = 'grav';
                elseif ($editoras)    $tab = 'edit';
            }
        }

        return compact('msg', 'tab', 'edProd', 'edGrav', 'edEdit', 'q', 'produtores', 'gravadoras', 'editoras');
    }

    private function processarProdutor($acao, $post)
    {
        if ($acao === 'adicionar') {
            $nome    = trim($post['nome'] ?? '');
            $apelido = trim($post['apelido'] ?? '');
            $novo = $this->produtorController->cadastrarProdutor(new Produtor(
                null, $nome, $apelido,
                trim($post['contacto'] ?? ''), trim($post['email'] ?? '')
            ));
            if ($novo > 0) {
                LogsController::registar('Registou o produtor: ' . $nome . ' ' . $apelido);
            }
            return $novo > 0 ? 'Produtor adicionado.' : 'Erro ao adicionar produtor.';
        }

        if ($acao === 'atualizar') {
            $p = new Produtor(
                (int)$post['id'], trim($post['nome'] ?? ''), trim($post['apelido'] ?? ''),
                trim($post['contacto'] ?? ''), trim($post['email'] ?? '')
            );
            return $this->produtorController->atualizarProdutor($p) ? 'Produtor atualizado.' : 'Sem alterações.';
        }

        if ($acao === 'remover') {
            $id = (int)($post['sel'] ?? 0);
            $removido = $id > 0 && $this->produtorController->removerProdutor($id);
            if ($removido) {
                LogsController::registar('Removeu o produtor ID ' . $id);
            }
            return $removido
                ? 'Produtor removido.'
                : 'Seleccione um produtor que não esteja em discos.';
        }

        return 'Acção inválida.';
    }

    private function processarGravadora($acao, $post)
    {
        if ($acao === 'adicionar') {
            $nome = trim($post['nome'] ?? '');
            $novo = $this->gravadoraController->cadastrarGravadora(new Gravadora(
                null, $nome, trim($post['contacto'] ?? ''),
                trim($post['endereco'] ?? ''), trim($post['email'] ?? '')
            ));
            if ($novo > 0) {
                LogsController::registar('Registou a gravadora: ' . $nome);
            }
            return $novo > 0 ? 'Gravadora adicionada.' : 'Erro ao adicionar gravadora.';
        }

        if ($acao === 'atualizar') {
            $g = new Gravadora(
                (int)$post['id'], trim($post['nome'] ?? ''), trim($post['contacto'] ?? ''),
                trim($post['endereco'] ?? ''), trim($post['email'] ?? '')
            );
            return $this->gravadoraController->atualizarGravadora($g) ? 'Gravadora atualizada.' : 'Sem alterações.';
        }

        if ($acao === 'remover') {
            $id = (int)($post['sel'] ?? 0);
            $removido = $id > 0 && $this->gravadoraController->removerGravadora($id);
            if ($removido) {
                LogsController::registar('Removeu a gravadora ID ' . $id);
            }
            return $removido
                ? 'Gravadora removida.'
                : 'Seleccione uma gravadora que não esteja em discos.';
        }

        return 'Acção inválida.';
    }

    private function processarEditora($acao, $post)
    {
        if ($acao === 'adicionar') {
            $nome = trim($post['nome'] ?? '');
            $novo = $this->editoraController->cadastrarEditora(new Editora(
                null, $nome, trim($post['contacto'] ?? ''),
                trim($post['email'] ?? ''), trim($post['endereco'] ?? '')
            ));
            if ($novo > 0) {
                LogsController::registar('Registou a editora: ' . $nome);
            }
            return $novo > 0 ? 'Editora adicionada.' : 'Erro ao adicionar editora.';
        }

        if ($acao === 'atualizar') {
            $e = new Editora(
                (int)$post['id'], trim($post['nome'] ?? ''), trim($post['contacto'] ?? ''),
                trim($post['email'] ?? ''), trim($post['endereco'] ?? '')
            );
            return $this->editoraController->atualizarEditora($e) ? 'Editora atualizada.' : 'Sem alterações.';
        }

        if ($acao === 'remover') {
            $id = (int)($post['sel'] ?? 0);
            $removido = $id > 0 && $this->editoraController->removerEditora($id);
            if ($removido) {
                LogsController::registar('Removeu a editora ID ' . $id);
            }
            return $removido
                ? 'Editora removida.'
                : 'Seleccione uma editora que não esteja em discos.';
        }

        return 'Acção inválida.';
    }
}
