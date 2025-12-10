<?php
require_once __DIR__ . '/../../controller/UserController.php';

// Check if user is logged in and is Admin or SuperAdmin
if (!UserController::isLoggedIn()) {
    header('Location: ../front/login.php');
    exit();
}

$currentUser = UserController::getCurrentUser();
$userRole = strtolower($currentUser ? $currentUser->getRole() : '');
if (!$currentUser || ($userRole !== 'admin' && $userRole !== 'superadmin')) {
    header('Location: ../front/index.php');
    exit();
}

$message = '';
$messageType = '';
$shouldRedirect = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    
    // Validate inputs
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($dob)) {
        $errors[] = "Date of birth is required";
    } else {
        // Validate date of birth - cannot be in the future
        $dobDate = new DateTime($dob);
        $today = new DateTime();
        if ($dobDate > $today) {
            $errors[] = 'Date of birth cannot be in the future!';
        }
    }
    
    
    if (empty($gender)) {
        $errors[] = "Gender is required";
    }
    
    // Handle image upload
    $imagePath = $currentUser->getImage();
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_image']['name'];
        $fileTmpName = $_FILES['profile_image']['tmp_name'];
        $fileSize = $_FILES['profile_image']['size'];
        $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($fileExt, $allowed)) {
            $errors[] = "Only JPG, JPEG, PNG & GIF files are allowed";
        } elseif ($fileSize > 5000000) { // 5MB max
            $errors[] = "File size must be less than 5MB";
        } else {
            $uploadDir = __DIR__ . '/../uploads/profiles/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Delete old image if exists
            if ($imagePath && file_exists(__DIR__ . '/../' . $imagePath)) {
                unlink(__DIR__ . '/../' . $imagePath);
            }
            
            $newFilename = 'profile_' . uniqid() . '.' . $fileExt;
            $destination = $uploadDir . $newFilename;
            
            if (move_uploaded_file($fileTmpName, $destination)) {
                $imagePath = 'uploads/profiles/' . $newFilename;
            } else {
                $errors[] = "Failed to upload image";
            }
        }
    }
    
    if (empty($errors)) {
        // Update user
        $currentUser->setUsername($username);
        $currentUser->setEmail($email);
        $currentUser->setDob($dob);
        $currentUser->setGender($gender);
        $currentUser->setImage($imagePath);
        
        if ($currentUser->update()) {
            $_SESSION['user'] = serialize($currentUser);
            $message = 'Profile updated successfully! Redirecting to your profile...';
            $messageType = 'success';
            $shouldRedirect = true;
        } else {
            $message = 'Failed to update profile';
            $messageType = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}

// Get user image - NO DEFAULT IMAGE
$userImage = null;
if ($currentUser->getImage()) {
    $userImage = '../../view/' . $currentUser->getImage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Admin Profile - FoxUnity Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Poppins:wght@300;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  
  <style>
    /* Admin Dropdown Styles - COHERENT */
    /* Admin Dropdown Styles - COHERENT */
    .admin-dropdown {
      position: relative;
      display: inline-block;
    }

    .admin-user {
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: all 0.3s ease;
      padding: 5px 10px;
      border-radius: 8px;
    }

    .admin-user:hover {
      background: rgba(255, 122, 0, 0.1);
    }

    .admin-user img {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #ff7a00;
    }

    .admin-user i.fa-user-circle {
      font-size: 35px;
      color: #ff7a00;
    }

    .admin-user span {
      color: #fff;
      font-weight: 600;
      font-size: 16px;
    }

    .admin-user img {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #ff7a00;
    }

    .admin-user i.fa-user-circle {
      font-size: 35px;
      color: #ff7a00;
    }

    .admin-user span {
      color: #fff;
      font-weight: 600;
      font-size: 16px;
    }

    .admin-user i.fa-chevron-down {
      font-size: 12px;
      color: #ff7a00;
      font-size: 12px;
      color: #ff7a00;
      transition: transform 0.3s ease;
    }

    .admin-dropdown.active .admin-user i.fa-chevron-down {
      transform: rotate(180deg);
    }

    .admin-dropdown-menu {
      position: absolute;
      top: 100%;
      right: 0;
      margin-top: 10px;
      background: rgba(20, 20, 20, 0.98);
      border: 2px solid rgba(255, 122, 0, 0.3);
      border-radius: 12px;
      min-width: 200px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s ease;
      z-index: 1000;
      overflow: hidden;
    }

    .admin-dropdown.active .admin-dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .dropdown-item {
      padding: 12px 15px;
      color: #fff;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: all 0.3s ease;
      border-left: 3px solid transparent;
    }

    .dropdown-item:hover {
      background: rgba(255, 122, 0, 0.1);
      border-left-color: #ff7a00;
    }

    .dropdown-item i {
      color: #ff7a00;
      width: 20px;
      font-size: 16px;
    }

    .dropdown-item.logout {
      color: #ff4444;
    }

    .dropdown-item.logout i {
      color: #ff4444;
    }

    .dropdown-item.logout:hover {
      background: rgba(255, 68, 68, 0.1);
      border-left-color: #ff4444;
    }

    .dropdown-divider {
      height: 1px;
      background: rgba(255, 122, 0, 0.2);
      margin: 5px 0;
    }

    /* Edit Profile Form Styles */
    .edit-profile-card {
      background: rgba(20, 20, 20, 0.95);
      border-radius: 15px;
      padding: 40px;
      margin: 20px 0;
      border: 1px solid rgba(255, 122, 0, 0.3);
      max-width: 800px;
      margin: 20px auto;
    }

    .page-title {
      font-family: 'Orbitron', sans-serif;
      font-size: 32px;
      color: #fff;
      margin-bottom: 10px;
      text-align: center;
    }

    .page-subtitle {
      color: #888;
      font-size: 16px;
      margin-bottom: 40px;
      text-align: center;
    }

    .profile-image-section {
      text-align: center;
      margin-bottom: 40px;
      padding-bottom: 30px;
      border-bottom: 2px solid rgba(255, 122, 0, 0.3);
      display: flex;
      flex-direction: column;
      align-items: center;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .current-avatar {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid #ff7a00;
      box-shadow: 0 10px 30px rgba(255, 122, 0, 0.4);
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(255, 122, 0, 0.1);
    }

    .current-avatar i {
      font-size: 80px;
      color: #ff7a00;
    }

    .current-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(255, 122, 0, 0.1);
    }

    .current-avatar i {
      font-size: 80px;
      color: #ff7a00;
    }

    .current-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
    }

    .upload-btn-wrapper {
      position: relative;
      display: inline-block;
      margin-top: 15px;
    }

    .upload-btn {
      background: linear-gradient(135deg, #ff7a00, #ff4f00);
      color: white;
      padding: 12px 30px;
      border-radius: 25px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 10px;
    }

    .upload-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(255, 122, 0, 0.4);
    }

    .upload-btn-wrapper input[type=file] {
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }

    .file-name {
      color: #888;
      margin-top: 10px;
      font-size: 14px;
    }

    /* Input Group avec validation */
    .input-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      color: #fff;
      font-weight: 600;
      margin-bottom: 10px;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .form-label i {
      color: #ff7a00;
    }

    .form-input, .form-select {
      width: 100%;
      padding: 15px;
      background: rgba(255, 255, 255, 0.05);
      border: 2px solid rgba(255, 122, 0, 0.3);
      border-radius: 10px;
      color: #fff;
      font-size: 16px;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }

    /* États de validation */
    .form-input.valid,
    .form-select.valid {
      border-color: #4caf50 !important;
      background: rgba(76, 175, 80, 0.05) !important;
    }

    .form-input.invalid,
    .form-select.invalid {
      border-color: #f44336 !important;
      background: rgba(244, 67, 54, 0.05) !important;
    }

    /* Message de validation */
    .validation-message {
      font-size: 11px;
      margin-top: 4px;
      text-align: left;
      padding-left: 5px;
      min-height: 16px;
      transition: all 0.3s ease;
    }

    .validation-message.success {
      color: #4caf50;
    }

    .validation-message.error {
      color: #f44336;
    }

    .validation-message i {
      margin-right: 4px;
      font-size: 10px;
    }

    .form-input:focus, .form-select:focus {
      outline: none;
      border-color: #ff7a00;
      background: rgba(255, 122, 0, 0.05);
    }

    .form-select option {
      background: #1a1a1a;
      color: #fff;
    }

    .btn-submit {
      width: 100%;
      background: linear-gradient(135deg, #ff7a00, #ff4f00);
      color: white;
      padding: 15px 30px;
      border: none;
      border-radius: 25px;
      font-weight: 700;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
      margin-top: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .btn-submit:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(255, 122, 0, 0.4);
    }

    .message-alert {
      position: fixed;
      top: 80px;
      left: 50%;
      transform: translateX(-50%);
      padding: 20px 30px;
      border-radius: 12px;
      margin-bottom: 20px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 12px;
      animation: slideDown 0.3s ease;
      z-index: 9999;
      min-width: 400px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    }

    .message-success {
      background: rgba(20, 20, 20, 0.98);
      border: 2px solid #4caf50;
      color: #4caf50;
    }

    .message-error {
      background: rgba(20, 20, 20, 0.98);
      border: 2px solid #f44336;
      color: #f44336;
    }

    .message-success i,
    .message-error i {
      font-size: 24px;
    }

    @keyframes slideDown {
      from {
        transform: translate(-50%, -20px);
        opacity: 0;
      }
      to {
        transform: translate(-50%, 0);
        opacity: 1;
      }
    }

    /* Custom Confirmation Modal */
    .confirm-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.85);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      animation: fadeIn 0.3s ease;
    }

    .confirm-modal.show {
      display: flex;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .confirm-box {
      background: linear-gradient(135deg, rgba(20, 20, 20, 0.98) 0%, rgba(10, 10, 10, 0.98) 100%);
      border: 2px solid rgba(255, 122, 0, 0.5);
      border-radius: 20px;
      padding: 40px;
      max-width: 500px;
      text-align: center;
      animation: scaleIn 0.3s ease;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7);
    }

    @keyframes scaleIn {
      from { transform: scale(0.8); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }

    .confirm-box h3 {
      color: #fff;
      font-family: 'Orbitron', sans-serif;
      font-size: 24px;
      margin-bottom: 15px;
    }

    .confirm-box p {
      color: #aaa;
      font-size: 16px;
      margin-bottom: 30px;
      line-height: 1.6;
    }

    .confirm-buttons {
      display: flex;
      gap: 15px;
      justify-content: center;
    }

    .btn-confirm-yes, .btn-confirm-no {
      padding: 12px 30px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 700;
      font-size: 14px;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }

    .btn-confirm-yes {
      background: linear-gradient(135deg, #ff7a00, #ff4f00);
      color: white;
    }

    .btn-confirm-yes:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(255, 122, 0, 0.4);
    }

    .btn-confirm-no {
      background: transparent;
      border: 2px solid rgba(255, 255, 255, 0.3);
      color: #fff;
    }

    .btn-confirm-no:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: rgba(255, 255, 255, 0.5);
    }

    @media (max-width: 768px) {
      .edit-profile-card {
        padding: 20px;
        margin: 10px;
      }
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
    <a href="users.php">Users</a>
    <a href="#">Shop</a>
    <a href="tradingb.php">Trade History</a>
    <a href="#">Events</a>
    <a href="#">News</a>
    <a href="#">Support</a>
    <a href="../front/index.php">← Return Homepage</a>
  </div>

  <!-- ===== MAIN ===== -->
  <div class="main">
    <div class="topbar">
      <h1>Edit Profile</h1>
      <div class="admin-dropdown" id="adminDropdown">
        <div class="user admin-user">
          <?php if ($userImage): ?>
          <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Admin Avatar">
          <?php else: ?>
          <i class="fas fa-user-circle"></i>
          <i class="fas fa-user-circle"></i>
          <?php endif; ?>
          <span><?php echo htmlspecialchars($currentUser->getUsername()); ?></span>
          <i class="fas fa-chevron-down"></i>
          <i class="fas fa-chevron-down"></i>
        </div>
        
        <div class="admin-dropdown-menu">
          <a href="admin-profile.php" class="dropdown-item">
            <i class="fas fa-user"></i>
            <span>My Profile</span>
          </a>
          
          <div class="dropdown-divider"></div>
          
          <a href="../front/logout.php" class="dropdown-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
          </a>
        </div>
      </div>
    </div>

    <div class="content">
      <?php if ($message): ?>
      <div class="message-alert message-<?php echo $messageType; ?>">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <span><?php echo $message; ?></span>
      </div>
      <?php endif; ?>

      <div class="edit-profile-card">
        <h2 class="page-title"><i class="fas fa-user-edit"></i> Edit Your Profile</h2>
        <p class="page-subtitle">Update your personal information and profile picture</p>

        <form method="POST" enctype="multipart/form-data" id="editProfileForm" novalidate>
          <div class="profile-image-section">
            <div class="current-avatar" id="avatarPreview">
              <?php if ($userImage): ?>
              <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Current Avatar">
              <?php else: ?>
              <i class="fas fa-user-circle"></i>
              <?php endif; ?>
            </div>
            <div class="upload-btn-wrapper">
              <div class="upload-btn">
                <i class="fas fa-camera"></i> Change Profile Picture
              </div>
              <input type="file" name="profile_image" id="profileImage" accept="image/*">
            </div>
            <div class="file-name" id="fileName">No file chosen</div>
          </div>

          <div class="input-group">
            <label class="form-label">
              <i class="fas fa-user"></i> Username
            </label>
            <input type="text" id="username" name="username" class="form-input" 
                   value="<?php echo htmlspecialchars($currentUser->getUsername()); ?>">
            <div class="validation-message" id="username-message"></div>
          </div>

          <div class="input-group">
            <label class="form-label">
              <i class="fas fa-envelope"></i> Email
            </label>
            <input type="text" id="email" name="email" class="form-input" 
                   value="<?php echo htmlspecialchars($currentUser->getEmail()); ?>">
            <div class="validation-message" id="email-message"></div>
          </div>

          <div class="input-group">
            <label class="form-label">
              <i class="fas fa-calendar"></i> Date of Birth
            </label>
            <input type="date" id="dob" name="dob" class="form-input" 
                   value="<?php echo htmlspecialchars($currentUser->getDob()); ?>">
            <div class="validation-message" id="dob-message"></div>
          </div>

          <div class="input-group">
            <label class="form-label">
              <i class="fas fa-venus-mars"></i> Gender
            </label>
            <select id="gender" name="gender" class="form-select">
              <option value="">Select Gender</option>
              <option value="Male" <?php echo $currentUser->getGender() === 'Male' ? 'selected' : ''; ?>>Male</option>
              <option value="Female" <?php echo $currentUser->getGender() === 'Female' ? 'selected' : ''; ?>>Female</option>
              <option value="Other" <?php echo $currentUser->getGender() === 'Other' ? 'selected' : ''; ?>>Other</option>
            </select>
            <div class="validation-message" id="gender-message"></div>
          </div>

          <button type="submit" class="btn-submit">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </form>
      </div>
    </div>

    <footer class="site-footer">
      © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
    </footer>
  </div>

  <!-- Custom Confirmation Modal -->
  <div class="confirm-modal" id="confirmModal">
    <div class="confirm-box">
      <h3><i class="fas fa-question-circle" style="color: #ff7a00;"></i> Confirm Changes</h3>
      <p>Are you sure you want to update your profile information?</p>
      <div class="confirm-buttons">
        <button class="btn-confirm-yes" id="confirmYes">
          <i class="fas fa-check"></i> Yes, Update
        </button>
        <button class="btn-confirm-no" id="confirmNo">
          <i class="fas fa-times"></i> Cancel
        </button>
      </div>
    </div>
  </div>

  <!-- ===== PAGE TRANSITION OVERLAY ===== -->
  <div class="transition-screen"></div>

  <script>
    // ========== ADMIN DROPDOWN TOGGLE ==========
    document.addEventListener('DOMContentLoaded', function() {
      const adminDropdown = document.getElementById('adminDropdown');
      
      if (adminDropdown) {
        const adminUser = adminDropdown.querySelector('.admin-user');
        
        adminUser.addEventListener('click', function(e) {
          e.stopPropagation();
          adminDropdown.classList.toggle('active');
        });
        
        document.addEventListener('click', function(e) {
          if (!adminDropdown.contains(e.target)) {
            adminDropdown.classList.remove('active');
          }
        });
        
        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape') {
            adminDropdown.classList.remove('active');
          }
        });
      }
    });

    // ========== VALIDATION FUNCTIONS ==========
    
    // Helper function pour afficher validation
    function showValidation(inputId, messageId, isValid, message) {
      const input = document.getElementById(inputId);
      const messageEl = document.getElementById(messageId);
      
      if (!input || !messageEl) return;
      
      input.classList.remove('valid', 'invalid');
      messageEl.classList.remove('success', 'error');
      
      if (isValid) {
        input.classList.add('valid');
        messageEl.classList.add('success');
        messageEl.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
      } else if (message) {
        input.classList.add('invalid');
        messageEl.classList.add('error');
        messageEl.innerHTML = '<i class="fas fa-times-circle"></i> ' + message;
      } else {
        messageEl.innerHTML = '';
      }
    }

    // Validation Username
    function validateUsername() {
      const username = document.getElementById('username').value.trim();
      
      if (username.length === 0) {
        showValidation('username', 'username-message', false, '');
        return false;
      } else if (username.length < 3) {
        showValidation('username', 'username-message', false, 'Min 3 characters');
        return false;
      } else {
        showValidation('username', 'username-message', true, 'Valid');
        return true;
      }
    }

    // Validation Email
    function validateEmail() {
      const email = document.getElementById('email').value.trim();
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      
      if (email.length === 0) {
        showValidation('email', 'email-message', false, '');
        return false;
      } else if (!emailRegex.test(email)) {
        showValidation('email', 'email-message', false, 'Invalid email');
        return false;
      } else {
        showValidation('email', 'email-message', true, 'Valid');
        return true;
      }
    }

    // Validation Date of Birth
    function validateDOB() {
      const dob = document.getElementById('dob').value;
      
      if (!dob) {
        showValidation('dob', 'dob-message', false, 'Required');
        return false;
      }
      
      // Vérifier que la date n'est pas dans le futur
      const dobDate = new Date(dob);
      const today = new Date();
      today.setHours(0, 0, 0, 0); // Reset time to compare only dates
      
      if (dobDate > today) {
        showValidation('dob', 'dob-message', false, 'Cannot be in the future');
        return false;
      } else {
        showValidation('dob', 'dob-message', true, 'Valid');
        return true;
      }
    }

    // Validation Gender
    function validateGender() {
      const gender = document.getElementById('gender').value;
      
      if (!gender) {
        showValidation('gender', 'gender-message', false, 'Required');
        return false;
      } else {
        showValidation('gender', 'gender-message', true, 'Valid');
        return true;
      }
    }

    // ========== EVENT LISTENERS - REAL-TIME VALIDATION ==========
    document.getElementById('username').addEventListener('input', validateUsername);
    document.getElementById('username').addEventListener('blur', validateUsername);

    document.getElementById('email').addEventListener('input', validateEmail);
    document.getElementById('email').addEventListener('blur', validateEmail);

    document.getElementById('dob').addEventListener('change', validateDOB);
    document.getElementById('dob').addEventListener('blur', validateDOB);

    document.getElementById('gender').addEventListener('change', validateGender);
    document.getElementById('gender').addEventListener('blur', validateGender);

    // ========== IMAGE PREVIEW ==========
    const profileImage = document.getElementById('profileImage');
    const avatarPreview = document.getElementById('avatarPreview');
    const fileName = document.getElementById('fileName');

    profileImage.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        fileName.textContent = file.name;
        
        const reader = new FileReader();
        reader.onload = function(event) {
          avatarPreview.innerHTML = '<img src="' + event.target.result + '" alt="Preview">';
        };
        reader.readAsDataURL(file);
      } else {
        fileName.textContent = 'No file chosen';
      }
    });

    // ========== FORM SUBMISSION WITH VALIDATION ==========
    const form = document.getElementById('editProfileForm');
    const confirmModal = document.getElementById('confirmModal');
    const confirmYes = document.getElementById('confirmYes');
    const confirmNo = document.getElementById('confirmNo');
    let formSubmitPending = false;

    form.addEventListener('submit', function(e) {
      if (!formSubmitPending) {
        e.preventDefault();
        
        // Valider tous les champs
        const isUsernameValid = validateUsername();
        const isEmailValid = validateEmail();
        const isDOBValid = validateDOB();
        const isGenderValid = validateGender();
        
        // Si tout est valide, montrer modal
        if (isUsernameValid && isEmailValid && isDOBValid && isGenderValid) {
          confirmModal.classList.add('show');
        }
      }
    });

    // Confirm changes
    confirmYes.addEventListener('click', function() {
      formSubmitPending = true;
      confirmModal.classList.remove('show');
      form.submit();
    });

    // Cancel changes
    confirmNo.addEventListener('click', function() {
      confirmModal.classList.remove('show');
    });

    confirmModal.addEventListener('click', function(e) {
      if (e.target === confirmModal) {
        confirmModal.classList.remove('show');
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && confirmModal.classList.contains('show')) {
        confirmModal.classList.remove('show');
      }
    });

    // ========== PAGE TRANSITIONS ==========
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

    // Auto-hide message alert
    setTimeout(function() {
      const message = document.querySelector('.message-alert');
      if (message) {
        message.style.opacity = '0';
        setTimeout(() => message.remove(), 300);
      }
    }, 5000);

    // Auto-redirect after successful update
    <?php if (isset($shouldRedirect) && $shouldRedirect): ?>
    setTimeout(function() {
      window.location.href = 'admin-profile.php';
    }, 3000);
    }, 3000);
    <?php endif; ?>
  </script>
  
</body>
</html>