<?php
/**
 * User Dropdown Menu Component
 * 
 * This file contains the reusable user dropdown menu that can be included in any page.
 * It requires $currentUserObj to be set before including this file.
 * 
 * Usage:
 * 1. Make sure you have $currentUserObj set (from UserController::getCurrentUser())
 * 2. Include this file in your header: include __DIR__ . '/includes/user_dropdown.php';
 */

// Determine if user is logged in
$isLoggedIn = isset($currentUserObj) && $currentUserObj !== null;
?>

<div class="user-dropdown" id="userDropdown">
    <div class="username-display">
        <?php if ($isLoggedIn && $currentUserObj): ?>
            <?php if (isset($userImage) && $userImage): ?>
                <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Profile">
            <?php else: ?>
                <i class="fas fa-user-circle"></i>
            <?php endif; ?>
            <span><?php echo htmlspecialchars($currentUserObj->getUsername()); ?></span>
        <?php else: ?>
            <i class="fas fa-user-circle"></i>
            <span>Guest</span>
        <?php endif; ?>
        <i class="fas fa-chevron-down"></i>
    </div>
    
    <div class="dropdown-menu">
        <?php if ($isLoggedIn && $currentUserObj): ?>
        <a href="profile.php" class="dropdown-item">
            <i class="fas fa-user"></i>
            <span>My Profile</span>
        </a>
        
        <a href="tradehis.php" class="dropdown-item">
            <i class="fas fa-history"></i>
            <span>History</span>
        </a>
        
        <?php 
        $userRole = strtolower($currentUserObj->getRole());
        if ($userRole === 'admin' || $userRole === 'superadmin'): 
        ?>
        <a href="../back/dashboard.php" class="dropdown-item">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <?php endif; ?>
        
        <div class="dropdown-divider"></div>
        
        <a href="Logout.php" class="dropdown-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
        <?php else: ?>
        <a href="Login.php" class="dropdown-item">
            <i class="fas fa-sign-in-alt"></i>
            <span>Login/Register</span>
        </a>
        <?php endif; ?>
    </div>
</div>
