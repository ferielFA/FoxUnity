<?php
/**
 * Product Controller
 * Handles AJAX requests for product CRUD operations
 */

// Start session and load dependencies
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/ProductModel.php';

// Set JSON response header
header('Content-Type: application/json');

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$productModel = new ProductModel();
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    switch ($action) {
        case 'getAll':
            // Get all products
            $products = $productModel->getAllProducts();
            $response['success'] = true;
            $response['data'] = $products;
            break;
            
        case 'getOne':
            // Get single product
            $id = $_GET['id'] ?? 0;
            if ($id) {
                $product = $productModel->getProductById($id);
                if ($product) {
                    $response['success'] = true;
                    $response['data'] = $product;
                } else {
                    $response['message'] = 'Product not found';
                }
            } else {
                $response['message'] = 'Product ID required';
            }
            break;
            
        case 'create':
            // Create new product
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? 0;
            $image = $_POST['image'] ?? '';
            
            if (empty($name) || empty($description) || $price <= 0) {
                $response['message'] = 'All fields are required and price must be greater than 0';
            } else {
                $result = $productModel->createProduct($name, $description, $price, $image);
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Product created successfully';
                    $response['data'] = $productModel->getAllProducts();
                } else {
                    $response['message'] = 'Failed to create product';
                }
            }
            break;
            
        case 'update':
            // Update existing product
            $id = $_POST['id'] ?? 0;
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? 0;
            $image = $_POST['image'] ?? '';
            
            if (!$id) {
                $response['message'] = 'Product ID required';
            } elseif (empty($name) || empty($description) || $price <= 0) {
                $response['message'] = 'All fields are required and price must be greater than 0';
            } else {
                $result = $productModel->updateProduct($id, $name, $description, $price, $image);
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Product updated successfully';
                    $response['data'] = $productModel->getAllProducts();
                } else {
                    $response['message'] = 'Failed to update product';
                }
            }
            break;
            
        case 'delete':
            // Delete product
            $id = $_POST['id'] ?? 0;
            
            if (!$id) {
                $response['message'] = 'Product ID required';
            } else {
                $result = $productModel->deleteProduct($id);
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Product deleted successfully';
                    $response['data'] = $productModel->getAllProducts();
                } else {
                    $response['message'] = 'Failed to delete product';
                }
            }
            break;
            
        default:
            $response['message'] = 'Invalid action';
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
    error_log("ProductController error: " . $e->getMessage());
}

echo json_encode($response);
?>
