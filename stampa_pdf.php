<?php
require __DIR__ . "/vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

// ====== Funzione helper: immagine → base64 ======
function imgToBase64($path) {
    if (!file_exists($path) || !is_file($path)) return '';
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    return 'data:image/' . $type . ';base64,' . base64_encode($data);
}

// ====== Connessione DB (usa configDB.cfg) ======
$config = parse_ini_file("configDB.cfg", true);
$dbConf = $config['database'];

$pdo = new PDO(
    "mysql:host={$dbConf['host']};dbname={$dbConf['dbname']};charset=utf8mb4",
    $dbConf['user'],
    $dbConf['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ====== Recupero ID persona ======
if (!isset($_POST['idPersona'])) {
    die("ID persona non fornito");
}
$idPersona = (int) $_POST['idPersona'];

// ====== Recupero dati persona ======
$stmt = $pdo->prepare("SELECT * FROM lista_anagrafica WHERE ID_ANAGRAFICA = ?");
$stmt->execute([$idPersona]);
$persona = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$persona) {
    die("Persona non trovata");
}

// ====== Foto ======
$fotoPath = __DIR__ . "/foto/" . ($persona['FOTOGRAFIA'] ?? '');
if (empty($persona['FOTOGRAFIA']) || !file_exists($fotoPath)) {
    $fotoPath = __DIR__ . "/foto/nondisponibile.jpg";
}
$fotoBase64 = imgToBase64($fotoPath);

// ====== Costruzione HTML ======
$html = '<html><head>
<style>
body { font-family: DejaVu Sans, Arial, sans-serif; font-size:12px; }
h1 { font-size:20px; margin-bottom:5px; }
h2 { background:#eee; padding:4px; border-radius:4px; }
img.foto { max-width:150px; border-radius:8px; margin-bottom:10px; }
table { width:100%; border-collapse:collapse; margin-top:8px; }
td, th { border:1px solid #ccc; padding:5px; font-size:11px; }
.label { font-weight:bold; background:#f5f5f5; width:25%; }
</style>
</head><body>';

$html .= "<h1>{$persona['COGNOME']} {$persona['NOME']}</h1>";
if ($fotoBase64) {
    $html .= "<img src='{$fotoBase64}' class='foto'>";
}

// ====== Dati generali ======
$html .= "<h2>Dati generali</h2><table>";
$campiGenerali = [
    "Data di nascita" => $persona['DATA_NASCITA_GG'] . "/" . $persona['DATA_NASCITA_MM'] . "/" . $persona['DATA_NASCITA_YYYY'],
    "Luogo di nascita" => trim($persona['CITTA_NASCITA'] . " " . $persona['PROVINCIA_NASCITA'] . " " . $persona['STATO_NASCITA']),
    "Data di morte" => $persona['DATA_MORTE_GG'] . "/" . $persona['DATA_MORTE_MM'] . "/" . $persona['DATA_MORTE_YYYY'],
    "Luogo di morte" => trim($persona['CITTA_MORTE'] . " " . $persona['PROVINCIA_MORTE'] . " " . $persona['STATO_MORTE']),
];
foreach ($campiGenerali as $label => $valore) {
    if (trim(str_replace("/", "", $valore)) !== "" && trim($valore) !== "//") {
        $html .= "<tr><td class='label'>$label</td><td>$valore</td></tr>";
    }
}
$html .= "</table>";

// ====== Genitori ======
$stmt = $pdo->prepare("
    SELECT p.ID_ANAGRAFICA, p.COGNOME, p.NOME,
           CONCAT_WS('/', p.DATA_NASCITA_GG, p.DATA_NASCITA_MM, p.DATA_NASCITA_YYYY) AS DATA_NASCITA,
           CONCAT_WS(' ', p.CITTA_NASCITA, p.PROVINCIA_NASCITA, p.STATO_NASCITA) AS LUOGO_NASCITA,
           CASE WHEN g.ID_PADRE = p.ID_ANAGRAFICA THEN 'Padre' ELSE 'Madre' END AS RUOLO
    FROM genitori g
    LEFT JOIN lista_anagrafica p ON p.ID_ANAGRAFICA IN (g.ID_PADRE, g.ID_MADRE)
    WHERE g.ID_FIGLIO = ?
");
$stmt->execute([$idPersona]);
$parents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$html .= "<h2>Genitori</h2>";
if ($parents) {
    $html .= "<table><tr><th>Ruolo</th><th>Nominativo</th><th>Data nascita</th><th>Luogo nascita</th></tr>";
    foreach ($parents as $parent) {
        $html .= "<tr><td>{$parent['RUOLO']}</td><td>{$parent['COGNOME']} {$parent['NOME']}</td><td>{$parent['DATA_NASCITA']}</td><td>{$parent['LUOGO_NASCITA']}</td></tr>";
    }
    $html .= "</table>";
} else {
    $html .= "<p>Non disponibile</p>";
}

// ====== Fratelli/Sorelle ======
$html .= "<h2>Fratelli / Sorelle</h2>";
$stmt = $pdo->prepare("SELECT ID_PADRE, ID_MADRE FROM genitori WHERE ID_FIGLIO = ?");
$stmt->execute([$idPersona]);
$genitoriRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$genitoriRow || (empty($genitoriRow['ID_PADRE']) && empty($genitoriRow['ID_MADRE']))) {
    $html .= "<p>Non disponibile</p>";
} else {
    $sqlSiblings = "
        SELECT l.ID_ANAGRAFICA, l.COGNOME, l.NOME,
               CONCAT_WS('/', l.DATA_NASCITA_GG, l.DATA_NASCITA_MM, l.DATA_NASCITA_YYYY) AS DATA_NASCITA,
               CONCAT_WS(' ', l.CITTA_NASCITA, l.PROVINCIA_NASCITA, l.STATO_NASCITA) AS LUOGO_NASCITA
        FROM lista_anagrafica l
        WHERE l.ID_ANAGRAFICA IN (
            SELECT g.ID_FIGLIO
            FROM genitori g
            WHERE (g.ID_PADRE = :idPadre OR g.ID_MADRE = :idMadre)
              AND g.ID_FIGLIO <> :idPersona
        )
        ORDER BY l.DATA_NASCITA_YYYY, l.DATA_NASCITA_MM, l.DATA_NASCITA_GG, l.COGNOME, l.NOME
    ";
    $stmtSiblings = $pdo->prepare($sqlSiblings);
    $stmtSiblings->execute([
        'idPadre' => $genitoriRow['ID_PADRE'] ?? null,
        'idMadre' => $genitoriRow['ID_MADRE'] ?? null,
        'idPersona' => $idPersona
    ]);
    $siblings = $stmtSiblings->fetchAll(PDO::FETCH_ASSOC);

    if ($siblings) {
        $html .= "<table><tr><th>Nominativo</th><th>Data nascita</th><th>Luogo nascita</th></tr>";
        foreach ($siblings as $s) {
            $html .= "<tr><td>{$s['COGNOME']} {$s['NOME']}</td><td>{$s['DATA_NASCITA']}</td><td>{$s['LUOGO_NASCITA']}</td></tr>";
        }
        $html .= "</table>";
    } else {
        $html .= "<p>Nessun fratello/sorella trovato</p>";
    }
}

// ====== Matrimoni ======
$html .= "<h2>Matrimoni</h2>";
$sqlMatrimonio = "
    SELECT 
        uomo.COGNOME AS uCognome, uomo.NOME AS uNome,
        donna.COGNOME AS dCognome, donna.NOME AS dNome,
        m.DATA_MATRIMONIO, m.DATA_EBRAICA, m.NOTE
    FROM matrimonio m
    JOIN lista_anagrafica uomo ON uomo.ID_ANAGRAFICA = m.ID_USER_UOMO
    JOIN lista_anagrafica donna ON donna.ID_ANAGRAFICA = m.ID_USER_DONNA
    WHERE m.ID_USER_UOMO = :idPersona OR m.ID_USER_DONNA = :idPersona
    ORDER BY m.DATA_MATRIMONIO
";
$stmtMatrimonio = $pdo->prepare($sqlMatrimonio);
$stmtMatrimonio->execute(['idPersona' => $idPersona]);
$matrimoni = $stmtMatrimonio->fetchAll(PDO::FETCH_ASSOC);

if ($matrimoni) {
    $html .= "<table><tr><th>Sposo</th><th>Sposa</th><th>Data Matrimonio</th><th>Data Ebraica</th><th>Note</th></tr>";
    foreach ($matrimoni as $m) {
        $html .= "<tr><td>{$m['uCognome']} {$m['uNome']}</td><td>{$m['dCognome']} {$m['dNome']}</td><td>{$m['DATA_MATRIMONIO']}</td><td>{$m['DATA_EBRAICA']}</td><td>{$m['NOTE']}</td></tr>";
    }
    $html .= "</table>";
} else {
    $html .= "<p>Non disponibile</p>";
}

// ====== Figli ======
$sesso = $persona["SESSO"] ?? "";
if ($sesso === "M") {
    $sqlFigli = "SELECT l.COGNOME, l.NOME, CONCAT_WS('/', l.DATA_NASCITA_GG, l.DATA_NASCITA_MM, l.DATA_NASCITA_YYYY) AS DATA_NASCITA
                 FROM genitori g JOIN lista_anagrafica l ON l.ID_ANAGRAFICA = g.ID_FIGLIO
                 WHERE g.ID_PADRE = :idPersona";
} else {
    $sqlFigli = "SELECT l.COGNOME, l.NOME, CONCAT_WS('/', l.DATA_NASCITA_GG, l.DATA_NASCITA_MM, l.DATA_NASCITA_YYYY) AS DATA_NASCITA
                 FROM genitori g JOIN lista_anagrafica l ON l.ID_ANAGRAFICA = g.ID_FIGLIO
                 WHERE g.ID_MADRE = :idPersona";
}
$stmtFigli = $pdo->prepare($sqlFigli);
$stmtFigli->execute(['idPersona' => $idPersona]);
$figli = $stmtFigli->fetchAll(PDO::FETCH_ASSOC);

$html .= "<h2>Figli</h2>";
if ($figli) {
    $html .= "<table><tr><th>Nominativo</th><th>Data nascita</th></tr>";
    foreach ($figli as $f) {
        $html .= "<tr><td>{$f['COGNOME']} {$f['NOME']}</td><td>{$f['DATA_NASCITA']}</td></tr>";
    }
    $html .= "</table>";
} else {
    $html .= "<p>Non disponibile</p>";
}
// ====== Grafico genealogico (PNG per PDF) ======
$html .= "<h2>Albero genealogico</h2>";

$dotFile = __DIR__ . "/tmp/albero_{$idPersona}.dot";
$pngFile = __DIR__ . "/tmp/albero_{$idPersona}.png";

if (!file_exists(__DIR__ . "/tmp")) {
    mkdir(__DIR__ . "/tmp", 0777, true);
}

// Query ascendenza (max 3 livelli)
$sqlAsc = "
    WITH RECURSIVE genealogico AS (
        SELECT 
            a.ID_ANAGRAFICA, 0 AS livello, a.COGNOME, a.NOME, a.SESSO,
            g.ID_PADRE, g.ID_MADRE
        FROM lista_anagrafica a
        LEFT JOIN genitori g ON a.ID_ANAGRAFICA = g.ID_FIGLIO
        WHERE a.ID_ANAGRAFICA = :idPersona
        UNION ALL
        SELECT 
            a.ID_ANAGRAFICA, c.livello+1, a.COGNOME, a.NOME, a.SESSO,
            g.ID_PADRE, g.ID_MADRE
        FROM lista_anagrafica a
        LEFT JOIN genitori g ON a.ID_ANAGRAFICA = g.ID_FIGLIO
        INNER JOIN genealogico c ON c.ID_PADRE = a.ID_ANAGRAFICA OR c.ID_MADRE = a.ID_ANAGRAFICA
    )
    SELECT DISTINCT * FROM genealogico
    WHERE livello <= 3
    ORDER BY livello, COGNOME, NOME
";
$stmtAsc = $pdo->prepare($sqlAsc);
$stmtAsc->execute(['idPersona' => $idPersona]);
$personeAsc = $stmtAsc->fetchAll(PDO::FETCH_ASSOC);

if ($personeAsc && count($personeAsc) > 0) {
    $dot = "digraph G {\n";
    $dot .= "rankdir=BT;\n";
    $dot .= "node [shape=box, style=rounded, fontname=Helvetica, fontsize=12];\n";

    $idsValidi = [];
    foreach ($personeAsc as $p) {
        $id = $p['ID_ANAGRAFICA'];
        $idsValidi[] = $id;
        $label = addslashes($p['COGNOME'] . " " . $p['NOME']);
        $color = ($p['SESSO'] === 'M') ? "#E6F3FF" : "#FFE6F3";

        $dot .= "\"$id\" [label=\"$label\", style=filled, fillcolor=\"$color\"];\n";
    }

    foreach ($personeAsc as $p) {
        $id = $p['ID_ANAGRAFICA'];
        if ($p['ID_PADRE'] && in_array($p['ID_PADRE'], $idsValidi)) {
            $dot .= "\"$id\" -> \"" . $p['ID_PADRE'] . "\" [color=blue,label=\"Padre\"];\n";
        }
        if ($p['ID_MADRE'] && in_array($p['ID_MADRE'], $idsValidi)) {
            $dot .= "\"$id\" -> \"" . $p['ID_MADRE'] . "\" [color=pink,label=\"Madre\"];\n";
        }
    }

    $dot .= "}\n";

    // Salva il .dot
    if (file_put_contents($dotFile, $dot) !== false) {
        // Genera PNG con Graphviz
        $cmd = "dot -Tpng " . escapeshellarg($dotFile) . " -o " . escapeshellarg($pngFile);
        exec($cmd . " 2>&1", $output, $return_var);

        if ($return_var === 0 && file_exists($pngFile)) {
            // Converte il PNG in base64 per Dompdf
            $graficoBase64 = imgToBase64($pngFile);
            $html .= "<img src='{$graficoBase64}' style='width:100%; max-height:600px;'>";
        } else {
            $html .= "<p>❌ Errore Graphviz:<br><pre>" . implode("\n", $output) . "</pre></p>";
        }
    } else {
        $html .= "<p>❌ Impossibile scrivere il file DOT.</p>";
    }
} else {
    $html .= "<p>Nessun dato genealogico disponibile</p>";
}



$html .= "</body></html>";

// ====== Dompdf ======
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("persona_$idPersona.pdf", ["Attachment" => false]);
