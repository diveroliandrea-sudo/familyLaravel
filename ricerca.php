<?php
// ====== Connessione DB ======
$config = parse_ini_file("configDB.cfg", true);
$dbConf = $config['database'];

$pdo = new PDO(
    "mysql:host={$dbConf['host']};dbname={$dbConf['dbname']};charset=utf8mb4",
    $dbConf['user'],
    $dbConf['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ====== Costruzione query dinamica ======
$where = [];
$params = [];

if ($_SERVER["REQUEST_METHOD"] === "GET" && !empty($_GET)) {
    $campi = [
        'cognome' => ['sql' => 'l.COGNOME LIKE :cognome', 'tipo' => 'like'],
        'nome' => ['sql' => 'l.NOME LIKE :nome', 'tipo' => 'like'],
        'sesso' => ['sql' => 'l.SESSO = :sesso', 'tipo' => 'eq'],
        'data_nascita_gg' => ['sql' => 'l.DATA_NASCITA_GG = :dngg', 'tipo' => 'eq'],
        'data_nascita_mm' => ['sql' => 'l.DATA_NASCITA_MM = :dnmm', 'tipo' => 'eq'],
        'data_nascita_yyyy' => ['sql' => 'l.DATA_NASCITA_YYYY = :dnyyyy', 'tipo' => 'eq'],
        'citta_nascita' => ['sql' => 'l.CITTA_NASCITA LIKE :cittaN', 'tipo' => 'like'],
        'provincia_nascita' => ['sql' => 'l.PROVINCIA_NASCITA LIKE :provN', 'tipo' => 'like'],
        'stato_nascita' => ['sql' => 'l.STATO_NASCITA LIKE :statoN', 'tipo' => 'like'],
        'data_morte_gg' => ['sql' => 'l.DATA_MORTE_GG = :dmgg', 'tipo' => 'eq'],
        'data_morte_mm' => ['sql' => 'l.DATA_MORTE_MM = :dmmm', 'tipo' => 'eq'],
        'data_morte_yyyy' => ['sql' => 'l.DATA_MORTE_YYYY = :dmyyyy', 'tipo' => 'eq'],
        'citta_morte' => ['sql' => 'l.CITTA_MORTE LIKE :cittaM', 'tipo' => 'like'],
        'provincia_morte' => ['sql' => 'l.PROVINCIA_MORTE LIKE :provM', 'tipo' => 'like'],
        'stato_morte' => ['sql' => 'l.STATO_MORTE LIKE :statoM', 'tipo' => 'like'],
        'note' => ['sql' => 'l.NOTE LIKE :note', 'tipo' => 'like']
    ];

    foreach ($campi as $campo => $info) {
        if (!empty($_GET[$campo])) {
            $where[] = $info['sql'];
            if ($info['tipo'] === 'like') {
                $params[explode(" ", $info['sql'])[2]] = "%" . $_GET[$campo] . "%";
            } else {
                $params[explode(" ", $info['sql'])[2]] = $_GET[$campo];
            }
        }
    }
}

$sql = "SELECT l.ID_ANAGRAFICA, l.COGNOME, l.NOME, l.SESSO,
               CONCAT_WS('/', l.DATA_NASCITA_GG, l.DATA_NASCITA_MM, l.DATA_NASCITA_YYYY) AS NASCITA,
               CONCAT_WS(' ', l.CITTA_NASCITA, l.PROVINCIA_NASCITA, l.STATO_NASCITA) AS LUOGO_NASCITA,
               CONCAT_WS('/', l.DATA_MORTE_GG, l.DATA_MORTE_MM, l.DATA_MORTE_YYYY) AS MORTE,
               CONCAT_WS(' ', l.CITTA_MORTE, l.PROVINCIA_MORTE, l.STATO_MORTE) AS LUOGO_MORTE,
               l.NOTE
        FROM lista_anagrafica l";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY l.COGNOME, l.NOME";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$risultati = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Ricerca avanzata</title>
<style>
body { font-family: Arial, sans-serif; margin:20px; background:#f8f9fa; }
form { background:#fff; padding:15px; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,.15); margin-bottom:20px;}
fieldset { border:1px solid #ccc; margin-top:10px; padding:10px; border-radius:6px; }
legend { font-weight:bold; }
label { display:block; margin-top:8px; font-weight:bold;}
input, select { width:100%; padding:6px; margin-top:4px;}
button { margin-top:15px; padding:10px 20px; border:none; background:#4CAF50; color:white; border-radius:6px; cursor:pointer;}
table { width:100%; border-collapse:collapse; background:#fff; box-shadow:0 2px 6px rgba(0,0,0,.15);}
th, td { padding:8px; border:1px solid #ddd; }
th { background:#eee; }
</style>
</head>
<body>

<h1>🔍 Ricerca avanzata completa</h1>

<form method="get">
    <fieldset>
        <legend>Dati anagrafici</legend>
        <label>Cognome</label><input type="text" name="cognome" value="<?= htmlspecialchars($_GET['cognome'] ?? '') ?>">
        <label>Nome</label><input type="text" name="nome" value="<?= htmlspecialchars($_GET['nome'] ?? '') ?>">
        <label>Sesso</label>
        <select name="sesso">
            <option value="">--</option>
            <option value="M" <?= ($_GET['sesso'] ?? '')==='M'?'selected':'' ?>>Maschio</option>
            <option value="F" <?= ($_GET['sesso'] ?? '')==='F'?'selected':'' ?>>Femmina</option>
        </select>
    </fieldset>

    <fieldset>
        <legend>Nascita</legend>
        <label>Giorno</label><input type="number" name="data_nascita_gg" value="<?= htmlspecialchars($_GET['data_nascita_gg'] ?? '') ?>">
        <label>Mese</label><input type="number" name="data_nascita_mm" value="<?= htmlspecialchars($_GET['data_nascita_mm'] ?? '') ?>">
        <label>Anno</label><input type="number" name="data_nascita_yyyy" value="<?= htmlspecialchars($_GET['data_nascita_yyyy'] ?? '') ?>">
        <label>Città</label><input type="text" name="citta_nascita" value="<?= htmlspecialchars($_GET['citta_nascita'] ?? '') ?>">
        <label>Provincia</label><input type="text" name="provincia_nascita" value="<?= htmlspecialchars($_GET['provincia_nascita'] ?? '') ?>">
        <label>Stato</label><input type="text" name="stato_nascita" value="<?= htmlspecialchars($_GET['stato_nascita'] ?? '') ?>">
    </fieldset>

    <fieldset>
        <legend>Morte</legend>
        <label>Giorno</label><input type="number" name="data_morte_gg" value="<?= htmlspecialchars($_GET['data_morte_gg'] ?? '') ?>">
        <label>Mese</label><input type="number" name="data_morte_mm" value="<?= htmlspecialchars($_GET['data_morte_mm'] ?? '') ?>">
        <label>Anno</label><input type="number" name="data_morte_yyyy" value="<?= htmlspecialchars($_GET['data_morte_yyyy'] ?? '') ?>">
        <label>Città</label><input type="text" name="citta_morte" value="<?= htmlspecialchars($_GET['citta_morte'] ?? '') ?>">
        <label>Provincia</label><input type="text" name="provincia_morte" value="<?= htmlspecialchars($_GET['provincia_morte'] ?? '') ?>">
        <label>Stato</label><input type="text" name="stato_morte" value="<?= htmlspecialchars($_GET['stato_morte'] ?? '') ?>">
    </fieldset>

    <fieldset>
        <legend>Altro</legend>
        <label>Note</label><input type="text" name="note" value="<?= htmlspecialchars($_GET['note'] ?? '') ?>">
    </fieldset>

    <button type="submit">Cerca</button>
</form>

<?php if (!empty($_GET)): ?>
    <h2>Risultati</h2>
    <?php if ($risultati): ?>
        <table>
            <tr>
                <th>Cognome</th><th>Nome</th><th>Sesso</th>
                <th>Nascita</th><th>Luogo nascita</th>
                <th>Morte</th><th>Luogo morte</th>
                <th>Note</th><th>Dettaglio</th>
            </tr>
            <?php foreach ($risultati as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['COGNOME']?? '') ?></td>
                    <td><?= htmlspecialchars($r['NOME']?? '') ?></td>
                    <td><?= htmlspecialchars($r['SESSO']?? '') ?></td>
                    <td><?= htmlspecialchars($r['NASCITA']?? '') ?></td>
                    <td><?= htmlspecialchars($r['LUOGO_NASCITA']?? '') ?></td>
                    <td><?= htmlspecialchars($r['MORTE']?? '') ?></td>
                    <td><?= htmlspecialchars($r['LUOGO_MORTE']?? '') ?></td>
                    <td><?= htmlspecialchars($r['NOTE']?? '') ?></td>
                    <td><a href="view.php?id=<?= $r['ID_ANAGRAFICA'] ?>">➡️ Vai</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p><em>Nessun risultato trovato</em></p>
    <?php endif; ?>
<?php endif; ?>

<a href="index.php">⬅️ Torna all'elenco</a>
</body>
</html>
