<?php
class Database {
    private $host = 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com';
    private $db_name = 'test';
    private $username = 'wusPZjoBXzkSiuV.root';
    private $password = '9qMwNf8jR9VNpMM3';
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        

        
        return $this->conn;
    }
}
?>
