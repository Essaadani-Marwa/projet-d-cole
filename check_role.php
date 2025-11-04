<?php
session_start();
require_once 'config.php';

// Debug session
error_log("Session: " . print_r($_SESSION, true));

$role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;

if (empty($role)) {
    header("Location: login.php");
    exit;
}

// Chemins corrigés avec vérification
$paths = [
    'admin' => 'admin_dashboard.php',
    'enseignant' => 'enseignant_dashboard.php', 
    'etudiant' => 'etudiant_dashboard.php'
];

if (!isset($paths[$role])) {
    header("Location: logout.php");
    exit;
}

// Vérifie que le fichier existe
if (!file_exists($paths[$role])) {
    die("Erreur : Le tableau de bord ($paths[$role]) est introuvable. Contactez l'administrateur.");
}

header("Location: " . $paths[$role]);
exit;