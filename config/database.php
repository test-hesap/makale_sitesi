<?php
// Hata ayıklama modu
error_reporting(E_ALL);
ini_set('display_errors', 1);

class Database {
    private $host = 'localhost';
    private $db_name = 'makale_sitesi';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    public $pdo;
    private $connected = false;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            $this->connected = true;
        } catch(PDOException $e) {
            $this->connected = false;
            throw new Exception("Veritabanı bağlantı hatası: " . $e->getMessage());
        }
    }
    
    public function isConnected() {
        return $this->connected;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function query($sql, $params = []) {
        if (!$this->connected) {
            throw new Exception("Veritabanı bağlantısı yok");
        }
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            throw new Exception("Sorgu hatası: " . $e->getMessage());
        }
    }
    
    public function prepare($sql) {
        if (!$this->connected) {
            throw new Exception("Veritabanı bağlantısı yok");
        }
        
        return $this->pdo->prepare($sql);
    }

    // Getter metodları
    public function getHost() {
        return $this->host;
    }

    public function getDbName() {
        return $this->db_name;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getPassword() {
        return $this->password;
    }
}
?> 