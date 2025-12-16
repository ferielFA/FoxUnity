<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../../config.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category = $_POST['category'];
    $brand = $_POST['brand'];

    // Handle Image Upload
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = 'view/front/uploads/' . $fileName; // Store relative path for DB
            // Actually, since shop.php is in view/front, and if we store it as 'view/front/uploads/', 
            // from shop.php (view/front/shop.php) we would access it via 'uploads/filename'.
            // Let's store just the filename or relative path from project root?
            // Existing DB has 'uploads/profiles/...' for users.
            // Let's decide on a convention. 
            // If I store 'uploads/product.jpg' in DB.
            // shop.php is in view/front/. src="uploads/product.jpg" works if uploads is in view/front/uploads.
            $imagePath = 'uploads/' . $fileName;
        } else {
            $message = "Error uploading image.";
        }
    }

    if (!$message) {
        try {
            $sql = "INSERT INTO produit (name, description, price, stock, category, brand, image) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $description, $price, $stock, $category, $brand, $imagePath]);
            $message = "Product added successfully!";
        } catch (PDOException $e) {
            $message = "Error adding product: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - FoxUnity</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #0b0b2b; color: white; }
        form { max-width: 500px; margin: 0 auto; background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; }
        input, textarea, select { width: 100%; margin-bottom: 10px; padding: 10px; border-radius: 5px; border: none; box-sizing: border-box; }
        button { background: #ff7a00; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; width: 100%; font-weight: bold; }
        button:hover { background: #e06b00; }
        .message { text-align: center; margin-bottom: 20px; color: #4caf50; }
        .error { color: #f44336; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
    </style>
</head>
<body>

    <h1 style="text-align: center; color: #ff7a00;">Add New Product</h1>

    <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <label>Name</label>
        <input type="text" name="name" required>

        <label>Description</label>
        <textarea name="description" rows="4"></textarea>

        <label>Price</label>
        <input type="number" step="0.01" name="price" required>

        <label>Stock</label>
        <input type="number" name="stock" value="10">

        <label>Category</label>
        <select name="category">
            <option value="Peripherals">Peripherals</option>
            <option value="Audio">Audio</option>
            <option value="Hardware">Hardware</option>
            <option value="Merch">Merch</option>
        </select>

        <label>Brand</label>
        <input type="text" name="brand">

        <label>Image</label>
        <input type="file" name="image" accept="image/*">

        <button type="submit">Add Product</button>
    </form>

</body>
</html>
