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
        $options = [
            PDO::MYSQL_ATTR_SSL_CA => __DIR__ . "/ca.pem",
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        $this->conn = new PDO(
            "mysql:host={$this->host};dbname={$this->db_name};port=4000;charset=utf8mb4",
            $this->username,
            $this->password,
            $options
        );

    } catch (PDOException $exception) {
        echo "Connection error: " . $exception->getMessage();
    }

    return $this->conn;
}

}
?>

