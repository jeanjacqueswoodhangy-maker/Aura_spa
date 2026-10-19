<?php
/* ============================================================
   db.php — Connexion PDO + helpers globaux
   ============================================================ */

/* --- Connexion PDO (singleton) --- */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $serveur = "localhost";
        $login   = "root";
        $pass    = "jeanjacqueswoodhandy23@";
        $dbname  = "aura";

        try {
            $pdo = new PDO(
                "mysql:host=$serveur;dbname=$dbname;charset=utf8mb4",
                $login, $pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            die("Erreur connexion BD : " . $e->getMessage());
        }
    }
    return $pdo;
}

/* --- Démarrage sécurisé de session --- */
function secure_session_start(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true, // à activer en HTTPS
    ]);
    session_start();
}

/* --- Échappement HTML --- */
function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* --- Liste des soins --- */
const SOINS = [
    'Facial profond',
    'Facial anti-acné',
    'Facial anti-âge',
    'Microdermabrasion',
    'Hydradermabrasion',
    'Massage relaxant',
    'Massage suédois',
    'Massage pierre chaude',
    'Massage deep tissu',
    'Soin aromatherapy',
    'Madérothérapie',
    'Massage anti-cellulite',
    'Lipocavitation',
];