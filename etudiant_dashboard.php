<?php
session_start();
require_once 'config.php';

// Vérification du rôle étudiant
if (($_SESSION['user_role'] ?? '') !== 'etudiant') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Étudiant</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .menu { margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Mon Espace Étudiant</h1>
    <nav class="menu">
        <a href="etudiant_moyenne.php">Mes Notes</a> | 
        <a href="logout.php">Déconnexion</a>
    </nav>
    
    <section>
        <h2>Bienvenue <?= htmlspecialchars($_SESSION['user_email'] ?? 'Étudiant') ?></h2>
        <!-- Contenu spécifique -->
    </section>
</body>
</html>