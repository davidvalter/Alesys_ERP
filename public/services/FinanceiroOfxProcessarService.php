<?php
// public/services/FinanceiroOfxProcessarService.php

class FinanceiroOfxProcessarService
{
    private PDO $db;
    private FinanceiroImportacoesOfxRepository $importRepo;
    private FinanceiroImportacaoOfxLancamentoRepository $lancRepo;
    private FinanceiroContaBancariaRepository $contaRepo;

    public function __construct(
        PDO $db,
        FinanceiroImportacoesOfxRepository $importRepo,
        FinanceiroImportacaoOfxLancamentoRepository $lancRepo,
        FinanceiroContaBancariaRepository $contaRepo
    ) {
        $this->db = $db;
        $this->importRepo = $importRepo;
        $this->lancRepo = $lancRepo;
        $this->contaRepo = $contaRepo;
    }

    public function processar(array $payload, int $usuarioId): array
    {
        // ==========================
        // Normaliza banco (BANKID)
        // ==========================
        $bancoOfx = preg_replace('/\D/', '', (string) ($payload['banco'] ?? ''));
        $bancoOfx = ltrim($bancoOfx, '0');

        if (!$bancoOfx) {
            throw new Exception('Banco não identificado no OFX');
        }

        if (!$this->contaRepo->bancoExiste((int) $bancoOfx)) {
            throw new Exception("Banco {$bancoOfx} não cadastrado");
        }

        $hashArquivo = sha1(json_encode($payload['transacoes']));

        $this->db->beginTransaction();

        try {

            // 📁 Cria importação
            $importacaoId = $this->importRepo->criar([
                'NomeArquivo' => $payload['nome_arquivo'],
                'HashArquivo' => $hashArquivo,
                'Banco' => $bancoOfx,
                'Conta' => $payload['conta'],
                'UsuarioId' => $usuarioId
            ]);

            $totalLancamentos = 0;
            $totalEntradas = 0.0;
            $totalSaidas = 0.0;

            foreach ($payload['transacoes'] as $trn) {

                // Ignora saldos
                if (stripos($trn['descricao'], 'SALDO TOTAL') !== false) {
                    continue;
                }

                $tipo = $trn['tipo'] === 'CREDITO' ? 'entrada' : 'saida';

                if ($tipo === 'entrada') {
                    $totalEntradas += $trn['valor'];
                } else {
                    $totalSaidas += abs($trn['valor']);
                }

                $hashLinha = sha1(
                    $importacaoId .
                    $trn['fitid'] .
                    $trn['data'] .
                    $trn['raw_valor']
                );

                $this->lancRepo->inserir([
                    'ImportacaoOfxId' => $importacaoId,
                    'FitId' => $trn['fitid'],
                    'DataLancamento' => $trn['data'],
                    'Tipo' => $tipo,
                    'Valor' => abs($trn['valor']),
                    'ValorOriginal' => $trn['raw_valor'],
                    'Descricao' => $trn['descricao'],
                    'HashLinha' => $hashLinha
                ]);

                $totalLancamentos++;
            }

            //// ✅ Marca como processado
            //$this->importRepo->marcarProcessado(
            //    $importacaoId,
            //    $totalLancamentos,
            //    $totalEntradas,
            //    $totalSaidas
            //);

            $this->db->commit();

            return [
                'importacao_id' => $importacaoId,
                'lancamentos' => $totalLancamentos,
                'entradas' => $totalEntradas,
                'saidas' => $totalSaidas
            ];

        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
