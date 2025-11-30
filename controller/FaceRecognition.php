<?php

class FaceRecognition {
    private $subscriptionKey;
    
    public function __construct($subscriptionKey) {
        $this->subscriptionKey = $subscriptionKey;
    }
    
    /**
     * Compare two faces using MXFace verify API
     * 
     * @param string $image1Base64 First image base64
     * @param string $image2Base64 Second image base64
     * @return array Comparison result with confidence score
     */
    public function verifyFaces($image1Base64, $image2Base64) {
        try {
            // Remove data URL prefix if present
            if (strpos($image1Base64, 'data:image') === 0) {
                $image1Base64 = explode(',', $image1Base64)[1];
            }
            if (strpos($image2Base64, 'data:image') === 0) {
                $image2Base64 = explode(',', $image2Base64)[1];
            }
            
            $requestData = json_encode([
                'encoded_image1' => $image1Base64,
                'encoded_image2' => $image2Base64
            ]);
            
            $ch = curl_init('https://faceapi.mxface.ai/api/v3/face/verify');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Subscriptionkey: ' . $this->subscriptionKey
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            error_log("Face verify API - HTTP: $httpCode");
            
            if ($curlError) {
                error_log("CURL Error: $curlError");
                return [
                    'success' => false,
                    'confidence' => 0,
                    'error' => 'Connection error'
                ];
            }
            
            if ($httpCode === 200) {
                $result = json_decode($response, true);
                
                if (isset($result['matchedFaces']) && !empty($result['matchedFaces'])) {
                    $match = $result['matchedFaces'][0];
                    
                    $matchResult = $match['matchResult'] ?? 0;
                    $confidence = $match['confidence'] ?? 0;
                    $quality1 = $match['image1_face']['quality'] ?? 50;
                    $quality2 = $match['image2_face']['quality'] ?? 50;
                    
                    error_log("Match result: $matchResult | Confidence: $confidence | Q1: $quality1 | Q2: $quality2");
                    
                    return [
                        'success' => true,
                        'match_result' => $matchResult,
                        'confidence' => $confidence,
                        'quality1' => round($quality1, 2),
                        'quality2' => round($quality2, 2),
                        'is_match' => $matchResult === 1
                    ];
                }
                
                error_log("Face verify API returned no matched faces");
                return [
                    'success' => false,
                    'confidence' => 0,
                    'error' => 'No face detected in one or both images'
                ];
            }
            
            if ($httpCode === 401) {
                error_log("Face verify API error: INVALID API KEY (HTTP 401)");
                return ['success' => false, 'confidence' => 0, 'error' => 'Invalid API key'];
            } elseif ($httpCode === 403) {
                error_log("Face verify API error: ACCESS FORBIDDEN (HTTP 403)");
                return ['success' => false, 'confidence' => 0, 'error' => 'API access denied or no credits'];
            } else {
                error_log("Face verify API error: HTTP $httpCode - $response");
                return ['success' => false, 'confidence' => 0, 'error' => "API error: HTTP $httpCode"];
            }
            
        } catch (Exception $e) {
            error_log("Face verification error: " . $e->getMessage());
            return [
                'success' => false,
                'confidence' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Find matching user by comparing captured face with all profile pictures
     * Uses face verification API directly
     * 
     * @param string $capturedImageBase64 Base64 encoded captured image
     * @param array $users Array of users with their profile pictures
     * @param float $confidenceThreshold Minimum confidence threshold (1-201)
     * @return array Matched user data or null if no match found
     */
    public function findMatchingUser($capturedImageBase64, $users, $confidenceThreshold = 120) {
        $bestMatch = null;
        $bestConfidence = 0;
        $allScores = []; // Track all scores for debugging
        
        foreach ($users as $user) {
            // Skip users without profile pictures
            if (empty($user->getImage())) {
                continue;
            }
            
            // Get full image path
            $imagePath = __DIR__ . '/../view/' . $user->getImage();
            
            if (!file_exists($imagePath)) {
                error_log("Profile picture not found: " . $imagePath);
                continue;
            }
            
            // Convert profile picture to base64
            $imageData = file_get_contents($imagePath);
            $base64Image = base64_encode($imageData);
            
            // Use the face verification API
            $verifyResult = $this->verifyFaces($capturedImageBase64, $base64Image);
            
            if (!$verifyResult['success']) {
                error_log("Face verification failed for user {$user->getId()}: " . ($verifyResult['error'] ?? 'unknown error'));
                continue;
            }
            
            $confidence = $verifyResult['confidence'];
            $matchResult = $verifyResult['match_result'];
            $quality1 = $verifyResult['quality1'];
            $quality2 = $verifyResult['quality2'];
            
            $allScores[] = [
                'user_id' => $user->getId(),
                'username' => $user->getUsername(),
                'confidence' => $confidence,
                'match_result' => $matchResult,
                'captured_quality' => round($quality1, 2),
                'profile_quality' => round($quality2, 2),
                'is_match' => $matchResult === 1 ? 'YES' : 'NO'
            ];
            
            error_log("User {$user->getUsername()} - Confidence: {$confidence} | Match: " . ($matchResult === 1 ? 'YES' : 'NO'));
            
            if ($confidence > $bestConfidence) {
                $bestConfidence = $confidence;
                $bestMatch = $user;
            }
        }
        
        // Sort all scores by confidence (highest first)
        usort($allScores, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        error_log("All confidence scores: " . json_encode($allScores));
        error_log("Best match: " . ($bestMatch ? $bestMatch->getUsername() : 'none') . " with confidence {$bestConfidence} (threshold: {$confidenceThreshold})");
        
        // Prepare debug data
        $debugData = [
            'threshold' => $confidenceThreshold,
            'all_scores' => $allScores,
            'best_confidence' => $bestConfidence,
            'best_match' => $bestMatch ? $bestMatch->getUsername() : 'none'
        ];
        
        if ($bestMatch && $bestConfidence >= $confidenceThreshold) {
            return [
                'success' => true,
                'user' => $bestMatch,
                'confidence' => $bestConfidence,
                'debug' => $debugData
            ];
        }
        
        return [
            'success' => false,
            'message' => 'No matching user found. Best match: ' . ($bestMatch ? $bestMatch->getUsername() : 'none') . ' with confidence ' . round($bestConfidence) . ' (needs ' . $confidenceThreshold . ')',
            'debug' => $debugData
        ];
    }
}
?>