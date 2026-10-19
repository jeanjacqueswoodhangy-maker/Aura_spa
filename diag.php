<?php
$pdo = new PDO("mysql:host=localhost;dbname=aura;charset=utf8mb4", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

echo "<h2>1. Colonnes de la table 'commande'</h2><pre>";
foreach ($pdo->query("SHOW COLUMNS FROM commande") as $c) {
    echo str_pad($c['Field'], 15) . " | " . str_pad($c['Type'], 35)
       . " | Null: {$c['Null']} | Defaut: " . ($c['Default'] ?? 'NULL') . "\n";
}

echo "\n2. Nombre de lignes : ";
echo $pdo->query("SELECT COUNT(*) FROM commande")->fetchColumn();

echo "\n\n3. Dernières 5 lignes :\n";
$rows = $pdo->query("SELECT id, nom, prenom, produit, created_at FROM commande ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
if (!$rows) {
    echo "❌ TABLE VIDE → Le formulaire index n'appelle pas PHP.\n";
} else {
    print_r($rows);
}
echo "</pre>";