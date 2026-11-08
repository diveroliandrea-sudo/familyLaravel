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
if (!isset($_GET['idAnagrafica'])) {
    die("ID idAnagrafica non fornito");
}
$idPersona = (int) $_GET['idAnagrafica'];
?>

<html lang="IT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="./css/style.css" rel="stylesheet" type="text/css">
    <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,700' rel='stylesheet' type='text/css'>
    <link href="css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link href="css/magnific-popup.css" rel="stylesheet"> 
    <link href="css/templatemo_style.css" rel="stylesheet" type="text/css">    
    <title>Albero Genealogico Family</title>
    
    <script type="text/javascript">
        function modificaDocumento(id, idAnagrafica, idFonte){
            window.open(
                'modificaDocumento.php?fonteID=' + id + '&idAnagrafica=' + idAnagrafica + '&idFonte=' + idFonte,
                'modificaDocumento',
                'width=800,height=600,toolbar=no,location=no,status=yes,menubar=no,scrollbars=no,resizable=no'
            );
        }
    </script>
</head>

<body>

<?php 
$idAnagrafica = (int) $_GET['idAnagrafica'];
$idFonte =(int) $_GET['idFonte']; 



// Descrizione fonte
$stmt = $pdo->prepare("
    SELECT f.*, af.*
    FROM fonti f
    INNER JOIN anagrafica_fonti af ON af.ID_FONTE = f.ID_FONTE
    WHERE f.id_anagrafica = :idAnagrafica
      AND af.ID_FONTE = :idFonte
");
$stmt->execute(['idFonte' => $idFonte, 'idAnagrafica' => $idAnagrafica]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$descrizione = $row ? $row["DescrizioneFonte"] : "";
?>
    
<div class="content-container">
    <header>
        <h1 class="center-text">Galleria Documenti</h1>
        <h2 class="center-text">In questa pagina trovi i documenti caricati</h2>
        <h2 class="center-text"><?php echo htmlspecialchars($descrizione?? ""); ?></h2>
    </header>

    <div id="portfolio-content" class="center-text">
        <div class="portfolio-page" id="page-1">
<?php
// Documenti
$stmt = $pdo->prepare("
    SELECT f.*, af.*
    FROM fonti f
    INNER JOIN anagrafica_fonti af ON af.ID_FONTE = f.ID_FONTE
    WHERE af.ID_FONTE = :idFonte AND f.ID_ANAGRAFICA = :idAnagrafica
");
$stmt->execute(['idFonte' => $idFonte, 'idAnagrafica' => $idAnagrafica]);
$documenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($documenti as $row) {
    $isPdf = (stripos($row["img"], ".pdf") !== false);

    if ($isPdf) {
		echo "<p>" . htmlspecialchars($row["DESCRIZIONE"] ?? "") . "</p>";
echo "<p>" . htmlspecialchars($row["DescrizioneFonte"] ?? "") . "</p>";
echo "<p>Atto: " . htmlspecialchars($row["ATTO"] ?? "") . 
     " Parte: " . htmlspecialchars($row["Parte"] ?? "") . 
     " Serie: " . htmlspecialchars($row["Serie"] ?? "") . "</p>";
echo "<p>Pagina: " . htmlspecialchars($row["pagina"] ?? "") . 
     " Riferimento: " . htmlspecialchars($row["riferimento"] ?? "") . "</p>";
	 

        echo "<iframe src=\"./images/documenti/" . htmlspecialchars($row["img"]?? "") . "\" 
                    style=\"width:700px; height:500px;\" frameborder=\"0\" allowfullscreen></iframe>";
        echo "<br><input type=\"button\" onclick=\"modificaDocumento(".$row["IndiceTabFonti"]."," . $idAnagrafica  ."," . $row["ID_FONTE"] .")\" value=\"Modifica\"/>";

    } else {
        echo "<div class=\"portfolio-group\">";
        echo "<a class=\"portfolio-item\" href=\"./images/documenti/" . htmlspecialchars($row["img"]) . "\" >";
        echo "<img src=\"./images/documenti/" . htmlspecialchars($row["img"]) . "\" alt=\"image\">";
        echo "<div class=\"detail\">";
        echo "<h3>Documento</h3>";
        echo "<p>" . htmlspecialchars($row["DESCRIZIONE"]?? "") . "</p>";
        echo "<p>" . htmlspecialchars($row["DescrizioneFonte"]?? "") . "</p>";
        echo "<p>Atto: " . htmlspecialchars($row["ATTO"]?? "") . 
             " Parte: " . htmlspecialchars($row["Parte"]?? "") . 
             " Serie: " . htmlspecialchars($row["Serie"]?? "") . "</p>";
        echo "<p>Pagina: " . htmlspecialchars($row["pagina"]?? "") . 
             " Riferimento: " . htmlspecialchars($row["riferimento"]?? "") . "</p>";
        echo "<span class=\"btn\">Vedi</span>";
        echo "</div>";
        echo "</a>";
        echo "</div>";
        echo "<input type=\"button\" onclick=\"modificaDocumento(".$row["IndiceTabFonti"]."," . $idAnagrafica  ."," . $row["ID_FONTE"] .")\" value=\"Modifica\"/>";
    }
}
?>
        </div>
    </div>
</div>

<footer>
    <a onClick="window.close();" style="font-size: 20px;background-color:#1E90FF;color:white;padding:2%;align:center;">Chiudi</a>
</footer>

<script type="text/javascript" src="js/jquery-1.11.1.min.js"></script>
<script type="text/javascript" src="js/jquery.easing.1.3.js"></script>
<script type="text/javascript" src="js/modernizr.2.5.3.min.js"></script>
<script type="text/javascript" src="js/jquery.magnific-popup.min.js"></script> 
<script type="text/javascript" src="js/templatemo_script.js"></script>
<script type="text/javascript">
    $(function () {
        $('.portfolio-item').magnificPopup({ 
            type: 'image',
            gallery:{ enabled:true }
        });
    });
</script>
</body>
</html>
