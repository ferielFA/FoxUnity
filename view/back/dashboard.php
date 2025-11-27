<?php
require_once __DIR__ . '/../../controller/EvenementController.php';
require_once __DIR__ . '/../../controller/ParticipationController.php';
require_once __DIR__ . '/../../controller/CommentController.php';
require_once __DIR__ . '/../../controller/TicketController.php';

$eventController = new EvenementController();
$participationController = new ParticipationController();
$commentController = new CommentController();
$ticketController = new TicketController();

// Get recent tickets
$recentTickets = $ticketController->getAllTickets();
$recentTickets = array_slice($recentTickets, 0, 10); // Get last 10 tickets

// Récupérer toutes les données
$evenements = $eventController->lireTous();

// Statistiques globales
$totalEvents = count($evenements);
$totalParticipants = 0;
$totalComments = 0;
$upcomingEvents = 0;
$completedEvents = 0;
$totalRatings = 0;
$sumRatings = 0;

$now = new DateTime();

// Données pour les graphiques
$participationTrend = []; // Dernier 7 jours
$eventsByStatus = ['upcoming' => 0, 'ongoing' => 0, 'completed' => 0, 'cancelled' => 0];
$topEvents = [];
$ratingDistribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$hourlyRegistrations = array_fill(0, 24, 0);

foreach ($evenements as $item) {
    $event = $item['evenement'];
    $totalParticipants += $item['nb_participants'];
    
    // Statut des événements
    $eventsByStatus[$event->getStatut()]++;
    
    if ($event->getDateFin() < $now) {
        $completedEvents++;
    } else {
        $upcomingEvents++;
    }
    
    // Top événements par participants
    $topEvents[] = [
        'titre' => $event->getTitre(),
        'participants' => $item['nb_participants']
    ];
    
    // Récupérer commentaires et notes pour chaque événement
    $eventComments = $commentController->getEventComments($event->getIdEvenement());
    $totalComments += count($eventComments);
    
    foreach ($eventComments as $comment) {
        $rating = $comment->getRating();
        $ratingDistribution[$rating]++;
        $totalRatings++;
        $sumRatings += $rating;
    }
    
    // Récupérer les participations pour analyse horaire
    $participants = $participationController->lireParEvenement($event->getIdEvenement());
    foreach ($participants as $participant) {
        $hour = (int)$participant->getDateParticipation()->format('H');
        $hourlyRegistrations[$hour]++;
    }
}

// Trier top événements
usort($topEvents, fn($a, $b) => $b['participants'] - $a['participants']);
$topEvents = array_slice($topEvents, 0, 5);

// Calculer moyenne générale
$averageRating = $totalRatings > 0 ? round($sumRatings / $totalRatings, 1) : 0;

// Tendance des participations (simulée pour les 7 derniers jours)
$participationTrend = [
    ['day' => 'Lun', 'count' => rand(5, 20)],
    ['day' => 'Mar', 'count' => rand(5, 20)],
    ['day' => 'Mer', 'count' => rand(5, 20)],
    ['day' => 'Jeu', 'count' => rand(5, 20)],
    ['day' => 'Ven', 'count' => rand(5, 20)],
    ['day' => 'Sam', 'count' => rand(5, 20)],
    ['day' => 'Dim', 'count' => rand(5, 20)]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Nine Tailed Fox Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Poppins:wght@300;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="dashboard-body">
  <div class="stars"></div>
<div class="shooting-star"></div>
<div class="shooting-star"></div>
<div class="shooting-star"></div>

  <!-- ===== SIDEBAR ===== -->
  <div class="sidebar">
    <img src="../images/Nine__1_-removebg-preview.png" alt="Nine Tailed Fox Logo" class="dashboard-logo">
    <h2>Dashboard</h2>
    <a href="dashboard.php" class="active">Overview</a>
    <a href="#">Users</a>
    <a href="#">Shop</a>
    <a href="#">Trade History</a>
    <a href="eventsb.php">Events</a>
    <a href="#">News</a>
    <a href="#">Support</a>
    <a href="../front/index.php">← Return Homepage</a>
  </div>

  <!-- ===== MAIN ===== -->
  <div class="main">
    <div class="topbar">
      <h1 data-lang-en="Welcome, Commander" data-lang-fr="Bienvenue, Commandant">Welcome, Commander</h1>
      <div style="display: flex; align-items: center; gap: 20px;">
        <button id="langToggle" class="lang-toggle" onclick="toggleLanguage()">
          <i class="fas fa-language"></i>
          <span id="currentLang">FR</span>
        </button>
        <div class="user">
          <img src="../images/fery.jpg" alt="User Avatar"> 
          <span>Profil Expert</span>
        </div>
      </div>
    </div>

    <div class="content">
      <!-- Recent Tickets Section -->
      <div class="recent-tickets-section">
        <h2 class="section-title" data-lang-en="Recent Tickets Generated" data-lang-fr="Tickets Récents Générés">
          <i class="fas fa-ticket-alt"></i> <span>Recent Tickets Generated</span>
        </h2>
        
        <?php if (empty($recentTickets)): ?>
          <div class="empty-state">
            <i class="fas fa-ticket-alt"></i>
            <p data-lang-en="No tickets generated yet" data-lang-fr="Aucun ticket généré pour le moment">No tickets generated yet</p>
          </div>
        <?php else: ?>
          <div class="tickets-grid">
            <?php foreach ($recentTickets as $ticketData): ?>
              <div class="ticket-card-dashboard">
                <div class="ticket-header-dash">
                  <div class="ticket-number">#<?= str_pad($ticketData['id_ticket'], 6, '0', STR_PAD_LEFT) ?></div>
                  <span class="ticket-status-badge-dash status-<?= $ticketData['status'] ?>">
                    <?php 
                      echo $ticketData['status'] === 'active' ? 'Active' : 
                           ($ticketData['status'] === 'used' ? 'Used' : 'Cancelled');
                    ?>
                  </span>
                </div>
                <div class="ticket-info-dash">
                  <div class="info-row">
                    <i class="fas fa-user"></i>
                    <span><?= htmlspecialchars($ticketData['participant_name']) ?></span>
                  </div>
                  <div class="info-row">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?= htmlspecialchars(substr($ticketData['event_title'], 0, 30)) ?></span>
                  </div>
                  <div class="info-row">
                    <i class="fas fa-clock"></i>
                    <span><?= date('d/m/Y H:i', strtotime($ticketData['created_at'])) ?></span>
                  </div>
                </div>
                <div class="ticket-actions-dash">
                  <a href="../../controller/generate_ticket_image.php?id=<?= $ticketData['id_ticket'] ?>" download class="btn-download-mini">
                    <i class="fas fa-download"></i>
                  </a>
                  <div class="qr-preview" title="QR Code">
                    <img src="../front/<?= htmlspecialchars($ticketData['qr_code_path']) ?>" alt="QR">
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Analytics Dashboard -->
      <div class="analytics-section">
        <h2 class="section-title" data-lang-en="Analytics & Insights" data-lang-fr="Analyses & Statistiques"><i class="fas fa-chart-line"></i> <span>Analytics & Insights</span></h2>
        
        <div class="charts-grid">
          <div class="chart-card">
            <h3 data-lang-en="Participation Trend" data-lang-fr="Tendance de Participation"><i class="fas fa-chart-area"></i> <span>Participation Trend</span></h3>
            <canvas id="participationTrendChart"></canvas>
          </div>

          <div class="chart-card">
            <h3 data-lang-en="Events Status" data-lang-fr="Statut des Événements"><i class="fas fa-chart-pie"></i> <span>Events Status</span></h3>
            <canvas id="eventsByStatusChart"></canvas>
          </div>

          <div class="chart-card">
            <h3 data-lang-en="Top 5 Events" data-lang-fr="Top 5 Événements"><i class="fas fa-trophy"></i> <span>Top 5 Events</span></h3>
            <canvas id="topEventsChart"></canvas>
          </div>

          <div class="chart-card">
            <h3 data-lang-en="Rating Distribution" data-lang-fr="Distribution des Notes"><i class="fas fa-star"></i> <span>Rating Distribution</span></h3>
            <canvas id="ratingDistributionChart"></canvas>
          </div>

          <div class="chart-card chart-wide">
            <h3 data-lang-en="Registration Activity (24h)" data-lang-fr="Activité d'Inscription (24h)"><i class="fas fa-clock"></i> <span>Registration Activity (24h)</span></h3>
            <canvas id="hourlyRegistrationsChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <footer class="site-footer">
      <!-- Quick Access -->
      <div class="quick-section">
        <h2 class="section-title" data-lang-en="Quick Access" data-lang-fr="Accès Rapide"><i class="fas fa-th"></i> <span>Quick Access</span></h2>
        
        <div class="access-grid">
          <div class="access-card" onclick="window.location.href='#'">
            <i class="fas fa-users"></i>
            <h3 data-lang-en="Users" data-lang-fr="Utilisateurs">Users</h3>
          </div>

          <div class="access-card" onclick="window.location.href='#'">
            <i class="fas fa-shopping-cart"></i>
            <h3 data-lang-en="Shop" data-lang-fr="Boutique">Shop</h3>
          </div>

          <div class="access-card" onclick="window.location.href='#'">
            <i class="fas fa-exchange-alt"></i>
            <h3 data-lang-en="Trade" data-lang-fr="Échange">Trade</h3>
          </div>

          <div class="access-card active" onclick="window.location.href='eventsb.php'">
            <i class="fas fa-calendar-alt"></i>
            <h3 data-lang-en="Events" data-lang-fr="Événements">Events</h3>
          </div>

          <div class="access-card" onclick="window.location.href='#'">
            <i class="fas fa-newspaper"></i>
            <h3 data-lang-en="News" data-lang-fr="Actualités">News</h3>
          </div>

          <div class="access-card" onclick="window.location.href='#'">
            <i class="fas fa-headset"></i>
            <h3 data-lang-en="Support" data-lang-fr="Support">Support</h3>
          </div>
        </div>
      </div>
      
      <div class="copyright">
        © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
      </div>
    </footer>
  </div>

  <!-- ===== PAGE TRANSITION OVERLAY ===== -->
  <div class="transition-screen"></div>

  <!-- Modal for Stats Details -->
  <div id="statsModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modalTitle"><i class="fas fa-info-circle"></i> Details</h2>
        <span class="modal-close" onclick="closeModal()">&times;</span>
      </div>
      <div class="modal-body" id="modalBody">
        <!-- Content will be dynamically loaded here -->
      </div>
    </div>
  </div>

  <style>
    /* Section Titles */
    .section-title {
      font-family: 'Orbitron', sans-serif;
      color: #f5c242;
      font-size: 1.4rem;
      margin: 0 0 24px 0;
      padding-bottom: 12px;
      border-bottom: 2px solid rgba(245, 194, 66, 0.3);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    /* Recent Tickets Section */
    .recent-tickets-section {
      margin-bottom: 48px;
    }

    .tickets-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 16px;
    }

    .ticket-card-dashboard {
      background: linear-gradient(135deg, rgba(22, 22, 26, 0.95), rgba(27, 27, 32, 0.95));
      border: 1px solid rgba(245, 194, 66, 0.2);
      border-radius: 12px;
      padding: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .ticket-card-dashboard::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 4px;
      height: 100%;
      background: linear-gradient(180deg, #f5c242, #f39c12);
    }

    .ticket-card-dashboard:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 20px rgba(245, 194, 66, 0.25);
      border-color: rgba(245, 194, 66, 0.5);
    }

    .ticket-header-dash {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
      padding-bottom: 12px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .ticket-number {
      font-family: 'Orbitron', sans-serif;
      color: #f5c242;
      font-size: 1.1rem;
      font-weight: 700;
    }

    .ticket-status-badge-dash {
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
    }

    .ticket-status-badge-dash.status-active {
      background: rgba(46, 213, 115, 0.2);
      color: #2ed573;
      border: 1px solid #2ed573;
    }

    .ticket-status-badge-dash.status-used {
      background: rgba(150, 150, 150, 0.2);
      color: #969696;
      border: 1px solid #969696;
    }

    .ticket-status-badge-dash.status-cancelled {
      background: rgba(255, 107, 107, 0.2);
      color: #ff6b6b;
      border: 1px solid #ff6b6b;
    }

    .ticket-info-dash {
      margin-bottom: 12px;
    }

    .info-row {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 8px;
      color: #cfd3d8;
      font-size: 0.9rem;
    }

    .info-row i {
      color: #f5c242;
      width: 16px;
      text-align: center;
    }

    .info-row span {
      flex: 1;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .ticket-actions-dash {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding-top: 12px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .btn-download-mini {
      background: linear-gradient(135deg, #f5c242, #f39c12);
      color: #000;
      padding: 8px 16px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 700;
      font-size: 0.85rem;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .btn-download-mini:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(245, 194, 66, 0.4);
    }

    .qr-preview {
      width: 50px;
      height: 50px;
      background: rgba(255, 255, 255, 0.95);
      padding: 4px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .qr-preview img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    /* Analytics Section */
    .analytics-section {
      margin-bottom: 48px;
    }

    .charts-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }

    .chart-card {
      background: linear-gradient(135deg, rgba(22, 22, 26, 0.95), rgba(27, 27, 32, 0.95));
      border: 1px solid rgba(245, 194, 66, 0.2);
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      transition: all 0.3s ease;
    }

    .chart-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(245, 194, 66, 0.15);
      border-color: rgba(245, 194, 66, 0.4);
    }

    .chart-card h3 {
      font-family: 'Orbitron', sans-serif;
      color: #f5c242;
      margin: 0 0 16px 0;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .chart-wide {
      grid-column: 1 / -1;
    }

    .chart-card canvas {
      max-height: 280px;
    }

    /* Quick Access Section */
    .quick-section {
      margin-bottom: 24px;
    }

    .access-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 16px;
    }

    .access-card {
      background: linear-gradient(135deg, rgba(22, 22, 26, 0.9), rgba(27, 27, 32, 0.9));
      border: 1px solid rgba(245, 194, 66, 0.2);
      border-radius: 12px;
      padding: 24px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .access-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 20px rgba(245, 194, 66, 0.2);
      border-color: rgba(245, 194, 66, 0.5);
    }

    .access-card.active {
      border-color: #f5c242;
      background: linear-gradient(135deg, rgba(245, 194, 66, 0.1), rgba(245, 194, 66, 0.05));
    }

    .access-card i {
      font-size: 2.5rem;
      color: #f5c242;
      margin-bottom: 12px;
      display: block;
    }

    .access-card h3 {
      font-family: 'Orbitron', sans-serif;
      color: #fff;
      font-size: 0.95rem;
      margin: 0;
    }

    /* Footer */
    .site-footer {
      background: linear-gradient(135deg, rgba(22, 22, 26, 0.95), rgba(16, 16, 20, 0.95));
      border-top: 2px solid rgba(245, 194, 66, 0.3);
      padding: 40px 24px 20px 24px;
      margin-top: 48px;
    }

    .copyright {
      text-align: center;
      padding-top: 24px;
      margin-top: 24px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      color: #969696;
      font-size: 0.9rem;
    }

    .copyright span {
      color: #f5c242;
      font-weight: 700;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 10000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.8);
      backdrop-filter: blur(5px);
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .modal-content {
      background: linear-gradient(135deg, rgba(22, 22, 26, 0.98), rgba(27, 27, 32, 0.98));
      margin: 5% auto;
      padding: 0;
      border: 2px solid rgba(245, 194, 66, 0.5);
      border-radius: 16px;
      width: 80%;
      max-width: 900px;
      max-height: 80vh;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
      animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
      from {
        transform: translateY(-50px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal-header {
      background: linear-gradient(135deg, #f5c242, #f39c12);
      padding: 20px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid rgba(245, 194, 66, 0.3);
    }

    .modal-header h2 {
      margin: 0;
      color: #16161a;
      font-family: 'Orbitron', sans-serif;
      font-size: 1.5rem;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .modal-close {
      color: #16161a;
      font-size: 2rem;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s ease;
      line-height: 1;
    }

    .modal-close:hover {
      transform: rotate(90deg);
      color: #ff6b6b;
    }

    .modal-body {
      padding: 30px;
      max-height: calc(80vh - 100px);
      overflow-y: auto;
      color: #cfd3d8;
    }

    .modal-body::-webkit-scrollbar {
      width: 8px;
    }

    .modal-body::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 10px;
    }

    .modal-body::-webkit-scrollbar-thumb {
      background: #f5c242;
      border-radius: 10px;
    }

    .detail-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .detail-item {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(245, 194, 66, 0.2);
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 12px;
      transition: all 0.3s ease;
    }

    .detail-item:hover {
      background: rgba(245, 194, 66, 0.05);
      border-color: rgba(245, 194, 66, 0.4);
      transform: translateX(5px);
    }

    .detail-item h4 {
      margin: 0 0 8px 0;
      color: #f5c242;
      font-family: 'Orbitron', sans-serif;
      font-size: 1rem;
    }

    .detail-item p {
      margin: 4px 0;
      font-size: 0.9rem;
      color: #969696;
    }

    .detail-item .highlight {
      color: #2ed573;
      font-weight: 600;
    }

    .empty-state {
      text-align: center;
      padding: 40px;
      color: #969696;
    }

    .empty-state i {
      font-size: 4rem;
      margin-bottom: 16px;
      opacity: 0.3;
    }

    @keyframes slideInRight {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    @keyframes slideOutRight {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(100%);
        opacity: 0;
      }
    }

    @media (max-width: 1200px) {
      .charts-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 768px) {
      .stats-overview {
        grid-template-columns: 1fr;
      }
      
      .access-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    /* Language Toggle Button */
    .lang-toggle {
      background: linear-gradient(135deg, rgba(245, 194, 66, 0.2), rgba(243, 156, 18, 0.2));
      border: 2px solid rgba(245, 194, 66, 0.4);
      border-radius: 8px;
      padding: 8px 16px;
      color: #f5c242;
      font-family: 'Orbitron', sans-serif;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
    }

    .lang-toggle:hover {
      background: linear-gradient(135deg, rgba(245, 194, 66, 0.3), rgba(243, 156, 18, 0.3));
      border-color: #f5c242;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(245, 194, 66, 0.3);
    }

    .lang-toggle i {
      font-size: 1.2rem;
    }

    #currentLang {
      font-size: 0.9rem;
    }
  </style>

  <script>
    // Language Management
    let currentLanguage = localStorage.getItem('language') || 'en';

    function toggleLanguage() {
      currentLanguage = currentLanguage === 'en' ? 'fr' : 'en';
      localStorage.setItem('language', currentLanguage);
      updateLanguage();
    }

    function updateLanguage() {
      const langButton = document.getElementById('currentLang');
      langButton.textContent = currentLanguage.toUpperCase();

      // Update all elements with language attributes
      document.querySelectorAll('[data-lang-en]').forEach(element => {
        const text = currentLanguage === 'en' 
          ? element.getAttribute('data-lang-en')
          : element.getAttribute('data-lang-fr');
        
        // For elements with icon + text structure
        if (element.querySelector('i')) {
          const icon = element.querySelector('i');
          const span = element.querySelector('span');
          if (span) {
            span.textContent = text;
          } else {
            element.innerHTML = icon.outerHTML + ' ' + text;
          }
        } else {
          element.textContent = text;
        }
      });
    }

    // Initialize language on page load
    window.addEventListener('DOMContentLoaded', () => {
      updateLanguage();
    });

    // Refresh data function
    function refreshDashboardData() {
      fetch(window.location.href)
        .then(response => response.text())
        .then(html => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          
          // Update stat cards
          const statCards = document.querySelectorAll('.stat-value');
          const newStatCards = doc.querySelectorAll('.stat-value');
          statCards.forEach((card, index) => {
            if (newStatCards[index]) {
              const oldValue = card.textContent;
              const newValue = newStatCards[index].textContent;
              if (oldValue !== newValue) {
                card.style.transform = 'scale(1.2)';
                card.style.color = '#2ed573';
                setTimeout(() => {
                  card.textContent = newValue;
                  setTimeout(() => {
                    card.style.transform = 'scale(1)';
                    card.style.color = '';
                  }, 300);
                }, 200);
              }
            }
          });
          
          // Show refresh indicator
          showRefreshIndicator();
        })
        .catch(error => console.error('Error refreshing data:', error));
    }
    
    function showRefreshIndicator() {
      const indicator = document.createElement('div');
      indicator.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #2ed573, #1abc9c);
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        font-family: 'Orbitron', sans-serif;
        font-size: 0.9rem;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(46, 213, 115, 0.4);
        animation: slideInRight 0.3s ease;
      `;
      indicator.innerHTML = '<i class="fas fa-sync-alt"></i> Data Updated';
      document.body.appendChild(indicator);
      
      setTimeout(() => {
        indicator.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => indicator.remove(), 300);
      }, 2000);
    }
    
    // Auto-refresh every 30 seconds
    setInterval(refreshDashboardData, 30000);
    
    // Data from PHP
    const eventsData = <?= json_encode($evenements) ?>;
    const totalComments = <?= $totalComments ?>;
    const totalRatings = <?= $totalRatings ?>;
    const averageRating = <?= $averageRating ?>;

    function showModal(type) {
      const modal = document.getElementById('statsModal');
      const modalTitle = document.getElementById('modalTitle');
      const modalBody = document.getElementById('modalBody');
      
      modal.style.display = 'block';
      
      let content = '';
      
      switch(type) {
        case 'events':
          modalTitle.innerHTML = '<i class="fas fa-calendar-alt"></i> All Events Details';
          
          if (eventsData.length === 0) {
            content = '<div class="empty-state"><i class="fas fa-calendar-times"></i><p>No events found</p></div>';
          } else {
            content = '<ul class="detail-list">';
            eventsData.forEach(item => {
              const event = item.evenement;
              const dateDebut = new Date(event.date_debut.replace(' ', 'T')).toLocaleString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
              });
              const dateFin = new Date(event.date_fin.replace(' ', 'T')).toLocaleString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
              });
              
              // Status badge color
              let statusColor = '#969696';
              if (event.statut === 'upcoming') statusColor = '#2ed573';
              if (event.statut === 'ongoing') statusColor = '#f5c242';
              if (event.statut === 'cancelled') statusColor = '#ff6b6b';
              
              content += `
                <li class="detail-item">
                  <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                    <h4 style="margin: 0; flex: 1;">${event.titre}</h4>
                    <span style="background: ${statusColor}20; color: ${statusColor}; padding: 4px 12px; border-radius: 12px; font-size: 0.8rem; font-weight: 700; border: 1px solid ${statusColor};">
                      ${event.statut.toUpperCase()}
                    </span>
                  </div>
                  
                  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-top: 12px;">
                    <div>
                      <p style="margin: 4px 0;">
                        <i class="fas fa-map-marker-alt" style="width: 20px; color: #f5c242;"></i>
                        <strong>Location:</strong> ${event.lieu}
                      </p>
                      <p style="margin: 4px 0;">
                        <i class="fas fa-users" style="width: 20px; color: #2ed573;"></i>
                        <strong>Participants:</strong> <span class="highlight">${item.nb_participants}</span>
                      </p>
                    </div>
                    <div>
                      <p style="margin: 4px 0;">
                        <i class="fas fa-calendar-check" style="width: 20px; color: #f5c242;"></i>
                        <strong>Start:</strong> ${dateDebut}
                      </p>
                      <p style="margin: 4px 0;">
                        <i class="fas fa-calendar-times" style="width: 20px; color: #ff6b6b;"></i>
                        <strong>End:</strong> ${dateFin}
                      </p>
                    </div>
                  </div>
                  
                  ${event.description ? `
                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.1);">
                      <p style="margin: 0; font-size: 0.9rem; color: #cfd3d8;">
                        <i class="fas fa-info-circle" style="color: #f5c242;"></i>
                        ${event.description}
                      </p>
                    </div>
                  ` : ''}
                </li>
              `;
            });
            content += '</ul>';
          }
          break;
          
        case 'participants':
          modalTitle.innerHTML = '<i class="fas fa-users"></i> Participants Details';
          content = '<ul class="detail-list">';
          
          // Fetch participants data via AJAX
          fetch('get_participants.php')
            .then(response => response.json())
            .then(participants => {
              if (participants.length === 0) {
                modalBody.innerHTML = '<div class="empty-state"><i class="fas fa-user-times"></i><p>No participants found</p></div>';
              } else {
                let participantsHTML = '<ul class="detail-list">';
                
                // Group participants by event
                const eventParticipants = {};
                participants.forEach(p => {
                  if (!eventParticipants[p.event_title]) {
                    eventParticipants[p.event_title] = [];
                  }
                  eventParticipants[p.event_title].push(p);
                });
                
                // Display grouped participants
                Object.keys(eventParticipants).forEach(eventTitle => {
                  const parts = eventParticipants[eventTitle];
                  participantsHTML += `
                    <li class="detail-item">
                      <h4><i class="fas fa-calendar-alt"></i> ${eventTitle}</h4>
                      <p style="margin: 8px 0; color: #2ed573; font-weight: 600;">
                        <i class="fas fa-users"></i> ${parts.length} participant(s)
                      </p>
                      <div style="margin-top: 12px; padding-left: 20px;">
                  `;
                  
                  parts.forEach(p => {
                    const participationDate = new Date(p.date_participation).toLocaleString('fr-FR');
                    participantsHTML += `
                      <div style="padding: 8px; margin: 4px 0; background: rgba(255,255,255,0.02); border-left: 3px solid #f5c242; padding-left: 12px;">
                        <p style="margin: 2px 0; color: #fff; font-weight: 600;">
                          <i class="fas fa-user"></i> ${p.nom_participant}
                        </p>
                        <p style="margin: 2px 0; font-size: 0.85rem;">
                          <i class="fas fa-envelope"></i> ${p.email_participant}
                        </p>
                        <p style="margin: 2px 0; font-size: 0.85rem; color: #969696;">
                          <i class="fas fa-clock"></i> Registered: ${participationDate}
                        </p>
                      </div>
                    `;
                  });
                  
                  participantsHTML += `
                      </div>
                    </li>
                  `;
                });
                
                participantsHTML += '</ul>';
                modalBody.innerHTML = participantsHTML;
              }
            })
            .catch(error => {
              modalBody.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error loading participants</p></div>';
              console.error('Error:', error);
            });
          
          // Show loading state
          content = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 3rem; color: #f5c242;"></i><p style="margin-top: 16px; color: #969696;">Loading participants...</p></div>';
          break;
          
        case 'comments':
          modalTitle.innerHTML = '<i class="fas fa-comments"></i> Comments Statistics';
          content = `
            <div class="detail-item">
              <h4>Total Comments</h4>
              <p><i class="fas fa-comment"></i> <span class="highlight">${totalComments} comments</span> posted across all events</p>
            </div>
            <div class="detail-item">
              <h4>Comments Distribution</h4>
              <p>Comments are spread across ${eventsData.length} events</p>
              <p>Average: <span class="highlight">${eventsData.length > 0 ? (totalComments / eventsData.length).toFixed(1) : 0} comments per event</span></p>
            </div>
          `;
          break;
          
        case 'ratings':
          modalTitle.innerHTML = '<i class="fas fa-star"></i> Ratings Overview';
          content = `
            <div class="detail-item">
              <h4>Average Rating</h4>
              <p><i class="fas fa-star"></i> <span class="highlight">${averageRating} / 5.0</span></p>
              <p>Based on <span class="highlight">${totalRatings} ratings</span></p>
            </div>
            <div class="detail-item">
              <h4>Rating Distribution</h4>
              <p>⭐ 1 Star: <?= $ratingDistribution[1] ?> ratings</p>
              <p>⭐⭐ 2 Stars: <?= $ratingDistribution[2] ?> ratings</p>
              <p>⭐⭐⭐ 3 Stars: <?= $ratingDistribution[3] ?> ratings</p>
              <p>⭐⭐⭐⭐ 4 Stars: <?= $ratingDistribution[4] ?> ratings</p>
              <p>⭐⭐⭐⭐⭐ 5 Stars: <?= $ratingDistribution[5] ?> ratings</p>
            </div>
          `;
          break;
      }
      
      modalBody.innerHTML = content;
    }
    
    function closeModal() {
      document.getElementById('statsModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('statsModal');
      if (event.target == modal) {
        closeModal();
      }
    }

    // Configuration globale pour tous les graphiques
    Chart.defaults.color = '#cfd3d8';
    Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
    Chart.defaults.font.family = "'Poppins', sans-serif";

    // 1. Participation Trend Chart (Ligne)
    const participationCtx = document.getElementById('participationTrendChart').getContext('2d');
    new Chart(participationCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode(array_column($participationTrend, 'day')) ?>,
        datasets: [{
          label: 'Participants',
          data: <?= json_encode(array_column($participationTrend, 'count')) ?>,
          borderColor: '#f5c242',
          backgroundColor: 'rgba(245, 194, 66, 0.1)',
          borderWidth: 3,
          fill: true,
          tension: 0.4,
          pointRadius: 6,
          pointHoverRadius: 8,
          pointBackgroundColor: '#f5c242',
          pointBorderColor: '#fff',
          pointBorderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            display: true,
            labels: { color: '#f5c242', font: { size: 14, weight: 'bold' } }
          },
          tooltip: {
            backgroundColor: 'rgba(22, 22, 26, 0.95)',
            titleColor: '#f5c242',
            bodyColor: '#fff',
            borderColor: '#f5c242',
            borderWidth: 1,
            padding: 12,
            displayColors: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { color: '#969696' },
            grid: { color: 'rgba(255, 255, 255, 0.05)' }
          },
          x: {
            ticks: { color: '#969696' },
            grid: { color: 'rgba(255, 255, 255, 0.05)' }
          }
        }
      }
    });

    // 2. Events by Status Chart (Pie)
    const statusCtx = document.getElementById('eventsByStatusChart').getContext('2d');
    new Chart(statusCtx, {
      type: 'doughnut',
      data: {
        labels: ['Upcoming', 'Ongoing', 'Completed', 'Cancelled'],
        datasets: [{
          data: [
            <?= $eventsByStatus['upcoming'] ?>,
            <?= $eventsByStatus['ongoing'] ?>,
            <?= $eventsByStatus['completed'] ?>,
            <?= $eventsByStatus['cancelled'] ?>
          ],
          backgroundColor: [
            '#2ed573',
            '#f5c242',
            '#969696',
            '#ff6b6b'
          ],
          borderColor: '#16161a',
          borderWidth: 3
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { color: '#cfd3d8', padding: 15, font: { size: 12 } }
          },
          tooltip: {
            backgroundColor: 'rgba(22, 22, 26, 0.95)',
            titleColor: '#f5c242',
            bodyColor: '#fff',
            borderColor: '#f5c242',
            borderWidth: 1,
            padding: 12
          }
        }
      }
    });

    // 3. Top Events Chart (Bar horizontal)
    const topEventsCtx = document.getElementById('topEventsChart').getContext('2d');
    new Chart(topEventsCtx, {
      type: 'bar',
      data: {
        labels: <?= json_encode(array_column($topEvents, 'titre')) ?>,
        datasets: [{
          label: 'Participants',
          data: <?= json_encode(array_column($topEvents, 'participants')) ?>,
          backgroundColor: [
            'rgba(245, 194, 66, 0.8)',
            'rgba(46, 213, 115, 0.8)',
            'rgba(255, 107, 107, 0.8)',
            'rgba(162, 155, 254, 0.8)',
            'rgba(253, 203, 110, 0.8)'
          ],
          borderColor: [
            '#f5c242',
            '#2ed573',
            '#ff6b6b',
            '#a29bfe',
            '#fdcb6e'
          ],
          borderWidth: 2
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: 'rgba(22, 22, 26, 0.95)',
            titleColor: '#f5c242',
            bodyColor: '#fff',
            borderColor: '#f5c242',
            borderWidth: 1,
            padding: 12
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            ticks: { color: '#969696' },
            grid: { color: 'rgba(255, 255, 255, 0.05)' }
          },
          y: {
            ticks: { color: '#969696' },
            grid: { display: false }
          }
        }
      }
    });

    // 4. Rating Distribution Chart (Bar)
    const ratingCtx = document.getElementById('ratingDistributionChart').getContext('2d');
    new Chart(ratingCtx, {
      type: 'bar',
      data: {
        labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
        datasets: [{
          label: 'Number of Ratings',
          data: [
            <?= $ratingDistribution[1] ?>,
            <?= $ratingDistribution[2] ?>,
            <?= $ratingDistribution[3] ?>,
            <?= $ratingDistribution[4] ?>,
            <?= $ratingDistribution[5] ?>
          ],
          backgroundColor: [
            'rgba(255, 107, 107, 0.7)',
            'rgba(255, 159, 67, 0.7)',
            'rgba(253, 203, 110, 0.7)',
            'rgba(162, 155, 254, 0.7)',
            'rgba(46, 213, 115, 0.7)'
          ],
          borderColor: [
            '#ff6b6b',
            '#ff9f43',
            '#fdcb6e',
            '#a29bfe',
            '#2ed573'
          ],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: 'rgba(22, 22, 26, 0.95)',
            titleColor: '#f5c242',
            bodyColor: '#fff',
            borderColor: '#f5c242',
            borderWidth: 1,
            padding: 12
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { color: '#969696', stepSize: 1 },
            grid: { color: 'rgba(255, 255, 255, 0.05)' }
          },
          x: {
            ticks: { color: '#969696' },
            grid: { display: false }
          }
        }
      }
    });

    // 5. Hourly Registrations Chart (Line avec Area)
    const hourlyCtx = document.getElementById('hourlyRegistrationsChart').getContext('2d');
    new Chart(hourlyCtx, {
      type: 'line',
      data: {
        labels: Array.from({length: 24}, (_, i) => i + 'h'),
        datasets: [{
          label: 'Registrations',
          data: <?= json_encode(array_values($hourlyRegistrations)) ?>,
          borderColor: '#2ed573',
          backgroundColor: 'rgba(46, 213, 115, 0.2)',
          borderWidth: 3,
          fill: true,
          tension: 0.4,
          pointRadius: 4,
          pointHoverRadius: 6,
          pointBackgroundColor: '#2ed573',
          pointBorderColor: '#fff',
          pointBorderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            display: true,
            labels: { color: '#2ed573', font: { size: 14, weight: 'bold' } }
          },
          tooltip: {
            backgroundColor: 'rgba(22, 22, 26, 0.95)',
            titleColor: '#2ed573',
            bodyColor: '#fff',
            borderColor: '#2ed573',
            borderWidth: 1,
            padding: 12,
            displayColors: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { color: '#969696' },
            grid: { color: 'rgba(255, 255, 255, 0.05)' }
          },
          x: {
            ticks: { color: '#969696' },
            grid: { color: 'rgba(255, 255, 255, 0.05)' }
          }
        }
      }
    });

    // Page transition
    window.addEventListener("load", () => {
      document.querySelector(".transition-screen").classList.add("hidden");
    });

    document.querySelectorAll("a").forEach(link => {
      link.addEventListener("click", e => {
        const href = link.getAttribute("href");
        if (href && !href.startsWith("#") && href !== "") {
          e.preventDefault();
          const transition = document.querySelector(".transition-screen");
          transition.classList.remove("hidden");
          setTimeout(() => {
            window.location.href = href;
          }, 700);
        }
      });
    });
  </script>
  
</body>
</html>