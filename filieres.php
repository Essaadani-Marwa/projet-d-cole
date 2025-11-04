<?php
require_once 'config.php';
$pdo = getPDOConnection();

// Fonctions CRUD pour les filières
function ajouterFiliere($pdo, $data) {
    $sql = "INSERT INTO filieres (nom_filiere) VALUES (?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$data['nom_filiere']]);
}

function modifierFiliere($pdo, $id, $data) {
    $sql = "UPDATE filieres SET nom_filiere = ? WHERE id_filiere = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$data['nom_filiere'], $id]);
}

function supprimerFiliere($pdo, $id) {
    // D'abord mettre à NULL les références dans matieres
    $pdo->prepare("UPDATE matieres SET id_filiere = NULL WHERE id_filiere = ?")->execute([$id]);
    
    // Puis supprimer la filière
    $sql = "DELETE FROM filieres WHERE id_filiere = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}

function listerFilieres($pdo) {
    return $pdo->query("SELECT * FROM filieres")->fetchAll();
}

function getFiliere($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM filieres WHERE id_filiere = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getMatieresFiliere($pdo, $id_filiere) {
    $sql = "SELECT DISTINCT id_matiere, nom_matiere FROM matieres WHERE id_filiere = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_filiere]);
    return $stmt->fetchAll();
}

function getEtudiantsFiliere($pdo, $id_filiere) {
    $sql = "SELECT DISTINCT id_etudiant, nom, prenom, email 
            FROM etudiants 
            WHERE id_filiere = ? 
            GROUP BY nom, prenom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_filiere]);
    return $stmt->fetchAll();
}

// Traitement des actions
$action = $_POST['action'] ?? '';
$message = '';
$filiere_affichee = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($action) {
            case 'ajouter':
                if (ajouterFiliere($pdo, $_POST)) {
                    $message = "Filière ajoutée avec succès!";
                }
                break;
            case 'modifier':
                if (modifierFiliere($pdo, $_POST['id'], $_POST)) {
                    $message = "Filière modifiée avec succès!";
                }
                break;
            case 'supprimer':
                if (supprimerFiliere($pdo, $_POST['id'])) {
                    $message = "Filière supprimée avec succès!";
                }
                break;
            case 'afficher':
                $filiere_affichee = getFiliere($pdo, $_POST['id']);
                break;
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
    }
}

// Récupération des données
$filieres = listerFilieres($pdo);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Filières</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .form-group { margin-bottom: 15px; }
        .form-container { background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .details-container { margin-top: 30px; padding: 20px; background: #f0f8ff; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Gestion des Filières</h1>
    
    <?php if ($message): ?>
        <p style="color: green;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <!-- Formulaire Ajout/Modification -->
    <div class="form-container">
        <h2><?= (isset($_POST['modifier_id'])) ? 'Modifier' : 'Ajouter' ?> une filière</h2>
        <form method="post">
            <input type="hidden" name="action" value="<?= (isset($_POST['modifier_id'])) ? 'modifier' : 'ajouter' ?>">
            <?php if (isset($_POST['modifier_id'])): ?>
                <input type="hidden" name="id" value="<?= $_POST['modifier_id'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Nom de la filière:</label>
                <input type="text" name="nom_filiere" 
                       value="<?= (isset($_POST['modifier_id']) ? htmlspecialchars(getFiliere($pdo, $_POST['modifier_id'])['nom_filiere']) : '' )?>" 
                       required>
            </div>
            
            <button type="submit"><?= (isset($_POST['modifier_id'])) ? 'Modifier' : 'Ajouter' ?></button>
            <?php if (isset($_POST['modifier_id'])): ?>
                <a href="?" style="margin-left: 10px;">Annuler</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Liste des filières -->
    <h2>Liste des filières</h2>
    <?php if (empty($filieres)): ?>
        <p>Aucune filière trouvée</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($filieres as $filiere): ?>
            <tr>
                <td><?= $filiere['id_filiere'] ?></td>
                <td><?= htmlspecialchars($filiere['nom_filiere']) ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="modifier_id" value="<?= $filiere['id_filiere'] ?>">
                        <button type="submit">Modifier</button>
                    </form>
                    
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id" value="<?= $filiere['id_filiere'] ?>">
                        <button type="submit" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette filière?')">Supprimer</button>
                    </form>
                    
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="afficher">
                        <input type="hidden" name="id" value="<?= $filiere['id_filiere'] ?>">
                        <button type="submit">Détails</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <!-- Détails d'une filière -->
    <?php if ($filiere_affichee): ?>
    <div class="details-container">
        <h2>Détails de la filière: <?= htmlspecialchars($filiere_affichee['nom_filiere']) ?></h2>
        
        <h3>Matières associées</h3>
        <?php $matieres = getMatieresFiliere($pdo, $filiere_affichee['id_filiere']); ?>
        <?php if (empty($matieres)): ?>
            <p>Aucune matière associée</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                </tr>
                <?php foreach ($matieres as $matiere): ?>
                <tr>
                    <td><?= $matiere['id_matiere'] ?></td>
                    <td><?= htmlspecialchars($matiere['nom_matiere']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        
        <h3>Étudiants inscrits</h3>
        <?php $etudiants = getEtudiantsFiliere($pdo, $filiere_affichee['id_filiere']); ?>
        <?php if (empty($etudiants)): ?>
            <p>Aucun étudiant inscrit</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                </tr>
                <?php foreach ($etudiants as $etudiant): ?>
                <tr>
                    <td><?= $etudiant['id_etudiant'] ?></td>
                    <td><?= htmlspecialchars($etudiant['nom']) ?></td>
                    <td><?= htmlspecialchars($etudiant['prenom']) ?></td>
                    <td><?= htmlspecialchars($etudiant['email']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</body>
</html>