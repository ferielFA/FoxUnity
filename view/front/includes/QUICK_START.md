# Quick Start Guide - User Dropdown Component

## For New Pages

When creating a new page that needs the user dropdown menu, follow these steps:

### Step 1: PHP Setup (at the top of your file)

```php
<?php
require_once __DIR__ . '/../../controller/UserController.php';

// Check if user is logged in (optional, depends on your page)
if (!UserController::isLoggedIn()) {
    header('Location: Login.php');
    exit();
}

// Get current user object
$currentUserObj = UserController::getCurrentUser();

// Get user image (optional)
$userImage = null;
if ($currentUserObj && $currentUserObj->getImage()) {
    $userImage = '../../view/' . $currentUserObj->getImage();
}
?>
```

### Step 2: Include CSS in `<head>`

```html
<link rel="stylesheet" href="includes/user_dropdown.css">
```

### Step 3: Include the Dropdown in Your Header

```html
<div class="header-right">
    <?php 
    // Set $currentUserObj before including
    include __DIR__ . '/includes/user_dropdown.php'; 
    ?>
    
    <!-- Other header items like cart -->
    <a href="panier.php" class="cart-icon">
        <i class="fas fa-shopping-cart"></i> Cart
        <span class="cart-count">0</span>
    </a>
</div>
```

### Step 4: Include JavaScript Before `</body>`

```html
<script src="includes/user_dropdown.js"></script>
```

## That's It!

Your new page will now have:
- ✅ User dropdown with profile picture
- ✅ My Profile link
- ✅ **History link** (automatically included!)
- ✅ Dashboard link (for admins only)
- ✅ Logout link
- ✅ Consistent styling across all pages

## Example Pages

See these files for complete examples:
- `trading.php`
- `tradehis.php`
- `index.php`
- `profile.php`

## Need Help?

Check the full README.md in the includes directory for more details.
