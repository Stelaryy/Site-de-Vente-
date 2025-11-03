<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Achats</title>
    <link rel="stylesheet" href="style pour les page/achat.css">
</head>
<body>
    <div class="stars"></div>
    <div class="stars2"></div>
    <div class="stars3"></div>

    <h1>Mes achats</h1>

    <nav>
        <a href="catalogue.php">Catalogue</a>
        <a href="parametres.php">Paramètres</a>
    </nav>

    <hr>

    <?php
    session_start();
    require_once __DIR__ . "/code pour connexion/verification_connexion.php";

    // Vérification connexion
    if (!isset($_SESSION["connexion"]) || $_SESSION["connexion"] !== "ok") {
        header("Location: connexion.php");
        exit();
    }

    echo "<p class='user'>Connecté en tant que : <strong>" . htmlspecialchars($_SESSION["id_client"]) . "</strong></p>";

    // Récapitulatif du panier
    echo "<section class='recap'>";
    echo "<h2>Récapitulatif du panier</h2>";

    if (empty($_SESSION['panier'])) {
        echo "<p class='vide'>Votre panier est vide.</p>";
    } else {
        $total = 0;
        echo "<div class='panier'>";
        foreach ($_SESSION['panier'] as $id_article => $item) {
            $sous_total = $item['prix'] * $item['quantite'];
            $total += $sous_total;
            echo "
            <div class='produit'>
                <img src='" . htmlspecialchars($item['image']) . "' alt='" . htmlspecialchars($item['nom']) . "'>
                <div class='infos'>
                    <h3>" . htmlspecialchars($item['nom']) . "</h3>
                    <p>Quantité : " . $item['quantite'] . "</p>
                    <p>Prix unitaire : " . $item['prix'] . " €</p>
                    <p class='sous-total'>Sous-total : " . $sous_total . " €</p>
                </div>
            </div>";
        }
        echo "</div>";
        echo "<h3 class='total'>Total à payer : " . $total . " €</h3>";
    }
    echo "</section>";

    // Derniers achats
    echo "<section class='achats'>";
    echo "<h2>Derniers achats</h2>";

    $id_client = $_SESSION['id_client'];
    $stmt = $pdo->prepare("
        SELECT a.nom, a.prix, c.quantite, c.date_achat, a.image
        FROM commandes c
        JOIN articles a ON c.id_article = a.id
        WHERE c.id_client = ?
        ORDER BY c.date_achat DESC
        LIMIT 5
    ");
    $stmt->execute([$id_client]);
    $achats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($achats) {
        echo "<div class='achats-liste'>";
        foreach ($achats as $achat) {
            echo "
            <div class='achat-item'>
                <img src='" . htmlspecialchars($achat['image']) . "' alt='" . htmlspecialchars($achat['nom']) . "'>
                <div class='details'>
                    <h4>" . htmlspecialchars($achat['nom']) . "</h4>
                    <p>" . $achat['quantite'] . " × " . $achat['prix'] . " €</p>
                    <p class='date'>Le " . htmlspecialchars($achat['date_achat']) . "</p>
                </div>
            </div>";
        }
        echo "</div>";
    } else {
        echo "<p class='vide'>Aucun achat effectué pour le moment.</p>";
    }

    echo "</section>";
    ?>
</body>
</html>
