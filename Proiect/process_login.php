<?php
session_start();
require_once 'ConnectDB.php';

/**
 * Script de procesare a formularului de login
 */

// Inițializăm variabila pentru mesajele de eroare
$errorMsg = "";

// Verificăm dacă formularul a fost trimis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificăm rolul selectat
    if (isset($_POST['role'])) {
        $role = $_POST['role'];
        
        // Pentru elev nu avem nevoie de autentificare
        if ($role === 'elev') {
            $_SESSION['user_role'] = 'elev';
            $_SESSION['logged_in'] = true;
            header("Location: elev_page.php");
            exit();
        } 
        // Pentru admin și analist avem nevoie de autentificare
        elseif ($role === 'admin' || $role === 'analist') {
            // Verificăm dacă au fost trimise credențialele
            if (isset($_POST['username']) && isset($_POST['password'])) {
                $username = $_POST['username'];
                $password = $_POST['password'];
                
                // Creăm conexiunea la baza de date
                $db = new ConnectDB();
                $conn = $db->connect();
                
                if ($conn) {
                    // Pregătim interogarea pentru a evita SQL injection
                    $stmt = $conn->prepare("SELECT role FROM users WHERE username = ? AND password = ?");
                    $stmt->bind_param("ss", $username, $password);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $userRole = $row['role'];
                        
                        // Verificăm dacă rolul corespunde
                        if ($userRole === $role) {
                            $_SESSION['user_role'] = $userRole;
                            $_SESSION['username'] = $username;
                            $_SESSION['logged_in'] = true;
                            
                            // Închidere statement și conexiune
                            $stmt->close();
                            $db->closeConnection();
                            
                            // Redirecționare în funcție de rol
                            if ($userRole === 'admin') {
                                header("Location: admin_page.php");
                            } else if ($userRole === 'analist') {
                                header("Location: analist_page.php");
                            }
                            exit();
                        } else {
                            $errorMsg = "Rolul selectat nu corespunde cu contul dvs.!";
                        }
                    } else {
                        $errorMsg = "Nume de utilizator sau parolă incorecte!";
                    }
                    
                    // Închidere statement și conexiune
                    $stmt->close();
                    $db->closeConnection();
                } else {
                    $errorMsg = "Eroare la conectarea la baza de date!";
                }
            } else {
                $errorMsg = "Vă rugăm să introduceți numele de utilizator și parola!";
            }
        } else {
            $errorMsg = "Rol invalid selectat!";
        }
    } else {
        $errorMsg = "Vă rugăm să selectați un rol!";
    }
} else {
    // Accesul direct la acest script nu este permis
    header("Location: PanouLogin.php");
    exit();
}

// Dacă avem o eroare, o stocăm în sesiune și redirecționăm către pagina de login
if (!empty($errorMsg)) {
    $_SESSION['login_error'] = $errorMsg;
    header("Location: PanouLogin.php");
    exit();
}
?>