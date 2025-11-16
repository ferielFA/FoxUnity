<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Inclure les contrôleurs
require_once __DIR__ . '/controllers/ReclamationController.php';

$reclamationController = new ReclamationController();
$userReclamations = [];
$successMessage = '';
$errorMessage = '';

// Récupérer les réclamations si l'utilisateur a soumis un formulaire
if (isset($_POST['email'])) {
    $userReclamations = $reclamationController->getReclamationsByEmail($_POST['email']);
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'])) {
    $reclamation = new Reclamation(
        $_POST['full_name'],
        $_POST['email'],
        $_POST['subject'],
        $_POST['message']
    );
    
    $result = $reclamationController->addReclamation($reclamation);
    if ($result) {
        $successMessage = "Message sent successfully! We'll get back to you soon.";
        // Recharger les réclamations
        $userReclamations = $reclamationController->getReclamationsByEmail($_POST['email']);
    } else {
        $errorMessage = "Something went wrong. Please try again.";
    }
}

// Traitement de la suppression
if (isset($_GET['delete_id'])) {
    $result = $reclamationController->deleteReclamation($_GET['delete_id']);
    if ($result) {
        $successMessage = "Request deleted successfully!";
        // Recharger les réclamations si email en session
        if (isset($_POST['email'])) {
            $userReclamations = $reclamationController->getReclamationsByEmail($_POST['email']);
        }
    } else {
        $errorMessage = "Error deleting request.";
    }
    // Rediriger pour éviter la resoumission
    header("Location: " . str_replace("?delete_id=" . $_GET['delete_id'], "", $_SERVER['REQUEST_URI']));
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoxUnity - Support Center</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* VOTRE CSS EXISTANT - COLLEZ TOUT VOTRE CSS ICI */
        .cart-icon {
            color: #ff7a00 !important;
            position: relative;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .cart-icon:hover {
            color: #ff9933 !important;
            transform: translateY(-2px);
        }
        
        /* ... COLLEZ TOUT LE RESTE DE VOTRE CSS ... */
        
    </style>
</head>
<body>
    
    <div class="bubbles">
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
    </div>

    
    <header class="site-header">
        <div class="logo-section">
            <img src="../images/Nine__1_-removebg-preview.png" alt="FoxUnity Logo" class="site-logo">
            <span class="site-name">FoxUnity</span>
        </div>
        
        <nav class="site-nav">
            <a href="indexf.html">Home</a>
            <a href="events.html">Events</a>
            <a href="shop.html">Shop</a>
            <a href="trading.html">Trading</a>
            <a href="news.html">News</a>
            <a href="reclamation.php" class="active">Support</a>
            <a href="about.html">About Us</a>
        </nav>
        
        <div class="header-right">
            <a href="login.html" class="login-register-link">
                <i class="fas fa-user"></i> Login / Register
            </a>
            <a href="profile.html" class="profile-icon">
                <i class="fas fa-user-circle"></i>
            </a>
            <a href="panier.html" class="cart-icon">
                <i class="fas fa-shopping-cart"></i> Cart
                <span class="cart-count">0</span>
            </a>
        </div>
    </header>

    <main>
      
        <section class="support-hero">
            <div class="support-hero-icon">
                <i class="fas fa-headset"></i>
            </div>
            <h1>Support <span>Center</span></h1>
            <p>We're here to help! Get answers to your questions or reach out to our support team directly.</p>
        </section>

        <section class="quick-links-section">
            <div class="quick-links-grid">
                <div class="quick-link-card" onclick="document.getElementById('contact-form').scrollIntoView({behavior: 'smooth'})">
                    <div class="quick-link-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Contact Us</h3>
                    <p>Send us a message and we'll respond within 2 hours</p>
                </div>

                <div class="quick-link-card" onclick="document.getElementById('my-reclamations').scrollIntoView({behavior: 'smooth'})">
                    <div class="quick-link-icon">
                        <i class="fas fa-list-alt"></i>
                    </div>
                    <h3>My Requests</h3>
                    <p>View and manage your previous support requests</p>
                </div>

                <div class="quick-link-card" onclick="document.getElementById('faq-section').scrollIntoView({behavior: 'smooth'})">
                    <div class="quick-link-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <h3>FAQ</h3>
                    <p>Find answers to commonly asked questions</p>
                </div>
            </div>
        </section>

        
        <section class="contact-form-section" id="contact-form">
            <div class="contact-container">
               
                <div class="contact-info">
                    <div>
                        <h2>Get In <span>Touch</span></h2>
                        <p class="contact-info-text">
                            Have a question, issue, or feedback? Fill out the form and our team will get back to you as soon as possible. 
                            We typically respond within 2 hours during business hours.
                        </p>
                    </div>

                    <div class="contact-method">
                        <div class="contact-method-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-method-details">
                            <h4>Email Support</h4>
                            <p><a href="mailto:support@foxunity.com">support@foxunity.com</a></p>
                        </div>
                    </div>

                    <div class="contact-method">
                        <div class="contact-method-icon">
                            <i class="fab fa-discord"></i>
                        </div>
                        <div class="contact-method-details">
                            <h4>Discord Community</h4>
                            <p><a href="#">Join our Discord server</a></p>
                        </div>
                    </div>

                    <div class="contact-method">
                        <div class="contact-method-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="contact-method-details">
                            <h4>Response Time</h4>
                            <p>Average response: 2 hours<br>24/7 Support Available</p>
                        </div>
                    </div>

                    <div class="contact-method">
                        <div class="contact-method-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-method-details">
                            <h4>Location</h4>
                            <p>Global Support Team<br>Serving customers worldwide</p>
                        </div>
                    </div>
                </div>

                
                <div class="contact-form-wrapper">
                    <?php if ($successMessage): ?>
                        <div id="success-message" class="message success-message show">
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo $successMessage; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($errorMessage): ?>
                        <div id="error-message" class="message error-message show">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo $errorMessage; ?></span>
                        </div>
                    <?php endif; ?>

                    <form id="support-form" method="POST" action="">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" class="form-input" placeholder="Enter your full name" required 
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" class="form-input" placeholder="your.email@example.com" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Subject *</label>
                            <select name="subject" class="form-select" required>
                                <option value="">Select a subject</option>
                                <option value="Account Issues" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Account Issues') ? 'selected' : ''; ?>>Account Issues</option>
                                <option value="Payment & Billing" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Payment & Billing') ? 'selected' : ''; ?>>Payment & Billing</option>
                                <option value="Technical Support" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Technical Support') ? 'selected' : ''; ?>>Technical Support</option>
                                <option value="Shop & Orders" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Shop & Orders') ? 'selected' : ''; ?>>Shop & Orders</option>
                                <option value="Trading Issues" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Trading Issues') ? 'selected' : ''; ?>>Trading Issues</option>
                                <option value="Events & Tournaments" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Events & Tournaments') ? 'selected' : ''; ?>>Events & Tournaments</option>
                                <option value="Charity & Donations" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Charity & Donations') ? 'selected' : ''; ?>>Charity & Donations</option>
                                <option value="Feedback & Suggestions" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Feedback & Suggestions') ? 'selected' : ''; ?>>Feedback & Suggestions</option>
                                <option value="Other" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Message *</label>
                            <textarea name="message" class="form-textarea" placeholder="Describe your issue or question in detail..." required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i>
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- SECTION MES RÉCLAMATIONS -->
        <section class="my-reclamations-section" id="my-reclamations">
            <div class="my-reclamations-container">
                <div class="section-header">
                    <h2>My <span>Requests</span></h2>
                    <p>View and manage all your previous support requests in one place</p>
                </div>

                <div class="reclamations-grid" id="reclamations-list">
                    <?php if (empty($userReclamations)): ?>
                        <div class="no-reclamations">
                            <i class="fas fa-inbox"></i>
                            <h3>No Requests Yet</h3>
                            <p>Submit your first support request using the form above</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($userReclamations as $reclamation): ?>
                            <div class="reclamation-card">
                                <div class="reclamation-header">
                                    <h3 class="reclamation-subject"><?php echo htmlspecialchars($reclamation['subject']); ?></h3>
                                    <span class="reclamation-status status-<?php echo $reclamation['statut']; ?>">
                                        <?php 
                                        $statusText = [
                                            'nouveau' => 'New',
                                            'en_cours' => 'In Progress', 
                                            'resolu' => 'Resolved'
                                        ];
                                        echo $statusText[$reclamation['statut']];
                                        ?>
                                    </span>
                                </div>
                                <div class="reclamation-meta">
                                    <div class="reclamation-date">
                                        <i class="far fa-calendar"></i>
                                        <?php echo date('M j, Y', strtotime($reclamation['date_creation'])); ?>
                                    </div>
                                </div>
                                <div class="reclamation-message">
                                    <?php echo htmlspecialchars($reclamation['message']); ?>
                                </div>
                                <div class="reclamation-actions">
                                    <button class="action-btn btn-view" onclick="viewReclamation(<?php echo $reclamation['id_reclamation']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="action-btn btn-edit" onclick="editReclamation(<?php echo $reclamation['id_reclamation']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="?delete_id=<?php echo $reclamation['id_reclamation']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this request?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- VOTRE SECTION FAQ EXISTANTE -->
        <section class="faq-section" id="faq-section">
            <h2>Frequently Asked <span>Questions</span></h2>
            <!-- ... VOTRE FAQ EXISTANTE ... -->
        </section>
    </main>

    <!-- VOTRE FOOTER EXISTANT -->

    <script>
        // Fonctions JavaScript
        function viewReclamation(id) {
            alert('Viewing reclamation ID: ' + id + '\nThis would show full details in a real implementation.');
        }

        function editReclamation(id) {
            alert('Editing reclamation ID: ' + id + '\nThis would open an edit form in a real implementation.');
        }

        // FAQ functionality
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const faqItem = question.parentElement;
                const isActive = faqItem.classList.contains('active');
                
                document.querySelectorAll('.faq-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                if (!isActive) {
                    faqItem.classList.add('active');
                }
            });
        });

        // Form submission feedback
        document.getElementById('support-form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.submit-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            
            // Le formulaire sera soumis normalement via PHP
        });

        // Cart count
        window.addEventListener('load', function() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = cart.length;
            }
        });
    </script>
</body>
</html>