<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Nine Tailed Fox Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Poppins:wght@300;600&display=swap" rel="stylesheet">
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
    <a href="#" class="active">Overview</a>
    <a href="#">Users</a>
    <a href="#">Shop</a>
    <a href="#">Trade History</a>
    <a href="#">Events</a>
    <a href="news_admin.php">News</a>
    <a href="#">Support</a>
    <a href="../front/indexf.php">← Return Homepage</a>
  </div>

  <!-- ===== MAIN ===== -->
  <div class="main">
    <div class="topbar">
      <h1>Welcome, Commander</h1>
      <div class="user">
        <img src=../images/meriem.png alt="User Avatar">
        <span>FoxLeader</span>
      </div>
    </div>

    <div class="content">
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

      <div class="card">
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

    <footer class="site-footer">
      © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
    </footer>
  </div>

  <!-- ===== PAGE TRANSITION OVERLAY ===== -->
  <div class="transition-screen"></div>

  <script>
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