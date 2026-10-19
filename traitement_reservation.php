<?php
/* ============================================================
   traitement_reservation.php
   Enregistre la réservation en base — AUCUN WhatsApp
   ============================================================ */

require __DIR__ . '/db.php';
secure_session_start();

/* --- Sécurité : uniquement POST --- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

/* --- Anti-spam : champ piège --- */
if (!empty($_POST['website'])) {
    header('Location: index.php');
    exit;
}

/* --- Récupération + nettoyage --- */
$nom     = trim($_POST['nom']       ?? '');
$prenom  = trim($_POST['prenom']    ?? '');
$numero  = trim($_POST['telephone'] ?? '');
$produit = trim($_POST['produit']   ?? '');
$message = trim($_POST['message']   ?? '');

/* --- Validation --- */
$erreurs = [];

if ($nom === '')                                    $erreurs[] = "Le nom est obligatoire.";
elseif (mb_strlen($nom) > 60)                       $erreurs[] = "Le nom est trop long.";

if ($prenom === '')                                 $erreurs[] = "Le prénom est obligatoire.";
elseif (mb_strlen($prenom) > 60)                    $erreurs[] = "Le prénom est trop long.";

if ($numero === '')                                 $erreurs[] = "Le téléphone est obligatoire.";
elseif (mb_strlen($numero) > 25)                    $erreurs[] = "Le téléphone est invalide.";

if ($produit === '')                                $erreurs[] = "Veuillez choisir un soin.";
elseif (!in_array($produit, SOINS, true))           $erreurs[] = "Ce soin n'est pas reconnu.";

if (mb_strlen($message) > 1000)                     $erreurs[] = "Le message est trop long.";

/* --- En cas d'erreur : on renvoie au formulaire --- */
if ($erreurs) {
    $_SESSION['erreurs_reservation'] = $erreurs;
    $_SESSION['old_reservation'] = [
        'nom'       => $nom,
        'prenom'    => $prenom,
        'telephone' => $numero,
        'produit'   => $produit,
        'message'   => $message,
    ];
    header('Location: index.php#reservation');
    exit;
}

/* --- INSERT en base --- */
try {
    $stmt = db()->prepare(
        "INSERT INTO commande (nom, prenom, numero, produit, quantite, lieu, message, statut)
         VALUES (:nom, :prenom, :numero, :produit, 1, 'Au spa', :message, 'nouvelle')"
    );

    $stmt->execute([
        ':nom'     => $nom,
        ':prenom'  => $prenom,
        ':numero'  => $numero,
        ':produit' => $produit,
        ':message' => $message,
    ]);

    $idCommande = (int) db()->lastInsertId();

    /* --- Préparation des données pour la page de confirmation --- */
    $_SESSION['reservation_ok'] = [
        'id'      => $idCommande,
        'nom'     => $nom,
        'prenom'  => $prenom,
        'produit' => $produit,
    ];

} catch (PDOException $e) {
    /* En prod : log + message générique */
    error_log("Erreur INSERT commande : " . $e->getMessage());
    die("<h2 style='font-family:sans-serif;color:#a33224'>Une erreur est survenue</h2>
         <p style='font-family:sans-serif'>Veuillez réessayer ou nous contacter au +509 3425-5774.</p>
         <p style='font-family:sans-serif'><a href='index.php#reservation'>← Retour au formulaire</a></p>");
}

/* --- Redirection vers la page de confirmation --- */
header('Location: confirmation.php');
exit;