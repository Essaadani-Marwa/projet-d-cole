<?php
session_start();
require_once 'config.php';

// Vérification du rôle
if (($_SESSION['user_role'] ?? $_SESSION['role'] ?? null) !== 'admin') {
    // Redirection vers login si pas admin
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .menu { margin-bottom: 20px; padding: 10px; background: #f5f5f5; }
        .menu a { margin-right: 15px; text-decoration: none; color: #0066cc; }
        .menu a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Espace Administrateur</h1>
    
    <div class="menu">
        <!-- Liens vers les fichiers qui existent réellement -->
        <a href="etudiants.php">Gérer les étudiants</a> |
        <a href="enseignants.php">Gérer les enseignants</a> |
        <a href="matieres.php">Gérer les matières</a> |
        <a href="filieres.php">Gérer les filières</a> |
        <a href="evaluations.php">Gérer les évaluations</a> |
        <a href="login.php">Déconnexion</a>
    </div>
    
    <h2>Statistiques</h2>
    <p>Bienvenue, <?= htmlspecialchars($_SESSION['user_email'] ?? $_SESSION['email'] ?? 'Admin') ?>.</p>
    
    <div>
        <h3>Actions rapides :</h3>
        <ul>
            <li><a href="etudiants.php?action=add">Ajouter un étudiant</a></li>
            <li><a href="enseignants.php?action=add">Ajouter un enseignant</a></li>
            <li><a href="evaluations.php">Voir les évaluations</a></li>
        </ul>
    </div>
</body>
</html>