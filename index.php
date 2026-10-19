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
      content="Aura Wellness Spa à Mahotiere 75 #6 : soins du visage, massages relaxants, pierre chaude, madérothérapie et lipocavitation."
    />
    <title>Aura Wellness Spa | Votre oasis de calme</title>
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
        <a href="index.php#reservation">Réservation</a>
        <a href="contact.html">Contact</a>
      </nav>
    </header>

    <main>
      <section id="accueil" class="hero">
        <img
          class="hero-image"
          src="assets/aura-wellness-spa.jpeg"
          alt="Aura Wellness Spa, soins du visage et massages"
        />
        <div class="hero-overlay"></div>
        <div class="hero-content">
          <p class="eyebrow">Mahotiere 75 #6</p>
          <h1>Aura Wellness Spa</h1>
          <p class="hero-copy">
            Votre Oasis de calme et de sérénité,<br />
            Un espace dédié aux soins du visage, aux soins corporels<br />
            et à la détente du corps pour apaiser l'esprit.
          </p>
          <div class="hero-actions" aria-label="Actions principales">
            <a class="button primary" href="tel:+50934255774">Appeler maintenant</a>
            <a class="button secondary" href="tarifs.html">Voir les tarifs</a>
          </div>
        </div>
      </section>

      <section id="soins" class="section services-section">
        <div class="section-heading">
          <p class="eyebrow">Nos soins</p>
          <h2>Des rituels pour le visage et le corps</h2>
          <p>
            Sélectionnez un soin, puis contactez l'équipe pour confirmer l'horaire et recevoir les
            recommandations adaptées à votre peau ou à votre besoin du moment.
          </p>
        </div>

        <div class="services-grid">
          <article class="service-card">
            <span class="service-icon">01</span>
            <h3>Soins du visage</h3>
            <p>Facial profond, anti-acné, anti-âge, microdermabrasion et hydradermabrasion.</p>
          </article>
          <article class="service-card">
            <span class="service-icon">02</span>
            <h3>Massages bien-être</h3>
            <p>Massage relaxant, massage suédois, aromatherapy et massage deep tissu.</p>
          </article>
          <article class="service-card">
            <span class="service-icon">03</span>
            <h3>Esth&eacute;tiques corporels</h3>
            <p>Massage pierre chaude, madérothérapie, massage anti-cellulite et lipocavitation.</p>
          </article>
        </div>
      </section>

      <section id="experience" class="split-section">
        <div class="split-media" aria-hidden="true">
          <img src="assets/aura-wellness-spa.jpeg" alt="" />
        </div>
        <div class="split-content">
          <p class="eyebrow">L'expérience Aura</p>
          <h2>Un moment pour soi, un instant précieux pour renaître</h2>
          <p>
            Aura Wellness Spa accueille chaque cliente et chaque client dans une atmosphère douce,
            propre et attentive. Les soins sont pensés pour offrir une vraie pause, que vous veniez
            pour détendre les tensions, illuminer le teint ou sculpter la silhouette.
          </p>
          <div class="feature-list">
            <span>Ambiance calme</span>
            <span>Soins personnalisés</span>
            <span>Équipe attentionnée</span>
          </div>
          <div class="route-cta">
            <a class="button primary" href="a-propos.html">En savoir plus</a>
            <a class="button secondary" href="galerie.html">Voir la galerie</a>
          </div>
        </div>
      </section>

      <section class="section offers-section">
        <div class="section-heading">
          <p class="eyebrow">Suggestions</p>
          <h2>Choisir selon votre objectif</h2>
        </div>
        <div class="offers-grid">
          <article>
            <h3>Teint frais</h3>
            <p>Facial profond ou hydradermabrasion pour nettoyer, hydrater et raviver l'éclat.</p>
          </article>
          <article>
            <h3>Détente totale</h3>
            <p>Massage relaxant, suédois ou pierre chaude pour relâcher le dos, la nuque et l'esprit.</p>
          </article>
          <article>
            <h3>Soin silhouette</h3>
            <p>Madérothérapie, anti-cellulite ou lipocavitation pour accompagner vos objectifs corporels.</p>
          </article>
        </div>
        <div class="route-cta">
          <a class="button primary" href="tarifs.html">Consulter tous les tarifs</a>
        </div>
      </section>

      <!-- ============================================================
           BLOC RÉSERVATION — enregistré en base via traitement_reservation.php
           (visible ensuite dans admin.php)
           ============================================================ -->
      <section id="reservation" class="booking-section">
        <div class="booking-copy">
          <p class="eyebrow">Réservation</p>
          <h2>Prenez rendez-vous en quelques secondes</h2>
          <p>
            Choisissez le soin souhaité et laissez vos coordonnées.
            Votre demande sera enregistrée, puis notre équipe vous contactera
            pour confirmer le jour et l'heure.
          </p>
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