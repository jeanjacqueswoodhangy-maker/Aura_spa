<?php
$pdo = new PDO("mysql:host=localhost;dbname=aura;charset=utf8mb4", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

echo "<h2>Colonnes de la table 'commande' :</h2><pre>";
foreach ($pdo->query("SHOW COLUMNS FROM commande") as $c) {
    echo str_pad($c['Field'], 15) . " | " . str_pad($c['Type'], 30)
       . " | Null: " . $c['Null'] . " | Default: " . ($c['Default'] ?? 'NULL') . "\n";
}

echo "\n<h2>Dernières lignes :</h2>";
$rows = $pdo->query("SELECT id, nom, prenom, produit, created_at FROM commande ORDER BY id DESC LIMIT 5")->fetchAll();
if (!$rows) {
    echo "❌ TABLE VIDE — aucune réservation enregistrée.\n";
    echo "→ Le formulaire index.html n'appelle probablement pas PHP.\n";
} else {
    print_r($rows);
}
echo "</pre>";
?>