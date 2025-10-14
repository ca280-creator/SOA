<?php
// Includem fișierul de conectare la baza de date
require_once 'ConnectDB.php';

// Inițializăm variabilele pentru a evita erorile
$id = $username = $password = $role = "";
$errorMessage = $successMessage = "";

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
    $username = isset($_POST['username']) ? trim($_POST['username']) : "";
    $password = isset($_POST['password']) ? trim($_POST['password']) : "";
    $role = isset($_POST['role']) ? trim($_POST['role']) : "";
    
    // Nu facem nimic special aici, căutarea va fi executată în query-ul de selectare
}

// Procesăm adăugarea unui nou înregistrări
if (isset($_POST['adauga'])) {
    $username = isset($_POST['username']) ? trim($_POST['username']) : "";
    $password = isset($_POST['password']) ? trim($_POST['password']) : "";
    $role = isset($_POST['role']) ? trim($_POST['role']) : "";
    
    // Validare
    if (empty($username)) {
        $errorMessage = "Username-ul este obligatoriu!";
    } else {
        // Preparăm și executăm interogarea
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) 
                               VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $role);
        
        if ($stmt->execute()) {
            $successMessage = "Utilizator adăugat cu succes!";
            // Resetăm formul
            $id = $username = $password = $role = "";
        } else {
            $errorMessage = "Eroare la adăugarea utilizatorului: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Procesăm actualizarea unei înregistrări
if (isset($_POST['actualizeaza'])) {
    $id = isset($_POST['id']) ? trim($_POST['id']) : "";
    $username = isset($_POST['username']) ? trim($_POST['username']) : "";
    $password = isset($_POST['password']) ? trim($_POST['password']) : "";
    $role = isset($_POST['role']) ? trim($_POST['role']) : "";
    
    // Validare
    if (empty($id)) {
        $errorMessage = "ID-ul este obligatoriu pentru actualizare!";
    } else {
        // Preparăm și executăm interogarea
        $stmt = $conn->prepare("UPDATE users 
                               SET username = ?, 
                                   password = ?, 
                                   role = ? 
                               WHERE id = ?");
        $stmt->bind_param("sssi", $username, $password, $role, $id);
        
        if ($stmt->execute()) {
            $successMessage = "Utilizator actualizat cu succes!";
        } else {
            $errorMessage = "Eroare la actualizarea utilizatorului: " . $stmt->error;
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
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $successMessage = "Utilizator șters cu succes!";
            // Resetăm formul
            $id = $username = $password = $role = "";
        } else {
            $errorMessage = "Eroare la ștergerea utilizatorului: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Construim query-ul pentru a afișa datele
$sql = "SELECT * FROM users WHERE 1=1";

// Adăugăm filtrele dacă s-au specificat
if (!empty($id)) {
    $sql .= " AND id = " . $conn->real_escape_string($id);
}
if (!empty($username)) {
    $sql .= " AND username LIKE '%" . $conn->real_escape_string($username) . "%'";
}
if (!empty($password)) {
    $sql .= " AND password LIKE '%" . $conn->real_escape_string($password) . "%'";
}
if (!empty($role)) {
    $sql .= " AND role LIKE '%" . $conn->real_escape_string($role) . "%'";
}

// Executăm query-ul
$result = $conn->query($sql);

// Procesăm selecția unei înregistrări
if (isset($_GET['actiune']) && $_GET['actiune'] == 'selecteaza' && isset($_GET['id'])) {
    $selectId = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $selectId);
    $stmt->execute();
    $selectResult = $stmt->get_result();
    
    if ($row = $selectResult->fetch_assoc()) {
        $id = $row['id'];
        $username = $row['username'];
        $password = $row['password'];
        $role = $row['role'];
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrare Utilizatori</title>
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
            <h1>Administrare Utilizatori</h1>
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
                <div class="col-md-3 mb-3">
                    <label for="id" class="form-label">ID:</label>
                    <input type="text" class="form-control" id="id" name="id" value="<?php echo htmlspecialchars($id); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="password" class="form-label">Password:</label>
                    <input type="text" class="form-control" id="password" name="password" value="<?php echo htmlspecialchars($password); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="role" class="form-label">Role:</label>
                    <input type="text" class="form-control" id="role" name="role" value="<?php echo htmlspecialchars($role); ?>">
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
                        <th>Username</th>
                        <th>Password</th>
                        <th>Role</th>
                        <th>Acțiune</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['password']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                            echo "<td><a href='?actiune=selecteaza&id=" . $row['id'] . "' class='btn btn-secondary btn-sm'>Selectează</a></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center'>Nu s-au găsit înregistrări.</td></tr>";
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