<?php
// configs/database.php

class DatabaseConfig {
    // Configurações do banco de dados
    private static $config = [
        'host' => 'localhost',
        'dbname' => '',
        'username' => '',
        'password' => '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ];

    // Instância única da conexão (Singleton)
    private static $instance = null;

    // Método privado para prevenir instanciação direta
    private function __construct() {}

    // Método para obter a conexão
    public static function getConnection() {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . self::$config['host'] . ";dbname=" . self::$config['dbname'] . ";charset=" . self::$config['charset'];
            
            try {
                self::$instance = new PDO(
                    $dsn,
                    self::$config['username'],
                    self::$config['password'],
                    self::$config['options']
                );
            } catch (PDOException $e) {
                // Log seguro do erro
                error_log('Database connection error: ' . $e->getMessage());
                
                // Mensagem genérica para o usuário
                throw new Exception('Could not connect to the database. Please try again later.');
            }
        }
        
        return self::$instance;
    }

    // Prevenir clonagem
    private function __clone() {}
    
    // Prevenir desserialização - AGORA COM VISIBILIDADE PÚBLICA
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Função auxiliar para facilitar o uso
function getDB() {
    return DatabaseConfig::getConnection();
}
?>