<?php
session_start();
require_once 'ConnectDB.php';

/**
 * Clasa AdminPage pentru gestionarea elevilor la evaluarea națională
 */
class AdminPage {
    private $conn;
    private $db;

    /**
     * Constructor pentru clasa AdminPage
     */
    public function __construct() {
        $this->db = new ConnectDB();
        $this->conn = $this->db->connect();
    }

    /**
     * Metodă pentru a căuta elevi în baza de date
     * @param array $criteria Criteriile de căutare
     * @param string $sortColumn Coloana pentru sortare
     * @param string $sortOrder Ordinea sortării ('asc' sau 'desc')
     * @return array Rezultatele căutării
     */
    public function searchStudents($criteria, $sortColumn = 'id', $sortOrder = 'asc') {
        $query = "SELECT * FROM evaluarenationala WHERE 1=1";
        $params = [];
        $types = "";

        // Construim query-ul în funcție de criteriile primite
        if (!empty($criteria['id'])) {
            $query .= " AND id = ?";
            $params[] = $criteria['id'];
            $types .= "i";
        }
        if (!empty($criteria['nume'])) {
            $query .= " AND nume LIKE ?";
            $params[] = "%" . $criteria['nume'] . "%";
            $types .= "s";
        }
        if (!empty($criteria['gen'])) {
            $query .= " AND gen = ?";
            $params[] = $criteria['gen'];
            $types .= "s";
        }
        if (!empty($criteria['varsta'])) {
            $query .= " AND varsta = ?";
            $params[] = $criteria['varsta'];
            $types .= "i";
        }
        if (!empty($criteria['localitate'])) {
            $query .= " AND localitate LIKE ?";
            $params[] = "%" . $criteria['localitate'] . "%";
            $types .= "s";
        }
        
        // Procesăm intervalul pentru lb_romana
        if (!empty($criteria['lb_romana'])) {
            $romanaValue = $criteria['lb_romana'];
            // Verificăm dacă e un interval (conține "-")
            if (strpos($romanaValue, "-") !== false) {
                $range = explode("-", $romanaValue);
                if (count($range) == 2) {
                    $min = trim($range[0]);
                    $max = trim($range[1]);
                    $query .= " AND lb_romana >= ? AND lb_romana <= ?";
                    $params[] = $min;
                    $params[] = $max;
                    $types .= "dd";
                } else {
                    // Dacă formatul intervalului e invalid, tratăm ca o valoare simplă
                    $query .= " AND lb_romana = ?";
                    $params[] = $romanaValue;
                    $types .= "d";
                }
            } else {
                // Valoare simplă (ex: "6")
                $query .= " AND lb_romana = ?";
                $params[] = $romanaValue;
                $types .= "d";
            }
        }
        
        // Procesăm intervalul pentru matematica
        if (!empty($criteria['matematica'])) {
            $matematicaValue = $criteria['matematica'];
            // Verificăm dacă e un interval (conține "-")
            if (strpos($matematicaValue, "-") !== false) {
                $range = explode("-", $matematicaValue);
                if (count($range) == 2) {
                    $min = trim($range[0]);
                    $max = trim($range[1]);
                    $query .= " AND matematica >= ? AND matematica <= ?";
                    $params[] = $min;
                    $params[] = $max;
                    $types .= "dd";
                } else {
                    // Dacă formatul intervalului e invalid, tratăm ca o valoare simplă
                    $query .= " AND matematica = ?";
                    $params[] = $matematicaValue;
                    $types .= "d";
                }
            } else {
                // Valoare simplă (ex: "6")
                $query .= " AND matematica = ?";
                $params[] = $matematicaValue;
                $types .= "d";
            }
        }
        
        if (!empty($criteria['scoala'])) {
            $query .= " AND scoala = ?";
            $params[] = $criteria['scoala'];
            $types .= "i";
        }
        if (!empty($criteria['media'])) {
            $mediaValue = $criteria['media'];
            // Verificăm dacă e un interval (conține "-")
            if (strpos($mediaValue, "-") !== false) {
                $range = explode("-", $mediaValue);
                if (count($range) == 2) {
                    $min = trim($range[0]);
                    $max = trim($range[1]);
                    $query .= " AND media >= ? AND media <= ?";
                    $params[] = $min;
                    $params[] = $max;
                    $types .= "dd";
                } else {
                    // Dacă formatul intervalului e invalid, tratăm ca o valoare simplă
                    $query .= " AND media = ?";
                    $params[] = $mediaValue;
                    $types .= "d";
                }
            } else {
                // Valoare simplă (ex: "6")
                $query .= " AND media = ?";
                $params[] = $mediaValue;
                $types .= "d";
            }
        }

        // Adăugăm sortarea
        $allowedColumns = ['id', 'nume', 'gen', 'varsta', 'localitate', 'lb_romana', 'matematica', 'scoala', 'media'];
        if (in_array($sortColumn, $allowedColumns)) {
            $query .= " ORDER BY " . $sortColumn;
            $query .= ($sortOrder === 'desc') ? " DESC" : " ASC";
        } else {
            $query .= " ORDER BY id ASC"; // Sortare implicită
        }

        $stmt = $this->conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        
        $stmt->close();
        return $students;
    }

    /**
     * Închide conexiunea la baza de date
     */
    public function closeConnection() {
        $this->db->closeConnection();
    }
}

// Inițializare admin
$admin = new AdminPage();
$message = "";
$studentList = [];
$searchCriteria = [];

// Sortarea
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : (isset($_POST['sort']) ? $_POST['sort'] : 'id');
$sortOrder = isset($_GET['order']) ? $_GET['order'] : (isset($_POST['order']) ? $_POST['order'] : 'asc');

// Procesarea formularului
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Acțiunea de căutare
    if (isset($_POST['search']) || isset($_POST['sort_asc']) || isset($_POST['sort_desc'])) {
        $searchCriteria = [
            'id' => $_POST['id'] ?? '',
            'nume' => $_POST['nume'] ?? '',
            'gen' => $_POST['gen'] ?? '',
            'varsta' => $_POST['varsta'] ?? '',
            'localitate' => $_POST['localitate'] ?? '',
            'lb_romana' => $_POST['lb_romana'] ?? '',
            'matematica' => $_POST['matematica'] ?? '',
            'scoala' => $_POST['scoala'] ?? '',
            'media' => $_POST['media'] ?? ''
        ];
        
        // Determinăm sortarea în funcție de butonul apăsat
        if (isset($_POST['sort_asc'])) {
            $sortOrder = 'asc';
        } elseif (isset($_POST['sort_desc'])) {
            $sortOrder = 'desc';
        }
        
        $studentList = $admin->searchStudents($searchCriteria, $sortColumn, $sortOrder);
    }
}

// Încărcăm toți elevii la prima accesare
if (empty($studentList)) {
    $studentList = $admin->searchStudents([], $sortColumn, $sortOrder);
}

// Funcție pentru a genera URL-ul de sortare
function getSortUrl($column, $currentSortColumn, $currentSortOrder) {
    $order = 'asc';
    if ($column === $currentSortColumn && $currentSortOrder === 'asc') {
        $order = 'desc';
    }
    return '?sort=' . $column . '&order=' . $order;
}

// Funcție pentru a afișa săgeata de sortare
function getSortArrow($column, $currentSortColumn, $currentSortOrder) {
    if ($column === $currentSortColumn) {
        return ($currentSortOrder === 'asc') ? ' ▲' : ' ▼';
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrare Evaluare Națională</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .form-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-field {
            flex: 1;
            min-width: 200px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .note-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: flex-start;
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-search {
            background-color: #4CAF50;
            color: white;
        }
        .btn-sort-asc {
            background-color: #2196F3;
            color: white;
        }
        .btn-sort-desc {
            background-color: #FF9800;
            color: white;
        }
        button:hover {
            opacity: 0.9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #212529;
            color: white;
            cursor: pointer;
        }
        th a {
            color: white;
            text-decoration: none;
            display: block;
        }
        th a:hover {
            text-decoration: underline;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
            background-color: #e8f5e9;
            color: #388e3c;
        }
        .error {
            background-color: #ffebee;
            color: #c62828;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Administrare Evaluare Națională</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <div class="form-group">
                <div class="form-field">
                    <label for="id">ID:</label>
                    <input type="text" id="id" name="id" value="<?php echo isset($_POST['id']) ? htmlspecialchars($_POST['id']) : ''; ?>">
                </div>
                <div class="form-field">
                    <label for="nume">Nume:</label>
                    <input type="text" id="nume" name="nume" value="<?php echo isset($_POST['nume']) ? htmlspecialchars($_POST['nume']) : ''; ?>">
                </div>
                <div class="form-field">
                    <label for="gen">Gen:</label>
                    <select id="gen" name="gen">
                        <option value="">Selectați</option>
                        <option value="M" <?php echo (isset($_POST['gen']) && $_POST['gen'] == 'M') ? 'selected' : ''; ?>>Masculin</option>
                        <option value="F" <?php echo (isset($_POST['gen']) && $_POST['gen'] == 'F') ? 'selected' : ''; ?>>Feminin</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="varsta">Vârstă:</label>
                    <input type="number" id="varsta" name="varsta" value="<?php echo isset($_POST['varsta']) ? htmlspecialchars($_POST['varsta']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-field">
                    <label for="localitate">Localitate:</label>
                    <input type="text" id="localitate" name="localitate" value="<?php echo isset($_POST['localitate']) ? htmlspecialchars($_POST['localitate']) : ''; ?>">
                </div>
                <div class="form-field">
                    <label for="lb_romana">Limba Română:</label>
                    <input type="text" id="lb_romana" name="lb_romana" value="<?php echo isset($_POST['lb_romana']) ? htmlspecialchars($_POST['lb_romana']) : ''; ?>">
                    <div class="note-hint">Puteți introduce o valoare sau un interval (ex: 6-9)</div>
                </div>
                <div class="form-field">
                    <label for="matematica">Matematică:</label>
                    <input type="text" id="matematica" name="matematica" value="<?php echo isset($_POST['matematica']) ? htmlspecialchars($_POST['matematica']) : ''; ?>">
                    <div class="note-hint">Puteți introduce o valoare sau un interval (ex: 6-9)</div>
                </div>
                <div class="form-field">
                    <label for="scoala">Școala:</label>
                    <input type="number" id="scoala" name="scoala" value="<?php echo isset($_POST['scoala']) ? htmlspecialchars($_POST['scoala']) : ''; ?>">
                </div>
                <div class="form-field">
                    <label for="media">Media:</label>
                    <input type="text" id="media" name="media" value="<?php echo isset($_POST['media']) ? htmlspecialchars($_POST['media']) : ''; ?>">
                    <div class="note-hint">Puteți introduce o valoare sau un interval (ex: 6-9)</div>
                </div>
            </div>

            <div class="buttons">
                <button type="submit" name="search" class="btn-search">Caută</button>
                <button type="submit" name="sort_asc" class="btn-sort-asc">Sortare Ascendentă</button>
                <button type="submit" name="sort_desc" class="btn-sort-desc">Sortare Descendentă</button>
                <!-- Inputuri ascunse pentru parametrii de sortare -->
                <input type="hidden" name="sort" id="sort-column" value="<?php echo htmlspecialchars($sortColumn); ?>">
                <input type="hidden" name="order" id="sort-order" value="<?php echo htmlspecialchars($sortOrder); ?>">
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th data-column="id"><a href="<?php echo getSortUrl('id', $sortColumn, $sortOrder); ?>">ID<?php echo getSortArrow('id', $sortColumn, $sortOrder); ?></a></th>
                    <th data-column="nume"><a href="<?php echo getSortUrl('nume', $sortColumn, $sortOrder); ?>">Nume<?php echo getSortArrow('nume', $sortColumn, $sortOrder); ?></a></th>
                    <th data-column="gen"><a href="<?php echo getSortUrl('gen', $sortColumn, $sortOrder); ?>">Gen<?php echo getSortArrow('gen', $sortColumn, $sortOrder); ?></a></th>
                    <th data-column="varsta"><a href="<?php echo getSortUrl('varsta', $sortColumn, $sortOrder); ?>">Vârstă<?php echo getSortArrow('varsta', $sortColumn, $sortOrder); ?></a></th>
                    <th data-column="localitate"><a href="<?php echo getSortUrl('localitate', $sortColumn, $sortOrder); ?>">Localitate<?php echo getSortArrow('localitate', $sortColumn, $sortOrder); ?></a></th>
                    <th data-column="lb_romana"><a href="<?php echo getSortUrl('lb_romana', $sortColumn, $sortOrder); ?>">Lb. Română<?php echo getSortArrow('lb_romana', $sortColumn, $sortOrder); ?></a></th>
                    <th data-column="matematica"><a href="<?php echo getSortUrl('matematica', $sortColumn, $sortOrder); ?>">Matematică<?php echo getSortArrow('matematica', $sortColumn, $sortOrder); ?></a></th>
                    <th data-column="scoala"><a href="<?php echo getSortUrl('scoala', $sortColumn, $sortOrder); ?>">Școala<?php echo getSortArrow('scoala', $sortColumn, $sortOrder); ?></a></th>
                    <th data-column="media"><a href="<?php echo getSortUrl('media', $sortColumn, $sortOrder); ?>">Media<?php echo getSortArrow('media', $sortColumn, $sortOrder); ?></a></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($studentList)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center;">Nu s-au găsit rezultate.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($studentList as $student): ?>
                        <tr>
                            <td><?php echo $student['id']; ?></td>
                            <td><?php echo htmlspecialchars($student['nume']); ?></td>
                            <td><?php echo $student['gen']; ?></td>
                            <td><?php echo $student['varsta']; ?></td>
                            <td><?php echo htmlspecialchars($student['localitate']); ?></td>
                            <td><?php echo $student['lb_romana']; ?></td>
                            <td><?php echo $student['matematica']; ?></td>
                            <td><?php echo $student['scoala']; ?></td>
                            <td><?php echo $student['media']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Adăugăm funcționalitate pentru a selecta o coloană pentru sortare când se face click pe header
        document.addEventListener('DOMContentLoaded', function() {
            const tableHeaders = document.querySelectorAll('th[data-column]');
            const sortColumnInput = document.getElementById('sort-column');
            const sortOrderInput = document.getElementById('sort-order');
            
            tableHeaders.forEach(function(header) {
                header.addEventListener('click', function(e) {
                    const column = this.getAttribute('data-column');
                    let order = 'asc';
                    
                    // Dacă facem click pe aceeași coloană după care se sortează deja
                    if (sortColumnInput.value === column) {
                        // Inversăm ordinea
                        order = sortOrderInput.value === 'asc' ? 'desc' : 'asc';
                    }
                    
                    // Actualizăm valorile inputurilor ascunse
                    sortColumnInput.value = column;
                    sortOrderInput.value = order;
                    
                    // Trimitem formularul pentru a face sortarea
                    document.querySelector('form').submit();
                });
            });
            
            // Asigurăm că butoanele de sortare rețin coloana selectată
            document.querySelector('.btn-sort-asc').addEventListener('click', function() {
                sortOrderInput.value = 'asc';
            });
            
            document.querySelector('.btn-sort-desc').addEventListener('click', function() {
                sortOrderInput.value = 'desc';
            });
        });
    </script>
</body>
</html>

<?php
// Închidem conexiunea la final
$admin->closeConnection();
?>