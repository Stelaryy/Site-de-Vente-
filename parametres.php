<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paramètres du compte</title>
    <!-- Lien vers le fichier CSS pour le style -->
    <link rel="stylesheet" href="style pour les page/parametres.css">
</head>
<body>
    <!-- Fond d'écran animé avec des étoiles -->
    <div class="stars"></div>
    <div class="stars2"></div>
    <div class="stars3"></div>

    <?php
    // Démarre la session pour accéder aux infos de l'utilisateur
    session_start();

    // Si l'utilisateur n'est pas connecté, on le renvoie à la page de connexion
    if (!isset($_SESSION["connexion"]) || $_SESSION["connexion"] !== "ok") {
        header("Location: connexion.php");
        exit();
    }

    // On inclut le fichier de connexion à la base de données
    require_once __DIR__ . "/code pour connexion/verification_connexion.php";

    try {
        // Connexion à la base de données
        $connexion = new PDO("mysql:host=$serveur;dbname=$base;charset=utf8", $utilisateur, $motdepasseDB);
        $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // On récupère les informations du client connecté
        $requete = $connexion->prepare("SELECT nom, prenom, date_naissance 
                                        FROM clients_parametres 
                                        WHERE identifiant = :identifiant");
        $requete->bindParam(':identifiant', $_SESSION["id_client"]);
        $requete->execute();

        // On stocke les données dans un tableau associatif
        $client = $requete->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // Si une erreur de connexion ou de requête se produit
        echo "Erreur : " . $e->getMessage();
        exit();
    }
    ?>

    <!-- Titre principal -->
    <h1>Paramètres du compte</h1>

    <!-- Carte contenant les informations du client -->
    <div class="info-card">
        <ul>
            <!-- Affiche les informations de l'utilisateur -->
            <li><strong>Nom :</strong> <?= htmlspecialchars($client['nom']) ?></li>
            <li><strong>Prénom :</strong> <?= htmlspecialchars($client['prenom']) ?></li>
            <li><strong>Date de naissance :</strong> <?= htmlspecialchars($client['date_naissance']) ?></li>
        </ul>

        <!-- Liens vers les autres pages et bouton de déconnexion -->
        <div class="actions">
            <a href="catalogue.php">Catalogue</a>
            <a href="achat.php">Achat</a>

            <!-- Formulaire de déconnexion -->
            <form method="post" action="catalogue.php" style="display:inline;">
                <button type="submit" name="logout">Déconnexion</button>
            </form>
        </div>
    </div>

    <!-- Indique quel utilisateur est connecté -->
    <p class="user-id">Connecté en tant que : <strong><?= htmlspecialchars($_SESSION["id_client"]) ?></strong></p>
</body>
</html>
