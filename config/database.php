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
            // Connect without SSL
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};port=4000;charset=utf8mb4",
                $this->username,
                $this->password,
                $options
            );
            
        } catch (PDOException $exception) {
            // Log the error (in production, log to file instead of displaying)
            error_log("Database Connection Error: " . $exception->getMessage());
            
            // For development, you can uncomment this to see the error:
            // echo "Connection error: " . $exception->getMessage();
            
            // Return null - calling code must check for this
            return null;
        }
        
        return $this->conn;
    }
}
?>
