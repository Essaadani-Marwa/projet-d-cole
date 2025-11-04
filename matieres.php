<?php
require_once 'config.php';
$pdo = getPDOConnection();

// Fonctions CRUD pour les matières
function ajouterMatiere($pdo, $data) {
    $sql = "INSERT INTO matieres (nom_matiere, id_filiere) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nom_matiere'],
        $data['id_filiere'] ?? null // Permet une valeur NULL si pas de filière
    ]);
}

function modifierMatiere($pdo, $id, $data) {
    $sql = "UPDATE matieres SET nom_matiere = ?, id_filiere = ? WHERE id_matiere = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$data['nom_matiere'], $data['id_filiere'], $id]);
}

function supprimerMatiere($pdo, $id) {
    // D'abord supprimer les associations avec les enseignants
    $pdo->prepare("DELETE FROM enseignant_matiere WHERE id_matiere = ?")->execute([$id]);
    
    // Puis supprimer la matière
    $sql = "DELETE FROM matieres WHERE id_matiere = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}

function listerMatieres($pdo) {
    $sql = "SELECT m.*, f.nom_filiere 
            FROM matieres m
            LEFT JOIN filieres f ON m.id_filiere = f.id_filiere";
    return $pdo->query($sql)->fetchAll();
}

function getMatiere($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM matieres WHERE id_matiere = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getEnseignantsMatiere($pdo, $id_matiere) {
    $sql = "SELECT e.* FROM enseignants e
            JOIN enseignant_matiere em ON e.id_enseignant = em.id_enseignant
            WHERE em.id_matiere = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_matiere]);
    return $stmt->fetchAll();
}

function associerEnseignants($pdo, $id_matiere, $enseignants) {
    // D'abord supprimer les anciennes associations
    $pdo->prepare("DELETE FROM enseignant_matiere WHERE id_matiere = ?")->execute([$id_matiere]);
    
    // Puis ajouter les nouvelles
    if (!empty($enseignants)) {
        $sql = "INSERT INTO enseignant_matiere (id_matiere, id_enseignant) VALUES ";
        $values = [];
        $params = [];
        
        foreach ($enseignants as $id_enseignant) {
            $values[] = "(?, ?)";
            $params[] = $id_matiere;
            $params[] = $id_enseignant;
        }
        
        $sql .= implode(", ", $values);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
}

// Traitement des actions
$action = $_POST['action'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($action) {
            case 'ajouter':
                if (ajouterMatiere($pdo, $_POST)) {
                    $id_matiere = $pdo->lastInsertId();
                    associerEnseignants($pdo, $id_matiere, $_POST['enseignants'] ?? []);
                    $message = "Matière ajoutée avec succès!";
                }
                break;
            case 'modifier':
                if (modifierMatiere($pdo, $_POST['id'], $_POST)) {
                    associerEnseignants($pdo, $_POST['id'], $_POST['enseignants'] ?? []);
                    $message = "Matière modifiée avec succès!";
                }
                break;
            case 'supprimer':
                if (supprimerMatiere($pdo, $_POST['id'])) {
                    $message = "Matière supprimée avec succès!";
                }
                break;
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
    }
}

// Récupération des données
$matieres = listerMatieres($pdo);
$filieres = $pdo->query("SELECT * FROM filieres")->fetchAll();
$enseignants = $pdo->query("SELECT * FROM enseignants")->fetchAll();

// Pré-remplissage si modification
$matiere_a_modifier = null;
$enseignants_matiere = [];
if (isset($_POST['modifier_id'])) {
    $matiere_a_modifier = getMatiere($pdo, (int)$_POST['modifier_id']);
    $enseignants_matiere = array_column(getEnseignantsMatiere($pdo, (int)$_POST['modifier_id']), 'id_enseignant');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Matières</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .form-group { margin-bottom: 15px; }
        .form-container { background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .scroll-list { max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; }
    </style>
</head>
<body>
    <h1>Gestion des Matières</h1>
    
    <?php if ($message): ?>
        <p style="color: green;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <!-- Formulaire Ajout/Modification -->
    <div class="form-container">
        <h2><?= ($matiere_a_modifier) ? 'Modifier' : 'Ajouter' ?> une matière</h2>
        <form method="post">
            <input type="hidden" name="action" value="<?= ($matiere_a_modifier) ? 'modifier' : 'ajouter' ?>">
            <?php if ($matiere_a_modifier): ?>
                <input type="hidden" name="id" value="<?= $matiere_a_modifier['id_matiere'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Nom de la matière:</label>
                <input type="text" name="nom_matiere" value="<?= $matiere_a_modifier['nom_matiere'] ?? ($_POST['nom_matiere'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Filière associée:</label>
                <select name="id_filiere" required>
                    <option value="">-- Sélectionnez une filière --</option>
                    <?php foreach ($filieres as $filiere): ?>
                        <option value="<?= $filiere['id_filiere'] ?>" 
                            <?= ($matiere_a_modifier['id_filiere'] ?? ($_POST['id_filiere'] ?? '')) == $filiere['id_filiere'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($filiere['nom_filiere']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Enseignants associés:</label>
                <div class="scroll-list">
                    <?php foreach ($enseignants as $enseignant): ?>
                        <div>
                            <input type="checkbox" name="enseignants[]" value="<?= $enseignant['id_enseignant'] ?>" 
                                <?= in_array($enseignant['id_enseignant'], $enseignants_matiere) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($enseignant['nom'] . ' ' . $enseignant['prenom']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit"><?= ($matiere_a_modifier) ? 'Modifier' : 'Ajouter' ?></button>
            <?php if ($matiere_a_modifier): ?>
                <a href="?" style="margin-left: 10px;">Annuler</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Liste des matières -->
    <h2>Liste des matières</h2>
    <?php if (empty($matieres)): ?>
        <p>Aucune matière trouvée</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Filière</th>
                <th>Enseignants</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($matieres as $matiere): ?>
            <tr>
                <td><?= $matiere['id_matiere'] ?></td>
                <td><?= htmlspecialchars($matiere['nom_matiere']) ?></td>
                <td><?= htmlspecialchars($matiere['nom_filiere'] ?? 'Non associée') ?></td>
                <td>
                    <?php 
                    $ens = getEnseignantsMatiere($pdo, $matiere['id_matiere']);
                    if (empty($ens)) {
                        echo 'Aucun enseignant';
                    } else {
                        echo implode(', ', array_map(function($e) { 
                            return htmlspecialchars($e['nom'] . ' ' . $e['prenom']); 
                        }, $ens));
                    }
                    ?>
                </td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="modifier_id" value="<?= $matiere['id_matiere'] ?>">
                        <button type="submit">Modifier</button>
                    </form>
                    
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id" value="<?= $matiere['id_matiere'] ?>">
                        <button type="submit" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette matière?')">Supprimer</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>