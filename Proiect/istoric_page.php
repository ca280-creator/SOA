<?php
require_once 'ConnectDB.php'; // Include clasa ta de conexiune

/**
 * Clasa pentru gestionarea tabelului istoric_utilizare
 */
class IstoricUtilizareManager {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new ConnectDB();
        $this->conn = $this->db->connect();
    }
    
    /**
     * Obține toate înregistrările din tabel cu opțiuni de sortare și filtrare
     */
    public function getRecords($sortColumn = 'id', $sortOrder = 'ASC', $searchRole = '') {
        $allowedColumns = ['id', 'utilizator', 'rol', 'data_ora', 'actiune', 'operatia_sql'];
        $allowedOrders = ['ASC', 'DESC'];
        
        // Validare parametri
        if (!in_array($sortColumn, $allowedColumns)) {
            $sortColumn = 'id';
        }
        if (!in_array($sortOrder, $allowedOrders)) {
            $sortOrder = 'ASC';
        }
        
        $sql = "SELECT * FROM istoric_utilizare";
        
        // Adaugă condiția de căutare după rol
        if (!empty($searchRole)) {
            $sql .= " WHERE rol = ?";
        }
        
        $sql .= " ORDER BY $sortColumn $sortOrder";
        
        try {
            if (!empty($searchRole)) {
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("s", $searchRole);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $this->conn->query($sql);
            }
            
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            echo "Eroare la obținerea datelor: " . $e->getMessage();
            return [];
        }
    }
    
    /**
     * Șterge o înregistrare din tabel
     */
    public function deleteRecord($id) {
        $sql = "DELETE FROM istoric_utilizare WHERE id = ?";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            echo "Eroare la ștergerea înregistrării: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Afișează tabelul cu datele și funcționalitățile
     */
    public function displayTable() {
        // Procesează cererile POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['delete_id'])) {
                $this->deleteRecord($_POST['delete_id']);
                echo "<div class='alert alert-success'>Înregistrarea a fost ștearsă cu succes!</div>";
            }
        }
        
        // Obține parametrii pentru sortare și căutare
        $sortColumn = $_GET['sort'] ?? 'id';
        $sortOrder = $_GET['order'] ?? 'ASC';
        $searchRole = $_GET['search_role'] ?? '';
        
        $records = $this->getRecords($sortColumn, $sortOrder, $searchRole);
        
        echo $this->generateHTML($records, $sortColumn, $sortOrder, $searchRole);
    }
    
    /**
     * Generează HTML-ul pentru tabel
     */
    private function generateHTML($records, $sortColumn, $sortOrder, $searchRole) {
        $nextOrder = ($sortOrder === 'ASC') ? 'DESC' : 'ASC';
        
        $html = '
        <!DOCTYPE html>
        <html lang="ro">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Istoric Utilizare</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    background-color: #f5f5f5;
                }
                
                .container {
                    max-width: 2200px;
                    margin: 0 auto;
                    background-color: white;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                
                h1 {
                    color: #333;
                    text-align: center;
                    margin-bottom: 30px;
                }
                
                .search-container {
                    margin-bottom: 20px;
                    text-align: center;
                }
                
                .search-btn {
                    background-color: #007bff;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    margin: 0 10px;
                    border-radius: 5px;
                    cursor: pointer;
                    text-decoration: none;
                    display: inline-block;
                    font-size: 14px;
                }
                
                .search-btn:hover {
                    background-color: #0056b3;
                }
                
                .search-btn.admin {
                    background-color: #dc3545;
                }
                
                .search-btn.admin:hover {
                    background-color: #c82333;
                }
                
                .search-btn.analist {
                    background-color: #28a745;
                }
                
                .search-btn.analist:hover {
                    background-color: #218838;
                }
                
                .clear-btn {
                    background-color: #6c757d;
                }
                
                .clear-btn:hover {
                    background-color: #545b62;
                }
                
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                
                th {
                    background-color: #000000 !important;
                    color: white;
                    padding: 12px;
                    text-align: left;
                    cursor: pointer;
                    position: relative;
                }
                
                th:hover {
                    background-color: #333333 !important;
                }
                
                th a {
                    color: white;
                    text-decoration: none;
                    display: block;
                    width: 100%;
                    height: 100%;
                }
                
                .sort-indicator {
                    font-size: 12px;
                    margin-left: 5px;
                }
                
                td {
                    padding: 10px 12px;
                    border-bottom: 1px solid #ddd;
                }
                
                tr:nth-child(even) {
                    background-color: #f8f9fa;
                }
                
                tr:hover {
                    background-color: #e8f4f8;
                }
                
                .delete-btn {
                    background-color: #dc3545;
                    color: white;
                    border: none;
                    padding: 5px 10px;
                    border-radius: 3px;
                    cursor: pointer;
                    font-size: 12px;
                }
                
                .delete-btn:hover {
                    background-color: #c82333;
                }
                
                .alert {
                    padding: 15px;
                    margin-bottom: 20px;
                    border: 1px solid transparent;
                    border-radius: 4px;
                }
                
                .alert-success {
                    color: #155724;
                    background-color: #d4edda;
                    border-color: #c3e6cb;
                }
                
                .operatia-sql {
                    max-width: 300px;
                    word-wrap: break-word;
                    font-family: monospace;
                    font-size: 12px;
                }
                
                .no-records {
                    text-align: center;
                    padding: 40px;
                    color: #666;
                    font-style: italic;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Istoric Utilizare</h1>
                
                <div class="search-container">
                    <a href="?" class="search-btn clear-btn">Toate înregistrările</a>
                    <a href="?search_role=admin" class="search-btn admin">Caută Admin</a>
                    <a href="?search_role=analist" class="search-btn analist">Caută Analist</a>
                </div>';
        
        if (empty($records)) {
            $html .= '<div class="no-records">Nu au fost găsite înregistrări.</div>';
        } else {
            $html .= '
                <table>
                    <thead>
                        <tr>
                            <th>
                                <a href="?sort=id&order=' . $nextOrder . ($searchRole ? '&search_role=' . urlencode($searchRole) : '') . '">
                                    ID ' . ($sortColumn === 'id' ? '<span class="sort-indicator">' . ($sortOrder === 'ASC' ? '↑' : '↓') . '</span>' : '') . '
                                </a>
                            </th>
                            <th>
                                <a href="?sort=utilizator&order=' . $nextOrder . ($searchRole ? '&search_role=' . urlencode($searchRole) : '') . '">
                                    Utilizator ' . ($sortColumn === 'utilizator' ? '<span class="sort-indicator">' . ($sortOrder === 'ASC' ? '↑' : '↓') . '</span>' : '') . '
                                </a>
                            </th>
                            <th>
                                <a href="?sort=rol&order=' . $nextOrder . ($searchRole ? '&search_role=' . urlencode($searchRole) : '') . '">
                                    Rol ' . ($sortColumn === 'rol' ? '<span class="sort-indicator">' . ($sortOrder === 'ASC' ? '↑' : '↓') . '</span>' : '') . '
                                </a>
                            </th>
                            <th>
                                <a href="?sort=data_ora&order=' . $nextOrder . ($searchRole ? '&search_role=' . urlencode($searchRole) : '') . '">
                                    Data/Ora ' . ($sortColumn === 'data_ora' ? '<span class="sort-indicator">' . ($sortOrder === 'ASC' ? '↑' : '↓') . '</span>' : '') . '
                                </a>
                            </th>
                            <th>
                                <a href="?sort=actiune&order=' . $nextOrder . ($searchRole ? '&search_role=' . urlencode($searchRole) : '') . '">
                                    Acțiune ' . ($sortColumn === 'actiune' ? '<span class="sort-indicator">' . ($sortOrder === 'ASC' ? '↑' : '↓') . '</span>' : '') . '
                                </a>
                            </th>
                            <th>
                                <a href="?sort=operatia_sql&order=' . $nextOrder . ($searchRole ? '&search_role=' . urlencode($searchRole) : '') . '">
                                    Operația SQL ' . ($sortColumn === 'operatia_sql' ? '<span class="sort-indicator">' . ($sortOrder === 'ASC' ? '↑' : '↓') . '</span>' : '') . '
                                </a>
                            </th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($records as $record) {
                $html .= '
                        <tr>
                            <td>' . htmlspecialchars($record['id']) . '</td>
                            <td>' . htmlspecialchars($record['utilizator']) . '</td>
                            <td>' . htmlspecialchars($record['rol']) . '</td>
                            <td>' . htmlspecialchars($record['data_ora']) . '</td>
                            <td>' . htmlspecialchars($record['actiune']) . '</td>
                            <td class="operatia-sql">' . htmlspecialchars($record['operatia_sql']) . '</td>
                            <td>
                                <form method="POST" style="display: inline;" onsubmit="return confirm(\'Sigur doriți să ștergeți această înregistrare?\')">
                                    <input type="hidden" name="delete_id" value="' . $record['id'] . '">
                                    <button type="submit" class="delete-btn">Șterge</button>
                                </form>
                            </td>
                        </tr>';
            }
            
            $html .= '
                    </tbody>
                </table>';
        }
        
        $html .= '
            </div>
            
            <script>
                // Confirmă ștergerea
                function confirmDelete(id) {
                    return confirm("Sigur doriți să ștergeți această înregistrare?");
                }
            </script>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Destructor pentru închiderea conexiunii
     */
    public function __destruct() {
        if ($this->db) {
            $this->db->closeConnection();
        }
    }
}

// Utilizare
$manager = new IstoricUtilizareManager();
$manager->displayTable();
?>