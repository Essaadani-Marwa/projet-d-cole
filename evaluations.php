<?php
require_once 'config.php';
$pdo = getPDOConnection();

// Fonctions CRUD pour les évaluations
function ajouterEvaluation($pdo, $data) {
    $sql = "INSERT INTO evaluations (id_etudiant, id_matiere, note, appreciation, date_evaluation) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['id_etudiant'],
        $data['id_matiere'],
        $data['note'],
        $data['appreciation'],
        $data['date_evaluation'] ?? date('Y-m-d')
    ]);
}

function modifierEvaluation($pdo, $id, $data) {
    $sql = "UPDATE evaluations SET 
            note = ?, 
            appreciation = ?,
            date_evaluation = ?
            WHERE id_evaluation = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['note'],
        $data['appreciation'],
        $data['date_evaluation'] ?? date('Y-m-d'),
        $id
    ]);
}

function supprimerEvaluation($pdo, $id) {
    $sql = "DELETE FROM evaluations WHERE id_evaluation = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}

function getEvaluation($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM evaluations WHERE id_evaluation = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getEvaluationsEtudiant($pdo, $id_etudiant) {
    $sql = "SELECT e.*, m.nom_matiere 
            FROM evaluations e
            JOIN matieres m ON e.id_matiere = m.id_matiere
            WHERE e.id_etudiant = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_etudiant]);
    return $stmt->fetchAll();
}

function getEvaluationsMatiere($pdo, $id_matiere) {
    $sql = "SELECT e.*, et.nom, et.prenom 
            FROM evaluations e
            JOIN etudiants et ON e.id_etudiant = et.id_etudiant
            WHERE e.id_matiere = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_matiere]);
    return $stmt->fetchAll();
}

function calculerMoyenneEtudiant($pdo, $id_etudiant) {
    $sql = "SELECT AVG(note) as moyenne 
            FROM evaluations 
            WHERE id_etudiant = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_etudiant]);
    return $stmt->fetch()['moyenne'];
}

function calculerMoyenneMatiere($pdo, $id_matiere) {
    $sql = "SELECT AVG(note) as moyenne 
            FROM evaluations 
            WHERE id_matiere = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_matiere]);
    return $stmt->fetch()['moyenne'];
}

function genererReleveNotes($pdo, $id_etudiant) {
    $sql = "SELECT m.nom_matiere, e.note, e.appreciation, e.date_evaluation
            FROM evaluations e
            JOIN matieres m ON e.id_matiere = m.id_matiere
            WHERE e.id_etudiant = ?
            ORDER BY m.nom_matiere";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_etudiant]);
    return $stmt->fetchAll();
}

// Traitement des actions
$action = $_POST['action'] ?? '';
$message = '';
$evaluation_a_modifier = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($action) {
            case 'ajouter':
                if (ajouterEvaluation($pdo, $_POST)) {
                    $message = "Évaluation ajoutée avec succès!";
                }
                break;
            case 'modifier':
                if (modifierEvaluation($pdo, $_POST['id'], $_POST)) {
                    $message = "Évaluation modifiée avec succès!";
                }
                break;
            case 'supprimer':
                if (supprimerEvaluation($pdo, $_POST['id'])) {
                    $message = "Évaluation supprimée avec succès!";
                }
                break;
            case 'generer_releve':
                $releve_notes = genererReleveNotes($pdo, $_POST['id_etudiant']);
                break;
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
    }
}

// Récupération des données
$etudiants = $pdo->query("SELECT * FROM etudiants")->fetchAll();
$matieres = $pdo->query("SELECT * FROM matieres")->fetchAll();

if (isset($_POST['modifier_id'])) {
    $evaluation_a_modifier = getEvaluation($pdo, (int)$_POST['modifier_id']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Évaluations</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .form-group { margin-bottom: 15px; }
        .form-container { background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .releve-container { margin-top: 30px; padding: 20px; background: #f0f8ff; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Gestion des Évaluations</h1>
    
    <?php if ($message): ?>
        <p style="color: green;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <!-- Formulaire Ajout/Modification -->
    <div class="form-container">
        <h2><?= ($evaluation_a_modifier) ? 'Modifier' : 'Ajouter' ?> une évaluation</h2>
        <form method="post">
            <input type="hidden" name="action" value="<?= ($evaluation_a_modifier) ? 'modifier' : 'ajouter' ?>">
            <?php if ($evaluation_a_modifier): ?>
                <input type="hidden" name="id" value="<?= $evaluation_a_modifier['id_evaluation'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Étudiant:</label>
                <select name="id_etudiant" required>
                    <option value="">-- Sélectionnez un étudiant --</option>
                    <?php foreach ($etudiants as $etudiant): ?>
                        <option value="<?= $etudiant['id_etudiant'] ?>" 
                            <?= ($evaluation_a_modifier['id_etudiant'] ?? ($_POST['id_etudiant'] ?? '')) == $etudiant['id_etudiant'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Matière:</label>
                <select name="id_matiere" required>
                    <option value="">-- Sélectionnez une matière --</option>
                    <?php foreach ($matieres as $matiere): ?>
                        <option value="<?= $matiere['id_matiere'] ?>" 
                            <?= ($evaluation_a_modifier['id_matiere'] ?? ($_POST['id_matiere'] ?? '')) == $matiere['id_matiere'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($matiere['nom_matiere']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Note (sur 20):</label>
                <input type="number" name="note" min="0" max="20" step="0.01" 
                       value="<?= $evaluation_a_modifier['note'] ?? ($_POST['note'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Appréciation:</label>
                <textarea name="appreciation" rows="3"><?= $evaluation_a_modifier['appreciation'] ?? ($_POST['appreciation'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Date d'évaluation:</label>
                <input type="date" name="date_evaluation" 
                       value="<?= $evaluation_a_modifier['date_evaluation'] ?? ($_POST['date_evaluation'] ?? date('Y-m-d')) ?>" required>
            </div>
            
            <button type="submit"><?= ($evaluation_a_modifier) ? 'Modifier' : 'Ajouter' ?></button>
            <?php if ($evaluation_a_modifier): ?>
                <a href="?" style="margin-left: 10px;">Annuler</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Liste des évaluations -->
    <h2>Liste des évaluations</h2>
    <div class="tabs">
        <button class="tab-button active" onclick="openTab('tab-etudiants')">Par étudiant</button>
        <button class="tab-button" onclick="openTab('tab-matieres')">Par matière</button>
    </div>
    
    <div id="tab-etudiants" class="tab-content" style="display: block;">
        <h3>Évaluations par étudiant</h3>
        <?php foreach ($etudiants as $etudiant): ?>
        <div class="student-evaluations">
            <h4><?= htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']) ?>
                <small>(Moyenne: <?= number_format(calculerMoyenneEtudiant($pdo, $etudiant['id_etudiant']), 2) ?>/20)</small>
                
                <form method="post" style="display: inline; margin-left: 20px;">
                    <input type="hidden" name="action" value="generer_releve">
                    <input type="hidden" name="id_etudiant" value="<?= $etudiant['id_etudiant'] ?>">
                    <button type="submit">Générer relevé</button>
                </form>
            </h4>
            
            <?php $evaluations = getEvaluationsEtudiant($pdo, $etudiant['id_etudiant']); ?>
            <?php if (empty($evaluations)): ?>
                <p>Aucune évaluation</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Matière</th>
                        <th>Note</th>
                        <th>Appréciation</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($evaluations as $eval): ?>
                    <tr>
                        <td><?= htmlspecialchars($eval['nom_matiere']) ?></td>
                        <td><?= htmlspecialchars($eval['note']) ?></td>
                        <td><?= htmlspecialchars($eval['appreciation']) ?></td>
                        <td><?= date('d/m/Y', strtotime($eval['date_evaluation'])) ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="modifier_id" value="<?= $eval['id_evaluation'] ?>">
                                <button type="submit">Modifier</button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id" value="<?= $eval['id_evaluation'] ?>">
                                <button type="submit" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette évaluation?')">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div id="tab-matieres" class="tab-content" style="display: none;">
        <h3>Évaluations par matière</h3>
        <?php foreach ($matieres as $matiere): ?>
        <div class="matiere-evaluations">
            <h4><?= htmlspecialchars($matiere['nom_matiere']) ?>
                <small>(Moyenne: <?= number_format(calculerMoyenneMatiere($pdo, $matiere['id_matiere']), 2) ?>/20)</small>
            </h4>
            
            <?php $evaluations = getEvaluationsMatiere($pdo, $matiere['id_matiere']); ?>
            <?php if (empty($evaluations)): ?>
                <p>Aucune évaluation</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Étudiant</th>
                        <th>Note</th>
                        <th>Appréciation</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($evaluations as $eval): ?>
                    <tr>
                        <td><?= htmlspecialchars($eval['nom'] . ' ' . $eval['prenom']) ?></td>
                        <td><?= htmlspecialchars($eval['note']) ?></td>
                        <td><?= htmlspecialchars($eval['appreciation']) ?></td>
                        <td><?= date('d/m/Y', strtotime($eval['date_evaluation'])) ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="modifier_id" value="<?= $eval['id_evaluation'] ?>">
                                <button type="submit">Modifier</button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id" value="<?= $eval['id_evaluation'] ?>">
                                <button type="submit" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette évaluation?')">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Relevé de notes -->
    <?php if (isset($releve_notes)): ?>
    <div class="releve-container">
        <h2>Relevé de notes</h2>
        <?php $etudiant = $pdo->query("SELECT * FROM etudiants WHERE id_etudiant = ".(int)$_POST['id_etudiant'])->fetch(); ?>
        <h3><?= htmlspecialchars($etudiant['nom'].' '.$etudiant['prenom']) ?></h3>
        <p>Moyenne générale: <?= number_format(calculerMoyenneEtudiant($pdo, $etudiant['id_etudiant']), 2) ?>/20</p>
        
        <table>
            <tr>
                <th>Matière</th>
                <th>Note</th>
                <th>Appréciation</th>
                <th>Date</th>
            </tr>
            <?php foreach ($releve_notes as $note): ?>
            <tr>
                <td><?= htmlspecialchars($note['nom_matiere']) ?></td>
                <td><?= htmlspecialchars($note['note']) ?></td>
                <td><?= htmlspecialchars($note['appreciation']) ?></td>
                <td><?= date('d/m/Y', strtotime($note['date_evaluation'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <button onclick="window.print()">Imprimer le relevé</button>
    </div>
    <?php endif; ?>

    <script>
    function openTab(tabId) {
        // Masquer tous les contenus d'onglets
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.style.display = 'none';
        });
        
        // Désactiver tous les boutons d'onglets
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Afficher l'onglet sélectionné
        document.getElementById(tabId).style.display = 'block';
        
        // Activer le bouton correspondant
        event.currentTarget.classList.add('active');
    }
    </script>
</body>
</html>