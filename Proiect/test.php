<?php
// Fișier de test pentru verificarea conexiunii la baza de date
require_once 'ConnectDB.php';

echo "<h1>Test de Conectare la Baza de Date</h1>";

// Creăm conexiunea
$db = new ConnectDB();
$conn = $db->connect();

if ($conn) {
    echo "<p style='color: green;'>Conexiunea la baza de date a fost realizată cu succes!</p>";
    
    // Testăm interogarea pentru utilizatori
    $query = "SELECT * FROM users";
    $result = $conn->query($query);
    
    if ($result) {
        echo "<h2>Lista utilizatorilor din baza de date:</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Parolă</th><th>Rol</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['username'] . "</td>";
            echo "<td>" . $row['password'] . "</td>";
            echo "<td>" . $row['role'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color: red;'>Eroare la interogarea bazei de date: " . $conn->error . "</p>";
    }
    
    // Închidere conexiune
    $db->closeConnection();
} else {
    echo "<p style='color: red;'>Conexiunea la baza de date a eșuat!</p>";
}

// Testăm valorile de sesiune
echo "<h2>Informații de sesiune:</h2>";
echo "<pre>";
session_start();
print_r($_SESSION);
echo "</pre>";

// Informații despre server
echo "<h2>Informații despre server:</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";
?>