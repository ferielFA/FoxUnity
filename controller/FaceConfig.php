<?php
/**
 * Face Recognition Configuration
 * 
 * Store your MXFace API credentials here
 */

class FaceConfig {
    // Replace with your actual MXFace API subscription key
    const MXFACE_API_KEY = 'I641WFvrqKoNEvAtCI-SLunw6uMEZ4968';
    
    // API endpoint
    const API_URL = 'https://faceapi.mxface.ai/api/v3/face/detect';
    
    // Face matching confidence threshold (1-201 scale from MXFace API)
    // 120+ is recommended for good matches
    // matchResult=1 means faces match (confidence typically 150-201)
    const SIMILARITY_THRESHOLD = 80;
    
    // Maximum number of users to compare (for performance)
    const MAX_USERS_TO_CHECK = 100;
}
?>