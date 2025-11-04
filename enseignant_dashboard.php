<?php
session_start();
require_once 'config.php';

// Vérification améliorée
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

if ($_SESSION['user_role'] !== 'enseignant') {
    header("Location: unauthorized.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Enseignant</title>
</head>
<body>
    <h1>Bienvenue <?= htmlspecialchars($_SESSION['user_email']) ?></h1>
    <!-- Contenu du dashboard -->
</body>
</html>