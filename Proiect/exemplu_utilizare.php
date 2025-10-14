<?php
// Includem clasele necesare
require_once 'ConnectDB.php';
require_once 'statistica.php';

// Creăm o instanță a clasei Statistica
$statistica = new Statistica();

// Afișăm pagina cu statistici
$statistica->afiseazaStatistici();
?>