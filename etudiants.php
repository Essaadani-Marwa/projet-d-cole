<?php
require_once 'config.php';
$pdo = getPDOConnection();

// Fonctions CRUD
function ajouterEtudiant($pdo, $data) {
    $sql = "INSERT INTO etudiants (nom, prenom, date_naissance, email, telephone, id_filiere, password) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nom'], 
        $data['prenom'],
        $data['date_naissance'],
        $data['email'],
        $data['telephone'],
        $data['id_filiere'],
        password_hash($data['password'], PASSWORD_BCRYPT)
    ]);
}

function modifierEtudiant($pdo, $id, $data) {
    $sql = "UPDATE etudiants SET 
            nom = ?, prenom = ?, date_naissance = ?, email = ?, 
            telephone = ?, id_filiere = ? 
            WHERE id_etudiant = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nom'], $data['prenom'], $data['date_naissance'],
        $data['email'], $data['telephone'], $data['id_filiere'], $id
    ]);
}

function supprimerEtudiant($pdo, $id) {
    $sql = "DELETE FROM etudiants WHERE id_etudiant = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}

function chercherEtudiants($pdo, $search) {
    $sql = "SELECT e.*, f.nom_filiere FROM etudiants e 
            LEFT JOIN filieres f ON e.id_filiere = f.id_filiere
            WHERE e.nom LIKE ? OR e.prenom LIKE ? OR f.nom_filiere LIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    return $stmt->fetchAll();
}

function listerParFiliere($pdo, $id_filiere) {
    $sql = "SELECT e.*, f.nom_filiere FROM etudiants e 
            LEFT JOIN filieres f ON e.id_filiere = f.id_filiere
            WHERE e.id_filiere = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_filiere]);
    return $stmt->fetchAll();
}

function getNotesEtudiant($pdo, $id_etudiant) {
    $sql = "SELECT m.nom_matiere, 
                   AVG(e.note) as note_moyenne,
                   GROUP_CONCAT(DISTINCT e.appreciation SEPARATOR ' | ') as appreciations
            FROM evaluations e
            JOIN matieres m ON e.id_matiere = m.id_matiere
            WHERE e.id_etudiant = ?
            GROUP BY m.nom_matiere";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_etudiant]);
    return $stmt->fetchAll();
}


// Traitement des actions
$action = $_POST['action'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($action) {
            case 'ajouter':
                if (ajouterEtudiant($pdo, $_POST)) {
                    $message = "Étudiant ajouté avec succès!";
                }
                break;
            case 'modifier':
                if (modifierEtudiant($pdo, $_POST['id'], $_POST)) {
                    $message = "Étudiant modifié avec succès!";
                }
                break;
            case 'supprimer':
                if (supprimerEtudiant($pdo, $_POST['id'])) {
                    $message = "Étudiant supprimé avec succès!";
                }
                break;
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
    }
}

// Récupération des données
$search = $_GET['search'] ?? '';
$id_filiere = $_GET['filiere'] ?? null;

$filieres = $pdo->query("SELECT * FROM filieres")->fetchAll();

if (!empty($search)) {
    $etudiants = chercherEtudiants($pdo, $search);
} elseif (!empty($id_filiere)) {
    $etudiants = listerParFiliere($pdo, $id_filiere);
} else {
    $etudiants = $pdo->query("SELECT e.*, f.nom_filiere FROM etudiants e LEFT JOIN filieres f ON e.id_filiere = f.id_filiere")->fetchAll();
}

// Pré-remplissage du formulaire si modification
$etudiant_a_modifier = null;
if (isset($_POST['modifier_id'])) {
    $etudiant_a_modifier = $pdo->query("SELECT * FROM etudiants WHERE id_etudiant = " . (int)$_POST['modifier_id'])->fetch();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Étudiants</title>
    <style>
          .notes-container {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 5px;
        margin: 10px 0;
    }
    .notes-table {
        width: 100%;
        border-collapse: collapse;
    }
    .notes-table th, .notes-table td {
        border: 1px solid #ddd;
        padding: 8px;
    }
    .notes-table th {
        background-color: #e9ecef;
    }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .form-group { margin-bottom: 15px; }
        .form-container { margin-bottom: 30px; padding: 20px; background: #f9f9f9; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Gestion des Étudiants</h1>
    
    <?php if ($message): ?>
        <p style="color: green;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <!-- Formulaire Ajout/Modification -->
    <div class="form-container">
        <h2><?= ($etudiant_a_modifier) ? 'Modifier' : 'Ajouter' ?> un étudiant</h2>
        <form method="post">
            <input type="hidden" name="action" value="<?= ($etudiant_a_modifier) ? 'modifier' : 'ajouter' ?>">
            <?php if ($etudiant_a_modifier): ?>
                <input type="hidden" name="id" value="<?= $etudiant_a_modifier['id_etudiant'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Nom:</label>
                <input type="text" name="nom" value="<?= $etudiant_a_modifier['nom'] ?? ($_POST['nom'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Prénom:</label>
                <input type="text" name="prenom" value="<?= $etudiant_a_modifier['prenom'] ?? ($_POST['prenom'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Date de naissance:</label>
                <input type="date" name="date_naissance" value="<?= $etudiant_a_modifier['date_naissance'] ?? ($_POST['date_naissance'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="<?= $etudiant_a_modifier['email'] ?? ($_POST['email'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Téléphone:</label>
                <input type="text" name="telephone" value="<?= $etudiant_a_modifier['telephone'] ?? ($_POST['telephone'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Filière:</label>
                <select name="id_filiere" required>
                    <?php foreach ($filieres as $filiere): ?>
                        <option value="<?= $filiere['id_filiere'] ?>" 
                            <?= (($etudiant_a_modifier['id_filiere'] ?? ($_POST['id_filiere'] ?? '')) == $filiere['id_filiere'] ? 'selected' : '') ?>>
                            <?= htmlspecialchars($filiere['nom_filiere']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if (!$etudiant_a_modifier): ?>
                <div class="form-group">
                    <label>Mot de passe:</label>
                    <input type="password" name="password" required>
                </div>
            <?php endif; ?>
            
            <button type="submit"><?= ($etudiant_a_modifier) ? 'Modifier' : 'Ajouter' ?></button>
            <?php if ($etudiant_a_modifier): ?>
                <a href="?" style="margin-left: 10px;">Annuler</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Recherche -->
    <div class="form-container">
        <h2>Rechercher des étudiants</h2>
        <form method="get">
            <input type="text" name="search" placeholder="Nom, prénom ou filière" value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Rechercher</button>
            <a href="?" style="margin-left: 10px;">Réinitialiser</a>
        </form>
    </div>

    <!-- Liste par filière -->
    <div class="form-container">
        <h2>Liste par filière</h2>
        <form method="get">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <select name="filiere" onchange="this.form.submit()">
                <option value="">Toutes les filières</option>
                <?php foreach ($filieres as $filiere): ?>
                    <option value="<?= $filiere['id_filiere'] ?>" 
                        <?= ($id_filiere == $filiere['id_filiere']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($filiere['nom_filiere']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($id_filiere): ?>
                <a href="?" style="margin-left: 10px;">Afficher tous</a>
            <?php endif; ?>
        </form>
    </div>

 

    <!-- Liste des étudiants -->
  <!-- Liste des étudiants -->
<h2>Liste des étudiants</h2>
<?php if (empty($etudiants)): ?>
    <p>Aucun étudiant trouvé</p>
<?php else: ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Date naissance</th>
            <th>Filière</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($etudiants as $etudiant): ?>
        <tr>
            <td><?= $etudiant['id_etudiant'] ?></td>
            <td><?= htmlspecialchars($etudiant['nom']) ?></td>
            <td><?= htmlspecialchars($etudiant['prenom']) ?></td>
            <td><?= date('d/m/Y', strtotime($etudiant['date_naissance'])) ?></td>
            <td><?= htmlspecialchars($etudiant['nom_filiere'] ?? 'Non assigné') ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="modifier_id" value="<?= $etudiant['id_etudiant'] ?>">
                    <button type="submit">Modifier</button>
                </form>
                
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="supprimer">
                    <input type="hidden" name="id" value="<?= $etudiant['id_etudiant'] ?>">
                    <button type="submit" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet étudiant?')">Supprimer</button>
                </form>
                
                <button onclick="toggleNotes(<?= $etudiant['id_etudiant'] ?>)" style="margin-left:5px;">
                    Voir notes
                </button>
            </td>
        </tr>
        <tr id="notes-<?= $etudiant['id_etudiant'] ?>" style="display:none;">
    <td colspan="6">
        <div class="notes-container">
            <?php 
            $notes = getNotesEtudiant($pdo, $etudiant['id_etudiant']);
            if (empty($notes)): ?>
                <p>Aucune note disponible pour cet étudiant.</p>
            <?php else: ?>
                <table class="notes-table">
                    <tr>
                        <th>Matière</th>
                        <th>Note Moyenne</th>
                        <th>Appréciations</th>
                    </tr>
                    <?php foreach ($notes as $note): ?>
                    <tr>
                        <td><?= htmlspecialchars($note['nom_matiere']) ?></td>
                        <td><?= number_format($note['note_moyenne'], 2) ?></td>
                        <td>
                            <?php 
                            $appreciations = array_filter(explode(' | ', $note['appreciations']));
                            if (!empty($appreciations)) {
                                echo '<ul>';
                                foreach (array_unique($appreciations) as $appreciation) {
                                    if (!empty(trim($appreciation))) {
                                        echo '<li>'.htmlspecialchars($appreciation).'</li>';
                                    }
                                }
                                echo '</ul>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </td>
</tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

    <script>
        function toggleNotes(id) {
    const row = document.getElementById('notes-' + id);
    const button = event.target;
    
    if (row.style.display === 'none') {
        row.style.display = 'table-row';
        button.textContent = 'Masquer notes';
        
        // Scroll doux vers les notes
        row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
        row.style.display = 'none';
        button.textContent = 'Voir notes';
    }
}

    // Focus sur le premier champ du formulaire en mode modification
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($etudiant_a_modifier): ?>
            document.querySelector('input[name="nom"]').focus();
        <?php endif; ?>
    });
    </script>
</body>
</html>