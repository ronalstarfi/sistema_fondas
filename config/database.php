<?php
// config/database.php

class Database
{
    private $host = "localhost";
    private $db_name = "sistema_fondas";
    private $username = "root";
    private $password = "PARALELEPIPEDO3312";
    public $conn;

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES utf8mb4");
        } catch (PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
            $this->conn = null;
        }
        return $this->conn;
    }
}
?>