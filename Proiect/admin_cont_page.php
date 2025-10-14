<?php
// Includem fișierul de conectare la baza de date
require_once 'ConnectDB.php';

// Inițializăm variabilele pentru a evita erorile
$id = $nume = $nota_initiala_romana = $nota_dupa_contestatie_romana = $nota_initiala_matematica = $nota_dupa_contestatie_matematica = "";
$errorMessage = $successMessage = "";

// Creăm o instanță a clasei ConnectDB
$dbConnect = new ConnectDB();
$conn = $dbConnect->connect();

// Verificăm dacă am primit o conexiune validă
if (!$conn) {
    die("Conexiunea la baza de date a eșuat.");
}

// Funcție pentru înregistrarea în istoricul de utilizare
function adaugaInIstoric($conn, $actiune, $sql_comanda) {
    $utilizator = "admin";
    $rol = "admin";
    $data_ora = date('Y-m-d H:i:s');
    
    $stmt_istoric = $conn->prepare("INSERT INTO istoric_utilizare (utilizator, rol, data_ora, actiune, operatia_sql) VALUES (?, ?, ?, ?, ?)");
    $stmt_istoric->bind_param("sssss", $utilizator, $rol, $data_ora, $actiune, $sql_comanda);
    $stmt_istoric->execute();
    $stmt_istoric->close();
}

// Procesăm formularul de căutare
if (isset($_POST['cauta'])) {
    $id = isset($_POST['id']) ? trim($_POST['id']) : "";
    $nume = isset($_POST['nume']) ? trim($_POST['nume']) : "";
    $nota_initiala_romana = isset($_POST['nota_initiala_romana']) ? trim($_POST['nota_initiala_romana']) : "";
    $nota_dupa_contestatie_romana = isset($_POST['nota_dupa_contestatie_romana']) ? trim($_POST['nota_dupa_contestatie_romana']) : "";
    $nota_initiala_matematica = isset($_POST['nota_initiala_matematica']) ? trim($_POST['nota_initiala_matematica']) : "";
    $nota_dupa_contestatie_matematica = isset($_POST['nota_dupa_contestatie_matematica']) ? trim($_POST['nota_dupa_contestatie_matematica']) : "";
    
    // Înregistrăm căutarea în istoric
    $cautare_params = "ID: $id, Nume: $nume, Nota Init Ro: $nota_initiala_romana, Nota Contest Ro: $nota_dupa_contestatie_romana, Nota Init Mat: $nota_initiala_matematica, Nota Contest Mat: $nota_dupa_contestatie_matematica";
    adaugaInIstoric($conn, "Căutare înregistrări", "SELECT cu parametrii: $cautare_params");
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
            
            // Înregistrăm în istoric
            $sql_executat = "INSERT INTO contestatii (nume, nota_initiala_romana, nota_dupa_contestatie_romana, nota_initiala_matematica, nota_dupa_contestatie_matematica) VALUES ('$nume', '$nota_initiala_romana', '$nota_dupa_contestatie_romana', '$nota_initiala_matematica', '$nota_dupa_contestatie_matematica')";
            adaugaInIstoric($conn, "Adăugare contestație pentru $nume", $sql_executat);
            
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
            
            // Înregistrăm în istoric
            $sql_executat = "UPDATE contestatii SET nume = '$nume', nota_initiala_romana = '$nota_initiala_romana', nota_dupa_contestatie_romana = '$nota_dupa_contestatie_romana', nota_initiala_matematica = '$nota_initiala_matematica', nota_dupa_contestatie_matematica = '$nota_dupa_contestatie_matematica' WHERE id = $id";
            adaugaInIstoric($conn, "Actualizare contestație ID: $id pentru $nume", $sql_executat);
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
        // Obținem mai întâi datele pentru istoric înainte de ștergere
        $stmt_select = $conn->prepare("SELECT nume FROM contestatii WHERE id = ?");
        $stmt_select->bind_param("i", $id);
        $stmt_select->execute();
        $result_select = $stmt_select->get_result();
        $nume_sters = "";
        if ($row_select = $result_select->fetch_assoc()) {
            $nume_sters = $row_select['nume'];
        }
        $stmt_select->close();
        
        // Preparăm și executăm interogarea de ștergere
        $stmt = $conn->prepare("DELETE FROM contestatii WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $successMessage = "Contestație ștearsă cu succes!";
            
            // Înregistrăm în istoric
            $sql_executat = "DELETE FROM contestatii WHERE id = $id";
            adaugaInIstoric($conn, "Ștergere contestație ID: $id ($nume_sters)", $sql_executat);
            
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
if (!empty($nota_initiala_romana)) {
    $sql .= " AND nota_initiala_romana = " . $conn->real_escape_string($nota_initiala_romana);
}
if (!empty($nota_dupa_contestatie_romana)) {
    $sql .= " AND nota_dupa_contestatie_romana = " . $conn->real_escape_string($nota_dupa_contestatie_romana);
}
if (!empty($nota_initiala_matematica)) {
    $sql .= " AND nota_initiala_matematica = " . $conn->real_escape_string($nota_initiala_matematica);
}
if (!empty($nota_dupa_contestatie_matematica)) {
    $sql .= " AND nota_dupa_contestatie_matematica = " . $conn->real_escape_string($nota_dupa_contestatie_matematica);
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
        
        // Înregistrăm în istoric
        $sql_executat = "SELECT * FROM contestatii WHERE id = $selectId";
        adaugaInIstoric($conn, "Selectare contestație ID: $selectId pentru $nume", $sql_executat);
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
                    <input type="text" class="form-control" id="nota_initiala_romana" name="nota_initiala_romana" value="<?php echo htmlspecialchars($nota_initiala_romana); ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label for="nota_dupa_contestatie_romana" class="form-label">Nota După Contestație Română:</label>
                    <input type="text" class="form-control" id="nota_dupa_contestatie_romana" name="nota_dupa_contestatie_romana" value="<?php echo htmlspecialchars($nota_dupa_contestatie_romana); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-2 mb-3">
                    <label for="nota_initiala_matematica" class="form-label">Nota Inițială Matematică:</label>
                    <input type="text" class="form-control" id="nota_initiala_matematica" name="nota_initiala_matematica" value="<?php echo htmlspecialchars($nota_initiala_matematica); ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label for="nota_dupa_contestatie_matematica" class="form-label">Nota După Contestație Matematică:</label>
                    <input type="text" class="form-control" id="nota_dupa_contestatie_matematica" name="nota_dupa_contestatie_matematica" value="<?php echo htmlspecialchars($nota_dupa_contestatie_matematica); ?>">
                </div>
            </div>
            
            <div class="action-buttons">
                <button type="submit" name="cauta" class="btn btn-success">Caută</button>
                <button type="submit" name="adauga" class="btn btn-primary">Adaugă</button>
                <button type="submit" name="actualizeaza" class="btn btn-warning">Actualizează</button>
                <button type="submit" name="sterge" class="btn btn-danger">Șterge</button>
            </div>
        </form>
        
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nume</th>
                        <th>Nota Inițială Română</th>
                        <th>Nota După Contestație Română</th>
                        <th>Nota Inițială Matematică</th>
                        <th>Nota După Contestație Matematică</th>
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
        
        // Funcție pentru a sorta tabelul când se face click pe header
        document.querySelectorAll('th').forEach(function(header, index) {
            header.addEventListener('click', function() {
                sortTable(index);
            });
        });
        
        function sortTable(n) {
            var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            table = document.querySelector('table');
            switching = true;
            dir = "asc";
            
            while (switching) {
                switching = false;
                rows = table.rows;
                
                for (i = 1; i < (rows.length - 1); i++) {
                    shouldSwitch = false;
                    x = rows[i].getElementsByTagName("td")[n];
                    y = rows[i + 1].getElementsByTagName("td")[n];
                    
                    // Verificăm dacă este un număr sau un text
                    if (isNaN(parseFloat(x.innerHTML))) {
                        // Este un text
                        if (dir == "asc") {
                            if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                                shouldSwitch = true;
                                break;
                            }
                        } else if (dir == "desc") {
                            if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                                shouldSwitch = true;
                                break;
                            }
                        }
                    } else {
                        // Este un număr
                        if (dir == "asc") {
                            if (parseFloat(x.innerHTML) > parseFloat(y.innerHTML)) {
                                shouldSwitch = true;
                                break;
                            }
                        } else if (dir == "desc") {
                            if (parseFloat(x.innerHTML) < parseFloat(y.innerHTML)) {
                                shouldSwitch = true;
                                break;
                            }
                        }
                    }
                }
                
                if (shouldSwitch) {
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    switchcount++;
                } else {
                    if (switchcount == 0 && dir == "asc") {
                        dir = "desc";
                        switching = true;
                    }
                }
            }
        }
    </script>
</body>
</html>
<?php
// Închide conexiunea la baza de date
$dbConnect->closeConnection();
?>