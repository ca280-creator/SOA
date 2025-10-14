<?php
/**
 * Clasa Statistica pentru generarea statisticilor și graficelor
 * privind rezultatele elevilor la evaluarea națională
 */
class Statistica {
    private $conn;
    
    /**
     * Constructor pentru clasa Statistica
     */
    public function __construct() {
        // Inițializăm conexiunea la baza de date
        $connectDB = new ConnectDB();
        $this->conn = $connectDB->connect();
        
        if (!$this->conn) {
            die("Eroare la conectarea la baza de date");
        }
    }
    
    /**
     * Obține lista orașelor disponibile în baza de date
     * @return array Lista orașelor
     */
    public function getOrase() {
        $sql = "SELECT DISTINCT localitate FROM evaluarenationala ORDER BY localitate";
        $result = $this->conn->query($sql);
        
        $orase = array();
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $orase[] = $row['localitate'];
            }
        }
        
        return $orase;
    }
    
    /**
     * Obține lista școlilor disponibile în baza de date, filtrate după oraș dacă este specificat
     * @param string $oras Orașul pentru filtrare (opțional)
     * @return array Lista școlilor
     */
    public function getScoli($oras = null) {
        $sql = "SELECT DISTINCT scoala FROM evaluarenationala";
        
        if ($oras) {
            $oras = $this->conn->real_escape_string($oras);
            $sql .= " WHERE localitate = '$oras'";
        }
        
        $sql .= " ORDER BY scoala";
        $result = $this->conn->query($sql);
        
        $scoli = array();
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $scoli[] = $row['scoala'];
            }
        }
        
        return $scoli;
    }
    
    /**
     * Obține statistici despre elevii admiși și respinși în funcție de criteriile selectate
     * @param string $materie Materia selectată ('romana' sau 'matematica')
     * @param string $oras Orașul selectat (opțional)
     * @param int $scoala Școala selectată (opțional)
     * @return array Datele statistice pentru grafic
     */
    public function getStatisticiAdmisiRespinsi($materie, $oras = null, $scoala = null) {
        // Stabilim coloana corespunzătoare materiei selectate
        $coloanaMaterie = ($materie == 'romana') ? 'lb_romana' : 'matematica';
        
        // Construim query-ul SQL
        $sql = "SELECT 
                COUNT(CASE WHEN $coloanaMaterie >= 5 THEN 1 END) as admisi,
                COUNT(CASE WHEN $coloanaMaterie < 5 THEN 1 END) as respinsi
                FROM evaluarenationala WHERE 1=1";
        
        // Adăugăm filtrele pentru oraș și școală dacă sunt specificate
        if ($oras) {
            $oras = $this->conn->real_escape_string($oras);
            $sql .= " AND localitate = '$oras'";
        }
        
        if ($scoala) {
            $scoala = $this->conn->real_escape_string($scoala);
            $sql .= " AND scoala = '$scoala'";
        }
        
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $admisi = (int)$row['admisi'];
            $respinsi = (int)$row['respinsi'];
            $total = $admisi + $respinsi;
            
            // Calculăm procentele
            $procentAdmisi = ($total > 0) ? round(($admisi / $total) * 100, 2) : 0;
            $procentRespinsi = ($total > 0) ? round(($respinsi / $total) * 100, 2) : 0;
            
            return [
                'admisi' => $admisi,
                'respinsi' => $respinsi,
                'procentAdmisi' => $procentAdmisi,
                'procentRespinsi' => $procentRespinsi,
                'total' => $total
            ];
        }
        
        return [
            'admisi' => 0,
            'respinsi' => 0,
            'procentAdmisi' => 0,
            'procentRespinsi' => 0,
            'total' => 0
        ];
    }
    
    /**
     * Generează codul HTML pentru formularul de filtrare și pentru graficul pie chart
     * @return string Codul HTML generat
     */
    public function renderStatistici() {
        // Obținem listele de orașe și școli
        $orase = $this->getOrase();
        
        // Procesăm datele din formular
        $materie = isset($_POST['materie']) ? $_POST['materie'] : 'romana';
        $oras = isset($_POST['oras']) ? $_POST['oras'] : '';
        $scoala = isset($_POST['scoala']) ? $_POST['scoala'] : '';
        
        // Obținem statisticile
        $date = $this->getStatisticiAdmisiRespinsi($materie, $oras, $scoala);
        
        // Generăm titlul pentru grafic
        $titluGrafic = "Statistică ".($materie == 'romana' ? 'Limba Română' : 'Matematică');
        if ($oras) {
            $titluGrafic .= " - " . $oras;
        }
        if ($scoala) {
            $titluGrafic .= " - Școala " . $scoala;
        }
        
        // Construim HTML-ul pentru formular și grafic
        $html = '
        <!DOCTYPE html>
        <html lang="ro">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Statistici Evaluare Națională</title>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            
            <!-- Biblioteci pentru exportul PDF -->
            <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

            
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }
                .container {
                    max-width: 800px;
                    margin: 0 auto;
                }
                .form-group {
                    margin-bottom: 15px;
                }
                label {
                    display: inline-block;
                    width: 100px;
                    font-weight: bold;
                }
                select, button {
                    padding: 5px;
                    border-radius: 4px;
                    border: 1px solid #ccc;
                }
                button {
                    background-color: #4CAF50;
                    color: white;
                    border: none;
                    cursor: pointer;
                    padding: 8px 16px;
                }
                button:hover {
                    background-color: #45a049;
                }
                .chart-container {
                    width: 500px;
                    height: 500px;
                    margin: 20px auto;
                }
                h1, h2 {
                    text-align: center;
                }
                .stats {
                    text-align: center;
                    margin: 20px 0;
                    font-size: 16px;
                }
                .export-container {
                    text-align: center;
                    margin-top: 20px;
                }
                #loadingOverlay {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0,0,0,0.5);
                    z-index: 9999;
                    justify-content: center;
                    align-items: center;
                }
                .loader {
                    border: 5px solid #f3f3f3;
                    border-top: 5px solid #3498db;
                    border-radius: 50%;
                    width: 50px;
                    height: 50px;
                    animation: spin 2s linear infinite;
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        </head>
        <body>
            <div id="loadingOverlay">
                <div class="loader"></div>
            </div>
            
            <div class="container">
                <h1>Statistici Evaluare Națională</h1>
                
                <form id="filtrareForm" method="post">
                    <div class="form-group">
                        <label for="materie">Media:</label>
                        <select name="materie" id="materie" onchange="this.form.submit()">
                            <option value="romana" ' . ($materie == 'romana' ? 'selected' : '') . '>Limba Română</option>
                            <option value="matematica" ' . ($materie == 'matematica' ? 'selected' : '') . '>Matematică</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="oras">Oraș:</label>
                        <select name="oras" id="oras" onchange="this.form.submit()">
                            <option value="">Toate orașele</option>';
                            
        foreach ($orase as $o) {
            $html .= '<option value="' . $o . '" ' . ($oras == $o ? 'selected' : '') . '>' . $o . '</option>';
        }
                            
        $html .= '</select>
                    </div>
                    
                    <div class="form-group">
                        <label for="scoala">Școala:</label>
                        <select name="scoala" id="scoala" onchange="this.form.submit()">
                            <option value="">Toate școlile</option>';
                            
        $scoli = $this->getScoli($oras);
        foreach ($scoli as $s) {
            $html .= '<option value="' . $s . '" ' . ($scoala == $s ? 'selected' : '') . '>' . $s . '</option>';
        }
                            
        $html .= '</select>
                    </div>
                </form>
                
                <div id="chart-section">
                    <h2>' . $titluGrafic . '</h2>
                    <div class="stats">
                        <strong>Total elevi:</strong> ' . $date['total'] . ' | 
                        <strong>Admiși:</strong> ' . $date['admisi'] . ' (' . $date['procentAdmisi'] . '%) | 
                        <strong>Respinși:</strong> ' . $date['respinsi'] . ' (' . $date['procentRespinsi'] . '%)
                    </div>
                    <div class="chart-container">
                        <canvas id="pieChart"></canvas>
                    </div>
                    
                    <div class="export-container">
                        <button id="exportPdf" type="button">Export PDF</button>
                    </div>
                </div>
                
                <script>
                    // Funcție pentru afișarea/ascunderea indicatorului de încărcare
                    function toggleLoading(show) {
                        document.getElementById("loadingOverlay").style.display = show ? "flex" : "none";
                    }
                
                    // Generăm graficul pie chart
                    const ctx = document.getElementById("pieChart").getContext("2d");
                    const pieChart = new Chart(ctx, {
                        type: "pie",
                        data: {
                            labels: ["Admiși (' . $date['admisi'] . ')", "Respinși (' . $date['respinsi'] . ')"],
                            datasets: [{
                                data: [' . $date['admisi'] . ', ' . $date['respinsi'] . '],
                                backgroundColor: ["#4CAF50", "#f44336"],
                                hoverBackgroundColor: ["#45a049", "#e53935"]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || "";
                                            const value = context.raw || 0;
                                            const total = context.chart.getDatasetMeta(0).total;
                                            const percentage = Math.round((value / total) * 100);
                                            return `${label}: ${percentage}%`;
                                        }
                                    }
                                },
                                legend: {
                                    position: "bottom"
                                }
                            }
                        }
                    });
                    
                    // Verificăm dacă bibliotecile sunt încărcate corect
                    function checkLibraries() {
                        if (typeof html2canvas === "undefined") {
                            console.error("html2canvas nu este încărcat corect!");
                            alert("Eroare: Biblioteca html2canvas nu este disponibilă. Verificați conexiunea la internet sau instalați biblioteca local.");
                            return false;
                        }
                        
                        if (typeof window.jspdf === "undefined") {
                            console.error("jsPDF nu este încărcat corect!");
                            alert("Eroare: Biblioteca jsPDF nu este disponibilă. Verificați conexiunea la internet sau instalați biblioteca local.");
                            return false;
                        }
                        
                        return true;
                    }
                    
                    // Funcționalitate pentru exportul PDF
                    document.getElementById("exportPdf").addEventListener("click", function() {
                        // Verificăm dacă bibliotecile sunt disponibile
                        if (!checkLibraries()) {
                            return;
                        }
                        
                        toggleLoading(true);
                        
                        const chartSection = document.getElementById("chart-section");
                        
                        // Folosim timeout pentru a permite afișarea indicatorului de încărcare
                        setTimeout(function() {
                            html2canvas(chartSection, {
                                scale: 2, // Calitate mai bună
                                useCORS: true,
                                logging: false,
                                backgroundColor: "#ffffff"
                            }).then(function(canvas) {
                                try {
                                    const imgData = canvas.toDataURL("image/png");
                                    
                                    // Folosim jsPDF corect
                                    const { jsPDF } = window.jspdf;
                                    const pdf = new jsPDF();

                                    
                                    const imgWidth = 190; // A4 width cu margini
                                    const pageHeight = 297; // A4 height in mm
                                    const imgHeight = canvas.height * imgWidth / canvas.width;
                                    
                                    // Adăugăm data și ora exportului
                                    const now = new Date();
                                    const dateStr = now.toLocaleDateString("ro-RO");
                                    const timeStr = now.toLocaleTimeString("ro-RO");
                                    pdf.setFontSize(8);
                                    pdf.text(`Exportat la: ${dateStr} ${timeStr}`, 10, 290);
                                    
                                    pdf.addImage(imgData, "PNG", 10, 10, imgWidth, imgHeight);
                                    pdf.save("statistici_evaluare_nationala.pdf");
                                    
                                    toggleLoading(false);
                                } catch (error) {
                                    console.error("Eroare la generarea PDF-ului:", error);
                                    alert("A apărut o eroare la generarea PDF-ului. Verificați consola pentru detalii.");
                                    toggleLoading(false);
                                }
                            }).catch(function(error) {
                                console.error("Eroare la html2canvas:", error);
                                alert("A apărut o eroare la convertirea graficului. Verificați consola pentru detalii.");
                                toggleLoading(false);
                            });
                        }, 100);
                    });
                    
                    // Actualizare dinamică a școlilor în funcție de orașul selectat
                    document.getElementById("oras").addEventListener("change", function() {
                        document.getElementById("filtrareForm").submit();
                    });
                </script>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Generează pagina completă cu statistici
     */
    public function afiseazaStatistici() {
        echo $this->renderStatistici();
    }
}
?>