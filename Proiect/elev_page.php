<?php
// Include clasa ConnectDB
require_once 'ConnectDB.php';

/**
 * Clasa SearchEvaluare pentru căutarea în baza de date evaluarenationala
 */
class SearchEvaluare {
    private $db;
    private $conn;

    /**
     * Constructor pentru clasa SearchEvaluare
     */
    public function __construct() {
        $this->db = new ConnectDB();
        $this->conn = $this->db->connect();
    }

    /**
     * Metoda pentru căutarea unui record după ID
     * @param int $id ID-ul căutat
     * @return array|null Datele găsite sau null dacă nu există
     */
    public function searchById($id) {
        if ($this->conn === null) {
            return null;
        }

        // Pregătim query-ul pentru a evita SQL injection
        $stmt = $this->conn->prepare("SELECT * FROM evaluarenationala WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }

    /**
     * Metoda pentru afișarea formularului de căutare
     */
    public function displaySearchForm() {
        ?>
        <!DOCTYPE html>
        <html lang="ro">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Căutare Evaluare Națională</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    max-width: 1000px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #f5f5f5;
                }
                
                .search-container {
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    margin-bottom: 20px;
                }
                
                .search-box {
                    display: flex;
                    gap: 10px;
                    align-items: center;
                }
                
                input[type="number"] {
                    padding: 10px;
                    border: 2px solid #ddd;
                    border-radius: 4px;
                    font-size: 16px;
                    width: 200px;
                }
                
                input[type="submit"] {
                    padding: 10px 20px;
                    background-color: #007bff;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 16px;
                }
                
                input[type="submit"]:hover {
                    background-color: #0056b3;
                }
                
                table {
                    width: 100%;
                    border-collapse: collapse;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                th {
                    background-color: #000000;
                    color: white;
                    padding: 12px;
                    text-align: left;
                    font-weight: bold;
                }
                
                td {
                    padding: 12px;
                    border-bottom: 1px solid #ddd;
                }
                
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                
                .no-results {
                    text-align: center;
                    color: #666;
                    font-style: italic;
                    padding: 20px;
                }
                
                .error {
                    color: #dc3545;
                    background-color: #f8d7da;
                    border: 1px solid #f5c6cb;
                    padding: 10px;
                    border-radius: 4px;
                    margin: 10px 0;
                }
            </style>
        </head>
        <body>
            <h1>Căutare Evaluare Națională</h1>
            
            <div class="search-container">
                <form method="POST" action="">
                    <div class="search-box">
                        <label for="search_id">Introduceți ID-ul:</label>
                        <input type="number" 
                               id="search_id" 
                               name="search_id" 
                               min="1" 
                               placeholder="ID-ul căutat"
                               value="<?php echo isset($_POST['search_id']) ? htmlspecialchars($_POST['search_id']) : ''; ?>"
                               required>
                        <input type="submit" name="search" value="Caută">
                    </div>
                </form>
            </div>
            
            <?php
            // Procesăm căutarea doar dacă formularul a fost trimis
            if (isset($_POST['search']) && isset($_POST['search_id'])) {
                $searchId = intval($_POST['search_id']);
                $result = $this->searchById($searchId);
                
                if ($result) {
                    $this->displayResults($result);
                } else {
                    echo '<div class="error">Nu au fost găsite rezultate pentru ID-ul ' . $searchId . '</div>';
                }
            }
            ?>
            
        </body>
        </html>
        <?php
    }

    /**
     * Metoda pentru afișarea rezultatelor căutării
     * @param array $data Datele de afișat
     */
    private function displayResults($data) {
        ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nume</th>
                    <th>Gen</th>
                    <th>Vârsta</th>
                    <th>Localitate</th>
                    <th>Limba Română</th>
                    <th>Matematică</th>
                    <th>Școala</th>
                    <th>Media</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($data['id']); ?></td>
                    <td><?php echo htmlspecialchars($data['nume']); ?></td>
                    <td><?php echo htmlspecialchars($data['gen']); ?></td>
                    <td><?php echo htmlspecialchars($data['varsta']); ?></td>
                    <td><?php echo htmlspecialchars($data['localitate']); ?></td>
                    <td><?php echo htmlspecialchars($data['lb_romana']); ?></td>
                    <td><?php echo htmlspecialchars($data['matematica']); ?></td>
                    <td><?php echo htmlspecialchars($data['scoala']); ?></td>
                    <td><?php echo htmlspecialchars($data['media']); ?></td>
                </tr>
            </tbody>
        </table>
        <?php
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

// Utilizarea clasei
$searchEvaluare = new SearchEvaluare();
$searchEvaluare->displaySearchForm();
?>