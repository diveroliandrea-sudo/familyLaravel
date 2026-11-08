<?php
// Connessione al DB MySQL
$config = parse_ini_file("configDB.cfg");

if (!$config) {
    die("Errore: impossibile leggere configDB.cfg");
}

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Connessione fallita: " . $e->getMessage());
}

// Parametro di ricerca
$search = $_GET['search'] ?? '';
$where = '';
$params = [];

if (!empty($search)) {
    $where = "WHERE COGNOME LIKE :search OR NOME LIKE :search OR CITTA_NASCITA LIKE :search";
    $params[':search'] = "%$search%";
}

// Impaginazione
$perPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

// Conta totale record
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM lista_anagrafica $where");
$totalStmt->execute($params);
$totalRecords = $totalStmt->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

// Query con limit e offset
$stmt = $pdo->prepare("
    SELECT ID_ANAGRAFICA, COGNOME, NOME, 
           CONCAT(LPAD(DATA_NASCITA_GG,2,'0'),'/',LPAD(DATA_NASCITA_MM,2,'0'),'/',DATA_NASCITA_YYYY) AS DATA_NASCITA,
           CONCAT(LPAD(DATA_MORTE_GG,2,'0'),'/',LPAD(DATA_MORTE_MM,2,'0'),'/',DATA_MORTE_YYYY) AS DATA_MORTE,
           CITTA_NASCITA, SESSO, NOTE, FOTOGRAFIA
    FROM lista_anagrafica
    $where
    ORDER BY COGNOME, NOME
    LIMIT :limit OFFSET :offset
");
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$people = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Albero Genealogico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { background: #eef1f7; font-family: "Segoe UI", Tahoma, sans-serif; }
        .container { margin-top: 40px; }
        .card { border-radius: 15px; box-shadow: 0 6px 14px rgba(0,0,0,0.1); }
        h2 { font-weight: 600; background: linear-gradient(90deg, #0d6efd, #6610f2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        table { background: white; border-radius: 10px; overflow: hidden; }
        thead { background: linear-gradient(90deg, #0d6efd, #6610f2); color: white; }
        tbody tr:nth-child(odd) { background-color: #f9fbff; }
        tbody tr:hover { background-color: #e9f0ff; }
        .btn-modern { border-radius: 20px; padding: 6px 16px; font-size: 0.9rem; }
        .pagination .page-link { border-radius: 50%; margin: 0 2px; }
        .foto-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 50%; }
    </style>
</head>
<body>
<div class="container">
    <div class="card p-4">
        <h2 class="mb-4 text-center">Gestione Albero Genealogico</h2>

        <!-- Ricerca -->
        <form method="get" class="mb-4 d-flex justify-content-center">
            <input type="text" name="search" class="form-control w-50 me-2" placeholder="Cerca per nome, cognome o città..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary btn-modern">🔍 Cerca</button>
			<a href="ricerca.php" style="
				display:inline-block;
				padding:10px 20px;
				margin:10px 0;
				background:#007BFF;
				color:white;
				text-decoration:none;
				border-radius:6px;
				font-weight:bold;">
			🔍 Ricerca avanzata
			</a>
        </form>

        <!-- Tabella responsiva -->
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Cognome</th>
                        <th>Nome</th>
                        <th>Data nascita</th>
                        <th>Data morte</th>
                        <th>Città nascita</th>
                                        <!-- Colonna Azioni solo su desktop -->
                        <th class="d-none d-md-table-cell">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($people as $row): 
                    $fotoFile = "./foto/" . ($row['FOTOGRAFIA'] ?? '');
                    if (empty($row['FOTOGRAFIA']) || !file_exists($fotoFile)) {
                        $fotoFile = "./foto/nondisponibile.jpg";
                    }
                ?>
                    <tr>
                        <td><img src="<?= htmlspecialchars($fotoFile) ?>" class="foto-thumb"></td>
                        <td><?= htmlspecialchars($row['COGNOME'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['NOME'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['DATA_NASCITA'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['DATA_MORTE'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['CITTA_NASCITA'] ?? '') ?></td>
                       
                        <!-- Pulsante su desktop -->
                        <td class="d-none d-md-table-cell">
                            <a href="view.php?id=<?= $row['ID_ANAGRAFICA'] ?>" 
                               class="btn btn-sm btn-info btn-modern">
                               👁️ Visualizza
                            </a>
                        </td>
                        <!-- Menu a tendina su mobile -->
                        <td class="d-table-cell d-md-none">
                            <div class="dropdown">
                                <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    ⚙️
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="view.php?id=<?= $row['ID_ANAGRAFICA'] ?>">👁️ Visualizza</a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginazione migliorata -->
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>">«</a>
                </li>
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>">‹</a>
                </li>
                <?php
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">›</a>
                </li>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>">»</a>
                </li>
            </ul>
        </nav>

       <div class="text-center mt-4">
    <a href="insertNuovaPersona.php" class="btn btn-success btn-modern">
        ➕ Inserisci nuova persona
    </a>
</div>
    </div>
</div>
</body>
</html>
