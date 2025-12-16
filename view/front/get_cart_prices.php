<?php
require_once __DIR__ . '/../../model/config.php';
require_once __DIR__ . '/../../model/SkinModel.php';

require_once __DIR__ . '/../../controller/ProductController.php';

header('Content-Type: application/json');

$response = ['success' => false, 'prices' => [], 'error' => ''];

$skinModel = new SkinModel();
$productController = new ProductController();

// Handle new format (with types)
if (isset($_POST['cart_items'])) {
    $items = json_decode($_POST['cart_items'], true);

    if (is_array($items)) {
        foreach ($items as $item) {
            $id = (int) $item['id'];
            $type = isset($item['type']) ? $item['type'] : 'skin'; // Default to skin

            if ($type === 'product') {
                $product = $productController->getProductById($id);
                if ($product) {
                    $response['prices'][$id . '_product'] = [
                        'id' => $product->getId(),
                        'name' => $product->getName(),
                        'price' => (float) $product->getPrice(),
                        'image' => $product->getImage() ? '../' . ltrim($product->getImage(), '/\\') : '../images/placeholder-product.png',
                        'type' => 'product'
                    ];
                }
            } else {
                // Skin
                $skin = $skinModel->getSkinById($id);
                if ($skin) {
                    // Use composite key or just ID? JS needs to know which is which.
                    // But panier.php uses the ID to look up priceData.
                    // If we use 'id_type' as key, we need to update panier.php to look it up that way.
                    $response['prices'][$id] = [ // Keep old key format for skins to be safe? Or change all?
                        'id' => $skin['skin_id'],
                        'name' => $skin['name'],
                        'price' => (float) $skin['price'],
                        'image' => $skin['image'] ? '../' . ltrim($skin['image'], '/\\') : '../images/skin1.png',
                        'type' => 'skin'
                    ];
                }
            }
        }
        $response['success'] = true;
    } else {
        $response['error'] = 'Invalid cart items format';
    }

} elseif (isset($_POST['skin_ids'])) {
    // Legacy support for skins only
    $skinIds = json_decode($_POST['skin_ids'], true);

    if (is_array($skinIds)) {
        foreach ($skinIds as $id) {
            $skin = $skinModel->getSkinById((int) $id);
            if ($skin) {
                $response['prices'][$id] = [
                    'id' => $skin['skin_id'],
                    'name' => $skin['name'],
                    'price' => (float) $skin['price'],
                    'image' => $skin['image'] ? '../' . ltrim($skin['image'], '/\\') : '../images/skin1.png',
                    'type' => 'skin'
                ];
            }
        }
        $response['success'] = true;
    } else {
        $response['error'] = 'Invalid skin IDs';
    }
} else {
    $response['error'] = 'No cart data provided';
}

echo json_encode($response);
