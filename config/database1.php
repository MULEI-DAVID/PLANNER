<?php
class Database {
    private $host = 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com';
    private $db_name = 'test';
    private $username = 'wusPZjoBXzkSiuV.root';
    private $password = '9qMwNf8jR9VNpMM3';
    private $port = 4000;
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            // PDO connection with SSL (no certificate file required)
            $options = [
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];
            
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $exception) {
            error_log("Database Connection Error: " . $exception->getMessage());
            die("Connection error: " . $exception->getMessage());
        }
        
        return $this->conn;
    }
    
    public function closeConnection() {
        $this->conn = null;
    }
}
?>
