<?php
// test_graphviz.php

$dir = __DIR__ . "/tmp";
if (!file_exists($dir)) {
    mkdir($dir, 0775, true);
}

$dotFile = $dir . "/test.dot";
$svgFile = $dir . "/test.svg";

// contenuto DOT minimale
$dot = <<<EOD
digraph G {
    rankdir=LR;
    A [label="Nodo A", shape=box, style=filled, fillcolor="#E6F3FF"];
    B [label="Nodo B", shape=ellipse, style=filled, fillcolor="#FFE6F3"];
    A -> B [label="collegamento"];
}
EOD;

// scrivi file DOT
if (file_put_contents($dotFile, $dot) === false) {
    die("❌ Errore: impossibile scrivere $dotFile\n");
}

// esegui Graphviz
$cmd = "dot -Tsvg " . escapeshellarg($dotFile) . " -o " . escapeshellarg($svgFile);
exec($cmd . " 2>&1", $output, $return_var);

// esito
if ($return_var !== 0) {
    echo "❌ Errore eseguendo Graphviz (codice $return_var)<br>";
    echo "<pre>" . implode("\n", $output) . "</pre>";
} else {
    if (file_exists($svgFile)) {
        echo "✅ SVG generato con successo: $svgFile<br>";
        echo "<object data='tmp/" . basename($svgFile) . "' type='image/svg+xml' style='width:100%; height:400px; border:1px solid #ccc;'></object>";
    } else {
        echo "❌ Graphviz eseguito ma il file SVG non esiste.";
    }
}
?>
