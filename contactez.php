<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../../../controllers/ReclamationController.php';

$reclamationController = new ReclamationController();
$successMessage = '';
$errorMessage = '';

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
        $_SESSION['user_email'] = $_POST['email'];
    } else {
        $errorMessage = "Something went wrong. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contactez-nous - FoxUnity</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <main>
        <section class="support-section">
            <div class="support-content">
                <div class="support-text-area">
                    <h2 class="section-title">Contactez <span>Nous</span></h2>
                    <p class="support-description">Have a question, issue, or feedback? Fill out the form and our team will get back to you as soon as possible.</p>
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

                    <form id="contact-form" method="POST" action="">
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
                                <option value="Account Issues">Account Issues</option>
                                <option value="Payment & Billing">Payment & Billing</option>
                                <option value="Technical Support">Technical Support</option>
                                <option value="Shop & Orders">Shop & Orders</option>
                                <option value="Trading Issues">Trading Issues</option>
                                <option value="Events & Tournaments">Events & Tournaments</option>
                                <option value="Charity & Donations">Charity & Donations</option>
                                <option value="Feedback & Suggestions">Feedback & Suggestions</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Message *</label>
                            <textarea name="message" class="form-textarea" placeholder="Describe your issue or question in detail..." required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i>
                            Envoyer le message
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
