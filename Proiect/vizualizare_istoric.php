<?php
session_start();
require_once 'ConnectDB.php';
require_once 'istoric.php';

// Verificăm dacă utilizatorul este logat și are permisiuni de admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: PanouLogin.php');
    exit();
}

$istoric = new Istoric();
$message = "";

// Parametrii pentru filtrare și paginare
$filtru = [];
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$pe_pagina = isset($_GET['pe_pagina']) ? max(10, min(100, intval($_GET['pe_pagina']))) : 25;
$perioada_statistici = $_GET['perioada'] ?? 'today';

// Procesăm filtrele din formularul de căutare
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['filtreaza'])) {
        $filtru = [
            'utilizator' => trim($_POST['utilizator'] ?? ''),
            'rol' => trim($_POST['rol'] ?? ''),
            'operatia_logica' => trim($_POST['operatia_logica'] ?? ''),
            'tabela' => trim($_POST['tabela'] ?? ''),
            'data_de_la' => trim($_POST['data_de_la'] ?? ''),
            'data_pana_la' => trim($_POST['data_pana_la'] ?? '')
        ];
        
        // Eliminăm câmpurile goale
        $filtru = array_filter($filtru, function($value) {
            return $value !== '';
        });
    }
    
    if (isset($_POST['curata_istoric'])) {
        $zile = intval($_POST['zile_pastrate'] ?? 90);
        if ($istoric->curataIstoric($zile)) {
            $message = "Istoricul mai vechi de $zile zile a fost curățat cu succes!";
        } else {
            $message = "Eroare la curățarea istoricului!";
        }
    }
}

// Obținem datele din istoric
$rezultat_istoric = $istoric->getIstoric($filtru, $pagina, $pe_pagina);
$istoric_data = $rezultat_istoric['data'];
$total_inregistrari = $rezultat_istoric['total'];
$total_pagini = $rezultat_istoric['total_pagini'];

// Obținem statisticile
$statistici = $istoric->getStatistici($perioada_statistici);

// Înregistrăm accesul la pagina de istoric
$istoric->inregistreazaOperatie(
    $_SESSION['username'],
    $_SESSION['role'],
    "Acces la pagina de vizualizare istoric",
    "GET vizualizare_istoric.php",
    'istoric_utilizare',
    "Pagina: $pagina, Filtru: " . json_encode($filtru, JSON_UNESCAPED_UNICODE)
);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Istoric Utilizare Aplicație</title>
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
            margin-bottom: 20px;
        }
        h1, h2 {
            color: #333;
            text-align: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
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
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        .btn-danger {
            background-color: #f44336;
            color: white;
        }
        .btn-info {
            background-color: #2196F3;
            color: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #212529;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .pagination a:hover {
            background-color: #f1f1f1;
        }
        .pagination .current {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
            background-color: #e8f5e9;
            color: #388e3c;
        }
        .message.error {
            background-color: #ffebee;
            color: #c62828;
        }
        .details-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .details-cell:hover {
            white-space: normal;
            overflow: visible;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .periode-buttons {
            display: flex;
            gap: 5px;
            margin-bottom: 15px;
            justify-content: center;
        }
        .periode-btn {
            padding: 5px 15px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 3px;
        }
        .periode-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Istoric Utilizare Aplicație</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'Eroare') !== false ? 'error' : ''; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistici -->
        <div class="container">
            <h2>Statistici</h2>
            <div class="periode-buttons">
                <a href="?perioada=today" class="periode-btn <?php echo $perioada_statistici === 'today' ? 'active' : ''; ?>">Astăzi</a>
                <a href="?perioada=week" class="periode-btn <?php echo $perioada_statistici === 'week' ? 'active' : ''; ?>">Ultima săptămână</a>
                <a href="?perioada=month" class="periode-btn <?php echo $perioada_statistici === 'month' ? 'active' : ''; ?>">Ultima lună</a>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $statistici['total_operatii'] ?? 0; ?></div>
                    <div class="stat-label">Total Operații</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $statistici['utilizatori_activi'] ?? 0; ?></div>
                    <div class="stat-label">Utilizatori Activi</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($statistici['top_utilizatori'] ?? []); ?></div>
                    <div class="stat-label">Utilizatori Unici</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($statistici['operatii_pe_tabela'] ?? []); ?></div>
                    <div class="stat-label">Tabele Accesate</div>
                </div>
            </div>
        </div>

        <!-- Filtre -->
        <div class="filter-section">
            <h3>Filtrare Istoric</h3>
            <form method="post">
                <div class="form-group">
                    <div class="form-field">
                        <label for="utilizator">Utilizator:</label>
                        <input type="text" id="utilizator" name="utilizator" value="<?php echo htmlspecialchars($filtru['utilizator'] ?? ''); ?>">
                    </div>
                    <div class="form-field">
                        <label for="rol">Rol:</label>
                        <select id="rol" name="rol">
                            <option value="">Toate</option>
                            <option value="admin" <?php echo isset($filtru['rol']) && $filtru['rol'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="analist" <?php echo isset($filtru['rol']) && $filtru['rol'] === 'analist' ? 'selected' : ''; ?>>Analist</option>
                            <option value="elev" <?php echo isset($filtru['rol']) && $filtru['rol'] === 'elev' ? 'selected' : ''; ?>>Elev</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="tabela">Tabelă:</label>
                        <select id="tabela" name="tabela">
                            <option value="">Toate</option>
                            <option value="evaluarenationala" <?php echo isset($filtru['tabela']) && $filtru['tabela'] === 'evaluarenationala' ? 'selected' : ''; ?>>Evaluare Națională</option>
                            <option value="contestatii" <?php echo isset($filtru['tabela']) && $filtru['tabela'] === 'contestatii' ? 'selected' : ''; ?>>Contestații</option>
                            <option value="users" <?php echo isset($filtru['tabela']) && $filtru['tabela'] === 'users' ? 'selected' : ''; ?>>Utilizatori</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-field">
                        <label for="operatia_logica">Operația:</label>
                        <input type="text" id="operatia_logica" name="operatia_logica" value="<?php echo htmlspecialchars($filtru['operatia_logica'] ?? ''); ?>" placeholder="Ex: Adăugare, Căutare, etc.">
                    </div>
                    <div class="form-field">
                        <label for="data_de_la">Data de la:</label>
                        <input type="date" id="data_de_la" name="data_de_la" value="<?php echo htmlspecialchars($filtru['data_de_la'] ?? ''); ?>">
                    </div>
                    <div class="form-field">
                        <label for="data_pana_la">Data până la:</label>
                        <input type="date" id="data_pana_la" name="data_pana_la" value="<?php echo htmlspecialchars($filtru['data_pana_la'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="buttons">
                    <button type="submit" name="filtreaza" class="btn-primary">Filtrează</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn-info" style="text-decoration: none; display: inline-block; text-align: center;">Reset Filtre</a>
                </div>
            </form>
        </div>

        <!-- Curățare istoric -->
        <div class="container">
            <h3>Curățare Istoric</h3>
            <form method="post" onsubmit="return confirm('Sunteți sigur că doriți să ștergeți datele din istoric?');">
                <div style="display: flex; align-items: end; gap: 10px;">
                    <div>
                        <label for="zile_pastrate">Păstrează ultimele:</label>
                        <input type="number" id="zile_pastrate" name="zile_pastrate" value="90" min="1" max="365" style="width: 80px;">
                        <span>zile</span>
                    </div>
                    <button type="submit" name="curata_istoric" class="btn-danger">Curăță Istoric</button>
                </div>
            </form>
        </div>

        <!-- Tabelul cu istoric -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data/Ora</th>
                    <th>Utilizator</th>
                    <th>Rol</th>
                    <th>Operația</th>
                    <th>Tabelă</th>
                    <th>Comandă SQL</th>
                    <th>Detalii</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($istoric_data)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">Nu s-au găsit înregistrări în istoric.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($istoric_data as $inregistrare): ?>
                        <tr>
                            <td><?php echo $inregistrare['id']; ?></td>
                            <td><?php echo date('d.m.Y H:i:s', strtotime($inregistrare['data_ora'])); ?></td>
                            <td><?php echo htmlspecialchars($inregistrare['utilizator']); ?></td>
                            <td><?php echo htmlspecialchars($inregistrare['rol']); ?></td>
                            <td><?php echo htmlspecialchars($inregistrare['operatia_logica']); ?></td>
                            <td><?php echo htmlspecialchars($inregistrare['tabela'] ?? 'N/A'); ?></td>
                            <td class="details-cell" title="<?php echo htmlspecialchars($inregistrare['comanda_sql'] ?? ''); ?>">
                                <?php echo htmlspecialchars(substr($inregistrare['comanda_sql'] ?? '', 0, 50)) . (strlen($inregistrare['comanda_sql'] ?? '') > 50 ? '...' : ''); ?>
                            </td>
                            <td class="details-cell" title="<?php echo htmlspecialchars($inregistrare['detalii'] ?? ''); ?>">
                                <?php echo htmlspecialchars(substr($inregistrare['detalii'] ?? '', 0, 50)) . (strlen($inregistrare['detalii'] ?? '') > 50 ? '...' : ''); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Paginare -->
        <?php if ($total_pagini > 1): ?>
            <div class="pagination">
                <?php if ($pagina > 1): ?>
                    <a href="?pagina=1&pe_pagina=<?php echo $pe_pagina; ?>&<?php echo http_build_query($filtru); ?>">Prima</a>
                    <a href="?pagina=<?php echo $pagina - 1; ?>&pe_pagina=<?php echo $pe_pagina; ?>&<?php echo http_build_query($filtru); ?>">Anterioară</a>
                <?php endif; ?>
                
                <?php
                $start = max(1, $pagina - 2);
                $end = min($total_pagini, $pagina + 2);
                
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <?php if ($i == $pagina): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?pagina=<?php echo $i; ?>&pe_pagina=<?php echo $pe_pagina; ?>&<?php echo http_build_query($filtru); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($pagina < $total_pagini): ?>
                    <a href="?pagina=<?php echo $pagina + 1; ?>&pe_pagina=<?php echo $pe_pagina; ?>&<?php echo http_build_query($filtru); ?>">Următoarea</a>
                    <a href="?pagina=<?php echo $total_pagini; ?>&pe_pagina=<?php echo $pe_pagina; ?>&<?php echo http_build_query($filtru); ?>">Ultima</a>
                <?php endif; ?>
                
                <span>
                    Pagina <?php echo $pagina; ?> din <?php echo $total_pagini; ?> 
                    (<?php echo $total_inregistrari; ?> înregistrări total)
                </span>
            </div>
        <?php endif; ?>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <a href="admin.php" class="btn-info" style="text-decoration: none; padding: 10px 20px; border-radius: 4px;">Înapoi la Administrare</a>
    </div>
</body>
</html>

<?php
$istoric->closeConnection();
?>