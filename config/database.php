<?php
class Database {
    private $host = "localhost";
    private $dbname = ""; 
    private $user = "root";
    private $pass = "";
    public $conn;

    public function getConnection() {
        try {
            $this->conn = new PDO("mysql:host=$this->host;dbname=$this->dbname", $this->user, $this->pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES utf8");
        } catch (PDOException $e) {
            echo "Database connection failed: " . $e->getMessage();
        }

        return $this->conn;
    }
}


date_default_timezone_set('Asia/Calcutta'); 


$base = "http://localhost/room-booking-system/";
?>
