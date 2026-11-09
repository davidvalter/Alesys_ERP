<?php
// public/repositories/FinanceiroContaBancariaRepository.php
class FinanceiroContaBancariaRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function bancoExiste(int $codigoBanco): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM FinanceiroContasBancarias
            WHERE Banco = :banco
            LIMIT 1
        ");
        $stmt->execute(['banco' => $codigoBanco]);
        return (bool) $stmt->fetchColumn();
    }
}
