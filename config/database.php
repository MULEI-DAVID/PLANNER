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
            // Initialize mysqli
            $this->conn = mysqli_init();
            
            if (!$this->conn) {
                throw new Exception("mysqli_init failed");
            }
            
            // Enable SSL (no certs required for TiDB Serverless)
            $this->conn->ssl_set(null, null, null, null, null);
            
            // Connect using SSL
            $connected = $this->conn->real_connect(
                $this->host,
                $this->username,
                $this->password,
                $this->db_name,
                $this->port,
                null,
                MYSQLI_CLIENT_SSL
            );
            
            if (!$connected) {
                throw new Exception("Connection failed: " . mysqli_connect_error());
            }
            
            // Set charset to utf8mb4 (recommended)
            $this->conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            echo "Connection Error: " . $e->getMessage();
            return null;
        }
        
        return $this->conn;
    }
    
    // Optional: Close connection method
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
