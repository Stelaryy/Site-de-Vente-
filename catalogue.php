<?php 
session_start();
require_once __DIR__ . "/code pour connexion/verification_connexion.php";

// Déconnexion
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: connexion.php");
    exit();
}

// Initialisation panier
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Connexion à la base + catégories
try {
    $pdo = new PDO("mysql:host=$serveur;dbname=$base;charset=utf8", $utilisateur, $motdepasseDB);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 🔹 Récupération des catégories distinctes
    $stmt = $pdo->query("SELECT DISTINCT categorie FROM articles ORDER BY categorie ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 🔹 Filtrage par catégorie (si sélectionnée)
    $categorie_selectionnee = $_GET['categorie'] ?? null;

    if ($categorie_selectionnee) {
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE categorie = ?");
        $stmt->execute([$categorie_selectionnee]);
    } else {
        $stmt = $pdo->query("SELECT * FROM articles");
    }

    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
    exit();
}

// Gestion achat (si connecté)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['acheter']) && isset($_SESSION["connexion"]) && $_SESSION["connexion"] === "ok") {
    $id_article = intval($_POST['id_article']);
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([$id_article]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($article) {
        if (isset($_SESSION['panier'][$id_article])) {
            $_SESSION['panier'][$id_article]['quantite']++;
        } else {
            $_SESSION['panier'][$id_article] = [
                'nom' => $article['nom'],
                'prix' => $article['prix'],
                'quantite' => 1,
                'image' => $article['image']
            ];
        }
    }
}


// Suppression AJAX (si activée)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['supprimer']) && isset($_POST['id_article'])) {
    $id_article = intval($_POST['id_article']);
    if (isset($_SESSION['panier'][$id_article])) {
        unset($_SESSION['panier'][$id_article]);
        echo "✅ Produit supprimé avec succès.";
    } else {
        echo "❌ Produit introuvable dans le panier.";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Catalogue - Chez Ahmed</title>
    <link rel="stylesheet" href="style pour les page/catalogue.css">
</head>
<body>

<div class="stars"></div>
<div class="stars2"></div>
<div class="stars3"></div>

<header>
    <h1>Chez Ahmed</h1>
    <nav>
        <a href="catalogue.php">Catalogue</a>
        <?php if (isset($_SESSION["connexion"]) && $_SESSION["connexion"] === "ok"): ?>
            <a href="achat.php">Page Achat</a>
            <a href="parametres.php">Paramètres</a>
        <?php else: ?>
            <a href="connexion.php">Connexion</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <?php if (isset($_SESSION["connexion"]) && $_SESSION["connexion"] === "ok"): ?>
        <p class="user">Connecté en tant que : <strong><?= htmlspecialchars($_SESSION["id_client"]) ?></strong></p>
    <?php else: ?>
        <p class="user">Vous naviguez en mode invité 🌙 — 
            <a href="connexion.php" style="color:#61dafb;">connectez-vous</a> pour acheter.</p>
    <?php endif; ?>

    <!--BARRE DE CATÉGORIES-->
    <div class="categories">
        <a href="catalogue.php" class="<?= !$categorie_selectionnee ? 'active' : '' ?>">Tous</a>
        <?php foreach ($categories as $cat): ?>
            <a href="catalogue.php?categorie=<?= urlencode($cat) ?>" 
               class="<?= $categorie_selectionnee === $cat ? 'active' : '' ?>">
               <?= htmlspecialchars(ucfirst($cat)) ?>
            </a>
        <?php endforeach; ?>
    </div>

    
        <!--CATALOGUE D’ARTICLES-->
    <div id="message"></div>
    <h2><?= $categorie_selectionnee ? htmlspecialchars(ucfirst($categorie_selectionnee)) : "Catalogue complet" ?></h2>

    <div class="catalogue">
        <?php foreach ($articles as $article): ?>
            <div class="article">
                <div class="image-container">
                    <img src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['nom']) ?>">
                    <div class="overlay">
                        <p class="prix"><?= htmlspecialchars($article['prix']) ?> €</p>
                        <?php if (isset($_SESSION["connexion"]) && $_SESSION["connexion"] === "ok"): ?>
                            <form method="post">
                                <input type="hidden" name="id_article" value="<?= $article['id'] ?>">
                                <button type="submit" name="acheter">Ajouter au panier</button>
                            </form>
                        <?php else: ?>
                            <button type="button" onclick="afficherPopupConnexion()">Ajouter au panier</button>
                        <?php endif; ?>
                    </div>
                </div>
                <h3><?= htmlspecialchars($article['nom']) ?></h3>
            </div>
        <?php endforeach; ?>
    </div>

        
        <!--PANIER (si connecté)-->
    <?php if (isset($_SESSION["connexion"]) && $_SESSION["connexion"] === "ok"): ?>
        <h2>🛒 Mon panier</h2>
        <?php if (empty($_SESSION['panier'])): ?>
            <p class="vide">Votre panier est vide.</p>
        <?php else: 
            $total = 0; ?>
            <ul id="panier">
            <?php foreach ($_SESSION['panier'] as $id_article => $item): 
                $sous_total = $item['prix'] * $item['quantite'];
                $total += $sous_total; ?>
                <li class="produit" data-id="<?= $id_article ?>">
                    <img src="<?= htmlspecialchars($item['image']) ?>" width="40"> 
                    <?= htmlspecialchars($item['nom']) ?> - <?= $item['quantite'] ?> × <?= $item['prix'] ?> € = <?= $sous_total ?> €
                    <button class="supprimer">Supprimer</button>
                </li>
            <?php endforeach; ?>
            </ul>
            <h3 id="total">Total : <?= $total ?> €</h3>

            <form action="achat.php" method="get" style="text-align:center; margin-top:15px;">
                <button type="submit" class="btn-achat">Aller à la page Achat</button>
            </form>
        <?php endif; ?>

        <form method="post" style="text-align:center;">
            <input type="submit" name="logout" value="Déconnexion" class="btn-logout">
        </form>
    <?php endif; ?>

</main>

    <!--POPUP CONNEXION-->
<div id="popup-connexion" class="popup">
    <div class="popup-content">
        <h2>Connexion requise</h2>
        <p>Vous devez être connecté pour ajouter un article à votre panier.</p>
        <div class="popup-actions">
            <a href="connexion.php" class="btn-popup">Se connecter</a>
            <button onclick="fermerPopupConnexion()" class="btn-popup btn-fermer">Fermer</button>
        </div>
    </div>
</div>

<script>
function afficherPopupConnexion() {
    document.getElementById('popup-connexion').style.display = 'flex';
}
function fermerPopupConnexion() {
    document.getElementById('popup-connexion').style.display = 'none';
}
</script>

<script src="script java/catalogue.js"></script>
</body>
</html>
