<?php
class Database {
    private $host = 'gateway01.eu-central-1.prod.aws.tidbcloud.com';
    private $db_name = 'test';
    private $username = '2t8zMoS1YYFaQgs.root';


    
    private $password = 'Z8k2eXxb0JrGw6oV';
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>

