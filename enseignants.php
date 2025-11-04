<?php
require_once 'config.php';
$pdo = getPDOConnection();

function ajouterEnseignant($pdo, $data) {
    $sql = "INSERT INTO enseignants (nom, prenom, email, telephone) 
            VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nom'], 
        $data['prenom'],
        $data['email'],
        $data['telephone']
    ]);
}

function modifierEnseignant($pdo, $id, $data) {
    $sql = "UPDATE enseignants SET 
            nom = ?, prenom = ?, email = ?, telephone = ? 
            WHERE id_enseignant = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nom'], 
        $data['prenom'],
        $data['email'],
        $data['telephone'],
        $id
    ]);
}

function supprimerEnseignant($pdo, $id) {
    // D'abord supprimer les associations avec les matières
    $pdo->prepare("DELETE FROM enseignant_matiere WHERE id_enseignant = ?")->execute([$id]);
    
    // Puis supprimer l'enseignant
    $sql = "DELETE FROM enseignants WHERE id_enseignant = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}

function listerEnseignants($pdo) {
    return $pdo->query("SELECT * FROM enseignants")->fetchAll();
}

function getEnseignant($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM enseignants WHERE id_enseignant = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getMatieresEnseignant($pdo, $id_enseignant) {
    $sql = "SELECT m.id_matiere, m.nom_matiere 
            FROM matieres m
            JOIN enseignant_matiere em ON m.id_matiere = em.id_matiere
            WHERE em.id_enseignant = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_enseignant]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function associerMatieres($pdo, $id_enseignant, $matieres) {
    // D'abord supprimer les anciennes associations
    $pdo->prepare("DELETE FROM enseignant_matiere WHERE id_enseignant = ?")->execute([$id_enseignant]);
    
    // Puis ajouter les nouvelles
    if (!empty($matieres)) {
        $sql = "INSERT INTO enseignant_matiere (id_enseignant, id_matiere) VALUES ";
        $values = [];
        $params = [];
        
        foreach ($matieres as $id_matiere) {
            $values[] = "(?, ?)";
            $params[] = $id_enseignant;
            $params[] = $id_matiere;
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
                if (ajouterEnseignant($pdo, $_POST)) {
                    $id_enseignant = $pdo->lastInsertId();
                    associerMatieres($pdo, $id_enseignant, $_POST['matieres'] ?? []);
                    $message = "Enseignant ajouté avec succès!";
                }
                break;
            case 'modifier':
                if (modifierEnseignant($pdo, $_POST['id'], $_POST)) {
                    associerMatieres($pdo, $_POST['id'], $_POST['matieres'] ?? []);
                    $message = "Enseignant modifié avec succès!";
                }
                break;
            case 'supprimer':
                if (supprimerEnseignant($pdo, $_POST['id'])) {
                    $message = "Enseignant supprimé avec succès!";
                }
                break;
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
    }
}

// Récupération des données
$enseignants = listerEnseignants($pdo);
$matieres = $pdo->query("SELECT * FROM matieres")->fetchAll();

// Pré-remplissage si modification
$enseignant_a_modifier = null;
$matieres_enseignant = [];
if (isset($_POST['modifier_id'])) {
    $enseignant_a_modifier = getEnseignant($pdo, (int)$_POST['modifier_id']);
    $matieres_enseignant = array_column(getMatieresEnseignant($pdo, (int)$_POST['modifier_id']), 'id_matiere');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Enseignants</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .form-group { margin-bottom: 15px; }
        .form-container { margin-bottom: 30px; padding: 20px; background: #f9f9f9; border-radius: 5px; }
        .matieres-list { max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; }
    </style>
</head>
<body>
    <h1>Gestion des Enseignants</h1>
    
    <?php if ($message): ?>
        <p style="color: green;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <!-- Formulaire Ajout/Modification -->
    <div class="form-container">
        <h2><?= ($enseignant_a_modifier) ? 'Modifier' : 'Ajouter' ?> un enseignant</h2>
        <form method="post">
            <input type="hidden" name="action" value="<?= ($enseignant_a_modifier) ? 'modifier' : 'ajouter' ?>">
            <?php if ($enseignant_a_modifier): ?>
                <input type="hidden" name="id" value="<?= $enseignant_a_modifier['id_enseignant'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Nom:</label>
                <input type="text" name="nom" value="<?= $enseignant_a_modifier['nom'] ?? ($_POST['nom'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Prénom:</label>
                <input type="text" name="prenom" value="<?= $enseignant_a_modifier['prenom'] ?? ($_POST['prenom'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="<?= $enseignant_a_modifier['email'] ?? ($_POST['email'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Téléphone:</label>
                <input type="text" name="telephone" value="<?= $enseignant_a_modifier['telephone'] ?? ($_POST['telephone'] ?? '') ?>">
            </div>
            
            
            <div class="form-group">
                <label>Matières enseignées:</label>
                <div class="matieres-list">
                    <?php foreach ($matieres as $matiere): ?>
                    <div>
                        <input type="checkbox" name="matieres[]" value="<?= $matiere['id_matiere'] ?>" 
                            <?= in_array($matiere['id_matiere'], $matieres_enseignant) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($matiere['nom_matiere']) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit"><?= ($enseignant_a_modifier) ? 'Modifier' : 'Ajouter' ?></button>
            <?php if ($enseignant_a_modifier): ?>
                <a href="?" style="margin-left: 10px;">Annuler</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Liste des enseignants -->
    <h2>Liste des enseignants</h2>
    <?php if (empty($enseignants)): ?>
        <p>Aucun enseignant trouvé</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Matières</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($enseignants as $enseignant): ?>
            <tr>
                <td><?= $enseignant['id_enseignant'] ?></td>
                <td><?= htmlspecialchars($enseignant['nom']) ?></td>
                <td><?= htmlspecialchars($enseignant['prenom']) ?></td>
                <td><?= htmlspecialchars($enseignant['email']) ?></td>
                <td><?= htmlspecialchars($enseignant['telephone']) ?></td>
                <td>
                    <?php 
                    $matieres_ens = getMatieresEnseignant($pdo, $enseignant['id_enseignant']);
                    echo implode(', ', array_column($matieres_ens, 'nom_matiere'));
                    ?>
                </td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="modifier_id" value="<?= $enseignant['id_enseignant'] ?>">
                        <button type="submit">Modifier</button>
                    </form>
                    
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id" value="<?= $enseignant['id_enseignant'] ?>">
                        <button type="submit" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet enseignant?')">Supprimer</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>