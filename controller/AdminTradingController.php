<?php
/**
 * Admin Trading Controller
 * Handles admin trading dashboard requests and business logic
 */

require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/TradeHistoryController.php';

class AdminTradingController {
    private $tradeHistoryModel;
    
    public function __construct() {
        $this->tradeHistoryModel = new TradeHistoryController();
    }
    
    /**
     * Get all view data for admin dashboard
     * 
     * @return array
     */
    public function getViewData(): array {
        $tradeHistory = [];
        $stats = [
            'total_trades' => 0,
            'total_users' => 0,
            'total_skins' => 0,
            'total_value' => 0
        ];
        $error = null;
        
        // Filter params
        $filters = [
            'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
            'action' => isset($_GET['action']) ? trim($_GET['action']) : 'all',
            'game' => isset($_GET['game']) ? trim($_GET['game']) : 'all'
        ];

        // Pagination logic
        $limit = 10; // Number of items per page
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $limit;
        $totalRecords = 0;
        $totalPages = 0;

        try {
            $totalRecords = $this->tradeHistoryModel->countAllTradeHistory($filters);
            $totalPages = ceil($totalRecords / $limit);
            
            // Ensure page is within valid range (unless no records)
            if ($page > $totalPages && $totalPages > 0) {
                $page = $totalPages;
                $offset = ($page - 1) * $limit;
            } elseif ($totalPages == 0) {
                $page = 1;
                $offset = 0;
            }
            
            $tradeHistory = $this->tradeHistoryModel->getPaginatedTradeHistory($limit, $offset, $filters);
            $stats = $this->tradeHistoryModel->getStatistics();
        } catch (Exception $e) {
            error_log("AdminTradingController::getViewData error: " . $e->getMessage());
            $error = "Failed to load trade history data.";
        }
        
        return [
            'tradeHistory' => $tradeHistory,
            'stats' => $stats,
            'error' => $error,
            'filters' => $filters,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords,
                'limit' => $limit
            ]
        ];
    }
}


