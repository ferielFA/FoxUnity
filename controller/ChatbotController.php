<?php
// Use the config file which includes Database class
require_once __DIR__ . '/../model/config.php';

class ChatbotController {
    // ⚠️ IMPORTANT: Replace 'YOUR_OPENROUTER_API_KEY' with your actual API key from https://openrouter.ai/keys
    private static $apiKey = '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';
    
    // Meta's Llama 3.3 70B Instruct - Excellent choice! Powerful and affordable
    private static $model = 'meta-llama/llama-3.3-70b-instruct';
    
    private static $apiUrl = 'https://openrouter.ai/api/v1/chat/completions';
    
    /**
     * Get chatbot response with context awareness
     */
    public static function getChatResponse($userMessage, $conversationHistory = []) {
        // Check if API key is configured
        if (self::$apiKey === 'YOUR_OPENROUTER_API_KEY') {
            return [
                'success' => false,
                'error' => '⚠️ API key not configured. Please update the API key in ChatbotController.php (line 6). Get your key from https://openrouter.ai/keys'
            ];
        }
        
        // Check if message is related to website content
        if (!self::isRelevantQuery($userMessage)) {
            return [
                'success' => true,
                'response' => "I apologize, but I'm specifically designed to assist with FoxUnity platform-related questions only. I can help you with:\n\n• Shopping for gaming products\n• Trading skins and game items\n• Event registrations and information\n• News articles and gaming content\n• Support and reclamation processes\n• Charity donation information\n• Account and platform features\n\nPlease ask me anything related to our gaming charity platform!"
            ];
        }
        
        // Get relevant database context
        $context = self::getDatabaseContext($userMessage);
        
        // Build system prompt with website context
        $systemPrompt = self::buildSystemPrompt($context);
        
        // Prepare messages for API
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];
        
        // Add conversation history
        foreach ($conversationHistory as $msg) {
            $messages[] = $msg;
        }
        
        // Add current user message
        $messages[] = ['role' => 'user', 'content' => $userMessage];
        
        // Call OpenRouter API
        return self::callOpenRouterAPI($messages);
    }
    
    /**
     * Check if query is relevant to website content
     */
    private static function isRelevantQuery($message) {
        $message = strtolower($message);
        
        // Keywords related to the website
        $relevantKeywords = [
            // General platform
            'foxunity', 'gaming', 'charity', 'donate', 'donation', 'pool',
            
            // Shop
            'shop', 'buy', 'purchase', 'product', 'keyboard', 'mouse', 'headset', 
            'gaming gear', 'equipment', 'peripheral', 'price', 'cart', 'checkout',
            'amd', 'intel', 'nvidia', 'ryzen', 'processor', 'cpu', 'gpu', 'ram',
            'razer', 'logitech', 'corsair', 'steelseries',
            
            // Trading
            'trade', 'trading', 'skin', 'skins', 'sell', 'seller', 'negotiate',
            'trade history', 'discussion', 'analyze', 'analysis',
            
            // Events
            'event', 'tournament', 'registration', 'ticket', 'qr code', 'participate',
            'feedback', 'join', 'compete',
            
            // News
            'news', 'article', 'read', 'subscribe', 'category', 'comment',
            'reading history', 'filter',
            
            // Support
            'support', 'help', 'reclamation', 'claim', 'problem', 'issue',
            'evaluation', 'evaluate', 'complaint', 'feedback',
            
            // Account
            'account', 'profile', 'login', 'register', 'password', 'username',
            
            // General queries
            'how', 'what', 'where', 'when', 'why', 'can i', 'do you', 'tell me',
            'is there', 'do you have', 'available', 'stock', 'exist'
        ];
        
        foreach ($relevantKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        
        // Check if it's a greeting or general question about the platform
        $greetings = ['hi', 'hello', 'hey', 'greetings', 'good morning', 'good afternoon', 'good evening'];
        foreach ($greetings as $greeting) {
            if (strpos($message, $greeting) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Search for specific product by name
     */
    private static function searchProductByName($message) {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // Extract potential product keywords from message
            $words = explode(' ', $message);
            $searchResults = [];
            
            // Search for products matching any significant words (3+ characters)
            foreach ($words as $word) {
                if (strlen($word) >= 3) {
                    $stmt = $pdo->prepare("
                        SELECT id, name, price, stock, description, category 
                        FROM product 
                        WHERE LOWER(name) LIKE LOWER(?) 
                        OR LOWER(description) LIKE LOWER(?)
                        OR LOWER(category) LIKE LOWER(?)
                        LIMIT 10
                    ");
                    $searchTerm = "%{$word}%";
                    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($results as $result) {
                        $searchResults[$result['id']] = $result;
                    }
                }
            }
            
            return array_values($searchResults);
        } catch (PDOException $e) {
            error_log("Product search error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get relevant database context based on user query
     */
    private static function getDatabaseContext($message) {
        $context = [];
        $message = strtolower($message);
        
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // ALWAYS search for specific products mentioned in the message
            $productSearchResults = self::searchProductByName($message);
            if (!empty($productSearchResults)) {
                $context['searched_products'] = $productSearchResults;
                $context['found_specific_products'] = count($productSearchResults);
            }
            
            // Check for product queries
            if (self::containsKeywords($message, ['product', 'shop', 'buy', 'keyboard', 'mouse', 'headset', 'gear', 'processor', 'cpu', 'gpu', 'ryzen', 'amd', 'intel'])) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM product WHERE stock > 0");
                $productCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                $context['total_products'] = $productCount;
                
                // If no specific products found, get general samples
                if (empty($productSearchResults)) {
                    $stmt = $pdo->query("SELECT id, name, price, stock, description, category FROM product WHERE stock > 0 ORDER BY RAND() LIMIT 5");
                    $context['product_samples'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            
            // Check for trading queries
            if (self::containsKeywords($message, ['trade', 'trading', 'skin', 'sell', 'negotiate'])) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM trade WHERE status = 'active'");
                $tradeCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                $context['active_trades'] = $tradeCount;
                
                // Get recent trades
                $stmt = $pdo->query("SELECT id, title, game, price, status FROM trade WHERE status = 'active' LIMIT 5");
                $context['trade_samples'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Check for event queries
            if (self::containsKeywords($message, ['event', 'tournament', 'registration', 'participate', 'compete'])) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM event WHERE event_date >= CURDATE()");
                $eventCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                $context['upcoming_events'] = $eventCount;
                
                // Get upcoming events
                $stmt = $pdo->query("SELECT id, title, event_date, location, max_participants FROM event WHERE event_date >= CURDATE() ORDER BY event_date LIMIT 5");
                $context['event_samples'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Check for news queries
            if (self::containsKeywords($message, ['news', 'article', 'read', 'subscribe', 'category'])) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM article WHERE datePublication <= CURDATE()");
                $newsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                $context['published_news'] = $newsCount;
                
                // Get recent news
                $stmt = $pdo->query("SELECT idArticle, titre, datePublication FROM article WHERE datePublication <= CURDATE() ORDER BY datePublication DESC LIMIT 5");
                $context['news_samples'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Get ALL product categories
            $stmt = $pdo->query("SELECT DISTINCT category FROM product WHERE category IS NOT NULL AND category != ''");
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($categories)) {
                $context['product_categories'] = $categories;
            }
            
        } catch (PDOException $e) {
            error_log("Database error in chatbot: " . $e->getMessage());
        }
        
        return $context;
    }
    
    /**
     * Check if message contains specific keywords
     */
    private static function containsKeywords($message, $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Build comprehensive system prompt with context
     */
    private static function buildSystemPrompt($context) {
        $prompt = "You are FoxUnity AI Assistant, a helpful and knowledgeable chatbot for the FoxUnity gaming charity platform.\n\n";
        
        $prompt .= "PLATFORM OVERVIEW:\n";
        $prompt .= "FoxUnity is a gaming platform where every action contributes 10% to verified charitable organizations. We unite gamers worldwide to make a positive impact.\n\n";
        
        $prompt .= "FEATURES:\n";
        $prompt .= "1. SHOP: Purchase gaming products (keyboards, mice, headsets, gaming gear). 10% of every purchase goes to charity.\n";
        $prompt .= "2. TRADING: Buy, sell, or trade game skins with negotiable prices. View trade history and discussions. AI analysis available for trade recommendations.\n";
        $prompt .= "3. EVENTS: Gaming tournaments, challenges, and community events with QR-code ticketing system and participant feedback.\n";
        $prompt .= "4. NEWS: Gaming articles, platform updates, and community highlights. Filter by category, subscribe, read and comment.\n";
        $prompt .= "5. SUPPORT: 24/7 assistance for any platform questions or issues.\n";
        $prompt .= "6. RECLAMATION: Submit and track complaints or issues.\n";
        $prompt .= "7. PUBLIC EVALUATIONS: Anonymously or publicly evaluate reclamations from the community.\n";
        $prompt .= "8. ABOUT US: Information about our charity pool and impact.\n\n";
        
        // Add real-time database context
        if (!empty($context)) {
            $prompt .= "CURRENT PLATFORM DATA (REAL-TIME FROM DATABASE):\n";
            
            // PRIORITY: Show specific searched products FIRST
            if (isset($context['searched_products']) && !empty($context['searched_products'])) {
                $prompt .= "\n🔍 PRODUCTS MATCHING USER QUERY:\n";
                foreach ($context['searched_products'] as $product) {
                    $stockStatus = $product['stock'] > 0 ? "IN STOCK ({$product['stock']} available)" : "OUT OF STOCK";
                    $prompt .= "✓ YES - {$product['name']}\n";
                    $prompt .= "  - Price: \${$product['price']}\n";
                    $prompt .= "  - Stock: {$stockStatus}\n";
                    $prompt .= "  - Category: {$product['category']}\n";
                    if (!empty($product['description'])) {
                        $prompt .= "  - Description: " . substr($product['description'], 0, 150) . "\n";
                    }
                    $prompt .= "\n";
                }
                $prompt .= "IMPORTANT: These products EXIST in our shop. Confirm their availability to the user!\n\n";
            }
            
            if (isset($context['total_products'])) {
                $prompt .= "- Total available products in shop: {$context['total_products']}\n";
            }
            
            if (isset($context['product_categories']) && !empty($context['product_categories'])) {
                $prompt .= "- Product categories: " . implode(', ', $context['product_categories']) . "\n";
            }
            
            if (isset($context['product_samples']) && !empty($context['product_samples'])) {
                $prompt .= "\nOther sample products:\n";
                foreach ($context['product_samples'] as $product) {
                    $prompt .= "  * {$product['name']} - \${$product['price']} ({$product['stock']} in stock)\n";
                }
            }
            
            if (isset($context['active_trades'])) {
                $prompt .= "\n- Active trades: {$context['active_trades']}\n";
                if (!empty($context['trade_samples'])) {
                    $prompt .= "Sample trades:\n";
                    foreach ($context['trade_samples'] as $trade) {
                        $prompt .= "  * {$trade['title']} ({$trade['game']}) - \${$trade['price']}\n";
                    }
                }
            }
            
            if (isset($context['upcoming_events'])) {
                $prompt .= "\n- Upcoming events: {$context['upcoming_events']}\n";
                if (!empty($context['event_samples'])) {
                    $prompt .= "Sample events:\n";
                    foreach ($context['event_samples'] as $event) {
                        $prompt .= "  * {$event['title']} on {$event['event_date']} at {$event['location']}\n";
                    }
                }
            }
            
            if (isset($context['published_news'])) {
                $prompt .= "\n- Published news articles: {$context['published_news']}\n";
                if (!empty($context['news_samples'])) {
                    $prompt .= "Recent news:\n";
                    foreach ($context['news_samples'] as $news) {
                        $prompt .= "  * {$news['titre']} ({$news['datePublication']})\n";
                    }
                }
            }
            
            $prompt .= "\n";
        }
        
        $prompt .= "CRITICAL GUIDELINES:\n";
        $prompt .= "- ALWAYS check the 'PRODUCTS MATCHING USER QUERY' section FIRST before answering about product availability\n";
        $prompt .= "- If a product is listed in 'PRODUCTS MATCHING USER QUERY', it EXISTS in our shop - confirm this to the user!\n";
        $prompt .= "- Provide the exact product name, price, and stock status from the database\n";
        $prompt .= "- Be helpful, friendly, and enthusiastic about gaming and charity\n";
        $prompt .= "- Provide accurate information based on the REAL database data provided above\n";
        $prompt .= "- When users ask about specific products, ALWAYS reference the searched products data\n";
        $prompt .= "- Encourage users to explore different features of the platform\n";
        $prompt .= "- Highlight the charity aspect (10% donation) when relevant\n";
        $prompt .= "- Keep responses concise but informative\n";
        $prompt .= "- Use emojis sparingly to maintain a professional yet friendly tone\n\n";
        
        $prompt .= "Remember: You ONLY answer questions related to FoxUnity platform. The database data provided above is REAL and CURRENT - trust it completely!";
        
        return $prompt;
    }
    
    /**
     * Call OpenRouter API with improved error handling
     */
    private static function callOpenRouterAPI($messages) {
        $data = [
            'model' => self::$model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 1000
        ];
        
        $ch = curl_init(self::$apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . self::$apiKey,
            'Content-Type: application/json',
            'HTTP-Referer: https://foxunity.com',
            'X-Title: FoxUnity AI Assistant'
        ]);
        
        // Add these for better compatibility and SSL handling
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);
        
        // Better error logging and handling
        if ($error || $curlErrno) {
            error_log("CURL Error #{$curlErrno}: " . $error);
            return [
                'success' => false,
                'error' => 'Sorry, I\'m having trouble connecting. Please try again later. (Connection error)'
            ];
        }
        
        if ($httpCode !== 200) {
            error_log("API HTTP Error: " . $httpCode . " - Response: " . substr($response, 0, 500));
            
            // Specific error messages based on HTTP code
            if ($httpCode === 401) {
                return [
                    'success' => false,
                    'error' => '🔑 API authentication failed. Please check that your OpenRouter API key is correct in ChatbotController.php'
                ];
            } elseif ($httpCode === 429) {
                return [
                    'success' => false,
                    'error' => '⏳ Too many requests. Please wait a moment and try again.'
                ];
            } elseif ($httpCode === 402) {
                return [
                    'success' => false,
                    'error' => '💳 Insufficient credits. Please add credits to your OpenRouter account at https://openrouter.ai'
                ];
            } elseif ($httpCode === 404) {
                return [
                    'success' => false,
                    'error' => '🔍 Model not found. The AI model may not exist or is unavailable. Try changing the model in ChatbotController.php'
                ];
            } elseif ($httpCode >= 500) {
                return [
                    'success' => false,
                    'error' => '🔧 OpenRouter service is temporarily unavailable. Please try again in a moment.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Service temporarily unavailable (Error ' . $httpCode . '). Please try again.'
                ];
            }
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            return [
                'success' => false,
                'error' => 'Received invalid response from AI service. Please try again.'
            ];
        }
        
        if (isset($result['choices'][0]['message']['content'])) {
            return [
                'success' => true,
                'response' => $result['choices'][0]['message']['content']
            ];
        }
        
        if (isset($result['error'])) {
            error_log("API Error Response: " . json_encode($result['error']));
            return [
                'success' => false,
                'error' => 'AI service error: ' . ($result['error']['message'] ?? 'Unknown error')
            ];
        }
        
        error_log("Unexpected API response format: " . substr($response, 0, 500));
        return [
            'success' => false,
            'error' => 'Received unexpected response from AI service. Please try again.'
        ];
    }
    
    /**
     * Save conversation to database (optional feature)
     */
    public static function saveConversation($userId, $message, $response) {
        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO chatbot_conversations (user_id, user_message, bot_response, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $message, $response]);
            return true;
        } catch (PDOException $e) {
            error_log("Failed to save conversation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get conversation history for a user (optional feature)
     */
    public static function getUserConversations($userId, $limit = 50) {
        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("
                SELECT user_message, bot_response, created_at
                FROM chatbot_conversations
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get conversations: " . $e->getMessage());
            return [];
        }
    }
}