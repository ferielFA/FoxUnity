<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Inclure les contrôleurs
require_once __DIR__ . '/../../controllers/ReclamationController.php';

$reclamationController = new ReclamationController();

// Traitement de la suppression
if (isset($_GET['delete_id'])) {
    $result = $reclamationController->deleteReclamation($_GET['delete_id']);
    if ($result) {
        header("Location: reclamback.php?deleted=1");
        exit;
    }
}

$allReclamations = $reclamationController->getAllReclamations();

// Statistiques
$totalReclamations = count($allReclamations);
$nouveauCount = 0;
$enCoursCount = 0;
$resoluCount = 0;

foreach ($allReclamations as $reclamation) {
    switch ($reclamation['statut']) {
        case 'nouveau':
            $nouveauCount++;
            break;
        case 'en_cours':
            $enCoursCount++;
            break;
        case 'resolu':
            $resoluCount++;
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoxUnity - Dashboard Support</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #ff7a00;
            --primary-dark: #ff4f00;
            --bg-dark: #0a0a0a;
            --bg-card: rgba(20, 20, 20, 0.95);
            --text-light: #ffffff;
            --text-gray: #aaaaaa;
            --border-color: rgba(255, 255, 255, 0.1);
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-dark);
            color: var(--text-light);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles - Identique au dashboard */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.95) 100%);
            border-right: 2px solid var(--border-color);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        
        .dashboard-logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            display: block;
        }
        
        .sidebar h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            text-align: center;
            margin-bottom: 30px;
            color: var(--text-light);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .sidebar a {
            display: block;
            padding: 15px 25px;
            color: var(--text-light);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-weight: 500;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255, 122, 0, 0.1);
            color: var(--primary-color);
            border-left: 3px solid var(--primary-color);
        }
        
        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .topbar h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
            color: var(--text-light);
        }
        
        .user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
        }
        
        .user span {
            font-weight: 600;
            color: var(--text-light);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 25px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 122, 0, 0.3);
            box-shadow: 0 10px 25px rgba(255, 122, 0, 0.2);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 24px;
        }
        
        .stat-icon.reviews {
            background: rgba(33, 150, 243, 0.2);
            color: #2196f3;
        }
        
        .stat-icon.pending {
            background: rgba(255, 152, 0, 0.2);
            color: var(--warning-color);
        }
        
        .stat-icon.responded {
            background: rgba(76, 175, 80, 0.2);
            color: var(--success-color);
        }
        
        .stat-icon.rating {
            background: rgba(255, 122, 0, 0.2);
            color: var(--primary-color);
        }
        
        .stat-info h3 {
            font-size: 14px;
            color: var(--text-gray);
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            font-family: 'Orbitron', sans-serif;
        }
        
        /* Filters */
        .filters-section {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .filters-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-light);
        }
        
        .filter-select, .filter-input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-light);
            font-family: 'Poppins', sans-serif;
        }
        
        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .btn {
            padding: 12px 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 122, 0, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #d32f2f);
        }
        
        /* Reviews List */
        .reviews-section {
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 22px;
        }
        
        .section-header h2 span {
            color: var(--primary-color);
        }
        
        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .review-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
        }
        
        .review-card:hover {
            border-color: rgba(255, 122, 0, 0.3);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .review-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2196f3, #1976d2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .user-info h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .user-info p {
            font-size: 12px;
            color: var(--text-gray);
        }
        
        .review-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--warning-color);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.status-nouveau {
            background: rgba(255, 122, 0, 0.2);
            color: #ff7a00;
            border: 1px solid rgba(255, 122, 0, 0.4);
        }
        
        .status-badge.status-en_cours {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.4);
        }
        
        .status-badge.status-resolu {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            border: 1px solid rgba(76, 175, 80, 0.4);
        }
        
        .review-content {
            margin-bottom: 20px;
        }
        
        .review-text {
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .review-date {
            font-size: 12px;
            color: var(--text-gray);
        }
        
        .review-response {
            background: rgba(255, 122, 0, 0.05);
            border-left: 3px solid var(--primary-color);
            padding: 15px;
            border-radius: 0 10px 10px 0;
            margin-top: 15px;
        }
        
        .response-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .response-header h5 {
            font-size: 14px;
            color: var(--primary-color);
        }
        
        .response-text {
            color: var(--text-light);
            line-height: 1.6;
        }
        
        .review-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .action-btn {
            padding: 8px 15px;
            background: transparent;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        
        .action-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .action-btn.delete:hover {
            border-color: var(--danger-color);
            color: var(--danger-color);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            padding: 30px;
            position: relative;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 22px;
        }
        
        .modal-header h3 span {
            color: var(--primary-color);
        }
        
        .close-modal {
            background: none;
            border: none;
            color: var(--text-gray);
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .close-modal:hover {
            color: var(--primary-color);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-light);
        }
        
        .form-textarea {
            width: 100%;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-light);
            font-family: 'Poppins', sans-serif;
            resize: vertical;
            min-height: 120px;
        }
        
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* Footer */
        .site-footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            border-top: 1px solid var(--border-color);
            color: var(--text-gray);
        }
        
        .site-footer span {
            color: var(--primary-color);
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar h2, .sidebar a span {
                display: none;
            }
            
            .main {
                margin-left: 70px;
            }
            
            .sidebar a {
                text-align: center;
                padding: 15px;
            }
            
            .sidebar a i {
                margin-right: 0;
                font-size: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .review-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .review-actions {
                flex-wrap: wrap;
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .main {
                padding: 15px;
            }
            
            .filters-row {
                flex-direction: column;
            }
            
            .filter-actions {
                width: 100%;
            }
            
            .btn {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Identique au Dashboard -->
        <div class="sidebar">
            <img src="../images/Nine__1_-removebg-preview.png" alt="Nine Tailed Fox Logo" class="dashboard-logo">
            <h2>Dashboard</h2>
            <a href="dashboard.html"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="#"><i class="fas fa-users"></i> <span>Users</span></a>
            <a href="#"><i class="fas fa-shopping-cart"></i> <span>Shop</span></a>
            <a href="#"><i class="fas fa-exchange-alt"></i> <span>Trade History</span></a>
            <a href="#"><i class="fas fa-calendar-alt"></i> <span>Events</span></a>
            <a href="#"><i class="fas fa-newspaper"></i> <span>News</span></a>
            <a href="#" class="active"><i class="fas fa-headset"></i> <span>Support</span></a>
            <a href="../front/reclamation.php"><i class="fas fa-comments"></i> <span>Support Page</span></a>
            <a href="../front/indexf.html"><i class="fas fa-home"></i> <span>Homepage</span></a>
        </div>

        <!-- Main Content -->
        <div class="main">
            <div class="topbar">
                <h1>Dashboard <span>Support</span></h1>
                <div class="user">
                    <img src="../images/meriem.png" alt="User Avatar">
                    <span>FoxLeader</span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon reviews">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Requests</h3>
                        <div class="stat-value"><?php echo $totalReclamations; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>New</h3>
                        <div class="stat-value"><?php echo $nouveauCount; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon responded">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-info">
                        <h3>In Progress</h3>
                        <div class="stat-value"><?php echo $enCoursCount; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon rating">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Resolved</h3>
                        <div class="stat-value"><?php echo $resoluCount; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters-section">
                <div class="filters-row">
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select class="filter-select" id="status-filter">
                            <option value="all">All Requests</option>
                            <option value="nouveau">New</option>
                            <option value="en_cours">In Progress</option>
                            <option value="resolu">Resolved</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Date</label>
                        <select class="filter-select" id="date-filter">
                            <option value="all">All Dates</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button class="btn" id="apply-filters">
                            <i class="fas fa-filter"></i> Apply
                        </button>
                        <button class="btn btn-outline" id="reset-filters">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Support Requests List -->
            <div class="reviews-section">
                <div class="section-header">
                    <h2>All <span>Requests</span></h2>
                    <div class="review-count">
                        Showing <span id="display-count"><?php echo $totalReclamations; ?></span> of <span id="total-count"><?php echo $totalReclamations; ?></span> requests
                    </div>
                </div>
                
                <div class="reviews-list" id="reviews-list">
                    <?php if (empty($allReclamations)): ?>
                        <div class="review-card">
                            <p style="text-align: center; color: var(--text-gray); padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 20px; display: block; opacity: 0.5;"></i>
                                No support requests yet.
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($allReclamations as $reclamation): ?>
                            <div class="review-card" data-status="<?php echo $reclamation['statut']; ?>" data-date="<?php echo $reclamation['date_creation']; ?>">
                                <div class="review-header">
                                    <div class="review-user">
                                        <div class="user-avatar-small"><?php echo strtoupper(substr($reclamation['full_name'], 0, 2)); ?></div>
                                        <div class="user-info">
                                            <h4><?php echo htmlspecialchars($reclamation['full_name']); ?></h4>
                                            <p><?php echo htmlspecialchars($reclamation['email']); ?></p>
                                        </div>
                                    </div>
                                    <div class="review-rating">
                                        <span class="status-badge status-<?php echo $reclamation['statut']; ?>">
                                            <?php 
                                            $statusText = [
                                                'nouveau' => 'New',
                                                'en_cours' => 'In Progress',
                                                'resolu' => 'Resolved'
                                            ];
                                            echo $statusText[$reclamation['statut']] ?? $reclamation['statut'];
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="review-content">
                                    <h5 style="color: var(--primary-color); margin-bottom: 10px;"><?php echo htmlspecialchars($reclamation['subject']); ?></h5>
                                    <p class="review-text">
                                        <?php echo htmlspecialchars($reclamation['message']); ?>
                                    </p>
                                    <div class="review-date">
                                        <i class="far fa-calendar"></i> Posted on <?php echo date('M j, Y H:i', strtotime($reclamation['date_creation'])); ?>
                                    </div>
                                </div>
                                
                                <div class="review-actions">
                                    <button class="action-btn view-request" data-id="<?php echo $reclamation['id_reclamation']; ?>">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>
                                    <button class="action-btn edit-status" data-id="<?php echo $reclamation['id_reclamation']; ?>" data-status="<?php echo $reclamation['statut']; ?>">
                                        <i class="fas fa-edit"></i> Update Status
                                    </button>
                                    <button class="action-btn delete" data-id="<?php echo $reclamation['id_reclamation']; ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <footer class="site-footer">
                © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
            </footer>
        </div>
    </div>
    
    <!-- Response Modal -->
    <div class="modal" id="response-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Réponse à l'<span>Avis</span></h3>
                <button class="close-modal">&times;</button>
            </div>
            
            <div class="review-preview" id="review-preview">
                <!-- Review content will be inserted here -->
            </div>
            
            <form id="response-form">
                <div class="form-group">
                    <label class="form-label">Votre réponse</label>
                    <textarea class="form-textarea" id="response-text" placeholder="Tapez votre réponse ici..."></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" id="cancel-response">Annuler</button>
                    <button type="submit" class="btn" id="submit-response">Publier la réponse</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal" id="delete-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmer la <span>Suppression</span></h3>
                <button class="close-modal">&times;</button>
            </div>
            
            <p>Are you sure you want to delete this request? This action is irreversible.</p>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" id="cancel-delete">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirm-delete">Supprimer</button>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functionality
        const responseModal = document.getElementById('response-modal');
        const deleteModal = document.getElementById('delete-modal');
        const closeModalButtons = document.querySelectorAll('.close-modal');
        const cancelResponse = document.getElementById('cancel-response');
        const cancelDelete = document.getElementById('cancel-delete');
        const responseForm = document.getElementById('response-form');
        
        // Open response modal
        document.querySelectorAll('.add-response, .edit-response').forEach(button => {
            button.addEventListener('click', function() {
                const reviewId = this.getAttribute('data-review');
                const reviewCard = this.closest('.review-card');
                
                // Populate review preview
                const user = reviewCard.querySelector('.user-info h4').textContent;
                const rating = reviewCard.querySelector('.review-rating span').textContent;
                const text = reviewCard.querySelector('.review-text').textContent;
                
                document.getElementById('review-preview').innerHTML = `
                    <div class="review-card" style="margin-bottom: 20px; border: 1px solid var(--border-color);">
                        <div class="review-header">
                            <div class="review-user">
                                <div class="user-info">
                                    <h4>${user}</h4>
                                </div>
                            </div>
                            <div class="review-rating">
                                <span>${rating}</span>
                            </div>
                        </div>
                        <div class="review-content">
                            <p class="review-text">${text}</p>
                        </div>
                    </div>
                `;
                
                // Set form action
                responseForm.setAttribute('data-review', reviewId);
                
                // Clear textarea
                document.getElementById('response-text').value = '';
                
                // Show modal
                responseModal.classList.add('active');
            });
        });
        
        // Open delete modal
        document.querySelectorAll('.action-btn.delete').forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.getAttribute('data-id');
                deleteModal.setAttribute('data-id', requestId);
                deleteModal.classList.add('active');
            });
        });
        
        // Close modals
        function closeModals() {
            responseModal.classList.remove('active');
            deleteModal.classList.remove('active');
        }
        
        closeModalButtons.forEach(button => {
            button.addEventListener('click', closeModals);
        });
        
        cancelResponse.addEventListener('click', closeModals);
        cancelDelete.addEventListener('click', closeModals);
        
        // Submit response
        responseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const reviewId = this.getAttribute('data-review');
            const responseText = document.getElementById('response-text').value.trim();
            
            if (responseText) {
                // In a real application, you would send this to the server
                console.log(`Response to review ${reviewId}: ${responseText}`);
                
                // Close modal and show success message
                closeModals();
                alert('Réponse publiée avec succès!');
            }
        });
        
        // Confirm delete
        document.getElementById('confirm-delete').addEventListener('click', function() {
            const requestId = deleteModal.getAttribute('data-id');
            
            if (requestId) {
                // Redirect to delete
                window.location.href = 'reclamback.php?delete_id=' + requestId;
            } else {
                closeModals();
            }
        });
        
        // Apply filters
        document.getElementById('apply-filters').addEventListener('click', function() {
            const status = document.getElementById('status-filter').value;
            const date = document.getElementById('date-filter').value;
            
            const cards = document.querySelectorAll('.review-card');
            let visibleCount = 0;
            
            cards.forEach(card => {
                const cardStatus = card.getAttribute('data-status');
                const cardDate = new Date(card.getAttribute('data-date'));
                const today = new Date();
                let showCard = true;
                
                // Filter by status
                if (status !== 'all' && cardStatus !== status) {
                    showCard = false;
                }
                
                // Filter by date
                if (date !== 'all' && showCard) {
                    const diffTime = today - cardDate;
                    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                    
                    if (date === 'today' && diffDays !== 0) {
                        showCard = false;
                    } else if (date === 'week' && diffDays > 7) {
                        showCard = false;
                    } else if (date === 'month' && diffDays > 30) {
                        showCard = false;
                    }
                }
                
                if (showCard) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            document.getElementById('display-count').textContent = visibleCount;
        });
        
        // Reset filters
        document.getElementById('reset-filters').addEventListener('click', function() {
            document.getElementById('status-filter').value = 'all';
            document.getElementById('date-filter').value = 'all';
            
            const cards = document.querySelectorAll('.review-card');
            cards.forEach(card => {
                card.style.display = 'block';
            });
            
            document.getElementById('display-count').textContent = cards.length;
        });
        
        // Simple sidebar navigation
        document.querySelectorAll('.sidebar a').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.sidebar a').forEach(nav => {
                    nav.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>