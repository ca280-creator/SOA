<?php
session_start();
require_once 'ConnectDB.php';

/**
 * Clasa PanouLogin pentru interfața de logare la sistemul de evaluare națională
 */
class PanouLogin {
    private $db;
    
    /**
     * Constructor pentru clasa PanouLogin
     */
    public function __construct() {
        $this->db = new ConnectDB();
    }
    
    /**
     * Metoda de autentificare pentru utilizatori
     * @param string $username Numele de utilizator
     * @param string $password Parola utilizatorului
     * @return bool|string Rolul utilizatorului în caz de succes sau false în caz de eșec
     */
    public function login($username, $password) {
        $conn = $this->db->connect();
        
        if ($conn) {
            // Pregătim interogarea pentru a evita SQL injection
            $stmt = $conn->prepare("SELECT role FROM users WHERE username = ? AND password = ?");
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $stmt->close();
                $this->db->closeConnection();
                return $row['role'];
            }
            
            $stmt->close();
            $this->db->closeConnection();
        }
        
        return false;
    }
    
    /**
     * Metoda pentru redirecționarea utilizatorului către pagina corespunzătoare rolului
     * @param string $role Rolul utilizatorului (admin, analist, elev)
     */
    public function redirect($role) {
        switch ($role) {
            case 'admin':
                header("Location: admin_page.php");
                break;
            case 'analist':
                header("Location: analist_page.php");
                break;
            case 'elev':
                header("Location: elev_page.php");
                break;
            default:
                // În caz de rol necunoscut, redirecționăm către pagina de login
                header("Location: index.php");
                break;
        }
        // Asigurăm-ne că header-ul este trimis și ieșim din script
        exit();
    }
    
    /**
     * Afișarea formularului de login
     */
    public function renderLoginForm() {
        // Verificăm dacă există o eroare de login
        $errorMessage = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
        unset($_SESSION['login_error']);
        
        // HTML pentru interfața de login
        $html = '
        <!DOCTYPE html>
        <html lang="ro">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Evaluare Națională - Login</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 20px;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                }
                .container {
                    background-color: white;
                    border-radius: 8px;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                    padding: 30px;
                    width: 350px;
                    text-align: center;
                }
                h1 {
                    color: #2c3e50;
                    margin-bottom: 20px;
                }
                .role-buttons {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 20px;
                }
                .role-button {
                    background-color: #3498db;
                    color: white;
                    border: none;
                    padding: 10px 15px;
                    cursor: pointer;
                    border-radius: 4px;
                    width: 30%;
                    transition: background-color 0.3s;
                }
                .role-button:hover {
                    background-color: #2980b9;
                }
                .role-button.active {
                    background-color: #2980b9;
                }
                .form-group {
                    margin-bottom: 15px;
                    text-align: left;
                }
                label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: bold;
                    color: #555;
                }
                input[type="text"], input[type="password"] {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    box-sizing: border-box;
                }
                .error-message {
                    color: #e74c3c;
                    margin-bottom: 15px;
                }
                .login-btn {
                    background-color: #27ae60;
                    color: white;
                    border: none;
                    padding: 10px 15px;
                    width: 100%;
                    cursor: pointer;
                    border-radius: 4px;
                    font-size: 16px;
                    transition: background-color 0.3s;
                }
                .login-btn:hover {
                    background-color: #219653;
                }
                .login-form {
                    display: none;
                }
                .login-form.active {
                    display: block;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Evaluare Națională</h1>
                
                <div class="role-buttons">
                    <button class="role-button" id="btn-elev">Elev</button>
                    <button class="role-button" id="btn-analist">Analist</button>
                    <button class="role-button" id="btn-admin">Admin</button>
                </div>
                
                <div class="error-message">' . $errorMessage . '</div>
                
                <!-- Formular pentru Elev -->
                <form id="form-elev" class="login-form" action="process_login.php" method="post">
                    <input type="hidden" name="role" value="elev">
                    <p>Apăsați butonul de mai jos pentru a intra în platformă ca elev.</p>
                    <button type="submit" class="login-btn">Intră ca Elev</button>
                </form>
                
                <!-- Formular pentru Analist -->
                <form id="form-analist" class="login-form" action="process_login.php" method="post">
                    <input type="hidden" name="role" value="analist">
                    <div class="form-group">
                        <label for="username-analist">Nume utilizator:</label>
                        <input type="text" id="username-analist" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password-analist">Parolă:</label>
                        <input type="password" id="password-analist" name="password" required>
                    </div>
                    <button type="submit" class="login-btn">Autentificare</button>
                </form>
                
                <!-- Formular pentru Admin -->
                <form id="form-admin" class="login-form" action="process_login.php" method="post">
                    <input type="hidden" name="role" value="admin">
                    <div class="form-group">
                        <label for="username-admin">Nume utilizator:</label>
                        <input type="text" id="username-admin" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password-admin">Parolă:</label>
                        <input type="password" id="password-admin" name="password" required>
                    </div>
                    <button type="submit" class="login-btn">Autentificare</button>
                </form>
            </div>
            
            <script>
                // Funcție pentru comutarea între formulare
                function showForm(formId) {
                    // Ascundem toate formularele
                    document.querySelectorAll(".login-form").forEach(form => {
                        form.classList.remove("active");
                    });
                    
                    // Resetăm stilul tuturor butoanelor
                    document.querySelectorAll(".role-button").forEach(button => {
                        button.classList.remove("active");
                    });
                    
                    // Afișăm formularul selectat
                    document.getElementById("form-" + formId).classList.add("active");
                    
                    // Activăm butonul corespunzător
                    document.getElementById("btn-" + formId).classList.add("active");
                }
                
                // Adăugăm evenimentele de click pentru butoane
                document.getElementById("btn-elev").addEventListener("click", () => showForm("elev"));
                document.getElementById("btn-analist").addEventListener("click", () => showForm("analist"));
                document.getElementById("btn-admin").addEventListener("click", () => showForm("admin"));
                
                // Arătăm implicit formularul pentru elev la încărcarea paginii
                showForm("elev");
            </script>
        </body>
        </html>
        ';
        
        echo $html;
    }
}

// Verificăm dacă acest fișier este accesat direct sau inclus în alt fișier
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    // Dacă este accesat direct, afișăm interfața de login
    $login = new PanouLogin();
    $login->renderLoginForm();
}
?>