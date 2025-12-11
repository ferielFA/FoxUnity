<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification des Réclamations - FoxUnity</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e1e1e 0%, #2d2d2d 100%);
            color: #fff;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        h1 {
            color: #ff7a00;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #ccc;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(255, 122, 0, 0.1);
            border: 1px solid rgba(255, 122, 0, 0.3);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .stat-card h3 {
            color: #ff7a00;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #fff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 8px;
            overflow: hidden;
        }
        thead {
            background: rgba(255, 122, 0, 0.2);
        }
        th {
            padding: 15px;
            text-align: left;
            color: #ff7a00;
            font-weight: 600;
            border-bottom: 2px solid rgba(255, 122, 0, 0.3);
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        tr:hover {
            background: rgba(255, 122, 0, 0.05);
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        .badge-nouveau {
            background: rgba(33, 150, 243, 0.2);
            color: #2196f3;
            border: 1px solid rgba(33, 150, 243, 0.3);
        }
        .badge-resolu {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        .empty {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }
        .empty i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        .refresh-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: linear-gradient(90deg, #ff7a00, #ff4f00);
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.2s;
        }
        .refresh-btn:hover {
            transform: translateY(-2px);
        }
        .description-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-database"></i> Vérification des Réclamations</h1>
        <p class="subtitle">Affichage de toutes les réclamations enregistrées dans la base de données</p>
        
        <a href="view_reclamations.php" class="refresh-btn">
            <i class="fas fa-sync-alt"></i> Actualiser
        </a>

        <?php
        try {
            $db = Config::getConnexion();
            
            // Compter le total de réclamations
            $countQuery = $db->query("SELECT COUNT(*) as total FROM reclamations");
            $totalCount = $countQuery->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Compter par statut
            $pendingQuery = $db->query("SELECT COUNT(*) as total FROM reclamations WHERE statut = 'pending' OR statut IS NULL");
            $pendingCount = $pendingQuery->fetch(PDO::FETCH_ASSOC)['total'];
            
            $nouveauQuery = $db->query("SELECT COUNT(*) as total FROM reclamations WHERE statut = 'nouveau'");
            $nouveauCount = $nouveauQuery->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Récupérer toutes les réclamations
            $query = $db->query("SELECT * FROM reclamations ORDER BY date_creation DESC");
            $reclamations = $query->fetchAll(PDO::FETCH_ASSOC);
            
            ?>
            <div class="stats">
                <div class="stat-card">
                    <h3>Total</h3>
                    <div class="number"><?php echo $totalCount; ?></div>
                </div>
                <div class="stat-card">
                    <h3>En attente</h3>
                    <div class="number"><?php echo $pendingCount; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Nouveau</h3>
                    <div class="number"><?php echo $nouveauCount; ?></div>
                </div>
            </div>

            <?php if (empty($reclamations)): ?>
                <div class="empty">
                    <i class="fas fa-inbox"></i>
                    <h2>Aucune réclamation trouvée</h2>
                    <p>Les réclamations soumises via le formulaire apparaîtront ici.</p>
                    <p style="margin-top: 20px;">
                        <a href="view/front/contact_us.php" style="color: #ff7a00;">Aller au formulaire de contact</a>
                    </p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Sujet</th>
                            <th>Description</th>
                            <th>Date de création</th>
                            <th>Statut</th>
                            <th>ID Utilisateur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reclamations as $reclamation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reclamation['id_reclamation']); ?></td>
                                <td><?php echo htmlspecialchars($reclamation['email']); ?></td>
                                <td><?php echo htmlspecialchars($reclamation['sujet']); ?></td>
                                <td class="description-cell" title="<?php echo htmlspecialchars($reclamation['description']); ?>">
                                    <?php echo htmlspecialchars(substr($reclamation['description'], 0, 50)) . (strlen($reclamation['description']) > 50 ? '...' : ''); ?>
                                </td>
                                <td><?php echo htmlspecialchars($reclamation['date_creation'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php 
                                    $statut = $reclamation['statut'] ?? 'pending';
                                    $badgeClass = 'badge-pending';
                                    if ($statut == 'nouveau') $badgeClass = 'badge-nouveau';
                                    if ($statut == 'resolu') $badgeClass = 'badge-resolu';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars($statut); ?>
                                    </span>
                                </td>
                                <td><?php echo $reclamation['id_utilisateur'] ? htmlspecialchars($reclamation['id_utilisateur']) : 'N/A'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
        <?php
        } catch (PDOException $e) {
            echo '<div style="background: rgba(255, 60, 60, 0.1); border: 1px solid rgba(255, 60, 60, 0.3); padding: 20px; border-radius: 8px; color: #ff6b6b;">';
            echo '<h3 style="color: #ff6b6b; margin-bottom: 10px;"><i class="fas fa-exclamation-circle"></i> Erreur de connexion</h3>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
            <h3 style="color: #ff7a00; margin-bottom: 15px;">Comment vérifier :</h3>
            <ol style="line-height: 2; color: #ccc;">
                <li><strong>Via cette page :</strong> Actualisez cette page après avoir soumis le formulaire</li>
                <li><strong>Via phpMyAdmin :</strong> 
                    <ul style="margin-left: 20px; margin-top: 5px;">
                        <li>Ouvrez <a href="http://localhost/phpmyadmin" target="_blank" style="color: #ff7a00;">phpMyAdmin</a></li>
                        <li>Sélectionnez la base de données <code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 3px;">foxunity0</code></li>
                        <li>Cliquez sur la table <code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 3px;">reclamations</code></li>
                        <li>Cliquez sur l'onglet <strong>"Parcourir"</strong> pour voir toutes les données</li>
                    </ul>
                </li>
                <li><strong>Via le formulaire :</strong> Après soumission, vous devriez voir un message de succès</li>
            </ol>
        </div>
    </div>
</body>
</html>









