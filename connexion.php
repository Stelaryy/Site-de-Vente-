<?php
session_start();

// Si déjà connecté → catalogue
if (isset($_SESSION["connexion"]) && $_SESSION["connexion"] === "ok") {
    header("Location: catalogue.php");
    exit();
}

// Initialiser les variables de tentative
if (!isset($_SESSION['tentatives'])) {
    $_SESSION['tentatives'] = 0;
}
if (!isset($_SESSION['dernierEchec'])) {
    $_SESSION['dernierEchec'] = 0;
}

// Connexion à la base
require_once __DIR__ . "/code pour connexion/verification_connexion.php";

// Fonction d’authentification
function authentification($identifiant, $motdepasse, $connexion)
{
    try {
        $requete = $connexion->prepare("SELECT mot_de_passe FROM clients_parametres WHERE identifiant = :identifiant");
        $requete->bindParam(':identifiant', $identifiant);
        $requete->execute();

        if ($requete->rowCount() === 1) {
            $resultat = $requete->fetch(PDO::FETCH_ASSOC);
            // ⚠️ à changer plus tard par password_verify() si tu hashes tes mdp
            if ($motdepasse === $resultat['mot_de_passe']) {
                return true;
            }
        }
        return false;
    } catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
        return false;
    }
}

// Variables
$identifiantSaisi = "";
$messageErreur = "";
$tempsRestant = 0;

// Calcul du blocage
if ($_SESSION['tentatives'] >= 3) {
    $tempsRestant = 10 - (time() - $_SESSION['dernierEchec']);
    if ($tempsRestant <= 0) {
        $_SESSION['tentatives'] = 0;
        $tempsRestant = 0;
    }
}

// Traitement formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST" && $tempsRestant <= 0) {
    $identifiant = $_POST['identifiant'] ?? '';
    $motdepasse  = $_POST['motdepasse'] ?? '';
    $identifiantSaisi = htmlspecialchars($identifiant);

    if (authentification($identifiant, $motdepasse, $connexion)) {
        $_SESSION["connexion"] = "ok";
        $_SESSION["id_client"] = $identifiant;
        $_SESSION['tentatives'] = 0;
        header("Location: catalogue.php");
        exit();
    } else {
        $_SESSION['tentatives']++;
        $_SESSION['dernierEchec'] = time();
        $messageErreur = "Identifiant ou mot de passe incorrect.";
        if ($_SESSION['tentatives'] >= 3) {
            $tempsRestant = 10;
            $messageErreur = "Trop de tentatives. Réessayez dans 10 secondes.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Chez Ahmed</title>
    <link rel="stylesheet" href="style pour les page/connexion.css">
</head>
<body>

    <!-- FOND ÉTOILÉ -->
    <div class="stars"></div>
    <div class="stars2"></div>
    <div class="stars3"></div>

    <h1>Connexion</h1>

    <?php if (!empty($messageErreur)): ?>
        <p class="erreur"><?= htmlspecialchars($messageErreur) ?></p>
    <?php endif; ?>

    <form action="connexion.php" method="post" class="form-connexion">
        <label for="identifiant">Identifiant</label>
        <input type="text" id="identifiant" name="identifiant" value="<?= $identifiantSaisi ?>" required <?= $tempsRestant > 0 ? 'disabled' : '' ?>>

        <label for="motdepasse">Mot de passe</label>
        <input type="password" id="motdepasse" name="motdepasse" required <?= $tempsRestant > 0 ? 'disabled' : '' ?>>

        <button type="submit" class="btn-valider" <?= $tempsRestant > 0 ? 'disabled' : '' ?>>Se connecter</button>

        <?php if ($tempsRestant > 0): ?>
            <p class="timer">⏳ Réessayez dans <span id="countdown"><?= $tempsRestant ?></span> secondes...</p>
        <?php endif; ?>
    </form>

    <nav>
        <a href="catalogue.php">← Retour au catalogue</a>
    </nav>

    <?php if ($tempsRestant > 0): ?>
    <script>
        let timeLeft = <?= $tempsRestant ?>;
        const countdown = document.getElementById('countdown');
        const button = document.querySelector('.btn-valider');

        const interval = setInterval(() => {
            timeLeft--;
            countdown.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(interval);
                // Recharge la page pour réactiver le formulaire
                window.location.reload();
            }
        }, 1000);
    </script>
    <?php endif; ?>

</body>
</html>
