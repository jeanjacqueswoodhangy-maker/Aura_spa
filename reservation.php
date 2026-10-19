<?php
require __DIR__ . '/db.php';
secure_session_start();

/* Erreurs / valeurs saisies renvoyées par traitement_reservation.php */
$erreurs = $_SESSION['erreurs_reservation'] ?? [];
$old     = $_SESSION['old_reservation'] ?? [];
unset($_SESSION['erreurs_reservation'], $_SESSION['old_reservation']);

/* Retour depuis la page de confirmation : on peut oublier la réservation */
if (isset($_GET['fin'])) {
    unset($_SESSION['reservation_ok']);
}
$val = fn(string $k): string => e($old[$k] ?? '');
?>
<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta
      name="description"
      content="Réservez un soin chez Aura Wellness Spa à Mahotiere 75 #6 : soins du visage, massages relaxants, pierre chaude, madérothérapie et lipocavitation."
    />
    <title>Réservation | Aura Wellness Spa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml" />
    <link rel="stylesheet" href="styles.css" />
  </head>
  <body>
    <header class="site-header" aria-label="Navigation principale">
      <a class="brand" href="index.php" aria-label="Aura Wellness Spa">
        <span class="brand-mark">A</span>
        <span>
          <strong>Aura</strong>
          <small>Wellness Spa</small>
        </span>
      </a>

      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav">
        <span></span>
        <span></span>
        <span></span>
        <span class="sr-only">Menu</span>
      </button>

      <nav id="main-nav" class="main-nav">
        <a href="soins.html">Soins</a>
        <a href="experience.html">Expérience</a>
        <a href="a-propos.html">&Agrave; propos</a>
        <a href="galerie.html">Galerie</a>
        <a href="tarifs.html">Tarifs</a>
        <a href="reservation.php" aria-current="page">Réservation</a>
        <a href="contact.html">Contact</a>
      </nav>
    </header>

    <main>
      <section id="reservation" class="booking-section">
        <div class="booking-copy">
          <p class="eyebrow">Réservation</p>
          <h1>Prenez rendez-vous en quelques secondes</h1>
          <p>
            Choisissez le soin souhaité et laissez vos coordonnées.
            Votre demande sera enregistrée, puis notre équipe vous contactera
            pour confirmer le jour et l'heure.
          </p>
          <div class="route-cta">
            <a class="button secondary" href="tel:+50934255774">Appeler +509 3425-5774</a>
          </div>
        </div>

        <form class="booking-form" action="traitement_reservation.php" method="post">
          <?php if ($erreurs): ?>
            <div role="alert" style="background:#fdecea;color:#8a1f17;border:1px solid #f5c6c1;border-radius:10px;padding:12px 16px;margin-bottom:14px;font-size:14px">
              <strong>Veuillez corriger :</strong>
              <ul style="margin:6px 0 0;padding-left:18px">
                <?php foreach ($erreurs as $m): ?>
                  <li><?= e($m) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <!-- Champ piège anti-spam : doit rester vide -->
          <div style="position:absolute;left:-9999px;height:0;overflow:hidden" aria-hidden="true">
            <label>Ne pas remplir <input type="text" name="website" tabindex="-1" autocomplete="off" /></label>
          </div>

          <label>
            Nom *
            <input type="text" name="nom" required maxlength="60" placeholder="Votre nom" value="<?= $val('nom') ?>" />
          </label>

          <label>
            Prénom *
            <input type="text" name="prenom" required maxlength="60" placeholder="Votre prénom" value="<?= $val('prenom') ?>" />
          </label>

          <label>
            Téléphone *
            <input type="tel" name="telephone" required maxlength="25" inputmode="tel"
                   pattern="\+?[0-9 .\-]{8,25}" placeholder="+509 XXXX-XXXX" value="<?= $val('telephone') ?>" />
          </label>

          <label>
            Soin souhaité *
            <select name="produit" required>
              <option value="">— Choisir un soin —</option>
              <?php foreach (SOINS as $soin): ?>
                <option value="<?= e($soin) ?>" <?= ($old['produit'] ?? '') === $soin ? 'selected' : '' ?>><?= e($soin) ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label>
            Message (facultatif)
            <textarea name="message" rows="4" maxlength="1000"
                      placeholder="Date souhaitée, heure préférée, précision..."><?= $val('message') ?></textarea>
          </label>

          <button class="button primary full" type="submit">
            Envoyer la réservation
          </button>
        </form>
      </section>
    </main>

    <footer id="contact" class="site-footer">
      <div>
        <h2>Aura Wellness Spa</h2>
        <p>Votre oasis de calme et de sérénité.</p>
      </div>
      <address>
        <a href="tel:+50934255774">+509 3425-5774</a>
        <span>Mahotiere 75 #6</span>
        <a href="https://www.instagram.com/aur_awellnessspa" target="_blank" rel="noreferrer">
          @aur_awellnessspa
        </a>
        <a href="https://www.tiktok.com/@aura.wellness.spa" target="_blank" rel="noreferrer">
          @aura.wellness.spa
        </a>
      </address>
    </footer>

    <script src="script.js"></script>
  </body>
</html>