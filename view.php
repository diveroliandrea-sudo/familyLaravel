<?php
// ====== Lettura configurazione DB ======
$config = parse_ini_file("configDB.cfg", true);
$dbConf = $config['database'];

$pdo = new PDO(
    "mysql:host={$dbConf['host']};dbname={$dbConf['dbname']};charset=utf8mb4",
    $dbConf['user'],
    $dbConf['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ====== Recupero ID persona ======
if (!isset($_GET['id'])) {
    die("ID persona non fornito");
}
$idPersona = (int) $_GET['id'];

// ====== Recupero dati persona ======
$stmt = $pdo->prepare("SELECT * FROM lista_anagrafica WHERE ID_ANAGRAFICA = ?");
$stmt->execute([$idPersona]);
$persona = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$persona) {
    die("Persona non trovata");
}

$fotoPath = "./foto/" . ($persona['FOTOGRAFIA'] ?? '');
if (empty($persona['FOTOGRAFIA']) || !file_exists($fotoPath)) {
    $fotoPath = "./foto/nondisponibile.jpg";
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dettaglio Persona</title>
	<script type="text/javascript">
	function VisualizzaDocumento(id,idFonte){
			
				window.open('VisualizzaImg.php?idAnagrafica=' + id +"&idFonte="+ idFonte  , 'VisualizzaImg', 'width="50%",height="50% ,toolbar=no, location=no,status=yes,menubar=no,scrollbars=no,resizable=no');
			}
	</script>
    <style>
        body { font-family: Arial, sans-serif; margin:20px; background:#f8f9fa; }
        h1 { margin-bottom:5px; }
        h2 { margin-top:0; }
        img { max-width:150px; border-radius:8px; margin-right:20px; }
        .header { display:flex; align-items:center; margin-bottom:20px; }
        .card {
            background:#fff;
            padding:15px;
            margin-top:20px;
            border-radius:12px;
            box-shadow:0 2px 6px rgba(0,0,0,0.15);
        }
        .card h2, .card h3 { margin-top:0; }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        table, td { border:1px solid #ccc; }
        td { padding:6px; }
        tr:nth-child(even) { background:#f9f9f9; }
        .label { font-weight:bold; width:30%; background:#eee; }
        /* Colori diversi per sezioni */
        .generali { border-top:5px solid #4CAF50; }
        .ebraici { border-top:5px solid #FF9800; }
        .genitori { border-top:5px solid #F44336; }
        .fratelli { border-top:5px solid #2196F3; }
        .matrimonio { border-top:5px solid #9C27B0; }
		.documenti { border-top:5px solid #607D8B; } /* grigio-blu */
    </style>
</head>
<body>

<div class="header">
    <img src="<?= htmlspecialchars($fotoPath) ?>" alt="Foto">
    <h1><?= htmlspecialchars($persona['COGNOME'] . " " . $persona['NOME']) ?></h1>
</div>

<!-- ================= DATI GENERALI ================= -->
<div class="card generali">
    <h2>Dati generali</h2>
    <table>
        <?php
        $campiGenerali = [
            "Data di nascita" => $persona['DATA_NASCITA_GG'] . "/" . $persona['DATA_NASCITA_MM'] . "/" . $persona['DATA_NASCITA_YYYY'],
            "Luogo di nascita" => trim($persona['CITTA_NASCITA'] . " " . $persona['PROVINCIA_NASCITA'] . " " . $persona['STATO_NASCITA']),
            "Data di morte" => $persona['DATA_MORTE_GG'] . "/" . $persona['DATA_MORTE_MM'] . "/" . $persona['DATA_MORTE_YYYY'],
            "Luogo di morte" => trim($persona['CITTA_MORTE'] . " " . $persona['PROVINCIA_MORTE'] . " " . $persona['STATO_MORTE']),
        ];
        foreach ($campiGenerali as $label => $valore) {
            if (trim(str_replace("/", "", $valore)) !== "" && trim($valore) !== "//") {
                echo "<tr><td class='label'>{$label}</td><td>" . htmlspecialchars($valore) . "</td></tr>";
            }
        }
	
        ?>
    </table>
</div>

<!-- ================= DATI EBRAICI ================= -->
<?php
$stmtEbraici = $pdo->prepare("
    SELECT 
        DATA_EBRAICA_NASCITA,
        DATA_MILAH,
        LUOGO_MILAH,
        MOEL,
        RAV_MILAH,
        NOTE_MILAH,
        DATA_EBRAICA_MORTE,
        ORA_MORTE,
        RAV_MORTE,
        DATA_BAR_BAT_MITZVAH,
        DATA_EBRAICA_BAR_BAT_MITZVAH,
        LUOGO_BAR_BAT_MITZVAH,
        PARASHA,
        RAV_BAR_BAT_MITZVAH,
        NOTE_BAR_BAT_MITZVAH
    FROM dati_ebraismo
    WHERE ID_ANAGRAFICA = ?
");
$stmtEbraici->execute([$idPersona]);
$datiEbraici = $stmtEbraici->fetch(PDO::FETCH_ASSOC);

if ($datiEbraici && count(array_filter($datiEbraici)) > 0) { // Mostra solo se almeno un campo non è vuoto
?>
<div class="card ebraici">
    <h2>Dati ebraici</h2>
    <table>
        <?php
        $etichette = [
            "DATA_EBRAICA_NASCITA"        => "Data ebraica nascita",
            "DATA_MILAH"                  => "Data milah",
            "LUOGO_MILAH"                 => "Luogo milah",
            "MOEL"                        => "Moel",
            "RAV_MILAH"                   => "Rav milah",
            "NOTE_MILAH"                  => "Note milah",
            "DATA_EBRAICA_MORTE"          => "Data ebraica morte",
            "ORA_MORTE"                   => "Ora morte",
            "RAV_MORTE"                   => "Rav morte",
            "DATA_BAR_BAT_MITZVAH"        => "Data Bar/Bat Mitzvah (greg.)",
            "DATA_EBRAICA_BAR_BAT_MITZVAH"=> "Data ebraica Bar/Bat Mitzvah",
            "LUOGO_BAR_BAT_MITZVAH"       => "Luogo Bar/Bat Mitzvah",
            "PARASHA"                     => "Parashà",
            "RAV_BAR_BAT_MITZVAH"         => "Rav Bar/Bat Mitzvah",
            "NOTE_BAR_BAT_MITZVAH"        => "Note Bar/Bat Mitzvah",
        ];

        foreach ($etichette as $campo => $etichetta) {
            $valore = trim($datiEbraici[$campo] ?? "");
            if ($valore !== "") {
                echo "<tr><td class='label'>{$etichetta}</td><td>" . htmlspecialchars($valore) . "</td></tr>";
            }
        }


if (!empty($datiEbraici['DATA_EBRAICA_MORTE'])) {
    $dataEbraicaMorte = $datiEbraici['DATA_EBRAICA_MORTE']; // es: "16 Elul 5775"

    // --- separo la stringa
    $parts = explode(" ", $dataEbraicaMorte);
    if (count($parts) === 3) {
        $giorno = $parts[0];     // 16
        $mese  = $parts[1];      // Elul
        $annoOriginale = $parts[2]; // 5775

        // --- Calcolo anno ebraico corrente (approssimativo)
        $gregYear = date("Y");
        $gregMonth = date("n");
        $annoCorrenteEbraico = $gregYear + 3760;
        if ($gregMonth >= 9) {
            $annoCorrenteEbraico++;
        }

        // --- Funzione per conversione
        function convertiData($giorno, $mese, $annoEbraico) {
            $url = "https://www.hebcal.com/converter?cfg=json&hy={$annoEbraico}&hm={$mese}&hd={$giorno}&h2g=1";
            $json = @file_get_contents($url);
            if ($json === false) return null;
            $data = json_decode($json, true);
            if (!empty($data['gd']) && !empty($data['gm']) && !empty($data['gy'])) {
                return sprintf("%02d/%02d/%04d", $data['gd'], $data['gm'], $data['gy']);
            }
            return null;
        }

        // --- prima prova: anno ebraico corrente
        $gregDate = convertiData($giorno, $mese, $annoCorrenteEbraico);

        if ($gregDate) {
            // controllo: se la data cade nell’anno solare precedente → rifaccio per l’anno ebraico successivo
            $annoGreg = (int)substr($gregDate, 6, 4);
            if ($annoGreg < (int)date("Y")) {
                $annoCorrenteEbraico++;
                $gregDate = convertiData($giorno, $mese, $annoCorrenteEbraico);
            }

            if ($gregDate) {
                // --- calcolo vigilia
                $dt = DateTime::createFromFormat("d/m/Y", $gregDate);
                $vigilia = clone $dt;
                $vigilia->modify("-1 day");
                $vigiliaStr = $vigilia->format("d/m/Y");

                // --- output tabella
              #  echo "<tr><td class='label'>Anno ebraico corrente</td><td>{$giorno} {$mese} {$annoCorrenteEbraico}</td></tr>";
                echo "<tr><td class='label'>Ascharà</td>
                      <td>La Ascharà per l’anno {$annoCorrenteEbraico} sarà dalla sera del {$vigiliaStr} al {$gregDate}.<br>
                      La cerimonia di suffragio avrà pertanto luogo in uno dei Templi della Comunità secondo il desiderio dei familiari <b> la sera del {$vigiliaStr} </b></td></tr>";
            }
        }
    }
}

?>
        
        
		
    </table>
</div>
<?php } ?>

<!-- ================= SEZIONE GENITORI ================= -->
<div class="card genitori">
    <h2>Genitori</h2>
    <?php
    $stmt = $pdo->prepare("
        SELECT 
            p.ID_ANAGRAFICA, p.COGNOME, p.NOME,
            CONCAT_WS('/', p.DATA_NASCITA_GG, p.DATA_NASCITA_MM, p.DATA_NASCITA_YYYY) AS DATA_NASCITA,
            CONCAT_WS(' ', p.CITTA_NASCITA, p.PROVINCIA_NASCITA, p.STATO_NASCITA) AS LUOGO_NASCITA,
            CONCAT_WS('/', p.DATA_MORTE_GG, p.DATA_MORTE_MM, p.DATA_MORTE_YYYY) AS DATA_MORTE,
            CONCAT_WS(' ', p.CITTA_MORTE, p.PROVINCIA_MORTE, p.STATO_MORTE) AS LUOGO_MORTE,
            CASE WHEN g.ID_PADRE = p.ID_ANAGRAFICA THEN 'Padre' ELSE 'Madre' END AS RUOLO
        FROM genitori g
        LEFT JOIN lista_anagrafica p ON p.ID_ANAGRAFICA IN (g.ID_PADRE, g.ID_MADRE)
        WHERE g.ID_FIGLIO = ?
    ");
    $stmt->execute([$idPersona]);
    $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$parents || count($parents) === 0) {
        echo "<p>Non disponibile</p>";
    } else {
        echo "<table><tr><td class='label'>Ruolo</td><td class='label'>Nominativo</td><td class='label'>Data Nascita</td><td class='label'>Luogo Nascita</td><td class='label'>Data Morte</td><td class='label'>Luogo Morte</td></tr>";
        foreach ($parents as $parent) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($parent['RUOLO']) . "</td>";
            echo "<td><a href='view.php?id=" . htmlspecialchars($parent['ID_ANAGRAFICA']) . "'>" . htmlspecialchars($parent['COGNOME'] . " " . $parent['NOME']) . "</a></td>";
            echo "<td>" . htmlspecialchars($parent['DATA_NASCITA']) . "</td>";
            echo "<td>" . htmlspecialchars($parent['LUOGO_NASCITA']) . "</td>";
            echo "<td>" . htmlspecialchars($parent['DATA_MORTE']) . "</td>";
            echo "<td>" . htmlspecialchars($parent['LUOGO_MORTE']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    ?>
</div>

<!-- ================= SEZIONE FRATELLI/SORELLE ================= -->
<div class="card fratelli">
    <h2>Fratelli / Sorelle</h2>
    <?php
    $stmt = $pdo->prepare("SELECT ID_PADRE, ID_MADRE FROM genitori WHERE ID_FIGLIO = ?");
    $stmt->execute([$idPersona]);
    $genitoriRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$genitoriRow || ($genitoriRow['ID_PADRE'] === null && $genitoriRow['ID_MADRE'] === null)) {
        echo "<p>Non disponibile</p>";
    } else {
        $idPadre = $genitoriRow['ID_PADRE'] ?: 'NULL';
        $idMadre = $genitoriRow['ID_MADRE'] ?: 'NULL';

        $sqlSiblings = "
            SELECT l.ID_ANAGRAFICA, l.COGNOME, l.NOME,
                   CONCAT_WS('/', l.DATA_NASCITA_GG, l.DATA_NASCITA_MM, l.DATA_NASCITA_YYYY) AS DATA_NASCITA,
                   CONCAT_WS(' ', l.CITTA_NASCITA, l.PROVINCIA_NASCITA, l.STATO_NASCITA) AS LUOGO_NASCITA,
                   CONCAT_WS('/', l.DATA_MORTE_GG, l.DATA_MORTE_MM, l.DATA_MORTE_YYYY) AS DATA_MORTE,
                   CONCAT_WS(' ', l.CITTA_MORTE, l.PROVINCIA_MORTE, l.STATO_MORTE) AS LUOGO_MORTE
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
        $stmtSiblings->execute(['idPadre' => $idPadre, 'idMadre' => $idMadre, 'idPersona' => $idPersona]);
        $siblings = $stmtSiblings->fetchAll(PDO::FETCH_ASSOC);

        if (count($siblings) === 0) {
            echo "<p>Nessun fratello/sorella trovato</p>";
        } else {
            echo "<table><tr><td class='label'>Nominativo</td><td class='label'>Data Nascita</td><td class='label'>Luogo Nascita</td><td class='label'>Data Morte</td><td class='label'>Luogo Morte</td></tr>";
            foreach ($siblings as $sibling) {
                echo "<tr>";
                echo "<td><a href='view.php?id=" . htmlspecialchars($sibling['ID_ANAGRAFICA']) . "'>" . htmlspecialchars($sibling['COGNOME'] . " " . $sibling['NOME']) . "</a></td>";
                echo "<td>" . htmlspecialchars($sibling['DATA_NASCITA']) . "</td>";
                echo "<td>" . htmlspecialchars($sibling['LUOGO_NASCITA']) . "</td>";
                echo "<td>" . htmlspecialchars($sibling['DATA_MORTE']) . "</td>";
                echo "<td>" . htmlspecialchars($sibling['LUOGO_MORTE']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    ?>
</div>

<!-- ================= SEZIONE MATRIMONIO ================= -->
<div class="card matrimonio">
    <h2>Matrimoni</h2>
    <?php
    $stmtMatrimonio = $pdo->prepare("
        SELECT 
            uomo.ID_ANAGRAFICA AS uID, uomo.COGNOME AS uCognome, uomo.NOME AS uNome,
            donna.ID_ANAGRAFICA AS dID, donna.COGNOME AS dCognome, donna.NOME AS dNome,
            m.DATA_MATRIMONIO, m.DATA_EBRAICA, m.NOTE
        FROM matrimonio m
        JOIN lista_anagrafica uomo ON uomo.ID_ANAGRAFICA = m.ID_USER_UOMO
        JOIN lista_anagrafica donna ON donna.ID_ANAGRAFICA = m.ID_USER_DONNA
        WHERE m.ID_USER_UOMO = :idPersona OR m.ID_USER_DONNA = :idPersona
        ORDER BY m.DATA_MATRIMONIO
    ");
    $stmtMatrimonio->execute(['idPersona' => $idPersona]);
    $matrimoni = $stmtMatrimonio->fetchAll(PDO::FETCH_ASSOC);

    if (!$matrimoni || count($matrimoni) === 0) {
        echo "<p>Non disponibile</p>";
    } else {
        echo "<table style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
        echo "  <tr style='background-color:#f2f2f2;'>";
        echo "      <th style='padding:8px; text-align:left; width:20%;'>Sposo</th>";
        echo "      <th style='padding:8px; text-align:left; width:20%;'>Sposa</th>";
        echo "      <th style='padding:8px; text-align:left; width:15%;'>Data Matrimonio</th>";
        echo "      <th style='padding:8px; text-align:left; width:15%;'>Data Ebraica</th>";
        echo "      <th style='padding:8px; text-align:left; width:30%;'>Note</th>";
        echo "  </tr>";

        foreach ($matrimoni as $row) {
            echo "<tr>";
            echo "<td style='padding:10px;'><a href='view.php?id=" . $row['uID'] . "'>" . htmlspecialchars($row['uCognome'] . " " . $row['uNome']) . "</a></td>";
            echo "<td style='padding:10px;'><a href='view.php?id=" . $row['dID'] . "'>" . htmlspecialchars($row['dCognome'] . " " . $row['dNome']) . "</a></td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($row['DATA_MATRIMONIO']?? '') . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($row['DATA_EBRAICA']?? '') . "</td>";
			
            echo "<td style='padding:10px; white-space:normal;'>" . htmlspecialchars($row['NOTE'] ?? '') . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    }
    ?>
</div>
<!-- ================= SEZIONE FIGLI ================= -->
<div class="card figli">
    <h2>Figli</h2>
    <?php
    // Recupero sesso della persona (serve per capire se padre o madre)
    $sesso = $persona["SESSO"] ?? "";

    if ($sesso === "M") {
        $sqlFigli = "
            SELECT l.ID_ANAGRAFICA, l.COGNOME, l.NOME, l.SESSO,
                   CONCAT_WS('/', l.DATA_NASCITA_GG, l.DATA_NASCITA_MM, l.DATA_NASCITA_YYYY) AS DATA_NASCITA,
                   CONCAT_WS(' ', l.CITTA_NASCITA, l.PROVINCIA_NASCITA, l.STATO_NASCITA) AS LUOGO_NASCITA,
                   CONCAT_WS('/', l.DATA_MORTE_GG, l.DATA_MORTE_MM, l.DATA_MORTE_YYYY) AS DATA_MORTE,
                   CONCAT_WS(' ', l.CITTA_MORTE, l.PROVINCIA_MORTE, l.STATO_MORTE) AS LUOGO_MORTE
            FROM genitori g
            JOIN lista_anagrafica l ON l.ID_ANAGRAFICA = g.ID_FIGLIO
            WHERE g.ID_PADRE = :idPersona
            ORDER BY l.DATA_NASCITA_YYYY, l.DATA_NASCITA_MM, l.DATA_NASCITA_GG, l.COGNOME, l.NOME
        ";
    } else {
        $sqlFigli = "
            SELECT l.ID_ANAGRAFICA, l.COGNOME, l.NOME, l.SESSO,
                   CONCAT_WS('/', l.DATA_NASCITA_GG, l.DATA_NASCITA_MM, l.DATA_NASCITA_YYYY) AS DATA_NASCITA,
                   CONCAT_WS(' ', l.CITTA_NASCITA, l.PROVINCIA_NASCITA, l.STATO_NASCITA) AS LUOGO_NASCITA,
                   CONCAT_WS('/', l.DATA_MORTE_GG, l.DATA_MORTE_MM, l.DATA_MORTE_YYYY) AS DATA_MORTE,
                   CONCAT_WS(' ', l.CITTA_MORTE, l.PROVINCIA_MORTE, l.STATO_MORTE) AS LUOGO_MORTE
            FROM genitori g
            JOIN lista_anagrafica l ON l.ID_ANAGRAFICA = g.ID_FIGLIO
            WHERE g.ID_MADRE = :idPersona
            ORDER BY l.DATA_NASCITA_YYYY, l.DATA_NASCITA_MM, l.DATA_NASCITA_GG, l.COGNOME, l.NOME
        ";
    }

    $stmtFigli = $pdo->prepare($sqlFigli);
    $stmtFigli->execute(['idPersona' => $idPersona]);
    $figli = $stmtFigli->fetchAll(PDO::FETCH_ASSOC);

    if (!$figli || count($figli) === 0) {
        echo "<p>Non disponibile</p>";
    } else {
        echo "<table><tr>
                <td class='label'>Nominativo</td>
                <td class='label'>Data Nascita</td>
                <td class='label'>Luogo Nascita</td>
                <td class='label'>Data Morte</td>
                <td class='label'>Luogo Morte</td>
              </tr>";
        foreach ($figli as $figlio) {
            echo "<tr>";
            echo "<td><a href='view.php?id=" . htmlspecialchars($figlio['ID_ANAGRAFICA']) . "'>" . htmlspecialchars($figlio['COGNOME'] . " " . $figlio['NOME']) . "</a></td>";
            echo "<td>" . htmlspecialchars($figlio['DATA_NASCITA'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($figlio['LUOGO_NASCITA'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($figlio['DATA_MORTE'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($figlio['LUOGO_MORTE'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    ?>
</div>

<?php
// ====== Query documenti ======
$idAnagrafica = $idPersona;
$stmt = $pdo->prepare("
    SELECT b.DESCRIZIONE, fonti.img
    FROM fonti
    INNER JOIN anagrafica_fonti b ON b.ID_FONTE = fonti.ID_FONTE
    WHERE fonti.ID_ANAGRAFICA = :idAnagrafica
");
$stmt->execute(['idAnagrafica' => $idAnagrafica]);
$documenti = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-container">
    <header>
        <h2 class="center-text">📂 Documenti</h2>
    </header>

    <div class="documenti-container">
        <?php foreach ($documenti as $doc): ?>
            <?php 
                $file = htmlspecialchars($doc['img'] ?? "");
                $descrizione = htmlspecialchars($doc['DESCRIZIONE'] ?? "");
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $filePath = "https://memoriadeportati.it/family/images/documenti/" . $file;
            ?>

            <div class="documento-box">
                <p><strong><?= $descrizione ?></strong></p>

                <?php if (in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
                    <!-- 📷 IMMAGINE -->
                    <a href="<?= $filePath ?>" target="_blank">
                        <img src="<?= $filePath ?>" alt="documento" style="max-width:300px; max-height:300px;">
                    </a>

                <?php elseif ($ext === 'pdf'): ?>
                    <!-- 📄 PDF -->
					<object data="view_pdf.php?f=<?= urlencode($file) ?>" type="application/pdf" width="400" height="500"></object>
                    <iframe src="<?= $filePath ?>" style="width:400px; height:500px;" frameborder="0"></iframe>
                    <br><a href="<?= $filePath ?>" target="_blank">Apri PDF</a>

                <?php elseif (in_array($ext, ['mp4','avi','mov','mkv'])): ?>
                    <!-- 🎥 VIDEO -->
                    <video width="400" controls>
                        <source src="<?= $filePath ?>" type="video/<?= $ext ?>">
                        Il tuo browser non supporta il formato video.
                    </video>

                <?php else: ?>
                    <!-- ❓ ALTRO FORMATO -->
                    <a href="<?= $filePath ?>" target="_blank">Scarica documento</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.documenti-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}
.documento-box {
    border: 1px solid #ccc;
    padding: 10px;
    border-radius: 8px;
    width: 420px;
    text-align: center;
    background: #f9f9f9;
}
</style>


<!-- ================= SEZIONE GRAFICO GENEALOGICO ================= -->
<div class="card grafico">
    <h2>Albero genealogico</h2>
    <?php
    $tmpDir = __DIR__ . "/tmp";
    if (!file_exists($tmpDir)) {
        mkdir($tmpDir, 0775, true);
    }

    $dotFile = $tmpDir . "/albero_{$idPersona}.dot";
    $svgFile = $tmpDir . "/albero_{$idPersona}.svg";

    $svgWebPath = "tmp/albero_{$idPersona}.svg"; // percorso web per il browser

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

            $dot .= "\"$id\" [label=\"$label\", style=filled, fillcolor=\"$color\", URL=\"./view.php?id=$id\", target=\"_parent\"];\n";
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
        if (file_put_contents($dotFile, $dot) === false) {
            echo "<p>❌ Errore: impossibile scrivere il file DOT ($dotFile)</p>";
        } else {
            // Esegui Graphviz
            $cmd = "dot -Tsvg " . escapeshellarg($dotFile) . " -o " . escapeshellarg($svgFile);
            exec($cmd . " 2>&1", $output, $return_var);
 sleep(2);
           
if ($return_var !== 0) {
    echo "<p>❌ Errore Graphviz (codice $return_var):<br><pre>" . implode("\n", $output) . "</pre></p>";
} elseif (file_exists($svgFile)) {
    // 🔹 Se vuoi i link cliccabili dentro il grafico, includi inline:
    $svgContent = file_get_contents($svgFile);
    echo "<div style='width:100%;height:100%;overflow:auto; border:1px solid #ccc; border-radius:8px;'>"
       . $svgContent .
       "</div>";

    // 🔹 Se ti basta solo visualizzare come immagine statica, usa questa riga al posto di quelle sopra:
    // echo "<img src='$svgWebPath' alt='Albero genealogico'
    //          style='width:100%; max-height:600px; border:1px solid #ccc; border-radius:8px; object-fit:contain;'>";
} else {
    echo "<p>❌ Graphviz eseguito ma nessun file SVG trovato.</p>";
}
        }
    } else {
        echo "<p>Nessun dato genealogico disponibile</p>";
    }
	
    ?>
</div>
<form method="post" action="stampa_pdf.php" target="_blank">
    <input type="hidden" name="idPersona" value="<?= $idPersona ?>">
    <button type="submit" style="margin-top:20px; padding:10px 20px; border:none; background:#4CAF50; color:white; font-size:16px; border-radius:6px; cursor:pointer;">
        🖨️ Stampa in PDF
    </button>
</form>

<a href="index.php" class="btn btn-secondary mt-3">⬅️ Torna all'elenco</a>
</body>
</html>
