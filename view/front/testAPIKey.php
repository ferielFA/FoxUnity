<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
require_once __DIR__ . '/../../controller/FaceConfig.php';

$testResult = null;
$apiKey = FaceConfig::MXFACE_API_KEY;

// Handle test submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_image'])) {
    $testResult = testAPIKey($_FILES['test_image']);
}

function testAPIKey($uploadedFile) {
    $apiKey = FaceConfig::MXFACE_API_KEY;
    
    // Check if API key is set
    if ($apiKey === 'YOUR_MXFACE_API_KEY_HERE' || empty($apiKey)) {
        return [
            'success' => false,
            'error' => 'API key not configured in FaceConfig.php'
        ];
    }
    
    // Check if file was uploaded
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'error' => 'File upload error: ' . $uploadedFile['error']
        ];
    }
    
    // Read and encode image
    $imageData = file_get_contents($uploadedFile['tmp_name']);
    $base64Image = base64_encode($imageData);
    
    // Test 1: Face Detection
    $detectResult = testFaceDetection($apiKey, $base64Image);
    
    // Test 2: Face Verification (compare with itself - should be 100% match)
    $verifyResult = testFaceVerification($apiKey, $base64Image, $base64Image);
    
    return [
        'success' => $detectResult['success'] && $verifyResult['success'],
        'detection' => $detectResult,
        'verification' => $verifyResult,
        'api_key_preview' => substr($apiKey, 0, 8) . '...' . substr($apiKey, -4)
    ];
}

function testFaceDetection($apiKey, $base64Image) {
    $requestData = json_encode([
        'encoded_image' => $base64Image
    ]);
    
    $ch = curl_init('https://faceapi.mxface.ai/api/v3/face/detect');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Subscriptionkey: ' . $apiKey
    ]);
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $endTime = microtime(true);
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    $responseData = json_decode($response, true);
    
    return [
        'success' => $httpCode === 200,
        'http_code' => $httpCode,
        'response_time' => $responseTime,
        'curl_error' => $curlError ?: null,
        'response' => $responseData,
        'raw_response' => $response
    ];
}

function testFaceVerification($apiKey, $base64Image1, $base64Image2) {
    $requestData = json_encode([
        'encoded_image1' => $base64Image1,
        'encoded_image2' => $base64Image2
    ]);
    
    $ch = curl_init('https://faceapi.mxface.ai/api/v3/face/verify');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Subscriptionkey: ' . $apiKey
    ]);
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $endTime = microtime(true);
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    $responseData = json_decode($response, true);
    
    return [
        'success' => $httpCode === 200,
        'http_code' => $httpCode,
        'response_time' => $responseTime,
        'curl_error' => $curlError ?: null,
        'response' => $responseData,
        'raw_response' => $response
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MXFace API Key Tester</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            color: #fff;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 36px;
            color: #ff7a00;
            margin-bottom: 10px;
        }

        .header p {
            color: #aaa;
            font-size: 14px;
        }

        .card {
            background: rgba(30, 30, 30, 0.95);
            border: 2px solid rgba(255, 122, 0, 0.3);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .card h2 {
            color: #ff7a00;
            margin-bottom: 20px;
            font-size: 22px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .label {
            color: #aaa;
            font-weight: 600;
        }

        .value {
            color: #fff;
            font-family: monospace;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-success {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            border: 1px solid #4caf50;
        }

        .status-error {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
            border: 1px solid #f44336;
        }

        .status-warning {
            background: rgba(255, 152, 0, 0.2);
            color: #ff9800;
            border: 1px solid #ff9800;
        }

        .upload-area {
            border: 3px dashed rgba(255, 122, 0, 0.3);
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .upload-area:hover {
            border-color: #ff7a00;
            background: rgba(255, 122, 0, 0.05);
        }

        .upload-area i {
            font-size: 48px;
            color: #ff7a00;
            margin-bottom: 15px;
        }

        .upload-area p {
            color: #aaa;
            margin: 5px 0;
        }

        input[type="file"] {
            display: none;
        }

        .btn {
            background: linear-gradient(135deg, #ff7a00 0%, #ff4f00 100%);
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 122, 0, 0.4);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .result-box {
            margin-top: 30px;
            padding: 20px;
            border-radius: 12px;
            border: 2px solid;
        }

        .result-box.success {
            background: rgba(76, 175, 80, 0.1);
            border-color: #4caf50;
        }

        .result-box.error {
            background: rgba(244, 67, 54, 0.1);
            border-color: #f44336;
        }

        .result-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .json-output {
            background: #000;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: monospace;
            font-size: 12px;
            color: #0f0;
            max-height: 400px;
            overflow-y: auto;
        }

        .preview-image {
            max-width: 300px;
            border-radius: 10px;
            margin: 15px 0;
            border: 2px solid #ff7a00;
        }

        .face-count {
            display: inline-block;
            background: linear-gradient(135deg, #00bcd4, #0097a7);
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 700;
            margin: 10px 0;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 28px;
            }

            .card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🦊 MXFace API Key Tester</h1>
            <p>Test your API key and verify face detection is working</p>
        </div>

        <!-- API Configuration -->
        <div class="card">
            <h2><i class="fas fa-cog"></i> Current Configuration</h2>
            <div class="info-row">
                <span class="label">API Key Status:</span>
                <span class="value">
                    <?php if ($apiKey === 'YOUR_MXFACE_API_KEY_HERE' || empty($apiKey)): ?>
                        <span class="status-badge status-error">NOT CONFIGURED</span>
                    <?php else: ?>
                        <span class="status-badge status-success">CONFIGURED</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="info-row">
                <span class="label">API Key Preview:</span>
                <span class="value">
                    <?php 
                    if ($apiKey === 'YOUR_MXFACE_API_KEY_HERE' || empty($apiKey)) {
                        echo '<span style="color: #f44336;">Not Set</span>';
                    } else {
                        echo substr($apiKey, 0, 8) . '...' . substr($apiKey, -4);
                    }
                    ?>
                </span>
            </div>
            <div class="info-row">
                <span class="label">API Endpoint:</span>
                <span class="value"><?php echo FaceConfig::API_URL; ?></span>
            </div>
            <div class="info-row">
                <span class="label">Threshold:</span>
                <span class="value"><?php echo FaceConfig::SIMILARITY_THRESHOLD; ?>%</span>
            </div>
            <?php if ($apiKey === 'YOUR_MXFACE_API_KEY_HERE' || empty($apiKey)): ?>
            <div style="margin-top: 20px; padding: 15px; background: rgba(244, 67, 54, 0.1); border: 2px solid #f44336; border-radius: 10px;">
                <strong style="color: #f44336;"><i class="fas fa-exclamation-triangle"></i> Warning:</strong>
                <p style="margin: 10px 0 0 0; color: #aaa;">API key is not configured. Please set your API key in <code style="color: #ff7a00;">controller/FaceConfig.php</code> (line 10)</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Upload Test Image -->
        <div class="card">
            <h2><i class="fas fa-upload"></i> Test Face Detection</h2>
            <form method="POST" enctype="multipart/form-data" id="testForm">
                <label for="fileInput" class="upload-area" id="uploadArea">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h3>Upload a Test Image</h3>
                    <p>Click to select an image with a face</p>
                    <p style="font-size: 12px; color: #666;">(JPG, PNG, max 5MB)</p>
                </label>
                <input type="file" id="fileInput" name="test_image" accept="image/*" required>
                <div style="text-align: center;">
                    <button type="submit" class="btn" id="testBtn" disabled>
                        <i class="fas fa-flask"></i> Test API Key
                    </button>
                </div>
            </form>
        </div>

        <!-- Test Results -->
        <?php if ($testResult): ?>
        <div class="card">
            <h2><i class="fas fa-chart-bar"></i> Test Results</h2>
            
            <?php if (isset($testResult['error'])): ?>
                <div class="result-box error">
                    <div class="result-title">
                        <i class="fas fa-times-circle"></i> Test Failed
                    </div>
                    <p style="color: #f44336; margin-top: 10px;">
                        <strong>Error:</strong> <?php echo htmlspecialchars($testResult['error']); ?>
                    </p>
                </div>
            <?php else: ?>
                
                <!-- Test 1: Face Detection -->
                <h3 style="color: #00bcd4; margin: 20px 0 15px 0;">
                    <i class="fas fa-search"></i> Test 1: Face Detection API
                </h3>
                
                <div class="result-box <?php echo $testResult['detection']['success'] ? 'success' : 'error'; ?>">
                    <div class="result-title">
                        <?php if ($testResult['detection']['success']): ?>
                            <i class="fas fa-check-circle"></i> Face Detection Successful!
                        <?php else: ?>
                            <i class="fas fa-times-circle"></i> Face Detection Failed
                        <?php endif; ?>
                    </div>

                    <div class="info-row">
                        <span class="label">HTTP Status:</span>
                        <span class="value">
                            <?php 
                            $code = $testResult['detection']['http_code'];
                            if ($code === 200) {
                                echo '<span class="status-badge status-success">200 OK</span>';
                            } elseif ($code === 401) {
                                echo '<span class="status-badge status-error">401 Unauthorized</span>';
                            } elseif ($code === 403) {
                                echo '<span class="status-badge status-error">403 Forbidden</span>';
                            } else {
                                echo '<span class="status-badge status-error">' . $code . '</span>';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="label">Response Time:</span>
                        <span class="value"><?php echo $testResult['detection']['response_time']; ?> ms</span>
                    </div>

                    <?php if ($testResult['detection']['success'] && isset($testResult['detection']['response'][0]['faces'])): ?>
                        <div style="margin-top: 20px;">
                            <strong style="color: #4caf50;"><i class="fas fa-smile"></i> Faces Detected:</strong>
                            <span class="face-count"><?php echo count($testResult['detection']['response'][0]['faces']); ?> Face(s)</span>
                            
                            <?php foreach ($testResult['detection']['response'][0]['faces'] as $index => $face): ?>
                                <div style="margin: 15px 0; padding: 15px; background: rgba(0, 188, 212, 0.1); border-radius: 10px;">
                                    <strong>Face #<?php echo $index + 1; ?>:</strong>
                                    <div style="margin-top: 10px;">
                                        <span class="label">Quality Score:</span>
                                        <span class="value" style="color: <?php echo $face['quality'] > 70 ? '#4caf50' : ($face['quality'] > 50 ? '#ff9800' : '#f44336'); ?>">
                                            <?php echo round($face['quality'], 2); ?>%
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 20px;">
                        <strong>Raw API Response:</strong>
                        <div class="json-output"><?php echo htmlspecialchars(json_encode($testResult['detection']['response'], JSON_PRETTY_PRINT)); ?></div>
                    </div>
                </div>

                <!-- Test 2: Face Verification -->
                <h3 style="color: #ff7a00; margin: 30px 0 15px 0;">
                    <i class="fas fa-user-check"></i> Test 2: Face Verification API (Self-Comparison)
                </h3>
                
                <div class="result-box <?php echo $testResult['verification']['success'] ? 'success' : 'error'; ?>">
                    <div class="result-title">
                        <?php if ($testResult['verification']['success']): ?>
                            <i class="fas fa-check-circle"></i> Face Verification Working!
                        <?php else: ?>
                            <i class="fas fa-times-circle"></i> Face Verification Failed
                        <?php endif; ?>
                    </div>

                    <div style="margin: 15px 0; padding: 15px; background: rgba(255, 122, 0, 0.1); border-radius: 10px;">
                        <p style="color: #aaa; margin-bottom: 10px;">
                            <i class="fas fa-info-circle"></i> This test compares the uploaded image with itself. 
                            It should return <strong>matchResult: 1</strong> (faces match).
                        </p>
                    </div>

                    <div class="info-row">
                        <span class="label">HTTP Status:</span>
                        <span class="value">
                            <?php 
                            $code = $testResult['verification']['http_code'];
                            if ($code === 200) {
                                echo '<span class="status-badge status-success">200 OK</span>';
                            } elseif ($code === 401) {
                                echo '<span class="status-badge status-error">401 Unauthorized</span>';
                            } elseif ($code === 403) {
                                echo '<span class="status-badge status-error">403 Forbidden</span>';
                            } else {
                                echo '<span class="status-badge status-error">' . $code . '</span>';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="label">Response Time:</span>
                        <span class="value"><?php echo $testResult['verification']['response_time']; ?> ms</span>
                    </div>

                    <?php if ($testResult['verification']['success'] && isset($testResult['verification']['response']['matchedFaces'])): ?>
                        <?php 
                        $matchedFaces = $testResult['verification']['response']['matchedFaces'];
                        if (!empty($matchedFaces)): 
                            $match = $matchedFaces[0];
                        ?>
                            <div style="margin-top: 20px;">
                                <strong style="color: #4caf50;"><i class="fas fa-check-double"></i> Match Result:</strong>
                                <div style="margin: 15px 0; padding: 20px; background: rgba(76, 175, 80, 0.1); border-radius: 10px;">
                                    <div class="info-row">
                                        <span class="label">Match Status:</span>
                                        <span class="value">
                                            <?php if ($match['matchResult'] === 1): ?>
                                                <span class="status-badge status-success">✓ MATCH (matchResult: 1)</span>
                                            <?php else: ?>
                                                <span class="status-badge status-error">✗ NO MATCH (matchResult: 0)</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <span class="label">Image 1 Quality:</span>
                                        <span class="value" style="color: #4caf50;">
                                            <?php echo round($match['image1_face']['quality'], 2); ?>%
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <span class="label">Image 2 Quality:</span>
                                        <span class="value" style="color: #4caf50;">
                                            <?php echo round($match['image2_face']['quality'], 2); ?>%
                                        </span>
                                    </div>
                                    
                                    <?php if ($match['matchResult'] === 1): ?>
                                        <div style="margin-top: 15px; padding: 15px; background: rgba(76, 175, 80, 0.2); border-radius: 8px; border: 2px solid #4caf50;">
                                            <strong style="color: #4caf50;">
                                                <i class="fas fa-trophy"></i> Perfect! Face Verification API is working correctly!
                                            </strong>
                                            <p style="margin-top: 10px; color: #aaa; font-size: 13px;">
                                                The API correctly identified that both images are the same person (matchResult: 1).
                                                You can now use this for face recognition in your project!
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <div style="margin-top: 15px; padding: 15px; background: rgba(244, 67, 54, 0.2); border-radius: 8px; border: 2px solid #f44336;">
                                            <strong style="color: #f44336;">
                                                <i class="fas fa-exclamation-triangle"></i> Unexpected Result
                                            </strong>
                                            <p style="margin-top: 10px; color: #aaa; font-size: 13px;">
                                                The same image should match with itself (matchResult should be 1).
                                                This might indicate low image quality or API issues.
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div style="margin-top: 20px;">
                        <strong>Raw API Response:</strong>
                        <div class="json-output"><?php echo htmlspecialchars(json_encode($testResult['verification']['response'], JSON_PRETTY_PRINT)); ?></div>
                    </div>
                </div>

                <!-- Overall Status -->
                <?php if ($testResult['success']): ?>
                    <div style="margin-top: 30px; padding: 20px; background: rgba(76, 175, 80, 0.1); border-radius: 15px; border: 3px solid #4caf50;">
                        <h3 style="color: #4caf50; margin-bottom: 15px;">
                            <i class="fas fa-check-circle"></i> All Tests Passed!
                        </h3>
                        <p style="color: #aaa; line-height: 1.8;">
                            ✓ Face Detection API is working<br>
                            ✓ Face Verification API is working<br>
                            ✓ Your API key is valid<br>
                            ✓ You're ready to implement face recognition!
                        </p>
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Instructions -->
        <div class="card">
            <h2><i class="fas fa-info-circle"></i> How to Use</h2>
            <ol style="color: #aaa; line-height: 2; margin-left: 20px;">
                <li>Make sure your API key is set in <code style="color: #ff7a00;">controller/FaceConfig.php</code></li>
                <li>Upload a test image with a clear face visible</li>
                <li>Click "Test API Key" button</li>
                <li>Check both test results:
                    <ul style="margin-top: 10px;">
                        <li><strong>Test 1:</strong> Verifies face detection API is working</li>
                        <li><strong>Test 2:</strong> Verifies face verification/comparison API is working</li>
                    </ul>
                </li>
            </ol>

            <div style="margin-top: 20px; padding: 15px; background: rgba(0, 188, 212, 0.1); border-radius: 10px;">
                <strong style="color: #00bcd4;"><i class="fas fa-lightbulb"></i> What Gets Tested:</strong>
                <ul style="margin-top: 10px; color: #aaa; line-height: 2; margin-left: 20px;">
                    <li><strong>Face Detection (/detect):</strong> Can the API find faces in images?</li>
                    <li><strong>Face Verification (/verify):</strong> Can the API compare two faces and determine if they match?</li>
                    <li><strong>Quality Scores:</strong> How good is the face image quality?</li>
                    <li><strong>Match Result:</strong> Does the API correctly identify matching faces?</li>
                </ul>
            </div>

            <div style="margin-top: 20px; padding: 15px; background: rgba(76, 175, 80, 0.1); border-radius: 10px;">
                <strong style="color: #4caf50;"><i class="fas fa-check-circle"></i> Expected Results:</strong>
                <ul style="margin-top: 10px; color: #aaa; line-height: 2; margin-left: 20px;">
                    <li><strong>HTTP 200:</strong> Both APIs working correctly</li>
                    <li><strong>Faces Detected > 0:</strong> Face found in image</li>
                    <li><strong>matchResult: 1:</strong> Faces match (comparing image with itself)</li>
                    <li><strong>Quality > 70%:</strong> Good image quality for recognition</li>
                </ul>
            </div>

            <div style="margin-top: 20px; padding: 15px; background: rgba(244, 67, 54, 0.1); border-radius: 10px;">
                <strong style="color: #f44336;"><i class="fas fa-exclamation-triangle"></i> Common Issues:</strong>
                <ul style="margin-top: 10px; color: #aaa; line-height: 2; margin-left: 20px;">
                    <li><strong>HTTP 401:</strong> Invalid API key - check FaceConfig.php</li>
                    <li><strong>HTTP 403:</strong> No credits or access denied</li>
                    <li><strong>0 Faces Detected:</strong> Poor lighting or no face in image</li>
                    <li><strong>matchResult: 0:</strong> Very low quality image</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('fileInput');
        const uploadArea = document.getElementById('uploadArea');
        const testBtn = document.getElementById('testBtn');

        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const fileName = this.files[0].name;
                uploadArea.querySelector('h3').textContent = '✓ ' + fileName;
                uploadArea.querySelector('p').textContent = 'Ready to test';
                uploadArea.style.borderColor = '#4caf50';
                testBtn.disabled = false;
            }
        });

        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#ff7a00';
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = 'rgba(255, 122, 0, 0.3)';
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = 'rgba(255, 122, 0, 0.3)';
            
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                fileInput.files = e.dataTransfer.files;
                const event = new Event('change');
                fileInput.dispatchEvent(event);
            }
        });
    </script>
</body>
</html>