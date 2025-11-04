<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'Gestion_scolarite');
define('DB_USER', 'admin_scolarite');
define('DB_PASS', 'NouveauMotDePasse123!');
define('DB_CHARSET', 'utf8mb4');

// Connexion PDO sécurisée
function getPDOConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Vérifie si une fonction existe avant de la déclarer
if (!function_exists('getNotesEtudiant')) {
    function getNotesEtudiant(int $id_etudiant): array {
        $pdo = getPDOConnection();
        
        $sql = "SELECT m.nom_matiere, e.note, e.appreciation, e.date_evaluation
                FROM evaluations e
                JOIN matieres m ON e.id_matiere = m.id_matiere
                WHERE e.id_etudiant = ?
                ORDER BY e.date_evaluation DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_etudiant]);
        return $stmt->fetchAll();
    }
}