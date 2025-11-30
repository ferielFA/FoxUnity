<?php
/**
 * Admin Trading Controller
 * Handles admin trading dashboard requests and business logic
 */

require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/TradeHistoryModel.php';

class AdminTradingController {
    private $tradeHistoryModel;
    
    public function __construct() {
        $this->tradeHistoryModel = new TradeHistoryModel();
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
        
        try {
            $tradeHistory = $this->tradeHistoryModel->getAllTradeHistory();
            $stats = $this->tradeHistoryModel->getStatistics();
        } catch (Exception $e) {
            error_log("AdminTradingController::getViewData error: " . $e->getMessage());
            $error = "Failed to load trade history data.";
        }
        
        return [
            'tradeHistory' => $tradeHistory,
            'stats' => $stats,
            'error' => $error
        ];
    }
}


