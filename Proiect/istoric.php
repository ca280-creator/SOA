<?php
require_once 'ConnectDB.php';

/**
 * Clasa Istoric pentru gestionarea istoricului de utilizare a aplicației
 */
class Istoric {
    private $conn;
    private $db;

    /**
     * Constructor pentru clasa Istoric
     */
    public function __construct() {
        $this->db = new ConnectDB();
        $this->conn = $this->db->connect();
    }

    /**
     * Înregistrează o operație în istoric
     * @param string $utilizator Numele utilizatorului
     * @param string $rol Rolul utilizatorului
     * @param string $operatia_logica Descrierea operației logice
     * @param string $comanda_sql Comanda SQL executată
     * @param string $tabela Tabelul afectat (opțional)
     * @param string $detalii Detalii suplimentare (opțional)
     * @return bool Rezultatul operației
     */
    public function inregistreazaOperatie($utilizator, $rol, $operatia_logica, $comanda_sql, $tabela = null, $detalii = null) {
        try {
            $query = "INSERT INTO istoric_utilizare (data_ora, utilizator, rol, operatia_logica, comanda_sql, tabela, detalii) 
                      VALUES (NOW(), ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Eroare la pregătirea query-ului: " . $this->conn->error);
            }
            
            $stmt->bind_param("ssssss", $utilizator, $rol, $operatia_logica, $comanda_sql, $tabela, $detalii);
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Eroare la înregistrarea în istoric: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obține istoricul cu filtrare și paginare
     * @param array $filtru Criterii de filtrare
     * @param int $pagina Pagina curentă
     * @param int $pe_pagina Numărul de înregistrări per pagină
     * @return array Array cu rezultatele și informații despre paginare
     */
    public function getIstoric($filtru = [], $pagina = 1, $pe_pagina = 50) {
        try {
            // Query pentru numărarea totalului de înregistrări
            $countQuery = "SELECT COUNT(*) as total FROM istoric_utilizare WHERE 1=1";
            $query = "SELECT * FROM istoric_utilizare WHERE 1=1";
            $params = [];
            $types = "";

            // Construim filtrele
            if (!empty($filtru['utilizator'])) {
                $countQuery .= " AND utilizator LIKE ?";
                $query .= " AND utilizator LIKE ?";
                $params[] = "%" . $filtru['utilizator'] . "%";
                $types .= "s";
            }
            
            if (!empty($filtru['rol'])) {
                $countQuery .= " AND rol = ?";
                $query .= " AND rol = ?";
                $params[] = $filtru['rol'];
                $types .= "s";
            }
            
            if (!empty($filtru['operatia_logica'])) {
                $countQuery .= " AND operatia_logica LIKE ?";
                $query .= " AND operatia_logica LIKE ?";
                $params[] = "%" . $filtru['operatia_logica'] . "%";
                $types .= "s";
            }
            
            if (!empty($filtru['tabela'])) {
                $countQuery .= " AND tabela = ?";
                $query .= " AND tabela = ?";
                $params[] = $filtru['tabela'];
                $types .= "s";
            }
            
            if (!empty($filtru['data_de_la'])) {
                $countQuery .= " AND DATE(data_ora) >= ?";
                $query .= " AND DATE(data_ora) >= ?";
                $params[] = $filtru['data_de_la'];
                $types .= "s";
            }
            
            if (!empty($filtru['data_pana_la'])) {
                $countQuery .= " AND DATE(data_ora) <= ?";
                $query .= " AND DATE(data_ora) <= ?";
                $params[] = $filtru['data_pana_la'];
                $types .= "s";
            }

            // Obținem totalul
            $countStmt = $this->conn->prepare($countQuery);
            if (!empty($params)) {
                $countStmt->bind_param($types, ...$params);
            }
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $total = $countResult->fetch_assoc()['total'];
            $countStmt->close();

            // Calculăm offset-ul pentru paginare
            $offset = ($pagina - 1) * $pe_pagina;
            $query .= " ORDER BY data_ora DESC LIMIT ? OFFSET ?";
            $params[] = $pe_pagina;
            $params[] = $offset;
            $types .= "ii";

            // Executăm query-ul principal
            $stmt = $this->conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $istoric = [];
            while ($row = $result->fetch_assoc()) {
                $istoric[] = $row;
            }
            $stmt->close();

            return [
                'data' => $istoric,
                'total' => $total,
                'pagina_curenta' => $pagina,
                'pe_pagina' => $pe_pagina,
                'total_pagini' => ceil($total / $pe_pagina)
            ];
        } catch (Exception $e) {
            error_log("Eroare la obținerea istoricului: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'pagina_curenta' => 1,
                'pe_pagina' => $pe_pagina,
                'total_pagini' => 0
            ];
        }
    }

    /**
     * Șterge înregistrările din istoric mai vechi de un anumit număr de zile
     * @param int $zile Numărul de zile
     * @return bool Rezultatul operației
     */
    public function curataIstoric($zile = 90) {
        try {
            $query = "DELETE FROM istoric_utilizare WHERE data_ora < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $zile);
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Eroare la curățarea istoricului: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obține statistici despre utilizarea aplicației
     * @param string $perioada Perioada pentru statistici ('today', 'week', 'month')
     * @return array Statisticile
     */
    public function getStatistici($perioada = 'today') {
        try {
            $whereClause = "";
            switch ($perioada) {
                case 'today':
                    $whereClause = "WHERE DATE(data_ora) = CURDATE()";
                    break;
                case 'week':
                    $whereClause = "WHERE data_ora >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $whereClause = "WHERE data_ora >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                default:
                    $whereClause = "WHERE DATE(data_ora) = CURDATE()";
            }

            // Statistici generale
            $queries = [
                'total_operatii' => "SELECT COUNT(*) as count FROM istoric_utilizare $whereClause",
                'utilizatori_activi' => "SELECT COUNT(DISTINCT utilizator) as count FROM istoric_utilizare $whereClause",
                'operatii_pe_rol' => "SELECT rol, COUNT(*) as count FROM istoric_utilizare $whereClause GROUP BY rol",
                'operatii_pe_tabela' => "SELECT tabela, COUNT(*) as count FROM istoric_utilizare $whereClause AND tabela IS NOT NULL GROUP BY tabela",
                'top_utilizatori' => "SELECT utilizator, COUNT(*) as count FROM istoric_utilizare $whereClause GROUP BY utilizator ORDER BY count DESC LIMIT 5"
            ];

            $statistici = [];
            
            foreach ($queries as $key => $query) {
                $result = $this->conn->query($query);
                if ($result) {
                    if (in_array($key, ['total_operatii', 'utilizatori_activi'])) {
                        $statistici[$key] = $result->fetch_assoc()['count'];
                    } else {
                        $statistici[$key] = $result->fetch_all(MYSQLI_ASSOC);
                    }
                } else {
                    $statistici[$key] = 0;
                }
            }

            return $statistici;
        } catch (Exception $e) {
            error_log("Eroare la obținerea statisticilor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Funcție helper pentru înregistrarea rapidă a operațiilor CRUD
     * @param string $utilizator Numele utilizatorului
     * @param string $rol Rolul utilizatorului
     * @param string $actiune Acțiunea (CREATE, READ, UPDATE, DELETE)
     * @param string $tabela Tabelul
     * @param string $id_inregistrare ID-ul înregistrării (opțional)
     * @param array $date_vechi Datele vechi (pentru UPDATE/DELETE)
     * @param array $date_noi Datele noi (pentru CREATE/UPDATE)
     * @return bool
     */
    public function logCRUD($utilizator, $rol, $actiune, $tabela, $id_inregistrare = null, $date_vechi = null, $date_noi = null) {
        $operatia_logica = "";
        $comanda_sql = "";
        $detalii = "";

        switch (strtoupper($actiune)) {
            case 'CREATE':
                $operatia_logica = "Adăugare înregistrare în tabela $tabela";
                $comanda_sql = "INSERT INTO $tabela";
                if ($date_noi) {
                    $detalii = "Date noi: " . json_encode($date_noi, JSON_UNESCAPED_UNICODE);
                }
                break;
            
            case 'READ':
                $operatia_logica = "Consultare date din tabela $tabela";
                $comanda_sql = "SELECT FROM $tabela";
                break;
            
            case 'UPDATE':
                $operatia_logica = "Modificare date în tabela $tabela";
                $comanda_sql = "UPDATE $tabela SET ... WHERE id = $id_inregistrare";
                if ($date_vechi && $date_noi) {
                    $detalii = "Date vechi: " . json_encode($date_vechi, JSON_UNESCAPED_UNICODE) . 
                              " | Date noi: " . json_encode($date_noi, JSON_UNESCAPED_UNICODE);
                }
                break;
            
            case 'DELETE':
                $operatia_logica = "Ștergere înregistrare din tabela $tabela";
                $comanda_sql = "DELETE FROM $tabela WHERE id = $id_inregistrare";
                if ($date_vechi) {
                    $detalii = "Date șterse: " . json_encode($date_vechi, JSON_UNESCAPED_UNICODE);
                }
                break;
        }

        return $this->inregistreazaOperatie($utilizator, $rol, $operatia_logica, $comanda_sql, $tabela, $detalii);
    }

    /**
     * Închide conexiunea la baza de date
     */
    public function closeConnection() {
        if ($this->db) {
            $this->db->closeConnection();
        }
    }

    /**
     * Destructor
     */
    public function __destruct() {
        $this->closeConnection();
    }
}
?>