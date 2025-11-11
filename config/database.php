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
            // Build DSN (Data Source Name)
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            
            // PDO options with proper SSL configuration
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            // Create PDO connection with SSL
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Force SSL connection
            $this->conn->exec("SET SESSION sql_require_secure_transport=ON");
            
        } catch (PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
            return null;
        }
        
        return $this->conn;
    }
    
    // Optional: Close connection method
    public function closeConnection() {
        $this->conn = null;
    }
}
?>
