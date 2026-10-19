<?php
// create_admin.php — À SUPPRIMER APRÈS UTILISATION

$serveur = "localhost";
$login   = "root";
$pass    = "";
$dbname  = "aura";

try {
    $pdo = new PDO("mysql:host=$serveur;dbname=$dbname;charset=utf8mb4", $login, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("Erreur connexion : " . $e->getMessage());
}

// Modifiez ces deux valeurs
$username = "admin2099";           // votre nom d'utilisateur
$password = "masterchief117"; // votre mot de passe

// Génération du hash sécurisé
$hash = password_hash($password, PASSWORD_DEFAULT);

// Vérifie si l'utilisateur existe déjà
$stmt = $pdo->prepare("SELECT id FROM admin WHERE username = ?");
$stmt->execute([$username]);
$existe = $stmt->fetchColumn();

if ($existe) {
    // Mise à jour du mot de passe
    $stmt = $pdo->prepare("UPDATE admin SET password_hash = ? WHERE username = ?");
    $stmt->execute([$hash, $username]);
    echo "✅ Mot de passe mis à jour pour l'utilisateur <strong>$username</strong>.";
} else {
    // Création d'un nouvel admin
    $stmt = $pdo->prepare("INSERT INTO admin (username, password_hash) VALUES (?, ?)");
    $stmt->execute([$username, $hash]);
    echo "✅ Nouvel administrateur créé : <strong>$username</strong> / <strong>$password</strong>";
}
?>