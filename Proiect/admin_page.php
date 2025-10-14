<?php
session_start();
require_once 'ConnectDB.php';

/**
 * Clasa AdminPage pentru gestionarea elevilor la evaluarea națională
 */
class AdminPage {
    private $conn;
    private $db;
    private $utilizator = 'admin';
    private $rol = 'admin';

    /**
     * Constructor pentru clasa AdminPage
     */
    public function __construct() {
        $this->db = new ConnectDB();
        $this->conn = $this->db->connect();
    }

    /**
     * Metodă pentru a înregistra operațiunile în istoricul de utilizare
     * @param string $actiune Descrierea acțiunii efectuate
     * @param string $operatia_sql Comanda SQL executată
     */
    private function logActivity($actiune, $operatia_sql) {
        $query = "INSERT INTO istoric_utilizare (utilizator, rol, data_ora, actiune, operatia_sql) 
                  VALUES (?, ?, NOW(), ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssss", $this->utilizator, $this->rol, $actiune, $operatia_sql);
        $stmt->execute();
        $stmt->close();
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
        if (!empty($criteria['lb_romana'])) {
            $query .= " AND lb_romana = ?";
            $params[] = $criteria['lb_romana'];
            $types .= "d";
        }
        if (!empty($criteria['matematica'])) {
            $query .= " AND matematica = ?";
            $params[] = $criteria['matematica'];
            $types .= "d";
        }
        if (!empty($criteria['scoala'])) {
            $query .= " AND scoala = ?";
            $params[] = $criteria['scoala'];
            $types .= "i";
        }
        if (!empty($criteria['media'])) {
            $query .= " AND media = ?";
            $params[] = $criteria['media'];
            $types .= "d";
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
        
        // Înregistrăm căutarea în istoric
        $criteriaStr = json_encode($criteria);
        $this->logActivity("Căutare elevi cu criteriile: " . $criteriaStr, $query);
        
        return $students;
    }

    /**
     * Metodă pentru a adăuga un elev nou
     * @param array $studentData Datele elevului
     * @return bool Rezultatul operației
     */
    public function addStudent($studentData) {
        $query = "INSERT INTO evaluarenationala (nume, gen, varsta, localitate, lb_romana, matematica, scoala, media) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "ssisdids",
            $studentData['nume'],
            $studentData['gen'],
            $studentData['varsta'],
            $studentData['localitate'],
            $studentData['lb_romana'],
            $studentData['matematica'],
            $studentData['scoala'],
            $studentData['media']
        );
        
        $result = $stmt->execute();
        
        if ($result) {
            // Înregistrăm adăugarea în istoric
            $studentDataStr = json_encode($studentData);
            $this->logActivity("Adăugare elev nou: " . $studentDataStr, $query);
        }
        
        $stmt->close();
        return $result;
    }

    /**
     * Metodă pentru a actualiza datele unui elev
     * @param int $id ID-ul elevului
     * @param array $studentData Datele actualizate ale elevului
     * @return bool Rezultatul operației
     */
    public function updateStudent($id, $studentData) {
        $query = "UPDATE evaluarenationala SET 
                 nume = ?, 
                 gen = ?, 
                 varsta = ?, 
                 localitate = ?, 
                 lb_romana = ?, 
                 matematica = ?, 
                 scoala = ?, 
                 media = ? 
                 WHERE id = ?";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "ssisdidsi",
            $studentData['nume'],
            $studentData['gen'],
            $studentData['varsta'],
            $studentData['localitate'],
            $studentData['lb_romana'],
            $studentData['matematica'],
            $studentData['scoala'],
            $studentData['media'],
            $id
        );
        
        $result = $stmt->execute();
        
        if ($result) {
            // Înregistrăm actualizarea în istoric
            $studentDataStr = json_encode($studentData);
            $this->logActivity("Actualizare elev ID " . $id . ": " . $studentDataStr, $query);
        }
        
        $stmt->close();
        return $result;
    }

    /**
     * Metodă pentru a șterge un elev
     * @param int $id ID-ul elevului
     * @return bool Rezultatul operației
     */
    public function deleteStudent($id) {
        // Obținem datele elevului înainte de ștergere pentru istoric
        $studentData = $this->getStudentById($id);
        
        $query = "DELETE FROM evaluarenationala WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        $result = $stmt->execute();
        
        if ($result) {
            // Înregistrăm ștergerea în istoric
            $studentDataStr = json_encode($studentData);
            $this->logActivity("Ștergere elev ID " . $id . ": " . $studentDataStr, $query);
        }
        
        $stmt->close();
        return $result;
    }

    /**
     * Metodă pentru a obține un elev după ID
     * @param int $id ID-ul elevului
     * @return array Datele elevului
     */
    public function getStudentById($id) {
        $query = "SELECT * FROM evaluarenationala WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        
        $stmt->close();
        
        // Înregistrăm selectarea în istoric
        $this->logActivity("Selectare elev pentru editare - ID: " . $id, $query);
        
        return $student;
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
$selectedStudent = null;

// Sortarea
$sortColumn = $_GET['sort'] ?? 'id';
$sortOrder = $_GET['order'] ?? 'asc';
// Procesarea formularului
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Acțiunea de căutare
    if (isset($_POST['search'])) {
        // Important: NU calculăm media la căutare, ci folosim direct valoarea introdusă
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
        
        $studentList = $admin->searchStudents($searchCriteria, $sortColumn, $sortOrder);
    }
    
    // Acțiunea de adăugare
    elseif (isset($_POST['add'])) {
        // Calculăm media
        $lb_romana = floatval($_POST['lb_romana']);
        $matematica = floatval($_POST['matematica']);
        $media = ($lb_romana + $matematica) / 2;
        
        $studentData = [
            'nume' => $_POST['nume'],
            'gen' => $_POST['gen'],
            'varsta' => intval($_POST['varsta']),
            'localitate' => $_POST['localitate'],
            'lb_romana' => $lb_romana,
            'matematica' => $matematica,
            'scoala' => intval($_POST['scoala']),
            'media' => $media
        ];
        
        if ($admin->addStudent($studentData)) {
            $message = "Elevul a fost adăugat cu succes!";
            // Reîncărcăm lista de elevi
            $studentList = $admin->searchStudents([]);
        } else {
            $message = "Eroare la adăugarea elevului!";
        }
    }
    
    // Acțiunea de actualizare
    elseif (isset($_POST['update'])) {
        if (!empty($_POST['id'])) {
            // Calculăm media
            $lb_romana = floatval($_POST['lb_romana']);
            $matematica = floatval($_POST['matematica']);
            $media = ($lb_romana + $matematica) / 2;
            
            $studentData = [
                'nume' => $_POST['nume'],
                'gen' => $_POST['gen'],
                'varsta' => intval($_POST['varsta']),
                'localitate' => $_POST['localitate'],
                'lb_romana' => $lb_romana,
                'matematica' => $matematica,
                'scoala' => intval($_POST['scoala']),
                'media' => $media
            ];
            
            if ($admin->updateStudent($_POST['id'], $studentData)) {
                $message = "Datele elevului au fost actualizate cu succes!";
                // Reîncărcăm lista de elevi
                $studentList = $admin->searchStudents([]);
            } else {
                $message = "Eroare la actualizarea datelor elevului!";
            }
        } else {
            $message = "Selectați un elev pentru actualizare!";
        }
    }
    
    // Acțiunea de ștergere
    elseif (isset($_POST['delete'])) {
        if (!empty($_POST['id'])) {
            if ($admin->deleteStudent($_POST['id'])) {
                $message = "Elevul a fost șters cu succes!";
                // Reîncărcăm lista de elevi
                $studentList = $admin->searchStudents([]);
            } else {
                $message = "Eroare la ștergerea elevului!";
            }
        } else {
            $message = "Selectați un elev pentru ștergere!";
        }
    }
    
    // Acțiunea de selectare pentru editare
    elseif (isset($_POST['select_id'])) {
        $selectedStudent = $admin->getStudentById($_POST['select_id']);
    }
}

// Încărcăm toți elevii la prima accesare
if (empty($studentList) && $_SERVER["REQUEST_METHOD"] != "POST") {
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
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin: 0;
        }
        .nav-buttons {
            display: flex;
            gap: 10px;
        }
        .nav-btn {
            background-color: #333;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .nav-btn:hover {
            background-color: #555;
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
        .btn-add {
            background-color: #2196F3;
            color: white;
        }
        .btn-update {
            background-color: #FF9800;
            color: white;
        }
        .btn-delete {
            background-color: #f44336;
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
        }
        th a {
            color: white;
            text-decoration: none;
            display: block;
            cursor: pointer;
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
        .select-btn {
            background-color: #673AB7;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
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
        <div class="header">
            <h1>Administrare Evaluare Națională</h1>
            <div class="nav-buttons">
                <a href="admin_cont_page.php" class="nav-btn" >Contestații</a>
                <a href="admin_user_page.php" class="nav-btn">Useri</a>
                <a href="import_export.php" class="nav-btn">Import/Export</a>
                <a href="exemplu_utilizare.php" class="nav-btn">Statistici</a>
                <a href="istoric_page.php" class="nav-btn">Istoric</a>
				<a href="PanouLogin.php" class="nav-btn">LogOut</a>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <div class="form-group">
                <div class="form-field">
                    <label for="id">ID:</label>
                    <input type="text" id="id" name="id" value="<?php echo $selectedStudent['id'] ?? ''; ?>">
                </div>
                <div class="form-field">
                    <label for="nume">Nume:</label>
                    <input type="text" id="nume" name="nume" value="<?php echo $selectedStudent['nume'] ?? ''; ?>">
                </div>
                <div class="form-field">
                    <label for="gen">Gen:</label>
                    <select id="gen" name="gen">
                        <option value="">Selectați</option>
                        <option value="M" <?php echo (isset($selectedStudent['gen']) && $selectedStudent['gen'] == 'M') ? 'selected' : ''; ?>>Masculin</option>
                        <option value="F" <?php echo (isset($selectedStudent['gen']) && $selectedStudent['gen'] == 'F') ? 'selected' : ''; ?>>Feminin</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="varsta">Vârstă:</label>
                    <input type="number" id="varsta" name="varsta" value="<?php echo $selectedStudent['varsta'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-field">
                    <label for="localitate">Localitate:</label>
                    <input type="text" id="localitate" name="localitate" value="<?php echo $selectedStudent['localitate'] ?? ''; ?>">
                </div>
                <div class="form-field">
                    <label for="lb_romana">Limba Română:</label>
                    <input type="number" id="lb_romana" name="lb_romana" step="0.01" min="1" max="10" value="<?php echo $selectedStudent['lb_romana'] ?? ''; ?>">
                </div>
                <div class="form-field">
                    <label for="matematica">Matematică:</label>
                    <input type="number" id="matematica" name="matematica" step="0.01" min="1" max="10" value="<?php echo $selectedStudent['matematica'] ?? ''; ?>">
                </div>
                <div class="form-field">
                    <label for="scoala">Școala:</label>
                    <input type="number" id="scoala" name="scoala" value="<?php echo $selectedStudent['scoala'] ?? ''; ?>">
                </div>
                <div class="form-field">
                    <label for="media">Media:</label>
                    <input type="number" id="media" name="media" step="0.01" min="1" max="10" value="<?php echo $selectedStudent['media'] ?? ''; ?>">
                </div>
            </div>

            <div class="buttons">
                <button type="submit" name="search" class="btn-search">Caută</button>
                <button type="submit" name="add" class="btn-add">Adaugă</button>
                <button type="submit" name="update" class="btn-update">Actualizează</button>
                <button type="submit" name="delete" class="btn-delete" onclick="return confirm('Sunteți sigur că doriți să ștergeți acest elev?')">Șterge</button>
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th><a href="<?php echo getSortUrl('id', $sortColumn, $sortOrder); ?>">ID<?php echo getSortArrow('id', $sortColumn, $sortOrder); ?></a></th>
                    <th><a href="<?php echo getSortUrl('nume', $sortColumn, $sortOrder); ?>">Nume<?php echo getSortArrow('nume', $sortColumn, $sortOrder); ?></a></th>
                    <th><a href="<?php echo getSortUrl('gen', $sortColumn, $sortOrder); ?>">Gen<?php echo getSortArrow('gen', $sortColumn, $sortOrder); ?></a></th>
                    <th><a href="<?php echo getSortUrl('varsta', $sortColumn, $sortOrder); ?>">Vârstă<?php echo getSortArrow('varsta', $sortColumn, $sortOrder); ?></a></th>
                    <th><a href="<?php echo getSortUrl('localitate', $sortColumn, $sortOrder); ?>">Localitate<?php echo getSortArrow('localitate', $sortColumn, $sortOrder); ?></a></th>
                    <th><a href="<?php echo getSortUrl('lb_romana', $sortColumn, $sortOrder); ?>">Lb. Română<?php echo getSortArrow('lb_romana', $sortColumn, $sortOrder); ?></a></th>
                    <th><a href="<?php echo getSortUrl('matematica', $sortColumn, $sortOrder); ?>">Matematică<?php echo getSortArrow('matematica', $sortColumn, $sortOrder); ?></a></th>
                    <th><a href="<?php echo getSortUrl('scoala', $sortColumn, $sortOrder); ?>">Școala<?php echo getSortArrow('scoala', $sortColumn, $sortOrder); ?></a></th>
                    <th><a href="<?php echo getSortUrl('media', $sortColumn, $sortOrder); ?>">Media<?php echo getSortArrow('media', $sortColumn, $sortOrder); ?></a></th>
                    <th>Acțiune</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($studentList)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center;">Nu s-au găsit rezultate.</td>
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
                            <td>
                                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                    <input type="hidden" name="select_id" value="<?php echo $student['id']; ?>">
                                    <button type="submit" class="select-btn">Selectează</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Calculăm media automată când se modifică notele
        document.addEventListener('DOMContentLoaded', function() {
            const lbRomanaInput = document.getElementById('lb_romana');
            const matematicaInput = document.getElementById('matematica');
            const mediaInput = document.getElementById('media');
            
            function calculateAverage() {
                // Calculăm media doar pentru formular principal de adăugare/editare
                // Nu folosim valorile din search pentru calcul
                if (!document.activeElement || 
                    !document.activeElement.form || 
                    !document.activeElement.form.search) {
                    
                    const lbRomana = parseFloat(lbRomanaInput.value) || 0;
                    const matematica = parseFloat(matematicaInput.value) || 0;
                    
                    if (lbRomana > 0 || matematica > 0) {
                        const media = (lbRomana + matematica) / 2;
                        mediaInput.value = media.toFixed(2);
                    } else {
                        mediaInput.value = '';
                    }
                }
            }
            
            lbRomanaInput.addEventListener('input', calculateAverage);
            matematicaInput.addEventListener('input', calculateAverage);
        });
        
        // Facem readonly pentru câmpul media doar când se folosește pentru căutare
        document.addEventListener('DOMContentLoaded', function() {
            const mediaInput = document.getElementById('media');
            const searchBtn = document.querySelector('button[name="search"]');
            const addBtn = document.querySelector('button[name="add"]');
            const updateBtn = document.querySelector('button[name="update"]');
            
            // Când apăsăm butonul de căutare
            searchBtn.addEventListener('click', function() {
                // Media devine editabilă pentru căutare
                mediaInput.readOnly = false;
            });
            
            // Când apăsăm butoanele de add sau update
            [addBtn, updateBtn].forEach(function(btn) {
                btn.addEventListener('click', function() {
                    // Media e calculată automat și readonly pentru adăugare/actualizare
                    mediaInput.readOnly = true;
                });
            });
        });
    </script>
</body>
</html>

<?php
// Închidem conexiunea la final
$admin->closeConnection();
?>