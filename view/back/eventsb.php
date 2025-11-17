<?php
require_once __DIR__ . '/../../controller/EvenementController.php';
require_once __DIR__ . '/../../controller/ParticipationController.php';

$eventController = new EvenementController();
$participationController = new ParticipationController();

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
    }

    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 32px;
    }

    .stat-card {
      background: linear-gradient(135deg, rgba(22, 22, 26, 0.9), rgba(27, 27, 32, 0.9));
      border: 1px solid rgba(245, 194, 66, 0.2);
      border-radius: 12px;
      padding: 24px;
      display: flex;
      align-items: center;
      gap: 16px;
      transition: all 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(245, 194, 66, 0.2);
      border-color: rgba(245, 194, 66, 0.4);
    }

    .stat-icon {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
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

    .stat-content {
      flex: 1;
    }

    .stat-label {
      color: #969696;
      font-size: 0.85rem;
      margin-bottom: 4px;
      font-weight: 600;
    }

    .stat-value {
      font-family: 'Orbitron', sans-serif;
      font-size: 2rem;
      font-weight: 700;
      color: #f5c242;
      line-height: 1;
    }

    .events-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 32px;
    }

    .events-header h2 {
      font-family: 'Orbitron', sans-serif;
      color: #f5c242;
      font-size: 2rem;
      margin: 0;
    }

    .events-table {
      background: rgba(255, 255, 255, 0.03);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
    }

    .events-table table {
      width: 100%;
      border-collapse: collapse;
    }

    .events-table thead {
      background: linear-gradient(135deg, #16161a, #1b1b20);
    }

    .events-table th {
      padding: 16px;
      text-align: left;
      font-family: 'Orbitron', sans-serif;
      color: #f5c242;
      font-weight: 700;
      font-size: 0.9rem;
      border-bottom: 2px solid rgba(245, 194, 66, 0.3);
    }

    .events-table td {
      padding: 16px;
      color: #cfd3d8;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
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
    }

    .status-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 700;
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

    .action-buttons {
      display: flex;
      gap: 8px;
    }

    .btn-action {
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #fff;
      padding: 6px 12px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.85rem;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
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
    <a href="dashboard.html">Overview</a>
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
      <h1>Events Management</h1>
      <div class="user">
        <img src="../images/fery.jpg" alt="User Avatar">
        <span>FoxLeader</span>
      </div>
    </div>

    <div class="content">
      <div class="events-management">
        <!-- Statistics Section -->
        <div class="stats-container">
          <div class="stat-card">
            <div class="stat-icon total">
              <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
              <div class="stat-label">Total Events</div>
              <div class="stat-value"><?= $totalEvents ?></div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon upcoming">
              <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
              <div class="stat-label">Upcoming Events</div>
              <div class="stat-value"><?= $upcomingEvents ?></div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon expired">
              <i class="fas fa-calendar-times"></i>
            </div>
            <div class="stat-content">
              <div class="stat-label">Expired Events</div>
              <div class="stat-value"><?= $expiredEvents ?></div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon participants">
              <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
              <div class="stat-label">Total Participants</div>
              <div class="stat-value"><?= $totalParticipants ?></div>
            </div>
          </div>
        </div>

        <div class="events-header">
          <h2>All Events</h2>
        </div>

        <div class="events-table">
          <table>
            <thead>
              <tr>
                <th>Title</th>
                <th>Location</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Participants</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($evenements)): ?>
                <tr>
                  <td colspan="7">
                    <div class="empty-state">
                      <i class="fas fa-calendar-times"></i>
                      <p>No events found. Create a new event from the frontend.</p>
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
                  <td class="event-title-cell"><?= htmlspecialchars($event->getTitre()) ?></td>
                  <td><?= htmlspecialchars($event->getLieu()) ?></td>
                  <td><?= $event->getDateDebut()->format('M d, Y - H:i') ?></td>
                  <td><?= $event->getDateFin()->format('M d, Y - H:i') ?></td>
                  <td><?= $nbParticipants ?></td>
                  <td><span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                  <td>
                    <div class="action-buttons">
                      <a href="../front/events.php" class="btn-action">
                        <i class="fas fa-eye"></i> View
                      </a>
                      <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this event?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id_evenement" value="<?= $event->getIdEvenement() ?>">
                        <button type="submit" class="btn-action delete">
                          <i class="fas fa-trash"></i> Delete
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

  <!-- ===== PAGE TRANSITION OVERLAY ===== -->
  <div class="transition-screen"></div>

  <script>
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
