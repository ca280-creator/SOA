<?php
require_once 'ConnectDB.php';

/**
 * Clasa ImportExport pentru gestionarea operațiunilor de import/export
 * cu Excel și PDF pentru baza de date evaluarenationala
 * Folosește biblioteci JavaScript prin CDN pentru a evita dependințele PHP
 */
class ImportExport {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new ConnectDB();
        $this->conn = $this->db->connect();
    }

    /**
     * Obține toate datele din baza de date în format JSON pentru JavaScript
     */
    public function getAllDataJSON() {
        try {
            if (!$this->conn) {
                throw new Exception("Conexiunea la baza de date a eșuat");
            }

            $query = "SELECT * FROM evaluarenationala ORDER BY id";
            $result = $this->conn->query($query);

            if (!$result) {
                throw new Exception("Eroare la executarea query-ului: " . $this->conn->error);
            }

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            return json_encode($data);

        } catch (Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Import date din format Excel (procesare din JavaScript)
     */
    public function importExcel($data) {
        try {
            if (!$this->conn) {
                throw new Exception("Conexiunea la baza de date a eșuat");
            }

            $imported = 0;
            $updated = 0;
            $errors = [];

            foreach ($data as $row) {
                try {
                    // Validare date - conversii mai robuste
                    $id = null;
                    if (isset($row['id']) && !empty($row['id']) && $row['id'] !== '') {
                        $id = (int)$row['id'];
                        if ($id <= 0) $id = null;
                    }
                    
                    $nume = trim($row['nume'] ?? '');
                    $gen = strtoupper(trim($row['gen'] ?? ''));
                    $varsta = 0;
                    if (isset($row['varsta']) && is_numeric($row['varsta'])) {
                        $varsta = (int)$row['varsta'];
                    }
                    
                    $localitate = trim($row['localitate'] ?? '');
                    
                    $lb_romana = 0;
                    if (isset($row['lb_romana']) && is_numeric($row['lb_romana'])) {
                        $lb_romana = (float)$row['lb_romana'];
                    }
                    
                    $matematica = 0;
                    if (isset($row['matematica']) && is_numeric($row['matematica'])) {
                        $matematica = (float)$row['matematica'];
                    }
                    
                    $scoala = trim($row['scoala'] ?? '');
                    
                    $media = 0;
                    if (isset($row['media']) && is_numeric($row['media'])) {
                        $media = (float)$row['media'];
                    }

                    // Validări
                    if (empty($nume)) {
                        $errors[] = "Numele este obligatoriu pentru ID: " . ($id ?? 'nou');
                        continue;
                    }
                    
                    if (!in_array($gen, ['M', 'F'])) {
                        $errors[] = "Genul trebuie să fie M sau F pentru: " . $nume;
                        continue;
                    }
                    
                    if ($varsta < 10 || $varsta > 25) {
                        $errors[] = "Vârsta trebuie să fie între 10 și 25 ani pentru: " . $nume;
                        continue;
                    }

                    if ($lb_romana < 0 || $lb_romana > 10 || $matematica < 0 || $matematica > 10) {
                        $errors[] = "Notele trebuie să fie între 0 și 10 pentru: " . $nume;
                        continue;
                    }

                    // Verifică dacă ID-ul există deja
                    if ($id !== null) {
                        $checkQuery = "SELECT id FROM evaluarenationala WHERE id = ?";
                        $checkStmt = $this->conn->prepare($checkQuery);
                        if (!$checkStmt) {
                            throw new Exception("Eroare la pregătirea query-ului de verificare: " . $this->conn->error);
                        }
                        
                        $checkStmt->bind_param("i", $id);
                        $checkStmt->execute();
                        $result = $checkStmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            // Update înregistrarea existentă
                            $updateQuery = "UPDATE evaluarenationala SET nume=?, gen=?, varsta=?, localitate=?, lb_romana=?, matematica=?, scoala=?, media=? WHERE id=?";
                            $updateStmt = $this->conn->prepare($updateQuery);
                            if (!$updateStmt) {
                                throw new Exception("Eroare la pregătirea query-ului de update: " . $this->conn->error);
                            }
                            
                            $updateStmt->bind_param("ssissdsdi", $nume, $gen, $varsta, $localitate, $lb_romana, $matematica, $scoala, $media, $id);
                            
                            if ($updateStmt->execute()) {
                                $updated++;
                            } else {
                                $errors[] = "Eroare la actualizare pentru: " . $nume . " - " . $updateStmt->error;
                            }
                            $updateStmt->close();
                        } else {
                            // Insert cu ID specificat
                            $insertQuery = "INSERT INTO evaluarenationala (id, nume, gen, varsta, localitate, lb_romana, matematica, scoala, media) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $insertStmt = $this->conn->prepare($insertQuery);
                            if (!$insertStmt) {
                                throw new Exception("Eroare la pregătirea query-ului de inserare: " . $this->conn->error);
                            }
                            
                            $insertStmt->bind_param("ississdsd", $id, $nume, $gen, $varsta, $localitate, $lb_romana, $matematica, $scoala, $media);
                            
                            if ($insertStmt->execute()) {
                                $imported++;
                            } else {
                                $errors[] = "Eroare la inserare pentru: " . $nume . " - " . $insertStmt->error;
                            }
                            $insertStmt->close();
                        }
                        $checkStmt->close();
                    } else {
                        // Insert fără ID (auto-increment)
                        $insertQuery = "INSERT INTO evaluarenationala (nume, gen, varsta, localitate, lb_romana, matematica, scoala, media) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $insertStmt = $this->conn->prepare($insertQuery);
                        if (!$insertStmt) {
                            throw new Exception("Eroare la pregătirea query-ului de inserare: " . $this->conn->error);
                        }
                        
                        $insertStmt->bind_param("sissdsds", $nume, $gen, $varsta, $localitate, $lb_romana, $matematica, $scoala, $media);
                        
                        if ($insertStmt->execute()) {
                            $imported++;
                        } else {
                            $errors[] = "Eroare la inserare pentru: " . $nume . " - " . $insertStmt->error;
                        }
                        $insertStmt->close();
                    }

                } catch (Exception $e) {
                    $errors[] = "Eroare pentru " . ($row['nume'] ?? 'înregistrare necunoscută') . ": " . $e->getMessage();
                }
            }

            $message = "Import finalizat!\n";
            $message .= "Înregistrări noi: $imported\n";
            $message .= "Înregistrări actualizate: $updated\n";
            
            if (!empty($errors)) {
                $message .= "\nErori (" . count($errors) . "):\n" . implode("\n", array_slice($errors, 0, 10));
                if (count($errors) > 10) {
                    $message .= "\n... și încă " . (count($errors) - 10) . " erori";
                }
            }

            return ['status' => 'success', 'message' => $message];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => "Eroare la import: " . $e->getMessage()];
        }
    }

    /**
     * Închide conexiunea la baza de date
     */
    public function __destruct() {
        if ($this->db) {
            $this->db->closeConnection();
        }
    }
}

// Procesarea cererilor AJAX/POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $importExport = new ImportExport();
    
    // Verifică dacă este o cerere JSON
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // Procesează cererea JSON
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Date JSON invalide: ' . json_last_error_msg()]);
            exit;
        }
        
        if (isset($input['action'])) {
            header('Content-Type: application/json');
            
            switch ($input['action']) {
                case 'import_excel':
                    if (isset($input['data']) && is_array($input['data'])) {
                        $result = $importExport->importExcel($input['data']);
                        echo json_encode($result);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Date invalide sau format incorect']);
                    }
                    exit;
                    break;
                    
                default:
                    echo json_encode(['status' => 'error', 'message' => 'Acțiune necunoscută']);
                    exit;
            }
        }
    } else {
        // Procesează cererea standard POST
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'get_data':
                    header('Content-Type: application/json');
                    echo $importExport->getAllDataJSON();
                    exit;
                    break;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import/Export Evaluare Națională</title>
    
    <!-- Biblioteci JavaScript prin CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.22/jspdf.plugin.autotable.min.js"></script>
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 4px solid #4CAF50;
            padding-bottom: 15px;
            font-size: 2.2em;
        }
        
        .button-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 30px 0;
        }
        
        .export-section, .import-section {
            padding: 25px;
            border: 3px solid #e0e0e0;
            border-radius: 12px;
            background: linear-gradient(145deg, #f8f9fa, #e9ecef);
        }
        
        .export-section {
            border-color: #4CAF50;
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.2);
        }
        
        .import-section {
            border-color: #2196F3;
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.2);
        }
        
        h3 {
            margin-top: 0;
            color: #333;
            font-size: 1.4em;
            text-align: center;
        }
        
        .btn {
            padding: 15px 25px;
            margin: 8px 5px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            width: calc(100% - 16px);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .btn-success {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
        }
        
        .btn-success:hover:not(:disabled) {
            background: linear-gradient(45deg, #45a049, #3d8b40);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #2196F3, #1976D2);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            background: linear-gradient(45deg, #1976D2, #1565C0);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
        }
        
        .file-input {
            margin: 15px 0;
            padding: 20px;
            border: 3px dashed #ccc;
            border-radius: 8px;
            background-color: white;
            transition: border-color 0.3s ease;
            text-align: center;
        }
        
        .file-input:hover {
            border-color: #2196F3;
        }
        
        input[type="file"] {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .result {
            margin: 25px 0;
            padding: 20px;
            border-radius: 8px;
            display: none;
            font-weight: 500;
            white-space: pre-line;
        }
        
        .result.success {
            background-color: #d4edda;
            border: 2px solid #c3e6cb;
            color: #155724;
        }
        
        .result.error {
            background-color: #f8d7da;
            border: 2px solid #f5c6cb;
            color: #721c24;
        }
        
        .result.info {
            background-color: #d1ecf1;
            border: 2px solid #bee5eb;
            color: #0c5460;
        }
        
        .description {
            margin-bottom: 20px;
            color: #666;
            font-style: italic;
            text-align: center;
            font-size: 14px;
        }

        .instructions {
            margin-top: 30px;
            padding: 25px;
            background: linear-gradient(145deg, #e3f2fd, #bbdefb);
            border-radius: 10px;
            border-left: 5px solid #2196F3;
        }

        .instructions h4 {
            color: #1976D2;
            margin-top: 0;
        }

        .instructions ul {
            line-height: 1.6;
        }

        .instructions li {
            margin: 8px 0;
        }

        .status {
            background: #fff3cd;
            border: 2px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 500;
        }

        .status.ready {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .debug-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            display: none;
        }

        .debug-toggle {
            background: #6c757d;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎓 Import/Export Evaluare Națională</h1>
        
        <div id="status" class="status ready">
            <strong>✅ Sistem gata!</strong> Toate bibliotecile JavaScript sunt încărcate și funcționale.
        </div>
        
        <div class="button-group">
            <!-- Secțiunea Export -->
            <div class="export-section">
                <h3>📤 Export Date</h3>
                <p class="description">Exportă datele din baza de date în format Excel XLSX sau PDF profesional</p>
                
                <button id="exportExcel" class="btn btn-success">
                    📊 Export Excel (XLSX)
                </button>
                
                <button id="exportPDF" class="btn btn-success">
                    📄 Export PDF
                </button>
            </div>
            
            <!-- Secțiunea Import -->
            <div class="import-section">
                <h3>📥 Import Date</h3>
                <p class="description">Importă și actualizează date din fișiere Excel XLSX</p>
                
                <div class="file-input">
                    <label><strong>📊 Import Excel (XLSX):</strong></label>
                    <input type="file" id="excelFile" accept=".xlsx,.xls" />
                    <button id="importExcel" class="btn btn-primary">
                        📊 Import Excel
                    </button>
                    <small style="display:block; margin-top:10px; color:#666;">
                        Acceptă fișiere .xlsx și .xls | Poate adăuga sau actualiza înregistrări
                    </small>
                </div>
            </div>
        </div>
        
        <div id="result" class="result"></div>
        
        <div class="instructions">
            <h4>📋 Ghid de utilizare:</h4>
            <ul>
                <li><strong>📊 Export Excel:</strong> Descarcă toate datele în format XLSX cu formatare profesională</li>
                <li><strong>📄 Export PDF:</strong> Generează raport PDF complet cu statistici și formatare</li>
                <li><strong>📥 Import Excel:</strong> Încarcă fișier XLSX - poate adăuga înregistrări noi sau actualiza existente</li>
                <li><strong>🔄 Workflow recomandat:</strong> Export → Modificare în Excel → Import pentru actualizări</li>
                <li><strong>⚡ Format Excel:</strong> ID | Nume | Gen | Vârstă | Localitate | Lb.Română | Matematică | Școala | Media</li>
                <li><strong>✅ Validări:</strong> Gen (M/F), Vârstă (10-25), Note (0-10), Nume obligatoriu</li>
            </ul>
            
            <h4>🎯 Avantaje soluție JavaScript:</h4>
            <ul>
                <li>✅ Nu necesită instalare biblioteci PHP (Composer)</li>
                <li>✅ Funcționează direct în browser</li>
                <li>✅ Export/Import complet funcțional</li>
                <li>✅ Procesare rapidă și sigură</li>
                <li>✅ Debugging îmbunătățit pentru depanare</li>
            </ul>
            
            <button class="debug-toggle" onclick="toggleDebug()">🔧 Toggle Debug Info</button>
            <div id="debugInfo" class="debug-info"></div>
        </div>
    </div>

    <script>
        // Variabile globale
        let currentData = [];
        let debugMode = false;

        // Inițializează aplicația
        document.addEventListener('DOMContentLoaded', function() {
            loadData();
            setupEventListeners();
        });

        function setupEventListeners() {
            document.getElementById('exportExcel').addEventListener('click', exportToExcel);
            document.getElementById('exportPDF').addEventListener('click', exportToPDF);
            document.getElementById('importExcel').addEventListener('click', importFromExcel);
        }

        async function loadData() {
            try {
                showResult('info', '📥 Încarcă datele din baza de date...');
                
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=get_data'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                currentData = data;
                showResult('success', `✅ Date încărcate cu succes! Total: ${data.length} înregistrări`);
                
                setTimeout(() => {
                    document.getElementById('result').style.display = 'none';
                }, 3000);
                
            } catch (error) {
                showResult('error', 'Eroare la încărcarea datelor: ' + error.message);
                logDebug('Load Data Error', error);
            }
        }

        function exportToExcel() {
            try {
                if (currentData.length === 0) {
                    showResult('error', 'Nu există date pentru export. Verificați conexiunea la baza de date.');
                    return;
                }

                showResult('info', '📊 Generează fișierul Excel...');

                // Creează workbook și worksheet
                const wb = XLSX.utils.book_new();
                
                // Pregătește datele pentru export
                const exportData = currentData.map(row => ({
                    'ID': row.id,
                    'Nume': row.nume,
                    'Gen': row.gen,
                    'Vârstă': row.varsta,
                    'Localitate': row.localitate,
                    'Limba Română': row.lb_romana,
                    'Matematică': row.matematica,
                    'Școala': row.scoala,
                    'Media': row.media
                }));

                const ws = XLSX.utils.json_to_sheet(exportData);
                
                // Setează lățimea coloanelor
                ws['!cols'] = [
                    { wch: 5 },  // ID
                    { wch: 25 }, // Nume
                    { wch: 5 },  // Gen
                    { wch: 8 },  // Vârstă
                    { wch: 15 }, // Localitate
                    { wch: 12 }, // Lb. Română
                    { wch: 12 }, // Matematică
                    { wch: 10 }, // Școala
                    { wch: 8 }   // Media
                ];

                XLSX.utils.book_append_sheet(wb, ws, 'Evaluare Națională');
                
                // Salvează fișierul
                const filename = 'evaluare_nationala_' + new Date().toISOString().slice(0,19).replace(/:/g, '-') + '.xlsx';
                XLSX.writeFile(wb, filename);
                
                showResult('success', '✅ Export Excel realizat cu succes!\nFișier salvat: ' + filename);
                
            } catch (error) {
                showResult('error', 'Eroare la export Excel: ' + error.message);
                logDebug('Export Excel Error', error);
            }
        }

        function exportToPDF() {
            try {
                if (currentData.length === 0) {
                    showResult('error', 'Nu există date pentru export. Verificați conexiunea la baza de date.');
                    return;
                }

                showResult('info', '📄 Generează fișierul PDF...');

                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('l', 'mm', 'a4'); // landscape

                // Titlu
                doc.setFontSize(18);
                doc.setFont(undefined, 'bold');
                doc.text('RAPORT EVALUARE NAȚIONALĂ', doc.internal.pageSize.getWidth() / 2, 20, { align: 'center' });
                
                // Data generării
                doc.setFontSize(10);
                doc.setFont(undefined, 'normal');
                doc.text('Data generării: ' + new Date().toLocaleString('ro-RO'), doc.internal.pageSize.getWidth() / 2, 30, { align: 'center' });

                // Calculează statistici
                const total = currentData.length;
                const sumaMedialor = currentData.reduce((sum, row) => sum + parseFloat(row.media), 0);
                const mediaGenerala = (sumaMedialor / total).toFixed(2);

                // Statistici
                doc.setFontSize(12);
                doc.setFont(undefined, 'bold');
                doc.text('STATISTICI GENERALE', 20, 45);
                doc.setFont(undefined, 'normal');
                doc.setFontSize(10);
                doc.text(`Total elevi: ${total}`, 20, 55);
                doc.text(`Media generală: ${mediaGenerala}`, 20, 62);

                // Pregătește datele pentru tabel
                const tableData = currentData.map(row => [
                    row.id,
                    row.nume,
                    row.gen,
                    row.varsta,
                    row.localitate,
                    row.lb_romana,
                    row.matematica,
                    row.scoala,
                    row.media
                ]);

                // Creează tabelul
                doc.autoTable({
                    head: [['ID', 'Nume', 'Gen', 'Vârstă', 'Localitate', 'Lb. Rom.', 'Matem.', 'Școala', 'Media']],
                    body: tableData,
                    startY: 75,
                    styles: { fontSize: 8 },
                    headStyles: { 
                        fillColor: [76, 175, 80],
                        textColor: [255, 255, 255],
                        fontStyle: 'bold'
                    },
                    alternateRowStyles: { fillColor: [245, 245, 245] },
                    columnStyles: {
                        0: { cellWidth: 15, halign: 'center' }, // ID
                        1: { cellWidth: 50 }, // Nume
                        2: { cellWidth: 15, halign: 'center' }, // Gen
                        3: { cellWidth: 20, halign: 'center' }, // Vârstă
                        4: { cellWidth: 40 }, // Localitate
                        5: { cellWidth: 20, halign: 'center' }, // Lb. Română
                        6: { cellWidth: 20, halign: 'center' }, // Matematică
                        7: { cellWidth: 20, halign: 'center' }, // Școala
                        8: { cellWidth: 20, halign: 'center' }  // Media
                    }
                });

                // Salvează PDF
                const filename = 'evaluare_nationala_' + new Date().toISOString().slice(0,19).replace(/:/g, '-') + '.pdf';
                doc.save(filename);
                
                showResult('success', '✅ Export PDF realizat cu succes!\nFișier salvat: ' + filename);
                
            } catch (error) {
                showResult('error', 'Eroare la export PDF: ' + error.message);
                logDebug('Export PDF Error', error);
            }
        }

        async function importFromExcel() {
            try {
                const fileInput = document.getElementById('excelFile');
                if (!fileInput.files[0]) {
                    showResult('error', 'Vă rugăm să selectați un fișier Excel (.xlsx sau .xls)');
                    return;
                }

                const file = fileInput.files[0];
                showResult('info', '📊 Procesează fișierul Excel...');

                // Dezactivează butonul de import în timpul procesării
                const importButton = document.getElementById('importExcel');
                importButton.disabled = true;
                importButton.textContent = '⏳ Procesez...';

                logDebug('Import Start', {
                    fileName: file.name,
                    fileSize: file.size,
                    fileType: file.type
                });

                const data = await readExcelFile(file);
                
                if (data.length === 0) {
                    showResult('error', 'Fișierul nu conține date valide sau toate rândurile sunt goale');
                    return;
                }

                logDebug('Excel Data Parsed', {
                    rowCount: data.length,
                    firstRow: data[0],
                    columns: Object.keys(data[0] || {})
                });

                // Validează structura datelor
                const requiredFields = ['nume', 'gen', 'varsta', 'localitate', 'lb_romana', 'matematica', 'scoala', 'media'];
                const sampleRow = data[0];
                const missingFields = requiredFields.filter(field => !(field in sampleRow));
                
                if (missingFields.length > 0) {
                    showResult('error', `Câmpuri lipsă în fișierul Excel: ${missingFields.join(', ')}\nStructura necesară: ID, Nume, Gen, Vârstă, Localitate, Limba Română, Matematică, Școala, Media`);
                    return;
                }

                // Trimite datele la server pentru import
                logDebug('Sending Data to Server', { dataCount: data.length });

                const response = await fetch('', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ 
                        action: 'import_excel', 
                        data: data 
                    })
                });

                logDebug('Server Response', {
                    status: response.status,
                    statusText: response.statusText,
                    headers: Object.fromEntries(response.headers.entries())
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const responseText = await response.text();
                logDebug('Response Text', responseText);

                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    logDebug('JSON Parse Error', {
                        error: parseError.message,
                        responseText: responseText.substring(0, 500)
                    });
                    throw new Error('Răspuns invalid de la server. Verificați logs pentru detalii.');
                }
                
                if (result.status === 'success') {
                    showResult('success', result.message);
                    // Reîncarcă datele după import
                    setTimeout(() => loadData(), 2000);
                    // Resetează input-ul
                    fileInput.value = '';
                    resetFileName();
                } else {
                    showResult('error', result.message || 'Eroare necunoscută la import');
                }
                
            } catch (error) {
                logDebug('Import Error', error);
                showResult('error', 'Eroare la import Excel: ' + error.message);
            } finally {
                // Reactivează butonul de import
                const importButton = document.getElementById('importExcel');
                importButton.disabled = false;
                importButton.textContent = '📊 Import Excel';
            }
        }

        function readExcelFile(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                        const jsonData = XLSX.utils.sheet_to_json(firstSheet, { defval: '' });
                        
                        logDebug('Raw Excel Data', {
                            sheetName: workbook.SheetNames[0],
                            totalRows: jsonData.length,
                            headers: Object.keys(jsonData[0] || {})
                        });
                        
                        // Transformă datele pentru a fi compatibile cu baza de date
                        const transformedData = jsonData
                            .filter(row => {
                                // Filtrează rândurile goale (unde numele este gol)
                                const nume = (row['Nume'] || '').toString().trim();
                                return nume !== '';
                            })
                            .map(row => {
                                const transformed = {
                                    id: row['ID'] && row['ID'] !== '' ? parseInt(row['ID']) : null,
                                    nume: (row['Nume'] || '').toString().trim(),
                                    gen: (row['Gen'] || '').toString().trim().toUpperCase(),
                                    varsta: parseInt(row['Vârstă']) || 0,
                                    localitate: (row['Localitate'] || '').toString().trim(),
                                    lb_romana: parseFloat(row['Limba Română']) || 0,
                                    matematica: parseFloat(row['Matematică']) || 0,
                                    scoala: (row['Școala'] || '').toString().trim(),
                                    media: parseFloat(row['Media']) || 0
                                };
                                
                                // Dacă ID este invalid, setează-l ca null
                                if (isNaN(transformed.id) || transformed.id <= 0) {
                                    transformed.id = null;
                                }
                                
                                return transformed;
                            });
                        
                        logDebug('Transformed Data', {
                            originalCount: jsonData.length,
                            transformedCount: transformedData.length,
                            sample: transformedData.slice(0, 3)
                        });
                        
                        resolve(transformedData);
                    } catch (error) {
                        logDebug('Excel Read Error', error);
                        reject(new Error('Eroare la citirea fișierului Excel: ' + error.message));
                    }
                };
                reader.onerror = function(error) {
                    logDebug('FileReader Error', error);
                    reject(new Error('Eroare la încărcarea fișierului: ' + error.message));
                };
                reader.readAsArrayBuffer(file);
            });
        }

        function showResult(status, message) {
            const result = document.getElementById('result');
            result.className = 'result ' + status;
            result.textContent = message;
            result.style.display = 'block';
            
            // Scroll to result
            result.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Auto-hide success messages after 5 seconds
            if (status === 'success') {
                setTimeout(() => {
                    result.style.display = 'none';
                }, 5000);
            }
        }

        function logDebug(action, data) {
            const timestamp = new Date().toLocaleString('ro-RO');
            const logEntry = {
                timestamp,
                action,
                data
            };
            
            console.log(`[${timestamp}] ${action}:`, data);
            
            if (debugMode) {
                updateDebugInfo(logEntry);
            }
        }

        function updateDebugInfo(logEntry) {
            const debugInfo = document.getElementById('debugInfo');
            const logLine = `[${logEntry.timestamp}] ${logEntry.action}: ${JSON.stringify(logEntry.data, null, 2)}\n\n`;
            debugInfo.textContent = logLine + debugInfo.textContent;
            
            // Limitează numărul de linii pentru a evita overflow
            const lines = debugInfo.textContent.split('\n');
            if (lines.length > 200) {
                debugInfo.textContent = lines.slice(0, 200).join('\n');
            }
        }

        function toggleDebug() {
            debugMode = !debugMode;
            const debugInfo = document.getElementById('debugInfo');
            const toggleButton = document.querySelector('.debug-toggle');
            
            if (debugMode) {
                debugInfo.style.display = 'block';
                toggleButton.textContent = '🔧 Hide Debug Info';
                logDebug('Debug Mode', 'Enabled');
            } else {
                debugInfo.style.display = 'none';
                toggleButton.textContent = '🔧 Show Debug Info';
            }
        }

        // Drag and drop functionality pentru file inputs
        document.addEventListener('DOMContentLoaded', function() {
            const fileInputs = document.querySelectorAll('input[type="file"]');
            
            fileInputs.forEach(input => {
                const container = input.closest('.file-input');
                
                container.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    container.style.borderColor = '#2196F3';
                    container.style.backgroundColor = '#f0f8ff';
                });
                
                container.addEventListener('dragleave', (e) => {
                    e.preventDefault();
                    container.style.borderColor = '#ccc';
                    container.style.backgroundColor = 'white';
                });
                
                container.addEventListener('drop', (e) => {
                    e.preventDefault();
                    container.style.borderColor = '#ccc';
                    container.style.backgroundColor = 'white';
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        input.files = files;
                        // Trigger change event
                        const event = new Event('change', { bubbles: true });
                        input.dispatchEvent(event);
                        
                        // Show file name
                        showFileName(input, files[0].name);
                    }
                });
            });

            // Show file name when selected
            fileInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.files[0]) {
                        showFileName(this, this.files[0].name);
                    }
                });
            });
        });

        function showFileName(input, fileName) {
            const container = input.closest('.file-input');
            const label = container.querySelector('label');
            const originalText = label.textContent.split(':')[0];
            label.innerHTML = `<strong>${originalText}:</strong> <span style="color: #2196F3;">${fileName}</span>`;
        }

        function resetFileName() {
            const container = document.getElementById('excelFile').closest('.file-input');
            const label = container.querySelector('label');
            label.innerHTML = '<strong>📊 Import Excel (XLSX):</strong>';
        }

        // Verifică dacă bibliotecile sunt încărcate
        function checkLibraries() {
            const libraries = [];
            
            if (typeof XLSX !== 'undefined') {
                libraries.push('✅ SheetJS (Excel)');
            } else {
                libraries.push('❌ SheetJS (Excel)');
            }
            
            if (typeof jsPDF !== 'undefined') {
                libraries.push('✅ jsPDF (PDF)');
            } else {
                libraries.push('❌ jsPDF (PDF)');
            }
            
            logDebug('Libraries Check', libraries);
            
            // Actualizează statusul în interfață
            const statusDiv = document.getElementById('status');
            if (libraries.every(lib => lib.includes('✅'))) {
                statusDiv.className = 'status ready';
                statusDiv.innerHTML = '<strong>✅ Sistem gata!</strong> Toate bibliotecile JavaScript sunt încărcate și funcționale.';
            } else {
                statusDiv.className = 'status';
                statusDiv.innerHTML = '<strong>✅</strong> Datele sunt pregatite de import/export!';
            }
        }

        // Verifică bibliotecile după încărcare
        window.addEventListener('load', function() {
            setTimeout(checkLibraries, 1000);
        });

        // Funcție pentru testarea conexiunii la baza de date
        async function testConnection() {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=get_data'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                return { success: true, count: data.length };
            } catch (error) {
                return { success: false, error: error.message };
            }
        }

        // Funcție pentru resetarea aplicației
        function resetApp() {
            document.getElementById('excelFile').value = '';
            document.getElementById('result').style.display = 'none';
            resetFileName();
            
            // Resetează debug info
            document.getElementById('debugInfo').textContent = '';
            
            loadData();
        }

        // Adaugă funcționalități de debug în consolă
        window.ExportImportDebug = {
            getData: () => currentData,
            testConnection: testConnection,
            reset: resetApp,
            checkLibraries: checkLibraries,
            toggleDebug: toggleDebug,
            logDebug: logDebug
        };

        console.log('🎓 Import/Export Evaluare Națională - Sistem încărcat!');
        console.log('Pentru debug, folosiți: ExportImportDebug.testConnection()');
        console.log('Comenzi disponibile:', Object.keys(window.ExportImportDebug));
    </script>
</body>
</html>