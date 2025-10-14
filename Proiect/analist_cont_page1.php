<?php
// Includem fișierul de conectare la baza de date
require_once 'ConnectDB.php';

// Inițializăm variabilele pentru a evita erorile
$id = $nume = $nota_initiala_romana = $nota_dupa_contestatie_romana = $nota_initiala_matematica = $nota_dupa_contestatie_matematica = "";
$errorMessage = $successMessage = "";
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Creăm o instanță a clasei ConnectDB
$dbConnect = new ConnectDB();
$conn = $dbConnect->connect();

// Verificăm dacă am primit o conexiune validă
if (!$conn) {
    die("Conexiunea la baza de date a eșuat.");
}

// Procesăm formularul de căutare
if (isset($_POST['cauta'])) {
    $id = isset($_POST['id']) ? trim($_POST['id']) : "";
    $nume = isset($_POST['nume']) ? trim($_POST['nume']) : "";
    $nota_initiala_romana = isset($_POST['nota_initiala_romana']) ? trim($_POST['nota_initiala_romana']) : "";
    $nota_dupa_contestatie_romana = isset($_POST['nota_dupa_contestatie_romana']) ? trim($_POST['nota_dupa_contestatie_romana']) : "";
    $nota_initiala_matematica = isset($_POST['nota_initiala_matematica']) ? trim($_POST['nota_initiala_matematica']) : "";
    $nota_dupa_contestatie_matematica = isset($_POST['nota_dupa_contestatie_matematica']) ? trim($_POST['nota_dupa_contestatie_matematica']) : "";
}

// Procesăm adăugarea unui nou înregistrări
if (isset($_POST['adauga'])) {
    $nume = isset($_POST['nume']) ? trim($_POST['nume']) : "";
    $nota_initiala_romana = isset($_POST['nota_initiala_romana']) ? trim($_POST['nota_initiala_romana']) : "";
    $nota_dupa_contestatie_romana = isset($_POST['nota_dupa_contestatie_romana']) ? trim($_POST['nota_dupa_contestatie_romana']) : "";
    $nota_initiala_matematica = isset($_POST['nota_initiala_matematica']) ? trim($_POST['nota_initiala_matematica']) : "";
    $nota_dupa_contestatie_matematica = isset($_POST['nota_dupa_contestatie_matematica']) ? trim($_POST['nota_dupa_contestatie_matematica']) : "";
    
    // Validare
    if (empty($nume)) {
        $errorMessage = "Numele este obligatoriu!";
    } else {
        // Preparăm și executăm interogarea
        $stmt = $conn->prepare("INSERT INTO contestatii (nume, nota_initiala_romana, nota_dupa_contestatie_romana, nota_initiala_matematica, nota_dupa_contestatie_matematica) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sdddd", $nume, $nota_initiala_romana, $nota_dupa_contestatie_romana, $nota_initiala_matematica, $nota_dupa_contestatie_matematica);
        
        if ($stmt->execute()) {
            $successMessage = "Contestație adăugată cu succes!";
            // Resetăm formul
            $id = $nume = $nota_initiala_romana = $nota_dupa_contestatie_romana = $nota_initiala_matematica = $nota_dupa_contestatie_matematica = "";
        } else {
            $errorMessage = "Eroare la adăugarea contestației: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Procesăm actualizarea unei înregistrări
if (isset($_POST['actualizeaza'])) {
    $id = isset($_POST['id']) ? trim($_POST['id']) : "";
    $nume = isset($_POST['nume']) ? trim($_POST['nume']) : "";
    $nota_initiala_romana = isset($_POST['nota_initiala_romana']) ? trim($_POST['nota_initiala_romana']) : "";
    $nota_dupa_contestatie_romana = isset($_POST['nota_dupa_contestatie_romana']) ? trim($_POST['nota_dupa_contestatie_romana']) : "";
    $nota_initiala_matematica = isset($_POST['nota_initiala_matematica']) ? trim($_POST['nota_initiala_matematica']) : "";
    $nota_dupa_contestatie_matematica = isset($_POST['nota_dupa_contestatie_matematica']) ? trim($_POST['nota_dupa_contestatie_matematica']) : "";
    
    // Validare
    if (empty($id)) {
        $errorMessage = "ID-ul este obligatoriu pentru actualizare!";
    } else {
        // Preparăm și executăm interogarea
        $stmt = $conn->prepare("UPDATE contestatii 
                               SET nume = ?, 
                                   nota_initiala_romana = ?, 
                                   nota_dupa_contestatie_romana = ?, 
                                   nota_initiala_matematica = ?, 
                                   nota_dupa_contestatie_matematica = ? 
                               WHERE id = ?");
        $stmt->bind_param("sddddi", $nume, $nota_initiala_romana, $nota_dupa_contestatie_romana, $nota_initiala_matematica, $nota_dupa_contestatie_matematica, $id);
        
        if ($stmt->execute()) {
            $successMessage = "Contestație actualizată cu succes!";
        } else {
            $errorMessage = "Eroare la actualizarea contestației: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Procesăm ștergerea unei înregistrări
if (isset($_POST['sterge'])) {
    $id = isset($_POST['id']) ? trim($_POST['id']) : "";
    
    // Validare
    if (empty($id)) {
        $errorMessage = "ID-ul este obligatoriu pentru ștergere!";
    } else {
        // Preparăm și executăm interogarea
        $stmt = $conn->prepare("DELETE FROM contestatii WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $successMessage = "Contestație ștearsă cu succes!";
            // Resetăm formul
            $id = $nume = $nota_initiala_romana = $nota_dupa_contestatie_romana = $nota_initiala_matematica = $nota_dupa_contestatie_matematica = "";
        } else {
            $errorMessage = "Eroare la ștergerea contestației: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Construim query-ul pentru a afișa datele
$sql = "SELECT * FROM contestatii WHERE 1=1";

// Adăugăm filtrele dacă s-au specificat
if (!empty($id)) {
    $sql .= " AND id = " . $conn->real_escape_string($id);
}
if (!empty($nume)) {
    $sql .= " AND nume LIKE '%" . $conn->real_escape_string($nume) . "%'";
}

// Funcție pentru a gestiona filtrarea după interval sau valoare unică
function addNumericFilter($conn, $fieldName, $value) {
    if (empty($value)) {
        return "";
    }
    
    // Verificăm dacă valoarea conține "-" pentru un interval
    if (strpos($value, '-') !== false) {
        $parts = explode('-', $value);
        if (count($parts) == 2) {
            $min = trim($parts[0]);
            $max = trim($parts[1]);
            
            if (is_numeric($min) && is_numeric($max)) {
                return " AND $fieldName BETWEEN " . $conn->real_escape_string($min) . " AND " . $conn->real_escape_string($max);
            }
        }
    }
    
    // Dacă nu este un interval valid, tratăm ca valoare unică
    if (is_numeric($value)) {
        return " AND $fieldName = " . $conn->real_escape_string($value);
    }
    
    return "";
}

// Adăugăm filtrul pentru notele
$sql .= addNumericFilter($conn, "nota_initiala_romana", $nota_initiala_romana);
$sql .= addNumericFilter($conn, "nota_dupa_contestatie_romana", $nota_dupa_contestatie_romana);
$sql .= addNumericFilter($conn, "nota_initiala_matematica", $nota_initiala_matematica);
$sql .= addNumericFilter($conn, "nota_dupa_contestatie_matematica", $nota_dupa_contestatie_matematica);

// Adăugăm sortarea
$validColumns = ['id', 'nume', 'nota_initiala_romana', 'nota_dupa_contestatie_romana', 'nota_initiala_matematica', 'nota_dupa_contestatie_matematica'];
if (in_array($sortColumn, $validColumns)) {
    $sql .= " ORDER BY " . $sortColumn . " " . ($sortOrder === 'asc' ? 'ASC' : 'DESC');
} else {
    $sql .= " ORDER BY id ASC";
}

// Executăm query-ul
$result = $conn->query($sql);

// Procesăm selecția unei înregistrări
if (isset($_GET['actiune']) && $_GET['actiune'] == 'selecteaza' && isset($_GET['id'])) {
    $selectId = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM contestatii WHERE id = ?");
    $stmt->bind_param("i", $selectId);
    $stmt->execute();
    $selectResult = $stmt->get_result();
    
    if ($row = $selectResult->fetch_assoc()) {
        $id = $row['id'];
        $nume = $row['nume'];
        $nota_initiala_romana = $row['nota_initiala_romana'];
        $nota_dupa_contestatie_romana = $row['nota_dupa_contestatie_romana'];
        $nota_initiala_matematica = $row['nota_initiala_matematica'];
        $nota_dupa_contestatie_matematica = $row['nota_dupa_contestatie_matematica'];
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrare Evaluare Națională</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .action-buttons {
            margin: 20px 0;
        }
        .message {
            margin: 15px 0;
        }
        th {
            cursor: pointer;
        }
        .sort-buttons {
            margin-bottom: 15px;
        }
        .bg-selected {
            /* Nu adăugăm nicio stilizare diferită pentru header-ul selectat */
            /* Headerul rămâne la fel, doar marcăm elementul intern pentru JavaScript */
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Administrare Evaluare Națională</h1>
        </div>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger message">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success message">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="row">
                <div class="col-md-2 mb-3">
                    <label for="id" class="form-label">ID:</label>
                    <input type="text" class="form-control" id="id" name="id" value="<?php echo htmlspecialchars($id); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="nume" class="form-label">Nume:</label>
                    <input type="text" class="form-control" id="nume" name="nume" value="<?php echo htmlspecialchars($nume); ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label for="nota_initiala_romana" class="form-label">Nota Inițială Română:</label>
                    <input type="text" class="form-control" id="nota_initiala_romana" name="nota_initiala_romana" value="<?php echo htmlspecialchars($nota_initiala_romana); ?>" placeholder="Eg: 6 sau 6-9">
                </div>
                <div class="col-md-2 mb-3">
                    <label for="nota_dupa_contestatie_romana" class="form-label">Nota După Contestație Română:</label>
                    <input type="text" class="form-control" id="nota_dupa_contestatie_romana" name="nota_dupa_contestatie_romana" value="<?php echo htmlspecialchars($nota_dupa_contestatie_romana); ?>" placeholder="Eg: 6 sau 6-9">
                </div>
            </div>
            <div class="row">
                <div class="col-md-2 mb-3">
                    <label for="nota_initiala_matematica" class="form-label">Nota Inițială Matematică:</label>
                    <input type="text" class="form-control" id="nota_initiala_matematica" name="nota_initiala_matematica" value="<?php echo htmlspecialchars($nota_initiala_matematica); ?>" placeholder="Eg: 6 sau 6-9">
                </div>
                <div class="col-md-2 mb-3">
                    <label for="nota_dupa_contestatie_matematica" class="form-label">Nota După Contestație Matematică:</label>
                    <input type="text" class="form-control" id="nota_dupa_contestatie_matematica" name="nota_dupa_contestatie_matematica" value="<?php echo htmlspecialchars($nota_dupa_contestatie_matematica); ?>" placeholder="Eg: 6 sau 6-9">
                </div>
            </div>
            
            <div class="action-buttons">
                <button type="submit" name="cauta" class="btn btn-success">Search</button>
                <button type="button" id="sortAsc" class="btn" style="background-color: #2196F3; color: white;">Sortare Ascendentă</button>
                <button type="button" id="sortDesc" class="btn" style="background-color: #FF9800; color: white;">Sortare Descendentă</button>
            </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th data-column="id">ID</th>
                        <th data-column="nume">Nume</th>
                        <th data-column="nota_initiala_romana">Nota Inițială Română</th>
                        <th data-column="nota_dupa_contestatie_romana">Nota După Contestație Română</th>
                        <th data-column="nota_initiala_matematica">Nota Inițială Matematică</th>
                        <th data-column="nota_dupa_contestatie_matematica">Nota După Contestație Matematică</th>
                        <th>Acțiune</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nume']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nota_initiala_romana']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nota_dupa_contestatie_romana']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nota_initiala_matematica']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nota_dupa_contestatie_matematica']) . "</td>";
                            echo "<td><a href='?actiune=selecteaza&id=" . $row['id'] . "' class='btn btn-secondary btn-sm'>Selectează</a></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>Nu s-au găsit înregistrări.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Afișăm mesajele de eroare/succes pentru 5 secunde
        setTimeout(function() {
            var messages = document.querySelectorAll('.message');
            messages.forEach(function(message) {
                message.style.display = 'none';
            });
        }, 5000);
        
        // Variabile pentru sortare
        let currentColumn = '<?php echo $sortColumn; ?>';
        
        // Adăugăm evenimentele pentru butoanele de sortare
        document.getElementById('sortAsc').addEventListener('click', function() {
            window.location.href = '?sort=' + currentColumn + '&order=asc';
        });
        
        document.getElementById('sortDesc').addEventListener('click', function() {
            window.location.href = '?sort=' + currentColumn + '&order=desc';
        });
        
        // Adăugăm evenimentele pentru header-ele de tabel
        document.querySelectorAll('th[data-column]').forEach(function(header) {
            header.addEventListener('click', function() {
                currentColumn = this.dataset.column;
                // Actualizăm indicatorul coloanei selectate
                document.querySelectorAll('th[data-column]').forEach(th => {
                    th.classList.remove('bg-selected');
                });
                this.classList.add('bg-selected');
            });
        });
        
        // Selectăm coloana curentă de sortare
        document.querySelector('th[data-column="<?php echo $sortColumn; ?>"]').classList.add('bg-selected');
    </script>
</body>
</html>
<?php
// Închide conexiunea la baza de date
$dbConnect->closeConnection();
?>