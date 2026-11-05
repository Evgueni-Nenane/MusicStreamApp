<?php

require_once __DIR__ . '/../repository/logs.php';
require_once __DIR__ . '/../model/logs.php';
require_once __DIR__ . '/../model/sessao.php';

class LogsController
{
    private $logsDAO;

    public function __construct()
    {
        $this->logsDAO = new LogsDAO();
    }

    public function inserirLog($log)
    {
        return $this->logsDAO->inserir($log);
    }

    public function listarLogs()
    {
        return $this->logsDAO->listarLogs();
    }

    /**
     * Lista os logs e filtra-os em memória pelo termo de pesquisa.
     */
    public function listarComPesquisa($termoPesquisa)
    {
        $logs = $this->listarLogs();
        $termoPesquisa = trim($termoPesquisa);

        if ($termoPesquisa === '') {
            return $logs;
        }

        $termoNormalizado = strtolower($termoPesquisa);

        return array_filter($logs, function ($log) use ($termoNormalizado) {
            $conteudo = strtolower(
                $log->getCodigo() . ' ' . $log->getNome() . ' ' . $log->getApelido() . ' ' .
                $log->getEmail() . ' ' . $log->getPerfil() . ' ' . $log->getAccao() . ' ' .
                $log->getDataHora()
            );

            return str_contains($conteudo, $termoNormalizado);
        });
    }

    /**
     * Regista uma acção do utilizador com sessão iniciada.
     * Uso: LogsController::registar('Registou o disco ID 5');
     */
    public static function registar($accao)
    {
        $utilizador = Sessao::getUtilizadorLogado();
        if (!$utilizador) {
            return false;
        }

        $log = new Logs(
            null,
            $utilizador->getNome(),
            $utilizador->getApelido(),
            $utilizador->getPerfil()->getNome(),
            $utilizador->getEmail(),
            $accao,
            date('Y-m-d H:i:s')
        );

        return (new LogsController())->inserirLog($log);
    }
}
