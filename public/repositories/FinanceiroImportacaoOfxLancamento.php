<?php
// public/repositories/FinanceiroImportacaoOfxLancamento.php
class FinanceiroImportacaoOfxLancamentoRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function inserir(array $dados): void
    {
        $sql = "
            INSERT IGNORE INTO FinanceiroImportacaoOfxLancamento
            (
                ImportacaoOfxId,
                FitId,
                DataLancamento,
                Tipo,
                Valor,
                ValorOriginal,
                Descricao,
                HashLinha,
                Status
            ) VALUES (
                :ImportacaoOfxId,
                :FitId,
                :DataLancamento,
                :Tipo,
                :Valor,
                :ValorOriginal,
                :Descricao,
                :HashLinha,
                'pendente'
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($dados);
    }
}
