<?php
class Database {
    private $host = 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com';
    private $db_name = 'test';
    private $username = 'wusPZjoBXzkSiuV.root';
    private $password = '9qMwNf8jR9VNpMM3';
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            // TiDB Cloud REQUIRES SSL - use built-in SSL without certificate file
            $options = [
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            // Add sslmode parameter to DSN
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};port=4000;charset=utf8mb4;sslmode=require",
                $this->username,
                $this->password,
                $options
            );
            
        } catch (PDOException $exception) {
            // Log the error (in production, log to file instead of displaying)
            error_log("Database Connection Error: " . $exception->getMessage());
            
            // Display error for debugging (remove in production)
            die("Connection error: " . $exception->getMessage());
        }
        
        return $this->conn;
    }
}
?>
