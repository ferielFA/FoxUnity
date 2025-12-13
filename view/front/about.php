<?php

declare(strict_types=1);

require_once __DIR__ . '/../../model/config.php';
require_once __DIR__ . '/../../controller/UserController.php';
require_once __DIR__ . '/../../controller/TradeHistoryController.php';

$db = getDB();

function ensureCharityTables(PDO $db): void {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS charity_votes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            charity_key VARCHAR(64) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $e) {
        error_log('ensureCharityTables error: ' . $e->getMessage());
    }
}

ensureCharityTables($db);

$charities = [
    'unicef' => 'UNICEF',
    'red_cross' => 'Red Cross',
    'msf' => 'Doctors Without Borders',
    'save_children' => 'Save the Children'
];

$charityIcons = [
    'unicef' => 'fa-child',
    'red_cross' => 'fa-first-aid',
    'msf' => 'fa-briefcase-medical',
    'save_children' => 'fa-hands-helping'
];

$currentUser = null;
if (UserController::isLoggedIn()) {
    $currentUser = UserController::getCurrentUser();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if (!$currentUser) {
        echo json_encode(['success' => false, 'error' => 'Please login to vote']);
        exit();
    }
    $charity = isset($_POST['charity']) ? trim((string)$_POST['charity']) : '';
    if (!array_key_exists($charity, $charities)) {
        echo json_encode(['success' => false, 'error' => 'Invalid charity']);
        exit();
    }
    try {
        $stmt = $db->prepare("INSERT INTO charity_votes (user_id, charity_key) VALUES (:uid, :ck) ON DUPLICATE KEY UPDATE charity_key = VALUES(charity_key), created_at = CURRENT_TIMESTAMP");
        $stmt->execute([':uid' => $currentUser->getId(), ':ck' => $charity]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit();
    }
    // Return updated stats
    $counts = getVoteCounts($db);
    $stats = (new TradeHistoryController())->getStatistics();
    $donationBase = (float)$stats['total_value'];
    $donationTenPercent = round($donationBase * 0.10, 2);
    echo json_encode(['success' => true, 'counts' => $counts, 'donation' => $donationTenPercent]);
    exit();
}

function getVoteCounts(PDO $db): array {
    try {
        $stmt = $db->query("SELECT charity_key, COUNT(*) as votes FROM charity_votes GROUP BY charity_key");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $row) {
            $out[$row['charity_key']] = (int)$row['votes'];
        }
        return $out;
    } catch (PDOException $e) {
        return [];
    }
}

$counts = getVoteCounts($db);
$totalVotes = array_sum($counts);
$userVote = null;
if ($currentUser) {
    try {
        $stmt = $db->prepare("SELECT charity_key FROM charity_votes WHERE user_id = :uid");
        $stmt->execute([':uid' => $currentUser->getId()]);
        $userVote = $stmt->fetchColumn() ?: null;
    } catch (PDOException $e) {
        $userVote = null;
    }
}

$stats = (new TradeHistoryController())->getStatistics();
$donationBase = (float)$stats['total_value'];
$donationTenPercent = round($donationBase * 0.10, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>FoxUnity - About Us</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="styletrade.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
  <style>
    .user-dropdown { position: relative; display: inline-block; }
    .username-display { cursor: pointer; display: flex; align-items: center; gap: 10px; transition: all 0.3s ease; padding: 5px 10px; border-radius: 8px; }
    .username-display:hover { background: rgba(255, 122, 0, 0.1); }
    .username-display img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #ff7a00; }
    .username-display span { color: #ff7a00; font-weight: 600; font-size: 16px; }
    .username-display i.fa-chevron-down { font-size: 12px; color: #ff7a00; transition: transform 0.3s ease; }
    .username-display i.fa-user-circle { font-size: 24px; color: #ff7a00; }
    .user-dropdown.active .username-display i.fa-chevron-down { transform: rotate(180deg); }
    .dropdown-menu { position: absolute; top: 100%; right: 0; margin-top: 10px; background: rgba(20, 20, 20, 0.98); border: 2px solid rgba(255, 122, 0, 0.3); border-radius: 12px; min-width: 200px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.3s ease; z-index: 1000; overflow: hidden; }
    .user-dropdown.active .dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }
    .dropdown-item { padding: 12px 15px; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; transition: all 0.3s ease; border-left: 3px solid transparent; }
    .dropdown-item:hover { background: rgba(255, 122, 0, 0.1); border-left-color: #ff7a00; }
    .dropdown-item i { font-size: 16px; color: #ff7a00; width: 20px; }
    .dropdown-divider { height: 1px; background: rgba(255, 122, 0, 0.2); margin: 5px 0; }
    .dropdown-item.logout { color: #ff4444; }
    .dropdown-item.logout i { color: #ff4444; }
    .dropdown-item.logout:hover { background: rgba(255, 68, 68, 0.1); border-left-color: #ff4444; }
    .cart-icon { color: #ff7a00 !important; position: relative; font-weight: 600; transition: all 0.3s ease; }
    .cart-icon i { color: #ff7a00; font-size: 18px; }
    .cart-count { background: linear-gradient(135deg, #ff7a00, #ff4f00); color: white; border-radius: 50%; padding: 2px 6px; font-size: 11px; font-weight: 700; position: absolute; top: -8px; right: -8px; min-width: 18px; text-align: center; box-shadow: 0 2px 8px rgba(255, 122, 0, 0.4); }
    .about-hero { padding: 60px 20px; text-align: center; }
    .poll-card { max-width: 900px; margin: 20px auto; background: rgba(15,15,15,0.75); border: 1px solid rgba(255,122,0,0.25); border-radius: 16px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.35); backdrop-filter: blur(6px); }
    .poll-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; }
    .poll-option { position: relative; background: linear-gradient(180deg, rgba(25,25,25,0.95), rgba(18,18,18,0.95)); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 14px; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease; }
    .poll-option:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255,122,0,0.15); border-color: rgba(255,122,0,0.35); }
    .poll-option input { position: absolute; opacity: 0; pointer-events: none; }
    .option-content { display: flex; align-items: center; gap: 12px; }
    .option-content i { font-size: 22px; }
    .option-content .title { color: #fff; font-weight: 700; }
    .option-content .check { margin-left: auto; color: #2ed573; opacity: 0; transition: opacity 0.2s ease; }
    .poll-option input:checked + .option-content .check { opacity: 1; }
    .vote-btn { margin-top: 14px; background: linear-gradient(135deg, #ff7a00, #ff4f00); color: #000; border: none; padding: 12px 18px; border-radius: 10px; cursor: pointer; font-weight: 700; }
    .donation-summary { margin-top: 18px; color: #ccc; }
    .bar { height: 12px; border-radius: 6px; background: #1a1a1a; overflow: hidden; border: 1px solid rgba(255,255,255,0.06); }
    .bar-fill { height: 100%; background: linear-gradient(135deg, #ff7a00, #ff4f00); width: 0; transition: width 700ms ease; }
    .poll-row { display: grid; grid-template-columns: 1fr 70px; gap: 10px; align-items: center; margin-top: 10px; }
    .note { color: #888; font-size: 13px; }
    .charity-unicef .option-content i { color: #00a3ff; }
    .charity-red_cross .option-content i { color: #ff4d4f; }
    .charity-msf .option-content i { color: #e74c3c; }
    .charity-save_children .option-content i { color: #ff7a00; }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="logo-section">
      <img src="../images/Nine__1_-removebg-preview.png" alt="FoxUnity Logo" class="site-logo">
      <span class="site-name">FoxUnity</span>
    </div>
    <nav class="site-nav">
      <a href="index.php">Home</a>
      <a href="events.html">Events</a>
      <a href="shop.html">Shop</a>
      <a href="trading.php">Trading</a>
      <a href="news.html">News</a>
      <a href="reclamation.html">Support</a>
      <a href="about.php" class="active">About Us</a>
    </nav>
    <div class="header-right">
      <div class="user-dropdown" id="userDropdown">
        <div class="username-display">
          <?php if ($currentUser): ?>
            <?php if ($currentUser->getImage()): ?>
              <img src="<?php echo htmlspecialchars('../../view/' . $currentUser->getImage()); ?>" alt="Profile">
            <?php else: ?>
              <i class="fas fa-user-circle"></i>
            <?php endif; ?>
            <span><?php echo htmlspecialchars($currentUser->getUsername()); ?></span>
          <?php else: ?>
            <i class="fas fa-user-circle"></i>
            <span>Guest</span>
          <?php endif; ?>
          <i class="fas fa-chevron-down"></i>
        </div>
        <div class="dropdown-menu">
          <?php if ($currentUser): ?>
          <a href="profile.php" class="dropdown-item"><i class="fas fa-user"></i><span>My Profile</span></a>
          <a href="tradehis.php" class="dropdown-item"><i class="fas fa-history"></i><span>History</span></a>
          <?php if (strtolower($currentUser->getRole()) === 'admin' || strtolower($currentUser->getRole()) === 'superadmin'): ?>
          <a href="../back/dashboard.php" class="dropdown-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
          <?php endif; ?>
          <div class="dropdown-divider"></div>
          <a href="Logout.php" class="dropdown-item logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
          <?php else: ?>
          <a href="Login.php" class="dropdown-item"><i class="fas fa-sign-in-alt"></i><span>Login/Register</span></a>
          <?php endif; ?>
        </div>
      </div>
      <a href="panier.php" class="cart-icon"><i class="fas fa-shopping-cart"></i> Cart <span class="cart-count">0</span></a>
    </div>
  </header>

  <main class="main-section">
    <section class="about-hero">
      <h1 class="main-title">About <span>FoxUnity</span></h1>
      <p class="intro-description">We believe in gaming for good. 10% of our income goes to charity.</p>
    </section>

    <section class="poll-card">
      <h2 class="section-title"><span>Charity</span> Poll</h2>
      <p class="note">Vote for the charity to receive 10% of site income. One vote per account. You can change your vote anytime.</p>
      <form id="pollForm">
        <div class="poll-options">
          <?php foreach ($charities as $key => $label): ?>
            <label class="poll-option charity-<?= htmlspecialchars($key) ?>">
              <input type="radio" name="charity" value="<?= htmlspecialchars($key) ?>" <?= $userVote === $key ? 'checked' : '' ?>>
              <div class="option-content">
                <i class="fas <?= htmlspecialchars($charityIcons[$key] ?? 'fa-heart') ?>"></i>
                <span class="title"><?= htmlspecialchars($label) ?></span>
                <span class="check"><i class="fas fa-check-circle"></i></span>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
        <button type="submit" class="vote-btn"><i class="fas fa-vote-yea"></i> Submit Vote</button>
      </form>

      <div class="donation-summary">
        <p><strong>10% Donation:</strong> Distributed based on poll results.</p>
        <?php foreach ($charities as $key => $label): 
            $votes = $counts[$key] ?? 0;
            $pct = $totalVotes > 0 ? round(($votes / $totalVotes) * 100) : 0;
        ?>
        <div class="poll-row">
          <div>
            <strong><?= htmlspecialchars($label) ?></strong>
            <div class="bar"><div class="bar-fill" data-pct="<?= $pct ?>"></div></div>
          </div>
          <div style="text-align:right; color:#fff;"><?= $pct ?>%</div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="footer-bottom">
      <p>© 2025 FoxUnity. 10% of income goes to charity.</p>
    </div>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const userDropdown = document.getElementById('userDropdown');
      if (userDropdown) {
        const usernameDisplay = userDropdown.querySelector('.username-display');
        usernameDisplay.addEventListener('click', function(e) {
          e.stopPropagation();
          userDropdown.classList.toggle('active');
        });
        document.addEventListener('click', function(e) {
          if (!userDropdown.contains(e.target)) {
            userDropdown.classList.remove('active');
          }
        });
      }
      document.querySelectorAll('.bar-fill').forEach(function(el) {
        var pct = parseInt(el.getAttribute('data-pct') || '0');
        requestAnimationFrame(function(){ el.style.width = pct + '%'; });
      });
    });
    document.getElementById('pollForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      fetch('about.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
          if (!data.success) { alert(data.error || 'Failed to vote'); return; }
          location.reload();
        })
        .catch(err => { console.error(err); alert('Request failed'); });
    });
  </script>
</body>
</html>
