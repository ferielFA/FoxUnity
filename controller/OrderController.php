<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

class OrderController {
    private $pdo;

    public function __construct() {
        $this->pdo = getDB();
    }

    public function handleRequest() {
        $action = $_GET['action'] ?? 'getAll';

        switch ($action) {
            case 'getAll':
                $this->getAllOrders();
                break;
            case 'getDetails':
                $this->getOrderDetails();
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
    }

    private function getAllOrders() {
        try {
            // Fetch all orders, ordered by newest first
            $stmt = $this->pdo->prepare("
                SELECT id, customer_name, total, status, created_at 
                FROM commande 
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $orders]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    private function getOrderDetails() {
        $orderId = $_GET['id'] ?? null;

        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Order ID is required']);
            return;
        }

        try {
            // Get order info
            $stmt = $this->pdo->prepare("SELECT * FROM commande WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                return;
            }

            // Get items
            $stmtItems = $this->pdo->prepare("
                SELECT product_name, quantity, price 
                FROM commande_items 
                WHERE commande_id = ?
            ");
            $stmtItems->execute([$orderId]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true, 
                'data' => [
                    'order' => $order,
                    'items' => $items
                ]
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
}

// Instantiate and handle
$controller = new OrderController();
$controller->handleRequest();
