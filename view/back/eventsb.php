<?php
require_once __DIR__ . '/../../controller/EvenementController.php';
require_once __DIR__ . '/../../controller/ParticipationController.php';
require_once __DIR__ . '/../../controller/TicketController.php';

$eventController = new EvenementController();
$participationController = new ParticipationController();
$ticketController = new TicketController();

// Handle delete event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $eventController->supprimer((int)$_POST['id_evenement']);
    header("Location: eventsb.php");
    exit;
}

// Get all events with participant counts
$evenements = $eventController->lireTous();

// Calculate statistics
$totalEvents = count($evenements);
$upcomingEvents = 0;
$expiredEvents = 0;
$totalParticipants = 0;
$totalTickets = $ticketController->countAllTickets();
$now = new DateTime();

foreach ($evenements as $item) {
    $event = $item['evenement'];
    $totalParticipants += $item['nb_participants'];
    
    if ($event->getDateFin() < $now) {
        $expiredEvents++;
    } else {
        $upcomingEvents++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Events Management - Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Poppins:wght@300;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .events-management {
      padding: 24px;
      max-width: 100%;
      overflow-x: auto;
    }

    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 16px;
      margin-bottom: 32px;
    }

    .stat-card {
      background: linear-gradient(135deg, rgba(22, 22, 26, 0.9), rgba(27, 27, 32, 0.9));
      border: 1px solid rgba(245, 194, 66, 0.2);
      border-radius: 12px;
      padding: 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: all 0.3s ease;
      cursor: pointer;
      min-height: 100px;
    }

    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(245, 194, 66, 0.2);
      border-color: rgba(245, 194, 66, 0.4);
    }

    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      flex-shrink: 0;
    }

    .stat-icon.total {
      background: linear-gradient(135deg, #f5c242, #f39c12);
      color: #000;
    }

    .stat-icon.upcoming {
      background: linear-gradient(135deg, #2ed573, #1abc9c);
      color: #fff;
    }

    .stat-icon.expired {
      background: linear-gradient(135deg, #969696, #636363);
      color: #fff;
    }

    .stat-icon.participants {
      background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
      color: #fff;
    }

    .stat-icon.tickets {
      background: linear-gradient(135deg, #a29bfe, #6c5ce7);
      color: #fff;
    }

    .stat-content {
      flex: 1;
    }

    .stat-label {
      color: #969696;
      font-size: 0.8rem;
      margin-bottom: 4px;
      font-weight: 600;
    }

    .stat-value {
      font-family: 'Orbitron', sans-serif;
      font-size: 1.8rem;
      font-weight: 700;
      color: #f5c242;
      line-height: 1;
    }

    .events-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }

    .events-header h2 {
      font-family: 'Orbitron', sans-serif;
      color: #f5c242;
      font-size: 1.8rem;
      margin: 0;
    }

    .events-table {
      background: rgba(255, 255, 255, 0.03);
      border-radius: 12px;
      overflow-x: auto;
      overflow-y: visible;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
      max-width: 100%;
    }

    .events-table table {
      width: 100%;
      min-width: 1200px;
      border-collapse: collapse;
    }

    .events-table thead {
      background: linear-gradient(135deg, #16161a, #1b1b20);
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .events-table th {
      padding: 14px 12px;
      text-align: left;
      font-family: 'Orbitron', sans-serif;
      color: #f5c242;
      font-weight: 700;
      font-size: 0.85rem;
      border-bottom: 2px solid rgba(245, 194, 66, 0.3);
      white-space: nowrap;
    }

    .events-table td {
      padding: 14px 12px;
      color: #cfd3d8;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      font-size: 0.9rem;
    }

    .events-table tbody tr {
      transition: all 0.3s ease;
    }

    .events-table tbody tr:hover {
      background: rgba(245, 194, 66, 0.05);
    }

    .event-title-cell {
      font-weight: 600;
      color: #fff;
      max-width: 200px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .status-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 10px;
      font-size: 0.75rem;
      font-weight: 700;
      white-space: nowrap;
    }

    .status-available {
      background: rgba(46, 213, 115, 0.2);
      color: #2ed573;
      border: 1px solid #2ed573;
    }

    .status-full {
      background: rgba(255, 107, 107, 0.2);
      color: #ff6b6b;
      border: 1px solid #ff6b6b;
    }

    .status-expired {
      background: rgba(150, 150, 150, 0.2);
      color: #969696;
      border: 1px solid #969696;
    }

    .ticket-badge {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 10px;
      font-size: 0.8rem;
      font-weight: 700;
      background: linear-gradient(135deg, rgba(245, 194, 66, 0.2), rgba(243, 156, 18, 0.2));
      color: #f5c242;
      border: 1px solid rgba(245, 194, 66, 0.4);
      transition: all 0.3s ease;
      white-space: nowrap;
    }

    .ticket-badge:hover {
      background: linear-gradient(135deg, rgba(245, 194, 66, 0.3), rgba(243, 156, 18, 0.3));
      border-color: #f5c242;
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(245, 194, 66, 0.3);
    }

    .ticket-badge i {
      margin-right: 4px;
    }

    .ticket-item {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(245, 194, 66, 0.2);
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 12px;
      display: grid;
      grid-template-columns: 1fr 1fr auto;
      gap: 16px;
      align-items: center;
    }

    .ticket-item:hover {
      background: rgba(245, 194, 66, 0.05);
      border-color: rgba(245, 194, 66, 0.4);
    }

    .ticket-info {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .ticket-info strong {
      color: #f5c242;
      font-size: 0.85rem;
    }

    .ticket-info span {
      color: #cfd3d8;
    }

    .ticket-qr {
      text-align: center;
    }

    .ticket-qr img {
      width: 80px;
      height: 80px;
      border: 2px solid #f5c242;
      border-radius: 8px;
      padding: 4px;
      background: white;
    }

    .ticket-status-badge {
      padding: 4px 10px;
      border-radius: 8px;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
    }

    .ticket-status-active {
      background: rgba(46, 213, 115, 0.2);
      color: #2ed573;
      border: 1px solid #2ed573;
    }

    .ticket-status-used {
      background: rgba(150, 150, 150, 0.2);
      color: #969696;
      border: 1px solid #969696;
    }

    .ticket-status-cancelled {
      background: rgba(255, 107, 107, 0.2);
      color: #ff6b6b;
      border: 1px solid #ff6b6b;
    }

    .action-buttons {
      display: flex;
      gap: 6px;
      flex-wrap: nowrap;
    }

    .btn-action {
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #fff;
      padding: 5px 10px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.8rem;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      white-space: nowrap;
    }

    .btn-action:hover {
      border-color: #f5c242;
      background: rgba(245, 194, 66, 0.1);
      transform: translateY(-2px);
    }

    .btn-action.delete {
      border-color: rgba(255, 107, 107, 0.4);
      color: #ff6b6b;
    }

    .btn-action.delete:hover {
      border-color: #ff6b6b;
      background: rgba(255, 107, 107, 0.1);
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #969696;
    }

    .empty-state i {
      font-size: 64px;
      margin-bottom: 16px;
      opacity: 0.3;
    }

    /* Modal Styles */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
      z-index: 9999;
      animation: fadeIn 0.3s ease;
    }

    .modal-overlay.active {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background: linear-gradient(135deg, #16161a, #1b1b20);
      border: 2px solid rgba(245, 194, 66, 0.3);
      border-radius: 16px;
      width: 90%;
      max-width: 800px;
      max-height: 80vh;
      overflow-y: auto;
      animation: slideDown 0.3s ease;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    }

    .modal-header {
      background: linear-gradient(135deg, rgba(245, 194, 66, 0.2), rgba(243, 156, 18, 0.2));
      padding: 24px;
      border-bottom: 2px solid rgba(245, 194, 66, 0.3);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-header h2 {
      font-family: 'Orbitron', sans-serif;
      color: #f5c242;
      margin: 0;
      font-size: 1.5rem;
    }

    .modal-close {
      background: transparent;
      border: none;
      color: #f5c242;
      font-size: 2rem;
      cursor: pointer;
      transition: all 0.3s ease;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-close:hover {
      transform: rotate(90deg);
      color: #ff6b6b;
    }

    .modal-body {
      padding: 24px;
    }

    .event-card-modal {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(245, 194, 66, 0.2);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 16px;
      transition: all 0.3s ease;
    }

    .event-card-modal:hover {
      background: rgba(245, 194, 66, 0.05);
      border-color: rgba(245, 194, 66, 0.4);
      transform: translateX(5px);
    }

    .event-card-modal h3 {
      font-family: 'Orbitron', sans-serif;
      color: #f5c242;
      margin: 0 0 12px 0;
      font-size: 1.2rem;
    }

    .event-info {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 12px;
      color: #cfd3d8;
    }

    .event-info-item {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .event-info-item i {
      color: #f5c242;
      width: 20px;
    }

    .participant-item {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(245, 194, 66, 0.2);
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 12px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .participant-item:hover {
      background: rgba(245, 194, 66, 0.05);
      border-color: rgba(245, 194, 66, 0.4);
    }

    .participant-info {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .participant-info strong {
      color: #f5c242;
      font-size: 0.85rem;
    }

    .participant-info span {
      color: #cfd3d8;
    }

    .event-group {
      margin-bottom: 24px;
    }

    .event-group-title {
      font-family: 'Orbitron', sans-serif;
      color: #f5c242;
      font-size: 1.1rem;
      margin-bottom: 12px;
      padding-bottom: 8px;
      border-bottom: 2px solid rgba(245, 194, 66, 0.3);
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes slideDown {
      from { 
        opacity: 0;
        transform: translateY(-50px);
      }
      to { 
        opacity: 1;
        transform: translateY(0);
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

    /* Table Responsive Improvements */
    @media (max-width: 1400px) {
      .events-table table {
        min-width: 1100px;
      }
      
      .events-table th,
      .events-table td {
        padding: 12px 8px;
        font-size: 0.85rem;
      }
      
      .event-title-cell {
        max-width: 150px;
      }
    }

    @media (max-width: 1200px) {
      .stat-card {
        padding: 16px;
      }
      
      .stat-icon {
        width: 45px;
        height: 45px;
        font-size: 20px;
      }
      
      .stat-value {
        font-size: 1.6rem;
      }
    }

    /* Scrollbar Styling */
    .events-table::-webkit-scrollbar {
      height: 8px;
    }

    .events-table::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 4px;
    }

    .events-table::-webkit-scrollbar-thumb {
      background: rgba(245, 194, 66, 0.3);
      border-radius: 4px;
    }

    .events-table::-webkit-scrollbar-thumb:hover {
      background: rgba(245, 194, 66, 0.5);
    }
  </style>
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
    <a href="dashboard.php">Overview</a>
    <a href="#">Users</a>
    <a href="#">Shop</a>
    <a href="#">Trade History</a>
    <a href="eventsb.php" class="active">Events</a>
    <a href="#">News</a>
    <a href="#">Support</a>
    <a href="../front/index.php">← Return Homepage</a>
  </div>

  <!-- ===== MAIN ===== -->
  <div class="main">
    <div class="topbar">
      <h1 data-lang-en="Events Management" data-lang-fr="Gestion des Événements">Events Management</h1>
      <div style="display: flex; align-items: center; gap: 20px;">
        <button id="langToggle" class="lang-toggle" onclick="toggleLanguage()">
          <i class="fas fa-language"></i>
          <span id="currentLang">FR</span>
        </button>
        <div class="user">
          <img src="../images/fery.jpg" alt="User Avatar">
          <span>FoxLeader</span>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="events-management">
        <!-- Statistics Section -->
        <div class="stats-container">
          <div class="stat-card" onclick="showModal('total')">
            <div class="stat-icon total">
              <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
              <div class="stat-label" data-lang-en="Total Events" data-lang-fr="Total Événements">Total Events</div>
              <div class="stat-value"><?= $totalEvents ?></div>
            </div>
          </div>

          <div class="stat-card" onclick="showModal('upcoming')">
            <div class="stat-icon upcoming">
              <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
              <div class="stat-label" data-lang-en="Upcoming Events" data-lang-fr="Événements À Venir">Upcoming Events</div>
              <div class="stat-value"><?= $upcomingEvents ?></div>
            </div>
          </div>

          <div class="stat-card" onclick="showModal('expired')">
            <div class="stat-icon expired">
              <i class="fas fa-calendar-times"></i>
            </div>
            <div class="stat-content">
              <div class="stat-label" data-lang-en="Expired Events" data-lang-fr="Événements Expirés">Expired Events</div>
              <div class="stat-value"><?= $expiredEvents ?></div>
            </div>
          </div>

          <div class="stat-card" onclick="showModal('participants')">
            <div class="stat-icon participants">
              <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
              <div class="stat-label" data-lang-en="Total Participants" data-lang-fr="Total Participants">Total Participants</div>
              <div class="stat-value"><?= $totalParticipants ?></div>
            </div>
          </div>

          <div class="stat-card" onclick="showModal('allTickets')">
            <div class="stat-icon tickets">
              <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="stat-content">
              <div class="stat-label" data-lang-en="Total Tickets" data-lang-fr="Total Tickets">Total Tickets</div>
              <div class="stat-value"><?= $totalTickets ?></div>
            </div>
          </div>
        </div>

        <div class="events-header">
          <h2 data-lang-en="All Events" data-lang-fr="Tous les Événements">All Events</h2>
        </div>

        <div class="events-table">
          <table>
            <thead>
              <tr>
                <th data-lang-en="Title" data-lang-fr="Titre" style="min-width: 150px;">Title</th>
                <th data-lang-en="Location" data-lang-fr="Lieu" style="min-width: 120px;">Location</th>
                <th data-lang-en="Start Date" data-lang-fr="Date Début" style="min-width: 120px;">Start Date</th>
                <th data-lang-en="End Date" data-lang-fr="Date Fin" style="min-width: 120px;">End Date</th>
                <th data-lang-en="Participants" data-lang-fr="Participants" style="text-align: center; min-width: 80px;">Participants</th>
                <th data-lang-en="Tickets" data-lang-fr="Tickets" style="text-align: center; min-width: 80px;">Tickets</th>
                <th data-lang-en="Status" data-lang-fr="Statut" style="text-align: center; min-width: 90px;">Status</th>
                <th data-lang-en="Actions" data-lang-fr="Actions" style="min-width: 150px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($evenements)): ?>
                <tr>
                  <td colspan="8">
                    <div class="empty-state">
                      <i class="fas fa-calendar-times"></i>
                      <p data-lang-en="No events found. Create a new event from the frontend." data-lang-fr="Aucun événement trouvé. Créez un nouvel événement depuis le frontend.">No events found. Create a new event from the frontend.</p>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($evenements as $item): 
                  $event = $item['evenement'];
                  $nbParticipants = $item['nb_participants'];
                  $now = new DateTime();
                  
                  // Determine status
                  if ($event->getDateFin() < $now) {
                    $statusClass = 'status-expired';
                    $statusLabel = 'Expired';
                  } else {
                    $statusClass = 'status-available';
                    $statusLabel = 'Available';
                  }
                ?>
                <tr>
                  <td class="event-title-cell" title="<?= htmlspecialchars($event->getTitre()) ?>"><?= htmlspecialchars($event->getTitre()) ?></td>
                  <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($event->getLieu()) ?>"><?= htmlspecialchars($event->getLieu()) ?></td>
                  <td style="white-space: nowrap;"><?= $event->getDateDebut()->format('d/m/Y H:i') ?></td>
                  <td style="white-space: nowrap;"><?= $event->getDateFin()->format('d/m/Y H:i') ?></td>
                  <td style="text-align: center;"><?= $nbParticipants ?></td>
                  <td style="text-align: center;">
                    <?php 
                      $ticketCount = $ticketController->countTicketsByEvent($event->getIdEvenement());
                    ?>
                    <span class="ticket-badge" onclick="showTicketsModal(<?= $event->getIdEvenement() ?>, '<?= htmlspecialchars($event->getTitre(), ENT_QUOTES) ?>')">
                      <i class="fas fa-ticket-alt"></i> <?= $ticketCount ?>
                    </span>
                  </td>
                  <td style="text-align: center;"><span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                  <td>
                    <div class="action-buttons">
                      <a href="event_comments.php?id=<?= $event->getIdEvenement() ?>" class="btn-action">
                        <i class="fas fa-eye"></i> <span data-lang-en="View" data-lang-fr="Voir">View</span>
                      </a>
                      <form method="POST" style="display:inline;" onsubmit="return confirmDelete();">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id_evenement" value="<?= $event->getIdEvenement() ?>">
                        <button type="submit" class="btn-action delete">
                          <i class="fas fa-trash"></i> <span data-lang-en="Delete" data-lang-fr="Supprimer">Delete</span>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <footer class="site-footer">
      © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
    </footer>
  </div>

  <!-- ===== MODAL ===== -->
  <div class="modal-overlay" id="eventModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modalTitle"></h2>
        <button class="modal-close" onclick="closeModal()">&times;</button>
      </div>
      <div class="modal-body" id="modalBody">
        <!-- Content will be loaded dynamically -->
      </div>
    </div>
  </div>

  <!-- ===== PAGE TRANSITION OVERLAY ===== -->
  <div class="transition-screen"></div>

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
        element.textContent = text;
      });

      // Update status badges
      document.querySelectorAll('.status-badge').forEach(badge => {
        if (badge.classList.contains('status-available')) {
          badge.textContent = currentLanguage === 'en' ? 'Available' : 'Disponible';
        } else if (badge.classList.contains('status-expired')) {
          badge.textContent = currentLanguage === 'en' ? 'Expired' : 'Expiré';
        }
      });
    }

    function confirmDelete() {
      const message = currentLanguage === 'en' 
        ? 'Are you sure you want to delete this event?'
        : 'Êtes-vous sûr de vouloir supprimer cet événement ?';
      return confirm(message);
    }

    // Initialize language on page load
    window.addEventListener('DOMContentLoaded', () => {
      updateLanguage();
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

    // Modal functions
    function showModal(type) {
      const modal = document.getElementById('eventModal');
      const modalTitle = document.getElementById('modalTitle');
      const modalBody = document.getElementById('modalBody');

      modal.classList.add('active');

      const titles = {
        total: { en: 'All Events', fr: 'Tous les Événements' },
        upcoming: { en: 'Upcoming Events', fr: 'Événements À Venir' },
        expired: { en: 'Expired Events', fr: 'Événements Expirés' },
        participants: { en: 'All Participants', fr: 'Tous les Participants' },
        allTickets: { en: 'All Tickets', fr: 'Tous les Tickets' }
      };

      modalTitle.textContent = titles[type][currentLanguage];

      switch(type) {
        case 'total':
          loadAllEvents();
          break;
        case 'upcoming':
          loadUpcomingEvents();
          break;
        case 'expired':
          loadExpiredEvents();
          break;
        case 'participants':
          loadAllParticipants();
          break;
        case 'allTickets':
          loadAllTickets();
          break;
      }
    }

    function closeModal() {
      const modal = document.getElementById('eventModal');
      modal.classList.remove('active');
    }

    // Close modal when clicking outside
    document.getElementById('eventModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeModal();
      }
    });

    async function loadAllEvents() {
      const modalBody = document.getElementById('modalBody');
      const loadingText = currentLanguage === 'en' ? 'Loading...' : 'Chargement...';
      modalBody.innerHTML = `<p style="text-align:center; color:#969696;"><i class="fas fa-spinner fa-spin"></i> ${loadingText}</p>`;

      try {
        const response = await fetch('get_events_data.php?type=all');
        const data = await response.json();
        
        if (data.length === 0) {
          const noEventsText = currentLanguage === 'en' ? 'No events found.' : 'Aucun événement trouvé.';
          modalBody.innerHTML = `<p style="text-align:center; color:#969696;">${noEventsText}</p>`;
          return;
        }

        let html = '';
        data.forEach(event => {
          const statusText = currentLanguage === 'en' ? event.status : (event.status === 'Available' ? 'Disponible' : 'Expiré');
          const participantsText = currentLanguage === 'en' ? 'participants' : 'participants';
          
          html += `
            <div class="event-card-modal">
              <h3>${event.title}</h3>
              <div class="event-info">
                <div class="event-info-item">
                  <i class="fas fa-map-marker-alt"></i>
                  <span>${event.location}</span>
                </div>
                <div class="event-info-item">
                  <i class="fas fa-calendar"></i>
                  <span>${event.start_date}</span>
                </div>
                <div class="event-info-item">
                  <i class="fas fa-users"></i>
                  <span>${event.participants} ${participantsText}</span>
                </div>
                <div class="event-info-item">
                  <i class="fas fa-tag"></i>
                  <span class="status-badge ${event.status_class}">${statusText}</span>
                </div>
              </div>
            </div>
          `;
        });
        modalBody.innerHTML = html;
      } catch (error) {
        const errorText = currentLanguage === 'en' ? 'Error loading events.' : 'Erreur lors du chargement des événements.';
        modalBody.innerHTML = `<p style="text-align:center; color:#ff6b6b;">${errorText}</p>`;
      }
    }

    async function loadUpcomingEvents() {
      const modalBody = document.getElementById('modalBody');
      const loadingText = currentLanguage === 'en' ? 'Loading...' : 'Chargement...';
      modalBody.innerHTML = `<p style="text-align:center; color:#969696;"><i class="fas fa-spinner fa-spin"></i> ${loadingText}</p>`;

      try {
        const response = await fetch('get_events_data.php?type=upcoming');
        const data = await response.json();
        
        if (data.length === 0) {
          const noEventsText = currentLanguage === 'en' ? 'No upcoming events found.' : 'Aucun événement à venir trouvé.';
          modalBody.innerHTML = `<p style="text-align:center; color:#969696;">${noEventsText}</p>`;
          return;
        }

        let html = '';
        data.forEach(event => {
          const statusText = currentLanguage === 'en' ? event.status : (event.status === 'Available' ? 'Disponible' : 'Expiré');
          const participantsText = currentLanguage === 'en' ? 'participants' : 'participants';
          
          html += `
            <div class="event-card-modal">
              <h3>${event.title}</h3>
              <div class="event-info">
                <div class="event-info-item">
                  <i class="fas fa-map-marker-alt"></i>
                  <span>${event.location}</span>
                </div>
                <div class="event-info-item">
                  <i class="fas fa-calendar"></i>
                  <span>${event.start_date}</span>
                </div>
                <div class="event-info-item">
                  <i class="fas fa-users"></i>
                  <span>${event.participants} ${participantsText}</span>
                </div>
                <div class="event-info-item">
                  <i class="fas fa-tag"></i>
                  <span class="status-badge ${event.status_class}">${statusText}</span>
                </div>
              </div>
            </div>
          `;
        });
        modalBody.innerHTML = html;
      } catch (error) {
        const errorText = currentLanguage === 'en' ? 'Error loading events.' : 'Erreur lors du chargement des événements.';
        modalBody.innerHTML = `<p style="text-align:center; color:#ff6b6b;">${errorText}</p>`;
      }
    }

    async function loadExpiredEvents() {
      const modalBody = document.getElementById('modalBody');
      const loadingText = currentLanguage === 'en' ? 'Loading...' : 'Chargement...';
      modalBody.innerHTML = `<p style="text-align:center; color:#969696;"><i class="fas fa-spinner fa-spin"></i> ${loadingText}</p>`;

      try {
        const response = await fetch('get_events_data.php?type=expired');
        const data = await response.json();
        
        if (data.length === 0) {
          const noEventsText = currentLanguage === 'en' ? 'No expired events found.' : 'Aucun événement expiré trouvé.';
          modalBody.innerHTML = `<p style="text-align:center; color:#969696;">${noEventsText}</p>`;
          return;
        }

        let html = '';
        data.forEach(event => {
          const statusText = currentLanguage === 'en' ? event.status : (event.status === 'Available' ? 'Disponible' : 'Expiré');
          const participantsText = currentLanguage === 'en' ? 'participants' : 'participants';
          
          html += `
            <div class="event-card-modal">
              <h3>${event.title}</h3>
              <div class="event-info">
                <div class="event-info-item">
                  <i class="fas fa-map-marker-alt"></i>
                  <span>${event.location}</span>
                </div>
                <div class="event-info-item">
                  <i class="fas fa-calendar"></i>
                  <span>${event.start_date}</span>
                </div>
                <div class="event-info-item">
                  <i class="fas fa-users"></i>
                  <span>${event.participants} ${participantsText}</span>
                </div>
                <div class="event-info-item">
                  <i class="fas fa-tag"></i>
                  <span class="status-badge ${event.status_class}">${statusText}</span>
                </div>
              </div>
            </div>
          `;
        });
        modalBody.innerHTML = html;
      } catch (error) {
        const errorText = currentLanguage === 'en' ? 'Error loading events.' : 'Erreur lors du chargement des événements.';
        modalBody.innerHTML = `<p style="text-align:center; color:#ff6b6b;">${errorText}</p>`;
      }
    }

    async function loadAllParticipants() {
      const modalBody = document.getElementById('modalBody');
      const loadingText = currentLanguage === 'en' ? 'Loading...' : 'Chargement...';
      modalBody.innerHTML = `<p style="text-align:center; color:#969696;"><i class="fas fa-spinner fa-spin"></i> ${loadingText}</p>`;

      try {
        const response = await fetch('get_participants.php');
        const data = await response.json();
        
        if (data.length === 0) {
          const noParticipantsText = currentLanguage === 'en' ? 'No participants found.' : 'Aucun participant trouvé.';
          modalBody.innerHTML = `<p style="text-align:center; color:#969696;">${noParticipantsText}</p>`;
          return;
        }

        // Group participants by event
        const groupedData = {};
        data.forEach(participant => {
          if (!groupedData[participant.event_title]) {
            groupedData[participant.event_title] = [];
          }
          groupedData[participant.event_title].push(participant);
        });

        const nameLabel = currentLanguage === 'en' ? 'Name' : 'Nom';
        const emailLabel = currentLanguage === 'en' ? 'Email' : 'Email';
        const dateLabel = currentLanguage === 'en' ? 'Registration Date' : 'Date d\'Inscription';

        let html = '';
        Object.keys(groupedData).forEach(eventTitle => {
          html += `
            <div class="event-group">
              <div class="event-group-title">
                <i class="fas fa-calendar-alt"></i> ${eventTitle}
              </div>
          `;
          
          groupedData[eventTitle].forEach(participant => {
            html += `
              <div class="participant-item">
                <div class="participant-info">
                  <strong>${nameLabel}</strong>
                  <span>${participant.nom_participant}</span>
                </div>
                <div class="participant-info">
                  <strong>${emailLabel}</strong>
                  <span>${participant.email_participant}</span>
                </div>
                <div class="participant-info">
                  <strong>${dateLabel}</strong>
                  <span>${new Date(participant.date_participation).toLocaleString('fr-FR')}</span>
                </div>
              </div>
            `;
          });
          
          html += '</div>';
        });

        modalBody.innerHTML = html;
      } catch (error) {
        const errorText = currentLanguage === 'en' ? 'Error loading participants.' : 'Erreur lors du chargement des participants.';
        modalBody.innerHTML = `<p style="text-align:center; color:#ff6b6b;">${errorText}</p>`;
      }
    }

    async function loadAllTickets() {
      const modalBody = document.getElementById('modalBody');
      const loadingText = currentLanguage === 'en' ? 'Loading tickets...' : 'Chargement des tickets...';
      modalBody.innerHTML = `<p style="text-align:center; color:#969696;"><i class="fas fa-spinner fa-spin"></i> ${loadingText}</p>`;

      try {
        const response = await fetch('get_all_tickets.php');
        const tickets = await response.json();

        if (tickets.error) {
          modalBody.innerHTML = `<p style="text-align:center; color:#ff6b6b;">${tickets.error}</p>`;
          return;
        }

        if (tickets.length === 0) {
          const noTicketsText = currentLanguage === 'en' ? 'No tickets generated yet.' : 'Aucun ticket généré pour le moment.';
          modalBody.innerHTML = `<p style="text-align:center; color:#969696;">${noTicketsText}</p>`;
          return;
        }

        const participantLabel = currentLanguage === 'en' ? 'Participant' : 'Participant';
        const emailLabel = currentLanguage === 'en' ? 'Email' : 'Email';
        const eventLabel = currentLanguage === 'en' ? 'Event' : 'Événement';
        const statusLabel = currentLanguage === 'en' ? 'Status' : 'Statut';
        const createdLabel = currentLanguage === 'en' ? 'Generated' : 'Généré';
        const qrLabel = currentLanguage === 'en' ? 'QR Code' : 'QR Code';
        const downloadLabel = currentLanguage === 'en' ? 'Download PNG' : 'Télécharger PNG';

        let html = '<div style="display: grid; gap: 16px;">';
        tickets.forEach(ticket => {
          const statusClass = `ticket-status-${ticket.status}`;
          const statusText = ticket.status === 'active' ? (currentLanguage === 'en' ? 'Active' : 'Actif') :
                           ticket.status === 'used' ? (currentLanguage === 'en' ? 'Used' : 'Utilisé') :
                           (currentLanguage === 'en' ? 'Cancelled' : 'Annulé');

          html += `
            <div class="ticket-item">
              <div class="ticket-info">
                <strong>${participantLabel}</strong>
                <span>${ticket.participant_name}</span>
                <strong style="margin-top: 8px;">${emailLabel}</strong>
                <span>${ticket.participant_email}</span>
              </div>
              <div class="ticket-info">
                <strong>${eventLabel}</strong>
                <span>${ticket.event_title}</span>
                <strong style="margin-top: 8px;">${statusLabel}</strong>
                <span class="ticket-status-badge ${statusClass}">${statusText}</span>
              </div>
              <div class="ticket-info">
                <strong>${createdLabel}</strong>
                <span>${new Date(ticket.created_at).toLocaleString('fr-FR')}</span>
                <strong style="margin-top: 8px;">Ticket #</strong>
                <span style="color: #f5c242;">${String(ticket.id_ticket).padStart(6, '0')}</span>
              </div>
              <div class="ticket-qr">
                <img src="../front/${ticket.qr_code_path}" alt="QR Code">
                <div style="color: #969696; font-size: 0.75rem; margin-top: 4px;">${qrLabel}</div>
                <a href="../../controller/generate_ticket_image.php?id=${ticket.id_ticket}" download class="btn-download-ticket" style="display: inline-flex; align-items: center; gap: 6px; margin-top: 10px; padding: 8px 16px; background: linear-gradient(135deg, #f5c242, #f39c12); color: #000; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 700; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(245, 194, 66, 0.4)';" onmouseout="this.style.transform=''; this.style.boxShadow='';">
                  <i class="fas fa-download"></i>
                  <span>${downloadLabel}</span>
                </a>
              </div>
            </div>
          `;
        });
        html += '</div>';

        modalBody.innerHTML = html;
      } catch (error) {
        const errorText = currentLanguage === 'en' ? 'Error loading tickets.' : 'Erreur lors du chargement des tickets.';
        modalBody.innerHTML = `<p style="text-align:center; color:#ff6b6b;">${errorText}</p>`;
      }
    }

    // Show tickets modal for a specific event
    async function showTicketsModal(eventId, eventTitle) {
      const modal = document.getElementById('eventModal');
      const modalTitle = document.getElementById('modalTitle');
      const modalBody = document.getElementById('modalBody');

      modal.classList.add('active');

      const title = currentLanguage === 'en' ? `Tickets - ${eventTitle}` : `Tickets - ${eventTitle}`;
      modalTitle.textContent = title;

      const loadingText = currentLanguage === 'en' ? 'Loading tickets...' : 'Chargement des tickets...';
      modalBody.innerHTML = `<p style="text-align:center; color:#969696;"><i class="fas fa-spinner fa-spin"></i> ${loadingText}</p>`;

      try {
        const response = await fetch(`get_tickets.php?id_evenement=${eventId}`);
        const tickets = await response.json();

        if (tickets.error) {
          modalBody.innerHTML = `<p style="text-align:center; color:#ff6b6b;">${tickets.error}</p>`;
          return;
        }

        if (tickets.length === 0) {
          const noTicketsText = currentLanguage === 'en' ? 'No tickets generated for this event yet.' : 'Aucun ticket généré pour cet événement.';
          modalBody.innerHTML = `<p style="text-align:center; color:#969696;">${noTicketsText}</p>`;
          return;
        }

        const participantLabel = currentLanguage === 'en' ? 'Participant' : 'Participant';
        const emailLabel = currentLanguage === 'en' ? 'Email' : 'Email';
        const statusLabel = currentLanguage === 'en' ? 'Status' : 'Statut';
        const createdLabel = currentLanguage === 'en' ? 'Generated' : 'Généré';
        const qrLabel = currentLanguage === 'en' ? 'QR Code' : 'QR Code';

        let html = '<div>';
        tickets.forEach(ticket => {
          const statusClass = `ticket-status-${ticket.status}`;
          const statusText = ticket.status === 'active' ? (currentLanguage === 'en' ? 'Active' : 'Actif') :
                           ticket.status === 'used' ? (currentLanguage === 'en' ? 'Used' : 'Utilisé') :
                           (currentLanguage === 'en' ? 'Cancelled' : 'Annulé');

          html += `
            <div class="ticket-item">
              <div class="ticket-info">
                <strong>${participantLabel}</strong>
                <span>${ticket.participant_name}</span>
                <strong style="margin-top: 8px;">${emailLabel}</strong>
                <span>${ticket.participant_email}</span>
              </div>
              <div class="ticket-info">
                <strong>${statusLabel}</strong>
                <span class="ticket-status-badge ${statusClass}">${statusText}</span>
                <strong style="margin-top: 8px;">${createdLabel}</strong>
                <span>${new Date(ticket.created_at).toLocaleString('fr-FR')}</span>
              </div>
              <div class="ticket-qr">
                <img src="../front/${ticket.qr_code_path}" alt="QR Code">
                <div style="color: #969696; font-size: 0.75rem; margin-top: 4px;">${qrLabel}</div>
                <a href="../../controller/generate_ticket_image.php?id=${ticket.id_ticket}" download class="btn-download-ticket" style="display: inline-flex; align-items: center; gap: 6px; margin-top: 10px; padding: 8px 16px; background: linear-gradient(135deg, #f5c242, #f39c12); color: #000; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 700; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(245, 194, 66, 0.4)';" onmouseout="this.style.transform=''; this.style.boxShadow='';">
                  <i class="fas fa-download"></i>
                  <span>${currentLanguage === 'en' ? 'Download PNG' : 'Télécharger PNG'}</span>
                </a>
              </div>
            </div>
          `;
        });
        html += '</div>';

        modalBody.innerHTML = html;
      } catch (error) {
        const errorText = currentLanguage === 'en' ? 'Error loading tickets.' : 'Erreur lors du chargement des tickets.';
        modalBody.innerHTML = `<p style="text-align:center; color:#ff6b6b;">${errorText}</p>`;
      }
    }
  </script>
  
</body>
</html>
