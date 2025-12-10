# User Dropdown Component

This directory contains reusable components for the user dropdown menu that appears in the header of all pages.

## Files

- **user_dropdown.php** - The HTML/PHP structure for the dropdown menu
- **user_dropdown.css** - The CSS styles for the dropdown menu
- **user_dropdown.js** - The JavaScript functionality for the dropdown menu

## How to Use

### 1. In your PHP page (before the header):

```php
<?php
// Get current user object
require_once __DIR__ . '/../../controller/UserController.php';
$currentUserObj = UserController::getCurrentUser();

// Get user image (optional)
$userImage = null;
if ($currentUserObj && $currentUserObj->getImage()) {
    $userImage = '../../view/' . $currentUserObj->getImage();
}
?>
```

### 2. In your HTML `<head>` section:

```html
<link rel="stylesheet" href="includes/user_dropdown.css">
```

### 3. In your header (where you want the dropdown to appear):

```php
<div class="header-right">
    <?php include __DIR__ . '/includes/user_dropdown.php'; ?>
    
    <!-- Other header elements like cart icon -->
    <a href="panier.php" class="cart-icon">
        <i class="fas fa-shopping-cart"></i> Cart
        <span class="cart-count">0</span>
    </a>
</div>
```

### 4. At the bottom of your page (before `</body>`):

```html
<script src="includes/user_dropdown.js"></script>
```

## Menu Items

The dropdown menu automatically includes:

- **My Profile** - Links to profile.php
- **History** - Links to tradehis.php (trade history)
- **Dashboard** - Only shown for admin/superadmin users
- **Logout** - Links to Logout.php

If the user is not logged in, it shows:
- **Login/Register** - Links to Login.php

## Customization

### Adding New Menu Items

Edit `user_dropdown.php` and add your new item in the appropriate section:

```php
<a href="your-page.php" class="dropdown-item">
    <i class="fas fa-your-icon"></i>
    <span>Your Menu Item</span>
</a>
```

### Styling

All styles are in `user_dropdown.css`. You can override them in your page's custom styles if needed.

### JavaScript

The dropdown functionality is handled in `user_dropdown.js`. It includes:
- Click to toggle
- Click outside to close
- Press Escape to close

## Example Implementation

See `trading.php`, `tradehis.php`, or `index.php` for complete examples of how to implement this component.

## Benefits

✅ **Consistency** - All pages use the same dropdown menu
✅ **Easy Updates** - Update once, applies everywhere
✅ **Future-Proof** - New pages automatically get the History link
✅ **Maintainable** - Centralized code is easier to maintain
