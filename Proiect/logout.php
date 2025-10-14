<?php
session_start();

// Distrugem toate datele sesiunii
$_SESSION = array();

// Ștergem cookie-ul de sesiune dacă există
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Distrugem sesiunea
session_destroy();

// Redirectionăm către pagina de login
header("Location: PanouLogin.php");
exit();
?>