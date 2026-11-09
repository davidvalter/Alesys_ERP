<?php
// public/utils/OfxParser.php

class OfxParser
{
    private string $logFile = __DIR__ . '/../logs/ofx_parser.log';

    /**
     * Método principal
     */
    public function parse(string $filePath): array
    {
        try {
            if (!file_exists($filePath)) {
                throw new Exception('Arquivo OFX não encontrado');
            }

            $raw = file_get_contents($filePath);
            if (!$raw) {
                throw new Exception('Arquivo OFX vazio ou ilegível');
            }

            // Normaliza encoding
            if (!mb_check_encoding($raw, 'UTF-8')) {
                $raw = mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1');
            }

            return [
                'banco' => $this->extractGlobalTag($raw, 'BANKID'),
                'agencia' => $this->extractGlobalTag($raw, 'BRANCHID'),
                'conta' => $this->extractGlobalTag($raw, 'ACCTID'),
                'transacoes' => $this->extractTransactions($raw),
            ];

        } catch (Exception $e) {
            $this->logError($e, $filePath);
            throw $e;
        }
    }

    /**
     * Extrai transações STMTTRN diretamente do OFX
     */
    private function extractTransactions(string $ofx): array
    {
        $transactions = [];

        if (!preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/is', $ofx, $matches)) {
            throw new Exception('Nenhuma transação encontrada no OFX');
        }

        foreach ($matches[1] as $block) {

            $amount = (float) str_replace(',', '.', $this->extractTag($block, 'TRNAMT'));
            $date = $this->extractTag($block, 'DTPOSTED');
            $memo = trim($this->extractTag($block, 'MEMO'));
            $name = trim($this->extractTag($block, 'NAME'));

            if ($memo !== '' && $name !== '' && $memo !== $name) {
                $descricao = $memo . ' - ' . $name;
            } else {
                $descricao = $memo !== '' ? $memo : $name;
            }

            $transactions[] = [
                'fitid' => $this->extractTag($block, 'FITID'),
                'data' => $this->formatDate($date),
                'descricao' => $descricao,
                'valor' => abs($amount),
                'tipo' => $amount < 0 ? 'DEBITO' : 'CREDITO',
                'raw_valor' => $amount,
            ];
        }

        return $transactions;
    }

    /**
     * Extrai tag simples dentro de um bloco
     */
    private function extractTag(string $block, string $tag): string
    {
        if (preg_match('/<' . $tag . '>([^<\r\n]+)/i', $block, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    /**
     * Extrai tag única do OFX inteiro (BANKID, ACCTID, etc)
     */
    private function extractGlobalTag(string $ofx, string $tag): ?string
    {
        if (preg_match('/<' . $tag . '>([^<\r\n]+)/i', $ofx, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    /**
     * Converte data OFX (YYYYMMDD ou YYYYMMDDHHMMSS)
     */
    private function formatDate(string $ofxDate): ?string
    {
        if (strlen($ofxDate) < 8) {
            return null;
        }

        return substr($ofxDate, 0, 4) . '-' .
            substr($ofxDate, 4, 2) . '-' .
            substr($ofxDate, 6, 2);
    }

    /**
     * Loga apenas erros
     */
    private function logError(Exception $e, string $filePath): void
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $msg = sprintf(
            "[%s] Erro OFX | Mensagem: %s | Arquivo: %s | Linha: %s | OFX: %s\n",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            basename($filePath)
        );

        file_put_contents($this->logFile, $msg, FILE_APPEND);
    }
}
