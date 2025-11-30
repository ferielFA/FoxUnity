<?php
require_once __DIR__ . '/../../model/config.php';
require_once __DIR__ . '/../../model/SkinModel.php';

header('Content-Type: application/json');

if (!isset($_POST['skin_ids'])) {
    echo json_encode(['success' => false, 'error' => 'No skin IDs provided']);
    exit;
}

$skinIds = json_decode($_POST['skin_ids'], true);

if (!is_array($skinIds)) {
    echo json_encode(['success' => false, 'error' => 'Invalid skin IDs']);
    exit;
}

$skinModel = new SkinModel();
$prices = [];

foreach ($skinIds as $id) {
    $skin = $skinModel->getSkinById((int)$id);
    if ($skin) {
        $prices[$id] = [
            'id' => $skin['skin_id'],
            'name' => $skin['name'],
            'price' => (float)$skin['price'],
            'image' => $skin['image'] ? '../' . ltrim($skin['image'], '/\\') : '../images/skin1.png'
        ];
    }
}

echo json_encode(['success' => true, 'prices' => $prices]);
