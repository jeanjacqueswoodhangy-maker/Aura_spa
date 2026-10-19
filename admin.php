<?php
/* ============================================================
   admin.php — Gestion complète des réservations
   Aura Wellness Spa
   ============================================================ */

require __DIR__ . '/db.php';
secure_session_start();

/* --- Connexion PDO (voir db.php) --- */
$connexion = db();

/* --- Helpers --- */

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(403);
        die('Jeton CSRF invalide.');
    }
}

/* --- Limitation des tentatives de connexion (stockée en base, par IP) --- */
function login_bloque(PDO $db, string $ip): bool {
    try {
        $s = $db->prepare("SELECT COUNT(*) FROM login_attempts
                           WHERE ip = :ip AND created_at > (NOW() - INTERVAL 5 MINUTE)");
        $s->execute([':ip' => $ip]);
        return (int)$s->fetchColumn() >= 5;
    } catch (PDOException $ex) {
        error_log('[Aura] login_attempts : ' . $ex->getMessage());
        return false;
    }
}
function login_echec(PDO $db, string $ip): void {
    try {
        $db->prepare("INSERT INTO login_attempts (ip) VALUES (:ip)")->execute([':ip' => $ip]);
        $db->exec("DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)");
    } catch (PDOException $ex) {
        error_log('[Aura] login_attempts : ' . $ex->getMessage());
    }
}
function login_reset(PDO $db, string $ip): void {
    try {
        $db->prepare("DELETE FROM login_attempts WHERE ip = :ip")->execute([':ip' => $ip]);
    } catch (PDOException $ex) {
        error_log('[Aura] login_attempts : ' . $ex->getMessage());
    }
}

/* --- Export CSV : neutralise les formules Excel (=, +, -, @) --- */
function csv_safe($v): string {
    $v = (string)$v;
    if ($v === '') return '';
    /* un numéro de téléphone (chiffres, + espaces . - ( ) ) ne peut pas contenir de formule */
    if (preg_match('/^\+?[0-9][0-9 ().\-]{5,}$/', $v)) return $v;
    if (preg_match('/^[=+\-@\t\r]/', $v)) return "'" . $v;
    return $v;
}

$statuts = [
    'nouvelle' => 'Nouvelle',
    'en_cours' => 'En cours',
    'terminee' => 'Terminée',
    'annulee'  => 'Annulée',
];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* ============================================================
   1) AUTHENTIFICATION
   ============================================================ */
$erreur = '';

if (($_POST['action'] ?? '') === 'login') {
    csrf_check();

    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (login_bloque($connexion, $ip)) {
        $erreur = "Trop de tentatives. Patientez quelques minutes.";
    } else {
        $stmt = $connexion->prepare(
            "SELECT id, username, password_hash FROM admin WHERE username = :u LIMIT 1"
        );
        $stmt->bindValue(':u', $u, PDO::PARAM_STR);
        $stmt->execute();
        $admin = $stmt->fetch();

        if ($admin && password_verify($p, $admin['password_hash'])) {
            login_reset($connexion, $ip);
            session_regenerate_id(true);
            $_SESSION['admin_id']  = (int)$admin['id'];
            $_SESSION['admin_nom'] = $admin['username'];
            header('Location: admin.php');
            exit;
        }

        login_echec($connexion, $ip);
        $erreur = "Identifiants incorrects.";
    }
}

if (($_POST['action'] ?? '') === 'logout') {
    csrf_check();
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php');
    exit;
}

$connecte = isset($_SESSION['admin_id']);

/* ============================================================
   2) ACTIONS PROTÉGÉES
   ============================================================ */
if ($connecte && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    /* --- UPDATE statut --- */
    if ($action === 'statut' && $id > 0) {
        csrf_check();
        $nouveau = $_POST['statut'] ?? '';

        if (isset($statuts[$nouveau])) {
            $stmt = $connexion->prepare("UPDATE commande SET statut = :s WHERE id = :id");
            $stmt->bindValue(':s',  $nouveau, PDO::PARAM_STR);
            $stmt->bindValue(':id', $id,      PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['flash'] = ['ok', "Réservation #$id → " . $statuts[$nouveau]];
        }
        header('Location: admin.php?' . ($_SERVER['QUERY_STRING'] ?? ''));
        exit;
    }

    /* --- DELETE --- */
    if ($action === 'supprimer' && $id > 0) {
        csrf_check();
        $stmt = $connexion->prepare("DELETE FROM commande WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $_SESSION['flash'] = ['ok', "Réservation #$id supprimée."];
        header('Location: admin.php');
        exit;
    }

    /* --- INSERT manuel --- */
    if ($action === 'ajouter') {
        csrf_check();

        $nom     = trim($_POST['nom'] ?? '');
        $prenom  = trim($_POST['prenom'] ?? '');
        $numero  = trim($_POST['numero'] ?? '');
        $produit = trim($_POST['produit'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($nom !== '' && $prenom !== '' && $numero !== '' && in_array($produit, SOINS, true)
            && mb_strlen($nom) <= 60 && mb_strlen($prenom) <= 60
            && mb_strlen($numero) <= 25 && mb_strlen($message) <= 1000) {
            $stmt = $connexion->prepare(
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
            $_SESSION['flash'] = ['ok', "Réservation ajoutée pour $prenom $nom."];
        } else {
            $_SESSION['flash'] = ['warn', "Tous les champs obligatoires doivent être remplis."];
        }
        header('Location: admin.php');
        exit;
    }
}

/* ============================================================
   3) FILTRES + RECHERCHE + PAGINATION
   ============================================================ */
$q       = trim($_GET['q'] ?? '');
$filtre  = $_GET['statut'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$parPage = 15;

$where  = [];
$params = [];

if ($filtre !== '' && isset($statuts[$filtre])) {
    $where[]           = 'statut = :statut';
    $params[':statut'] = $filtre;
}
if ($q !== '') {
    $where[] = '(nom LIKE :q OR prenom LIKE :q OR numero LIKE :q
                 OR produit LIKE :q OR message LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ============================================================
   4) EXPORT CSV
   ============================================================ */
if ($connecte && ($_GET['export'] ?? '') === 'csv') {

    $stmt = $connexion->prepare(
        "SELECT id, nom, prenom, numero, produit, message, statut, created_at
         FROM commande $sqlWhere ORDER BY id DESC"
    );
    $stmt->execute($params);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reservations_' . date('Y-m-d_H-i') . '.csv"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID','Nom','Prénom','Téléphone','Soin','Message','Statut','Date'], ';');

    while ($l = $stmt->fetch()) {
        fputcsv($out, [
            $l['id'], csv_safe($l['nom']), csv_safe($l['prenom']), csv_safe($l['numero']),
            csv_safe($l['produit']), csv_safe($l['message']),
            $statuts[$l['statut']] ?? $l['statut'],
            $l['created_at'],
        ], ';');
    }
    fclose($out);
    exit;
}

/* ============================================================
   5) RÉCUPÉRATION DES DONNÉES
   ============================================================ */
$commandes  = [];
$total      = 0;
$totalPages = 1;
$stats      = [];

if ($connecte) {

    $stats = $connexion
        ->query("SELECT statut, COUNT(*) AS n FROM commande GROUP BY statut")
        ->fetchAll(PDO::FETCH_KEY_PAIR);

    $stmt = $connexion->prepare("SELECT COUNT(*) FROM commande $sqlWhere");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $totalPages = max(1, (int)ceil($total / $parPage));
    $page       = min($page, $totalPages);
    $offset     = ($page - 1) * $parPage;

    $sql = "SELECT id, nom, prenom, numero, produit, message, statut, created_at
            FROM commande $sqlWhere
            ORDER BY id DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $connexion->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit',  $parPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();
    $commandes = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="noindex, nofollow" />
<title>Administration — Réservations | Aura Wellness Spa</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
<style>
  :root{
    --bg:#f4f6f4; --card:#fff; --ink:#1e2a24; --muted:#6b7a72;
    --line:#e2e8e4; --sage:#4f7c62; --sage-dark:#3d6350; --sage-soft:#eef4f0;
    --gold:#c9a227; --danger:#c0392b; --radius:14px;
  }
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--ink);
       font-family:'Inter',system-ui,sans-serif;font-size:15px;line-height:1.5}
  a{color:var(--sage);text-decoration:none}
  a:hover{text-decoration:underline}

  .login-wrap{min-height:100vh;display:grid;place-items:center;padding:24px}
  .login-card{width:100%;max-width:400px;background:var(--card);border-radius:var(--radius);
    padding:38px 34px;box-shadow:0 18px 50px rgba(30,42,36,.09);border:1px solid var(--line)}
  .login-card .logo{width:56px;height:56px;border-radius:50%;background:var(--sage);
    color:#fff;display:grid;place-items:center;font-weight:700;font-size:22px;margin-bottom:18px}
  .login-card h1{font-size:22px;margin:0 0 6px}
  .login-card p.sub{margin:0 0 26px;color:var(--muted);font-size:14px}

  label{display:block;font-weight:600;font-size:13px;margin-bottom:6px}
  input,select,textarea{
    width:100%;padding:11px 13px;border:1px solid var(--line);border-radius:9px;
    font-size:14px;font-family:inherit;background:#fff;color:var(--ink);outline:none;
    transition:border-color .15s,box-shadow .15s;
  }
  input:focus,select:focus,textarea:focus{border-color:var(--sage);
    box-shadow:0 0 0 3px rgba(79,124,98,.14)}
  .field{margin-bottom:16px}

  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;
    padding:11px 18px;border:1px solid transparent;border-radius:9px;font-size:14px;
    font-weight:600;font-family:inherit;cursor:pointer;transition:.15s;white-space:nowrap}
  .btn-primary{background:var(--sage);color:#fff}
  .btn-primary:hover{background:var(--sage-dark)}
  .btn-ghost{background:#fff;border-color:var(--line);color:var(--ink)}
  .btn-ghost:hover{background:var(--sage-soft);border-color:var(--sage)}
  .btn-danger{background:#fff;border-color:#f0c9c4;color:var(--danger)}
  .btn-danger:hover{background:#fdf1ef}
  .btn-sm{padding:6px 11px;font-size:13px;border-radius:7px}
  .btn-full{width:100%}

  .alert{padding:12px 15px;border-radius:9px;font-size:14px;margin-bottom:18px}
  .alert-error{background:#fdf1ef;color:#a33224;border:1px solid #f3cdc7}
  .alert-ok{background:var(--sage-soft);color:var(--sage-dark);border:1px solid #cfe3d7}
  .alert-warn{background:#fff9e8;color:#8a6d0b;border:1px solid #f0e0ac}

  .topbar{background:var(--card);border-bottom:1px solid var(--line);
    padding:0 26px;display:flex;align-items:center;justify-content:space-between;
    height:66px;position:sticky;top:0;z-index:20}
  .topbar .brand{display:flex;align-items:center;gap:12px;font-weight:700}
  .topbar .brand .mark{width:34px;height:34px;border-radius:50%;background:var(--sage);
    color:#fff;display:grid;place-items:center;font-size:15px}
  .topbar .user{display:flex;align-items:center;gap:14px;font-size:14px;color:var(--muted)}

  .wrap{max-width:1280px;margin:0 auto;padding:28px 26px 60px}

  .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));
    gap:14px;margin-bottom:26px}
  .stat{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);
    padding:18px 20px}
  .stat .num{font-size:28px;font-weight:700;line-height:1.1}
  .stat .lbl{font-size:12.5px;color:var(--muted);text-transform:uppercase;
    letter-spacing:.06em;margin-top:4px}
  .stat.total .num{color:var(--ink)}
  .stat.nouvelle .num{color:var(--gold)}
  .stat.en_cours .num{color:#2b6cb0}
  .stat.terminee .num{color:var(--sage)}
  .stat.annulee .num{color:var(--danger)}

  .card{background:var(--card);border:1px solid var(--line);
    border-radius:var(--radius);overflow:hidden;margin-bottom:26px}
  .card-head{padding:16px 20px;border-bottom:1px solid var(--line);
    display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between}
  .filters{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
  .filters input[type=search]{width:250px}
  .filters select{width:auto;min-width:150px}

  .table-scroll{overflow-x:auto}
  table{width:100%;border-collapse:collapse;font-size:14px}
  thead th{text-align:left;padding:12px 14px;background:#fafbfa;color:var(--muted);
    font-size:11.5px;text-transform:uppercase;letter-spacing:.06em;font-weight:600;
    border-bottom:1px solid var(--line);white-space:nowrap}
  tbody td{padding:13px 14px;border-bottom:1px solid #f0f3f1;vertical-align:middle}
  tbody tr:hover{background:#fafcfb}
  tbody tr:last-child td{border-bottom:none}
  .id{color:var(--muted);font-weight:600;font-size:13px}
  .who strong{display:block}

  select.pill{border:none;font-weight:600;padding:5px 10px;border-radius:999px;
    font-size:12px;cursor:pointer}
  .pill-nouvelle{background:#fff6dd;color:#8a6d0b}
  .pill-en_cours{background:#e8f1fb;color:#2b6cb0}
  .pill-terminee{background:var(--sage-soft);color:var(--sage-dark)}
  .pill-annulee{background:#fdf1ef;color:var(--danger)}

  .inline-form{display:inline}

  .empty{padding:60px 20px;text-align:center;color:var(--muted)}
  .empty strong{display:block;font-size:17px;color:var(--ink);margin-bottom:6px}

  .pagination{display:flex;gap:6px;justify-content:center;align-items:center;
    padding:18px;flex-wrap:wrap}
  .pagination a,.pagination span{min-width:36px;height:36px;padding:0 11px;
    display:grid;place-items:center;border:1px solid var(--line);border-radius:8px;
    font-size:13.5px;color:var(--ink);background:#fff}
  .pagination a:hover{background:var(--sage-soft);border-color:var(--sage);text-decoration:none}
  .pagination .current{background:var(--sage);border-color:var(--sage);color:#fff;font-weight:600}
  .pagination .disabled{opacity:.45;pointer-events:none}

  .grid-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:14px;padding:20px}
  .grid-form .full{grid-column:1/-1}

  .msg-cell{max-width:250px;color:var(--muted);font-size:13px;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

  @media (max-width:720px){
    .topbar{padding:0 16px}
    .wrap{padding:20px 16px 50px}
    .filters input[type=search]{width:100%}
    .filters{width:100%}
  }
</style>
</head>
<body>

<?php if (!$connecte): ?>

  <div class="login-wrap">
    <div class="login-card">
      <div class="logo">A</div>
      <h1>Administration</h1>
      <p class="sub">Aura Wellness Spa — Gestion des réservations</p>

      <?php if ($erreur): ?>
        <div class="alert alert-error"><?= e($erreur) ?></div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <input type="hidden" name="action" value="login" />
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />

        <div class="field">
          <label for="username">Nom d'utilisateur</label>
          <input type="text" id="username" name="username" required autofocus />
        </div>
        <div class="field">
          <label for="password">Mot de passe</label>
          <input type="password" id="password" name="password" required />
        </div>
        <button class="btn btn-primary btn-full" type="submit">Se connecter</button>
      </form>
    </div>
  </div>

<?php else: ?>

  <header class="topbar">
    <div class="brand">
      <span class="mark">A</span>
      <span>Aura Wellness — Réservations</span>
    </div>
    <div class="user">
      <span>Connecté : <strong><?= e($_SESSION['admin_nom']) ?></strong></span>
      <form method="post" class="inline-form">
        <input type="hidden" name="action" value="logout" />
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
        <button class="btn btn-ghost btn-sm" type="submit">Déconnexion</button>
      </form>
    </div>
  </header>

  <div class="wrap">

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash[0] === 'ok' ? 'ok' : 'warn' ?>">
        <?= e($flash[1]) ?>
      </div>
    <?php endif; ?>

    <div class="stats">
      <div class="stat total">
        <div class="num"><?= array_sum($stats) ?></div>
        <div class="lbl">Total</div>
      </div>
      <?php foreach ($statuts as $cle => $libelle): ?>
        <div class="stat <?= e($cle) ?>">
          <div class="num"><?= (int)($stats[$cle] ?? 0) ?></div>
          <div class="lbl"><?= e($libelle) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="card">
      <div class="card-head">
        <strong>➕ Ajouter une réservation manuellement</strong>
      </div>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
        <input type="hidden" name="action" value="ajouter" />
        <div class="grid-form">
          <div><label>Nom *</label><input name="nom" required /></div>
          <div><label>Prénom *</label><input name="prenom" required /></div>
          <div><label>Téléphone *</label><input name="numero" required /></div>
          <div><label>Soin *</label>
            <select name="produit" required>
              <option value="">— Choisir —</option>
              <?php foreach (SOINS as $soin): ?>
                <option value="<?= e($soin) ?>"><?= e($soin) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="full"><label>Message</label><input name="message" /></div>
          <div class="full">
            <button class="btn btn-primary" type="submit">Enregistrer la réservation</button>
          </div>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="card-head">
        <form class="filters" method="get" action="admin.php">
          <input type="search" name="q" placeholder="Rechercher…" value="<?= e($q) ?>" />
          <select name="statut">
            <option value="">Tous les statuts</option>
            <?php foreach ($statuts as $cle => $libelle): ?>
              <option value="<?= e($cle) ?>" <?= $filtre === $cle ? 'selected' : '' ?>>
                <?= e($libelle) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-primary btn-sm" type="submit">Filtrer</button>
          <?php if ($q !== '' || $filtre !== ''): ?>
            <a class="btn btn-ghost btn-sm" href="admin.php">Réinitialiser</a>
          <?php endif; ?>
        </form>

        <a class="btn btn-ghost btn-sm"
           href="admin.php?<?= e(http_build_query(array_filter(['q'=>$q,'statut'=>$filtre,'export'=>'csv']))) ?>">
          ⬇ Exporter CSV
        </a>
      </div>

      <?php if (!$commandes): ?>
        <div class="empty">
          <strong>Aucune réservation trouvée</strong>
          <?= ($q || $filtre) ? "Essayez de modifier vos filtres." : "Les nouvelles réservations apparaîtront ici." ?>
        </div>
      <?php else: ?>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Client</th>
                <th>Téléphone</th>
                <th>Soin</th>
                <th>Message</th>
                <th>Date</th>
                <th>Statut</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($commandes as $c): ?>
                <tr>
                  <td class="id">#<?= (int)$c['id'] ?></td>
                  <td class="who">
                    <strong><?= e($c['nom'] . ' ' . $c['prenom']) ?></strong>
                  </td>
                  <td><a href="tel:<?= e($c['numero']) ?>"><?= e($c['numero']) ?></a></td>
                  <td><?= e($c['produit']) ?></td>
                  <td class="msg-cell" title="<?= e($c['message']) ?>">
                    <?= e($c['message'] ?: '—') ?>
                  </td>
                  <td><?= e(date('d/m/Y H:i', strtotime($c['created_at']))) ?></td>

                  <td>
                    <form method="post" class="inline-form">
                      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
                      <input type="hidden" name="action" value="statut" />
                      <input type="hidden" name="id" value="<?= (int)$c['id'] ?>" />
                      <select name="statut" class="pill pill-<?= e($c['statut']) ?>"
                              onchange="this.form.submit()">
                        <?php foreach ($statuts as $cle => $libelle): ?>
                          <option value="<?= e($cle) ?>" <?= $c['statut'] === $cle ? 'selected' : '' ?>>
                            <?= e($libelle) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </form>
                  </td>

                  <td style="text-align:right">
                    <form method="post" class="inline-form"
                          onsubmit="return confirm('Supprimer définitivement #<?= (int)$c['id'] ?> ?');">
                      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
                      <input type="hidden" name="action" value="supprimer" />
                      <input type="hidden" name="id" value="<?= (int)$c['id'] ?>" />
                      <button class="btn btn-danger btn-sm" type="submit">Suppr.</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($totalPages > 1): ?>
          <nav class="pagination">
            <?php
              $base = array_filter(['q' => $q, 'statut' => $filtre]);
              $lien = fn($p) => 'admin.php?' . http_build_query($base + ['page' => $p]);
            ?>
            <a class="<?= $page <= 1 ? 'disabled' : '' ?>"
               href="<?= $page <= 1 ? '#' : e($lien($page - 1)) ?>">‹</a>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <?php if ($i === $page): ?>
                <span class="current"><?= $i ?></span>
              <?php else: ?>
                <a href="<?= e($lien($i)) ?>"><?= $i ?></a>
              <?php endif; ?>
            <?php endfor; ?>
            <a class="<?= $page >= $totalPages ? 'disabled' : '' ?>"
               href="<?= $page >= $totalPages ? '#' : e($lien($page + 1)) ?>">›</a>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

<?php endif; ?>
</body>
</html>