<?php
/**
 * Configuration de la base de données
 * Connexion PDO sécurisée avec gestion d'erreurs
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Configuration de la base de données
    private $host = 'localhost';
    private $dbname = 'facebook_clone';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log("Erreur de connexion à la base de données: " . $e->getMessage());
            throw new Exception("Erreur de connexion à la base de données");
        }
    }
    
    /**
     * Pattern Singleton pour éviter les connexions multiples
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtenir la connexion PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Exécuter une requête préparée
     */
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    /**
     * Exécuter une requête simple
     */
    public function query($sql) {
        return $this->connection->query($sql);
    }
    
    /**
     * Démarrer une transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Valider une transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Annuler une transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Obtenir le dernier ID inséré
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Fermer la connexion
     */
    public function close() {
        $this->connection = null;
        self::$instance = null;
    }
    
    /**
     * Vérifier si la connexion est active
     */
    public function isConnected() {
        return $this->connection !== null;
    }
}

// Fonction utilitaire pour obtenir la connexion
function getDB() {
    return Database::getInstance()->getConnection();
}

// Fonction utilitaire pour obtenir l'instance de la base de données
function getDatabase() {
    return Database::getInstance();
}
