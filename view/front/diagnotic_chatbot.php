<?php
// Diagnostic Test for Chatbot API
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot API Diagnostic</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1a1a1a;
            color: #fff;
            padding: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 { color: #ff7a00; }
        .test-section {
            background: #2a2a2a;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            border: 2px solid #ff7a00;
        }
        .success { color: #00ff88; }
        .error { color: #ff4444; }
        pre {
            background: #000;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        button {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            margin: 10px 5px;
        }
        button:hover { opacity: 0.9; }
        #result { margin-top: 20px; }
    </style>
</head>
<body>
    <h1>🔧 Chatbot API Diagnostic Tool</h1>

    <div class="test-section">
        <h2>Step 1: Test PHP Configuration</h2>
        <?php
        echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
        echo "<p><strong>cURL Extension:</strong> " . (extension_loaded('curl') ? '<span class="success">✓ Installed</span>' : '<span class="error">✗ Missing</span>') . "</p>";
        echo "<p><strong>JSON Extension:</strong> " . (extension_loaded('json') ? '<span class="success">✓ Installed</span>' : '<span class="error">✗ Missing</span>') . "</p>";
        ?>
    </div>

    <div class="test-section">
        <h2>Step 2: Test File Paths</h2>
        <?php
        $files = [
            'ChatbotController' => __DIR__ . '/../../controller/ChatbotController.php',
            'UserController' => __DIR__ . '/../../controller/UserController.php',
            'Database' => __DIR__ . '/../../config/Database.php',
            'chatbot_api.php' => __DIR__ . '/chatbot_api.php'
        ];
        
        foreach ($files as $name => $path) {
            $exists = file_exists($path);
            echo "<p><strong>$name:</strong> ";
            if ($exists) {
                echo '<span class="success">✓ Found</span> - ' . htmlspecialchars($path);
            } else {
                echo '<span class="error">✗ Missing</span> - ' . htmlspecialchars($path);
            }
            echo "</p>";
        }
        ?>
    </div>

    <div class="test-section">
        <h2>Step 3: Test API Response</h2>
        <button onclick="testAPI()">Test API Endpoint</button>
        <button onclick="testAPIRaw()">Test Raw Response</button>
        <div id="result"></div>
    </div>

    <div class="test-section">
        <h2>Step 4: Check for PHP Errors</h2>
        <p>Check if ChatbotController can be loaded:</p>
        <?php
        try {
            require_once __DIR__ . '/../../controller/ChatbotController.php';
            echo '<p class="success">✓ ChatbotController loaded successfully</p>';
            
            // Check if API key is set
            $reflection = new ReflectionClass('ChatbotController');
            $property = $reflection->getProperty('apiKey');
            $property->setAccessible(true);
            $apiKey = $property->getValue();
            
            if ($apiKey === 'YOUR_OPENROUTER_API_KEY') {
                echo '<p class="error">⚠️ API Key not configured! Update line 5 in ChatbotController.php</p>';
            } else {
                echo '<p class="success">✓ API Key is configured (starts with: ' . substr($apiKey, 0, 10) . '...)</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">✗ Error loading ChatbotController: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>

    <div class="test-section">
        <h2>Step 5: Direct Test</h2>
        <p>Testing ChatbotController directly:</p>
        <?php
        try {
            if (class_exists('ChatbotController')) {
                $testResult = ChatbotController::getChatResponse('Hello', []);
                echo '<pre>' . htmlspecialchars(json_encode($testResult, JSON_PRETTY_PRINT)) . '</pre>';
            }
        } catch (Exception $e) {
            echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        }
        ?>
    </div>

    <script>
        async function testAPI() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p>Testing API endpoint...</p>';

            try {
                const response = await fetch('chatbot_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: 'Hello, can you introduce yourself?',
                        history: []
                    })
                });

                const contentType = response.headers.get('content-type');
                console.log('Content-Type:', contentType);
                console.log('Status:', response.status);

                const text = await response.text();
                console.log('Raw response:', text);

                resultDiv.innerHTML = `
                    <p><strong>Status:</strong> ${response.status}</p>
                    <p><strong>Content-Type:</strong> ${contentType}</p>
                    <p><strong>Response:</strong></p>
                    <pre>${text}</pre>
                `;

                // Try to parse as JSON
                try {
                    const data = JSON.parse(text);
                    resultDiv.innerHTML += `
                        <p class="success">✓ Valid JSON Response</p>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                } catch (e) {
                    resultDiv.innerHTML += `<p class="error">✗ NOT valid JSON: ${e.message}</p>`;
                }

            } catch (error) {
                resultDiv.innerHTML = `<p class="error">Network Error: ${error.message}</p>`;
                console.error('Error:', error);
            }
        }

        async function testAPIRaw() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p>Fetching raw response...</p>';

            try {
                const response = await fetch('chatbot_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: 'test',
                        history: []
                    })
                });

                const text = await response.text();
                
                resultDiv.innerHTML = `
                    <p><strong>Raw Response (first 1000 chars):</strong></p>
                    <pre>${text.substring(0, 1000)}</pre>
                    <p><strong>Response Length:</strong> ${text.length} characters</p>
                `;

                // Show what's before the JSON
                const jsonStart = text.indexOf('{');
                if (jsonStart > 0) {
                    resultDiv.innerHTML += `
                        <p class="error">⚠️ Found ${jsonStart} characters BEFORE the JSON!</p>
                        <p><strong>Content before JSON:</strong></p>
                        <pre>${text.substring(0, jsonStart)}</pre>
                    `;
                }

            } catch (error) {
                resultDiv.innerHTML = `<p class="error">Error: ${error.message}</p>`;
            }
        }
    </script>
</body>
</html>