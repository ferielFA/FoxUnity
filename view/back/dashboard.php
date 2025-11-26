<?php
require_once __DIR__ . '/../../controller/EvenementController.php';
require_once __DIR__ . '/../../controller/ParticipationController.php';
require_once __DIR__ . '/../../controller/CommentController.php';

$eventController = new EvenementController();
$participationController = new ParticipationController();
$commentController = new CommentController();

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
        $hour = (int)$participant->getDateInscription()->format('H');
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
      <h1>Welcome, Commander</h1>
      <div class="user">
        <img src="../images/fery.jpg" alt="User Avatar"> 
        <span>Profil Expert</span>
      </div>
    </div>

    <div class="content">
      <!-- Stats Cards -->
      <div class="stats-overview">
        <div class="stat-card-dash" style="background: linear-gradient(135deg, #f5c242, #f39c12);">
          <div class="stat-icon-dash"><i class="fas fa-calendar-alt"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= $totalEvents ?></div>
            <div class="stat-label">Total Events</div>
          </div>
        </div>

        <div class="stat-card-dash" style="background: linear-gradient(135deg, #2ed573, #1abc9c);">
          <div class="stat-icon-dash"><i class="fas fa-users"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= $totalParticipants ?></div>
            <div class="stat-label">Total Participants</div>
          </div>
        </div>

        <div class="stat-card-dash" style="background: linear-gradient(135deg, #ff6b6b, #ee5a6f);">
          <div class="stat-icon-dash"><i class="fas fa-comments"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= $totalComments ?></div>
            <div class="stat-label">Total Comments</div>
          </div>
        </div>

        <div class="stat-card-dash" style="background: linear-gradient(135deg, #a29bfe, #6c5ce7);">
          <div class="stat-icon-dash"><i class="fas fa-star"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= $averageRating ?></div>
            <div class="stat-label">Average Rating</div>
          </div>
        </div>
      </div>

      <!-- Charts Row 1 -->
      <div class="charts-grid">
        <div class="chart-card">
          <h3><i class="fas fa-chart-line"></i> Participation Trend (Last 7 Days)</h3>
          <canvas id="participationTrendChart"></canvas>
        </div>

        <div class="chart-card">
          <h3><i class="fas fa-chart-pie"></i> Events by Status</h3>
          <canvas id="eventsByStatusChart"></canvas>
        </div>
      </div>

      <!-- Charts Row 2 -->
      <div class="charts-grid">
        <div class="chart-card">
          <h3><i class="fas fa-chart-bar"></i> Top 5 Events by Participants</h3>
          <canvas id="topEventsChart"></canvas>
        </div>

        <div class="chart-card">
          <h3><i class="fas fa-star-half-alt"></i> Rating Distribution</h3>
          <canvas id="ratingDistributionChart"></canvas>
        </div>
      </div>

      <!-- Charts Row 3 -->
      <div class="charts-grid">
        <div class="chart-card chart-card-wide">
          <h3><i class="fas fa-clock"></i> Registration Hours Distribution</h3>
          <canvas id="hourlyRegistrationsChart"></canvas>
        </div>
      </div>

      <!-- Original Cards -->
      <div class="original-cards">
        <div class="card">
          <h3>Users</h3>
          <p>Manage player accounts, view activity levels, and assign roles. Monitor active members in real time.</p>
        </div>

        <div class="card">
          <h3>Shop Overview</h3>
          <p>View current stock, promotions, and trade offers. Adjust pricing and featured items instantly.</p>
        </div>

        <div class="card">
          <h3>Trade History</h3>
          <p>Review completed trades, pending exchanges, and item transactions between players.</p>
        </div>

        <div class="card" onclick="window.location.href='eventsb.php'" style="cursor:pointer">
          <h3>Events</h3>
          <p>Track current and upcoming tournaments, seasonal events, and community missions.</p>
        </div>

        <div class="card">
          <h3>News Feed</h3>
          <p>Stay updated with game patches, esports news, and upcoming tournaments.</p>
        </div>

        <div class="card">
          <h3>Support</h3>
          <p>Check user feedback, analyze satisfaction trends, and respond to the community.</p>
        </div>
      </div>
    </div>

    <footer class="site-footer">
      © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
    </footer>
  </div>

  <!-- ===== PAGE TRANSITION OVERLAY ===== -->
  <div class="transition-screen"></div>

  <style>
    .stats-overview {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 32px;
    }

    .stat-card-dash {
      border-radius: 16px;
      padding: 24px;
      color: white;
      display: flex;
      align-items: center;
      gap: 20px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.3);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .stat-card-dash:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 32px rgba(0,0,0,0.4);
    }

    .stat-icon-dash {
      width: 70px;
      height: 70px;
      background: rgba(255,255,255,0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
    }

    .stat-info {
      flex: 1;
    }

    .stat-value {
      font-family: 'Orbitron', sans-serif;
      font-size: 2.5rem;
      font-weight: 700;
      line-height: 1;
      margin-bottom: 8px;
    }

    .stat-label {
      font-size: 0.9rem;
      opacity: 0.9;
      font-weight: 600;
    }

    .charts-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
      gap: 24px;
      margin-bottom: 24px;
    }

    .chart-card {
      background: linear-gradient(135deg, rgba(22, 22, 26, 0.95), rgba(27, 27, 32, 0.95));
      border: 1px solid rgba(245, 194, 66, 0.2);
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.3);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .chart-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 32px rgba(245, 194, 66, 0.2);
      border-color: rgba(245, 194, 66, 0.4);
    }

    .chart-card h3 {
      font-family: 'Orbitron', sans-serif;
      color: #f5c242;
      margin: 0 0 20px 0;
      font-size: 1.1rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .chart-card-wide {
      grid-column: 1 / -1;
    }

    .chart-card canvas {
      max-height: 300px;
    }

    .original-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-top: 32px;
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
      
      .stat-card-dash {
        flex-direction: column;
        text-align: center;
      }
    }
  </style>

  <script>
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