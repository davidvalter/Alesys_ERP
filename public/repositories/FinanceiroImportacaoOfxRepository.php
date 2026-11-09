<?php
// public/repositories/FinanceiroImportacaoOfxRepository.php
class FinanceiroImportacoesOfxRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function criar(array $dados): int
    {
        $sql = "
            INSERT INTO FinanceiroImportacaoOfx
            (NomeArquivo, HashArquivo, Banco, Conta, UsuarioId, Status)
            VALUES
            (:NomeArquivo, :HashArquivo, :Banco, :Conta, :UsuarioId, 'importado')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($dados);

        return (int) $this->db->lastInsertId();
    }

    public function marcarErro(int $id, string $obs): void
    {
        $stmt = $this->db->prepare("
            UPDATE FinanceiroImportacaoOfx
            SET Status = 'erro', Observacoes = :obs
            WHERE Id = :id
        ");
        $stmt->execute([
            'obs' => $obs,
            'id' => $id
        ]);
    }

    public function marcarProcessado(
        int $id,
        int $totalLancamentos,
        float $totalEntradas,
        float $totalSaidas
    ): void {
        $stmt = $this->db->prepare("
        UPDATE FinanceiroImportacaoOfx
        SET 
            Status = 'processado',
            Observacoes = :obs
        WHERE Id = :id
    ");

        $obs = sprintf(
            "Importação concluída. Lançamentos: %d | Entradas: %.2f | Saídas: %.2f",
            $totalLancamentos,
            $totalEntradas,
            $totalSaidas
        );

        $stmt->execute([
            'obs' => $obs,
            'id' => $id
        ]);
    }
}
