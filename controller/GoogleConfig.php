<?php
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

class GoogleConfig {
    // ⚠️ REMPLACE CES VALEURS PAR TES CREDENTIALS GOOGLE!
    const CLIENT_ID = '778961297198-vfihacd445g3jd07ctpen904qe29jc09.apps.googleusercontent.com';
    const CLIENT_SECRET = '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';
    const REDIRECT_URI = 'http://localhost/pw/projet_web/view/front/google-callback.php';
    
    private static $client = null;
    
    public static function getClient() {
        if (self::$client === null) {
            self::$client = new Google_Client();
            self::$client->setClientId(self::CLIENT_ID);
            self::$client->setClientSecret(self::CLIENT_SECRET);
            self::$client->setRedirectUri(self::REDIRECT_URI);
            self::$client->addScope('email');
            self::$client->addScope('profile');
        }
        return self::$client;
    }
    
    public static function getAuthUrl() {
        $client = self::getClient();
        return $client->createAuthUrl();
    }
    
    public static function authenticate($code) {
        $client = self::getClient();
        
        try {
            $token = $client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($token['error'])) {
                return ['success' => false, 'error' => $token['error']];
            }
            
            $client->setAccessToken($token);
            
            // Get user info
            $oauth = new Google_Service_Oauth2($client);
            $userInfo = $oauth->userinfo->get();
            
            return [
                'success' => true,
                'google_id' => $userInfo->id,
                'email' => $userInfo->email,
                'name' => $userInfo->name,
                'picture' => null,
                'given_name' => $userInfo->givenName,
                'family_name' => $userInfo->familyName
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// Handle login redirect
if (isset($_GET['action']) && $_GET['action'] === 'login') {
    $authUrl = GoogleConfig::getAuthUrl();
    header('Location: ' . $authUrl);
    exit();
}
?>