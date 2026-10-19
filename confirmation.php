<?php
/* ============================================================
   confirmation.php
   Page de confirmation — AUCUN bouton WhatsApp
   ============================================================ */

require __DIR__ . '/db.php';
secure_session_start();

/* --- Sécurité : accès direct interdit --- */
if (empty($_SESSION['reservation_ok'])) {
    header('Location: index.php');
    exit;
}

$r = $_SESSION['reservation_ok'];

/* On garde les données pour un éventuel rechargement, 
   mais on les efface après quelques secondes ou au départ */
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Réservation confirmée | Aura Wellness Spa</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet" />
<style>
  :root{
    --bg:#f4f6f4; --card:#fff; --ink:#1e2a24; --muted:#6b7a72;
    --line:#e2e8e4; --sage:#4f7c62; --sage-dark:#3d6350; --sage-soft:#eef4f0;
    --radius:16px;
  }
  *{box-sizing:border-box}
  body{
    margin:0;min-height:100vh;background:var(--bg);color:var(--ink);
    font-family:'Inter',system-ui,sans-serif;font-size:15px;line-height:1.55;
    display:grid;place-items:center;padding:24px;
  }
  .card{
    width:100%;max-width:540px;background:var(--card);border:1px solid var(--line);
    border-radius:var(--radius);padding:44px 36px;text-align:center;
    box-shadow:0 18px 50px rgba(30,42,36,.08);
  }
  .check{
    width:80px;height:80px;border-radius:50%;background:var(--sage-soft);
    display:grid;place-items:center;margin:0 auto 22px;
    border:2px solid #cfe3d7;
  }
  .check svg{width:42px;height:42px;stroke:var(--sage);stroke-width:3;fill:none;
    stroke-linecap:round;stroke-linejoin:round}
  h1{
    font-family:'Playfair Display',serif;font-size:26px;margin:0 0 8px;
    color:var(--ink);font-weight:700;
  }
  .sub{color:var(--muted);font-size:15px;margin:0 0 26px}
  .badge{
    display:inline-block;padding:6px 14px;background:#fff6dd;color:#8a6d0b;
    border-radius:999px;font-size:12px;font-weight:600;text-transform:uppercase;
    letter-spacing:.06em;margin-bottom:20px;
  }
  .recap{
    background:var(--sage-soft);border-radius:12px;padding:18px 20px;
    text-align:left;margin-bottom:26px;
  }
  .recap-row{display:flex;justify-content:space-between;padding:7px 0;font-size:14px}
  .recap-row + .recap-row{border-top:1px dashed #cfe3d7}
  .recap-row .lbl{color:var(--muted)}
  .recap-row .val{font-weight:600;color:var(--ink);text-align:right}
  .btn{
    display:inline-flex;align-items:center;justify-content:center;gap:10px;
    padding:14px 24px;border-radius:11px;font-size:15px;font-weight:600;
    text-decoration:none;cursor:pointer;border:1px solid var(--line);
    background:#fff;color:var(--ink);font-family:inherit;
    transition:background .15s, border-color .15s;width:100%;
  }
  .btn:hover{background:var(--sage-soft);border-color:var(--sage);text-decoration:none}
  .note{
    color:var(--muted);font-size:13px;margin-top:22px;line-height:1.65;
    padding:14px;background:#fafbfa;border-radius:10px;border:1px solid var(--line);
  }
  .note strong{color:var(--ink)}
  @media (max-width:520px){
    .card{padding:32px 22px}
    h1{font-size:22px}
  }
</style>
</head>
<body>
  <div class="card">

    <div class="check">
      <svg viewBox="0 0 24 24">
        <polyline points="20 6 9 17 4 12"></polyline>
      </svg>
    </div>

    <span class="badge">Réservation enregistrée</span>

    <h1>Merci <?= e($r['prenom']) ?> !</h1>
    <p class="sub">
      Votre demande a bien été enregistrée sous le numéro
      <strong>#<?= (int)$r['id'] ?></strong>.<br />
      Notre équipe vous contactera très bientôt pour confirmer votre rendez-vous.
    </p>

    <div class="recap">
      <div class="recap-row">
        <span class="lbl">Nom</span>
        <span class="val"><?= e($r['prenom'] . ' ' . $r['nom']) ?></span>
      </div>
      <div class="recap-row">
        <span class="lbl">Soin souhaité</span>
        <span class="val"><?= e($r['produit']) ?></span>
      </div>
      <div class="recap-row">
        <span class="lbl">Numéro de réservation</span>
        <span class="val">#<?= (int)$r['id'] ?></span>
      </div>
    </div>

    <a class="btn" href="index.php?fin=1">← Retour à l'accueil</a>

    <div class="note">
      <strong><a href="tel:+50934255774" style="color:var(--sage);font-weight:600">Besoin de modifier ou d'annuler ?</a></strong><br />
      Appelez-nous au <a href="tel:+50934255774" style="color:var(--sage);font-weight:600">+509 3425-5774</a>
      en mentionnant votre numéro <strong>#<?= (int)$r['id'] ?></strong>.
    </div>

  </div>
</body>
</html>