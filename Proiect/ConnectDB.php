<?php
/**
 * Clasa ConnectDB pentru realizarea conexiunii cu baza de date
 */
class ConnectDB {
    private $server;
    private $database;
    private $username;
    private $password;
    private $port;
    private $conn;

    /**
     * Constructor pentru clasa ConnectDB
     */
    public function __construct() {
        $this->server = "PAW";
        $this->database = "ca280";
        $this->username = "ca280";
        $this->password = "2kc8a1wt";
        $this->port = "3306";
        $this->conn = null;
    }

    /**
     * Metoda pentru conectarea la baza de date
     * @return mysqli|null Conexiunea la baza de date sau null în caz de eroare
     */
    public function connect() {
        try {
            $this->conn = new mysqli(
                $this->server, 
                $this->username, 
                $this->password, 
                $this->database, 
                $this->port
            );

            // Verifică dacă există vreo eroare de conexiune
            if ($this->conn->connect_error) {
                throw new Exception("Conexiune eșuată: " . $this->conn->connect_error);
            }
            
            // Setăm caracterele utf8
            $this->conn->set_charset("utf8");
            
            return $this->conn;
        } catch (Exception $e) {
            // Afișăm mesajul de eroare
            echo "Eroare: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Metoda pentru închiderea conexiunii
     */
    public function closeConnection() {
        if ($this->conn != null) {
            $this->conn->close();
        }
    }
}
?>