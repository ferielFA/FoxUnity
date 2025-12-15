<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Inclure les contrôleurs
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/reclamationcontroller.php';
require_once __DIR__ . '/../../controllers/ResponseController.php';
require_once __DIR__ . '/../../controllers/SatisfactionController.php';
require_once __DIR__ . '/../../models/Response.php';
require_once __DIR__ . '/../../models/Reclamation.php';
require_once __DIR__ . '/../../models/Satisfaction.php';

$reclamationController = new ReclamationController();
$responseController = new ResponseController();
$satisfactionController = new SatisfactionController();

/**
 * Fonction pour envoyer un email à l'utilisateur lorsqu'une réponse est ajoutée
 * @param string $userEmail L'email de l'utilisateur
 * @param string $reclamationSubject Le sujet de la réclamation
 * @param string $responseMessage Le message de la réponse
 * @param int $reclamationId L'ID de la réclamation
 * @return bool True si l'email a été envoyé avec succès, False sinon
 */
function sendResponseEmail($userEmail, $reclamationSubject, $responseMessage, $reclamationId) {
    try {
        // Vérifier que l'email est valide
        if (empty($userEmail) || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("❌ Email invalide pour l'envoi: " . $userEmail);
            return false;
        }
        
        // Préparer le sujet de l'email
        $emailSubject = "Réponse à votre réclamation - FoxUnity";
        
        // Créer le contenu HTML de l'email
        $emailBody = "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #ffffff;
                    padding: 20px;
                }
                .header {
                    background: linear-gradient(135deg, #ff7a00, #ff4f00);
                    color: white;
                    padding: 30px;
                    text-align: center;
                    border-radius: 8px 8px 0 0;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                }
                .content {
                    padding: 30px 20px;
                }
                .response-box {
                    background-color: #f9f9f9;
                    border-left: 4px solid #ff7a00;
                    padding: 20px;
                    margin: 20px 0;
                    border-radius: 4px;
                }
                .response-box p {
                    margin: 0;
                    white-space: pre-wrap;
                }
                .footer {
                    background-color: #f4f4f4;
                    padding: 20px;
                    text-align: center;
                    font-size: 12px;
                    color: #666;
                    border-radius: 0 0 8px 8px;
                }
                .button {
                    display: inline-block;
                    background-color: #ff7a00;
                    color: white;
                    padding: 12px 30px;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 20px 0;
                }
                .button:hover {
                    background-color: #ff4f00;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Nouvelle réponse à votre réclamation</h1>
                </div>
                <div class='content'>
                    <p>Bonjour,</p>
                    <p>Nous avons le plaisir de vous informer qu'une réponse a été apportée à votre réclamation :</p>
                    <p><strong>Sujet :</strong> " . htmlspecialchars($reclamationSubject) . "</p>
                    <div class='response-box'>
                        <p><strong>Réponse de l'équipe :</strong></p>
                        <p>" . nl2br(htmlspecialchars($responseMessage)) . "</p>
                    </div>
                    <p>Vous pouvez consulter votre réclamation et toutes les réponses en vous connectant à votre espace support.</p>
                    <p style='text-align: center;'>
                        <a href='http://localhost/foxunity/view/front/reclamation.php' class='button'>Voir ma réclamation</a>
                    </p>
                    <p>Cordialement,<br>L'équipe FoxUnity</p>
                </div>
                <div class='footer'>
                    <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre directement.</p>
                    <p>© 2025 FoxUnity. Tous droits réservés.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Préparer les en-têtes de l'email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: FoxUnity Support <noreply@foxunity.com>" . "\r\n";
        $headers .= "Reply-To: noreply@foxunity.com" . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // Envoyer l'email
        $mailSent = mail($userEmail, $emailSubject, $emailBody, $headers);
        
        if ($mailSent) {
            error_log("✅ Email envoyé avec succès à: " . $userEmail . " pour la réclamation ID: " . $reclamationId);
            return true;
        } else {
            error_log("❌ Échec de l'envoi de l'email à: " . $userEmail . " pour la réclamation ID: " . $reclamationId);
            return false;
        }
    } catch (Exception $e) {
        error_log("❌ Erreur lors de l'envoi de l'email: " . $e->getMessage());
        return false;
    }
}

// Traitement pour récupérer une réclamation par ID (pour View via AJAX)
if (isset($_GET['view_id']) && isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $selectedReclamation = $reclamationController->getReclamationById($_GET['view_id']);
    if ($selectedReclamation) {
        // S'assurer que responses est toujours un tableau
        $selectedReclamation['responses'] = [];
        $selectedReclamation['satisfactions'] = [];
        
        // Récupérer les réponses pour cette réclamation
        $responsesFromController = $responseController->getResponsesByReclamationId($_GET['view_id']);
        
        // Récupérer les évaluations pour cette réclamation
        $satisfactionsFromController = $satisfactionController->getSatisfactionsByReclamationId($_GET['view_id']);
        if (is_array($satisfactionsFromController) && !empty($satisfactionsFromController)) {
            // Convertir les objets Satisfaction en tableaux pour JSON
            $selectedReclamation['satisfactions'] = array_map(function($sat) {
                return [
                    'id_satisfaction' => $sat->getIdSatisfaction(),
                    'id_reclamation' => $sat->getIdReclamation(),
                    'email' => $sat->getEmail(),
                    'rating' => $sat->getRating(),
                    'commentaire' => $sat->getCommentaire(),
                    'date_evaluation' => $sat->getDateEvaluation()
                ];
            }, $satisfactionsFromController);
            error_log("✅ AJAX - " . count($selectedReclamation['satisfactions']) . " évaluation(s) récupérée(s)");
        } else {
            error_log("ℹ️ AJAX - Aucune évaluation pour réclamation ID " . $_GET['view_id']);
        }
        error_log("🔍 AJAX - Réponses récupérées via controller pour ID " . $_GET['view_id'] . ": " . (is_array($responsesFromController) ? count($responsesFromController) : 'N/A'));
        
        if (is_array($responsesFromController) && !empty($responsesFromController)) {
            $selectedReclamation['responses'] = $responsesFromController;
            error_log("✅ AJAX - " . count($responsesFromController) . " réponse(s) assignée(s) depuis controller");
        } else {
            // Fallback: Si aucune réponse n'est retournée mais qu'il y en a en base, les récupérer directement
            try {
                $db = Config::getConnexion();
                $directCheck = $db->prepare("SELECT COUNT(*) as count FROM reponses WHERE id_reclamation = ?");
                $directCheck->execute([$_GET['view_id']]);
                $directCount = $directCheck->fetch(PDO::FETCH_ASSOC);
                
                if ($directCount && $directCount['count'] > 0) {
                    error_log("⚠️ AJAX Fallback: getResponsesByReclamationId n'a pas retourné de réponses, mais il y en a " . $directCount['count'] . " en base. Récupération directe...");
                    // Récupérer directement depuis la base
                    $forcedQuery = $db->prepare("SELECT id_reponse, id_reclamation, id_admin, message, date_reponse, COALESCE(statut_reponse, 'sent') as statut_reponse FROM reponses WHERE id_reclamation = ? ORDER BY date_reponse DESC");
                    $forcedQuery->execute([$_GET['view_id']]);
                    $forcedResponses = $forcedQuery->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($forcedResponses) && is_array($forcedResponses)) {
                        $selectedReclamation['responses'] = $forcedResponses;
                        error_log("✅ AJAX Fallback réussi: " . count($forcedResponses) . " réponse(s) récupérée(s) directement");
                    }
                } else {
                    error_log("ℹ️ AJAX - Aucune réponse en base pour réclamation ID " . $_GET['view_id']);
                }
            } catch (Exception $e) {
                error_log("❌ AJAX Erreur lors du fallback pour récupérer les réponses: " . $e->getMessage());
            }
        }
        
        // Log final avant encodage JSON
        error_log("📤 AJAX - Nombre final de réponses à envoyer: " . (is_array($selectedReclamation['responses']) ? count($selectedReclamation['responses']) : 0));
        if (!empty($selectedReclamation['responses']) && is_array($selectedReclamation['responses'])) {
            error_log("📤 AJAX - Première réponse: " . print_r($selectedReclamation['responses'][0] ?? 'N/A', true));
        }
        
        $currentStatut = $selectedReclamation['statut'] ?? 'nouveau';
        
        // Si des réponses existent, mettre le statut à "resolu" (priorité)
        if (!empty($selectedReclamation['responses']) && is_array($selectedReclamation['responses']) && count($selectedReclamation['responses']) > 0 && $currentStatut !== 'resolu') {
            $updatedReclamation = new Reclamation(
                $selectedReclamation['email'] ?? '',
                $selectedReclamation['sujet'] ?? '',
                $selectedReclamation['description'] ?? '',
                $selectedReclamation['id_utilisateur'] ?? null,
                'resolu', // Statut automatiquement mis à "resolu" s'il y a des réponses
                $selectedReclamation['categorie'] ?? 'general'
            );
            $updatedReclamation->setIdReclamation($_GET['view_id']);
            $reclamationController->updateReclamation($updatedReclamation);
            $selectedReclamation['statut'] = 'resolu';
        }
        // Sinon, si c'est "nouveau" et pas encore "resolu", mettre à "en_cours" quand l'admin consulte
        elseif ($currentStatut === 'nouveau' || $currentStatut === 'pending') {
            $updatedReclamation = new Reclamation(
                $selectedReclamation['email'] ?? '',
                $selectedReclamation['sujet'] ?? '',
                $selectedReclamation['description'] ?? '',
                $selectedReclamation['id_utilisateur'] ?? null,
                'en_cours', // Statut automatiquement mis à "en_cours" quand l'admin consulte
                $selectedReclamation['categorie'] ?? 'general'
            );
            $updatedReclamation->setIdReclamation($_GET['view_id']);
            $reclamationController->updateReclamation($updatedReclamation);
            // Mettre à jour le statut dans l'objet pour l'affichage
            $selectedReclamation['statut'] = 'en_cours';
        }
        
        // S'assurer que responses est toujours un tableau pour JSON
        if (!isset($selectedReclamation['responses']) || !is_array($selectedReclamation['responses'])) {
            $selectedReclamation['responses'] = [];
        }
        
        // Récupérer les évaluations de satisfaction pour cette réclamation
        $satisfactions = $satisfactionController->getSatisfactionsByReclamationId($_GET['view_id']);
        $selectedReclamation['satisfactions'] = [];
        
        if (!empty($satisfactions) && is_array($satisfactions)) {
            // Convertir les objets Satisfaction en tableaux pour JSON
            foreach ($satisfactions as $satisfaction) {
                $selectedReclamation['satisfactions'][] = [
                    'id_satisfaction' => $satisfaction->getIdSatisfaction(),
                    'email' => $satisfaction->getEmail(),
                    'rating' => $satisfaction->getRating(),
                    'commentaire' => $satisfaction->getCommentaire(),
                    'date_evaluation' => $satisfaction->getDateEvaluation()
                ];
            }
        }
        
        echo json_encode($selectedReclamation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode(['error' => 'Reclamation not found']);
    }
    exit;
}

// Endpoint pour vérifier les nouvelles réclamations (notifications)
if (isset($_GET['check_notifications'])) {
    header('Content-Type: application/json');
    
    $lastCheckTime = isset($_GET['last_check']) ? intval($_GET['last_check']) : 0;
    $lastCheckDate = $lastCheckTime > 0 ? date('Y-m-d H:i:s', $lastCheckTime / 1000) : date('Y-m-d H:i:s', strtotime('-1 hour'));
    
    // Récupérer les réclamations créées après la dernière vérification
    $allReclamations = $reclamationController->getAllReclamations(null, null, null);
    $newReclamations = [];
    $newCount = 0;
    
    foreach ($allReclamations as $reclamation) {
        $dateCreation = $reclamation['date_creation'] ?? '';
        $statut = $reclamation['statut'] ?? 'nouveau';
        
        // Compter seulement les réclamations non résolues
        if (($statut === 'nouveau' || $statut === 'en_cours') && strtotime($dateCreation) > strtotime($lastCheckDate)) {
            $newReclamations[] = [
                'id' => $reclamation['id_reclamation'],
                'title' => 'Nouvelle réclamation: ' . htmlspecialchars($reclamation['sujet'] ?? 'Sans titre'),
                'text' => substr(htmlspecialchars($reclamation['description'] ?? ''), 0, 100) . '...',
                'time' => date('H:i', strtotime($dateCreation))
            ];
            $newCount++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'new_count' => $newCount,
        'notifications' => $newReclamations
    ]);
    exit;
}

// Endpoint pour récupérer toutes les notifications en attente
if (isset($_GET['get_pending_notifications'])) {
    header('Content-Type: application/json');
    
    // Récupérer toutes les réclamations non résolues
    $allReclamations = $reclamationController->getAllReclamations(null, null, null);
    $pendingNotifications = [];
    
    foreach ($allReclamations as $reclamation) {
        $statut = $reclamation['statut'] ?? 'nouveau';
        
        // Inclure seulement les réclamations non résolues
        if ($statut === 'nouveau' || $statut === 'en_cours') {
            $dateCreation = $reclamation['date_creation'] ?? date('Y-m-d H:i:s');
            $timeAgo = '';
            
            // Calculer le temps écoulé
            $now = new DateTime();
            $created = new DateTime($dateCreation);
            $diff = $now->diff($created);
            
            if ($diff->days > 0) {
                $timeAgo = 'Il y a ' . $diff->days . ' jour(s)';
            } elseif ($diff->h > 0) {
                $timeAgo = 'Il y a ' . $diff->h . ' heure(s)';
            } elseif ($diff->i > 0) {
                $timeAgo = 'Il y a ' . $diff->i . ' minute(s)';
            } else {
                $timeAgo = 'À l\'instant';
            }
            
            $pendingNotifications[] = [
                'id' => $reclamation['id_reclamation'],
                'title' => ($statut === 'nouveau' ? '🆕 Nouvelle' : '⏳ En cours') . ': ' . htmlspecialchars($reclamation['sujet'] ?? 'Sans titre'),
                'text' => substr(htmlspecialchars($reclamation['description'] ?? ''), 0, 100) . (strlen($reclamation['description'] ?? '') > 100 ? '...' : ''),
                'time' => $timeAgo,
                'status' => $statut
            ];
        }
    }
    
    // Trier par date (plus récentes en premier)
    usort($pendingNotifications, function($a, $b) {
        return strcmp($b['time'], $a['time']);
    });
    
    echo json_encode([
        'success' => true,
        'notifications' => $pendingNotifications
    ]);
    exit;
}

// Traitement pour mettre à jour le statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $reclamation = $reclamationController->getReclamationById($_POST['id_reclamation']);
    if ($reclamation) {
        $updatedReclamation = new Reclamation(
            $reclamation['email'] ?? '',
            $reclamation['sujet'] ?? '',
            $reclamation['description'] ?? '',
            $reclamation['id_utilisateur'] ?? null,
            $_POST['statut'],
            $reclamation['categorie'] ?? 'general'
        );
        $updatedReclamation->setIdReclamation($_POST['id_reclamation']);
        $result = $reclamationController->updateReclamation($updatedReclamation);
        if ($result) {
            header("Location: reclamback.php?status_updated=1");
            exit;
        } else {
            $errorMessage = "Error updating status.";
        }
    }
}

// Traitement de la suppression de réclamation
if (isset($_GET['delete_id'])) {
    $result = $reclamationController->deleteReclamation($_GET['delete_id']);
    if ($result) {
        header("Location: reclamback.php?deleted=1");
        exit;
    }
}

// Traitement pour ajouter une évaluation de satisfaction
if (isset($_POST['action']) && $_POST['action'] === 'submit_satisfaction' && isset($_POST['id_reclamation'])) {
    // Détecter si c'est une requête AJAX
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if ($isAjax) {
        header('Content-Type: application/json');
    }
    
    try {
        $id_reclamation = intval($_POST['id_reclamation'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $rating = intval($_POST['rating'] ?? 0);
        $commentaire = trim($_POST['commentaire'] ?? '');
        
        error_log("📝 Tentative d'ajout d'évaluation - ID réclamation: $id_reclamation, Email: $email, Rating: $rating");
        
        // Validation
        if (empty($id_reclamation) || $id_reclamation <= 0) {
            $errorMsg = 'ID de réclamation invalide.';
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            $errorMessage = $errorMsg;
        } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Email invalide ou manquant.';
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            $errorMessage = $errorMsg;
        } elseif ($rating < 1 || $rating > 5) {
            $errorMsg = 'Note invalide (doit être entre 1 et 5).';
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            $errorMessage = $errorMsg;
        } else {
            // Vérifier que la réclamation existe
            $reclamationCheck = $reclamationController->getReclamationById($id_reclamation);
            if (!$reclamationCheck) {
                $errorMsg = 'Réclamation introuvable.';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $errorMsg]);
                    exit;
                }
                $errorMessage = $errorMsg;
            } else {
                // Ajouter l'évaluation
                try {
                    $result = $satisfactionController->addSatisfaction($id_reclamation, $email, $rating, $commentaire);
                    
                    if ($result) {
                        error_log("✅ Évaluation ajoutée avec succès - ID réclamation: $id_reclamation, Rating: $rating");
                        if ($isAjax) {
                            echo json_encode([
                                'success' => true, 
                                'message' => 'Merci pour votre évaluation !',
                                'id_reclamation' => $id_reclamation
                            ]);
                            exit;
                        } else {
                            $successMessage = 'Merci pour votre évaluation !';
                        }
                    } else {
                        $errorMsg = 'Erreur lors de l\'enregistrement de l\'évaluation.';
                        error_log("❌ Échec de l'ajout d'évaluation - ID réclamation: $id_reclamation");
                        if ($isAjax) {
                            echo json_encode(['success' => false, 'message' => $errorMsg]);
                            exit;
                        }
                        $errorMessage = $errorMsg;
                    }
                } catch (Exception $e) {
                    $errorMsg = 'Erreur: ' . htmlspecialchars($e->getMessage());
                    error_log("❌ Exception lors de l'ajout d'évaluation: " . $e->getMessage());
                    
                    // Message spécial pour les erreurs de contrainte
                    if (strpos($e->getMessage(), 'CONSTRAINT_ERROR') !== false) {
                        $errorMsg = 'Vous avez déjà évalué cette réclamation.';
                    }
                    
                    if ($isAjax) {
                        echo json_encode(['success' => false, 'message' => $errorMsg]);
                        exit;
                    }
                    $errorMessage = $errorMsg;
                }
            }
        }
    } catch (PDOException $e) {
        $errorMsg = 'Erreur de base de données: ' . htmlspecialchars($e->getMessage());
        error_log('❌ Erreur PDO satisfaction: ' . $e->getMessage());
        
        // Vérifier si la table n'existe pas
        if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "n'existe pas") !== false) {
            $errorMsg = 'Table satisfactions non trouvée. Veuillez créer la table dans la base de données.';
        }
        
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => $errorMsg]);
            exit;
        }
        $errorMessage = $errorMsg;
    } catch (Exception $e) {
        $errorMsg = 'Erreur: ' . htmlspecialchars($e->getMessage());
        error_log('❌ Exception satisfaction: ' . $e->getMessage());
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => $errorMsg]);
            exit;
        }
        $errorMessage = $errorMsg;
    }
    
    if ($isAjax) {
        exit;
    }
}

// Traitement pour obtenir les statistiques de satisfaction (AJAX)
if (isset($_GET['get_satisfaction_stats']) && isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $stats = $satisfactionController->getStats();
    echo json_encode($stats);
    exit;
}


// Traitement des réponses
$successMessage = '';
$errorMessage = '';

// Ajouter une réponse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_response']) && $_POST['add_response'] == '1') {
    // Détecter si c'est une requête AJAX
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    $isAjax = $isAjax || (isset($_POST['ajax']) && $_POST['ajax'] == '1');
    
    if ($isAjax) {
        // Désactiver l'affichage des erreurs pour les requêtes AJAX
        ini_set('display_errors', 0);
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
        header('Content-Type: application/json; charset=utf-8');
        // S'assurer qu'il n'y a pas de sortie avant le JSON
        ob_clean();
    }
    
    try {
        // Récupérer et valider les données
        $idReclamation = isset($_POST['id_reclamation']) ? intval($_POST['id_reclamation']) : 0;
        $message = isset($_POST['response_text']) ? trim($_POST['response_text']) : '';
        
        // Validation de l'ID de réclamation
        if (empty($idReclamation) || $idReclamation <= 0) {
            $errorMessage = "ID de réclamation invalide.";
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMessage]);
                exit;
            }
            $errorMessage = $errorMessage;
        }
        // Validation du message
        elseif (empty($message) || strlen(trim($message)) === 0) {
            $errorMessage = "Veuillez entrer une réponse.";
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMessage]);
                exit;
            }
        }
        elseif (strlen($message) < 10) {
            $errorMessage = "La réponse doit contenir au moins 10 caractères.";
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMessage]);
                exit;
            }
        }
        elseif (strlen($message) > 5000) {
            $errorMessage = "La réponse ne doit pas dépasser 5000 caractères.";
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMessage]);
                exit;
            }
        }
        elseif (preg_match('/<script|javascript:|on\w+\s*=/i', $message)) {
            $errorMessage = "La réponse contient des caractères non autorisés pour des raisons de sécurité.";
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMessage]);
                exit;
            }
        }
        else {
            // Vérifier que la réclamation existe
            $reclamationCheck = $reclamationController->getReclamationById($idReclamation);
            if (!$reclamationCheck) {
                $errorMessage = "Réclamation introuvable.";
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $errorMessage]);
                    exit;
                }
            } else {
                // Récupérer un ID d'admin valide
                $db = Config::getConnexion();
                
                // Chercher un utilisateur avec le rôle Admin
                $adminQuery = $db->query("SELECT id FROM users WHERE LOWER(role) = 'admin' LIMIT 1");
                $adminData = $adminQuery->fetch(PDO::FETCH_ASSOC);
                
                if ($adminData) {
                    $adminId = (int)$adminData['id'];
                } else {
                    // Si aucun Admin, prendre le premier utilisateur disponible
                    $userQuery = $db->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
                    $userData = $userQuery->fetch(PDO::FETCH_ASSOC);
                    
                    if ($userData) {
                        $adminId = (int)$userData['id'];
                    } else {
                        // Aucun utilisateur trouvé - utiliser l'ID 1 par défaut
                        $adminId = 1;
                    }
                }
                
                // Vérifier que l'ID admin existe
                $verifyQuery = $db->prepare("SELECT id FROM users WHERE id = ?");
                $verifyQuery->execute([$adminId]);
                if (!$verifyQuery->fetch()) {
                    $errorMessage = "Erreur: Aucun utilisateur Admin trouvé dans la base de données.";
                    if ($isAjax) {
                        echo json_encode(['success' => false, 'message' => $errorMessage]);
                        exit;
                    }
                } else {
                    // Créer l'objet Response
                    $response = new Response($idReclamation, $message, 'Admin');
                    $response->setIdAdmin($adminId);
                    
                    // Ajouter la réponse
                    $result = $responseController->addResponse($response);
                    
                    if ($result && $result > 0) {
                        // Mettre à jour automatiquement le statut de la réclamation en "resolu"
                        $reclamation = $reclamationController->getReclamationById($idReclamation);
                        if ($reclamation && $reclamation['statut'] !== 'resolu') {
                            $updatedReclamation = new Reclamation(
                                $reclamation['email'] ?? '',
                                $reclamation['sujet'] ?? '',
                                $reclamation['description'] ?? '',
                                $reclamation['id_utilisateur'] ?? null,
                                'resolu',
                                $reclamation['categorie'] ?? 'Other'
                            );
                            $updatedReclamation->setIdReclamation($idReclamation);
                            $reclamationController->updateReclamation($updatedReclamation);
                        }
                        
                        // Retourner le succès
                        if ($isAjax) {
                            // Envoyer l'email en arrière-plan (ne pas bloquer la réponse JSON)
                            if ($reclamation && !empty($reclamation['email'])) {
                                @sendResponseEmail($reclamation['email'], $reclamation['sujet'] ?? 'Votre réclamation', $message, $reclamation['id_reclamation']);
                            }
                            
                            echo json_encode([
                                'success' => true, 
                                'message' => 'Réponse ajoutée avec succès', 
                                'id' => $result,
                                'reclamation_id' => $idReclamation
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            exit;
                        } else {
                            // Envoyer un email à l'utilisateur
                            if ($reclamation && !empty($reclamation['email'])) {
                                sendResponseEmail($reclamation['email'], $reclamation['sujet'] ?? 'Votre réclamation', $message, $reclamation['id_reclamation']);
                            }
                            
                            header("Location: reclamback.php?response_added=1&reclamation_id=" . $idReclamation);
                            exit;
                        }
                    } else {
                        $errorMessage = "Erreur lors de l'ajout de la réponse. Veuillez réessayer.";
                        if ($isAjax) {
                            echo json_encode(['success' => false, 'message' => $errorMessage]);
                            exit;
                        }
                    }
                }
            }
        }
    } catch (PDOException $e) {
        $errorMessage = "Erreur de base de données: " . htmlspecialchars($e->getMessage());
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => $errorMessage]);
            exit;
        }
    } catch (Exception $e) {
        $errorMessage = "Une erreur s'est produite: " . htmlspecialchars($e->getMessage());
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => $errorMessage]);
            exit;
        }
    }
}

// Modifier une réponse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_response']) && $_POST['edit_response'] == '1') {
    try {
        $idResponse = isset($_POST['id_response']) ? intval($_POST['id_response']) : 0;
        $idReclamation = isset($_POST['id_reclamation']) ? intval($_POST['id_reclamation']) : 0;
        $message = isset($_POST['response_text']) ? trim($_POST['response_text']) : '';
        
        if (empty($idResponse) || empty($idReclamation) || empty($message)) {
            $errorMessage = "Veuillez remplir tous les champs requis.";
            } else {
                $response = new Response(
                    $idReclamation,
                    $message,
                    'Admin' // admin_name pour compatibilité, mais sera converti en id_admin = 1
                );
            $response->setIdResponse($idResponse);
            $result = $responseController->updateResponse($response);
            if ($result) {
                header("Location: reclamback.php?response_updated=1");
                exit;
            } else {
                $errorMessage = "Error updating response.";
            }
        }
    } catch (Exception $e) {
        error_log('❌ Erreur modification réponse: ' . $e->getMessage());
        $errorMessage = "An error occurred while updating the response.";
    }
}

// Supprimer une réponse
if (isset($_GET['delete_response_id'])) {
    $result = $responseController->deleteResponse($_GET['delete_response_id']);
    if ($result) {
        header("Location: reclamback.php?response_deleted=1");
        exit;
    } else {
        $errorMessage = "Error deleting response.";
    }
}

// Messages de succès
if (isset($_GET['response_added'])) {
    $successMessage = "Réponse ajoutée avec succès !";
    
    // Si un ID de réclamation est fourni, forcer le rechargement des réponses
    if (isset($_GET['reclamation_id'])) {
        $reclamationId = intval($_GET['reclamation_id']);
        if ($reclamationId > 0) {
            // Recharger les réponses pour cette réclamation spécifique
            error_log("🔄 Rechargement forcé des réponses pour réclamation ID: $reclamationId");
        }
    }
}
if (isset($_GET['response_updated'])) {
    $successMessage = "Réponse mise à jour avec succès !";
}
if (isset($_GET['response_deleted'])) {
    $successMessage = "Réponse supprimée avec succès !";
}
if (isset($_GET['status_updated'])) {
    $successMessage = "Statut mis à jour avec succès !";
}
if (isset($_GET['deleted'])) {
    $successMessage = "Demande supprimée avec succès !";
}

// Récupérer l'erreur de la session si elle existe (pour les cas où on redirige)
if (isset($_SESSION['response_error'])) {
    $errorMessage = $_SESSION['response_error'];
    unset($_SESSION['response_error']);
}

// Récupérer TOUTES les réclamations pour calculer les statistiques (sans filtres)
$allReclamationsForStats = $reclamationController->getAllReclamations(null, null);

// Calculer les statistiques à partir de toutes les réclamations
$totalReclamations = count($allReclamationsForStats);
$nouveauCount = 0;
$enCoursCount = 0;
$resoluCount = 0;

foreach ($allReclamationsForStats as $reclamation) {
    $statut = $reclamation['statut'] ?? 'nouveau';
    switch ($statut) {
        case 'nouveau':
            $nouveauCount++;
            break;
        case 'en_cours':
            $enCoursCount++;
            break;
        case 'resolu':
            $resoluCount++;
            break;
    }
}

// Récupérer les paramètres de filtres depuis GET
$statusFilter = isset($_GET['status_filter']) && $_GET['status_filter'] !== 'all' ? $_GET['status_filter'] : null;
$dateFilter = isset($_GET['date_filter']) && $_GET['date_filter'] !== 'all' ? $_GET['date_filter'] : null;
$categorieFilter = isset($_GET['categorie_filter']) && $_GET['categorie_filter'] !== 'all' ? $_GET['categorie_filter'] : null;

// Récupérer les réclamations filtrées pour l'affichage
$allReclamations = $reclamationController->getAllReclamations($statusFilter, $dateFilter, $categorieFilter);

// Définir les catégories disponibles (mêmes que dans le formulaire New Request)
$categories = [
    'all' => 'All Categories',
    'Account Issues' => 'Account Issues',
    'Payment & Billing' => 'Payment & Billing',
    'Technical Support' => 'Technical Support',
    'Shop & Orders' => 'Shop & Orders',
    'Trading Issues' => 'Trading Issues',
    'Events & Tournaments' => 'Events & Tournaments',
    'Charity & Donations' => 'Charity & Donations',
    'Feedback & Suggestions' => 'Feedback & Suggestions',
    'Other' => 'Other'
];

// Fonction utilitaire pour récupérer les réponses de manière fiable
function getResponsesForReclamation($reclamationId, $responseController) {
    $responses = [];
    
    // Méthode 1: Utiliser le controller
    try {
        $controllerResponses = $responseController->getResponsesByReclamationId($reclamationId);
        if (is_array($controllerResponses) && !empty($controllerResponses)) {
            return $controllerResponses;
        }
    } catch (Exception $e) {
        error_log("⚠️ Erreur controller pour réclamation $reclamationId: " . $e->getMessage());
    }
    
    // Méthode 2: Requête directe en base
    try {
        $db = Config::getConnexion();
        if (!$db) {
            return [];
        }
        
        // Essayer avec les colonnes standards
        $query = $db->prepare("SELECT id_reponse, id_reclamation, id_admin, message, date_reponse, COALESCE(statut_reponse, 'sent') as statut_reponse FROM reponses WHERE id_reclamation = ? ORDER BY date_reponse DESC");
        $query->execute([$reclamationId]);
        $directResponses = $query->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($directResponses) && is_array($directResponses)) {
            return $directResponses;
        }
    } catch (PDOException $e) {
        error_log("⚠️ Erreur requête directe (standard) pour réclamation $reclamationId: " . $e->getMessage());
        
        // Méthode 3: SELECT * avec normalisation
        try {
            $query2 = $db->prepare("SELECT * FROM reponses WHERE id_reclamation = ? ORDER BY date_reponse DESC");
            $query2->execute([$reclamationId]);
            $allResponses = $query2->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($allResponses)) {
                $normalized = [];
                foreach ($allResponses as $resp) {
                    $normalized[] = [
                        'id_reponse' => $resp['id_reponse'] ?? $resp['response_id'] ?? '',
                        'id_reclamation' => $resp['id_reclamation'] ?? $resp['complaint_id'] ?? '',
                        'id_admin' => $resp['id_admin'] ?? '',
                        'message' => $resp['message'] ?? $resp['response_text'] ?? '',
                        'date_reponse' => $resp['date_reponse'] ?? $resp['response_date'] ?? $resp['date_creation'] ?? date('Y-m-d H:i:s'),
                        'statut_reponse' => $resp['statut_reponse'] ?? $resp['response_status'] ?? 'sent'
                    ];
                }
                return $normalized;
            }
        } catch (Exception $e2) {
            error_log("❌ Erreur SELECT * pour réclamation $reclamationId: " . $e2->getMessage());
        }
    } catch (Exception $e) {
        error_log("❌ Erreur générale pour réclamation $reclamationId: " . $e->getMessage());
    }
    
    return [];
}

// Récupérer les réponses pour chaque réclamation et mettre à jour le statut si nécessaire
foreach ($allReclamations as &$reclamation) {
    $reclamationId = $reclamation['id_reclamation'];
    
    // Utiliser la fonction utilitaire
    $reclamation['responses'] = getResponsesForReclamation($reclamationId, $responseController);
    
    // Si la réclamation a des réponses et n'est pas déjà "resolu", mettre à jour le statut
    if (!empty($reclamation['responses']) && is_array($reclamation['responses']) && count($reclamation['responses']) > 0) {
        if (($reclamation['statut'] ?? 'nouveau') !== 'resolu') {
            $updatedReclamation = new Reclamation(
                $reclamation['email'] ?? '',
                $reclamation['sujet'] ?? '',
                $reclamation['description'] ?? '',
                $reclamation['id_utilisateur'] ?? null,
                'resolu',
                $reclamation['categorie'] ?? 'general'
            );
            $updatedReclamation->setIdReclamation($reclamationId);
            $reclamationController->updateReclamation($updatedReclamation);
            $reclamation['statut'] = 'resolu';
        }
    }
}
unset($reclamation);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoxUnity - Dashboard Support</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #ff7a00;
            --primary-dark: #ff4f00;
            --bg-dark: #0a0a0a;
            --bg-card: rgba(20, 20, 20, 0.95);
            --text-light: #ffffff;
            --text-gray: #aaaaaa;
            --border-color: rgba(255, 255, 255, 0.1);
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
        }
        
        /* Mode Clair */
        :root[data-theme="light"] {
            --primary-color: #ff7a00;
            --primary-dark: #ff4f00;
            --bg-dark: #f5f5f5;
            --bg-card: #ffffff;
            --text-light: #1a1a1a;
            --text-gray: #666666;
            --border-color: rgba(0, 0, 0, 0.1);
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
        }
        
        /* Ajustements pour le mode clair */
        :root[data-theme="light"] body {
            background: var(--bg-dark);
        }
        
        :root[data-theme="light"] .sidebar {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(245, 245, 245, 0.95) 100%);
            border-right: 2px solid var(--border-color);
        }
        
        :root[data-theme="light"] .stat-card,
        :root[data-theme="light"] .review-card {
            background: var(--bg-card);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        :root[data-theme="light"] .modal-content {
            background: var(--bg-card);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-dark);
            color: var(--text-light);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles - Identique au dashboard */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.95) 100%);
            border-right: 2px solid var(--border-color);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        
        .dashboard-logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            display: block;
        }
        
        .sidebar h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            text-align: center;
            margin-bottom: 30px;
            color: var(--text-light);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .sidebar a {
            display: block;
            padding: 15px 25px;
            color: var(--text-light);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-weight: 500;
            border-radius: 12px;
            margin: 5px 15px;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background: linear-gradient(90deg, #ff7a00, #ff4f81);
            color: #fff;
            box-shadow: 0 0 15px rgba(255, 122, 0, 0.5);
            transform: translateX(4px);
            border-left: 3px solid transparent;
        }
        
        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .topbar h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
            color: var(--primary-color);
        }
        
        .topbar h1 span {
            color: var(--primary-color);
        }
        
        .user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
        }
        
        .user span {
            font-weight: 600;
            color: var(--text-light);
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        /* Notification Container */
        .notification-container {
            position: relative;
        }
        
        .notification-btn {
            position: relative;
            background: rgba(255, 122, 0, 0.1);
            border: 2px solid rgba(255, 122, 0, 0.3);
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--primary-color);
            font-size: 18px;
        }
        
        .notification-btn:hover {
            background: rgba(255, 122, 0, 0.2);
            border-color: var(--primary-color);
            transform: scale(1.1);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            border: 2px solid var(--bg-card);
            animation: pulse 2s infinite;
        }
        
        .notification-badge.hidden {
            display: none;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(244, 67, 54, 0.7);
            }
            50% {
                transform: scale(1.1);
                box-shadow: 0 0 0 8px rgba(244, 67, 54, 0);
            }
        }
        
        .notification-dropdown {
            position: absolute;
            top: 60px;
            right: 0;
            width: 350px;
            max-height: 500px;
            background: var(--bg-card);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
            overflow: hidden;
        }
        
        .notification-dropdown.active {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .notification-header {
            padding: 15px 20px;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-header h4 {
            color: var(--text-light);
            font-size: 16px;
            font-weight: 700;
        }
        
        .mark-all-read {
            background: transparent;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 12px;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .mark-all-read:hover {
            background: rgba(255, 122, 0, 0.1);
        }
        
        .notification-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .notification-item:hover {
            background: rgba(255, 122, 0, 0.05);
        }
        
        .notification-item.unread {
            background: rgba(255, 122, 0, 0.1);
            border-left: 3px solid var(--primary-color);
        }
        
        .notification-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            color: var(--text-light);
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .notification-text {
            color: var(--text-gray);
            font-size: 12px;
            line-height: 1.4;
        }
        
        .notification-time {
            color: var(--text-gray);
            font-size: 11px;
            margin-top: 5px;
        }
        
        .notification-empty {
            padding: 40px 20px;
            text-align: center;
            color: var(--text-gray);
            font-size: 14px;
        }
        
        /* Theme Toggle */
        .theme-toggle {
            background: rgba(255, 122, 0, 0.1);
            border: 2px solid rgba(255, 122, 0, 0.3);
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--primary-color);
            font-size: 18px;
        }
        
        .theme-toggle:hover {
            background: rgba(255, 122, 0, 0.2);
            border-color: var(--primary-color);
            transform: rotate(20deg) scale(1.1);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 25px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 122, 0, 0.3);
            box-shadow: 0 10px 25px rgba(255, 122, 0, 0.2);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 24px;
        }
        
        .stat-icon.reviews {
            background: rgba(33, 150, 243, 0.2);
            color: #2196f3;
        }
        
        .clickable-stat {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .clickable-stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .stat-icon.pending {
            background: rgba(255, 152, 0, 0.2);
            color: var(--warning-color);
        }
        
        .stat-icon.responded {
            background: rgba(76, 175, 80, 0.2);
            color: var(--success-color);
        }
        
        .stat-info h3 {
            font-size: 14px;
            color: var(--text-gray);
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            font-family: 'Orbitron', sans-serif;
        }
        
        /* Filters */
        .filters-section {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .filters-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-light);
        }
        
        .filter-select, .filter-input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-light);
            font-family: 'Poppins', sans-serif;
        }
        
        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        /* Style pour les options des select - texte noir sur fond blanc */
        .filter-select option {
            background: #ffffff !important;
            color: #000000 !important;
            padding: 10px;
        }
        
        /* Style global pour tous les select et leurs options dans le dashboard */
        select option {
            background: #ffffff !important;
            color: #000000 !important;
            padding: 8px 12px;
        }
        
        /* Pour les options au survol */
        select option:hover {
            background: #f0f0f0 !important;
            color: #000000 !important;
        }
        
        /* Pour les options sélectionnées */
        select option:checked {
            background: #e0e0e0 !important;
            color: #000000 !important;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .btn {
            padding: 12px 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 122, 0, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #d32f2f);
        }
        
        /* Reviews List */
        .reviews-section {
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 22px;
            color: var(--primary-color);
        }
        
        .section-header h2 span {
            color: var(--primary-color);
        }
        
        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        /* View Toggle Buttons */
        .view-toggle {
            display: flex;
            gap: 5px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 5px;
        }
        
        .view-btn {
            background: transparent;
            border: none;
            color: var(--text-gray);
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .view-btn:hover {
            color: var(--text-light);
            background: rgba(255, 122, 0, 0.1);
        }
        
        .view-btn.active {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(255, 122, 0, 0.3);
        }
        
        /* Kanban Board Styles */
        .kanban-board {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        
        .kanban-column {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 15px;
            min-height: 500px;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }
        
        /* Colonne "New" - Bleu/Cyan */
        .kanban-column[data-status="nouveau"] {
            border-color: rgba(33, 150, 243, 0.5);
            background: linear-gradient(135deg, rgba(13, 71, 161, 0.1) 0%, rgba(10, 10, 10, 0.95) 100%);
        }
        
        .kanban-column[data-status="nouveau"] .kanban-column-header {
            border-bottom-color: rgba(33, 150, 243, 0.3);
        }
        
        .kanban-column[data-status="nouveau"] .kanban-column-header h3 {
            color: #2196F3;
        }
        
        .kanban-column[data-status="nouveau"] .kanban-count {
            background: rgba(33, 150, 243, 0.2);
            color: #2196F3;
            border-color: rgba(33, 150, 243, 0.4);
        }
        
        /* Colonne "In Progress" - Orange */
        .kanban-column[data-status="en_cours"] {
            border-color: rgba(255, 152, 0, 0.5);
            background: linear-gradient(135deg, rgba(230, 81, 0, 0.1) 0%, rgba(10, 10, 10, 0.95) 100%);
        }
        
        .kanban-column[data-status="en_cours"] .kanban-column-header {
            border-bottom-color: rgba(255, 152, 0, 0.3);
        }
        
        .kanban-column[data-status="en_cours"] .kanban-column-header h3 {
            color: #FF9800;
        }
        
        .kanban-column[data-status="en_cours"] .kanban-count {
            background: rgba(255, 152, 0, 0.2);
            color: #FF9800;
            border-color: rgba(255, 152, 0, 0.4);
        }
        
        /* Colonne "Resolved" - Vert */
        .kanban-column[data-status="resolu"] {
            border-color: rgba(76, 175, 80, 0.5);
            background: linear-gradient(135deg, rgba(27, 94, 32, 0.1) 0%, rgba(10, 10, 10, 0.95) 100%);
        }
        
        .kanban-column[data-status="resolu"] .kanban-column-header {
            border-bottom-color: rgba(76, 175, 80, 0.3);
        }
        
        .kanban-column[data-status="resolu"] .kanban-column-header h3 {
            color: #4CAF50;
        }
        
        .kanban-column[data-status="resolu"] .kanban-count {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
            border-color: rgba(76, 175, 80, 0.4);
        }
        
        .kanban-column-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .kanban-column-header h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        
        .kanban-count {
            background: rgba(255, 122, 0, 0.2);
            color: var(--primary-color);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 700;
            border: 1px solid rgba(255, 122, 0, 0.3);
        }
        
        .kanban-column-content {
            flex: 1;
            overflow-y: auto;
            padding: 5px;
            min-height: 400px;
        }
        
        .kanban-card {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.8) 0%, rgba(10, 10, 10, 0.8) 100%);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: move;
            transition: all 0.3s ease;
            position: relative;
            user-select: none;
            border-left: 4px solid var(--border-color);
        }
        
        /* Carte "New" - Bordure bleue */
        .kanban-card[data-status="nouveau"] {
            border-left-color: #2196F3;
            background: linear-gradient(135deg, rgba(13, 71, 161, 0.15) 0%, rgba(20, 20, 20, 0.8) 100%);
        }
        
        .kanban-card[data-status="nouveau"]:hover {
            border-color: #2196F3;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(33, 150, 243, 0.3);
            background: linear-gradient(135deg, rgba(13, 71, 161, 0.25) 0%, rgba(20, 20, 20, 0.9) 100%);
        }
        
        .kanban-card[data-status="nouveau"] .kanban-card-title {
            color: #64B5F6;
        }
        
        /* Carte "In Progress" - Bordure orange */
        .kanban-card[data-status="en_cours"] {
            border-left-color: #FF9800;
            background: linear-gradient(135deg, rgba(230, 81, 0, 0.15) 0%, rgba(20, 20, 20, 0.8) 100%);
        }
        
        .kanban-card[data-status="en_cours"]:hover {
            border-color: #FF9800;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 152, 0, 0.3);
            background: linear-gradient(135deg, rgba(230, 81, 0, 0.25) 0%, rgba(20, 20, 20, 0.9) 100%);
        }
        
        .kanban-card[data-status="en_cours"] .kanban-card-title {
            color: #FFB74D;
        }
        
        /* Carte "Resolved" - Bordure verte */
        .kanban-card[data-status="resolu"] {
            border-left-color: #4CAF50;
            background: linear-gradient(135deg, rgba(27, 94, 32, 0.15) 0%, rgba(20, 20, 20, 0.8) 100%);
        }
        
        .kanban-card[data-status="resolu"]:hover {
            border-color: #4CAF50;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(76, 175, 80, 0.3);
            background: linear-gradient(135deg, rgba(27, 94, 32, 0.25) 0%, rgba(20, 20, 20, 0.9) 100%);
        }
        
        .kanban-card[data-status="resolu"] .kanban-card-title {
            color: #81C784;
        }
        
        .kanban-card:hover {
            transform: translateY(-2px);
        }
        
        .kanban-card.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
        }
        
        .kanban-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .kanban-card-title {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .kanban-card-email {
            color: var(--text-gray);
            font-size: 12px;
            margin-bottom: 10px;
        }
        
        .kanban-card-description {
            color: var(--text-light);
            font-size: 12px;
            line-height: 1.5;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .kanban-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--border-color);
        }
        
        .kanban-card-date {
            color: var(--text-gray);
            font-size: 11px;
        }
        
        .kanban-card-actions {
            display: flex;
            gap: 5px;
        }
        
        .kanban-card-actions button {
            background: rgba(255, 122, 0, 0.1);
            border: 1px solid rgba(255, 122, 0, 0.3);
            color: var(--primary-color);
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 11px;
            transition: all 0.3s ease;
        }
        
        .kanban-card-actions button:hover {
            background: rgba(255, 122, 0, 0.2);
            transform: scale(1.05);
        }
        
        .kanban-column.drag-over {
            border-color: var(--primary-color);
            background: rgba(255, 122, 0, 0.05);
            transform: scale(1.02);
        }
        
        /* Animation pour les colonnes */
        .kanban-column[data-status="nouveau"].drag-over {
            border-color: #2196F3;
            background: rgba(33, 150, 243, 0.1);
        }
        
        .kanban-column[data-status="en_cours"].drag-over {
            border-color: #FF9800;
            background: rgba(255, 152, 0, 0.1);
        }
        
        .kanban-column[data-status="resolu"].drag-over {
            border-color: #4CAF50;
            background: rgba(76, 175, 80, 0.1);
        }
        
        @media (max-width: 1024px) {
            .kanban-board {
                grid-template-columns: 1fr;
            }
        }
        
        .review-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
        }
        
        .review-card:hover {
            border-color: rgba(255, 122, 0, 0.3);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .review-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2196f3, #1976d2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .user-info h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .user-info p {
            font-size: 12px;
            color: var(--text-gray);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.status-nouveau {
            background: rgba(255, 122, 0, 0.2);
            color: #ff7a00;
            border: 1px solid rgba(255, 122, 0, 0.4);
        }
        
        .status-badge.status-en_cours {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.4);
        }
        
        .status-badge.status-resolu {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            border: 1px solid rgba(76, 175, 80, 0.4);
        }
        
        .review-content {
            margin-bottom: 20px;
        }
        
        .review-text {
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .review-date {
            font-size: 12px;
            color: var(--text-gray);
        }
        
        .review-response {
            background: rgba(255, 122, 0, 0.05);
            border-left: 3px solid var(--primary-color);
            padding: 15px;
            border-radius: 0 10px 10px 0;
            margin-top: 15px;
        }
        
        .response-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .response-header h5 {
            font-size: 14px;
            color: var(--primary-color);
        }
        
        .response-text {
            color: var(--text-light);
            line-height: 1.6;
        }
        
        /* Protection CSS absolue pour les images */
        .attachment-thumbnail,
        .attachment-thumbnail.persistent-image,
        img.attachment-thumbnail,
        img.persistent-image,
        img[class*="attachment"],
        img[data-type="image"] {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            width: auto !important;
            height: auto !important;
            max-width: 300px !important;
            max-height: 200px !important;
        }
        
        /* Empêcher tout masquage via CSS */
        .attachment-preview img,
        .image-container img,
        .review-card img.attachment-thumbnail {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        .attachment-thumbnail:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(255,122,0,0.4);
        }
        
        .attachment-preview,
        div.attachment-preview {
            display: block !important;
            visibility: visible !important;
            position: relative !important;
        }
        
        .image-container,
        div.image-container {
            display: block !important;
            visibility: visible !important;
            position: relative !important;
            min-height: 50px !important;
        }
        
        /* Protection absolue contre le masquage */
        .attachment-preview *,
        .image-container *,
        .attachment-thumbnail {
            pointer-events: auto !important;
        }
        
        #attachment-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        #attachment-modal .modal-close:hover {
            background: rgba(255,60,60,0.4);
            transform: rotate(90deg);
        }
        
        #attachment-modal img {
            max-width: 100%;
            max-height: 85vh;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        
        #attachment-modal video {
            max-width: 100%;
            max-height: 85vh;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        
        .review-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .action-btn {
            padding: 8px 15px;
            background: transparent;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        
        .action-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .action-btn.delete:hover {
            border-color: var(--danger-color);
            color: var(--danger-color);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            padding: 30px;
            position: relative;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 22px;
        }
        
        .modal-header h3 span {
            color: var(--primary-color);
        }
        
        .close-modal {
            background: none;
            border: none;
            color: var(--text-gray);
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .close-modal:hover {
            color: var(--primary-color);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-light);
        }
        
        .form-textarea {
            width: 100%;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-light);
            font-family: 'Poppins', sans-serif;
            resize: vertical;
            min-height: 120px;
        }
        
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(255, 122, 0, 0.2);
        }
        
        .form-textarea:invalid {
            border-color: #f44336;
        }
        
        #response-error {
            display: flex;
            align-items: center;
            gap: 5px;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        #response-char-count {
            transition: color 0.3s ease;
        }
        
        #response-text.error {
            border-color: #f44336 !important;
            box-shadow: 0 0 10px rgba(244, 67, 54, 0.3) !important;
        }
        
        #response-text.valid {
            border-color: #4caf50 !important;
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.2) !important;
        }
        
        .form-select {
            width: 100%;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-light);
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
        }
        
        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-select option {
            background: #ffffff !important;
            color: #000000 !important;
            padding: 10px;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* Footer */
        .site-footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            border-top: 1px solid var(--border-color);
            color: var(--text-gray);
        }
        
        .site-footer span {
            color: var(--primary-color);
        }
        
        /* Responsive
           NOTE : on garde désormais le sidebar en mode "ouvert" même sur mobile.
           Si tu veux de nouveau le mode compact, remets width: 70px et masque les <span>. */
        @media (max-width: 1024px) {
            .sidebar {
                width: 250px; /* même largeur que sur desktop */
            }
            
            .sidebar h2,
            .sidebar a span {
                display: inline; /* garder le titre et les textes de menu visibles */
            }
            
            .main {
                margin-left: 250px; /* aligné avec la largeur du sidebar */
            }
            
            .sidebar a {
                text-align: left;
                padding: 15px 25px;
            }
            
            .sidebar a i {
                margin-right: 10px;
                font-size: 16px;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .review-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .review-actions {
                flex-wrap: wrap;
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .main {
                padding: 15px;
            }
            
            .filters-row {
                flex-direction: column;
            }
            
            .filter-actions {
                width: 100%;
            }
            
            .btn {
                flex: 1;
                justify-content: center;
            }
            
            .chart-section > div[style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
            }
            
            .chart-section canvas {
                max-height: 250px !important;
            }
        }
        
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Identique au Dashboard -->
        <!-- Sidebar identique à dashboard.php -->
        <div class="sidebar">
            <img src="../images/Nine__1_-removebg-preview.png" alt="Nine Tailed Fox Logo" class="dashboard-logo">
            <h2>Dashboard</h2>
            <a href="dashboard.php">Overview</a>
            <a href="#">Users</a>
            <a href="#">Shop</a>
            <a href="#">Trade History</a>
            <a href="#">Events</a>
            <a href="#">News</a>
            <!-- Lien Support actif vers cette page -->
            <a href="reclamback.php" class="active"><i class="fas fa-headset"></i> <span>Support</span></a>
            <a href="evaluations_publiques.php"><i class="fas fa-star"></i> <span>Évaluations Publiques</span></a>
            <a href="../front/indexf.html"><i class="fas fa-home"></i> <span>Homepage</span></a>
        </div>

        <!-- Main Content -->
        <div class="main">
            <div class="topbar">
                <h1>Dashboard <span>Support</span></h1>
                <div class="topbar-right">
                    <!-- Notification Bell avec Badge -->
                    <div class="notification-container" id="notification-container">
                        <button class="notification-btn" id="notification-btn" title="Notifications">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" id="notification-badge">0</span>
                        </button>
                        <div class="notification-dropdown" id="notification-dropdown">
                            <div class="notification-header">
                                <h4>Notifications</h4>
                                <button class="mark-all-read" id="mark-all-read">Tout marquer comme lu</button>
                            </div>
                            <div class="notification-list" id="notification-list">
                                <div class="notification-empty">Aucune nouvelle réclamation</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Toggle Mode Sombre/Clair -->
                    <button class="theme-toggle" id="theme-toggle" title="Basculer le thème">
                        <i class="fas fa-moon" id="theme-icon"></i>
                    </button>
                    
                    <!-- Bouton de test du son (temporaire pour debug) -->
                    <button class="theme-toggle" id="test-sound-btn" title="Tester le son de notification" style="background: rgba(76, 175, 80, 0.1); border-color: rgba(76, 175, 80, 0.3); color: #4caf50;">
                        <i class="fas fa-volume-up"></i>
                    </button>
                    
                    <div class="user">
                        <img src="../images/meriem.png" alt="User Avatar">
                        <span>FoxLeader</span>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card clickable-stat" data-status-filter="all" style="cursor: pointer;" title="Click to show all requests">
                    <div class="stat-icon reviews">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Requests</h3>
                        <div class="stat-value"><?php echo $totalReclamations; ?></div>
                    </div>
                </div>
                
                <div class="stat-card clickable-stat" data-status-filter="nouveau" style="cursor: pointer;" title="Click to filter by New">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>New</h3>
                        <div class="stat-value"><?php echo $nouveauCount; ?></div>
                    </div>
                </div>
                
                <div class="stat-card clickable-stat" data-status-filter="en_cours" style="cursor: pointer;" title="Click to filter by In Progress">
                    <div class="stat-icon responded">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-info">
                        <h3>In Progress</h3>
                        <div class="stat-value"><?php echo $enCoursCount; ?></div>
                    </div>
                </div>
                
                <div class="stat-card clickable-stat" data-status-filter="resolu" style="cursor: pointer;" title="Click to filter by Resolved">
                    <div class="stat-icon rating">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Resolved</h3>
                        <div class="stat-value"><?php echo $resoluCount; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Filters and Sort -->
            <div class="filters-section">
                <div class="filters-row">
                    <div class="filter-group">
                        <label class="filter-label">Filter by Status</label>
                        <select class="filter-select" id="status-filter">
                            <option value="all" <?php echo (!isset($_GET['status_filter']) || $_GET['status_filter'] === 'all') ? 'selected' : ''; ?>>All Requests</option>
                            <option value="nouveau" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] === 'nouveau') ? 'selected' : ''; ?>>New</option>
                            <option value="en_cours" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] === 'en_cours') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolu" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] === 'resolu') ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Filter by Date</label>
                        <select class="filter-select" id="date-filter">
                            <option value="all" <?php echo (!isset($_GET['date_filter']) || $_GET['date_filter'] === 'all') ? 'selected' : ''; ?>>All Dates</option>
                            <option value="today" <?php echo (isset($_GET['date_filter']) && $_GET['date_filter'] === 'today') ? 'selected' : ''; ?>>Today</option>
                            <option value="week" <?php echo (isset($_GET['date_filter']) && $_GET['date_filter'] === 'week') ? 'selected' : ''; ?>>This Week</option>
                            <option value="month" <?php echo (isset($_GET['date_filter']) && $_GET['date_filter'] === 'month') ? 'selected' : ''; ?>>This Month</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Filter by Category</label>
                        <select class="filter-select" id="categorie-filter">
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($_GET['categorie_filter']) && $_GET['categorie_filter'] === $key) || (!isset($_GET['categorie_filter']) && $key === 'all') ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button class="btn" id="apply-filters">
                            <i class="fas fa-filter"></i> Apply
                        </button>
                        <button class="btn btn-outline" id="reset-filters">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Messages de succès/erreur -->
            <?php if ($successMessage): ?>
                <div style="background: rgba(76, 175, 80, 0.2); border: 1px solid rgba(76, 175, 80, 0.4); color: #4caf50; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $successMessage; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($errorMessage): ?>
                <div style="background: rgba(220, 53, 69, 0.2); border: 1px solid rgba(220, 53, 69, 0.4); color: #dc3545; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $errorMessage; ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Support Requests List -->
            <div class="reviews-section" style="margin-top: 30px;">
                <div class="section-header">
                    <h2>All <span>Requests</span></h2>
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <div class="review-count">
                            Showing <span id="display-count"><?php echo count($allReclamations); ?></span> of <span id="total-count"><?php echo $totalReclamations; ?></span> requests
                            <?php if ((isset($_GET['status_filter']) && $_GET['status_filter'] !== 'all') || (isset($_GET['date_filter']) && $_GET['date_filter'] !== 'all') || (isset($_GET['categorie_filter']) && $_GET['categorie_filter'] !== 'all')): ?>
                                <span style="margin-left: 10px; color: var(--primary-color); font-size: 12px;">
                                    <i class="fas fa-filter"></i> Filters active
                                </span>
                            <?php endif; ?>
                        </div>
                        <!-- Toggle Vue Liste / Kanban -->
                        <div class="view-toggle">
                            <button class="view-btn active" id="list-view-btn" title="Vue Liste">
                                <i class="fas fa-list"></i> Liste
                            </button>
                            <button class="view-btn" id="kanban-view-btn" title="Vue Kanban">
                                <i class="fas fa-columns"></i> Kanban
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Vue Liste (par défaut) -->
                <div class="reviews-list" id="reviews-list">
                    <?php if (empty($allReclamations)): ?>
                        <div class="review-card">
                            <p style="text-align: center; color: var(--text-gray); padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 20px; display: block; opacity: 0.5;"></i>
                                No support requests yet.
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($allReclamations as $reclamation): ?>
                            <div class="review-card" data-status="<?php echo $reclamation['statut']; ?>" data-date="<?php echo $reclamation['date_creation']; ?>">
                                <div class="review-header">
                                    <div class="review-user">
                                        <div class="user-avatar-small"><?php echo strtoupper(substr($reclamation['email'] ?? 'U', 0, 2)); ?></div>
                                        <div class="user-info">
                                            <h4><?php echo htmlspecialchars($reclamation['email'] ?? 'Utilisateur'); ?></h4>
                                            <p><?php echo htmlspecialchars($reclamation['email'] ?? ''); ?></p>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="status-badge status-<?php echo $reclamation['statut'] ?? 'nouveau'; ?>">
                                            <?php 
                                            $status = $reclamation['statut'] ?? 'nouveau';
                                            $statusText = [
                                                'nouveau' => 'New',
                                                'en_cours' => 'In Progress',
                                                'resolu' => 'Resolved',
                                                'pending' => 'Pending' // Pour compatibilité avec les anciennes données
                                            ];
                                            echo $statusText[$status] ?? $status;
                                            ?>
                                        </span>
                                        <?php
                                        // Afficher le badge de satisfaction si la réclamation est résolue et évaluée
                                        if ($status === 'resolu') {
                                            $satisfaction = $satisfactionController->getSatisfactionByReclamationId($reclamation['id_reclamation']);
                                            if ($satisfaction && $satisfaction->getRating() >= 4) {
                                                echo '<span class="status-badge" style="background: rgba(76, 175, 80, 0.2); color: #4caf50; border: 1px solid rgba(76, 175, 80, 0.4); margin-left: 8px;">
                                                    <i class="fas fa-star"></i> Résolu avec satisfaction
                                                </span>';
                                            }
                                            if ($satisfaction) {
                                                echo '<div style="margin-top: 5px; font-size: 12px; color: var(--text-gray);">
                                                    <i class="fas fa-star" style="color: #ffc107;"></i> ' . $satisfaction->getRating() . '/5';
                                                if ($satisfaction->getCommentaire()) {
                                                    echo ' - ' . htmlspecialchars(substr($satisfaction->getCommentaire(), 0, 50)) . (strlen($satisfaction->getCommentaire()) > 50 ? '...' : '');
                                                }
                                                echo '</div>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="review-content">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <h5 style="color: var(--primary-color); margin: 0;"><?php echo htmlspecialchars($reclamation['sujet'] ?? ''); ?></h5>
                                        <?php if (!empty($reclamation['categorie']) && strtolower($reclamation['categorie']) !== 'other'): ?>
                                            <span style="background: rgba(255, 122, 0, 0.2); color: #ff7a00; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; border: 1px solid rgba(255, 122, 0, 0.3);">
                                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($reclamation['categorie']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="review-text">
                                        <?php echo htmlspecialchars($reclamation['description'] ?? ''); ?>
                                    </p>
                                    
                                    <?php 
                                    // Vérifier et afficher les pièces jointes
                                    $pieceJointe = isset($reclamation['piece_jointe']) ? trim($reclamation['piece_jointe']) : '';
                                    if (!empty($pieceJointe)): 
                                        // Utiliser un chemin absolu avec timestamp pour éviter le cache
                                        // Le fichier est stocké comme "uploads/reclamations/filename.ext"
                                        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                                        
                                        // Construire le chemin absolu
                                        if (strpos($pieceJointe, 'uploads/') === 0) {
                                            $filePathAbsolute = $baseUrl . '/foxunity/' . $pieceJointe;
                                        } else {
                                            $filePathAbsolute = $baseUrl . '/foxunity/uploads/reclamations/' . basename($pieceJointe);
                                        }
                                        
                                        // Ajouter un timestamp pour éviter le cache et forcer le rechargement
                                        $timestamp = '?t=' . time();
                                        $filePath = $filePathAbsolute . $timestamp;
                                        $filePathFallback = $filePathAbsolute;
                                        
                                        $fileExt = strtolower(pathinfo($pieceJointe, PATHINFO_EXTENSION));
                                        $isImage = in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                        $isVideo = in_array($fileExt, ['mp4', 'mov', 'avi', 'webm', 'mkv']);
                                    ?>
                                        <div class="attachment-preview" style="margin-top: 15px; padding: 15px; background: rgba(255,122,0,0.05); border: 1px solid rgba(255,122,0,0.2); border-radius: 8px; display: block !important; visibility: visible !important; position: relative !important;">
                                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                                <i class="fas fa-paperclip" style="color: #ff7a00;"></i>
                                                <strong style="color: #ff7a00;">Attachment:</strong>
                                            </div>
                                            <?php if ($isImage): ?>
                                                <div class="image-container" data-image-id="<?php echo $reclamation['id_reclamation']; ?>" style="display: block !important; visibility: visible !important; position: relative !important; min-height: 50px !important;">
                                                    <!-- Image principale avec protection maximale -->
                                                    <img src="<?php echo htmlspecialchars($filePath); ?>" 
                                                         alt="Attachment" 
                                                         class="attachment-thumbnail persistent-image"
                                                         id="img-<?php echo $reclamation['id_reclamation']; ?>"
                                                         data-type="image"
                                                         data-src="<?php echo htmlspecialchars($filePath); ?>"
                                                         data-src-fallback="<?php echo htmlspecialchars($filePathFallback); ?>"
                                                         data-original-src="<?php echo htmlspecialchars($filePath); ?>"
                                                         data-reclamation-id="<?php echo $reclamation['id_reclamation']; ?>"
                                                         style="max-width: 300px !important; max-height: 200px !important; border-radius: 8px !important; cursor: pointer !important; border: 2px solid rgba(255,122,0,0.3) !important; transition: transform 0.3s ease !important; display: block !important; visibility: visible !important; opacity: 1 !important; position: relative !important; width: auto !important; height: auto !important; min-width: 50px !important; min-height: 50px !important;"
                                                         onload="
                                                             var img = this;
                                                             console.log('Image chargée:', img.src);
                                                             img.style.cssText = 'max-width: 300px !important; max-height: 200px !important; border-radius: 8px !important; cursor: pointer !important; border: 2px solid rgba(255,122,0,0.3) !important; display: block !important; visibility: visible !important; opacity: 1 !important; position: relative !important; width: auto !important; height: auto !important;';
                                                             if(img.parentElement) img.parentElement.style.cssText = 'display: block !important; visibility: visible !important; position: relative !important; min-height: 50px !important;';
                                                             img.setAttribute('data-loaded', 'true');
                                                             // Forcer le re-vérification
                                                             setTimeout(function() {
                                                                 if(img.style.display === 'none' || img.style.visibility === 'hidden') {
                                                                     img.style.cssText = 'max-width: 300px !important; max-height: 200px !important; border-radius: 8px !important; cursor: pointer !important; border: 2px solid rgba(255,122,0,0.3) !important; display: block !important; visibility: visible !important; opacity: 1 !important; position: relative !important; width: auto !important; height: auto !important;';
                                                                 }
                                                             }, 100);
                                                         "
                                                         onerror="
                                                             var img = this;
                                                             console.error('Erreur chargement image:', img.src);
                                                             var fallback = img.getAttribute('data-src-fallback');
                                                             if (fallback && img.src !== fallback) {
                                                                 console.log('Tentative avec chemin absolu:', fallback);
                                                                 img.src = fallback;
                                                             } else {
                                                                 img.style.setProperty('display', 'none', 'important');
                                                                 if(img.nextElementSibling) img.nextElementSibling.style.setProperty('display', 'block', 'important');
                                                             }
                                                         "
                                                         onclick="openAttachmentModal(this)">
                                                    <!-- Fallback si l'image ne charge pas -->
                                                    <noscript>
                                                        <img src="<?php echo htmlspecialchars($filePathFallback); ?>" 
                                                             alt="Attachment" 
                                                             style="max-width: 300px; max-height: 200px; border-radius: 8px; border: 2px solid rgba(255,122,0,0.3);">
                                                    </noscript>
                                                    <div style="display: none; color: #ff7a00; padding: 10px; background: rgba(255,122,0,0.1); border-radius: 8px;">
                                                        <i class="fas fa-exclamation-triangle"></i> Image non disponible
                                                        <a href="<?php echo htmlspecialchars($filePathFallback); ?>" target="_blank" style="color: #ff7a00; margin-left: 10px;">
                                                            <i class="fas fa-external-link-alt"></i> Ouvrir directement
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php elseif ($isVideo): ?>
                                                <video controls 
                                                       class="attachment-thumbnail"
                                                       data-type="video"
                                                       data-src="<?php echo htmlspecialchars($filePath); ?>"
                                                       style="max-width: 500px; max-height: 300px; border-radius: 8px; border: 2px solid rgba(255,122,0,0.3); cursor: pointer;"
                                                       onclick="openAttachmentModal(this)">
                                                    <source src="<?php echo htmlspecialchars($filePath); ?>" type="video/<?php echo $fileExt; ?>">
                                                    Your browser does not support the video tag.
                                                </video>
                                            <?php else: ?>
                                                <a href="<?php echo htmlspecialchars($filePath); ?>" target="_blank" style="color: #ff7a00; text-decoration: none;">
                                                    <i class="fas fa-download"></i> Download attachment
                                                </a>
                                            <?php endif; ?>
                                            <div style="margin-top: 10px;">
                                                <a href="<?php echo htmlspecialchars($filePath); ?>" target="_blank" 
                                                   style="color: #ff7a00; text-decoration: none; font-size: 12px;">
                                                    <i class="fas fa-external-link-alt"></i> Open in new tab
                                                </a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="review-date">
                                        <i class="far fa-calendar"></i> Posted on <?php echo date('M j, Y H:i', strtotime($reclamation['date_creation'])); ?>
                                    </div>
                                </div>
                                
                                <!-- Affichage des réponses existantes -->
                                <?php 
                                // Utiliser DIRECTEMENT les réponses pré-chargées depuis la boucle principale
                                $reclamationId = $reclamation['id_reclamation'];
                                
                                // Utiliser directement les réponses pré-chargées dans la boucle principale
                                $reclamationResponses = isset($reclamation['responses']) && is_array($reclamation['responses']) ? $reclamation['responses'] : [];
                                
                                // Si vide, essayer de recharger une fois
                                if (empty($reclamationResponses)) {
                                    $reclamationResponses = getResponsesForReclamation($reclamationId, $responseController);
                                }
                                
                                // Vérifier que c'est bien un tableau non vide
                                $hasResponses = !empty($reclamationResponses) && is_array($reclamationResponses) && count($reclamationResponses) > 0;
                                
                                if ($hasResponses): ?>
                                    <div class="responses-section" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                                        <h6 style="color: var(--primary-color); margin-bottom: 15px; font-size: 14px; font-weight: 600;">
                                            <i class="fas fa-reply"></i> Réponses (<?php echo count($reclamationResponses); ?>)
                                        </h6>
                                        <?php 
                                        foreach ($reclamationResponses as $index => $response):
                                            // Normaliser les données de la réponse pour gérer les différents noms de colonnes
                                            $responseId = $response['id_reponse'] ?? $response['id_response'] ?? $response['response_id'] ?? '';
                                            
                                            // Récupérer le message - vérifier plusieurs noms de colonnes possibles
                                            $responseMessage = '';
                                            if (isset($response['message']) && !empty(trim($response['message']))) {
                                                $responseMessage = $response['message'];
                                            } elseif (isset($response['response_text']) && !empty(trim($response['response_text']))) {
                                                $responseMessage = $response['response_text'];
                                            } elseif (isset($response['texte']) && !empty(trim($response['texte']))) {
                                                $responseMessage = $response['texte'];
                                            } else {
                                                $responseMessage = 'Aucun message';
                                            }
                                            
                                            $responseDate = $response['date_reponse'] ?? $response['response_date'] ?? $response['date_creation'] ?? 'now';
                                            
                                            // Debug: vérifier que le message est bien présent
                                            if (empty($responseMessage) || $responseMessage === 'Aucun message') {
                                                error_log("⚠️ Réponse ID $responseId - Message vide ou non trouvé. Données: " . print_r($response, true));
                                            }
                                        ?>
                                            <div class="response-item" style="background: rgba(255, 122, 0, 0.05); border-left: 3px solid var(--primary-color); padding: 15px; margin-bottom: 10px; border-radius: 8px;">
                                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                                    <div>
                                                        <strong style="color: var(--primary-color);">Admin</strong>
                                                        <span style="color: var(--text-gray); font-size: 12px; margin-left: 10px;">
                                                            <?php 
                                                            try {
                                                                $dateObj = new DateTime($responseDate);
                                                                echo $dateObj->format('d/m/Y à H:i');
                                                            } catch (Exception $e) {
                                                                echo date('d/m/Y à H:i');
                                                            }
                                                            ?>
                                                        </span>
                                                    </div>
                                                    <div style="display: flex; gap: 5px;">
                                                        <button class="action-btn edit-response-btn" 
                                                                data-id="<?php echo htmlspecialchars($responseId); ?>"
                                                                data-text="<?php echo htmlspecialchars($responseMessage); ?>"
                                                                data-reclamation="<?php echo $reclamation['id_reclamation']; ?>"
                                                                data-admin="Admin"
                                                                style="padding: 4px 8px; font-size: 11px; background: rgba(255, 193, 7, 0.2); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.3);">
                                                            <i class="fas fa-edit"></i> Modifier
                                                        </button>
                                                        <a href="reclamback.php?delete_response_id=<?php echo htmlspecialchars($responseId); ?>" 
                                                           class="action-btn delete" 
                                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réponse?');"
                                                           style="padding: 4px 8px; font-size: 11px; text-decoration: none; background: rgba(244, 67, 54, 0.2); color: #f44336; border: 1px solid rgba(244, 67, 54, 0.3);">
                                                            <i class="fas fa-trash"></i> Supprimer
                                                        </a>
                                                    </div>
                                                </div>
                                                <div style="color: var(--text-light); margin: 0; line-height: 1.6; white-space: pre-wrap; word-wrap: break-word;">
                                                    <?php 
                                                    // Afficher le message avec préservation des sauts de ligne
                                                    echo nl2br(htmlspecialchars($responseMessage, ENT_QUOTES, 'UTF-8')); 
                                                    ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <!-- Message informatif si aucune réponse -->
                                    <div style="margin-top: 20px; padding: 10px; background: rgba(255, 122, 0, 0.05); border-left: 3px solid var(--primary-color); border-radius: 8px;">
                                        <p style="color: var(--text-gray); font-size: 12px; margin: 0;">
                                            <i class="fas fa-info-circle"></i> Aucune réponse pour le moment
                                        </p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="review-actions">
                                    <button class="action-btn add-response-btn" data-id="<?php echo $reclamation['id_reclamation']; ?>">
                                        <i class="fas fa-reply"></i> Add Response
                                    </button>
                                    <button class="action-btn view-request" data-id="<?php echo $reclamation['id_reclamation']; ?>">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>
                                    <button class="action-btn edit-status" data-id="<?php echo $reclamation['id_reclamation']; ?>" data-status="<?php echo $reclamation['statut']; ?>">
                                        <i class="fas fa-edit"></i> Update Status
                                    </button>
                                    <button class="action-btn delete" data-id="<?php echo $reclamation['id_reclamation']; ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Vue Kanban (cachée par défaut) -->
                <div class="kanban-board" id="kanban-board" style="display: none;">
                    <div class="kanban-column" data-status="nouveau">
                        <div class="kanban-column-header">
                            <h3><i class="fas fa-clock"></i> New</h3>
                            <span class="kanban-count" id="kanban-count-nouveau">0</span>
                        </div>
                        <div class="kanban-column-content" id="kanban-nouveau">
                            <!-- Les cartes seront ajoutées ici par JavaScript -->
                        </div>
                    </div>
                    
                    <div class="kanban-column" data-status="en_cours">
                        <div class="kanban-column-header">
                            <h3><i class="fas fa-spinner"></i> In Progress</h3>
                            <span class="kanban-count" id="kanban-count-en_cours">0</span>
                        </div>
                        <div class="kanban-column-content" id="kanban-en_cours">
                            <!-- Les cartes seront ajoutées ici par JavaScript -->
                        </div>
                    </div>
                    
                    <div class="kanban-column" data-status="resolu">
                        <div class="kanban-column-header">
                            <h3><i class="fas fa-check-circle"></i> Resolved</h3>
                            <span class="kanban-count" id="kanban-count-resolu">0</span>
                        </div>
                        <div class="kanban-column-content" id="kanban-resolu">
                            <!-- Les cartes seront ajoutées ici par JavaScript -->
                        </div>
                    </div>
                </div>
            </div>

            <footer class="site-footer">
                © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
            </footer>
        </div>
    </div>
    
    <!-- Response Modal -->
    <div class="modal" id="response-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="response-modal-title">Add <span>Response</span></h3>
                <button class="close-modal">&times;</button>
            </div>
            
            <div class="review-preview" id="review-preview">
                <!-- Review content will be inserted here -->
            </div>
            
            <form id="response-form" method="POST" action="reclamback.php">
                <input type="hidden" name="id_reclamation" id="response-reclamation-id" value="">
                <input type="hidden" name="id_response" id="response-id" value="">
                <input type="hidden" name="admin_name" id="response-admin-name" value="Admin">
                <input type="hidden" name="add_response" id="add-response-flag" value="1">
                <input type="hidden" name="edit_response" id="edit-response-flag" value="0">
                
                <div class="form-group">
                    <label class="form-label">Votre réponse <span style="color: var(--primary-color);">*</span></label>
                    <textarea class="form-textarea" id="response-text" name="response_text" placeholder="Tapez votre réponse ici..." required minlength="10" maxlength="5000"></textarea>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                        <small id="response-error" style="color: #f44336; display: none; font-size: 12px;">
                            <i class="fas fa-exclamation-circle"></i> <span id="response-error-text"></span>
                        </small>
                        <small id="response-char-count" style="color: var(--text-gray); font-size: 12px; text-align: right; margin-left: auto;">
                            <span id="response-char-current">0</span> / <span id="response-char-max">5000</span> caractères
                        </small>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" id="cancel-response">Annuler</button>
                    <button type="submit" class="btn" id="submit-response">Submit Response</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- View Details Modal -->
    <div class="modal" id="view-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Request <span>Details</span></h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body" id="view-modal-body">
                <p style="text-align: center; color: var(--primary-color);">
                    <i class="fas fa-spinner fa-spin"></i> Chargement...
                </p>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div class="modal" id="status-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update <span>Status</span></h3>
                <button class="close-modal">&times;</button>
            </div>
            <form id="status-form" method="POST" action="">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="id_reclamation" id="status-reclamation-id">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="statut" class="form-select" id="status-select" required>
                        <option value="nouveau">New</option>
                        <option value="en_cours">In Progress</option>
                        <option value="resolu">Resolved</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" id="cancel-status">Annuler</button>
                    <button type="submit" class="btn" id="submit-status">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal" id="delete-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmer la <span>Suppression</span></h3>
                <button class="close-modal">&times;</button>
            </div>
            
            <p>Are you sure you want to delete this request? This action is irreversible.</p>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" id="cancel-delete">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirm-delete">Supprimer</button>
            </div>
        </div>
    </div>
    
    <!-- Attachment Modal -->
    <div id="attachment-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 90vw; max-height: 90vh; background: rgba(10,10,10,0.98); border: 2px solid rgba(255,122,0,0.3); border-radius: 15px; padding: 20px; position: relative;">
            <button class="modal-close" onclick="closeAttachmentModal()" style="position: absolute; top: 15px; right: 15px; background: rgba(255,60,60,0.2); color: #ff3c3c; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 20px; display: flex; align-items: center; justify-content: center; z-index: 10; transition: all 0.3s ease;">
                <i class="fas fa-times"></i>
            </button>
            <div id="attachment-modal-content" style="display: flex; align-items: center; justify-content: center; min-height: 400px;">
                <!-- Content will be inserted here -->
            </div>
            <div style="text-align: center; margin-top: 15px;">
                <a id="attachment-download-link" href="#" target="_blank" style="color: #ff7a00; text-decoration: none; font-size: 14px;">
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Attendre que le DOM soit complètement chargé
        document.addEventListener('DOMContentLoaded', function() {
        
        // Modal functionality
        const responseModal = document.getElementById('response-modal');
        const viewModal = document.getElementById('view-modal');
        const statusModal = document.getElementById('status-modal');
        const deleteModal = document.getElementById('delete-modal');
        const closeModalButtons = document.querySelectorAll('.close-modal');
        const cancelResponse = document.getElementById('cancel-response');
        const cancelDelete = document.getElementById('cancel-delete');
        const cancelStatus = document.getElementById('cancel-status');
        const responseForm = document.getElementById('response-form');
        
        // Open response modal - Ajouter une réponse
        document.querySelectorAll('.add-response-btn').forEach(button => {
            button.addEventListener('click', function() {
                const reclamationId = this.getAttribute('data-id');
                const reviewCard = this.closest('.review-card');
                
                // Populate review preview
                const user = reviewCard.querySelector('.user-info h4').textContent;
                const subject = reviewCard.querySelector('.review-content h5').textContent;
                const text = reviewCard.querySelector('.review-text').textContent;
                
                document.getElementById('review-preview').innerHTML = `
                    <div class="review-card" style="margin-bottom: 20px; border: 1px solid var(--border-color); padding: 15px;">
                        <div class="review-header">
                            <div class="review-user">
                                <div class="user-info">
                                    <h4>${user}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="review-content">
                            <h5 style="color: var(--primary-color); margin-bottom: 10px;">${subject}</h5>
                            <p class="review-text">${text}</p>
                        </div>
                    </div>
                `;
                
                // Set form data for adding
                if (!reclamationId || reclamationId <= 0) {
                    alert('Error: Invalid request ID.');
                    console.error('Invalid request ID:', reclamationId);
                    return;
                }
                
                // Convertir en nombre pour s'assurer que c'est un entier
                const reclamationIdNum = parseInt(reclamationId, 10);
                if (isNaN(reclamationIdNum) || reclamationIdNum <= 0) {
                    alert('Error: Invalid request ID format.');
                    console.error('Invalid request ID format:', reclamationId);
                    return;
                }
                
                // Vérifier que tous les éléments existent
                const reclamationIdField = document.getElementById('response-reclamation-id');
                const responseIdField = document.getElementById('response-id');
                const addFlagField = document.getElementById('add-response-flag');
                const editFlagField = document.getElementById('edit-response-flag');
                
                if (!reclamationIdField || !addFlagField || !editFlagField) {
                    console.error('❌ Erreur: Éléments du formulaire non trouvés');
                    alert('Erreur: Formulaire non initialisé correctement. Veuillez recharger la page.');
                    return;
                }
                
                reclamationIdField.value = reclamationIdNum;
                if (responseIdField) responseIdField.value = '';
                document.getElementById('response-admin-name').value = 'Admin';
                addFlagField.value = '1';
                editFlagField.value = '0';
                document.getElementById('response-modal-title').innerHTML = 'Add <span>Response</span>';
                document.getElementById('submit-response').innerHTML = '<i class="fas fa-paper-plane"></i> Submit Response';
                
                // Clear textarea and reset validation
                const textarea = document.getElementById('response-text');
                textarea.value = '';
                textarea.classList.remove('error', 'valid');
                
                // Reset error message and character count
                hideResponseError();
                document.getElementById('response-char-current').textContent = '0';
                document.getElementById('response-char-current').style.color = 'var(--text-gray)';
                
                // Re-enable submit button
                const submitBtn = document.getElementById('submit-response');
                submitBtn.disabled = false;
                
                // Debug
                console.log('=== ADD RESPONSE MODAL OPENED ===');
                console.log('✅ Request ID:', reclamationIdNum, '(type:', typeof reclamationIdNum, ')');
                console.log('✅ Add flag set to:', addFlagField.value);
                console.log('✅ Edit flag set to:', editFlagField.value);
                console.log('✅ Form configured for adding');
                console.log('✅ Form action:', document.getElementById('response-form').action);
                console.log('✅ Form method:', document.getElementById('response-form').method);
                
                // Show modal
                responseModal.classList.add('active');
            });
        });
        
        // Open response modal - Modifier une réponse
        document.querySelectorAll('.edit-response-btn').forEach(button => {
            button.addEventListener('click', function() {
                const responseId = this.getAttribute('data-id');
                const responseText = this.getAttribute('data-text');
                const reclamationId = this.getAttribute('data-reclamation');
                const reviewCard = this.closest('.review-card');
                
                // Populate review preview
                const user = reviewCard.querySelector('.user-info h4').textContent;
                const subject = reviewCard.querySelector('.review-content h5').textContent;
                const text = reviewCard.querySelector('.review-text').textContent;
                
                document.getElementById('review-preview').innerHTML = `
                    <div class="review-card" style="margin-bottom: 20px; border: 1px solid var(--border-color); padding: 15px;">
                        <div class="review-header">
                            <div class="review-user">
                                <div class="user-info">
                                    <h4>${user}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="review-content">
                            <h5 style="color: var(--primary-color); margin-bottom: 10px;">${subject}</h5>
                            <p class="review-text">${text}</p>
                        </div>
                    </div>
                `;
                
                // Set form data for editing
                const adminName = this.getAttribute('data-admin') || 'Admin';
                document.getElementById('response-reclamation-id').value = reclamationId;
                document.getElementById('response-id').value = responseId;
                document.getElementById('response-admin-name').value = adminName;
                document.getElementById('add-response-flag').value = '0';
                document.getElementById('edit-response-flag').value = '1';
                document.getElementById('response-modal-title').innerHTML = 'Modifier la <span>Réponse</span>';
                document.getElementById('submit-response').innerHTML = '<i class="fas fa-save"></i> Enregistrer les modifications';
                
                // Fill textarea with existing response (décoder les entités HTML)
                const textarea = document.getElementById('response-text');
                const decodedText = responseText.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&').replace(/&quot;/g, '"');
                textarea.value = decodedText;
                textarea.classList.remove('error', 'valid');
                
                // Reset error message and update character count
                hideResponseError();
                const charCount = decodedText.length;
                document.getElementById('response-char-current').textContent = charCount;
                if (charCount > 4500) {
                    document.getElementById('response-char-current').style.color = '#f44336';
                } else if (charCount > 3500) {
                    document.getElementById('response-char-current').style.color = '#ff9800';
                } else {
                    document.getElementById('response-char-current').style.color = 'var(--text-gray)';
                }
                
                // Re-enable submit button
                const submitBtn = document.getElementById('submit-response');
                submitBtn.disabled = false;
                
                // Show modal
                responseModal.classList.add('active');
            });
        });
        
        // Open delete modal (only for reclamation deletion, not response deletion)
        document.querySelectorAll('.review-actions .action-btn.delete').forEach(button => {
            button.addEventListener('click', function(e) {
                // Only handle if it's not a response delete link
                if (!this.closest('.response-item')) {
                    e.preventDefault();
                    const requestId = this.getAttribute('data-id');
                    deleteModal.setAttribute('data-id', requestId);
                    deleteModal.classList.add('active');
                }
            });
        });
        
        // View Details functionality
        document.querySelectorAll('.view-request').forEach(button => {
            button.addEventListener('click', function() {
                const reclamationId = this.getAttribute('data-id');
                const modalBody = document.getElementById('view-modal-body');
                
                // Show loading
                modalBody.innerHTML = '<p style="text-align: center; color: var(--primary-color);"><i class="fas fa-spinner fa-spin"></i> Chargement...</p>';
                viewModal.classList.add('active');
                
                // Fetch reclamation details
                fetch('reclamback.php?view_id=' + reclamationId + '&ajax=1')
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            modalBody.innerHTML = '<p style="color: #f44336;">Error: ' + data.error + '</p>';
                            return;
                        }
                        
                        const statusText = {
                            'nouveau': 'New',
                            'en_cours': 'In Progress',
                            'resolu': 'Resolved',
                            'pending': 'Pending' // Pour compatibilité
                        };
                        
                        let responsesHtml = '';
                        console.log('🔍 Données reçues pour réclamation ID:', reclamationId);
                        console.log('🔍 Objet data complet:', data);
                        console.log('🔍 Réponses dans data:', data.responses);
                        console.log('🔍 Type de data.responses:', typeof data.responses);
                        console.log('🔍 Est un tableau:', Array.isArray(data.responses));
                        console.log('🔍 Longueur:', data.responses ? data.responses.length : 'N/A');
                        
                        // S'assurer que data.responses est un tableau
                        if (!data.responses) {
                            data.responses = [];
                            console.log('⚠️ data.responses était undefined/null, initialisé comme tableau vide');
                        }
                        
                        if (!Array.isArray(data.responses)) {
                            console.log('⚠️ data.responses n\'est pas un tableau, conversion...');
                            data.responses = [];
                        }
                        
                        if (data.responses && Array.isArray(data.responses) && data.responses.length > 0) {
                            console.log('✅ Affichage de ' + data.responses.length + ' réponse(s)');
                            responsesHtml = '<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);"><h4 style="color: var(--primary-color); margin-bottom: 15px;"><i class="fas fa-reply"></i> Réponses (' + data.responses.length + ')</h4>';
                            data.responses.forEach((response, index) => {
                                console.log('🔍 Affichage réponse #' + (index + 1) + ':', response);
                                const responseId = response.id_reponse || response.id_response || response.response_id || '';
                                const responseDate = response.date_reponse || response.response_date || response.date_creation || new Date().toISOString();
                                const responseMessage = response.message || response.response_text || response.texte || 'Aucun message';
                                
                                responsesHtml += '<div style="background: rgba(255, 122, 0, 0.05); border-left: 3px solid var(--primary-color); padding: 15px; margin-bottom: 10px; border-radius: 8px;">';
                                responsesHtml += '<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">';
                                responsesHtml += '<div><strong style="color: var(--primary-color);">Admin</strong>';
                                try {
                                    const dateObj = new Date(responseDate);
                                    responsesHtml += '<span style="color: var(--text-gray); font-size: 12px; margin-left: 10px;">' + dateObj.toLocaleString('fr-FR') + '</span>';
                                } catch (e) {
                                    responsesHtml += '<span style="color: var(--text-gray); font-size: 12px; margin-left: 10px;">' + new Date().toLocaleString('fr-FR') + '</span>';
                                }
                                responsesHtml += '</div>';
                                responsesHtml += '</div>';
                                responsesHtml += '<p style="color: var(--text-light); margin: 0; line-height: 1.6; white-space: pre-wrap;">' + escapeHtml(responseMessage) + '</p>';
                                responsesHtml += '</div>';
                            });
                            responsesHtml += '</div>';
                        } else {
                            console.log('⚠️ Aucune réponse à afficher');
                            console.log('⚠️ data.responses:', data.responses);
                            console.log('⚠️ Est vide:', !data.responses || data.responses.length === 0);
                            // Afficher un message informatif même s'il n'y a pas de réponses
                            responsesHtml = '<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);"><p style="color: var(--text-gray); font-size: 12px;"><i class="fas fa-info-circle"></i> Aucune réponse pour le moment</p></div>';
                        }
                        
                        // Afficher les évaluations existantes
                        let satisfactionsHtml = '';
                        if (data.satisfactions && Array.isArray(data.satisfactions) && data.satisfactions.length > 0) {
                            console.log('✅ Affichage de ' + data.satisfactions.length + ' évaluation(s)');
                            satisfactionsHtml = '<div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid var(--primary-color);"><h4 style="color: var(--primary-color); margin-bottom: 15px;"><i class="fas fa-star"></i> Évaluations (' + data.satisfactions.length + ')</h4>';
                            
                            data.satisfactions.forEach((satisfaction, index) => {
                                const satisfactionEmail = satisfaction.email || 'Utilisateur';
                                const satisfactionRating = satisfaction.rating || 0;
                                const satisfactionComment = satisfaction.commentaire || '';
                                const satisfactionDate = satisfaction.date_evaluation || new Date().toISOString();
                                
                                // Masquer une partie de l'email pour la confidentialité
                                let displayEmail = satisfactionEmail;
                                if (satisfactionEmail.includes('@')) {
                                    const emailParts = satisfactionEmail.split('@');
                                    displayEmail = emailParts[0].substring(0, 3) + '***@' + emailParts[1];
                                }
                                
                                satisfactionsHtml += '<div style="background: rgba(255, 193, 7, 0.1); border-left: 3px solid #ffc107; padding: 15px; margin-bottom: 15px; border-radius: 8px;">';
                                satisfactionsHtml += '<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">';
                                satisfactionsHtml += '<div>';
                                satisfactionsHtml += '<strong style="color: #ffc107;">' + escapeHtml(displayEmail) + '</strong>';
                                satisfactionsHtml += '<span style="color: var(--text-gray); font-size: 12px; margin-left: 10px;">';
                                try {
                                    const dateObj = new Date(satisfactionDate);
                                    satisfactionsHtml += dateObj.toLocaleString('fr-FR');
                                } catch (e) {
                                    satisfactionsHtml += new Date().toLocaleString('fr-FR');
                                }
                                satisfactionsHtml += '</span>';
                                satisfactionsHtml += '</div>';
                                satisfactionsHtml += '<div style="display: flex; gap: 3px;">';
                                for (let i = 1; i <= 5; i++) {
                                    satisfactionsHtml += '<i class="fas fa-star" style="color: ' + (i <= satisfactionRating ? '#ffc107' : '#444') + '; font-size: 16px;"></i>';
                                }
                                satisfactionsHtml += '<span style="margin-left: 5px; color: var(--text-light); font-weight: 700;">(' + satisfactionRating + '/5)</span>';
                                satisfactionsHtml += '</div>';
                                satisfactionsHtml += '</div>';
                                
                                if (satisfactionComment) {
                                    satisfactionsHtml += '<p style="color: var(--text-light); margin: 0; line-height: 1.6; font-style: italic; white-space: pre-wrap;">"' + escapeHtml(satisfactionComment) + '"</p>';
                                }
                                
                                satisfactionsHtml += '</div>';
                            });
                            
                            satisfactionsHtml += '</div>';
                        } else {
                            console.log('ℹ️ Aucune évaluation à afficher');
                        }
                        
                        // Formulaire d'évaluation de satisfaction (seulement si résolu)
                        let satisfactionFormHtml = '';
                        if (data.statut === 'resolu') {
                            satisfactionFormHtml = `
                                <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid var(--primary-color);">
                                    <h4 style="color: var(--primary-color); margin-bottom: 15px;">
                                        <i class="fas fa-star"></i> Évaluer cette résolution
                                    </h4>
                                    <form id="satisfaction-form-${data.id_reclamation}" style="background: rgba(255, 122, 0, 0.05); padding: 20px; border-radius: 8px;">
                                        <input type="hidden" name="action" value="submit_satisfaction">
                                        <input type="hidden" name="id_reclamation" value="${data.id_reclamation}">
                                        
                                        <div style="margin-bottom: 15px;">
                                            <label style="display: block; color: var(--text-light); margin-bottom: 8px; font-weight: 600;">Votre email <span style="color: var(--primary-color);">*</span></label>
                                            <input type="email" name="email" id="satisfaction-email-${data.id_reclamation}" 
                                                   class="form-input" required 
                                                   value="${escapeHtml(data.email || '')}"
                                                   style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-light);">
                                        </div>
                                        
                                        <div style="margin-bottom: 15px;">
                                            <label style="display: block; color: var(--text-light); margin-bottom: 8px; font-weight: 600;">Note (1-5 étoiles) <span style="color: var(--primary-color);">*</span></label>
                                            <div class="rating-stars-${data.id_reclamation}" style="display: flex; gap: 5px; margin-bottom: 10px;">
                                                ${[1, 2, 3, 4, 5].map(i => `
                                                    <i class="fas fa-star rating-star-${data.id_reclamation}" 
                                                       data-rating="${i}" 
                                                       style="font-size: 28px; color: #444; cursor: pointer; transition: color 0.2s;"></i>
                                                `).join('')}
                                            </div>
                                            <input type="hidden" name="rating" id="satisfaction-rating-${data.id_reclamation}" value="0" required>
                                        </div>
                                        
                                        <div style="margin-bottom: 15px;">
                                            <label style="display: block; color: var(--text-light); margin-bottom: 8px; font-weight: 600;">Commentaire (optionnel)</label>
                                            <textarea name="commentaire" id="satisfaction-comment-${data.id_reclamation}" 
                                                      class="form-textarea" 
                                                      rows="3" 
                                                      placeholder="Partagez votre avis sur la résolution..."
                                                      style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-light); resize: vertical;"></textarea>
                                        </div>
                                        
                                        <div id="satisfaction-message-${data.id_reclamation}" style="margin-bottom: 15px; display: none;"></div>
                                        
                                        <button type="submit" class="btn" style="width: 100%;">
                                            <i class="fas fa-star"></i> Envoyer l'évaluation
                                        </button>
                                    </form>
                                </div>
                            `;
                        }
                        
                        modalBody.innerHTML = `
                            <div>
                                <p><strong>Email:</strong> ${escapeHtml(data.email || '')}</p>
                                <p><strong>Sujet:</strong> ${escapeHtml(data.sujet || '')}</p>
                                <p><strong>Statut:</strong> <span class="status-badge status-${data.statut || 'nouveau'}">${statusText[data.statut] || data.statut || 'New'}</span></p>
                                <p><strong>Date:</strong> ${new Date(data.date_creation).toLocaleString('fr-FR')}</p>
                                <p><strong>Description:</strong></p>
                                <p style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px; margin-top: 10px; white-space: pre-wrap; line-height: 1.6;">${escapeHtml(data.description || '')}</p>
                                ${responsesHtml}
                                ${satisfactionsHtml}
                                ${satisfactionFormHtml}
                            </div>
                        `;
                        
                        // Ajouter le gestionnaire d'événement pour le formulaire d'évaluation
                        if (data.statut === 'resolu') {
                            const satisfactionForm = document.getElementById('satisfaction-form-' + data.id_reclamation);
                            if (satisfactionForm) {
                                // Gestion des étoiles de notation
                                const stars = document.querySelectorAll('.rating-star-' + data.id_reclamation);
                                const ratingInput = document.getElementById('satisfaction-rating-' + data.id_reclamation);
                                
                                stars.forEach((star, index) => {
                                    star.addEventListener('mouseenter', function() {
                                        for (let i = 0; i <= index; i++) {
                                            stars[i].style.color = '#ffc107';
                                        }
                                    });
                                    
                                    star.addEventListener('mouseleave', function() {
                                        const currentRating = parseInt(ratingInput.value) || 0;
                                        stars.forEach((s, idx) => {
                                            s.style.color = idx < currentRating ? '#ffc107' : '#444';
                                        });
                                    });
                                    
                                    star.addEventListener('click', function() {
                                        const rating = index + 1;
                                        ratingInput.value = rating;
                                        stars.forEach((s, idx) => {
                                            s.style.color = idx < rating ? '#ffc107' : '#444';
                                        });
                                    });
                                });
                                
                                satisfactionForm.addEventListener('submit', function(e) {
                                    e.preventDefault();
                                    
                                    const formData = new FormData(this);
                                    const messageDiv = document.getElementById('satisfaction-message-' + data.id_reclamation);
                                    const submitBtn = this.querySelector('button[type="submit"]');
                                    
                                    // Réinitialiser le message
                                    messageDiv.style.display = 'none';
                                    messageDiv.innerHTML = '';
                                    
                                    // Validation
                                    const rating = parseInt(formData.get('rating') || '0');
                                    const email = formData.get('email') ? formData.get('email').trim() : '';
                                    
                                    // Valider la note
                                    if (!rating || rating < 1 || rating > 5) {
                                        messageDiv.style.display = 'block';
                                        messageDiv.style.color = '#f44336';
                                        messageDiv.style.background = 'rgba(244, 67, 54, 0.1)';
                                        messageDiv.style.padding = '10px';
                                        messageDiv.style.borderRadius = '6px';
                                        messageDiv.style.marginBottom = '15px';
                                        messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Veuillez sélectionner une note entre 1 et 5 étoiles.';
                                        return;
                                    }
                                    
                                    // Valider l'email
                                    if (!email || email === '') {
                                        messageDiv.style.display = 'block';
                                        messageDiv.style.color = '#f44336';
                                        messageDiv.style.background = 'rgba(244, 67, 54, 0.1)';
                                        messageDiv.style.padding = '10px';
                                        messageDiv.style.borderRadius = '6px';
                                        messageDiv.style.marginBottom = '15px';
                                        messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Veuillez entrer votre email.';
                                        return;
                                    }
                                    
                                    // Valider le format de l'email
                                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                                    if (!emailRegex.test(email)) {
                                        messageDiv.style.display = 'block';
                                        messageDiv.style.color = '#f44336';
                                        messageDiv.style.background = 'rgba(244, 67, 54, 0.1)';
                                        messageDiv.style.padding = '10px';
                                        messageDiv.style.borderRadius = '6px';
                                        messageDiv.style.marginBottom = '15px';
                                        messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Format d\'email invalide.';
                                        return;
                                    }
                                    
                                    // Désactiver le bouton
                                    const originalBtnText = submitBtn.innerHTML;
                                    submitBtn.disabled = true;
                                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';
                                    
                                    // Ajouter le header pour la détection AJAX
                                    formData.append('ajax', '1');
                                    
                                    // Envoyer la requête AJAX
                                    fetch('reclamback.php', {
                                        method: 'POST',
                                        body: formData,
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest'
                                        },
                                        credentials: 'same-origin'
                                    })
                                    .then(response => {
                                        const contentType = response.headers.get('content-type');
                                        if (contentType && contentType.includes('application/json')) {
                                            return response.json();
                                        } else {
                                            return response.text().then(text => {
                                                try {
                                                    return JSON.parse(text);
                                                } catch {
                                                    return { success: false, message: 'Réponse invalide du serveur' };
                                                }
                                            });
                                        }
                                    })
                                    .then(result => {
                                        if (result.success) {
                                            messageDiv.style.display = 'block';
                                            messageDiv.style.color = '#4caf50';
                                            messageDiv.style.background = 'rgba(76, 175, 80, 0.1)';
                                            messageDiv.style.padding = '10px';
                                            messageDiv.style.borderRadius = '6px';
                                            messageDiv.style.marginBottom = '15px';
                                            messageDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + (result.message || 'Merci pour votre évaluation !');
                                            
                                            // Réinitialiser le formulaire
                                            satisfactionForm.reset();
                                            stars.forEach(star => {
                                                star.style.color = '#444';
                                            });
                                            ratingInput.value = '0';
                                            
                                            // Recharger la modal après 1 seconde pour afficher la nouvelle évaluation
                                            setTimeout(() => {
                                                console.log('🔄 Rechargement de la modal pour afficher la nouvelle évaluation...');
                                                // Simuler un clic sur le bouton "View Details" pour recharger
                                                const viewBtn = document.querySelector(`.view-request[data-id="${data.id_reclamation}"]`);
                                                if (viewBtn) {
                                                    viewBtn.click();
                                                } else {
                                                    // Si le bouton n'est pas trouvé, recharger manuellement
                                                    const modalBody = document.getElementById('view-modal-body');
                                                    modalBody.innerHTML = '<p style="text-align: center; color: var(--primary-color);"><i class="fas fa-spinner fa-spin"></i> Chargement...</p>';
                                                    
                                                    fetch('reclamback.php?view_id=' + data.id_reclamation + '&ajax=1')
                                                        .then(response => response.json())
                                                        .then(updatedData => {
                                                            // Recharger en déclenchant l'événement view-request
                                                            const event = new Event('click');
                                                            viewBtn.dispatchEvent(event);
                                                        })
                                                        .catch(error => {
                                                            console.error('Erreur lors du rechargement:', error);
                                                        });
                                                }
                                            }, 1500);
                                        } else {
                                            messageDiv.style.display = 'block';
                                            messageDiv.style.color = '#f44336';
                                            messageDiv.style.background = 'rgba(244, 67, 54, 0.1)';
                                            messageDiv.style.padding = '10px';
                                            messageDiv.style.borderRadius = '6px';
                                            messageDiv.style.marginBottom = '15px';
                                            messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (result.message || 'Erreur lors de l\'envoi de l\'évaluation.');
                                        }
                                        submitBtn.disabled = false;
                                        submitBtn.innerHTML = originalBtnText;
                                    })
                                    .catch(error => {
                                        console.error('Erreur:', error);
                                        messageDiv.style.display = 'block';
                                        messageDiv.style.color = '#f44336';
                                        messageDiv.style.background = 'rgba(244, 67, 54, 0.1)';
                                        messageDiv.style.padding = '10px';
                                        messageDiv.style.borderRadius = '6px';
                                        messageDiv.style.marginBottom = '15px';
                                        messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Erreur lors de l\'envoi. Veuillez réessayer.';
                                        submitBtn.disabled = false;
                                        submitBtn.innerHTML = originalBtnText;
                                    });
                                });
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        modalBody.innerHTML = '<p style="color: #f44336;">Error loading details. Please try again.</p>';
                    });
            });
        });
        
        // Update Status functionality
        document.querySelectorAll('.edit-status').forEach(button => {
            button.addEventListener('click', function() {
                const reclamationId = this.getAttribute('data-id');
                const currentStatus = this.getAttribute('data-status');
                
                document.getElementById('status-reclamation-id').value = reclamationId;
                document.getElementById('status-select').value = currentStatus;
                statusModal.classList.add('active');
            });
        });
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Close modals
        function closeModals() {
            responseModal.classList.remove('active');
            viewModal.classList.remove('active');
            statusModal.classList.remove('active');
            deleteModal.classList.remove('active');
        }
        
        closeModalButtons.forEach(button => {
            button.addEventListener('click', closeModals);
        });
        
        cancelResponse.addEventListener('click', closeModals);
        cancelDelete.addEventListener('click', closeModals);
        cancelStatus.addEventListener('click', closeModals);
        
        // Handle status form submission
        const statusForm = document.getElementById('status-form');
        if (statusForm) {
            statusForm.addEventListener('submit', function(e) {
                // Le formulaire se soumettra normalement via POST
                // Afficher un loader pendant la soumission
                const submitBtn = document.getElementById('submit-status');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mise à jour...';
                }
            });
        }
        
        // Fonction pour afficher les erreurs
        function showSuccessMessage(message) {
            // Créer ou réutiliser un élément de notification
            let notification = document.getElementById('success-notification');
            if (!notification) {
                notification = document.createElement('div');
                notification.id = 'success-notification';
                notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #4caf50; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); z-index: 10000; max-width: 400px; animation: slideIn 0.3s ease-out;';
                document.body.appendChild(notification);
            }
            
            notification.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
            notification.style.display = 'block';
            
            // Masquer après 3 secondes
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 300);
            }, 3000);
        }
        
        function showResponseError(message) {
            const errorDiv = document.getElementById('response-error');
            const errorText = document.getElementById('response-error-text');
            const textarea = document.getElementById('response-text');
            
            errorText.textContent = message;
            errorDiv.style.display = 'block';
            textarea.classList.add('error');
            textarea.classList.remove('valid');
        }
        
        function hideResponseError() {
            const errorDiv = document.getElementById('response-error');
            const textarea = document.getElementById('response-text');
            
            errorDiv.style.display = 'none';
            textarea.classList.remove('error');
            
            // Ajouter la classe "valid" si le texte est valide
            const validation = validateResponse(textarea.value);
            if (validation.valid && textarea.value.trim().length >= 10) {
                textarea.classList.add('valid');
            } else {
                textarea.classList.remove('valid');
            }
        }
        
        // Fonction pour valider la réponse
        function validateResponse(text) {
            const trimmedText = text.trim();
            
            // Vérifier si vide
            if (!trimmedText) {
                return { valid: false, message: 'Veuillez entrer une réponse.' };
            }
            
            // Vérifier la longueur minimale
            if (trimmedText.length < 10) {
                return { valid: false, message: 'La réponse doit contenir au moins 10 caractères.' };
            }
            
            // Vérifier la longueur maximale
            if (trimmedText.length > 5000) {
                return { valid: false, message: 'La réponse ne doit pas dépasser 5000 caractères.' };
            }
            
            // Vérifier les caractères spéciaux dangereux (optionnel, pour sécurité)
            const dangerousPatterns = [
                /<script/i,
                /javascript:/i,
                /on\w+\s*=/i
            ];
            
            for (let pattern of dangerousPatterns) {
                if (pattern.test(trimmedText)) {
                    return { valid: false, message: 'La réponse contient des caractères non autorisés.' };
                }
            }
            
            // Vérifier si ce n'est pas que des espaces
            if (trimmedText.replace(/\s/g, '').length === 0) {
                return { valid: false, message: 'La réponse ne peut pas contenir uniquement des espaces.' };
            }
            
            return { valid: true, message: '' };
        }
        
        // Compteur de caractères en temps réel
        const responseTextarea = document.getElementById('response-text');
        const charCountCurrent = document.getElementById('response-char-current');
        const charCountMax = document.getElementById('response-char-max');
        
        responseTextarea.addEventListener('input', function() {
            const text = this.value;
            const length = text.length;
            const maxLength = parseInt(this.getAttribute('maxlength')) || 5000;
            
            charCountCurrent.textContent = length;
            
            // Changer la couleur selon la longueur
            if (length > maxLength * 0.9) {
                charCountCurrent.style.color = '#f44336';
            } else if (length > maxLength * 0.7) {
                charCountCurrent.style.color = '#ff9800';
            } else {
                charCountCurrent.style.color = 'var(--text-gray)';
            }
            
            // Valider en temps réel
            const validation = validateResponse(text);
            if (!validation.valid && length > 0) {
                showResponseError(validation.message);
            } else {
                hideResponseError();
            }
        });
        
        // Validation au focus
        responseTextarea.addEventListener('blur', function() {
            const validation = validateResponse(this.value);
            if (!validation.valid) {
                showResponseError(validation.message);
            } else {
                hideResponseError();
            }
        });
        
        // Validation au focus (cacher l'erreur si l'utilisateur commence à taper)
        responseTextarea.addEventListener('focus', function() {
            hideResponseError();
        });
        
        // Submit response - Version AJAX simplifiée
        responseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const responseText = document.getElementById('response-text').value.trim();
            const reclamationId = parseInt(document.getElementById('response-reclamation-id').value);
            const isEditMode = document.getElementById('edit-response-flag').value === '1';
            const isAddMode = document.getElementById('add-response-flag').value === '1';
            
            // Valider la réponse
            if (!responseText || responseText.length < 10) {
                showResponseError('La réponse doit contenir au moins 10 caractères.');
                return;
            }
            
            if (responseText.length > 5000) {
                showResponseError('La réponse ne doit pas dépasser 5000 caractères.');
                return;
            }
            
            // Valider l'ID de réclamation
            if (!reclamationId || reclamationId <= 0 || isNaN(reclamationId)) {
                showResponseError('Erreur: ID de réclamation invalide.');
                return;
            }
            
            // S'assurer que les bons flags sont définis
            if (isEditMode) {
                document.getElementById('add-response-flag').value = '0';
            } else {
                document.getElementById('add-response-flag').value = '1';
                document.getElementById('edit-response-flag').value = '0';
            }
            
            // Désactiver le bouton pour éviter les doubles soumissions
            const submitBtn = document.getElementById('submit-response');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';
            
            // Préparer les données
            const formData = new FormData(responseForm);
            formData.append('ajax', '1');
            
            // Envoyer via AJAX
            fetch('reclamback.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch {
                            return { html: text };
                        }
                    });
                }
            })
            .then(data => {
                if (typeof data === 'object' && data !== null) {
                    if (data.success === true) {
                        // Fermer le modal
                        closeModals();
                        
                        // Afficher un message de succès
                        showSuccessMessage('Réponse ajoutée avec succès !');
                        
                        // Recharger la page avec l'ID de réclamation pour forcer le rechargement des réponses
                        const reclamationId = data.reclamation_id || document.getElementById('response-reclamation-id').value;
                        setTimeout(() => {
                            if (reclamationId) {
                                window.location.href = 'reclamback.php?response_added=1&reclamation_id=' + reclamationId;
                            } else {
                                window.location.reload();
                            }
                        }, 500);
                    } else {
                        // Erreur retournée par le serveur
                        const errorMsg = data.message || 'Erreur lors de l\'ajout de la réponse';
                        showResponseError(errorMsg);
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    }
                } else {
                    // Réponse HTML inattendue
                    showResponseError('Erreur: Réponse inattendue du serveur.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showResponseError('Erreur lors de l\'envoi de la réponse: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
        
        // Confirm delete
        document.getElementById('confirm-delete').addEventListener('click', function() {
            const requestId = deleteModal.getAttribute('data-id');
            
            if (requestId) {
                // Redirect to delete
                window.location.href = 'reclamback.php?delete_id=' + requestId;
            } else {
                closeModals();
            }
        });
        
        // Apply filters
        document.getElementById('apply-filters').addEventListener('click', function() {
            const statusFilter = document.getElementById('status-filter').value;
            const dateFilter = document.getElementById('date-filter').value;
            const categorieFilter = document.getElementById('categorie-filter').value;
            
            // Construire l'URL avec les paramètres
            const url = new URL(window.location.href);
            url.searchParams.set('status_filter', statusFilter);
            url.searchParams.set('date_filter', dateFilter);
            url.searchParams.set('categorie_filter', categorieFilter);
            
            // Recharger la page avec les nouveaux paramètres
            window.location.href = url.toString();
        });
        
        // Reset filters
        document.getElementById('reset-filters').addEventListener('click', function() {
            // Reload page without parameters
            window.location.href = window.location.pathname;
        });
        
        // Clickable stat cards to filter
        document.querySelectorAll('.clickable-stat').forEach(card => {
            card.addEventListener('click', function() {
                const statusFilter = this.getAttribute('data-status-filter');
                const url = new URL(window.location.href);
                url.searchParams.set('status_filter', statusFilter);
                url.searchParams.delete('date_filter'); // Reset date filter when clicking stat
                url.searchParams.delete('categorie_filter'); // Reset category filter when clicking stat
                window.location.href = url.toString();
            });
        });
        
        // Attachment Modal Functions
        function openAttachmentModal(element) {
            const modal = document.getElementById('attachment-modal');
            const modalContent = document.getElementById('attachment-modal-content');
            const downloadLink = document.getElementById('attachment-download-link');
            const fileSrc = element.getAttribute('data-src');
            const fileType = element.getAttribute('data-type');
            
            if (!modal || !modalContent) {
                console.error('Attachment modal elements not found');
                return;
            }
            
            modal.style.display = 'flex';
            if (downloadLink) {
                downloadLink.href = fileSrc;
            }
            
            if (fileType === 'image') {
                console.log('Opening image modal with src:', fileSrc);
                
                // Afficher directement l'image avec gestion d'erreur
                modalContent.innerHTML = `
                    <img src="${fileSrc}" 
                         alt="Full size attachment" 
                         style="max-width: 100%; max-height: 85vh; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); display: block; margin: 0 auto;"
                         onerror="this.onerror=null; this.parentElement.innerHTML='<div style=\\'text-align: center; padding: 40px; color: #ff7a00;\\'><i class=\\'fas fa-exclamation-triangle\\' style=\\'font-size: 48px; margin-bottom: 20px; display: block;\\'></i><p style=\\'font-size: 18px; margin-bottom: 10px;\\'>Impossible de charger l\\'image</p><p style=\\'font-size: 14px; color: #aaa; word-break: break-all;\\'>Chemin: ${fileSrc}</p><a href=\\'${fileSrc}\\' target=\\'_blank\\' style=\\'color: #ff7a00; text-decoration: none; margin-top: 20px; display: inline-block;\\'><i class=\\'fas fa-external-link-alt\\'></i> Ouvrir dans un nouvel onglet</a></div>';">
                `;
            } else if (fileType === 'video') {
                const videoExt = fileSrc.split('.').pop();
                const video = document.createElement('video');
                video.controls = true;
                video.autoplay = true;
                video.style.cssText = 'max-width: 100%; max-height: 85vh; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); display: block; margin: 0 auto;';
                
                const source = document.createElement('source');
                source.src = fileSrc;
                source.type = `video/${videoExt}`;
                video.appendChild(source);
                
                video.onerror = function() {
                    console.error('Erreur de chargement de la vidéo:', fileSrc);
                    modalContent.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #ff7a00;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 20px; display: block;"></i>
                            <p style="font-size: 18px; margin-bottom: 10px;">Impossible de charger la vidéo</p>
                            <p style="font-size: 14px; color: #aaa;">Chemin: ${fileSrc}</p>
                            <a href="${fileSrc}" target="_blank" style="color: #ff7a00; text-decoration: none; margin-top: 20px; display: inline-block;">
                                <i class="fas fa-download"></i> Télécharger la vidéo
                            </a>
                        </div>
                    `;
                };
                
                modalContent.innerHTML = '';
                modalContent.appendChild(video);
            }
            
            // Prevent body scroll when modal is open
            document.body.style.overflow = 'hidden';
        }
        
        function closeAttachmentModal() {
            const modal = document.getElementById('attachment-modal');
            const modalContent = document.getElementById('attachment-modal-content');
            
            if (!modal) return;
            
            modal.style.display = 'none';
            if (modalContent) {
                modalContent.innerHTML = '';
            }
            
            // Restore body scroll
            document.body.style.overflow = '';
        }
        
        // Close modal when clicking outside (only if modal exists)
        const attachmentModal = document.getElementById('attachment-modal');
        if (attachmentModal) {
            attachmentModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeAttachmentModal();
                }
            });
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('attachment-modal');
                if (modal.style.display === 'flex') {
                    closeAttachmentModal();
                }
            }
        });
        
        // Simple sidebar navigation
        document.querySelectorAll('.sidebar a').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.sidebar a').forEach(nav => {
                    nav.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
        
        // Protéger les images contre la disparition - Fonction robuste
        const protectImages = function() {
            // Forcer l'affichage de toutes les images
            document.querySelectorAll('.attachment-thumbnail, .persistent-image').forEach(img => {
                // Vérifier si l'image existe toujours dans le DOM
                if (!img.isConnected) {
                    console.warn('Image retirée du DOM, tentative de restauration...');
                    // L'image a été retirée du DOM, on ne peut pas la restaurer facilement
                    return;
                }
                
                // Vérifier si l'image a un src valide et le restaurer si nécessaire
                const originalSrc = img.getAttribute('data-original-src') || img.getAttribute('data-src');
                const fallbackSrc = img.getAttribute('data-src-fallback');
                
                if (originalSrc) {
                    // Si le src a été modifié, supprimé ou vidé, le restaurer
                    if (!img.src || img.src === '' || img.src === window.location.href || 
                        (!img.src.includes('uploads') && !img.src.includes('reclamations'))) {
                        console.log('Restauration du src de l\'image:', originalSrc);
                        img.src = originalSrc;
                        // Forcer le rechargement
                        img.load();
                    }
                }
                
                // Vérifier si l'image est visible dans le viewport
                const rect = img.getBoundingClientRect();
                const isVisible = rect.width > 0 && rect.height > 0 && 
                                 window.getComputedStyle(img).display !== 'none' &&
                                 window.getComputedStyle(img).visibility !== 'hidden' &&
                                 window.getComputedStyle(img).opacity !== '0';
                
                if (!isVisible) {
                    console.log('Image non visible, restauration forcée');
                }
                
                // Forcer l'affichage avec important - utiliser cssText pour tout remplacer
                img.style.cssText = 'max-width: 300px !important; max-height: 200px !important; border-radius: 8px !important; cursor: pointer !important; border: 2px solid rgba(255,122,0,0.3) !important; transition: transform 0.3s ease !important; display: block !important; visibility: visible !important; opacity: 1 !important; position: relative !important; width: auto !important; height: auto !important; min-width: 50px !important; min-height: 50px !important;';
                
                // S'assurer que le parent est aussi visible
                let parent = img.parentElement;
                while (parent) {
                    if (parent && parent.style) {
                        parent.style.cssText = parent.style.cssText.replace(/display\s*:\s*none[^;]*;?/gi, '') + ' display: block !important; visibility: visible !important;';
                    }
                    if (parent && parent.classList && parent.classList.contains('attachment-preview')) {
                        break;
                    }
                    parent = parent ? parent.parentElement : null;
                }
            });
            
            // Forcer l'affichage de tous les conteneurs avec cssText
            document.querySelectorAll('.attachment-preview, .image-container').forEach(div => {
                if (div && div.isConnected) {
                    const currentStyle = div.style.cssText || '';
                    div.style.cssText = currentStyle.replace(/display\s*:\s*none[^;]*;?/gi, '').replace(/visibility\s*:\s*hidden[^;]*;?/gi, '') + ' display: block !important; visibility: visible !important; position: relative !important;';
                }
            });
        };
        
        // Intercepter les tentatives de suppression des images
        const originalRemoveChild = Node.prototype.removeChild;
        Node.prototype.removeChild = function(child) {
            if (child && (child.classList || child.tagName === 'IMG')) {
                const isImage = (child.classList && (
                    child.classList.contains('attachment-thumbnail') || 
                    child.classList.contains('attachment-preview') ||
                    child.classList.contains('image-container') ||
                    child.classList.contains('persistent-image')
                )) || (child.tagName === 'IMG' && child.src && child.src.includes('uploads'));
                
                if (isImage) {
                    console.warn('Tentative de suppression d\'une image bloquée:', child);
                    return child; // Ne pas supprimer
                }
            }
            return originalRemoveChild.call(this, child);
        };
        
        const originalRemove = Element.prototype.remove;
        Element.prototype.remove = function() {
            if (this && (this.classList || this.tagName === 'IMG')) {
                const isImage = (this.classList && (
                    this.classList.contains('attachment-thumbnail') || 
                    this.classList.contains('attachment-preview') ||
                    this.classList.contains('image-container') ||
                    this.classList.contains('persistent-image')
                )) || (this.tagName === 'IMG' && this.src && this.src.includes('uploads'));
                
                if (isImage) {
                    console.warn('Tentative de suppression d\'une image bloquée:', this);
                    return; // Ne pas supprimer
                }
            }
            return originalRemove.call(this);
        };
        
        // Intercepter les modifications de style.style.display, .style.visibility, etc.
        const protectStyleProperty = function(element, property, value) {
            if (!element || !element.classList) return false;
            
            const isImage = element.classList.contains('attachment-thumbnail') || 
                          element.classList.contains('persistent-image') ||
                          element.classList.contains('image-container') ||
                          element.classList.contains('attachment-preview');
            
            if (isImage && (property === 'display' || property === 'visibility' || property === 'opacity')) {
                if (value === 'none' || value === 'hidden' || (property === 'opacity' && parseFloat(value) === 0)) {
                    console.warn('Tentative de masquer une image bloquée:', property, value);
                    return true; // Bloquer
                }
            }
            return false;
        };
        
        // Intercepter style.display, style.visibility directement
        ['display', 'visibility', 'opacity'].forEach(prop => {
            Object.defineProperty(HTMLElement.prototype, prop, {
                set: function(value) {
                    if (protectStyleProperty(this, prop, value)) {
                        return; // Ne pas appliquer
                    }
                    this.style[prop] = value;
                },
                get: function() {
                    return this.style[prop];
                }
            });
        });
        
        // Précharger toutes les images pour garantir leur disponibilité
        const preloadImages = function() {
            document.querySelectorAll('.persistent-image').forEach(img => {
                const src = img.getAttribute('data-original-src') || img.getAttribute('data-src');
                if (src) {
                    const preloadImg = new Image();
                    preloadImg.src = src;
                    preloadImg.onload = function() {
                        // Une fois préchargée, s'assurer que l'image principale est visible
                        if (img.src !== src) {
                            img.src = src;
                        }
                        img.style.setProperty('display', 'block', 'important');
                        img.style.setProperty('visibility', 'visible', 'important');
                        img.style.setProperty('opacity', '1', 'important');
                    };
                }
            });
        };
        
        // Exécuter immédiatement
        protectImages();
        preloadImages();
        
        // Exécuter après le chargement complet
        window.addEventListener('load', function() {
            protectImages();
            preloadImages();
            // Re-vérifier après un court délai
            setTimeout(function() { protectImages(); preloadImages(); }, 100);
            setTimeout(function() { protectImages(); preloadImages(); }, 500);
            setTimeout(function() { protectImages(); preloadImages(); }, 1000);
        });
        
        // Vérifier périodiquement que les images sont visibles (toutes les 100ms pour être très réactif)
        setInterval(function() {
            protectImages();
            // Recharger les images qui ont disparu
            document.querySelectorAll('.persistent-image, .attachment-thumbnail').forEach(img => {
                // Vérifier si l'image est toujours dans le DOM
                if (!img.isConnected) {
                    console.warn('Image retirée du DOM, recréation...');
                    // L'image a été retirée, essayer de la recréer
                    const container = document.querySelector(`[data-image-id="${img.getAttribute('data-reclamation-id')}"]`);
                    if (container && container.isConnected) {
                        const newImg = img.cloneNode(true);
                        newImg.src = img.getAttribute('data-original-src') + '?reload=' + Date.now();
                        container.appendChild(newImg);
                    }
                    return;
                }
                
                // Vérifier si l'image est chargée
                if (!img.complete || img.naturalWidth === 0) {
                    const src = img.getAttribute('data-original-src') || img.getAttribute('data-src');
                    if (src && (!img.src || img.src !== src)) {
                        console.log('Rechargement de l\'image:', src);
                        img.src = src + '?reload=' + Date.now();
                    }
                }
                
                // Forcer l'affichage à chaque vérification
                img.style.cssText = 'max-width: 300px !important; max-height: 200px !important; border-radius: 8px !important; cursor: pointer !important; border: 2px solid rgba(255,122,0,0.3) !important; display: block !important; visibility: visible !important; opacity: 1 !important; position: relative !important; width: auto !important; height: auto !important;';
            });
        }, 100);
        
        // Observer les changements dans le DOM pour protéger les images
        const observer = new MutationObserver(function(mutations) {
            let shouldProtect = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes') {
                    const target = mutation.target;
                    if (target.classList && (target.classList.contains('attachment-thumbnail') || target.classList.contains('attachment-preview') || target.classList.contains('image-container'))) {
                        shouldProtect = true;
                        // Forcer immédiatement l'affichage
                        if (target.classList.contains('attachment-thumbnail')) {
                            target.style.setProperty('display', 'block', 'important');
                            target.style.setProperty('visibility', 'visible', 'important');
                            target.style.setProperty('opacity', '1', 'important');
                            // Restaurer le src si modifié
                            const originalSrc = target.getAttribute('data-original-src') || target.getAttribute('data-src');
                            if (originalSrc && target.src !== originalSrc) {
                                target.src = originalSrc;
                            }
                        } else {
                            target.style.setProperty('display', 'block', 'important');
                            target.style.setProperty('visibility', 'visible', 'important');
                        }
                    }
                }
                
                // Si des nœuds sont supprimés, vérifier s'il s'agit d'images
                if (mutation.removedNodes) {
                    mutation.removedNodes.forEach(node => {
                        if (node.nodeType === 1 && node.classList && 
                            (node.classList.contains('attachment-thumbnail') || 
                             node.classList.contains('attachment-preview') ||
                             node.classList.contains('image-container'))) {
                            console.warn('Image supprimée du DOM, tentative de restauration...');
                            shouldProtect = true;
                        }
                    });
                }
                
                // Si des nœuds sont ajoutés, protéger les nouvelles images
                if (mutation.addedNodes) {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) { // Element node
                            if (node.classList) {
                                if (node.classList.contains('attachment-thumbnail')) {
                                    node.style.setProperty('display', 'block', 'important');
                                    node.style.setProperty('visibility', 'visible', 'important');
                                    node.style.setProperty('opacity', '1', 'important');
                                }
                                if (node.classList.contains('attachment-preview') || node.classList.contains('image-container')) {
                                    node.style.setProperty('display', 'block', 'important');
                                    node.style.setProperty('visibility', 'visible', 'important');
                                }
                            }
                            // Vérifier aussi les enfants
                            if (node.querySelectorAll) {
                                node.querySelectorAll('.attachment-thumbnail, .attachment-preview, .image-container').forEach(el => {
                                    if (el.classList.contains('attachment-thumbnail')) {
                                        el.style.setProperty('display', 'block', 'important');
                                        el.style.setProperty('visibility', 'visible', 'important');
                                        el.style.setProperty('opacity', '1', 'important');
                                    } else {
                                        el.style.setProperty('display', 'block', 'important');
                                        el.style.setProperty('visibility', 'visible', 'important');
                                    }
                                });
                            }
                        }
                    });
                }
            });
            
            // Si des changements ont été détectés, protéger immédiatement
            if (shouldProtect) {
                setTimeout(protectImages, 0);
            }
        });
        
        // Observer tous les éléments avec la classe attachment et le body entier
        document.querySelectorAll('.attachment-thumbnail, .attachment-preview, .image-container').forEach(el => {
            observer.observe(el, {
                attributes: true,
                attributeFilter: ['style', 'class', 'src'],
                childList: true,
                subtree: true
            });
        });
        
        // Observer aussi le body pour détecter les nouveaux éléments ajoutés ou supprimés
        if (document.body) {
            observer.observe(document.body, {
                childList: true,
                subtree: true,
                attributes: false
            });
        }
        
        // Observer aussi le conteneur des reviews
        const reviewsList = document.querySelector('.reviews-list');
        if (reviewsList) {
            observer.observe(reviewsList, {
                childList: true,
                subtree: true,
                attributes: false
            });
        }
        
        // Stocker les informations des images pour pouvoir les recréer si nécessaire
        const imageRegistry = new Map();
        document.querySelectorAll('.persistent-image').forEach(img => {
            const id = img.getAttribute('data-reclamation-id') + '_' + Math.random().toString(36).substr(2, 9);
            img.setAttribute('data-image-id', id);
            imageRegistry.set(id, {
                src: img.getAttribute('data-original-src'),
                fallback: img.getAttribute('data-src-fallback'),
                container: img.closest('.image-container'),
                parent: img.parentElement
            });
        });
        
        // Fonction pour recréer une image si elle a été supprimée
        const recreateImageIfNeeded = function() {
            imageRegistry.forEach((data, id) => {
                const existingImg = document.querySelector(`[data-image-id="${id}"]`);
                if (!existingImg || !existingImg.isConnected) {
                    // L'image a été supprimée, la recréer
                    console.log('Recréation de l\'image supprimée:', id);
                    if (data.container && data.container.isConnected) {
                        const newImg = document.createElement('img');
                        newImg.src = data.src;
                        newImg.className = 'attachment-thumbnail persistent-image';
                        newImg.setAttribute('data-image-id', id);
                        newImg.setAttribute('data-type', 'image');
                        newImg.setAttribute('data-src', data.src);
                        newImg.setAttribute('data-src-fallback', data.fallback);
                        newImg.setAttribute('data-original-src', data.src);
                        newImg.setAttribute('data-reclamation-id', id.split('_')[0]);
                        newImg.style.cssText = 'max-width: 300px; max-height: 200px; border-radius: 8px; cursor: pointer; border: 2px solid rgba(255,122,0,0.3); transition: transform 0.3s ease; display: block !important; visibility: visible !important; opacity: 1 !important; position: relative !important; width: auto !important; height: auto !important;';
                        newImg.onclick = function() { openAttachmentModal(this); };
                        newImg.onload = function() {
                            this.style.setProperty('display', 'block', 'important');
                            this.style.setProperty('visibility', 'visible', 'important');
                            this.style.setProperty('opacity', '1', 'important');
                        };
                        newImg.onerror = function() {
                            if (data.fallback && this.src !== data.fallback) {
                                this.src = data.fallback;
                            }
                        };
                        data.container.appendChild(newImg);
                    }
                }
            });
        };
        
        // Vérifier périodiquement si des images doivent être recréées
        setInterval(recreateImageIfNeeded, 500);
        
        }); // Fin de DOMContentLoaded
        
        // Intercepter getComputedStyle pour forcer l'affichage
        const originalGetComputedStyle = window.getComputedStyle;
        window.getComputedStyle = function(element, pseudoElement) {
            const result = originalGetComputedStyle.call(this, element, pseudoElement);
            if (element && element.classList) {
                const isImage = element.classList.contains('attachment-thumbnail') || 
                               element.classList.contains('persistent-image') ||
                               element.classList.contains('image-container') ||
                               element.classList.contains('attachment-preview');
                if (isImage) {
                    // Créer un proxy qui force les valeurs d'affichage
                    return new Proxy(result, {
                        get: function(target, prop) {
                            if (prop === 'display') return 'block';
                            if (prop === 'visibility') return 'visible';
                            if (prop === 'opacity') return '1';
                            return target[prop];
                        }
                    });
                }
            }
            return result;
        };
        
        // Fonction pour protéger une image
        const protectImageSrc = function(img) {
            if (!img || img.tagName !== 'IMG') return;
            
            const originalSrc = img.getAttribute('data-original-src') || img.src;
            if (!originalSrc || !originalSrc.includes('uploads')) return;
            
            // Sauvegarder le src original
            img.setAttribute('data-original-src', originalSrc);
            
            // Intercepter les modifications du src
            try {
                Object.defineProperty(img, 'src', {
                    get: function() {
                        const currentSrc = this.getAttribute('src');
                        return currentSrc && currentSrc.includes('uploads') ? currentSrc : originalSrc;
                    },
                    set: function(value) {
                        // Si on essaie de vider le src ou de le changer pour autre chose que uploads, bloquer
                        if (!value || value === '' || value === window.location.href || (!value.includes('uploads') && value !== originalSrc)) {
                            console.warn('Tentative de modifier le src de l\'image bloquée:', value);
                            this.setAttribute('src', originalSrc);
                            return;
                        }
                        // Si c'est un nouveau chemin valide vers uploads, autoriser
                        if (value.includes('uploads')) {
                            this.setAttribute('src', value);
                            this.setAttribute('data-original-src', value); // Mettre à jour le src original
                        } else {
                            // Sinon, restaurer le src original
                            this.setAttribute('src', originalSrc);
                        }
                    },
                    configurable: false
                });
            } catch(e) {
                // Si on ne peut pas définir la propriété, utiliser un setter alternatif
                console.warn('Impossible de protéger le src de l\'image:', e);
            }
        };
        
        // Protéger toutes les images existantes
        document.querySelectorAll('img.attachment-thumbnail, img.persistent-image').forEach(protectImageSrc);
        
        // Protéger les nouvelles images ajoutées dynamiquement
        const imageObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) {
                        if (node.tagName === 'IMG' && (node.classList.contains('attachment-thumbnail') || node.classList.contains('persistent-image'))) {
                            protectImageSrc(node);
                        }
                        // Vérifier aussi les images dans les nœuds ajoutés
                        node.querySelectorAll && node.querySelectorAll('img.attachment-thumbnail, img.persistent-image').forEach(protectImageSrc);
                    }
                });
            });
        });
        
        imageObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        // Protection finale - s'exécuter en dernier pour garantir l'affichage
        // Cette fonction doit s'exécuter APRÈS tous les autres scripts
        (function() {
            'use strict';
            
            const forceShowImages = function() {
                try {
                    // Forcer l'affichage de toutes les images avec la méthode la plus agressive
                    const images = document.querySelectorAll('img.attachment-thumbnail, img.persistent-image, .attachment-thumbnail, .persistent-image');
                    images.forEach(img => {
                        if (img && img.isConnected && img.tagName === 'IMG') {
                            // Sauvegarder le src original si nécessaire
                            const originalSrc = img.getAttribute('data-original-src') || img.getAttribute('data-src');
                            if (originalSrc && (!img.src || !img.src.includes('uploads'))) {
                                img.src = originalSrc;
                            }
                            
                            // Remplacer complètement le style - méthode la plus agressive
                            const computedStyle = window.getComputedStyle(img);
                            if (computedStyle.display === 'none' || computedStyle.visibility === 'hidden' || parseFloat(computedStyle.opacity) === 0) {
                                // L'image est cachée, forcer l'affichage
                                img.style.cssText = 'max-width: 300px !important; max-height: 200px !important; border-radius: 8px !important; cursor: pointer !important; border: 2px solid rgba(255,122,0,0.3) !important; display: block !important; visibility: visible !important; opacity: 1 !important; position: relative !important; width: auto !important; height: auto !important;';
                                
                                // Forcer aussi via setAttribute pour être sûr
                                img.setAttribute('style', 'max-width: 300px !important; max-height: 200px !important; border-radius: 8px !important; cursor: pointer !important; border: 2px solid rgba(255,122,0,0.3) !important; display: block !important; visibility: visible !important; opacity: 1 !important; position: relative !important; width: auto !important; height: auto !important;');
                            } else {
                                // Même si visible, s'assurer que le style est correct
                                img.style.setProperty('display', 'block', 'important');
                                img.style.setProperty('visibility', 'visible', 'important');
                                img.style.setProperty('opacity', '1', 'important');
                            }
                        }
                    });
                    
                    // Forcer l'affichage des conteneurs
                    const containers = document.querySelectorAll('.attachment-preview, .image-container');
                    containers.forEach(div => {
                        if (div && div.isConnected) {
                            const computedStyle = window.getComputedStyle(div);
                            if (computedStyle.display === 'none' || computedStyle.visibility === 'hidden') {
                                div.style.cssText = (div.style.cssText || '') + ' display: block !important; visibility: visible !important; position: relative !important;';
                                div.setAttribute('style', (div.getAttribute('style') || '') + ' display: block !important; visibility: visible !important; position: relative !important;');
                            }
                        }
                    });
                } catch(e) {
                    console.error('Erreur dans forceShowImages:', e);
                }
            };
            
            // Exécuter immédiatement
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', forceShowImages);
            } else {
                forceShowImages();
            }
            
            // Exécuter après tous les autres scripts possibles
            setTimeout(forceShowImages, 0);
            setTimeout(forceShowImages, 10);
            setTimeout(forceShowImages, 50);
            setTimeout(forceShowImages, 100);
            setTimeout(forceShowImages, 200);
            setTimeout(forceShowImages, 500);
            setTimeout(forceShowImages, 1000);
            setTimeout(forceShowImages, 2000);
            
            // Exécuter après le chargement complet
            window.addEventListener('load', function() {
                setTimeout(forceShowImages, 0);
                setTimeout(forceShowImages, 10);
                setTimeout(forceShowImages, 50);
                setTimeout(forceShowImages, 100);
                setTimeout(forceShowImages, 500);
            });
            
            // Exécuter très fréquemment pour contrer tout script qui cache (toutes les 50ms)
            setInterval(function() {
                forceShowImages();
                
                // Diagnostic : vérifier si des images ont disparu
                const images = document.querySelectorAll('img.attachment-thumbnail, img.persistent-image');
                images.forEach(img => {
                    const computedStyle = window.getComputedStyle(img);
                    if (computedStyle.display === 'none' || computedStyle.visibility === 'hidden' || parseFloat(computedStyle.opacity) === 0) {
                        console.warn('Image cachée détectée:', img.src, 'Display:', computedStyle.display, 'Visibility:', computedStyle.visibility, 'Opacity:', computedStyle.opacity);
                        // Stack trace pour identifier le script qui cache
                        console.trace('Stack trace de l\'image cachée');
                    }
                    if (!img.src || !img.src.includes('uploads')) {
                        console.warn('Image avec src invalide:', img, 'Src actuel:', img.src);
                    }
                });
            }, 50);
            
            // Observer les changements de style en temps réel
            const styleObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        const target = mutation.target;
                        if (target && (target.classList.contains('attachment-thumbnail') || 
                                      target.classList.contains('persistent-image') ||
                                      target.classList.contains('attachment-preview') ||
                                      target.classList.contains('image-container'))) {
                            // Un style a été modifié, forcer immédiatement l'affichage
                            setTimeout(forceShowImages, 0);
                        }
                    }
                });
            });
            
            // Observer tous les éléments d'images
            document.querySelectorAll('.attachment-thumbnail, .persistent-image, .attachment-preview, .image-container').forEach(el => {
                styleObserver.observe(el, {
                    attributes: true,
                    attributeFilter: ['style', 'class']
                });
            });
        })();
        
        // ========== SYSTÈME DE THÈME SOMBRE/CLAIR ==========
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const html = document.documentElement;
        
        // Charger le thème sauvegardé
        const savedTheme = localStorage.getItem('theme') || 'dark';
        html.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
        
        // Toggle du thème
        themeToggle.addEventListener('click', function() {
            const currentTheme = html.getAttribute('data-theme') || 'dark';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });
        
        function updateThemeIcon(theme) {
            if (theme === 'light') {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            } else {
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            }
        }
        
        // ========== SYSTÈME DE NOTIFICATIONS ==========
        const notificationBtn = document.getElementById('notification-btn');
        const notificationDropdown = document.getElementById('notification-dropdown');
        const notificationBadge = document.getElementById('notification-badge');
        const notificationList = document.getElementById('notification-list');
        const markAllReadBtn = document.getElementById('mark-all-read');
        
        let unreadCount = 0;
        let lastCheckTime = 0; // Commencer à 0 pour détecter le chargement initial
        let notificationSound = null;
        let isInitialLoad = true; // Flag pour détecter le premier chargement
        
        // Variable globale pour le contexte audio
        let globalAudioContext = null;
        let audioEnabled = false;
        
        // Activer l'audio automatiquement au chargement de la page
        function enableAudio() {
            if (!audioEnabled && (typeof AudioContext !== 'undefined' || typeof webkitAudioContext !== 'undefined')) {
                try {
                    globalAudioContext = new (window.AudioContext || window.webkitAudioContext)();
                    
                    // Essayer de réactiver immédiatement si suspendu
                    if (globalAudioContext.state === 'suspended') {
                        globalAudioContext.resume().then(() => {
                            audioEnabled = true;
                            console.log('✅ Audio activé automatiquement au chargement (prêt pour les notifications)');
                            // Ne pas jouer de son de test - le son ne se jouera que pour les vraies notifications
                        }).catch(e => {
                            console.log('⚠️ Audio suspendu, sera activé au prochain clic');
                            // Activer quand même pour les prochaines fois
                            audioEnabled = true;
                        });
                    } else {
                        audioEnabled = true;
                        console.log('✅ Audio activé automatiquement au chargement (prêt pour les notifications)');
                        // Ne pas jouer de son de test - le son ne se jouera que pour les vraies notifications
                    }
                } catch(e) {
                    console.log('Erreur activation audio:', e);
                }
            }
        }
        
        // Fonction pour tester l'audio silencieusement (très court et très faible)
        function testAudioSilently() {
            try {
                if (!globalAudioContext || globalAudioContext.state === 'suspended') return;
                
                const oscillator = globalAudioContext.createOscillator();
                const gainNode = globalAudioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(globalAudioContext.destination);
                
                // Utiliser la même note que le son de notification mais très faible
                oscillator.frequency.value = 523.25; // Do
                oscillator.type = 'sine';
                
                // Son très court et très faible (presque inaudible)
                gainNode.gain.setValueAtTime(0, globalAudioContext.currentTime);
                gainNode.gain.linearRampToValueAtTime(0.03, globalAudioContext.currentTime + 0.01);
                gainNode.gain.linearRampToValueAtTime(0, globalAudioContext.currentTime + 0.05);
                
                oscillator.start(globalAudioContext.currentTime);
                oscillator.stop(globalAudioContext.currentTime + 0.05);
            } catch(e) {
                // Ignorer les erreurs de test
            }
        }
        
        // Activer l'audio automatiquement au chargement de la page
        // Essayer plusieurs méthodes pour contourner les restrictions
        window.addEventListener('load', function() {
            setTimeout(enableAudio, 100);
        });
        
        // Activer aussi dès que le DOM est prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(enableAudio, 100);
            });
        } else {
            // DOM déjà chargé
            setTimeout(enableAudio, 100);
        }
        
        // Activer aussi au premier clic (fallback si l'activation automatique échoue)
        document.addEventListener('click', function() {
            if (!audioEnabled) {
                enableAudio();
            }
        }, { once: true });
        
        // Activer aussi au premier appui sur une touche du clavier
        document.addEventListener('keydown', function() {
            if (!audioEnabled) {
                enableAudio();
            }
        }, { once: true });
        
        // Fonction pour jouer le son de notification
        function playNotificationSound() {
            console.log('🔊 Tentative de lecture du son...');
            console.log('Audio activé:', audioEnabled);
            console.log('Contexte audio:', globalAudioContext ? globalAudioContext.state : 'null');
            
            try {
                // S'assurer que l'audio est activé
                if (!audioEnabled) {
                    console.log('⚠️ Audio pas encore activé, activation...');
                    enableAudio();
                }
                
                if (!globalAudioContext) {
                    console.log('⚠️ Création du contexte audio...');
                    globalAudioContext = new (window.AudioContext || window.webkitAudioContext)();
                }
                
                // Réactiver le contexte s'il est suspendu
                if (globalAudioContext.state === 'suspended') {
                    console.log('⚠️ Contexte suspendu, réactivation...');
                    globalAudioContext.resume().then(() => {
                        console.log('✅ Contexte réactivé, lecture du son...');
                        playBeep();
                    }).catch(e => {
                        console.error('❌ Impossible de réactiver le contexte audio:', e);
                        // Essayer quand même
                        playBeep();
                    });
                } else {
                    console.log('✅ Contexte actif, lecture du son...');
                    playBeep();
                }
            } catch(e) {
                console.error('❌ Erreur lecture son:', e);
            }
        }
        
        // Fonction pour jouer un son de notification moderne et agréable
        function playBeep() {
            try {
                if (!globalAudioContext) {
                    console.error('❌ Pas de contexte audio disponible');
                    return;
                }
                
                console.log('🔊 Lecture du son de notification, état:', globalAudioContext.state);
                
                const now = globalAudioContext.currentTime;
                
                // Créer un son de notification plus moderne (triple bip ascendant)
                // Premier bip - Note basse (Do - 523 Hz)
                const osc1 = globalAudioContext.createOscillator();
                const gain1 = globalAudioContext.createGain();
                osc1.connect(gain1);
                gain1.connect(globalAudioContext.destination);
                osc1.frequency.value = 523.25; // Do
                osc1.type = 'sine';
                gain1.gain.setValueAtTime(0, now);
                gain1.gain.linearRampToValueAtTime(0.4, now + 0.05);
                gain1.gain.linearRampToValueAtTime(0, now + 0.2);
                osc1.start(now);
                osc1.stop(now + 0.2);
                
                // Deuxième bip - Note moyenne (Mi - 659 Hz) après 150ms
                setTimeout(() => {
                    try {
                        if (!globalAudioContext || globalAudioContext.state === 'closed') return;
                        const now2 = globalAudioContext.currentTime;
                        const osc2 = globalAudioContext.createOscillator();
                        const gain2 = globalAudioContext.createGain();
                        osc2.connect(gain2);
                        gain2.connect(globalAudioContext.destination);
                        osc2.frequency.value = 659.25; // Mi
                        osc2.type = 'sine';
                        gain2.gain.setValueAtTime(0, now2);
                        gain2.gain.linearRampToValueAtTime(0.4, now2 + 0.05);
                        gain2.gain.linearRampToValueAtTime(0, now2 + 0.2);
                        osc2.start(now2);
                        osc2.stop(now2 + 0.2);
                    } catch(e) {
                        console.error('❌ Erreur deuxième note:', e);
                    }
                }, 150);
                
                // Troisième bip - Note haute (Sol - 784 Hz) après 300ms
                setTimeout(() => {
                    try {
                        if (!globalAudioContext || globalAudioContext.state === 'closed') return;
                        const now3 = globalAudioContext.currentTime;
                        const osc3 = globalAudioContext.createOscillator();
                        const gain3 = globalAudioContext.createGain();
                        osc3.connect(gain3);
                        gain3.connect(globalAudioContext.destination);
                        osc3.frequency.value = 783.99; // Sol
                        osc3.type = 'sine';
                        gain3.gain.setValueAtTime(0, now3);
                        gain3.gain.linearRampToValueAtTime(0.5, now3 + 0.05);
                        gain3.gain.linearRampToValueAtTime(0.3, now3 + 0.15);
                        gain3.gain.linearRampToValueAtTime(0, now3 + 0.3);
                        osc3.start(now3);
                        osc3.stop(now3 + 0.3);
                        console.log('✅ Son de notification joué (Do-Mi-Sol)');
                    } catch(e) {
                        console.error('❌ Erreur troisième note:', e);
                    }
                }, 300);
                
            } catch(e) {
                console.error('❌ Erreur playBeep:', e);
            }
        }
        
        // Fonction de test pour vérifier le son (accessible depuis la console)
        window.testNotificationSound = function() {
            console.log('🧪 Test du son de notification...');
            playNotificationSound();
        };
        
        // Fonction pour vérifier les nouvelles réclamations
        async function checkNewReclamations() {
            try {
                const response = await fetch('reclamback.php?check_notifications=1&last_check=' + lastCheckTime);
                const data = await response.json();
                
                // Vérifier s'il y a des réclamations
                if (data.success && data.new_count > 0) {
                    // Vérifier que c'est le chargement initial (première fois qu'on entre dans le dashboard)
                    if (lastCheckTime === 0 || isInitialLoad) {
                        // C'est l'entrée dans le dashboard - JOUER LE SON UNE SEULE FOIS
                        console.log('🔔 Entrée dans le dashboard - lecture du son de notification');
                        
                        if (data.notifications && data.notifications.length > 0) {
                            data.notifications.forEach(notif => {
                                addNotification(notif, false);
                            });
                        }
                        unreadCount = data.new_count;
                        updateNotificationBadge();
                        
                        // Jouer le son UNIQUEMENT à l'entrée dans le dashboard
                        playNotificationSound();
                    } else {
                        // C'est une nouvelle notification arrivée après l'entrée - PAS DE SON
                        console.log('📋 Nouvelle notification détectée (après entrée) - pas de son');
                        unreadCount += data.new_count;
                        updateNotificationBadge();
                        
                        // Ajouter les nouvelles notifications
                        if (data.notifications && data.notifications.length > 0) {
                            data.notifications.forEach(notif => {
                                addNotification(notif);
                            });
                        }
                        
                        // Notification browser si autorisée (mais pas de son)
                        if (Notification.permission === 'granted') {
                            new Notification('Nouvelle réclamation', {
                                body: `${data.new_count} nouvelle(s) réclamation(s) en attente`,
                                icon: '../images/Nine__1_-removebg-preview.png',
                                tag: 'new-reclamation',
                                silent: true // Pas de son pour les notifications browser non plus
                            });
                        }
                    }
                } else if (data.success) {
                    // Pas de nouvelles notifications
                    console.log('✅ Aucune nouvelle notification');
                }
                
                // Mettre à jour le temps de dernière vérification
                if (lastCheckTime === 0) {
                    // Première vérification, initialiser le temps
                    lastCheckTime = Date.now();
                } else {
                    // Mises à jour suivantes
                    lastCheckTime = Date.now();
                }
            } catch(error) {
                console.error('Erreur lors de la vérification des notifications:', error);
            }
        }
        
        // Fonction pour mettre à jour le badge
        function updateNotificationBadge() {
            if (unreadCount > 0) {
                notificationBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                notificationBadge.classList.remove('hidden');
            } else {
                notificationBadge.classList.add('hidden');
            }
        }
        
        // Fonction pour ajouter une notification
        function addNotification(notif, prepend = true) {
            const notificationItem = document.createElement('div');
            notificationItem.className = 'notification-item unread';
            notificationItem.setAttribute('data-notification-id', notif.id || '');
            notificationItem.innerHTML = `
                <div class="notification-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${notif.title || 'Nouvelle réclamation'}</div>
                    <div class="notification-text">${notif.text || 'Une nouvelle réclamation nécessite votre attention'}</div>
                    <div class="notification-time">${notif.time || 'À l\'instant'}</div>
                </div>
            `;
            
            notificationItem.addEventListener('click', function() {
                // Marquer comme lu
                this.classList.remove('unread');
                unreadCount = Math.max(0, unreadCount - 1);
                updateNotificationBadge();
                
                // Rediriger vers la réclamation
                if (notif.id) {
                    window.location.href = 'reclamback.php#reclamation-' + notif.id;
                }
            });
            
            const emptyMsg = notificationList.querySelector('.notification-empty');
            if (emptyMsg) {
                emptyMsg.remove();
            }
            
            if (prepend) {
                notificationList.insertBefore(notificationItem, notificationList.firstChild);
            } else {
                notificationList.appendChild(notificationItem);
            }
        }
        
        // Fonction pour charger les notifications existantes au démarrage
        async function loadExistingNotifications() {
            try {
                // Récupérer toutes les réclamations non résolues
                const response = await fetch('reclamback.php?get_pending_notifications=1');
                const data = await response.json();
                
                if (data.success && data.notifications && data.notifications.length > 0) {
                    // Vider la liste
                    notificationList.innerHTML = '';
                    
                    // Ajouter toutes les notifications
                    data.notifications.forEach(notif => {
                        addNotification(notif, false);
                    });
                    
                    // Mettre à jour le compteur
                    unreadCount = data.notifications.length;
                    updateNotificationBadge();
                } else {
                    // Afficher le message vide
                    notificationList.innerHTML = '<div class="notification-empty">Aucune nouvelle réclamation</div>';
                }
            } catch(error) {
                console.error('Erreur lors du chargement des notifications:', error);
            }
        }
        
        // Toggle du dropdown de notifications
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('active');
        });
        
        // Fermer le dropdown en cliquant ailleurs
        document.addEventListener('click', function(e) {
            if (!notificationDropdown.contains(e.target) && !notificationBtn.contains(e.target)) {
                notificationDropdown.classList.remove('active');
            }
        });
        
        // Marquer toutes les notifications comme lues
        markAllReadBtn.addEventListener('click', function() {
            unreadCount = 0;
            updateNotificationBadge();
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
        });
        
        // Bouton de test du son
        const testSoundBtn = document.getElementById('test-sound-btn');
        if (testSoundBtn) {
            testSoundBtn.addEventListener('click', function() {
                console.log('🧪 Test du son demandé par l\'utilisateur');
                if (!audioEnabled) {
                    enableAudio();
                    setTimeout(() => {
                        playNotificationSound();
                    }, 100);
                } else {
                    playNotificationSound();
                }
            });
        }
        
        // Demander la permission pour les notifications browser
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
        
        // Charger les notifications existantes au démarrage
        loadExistingNotifications();
        
        // Vérifier les nouvelles réclamations toutes les 30 secondes
        setInterval(checkNewReclamations, 30000);
        
        // Faire une première vérification après 2 secondes pour jouer le son à l'entrée
        // Le son se jouera UNIQUEMENT à cette première vérification (entrée dans le dashboard)
        setTimeout(function() {
            checkNewReclamations();
            // Après cette première vérification, marquer que l'entrée est terminée
            setTimeout(function() {
                isInitialLoad = false;
                console.log('✅ Entrée dans le dashboard terminée - le son ne se jouera plus pour les nouvelles notifications');
            }, 1000);
        }, 2000);
        
        // Initialiser le badge avec le nombre actuel
        const pendingCount = <?php echo $nouveauCount + $enCoursCount; ?>;
        if (pendingCount > 0) {
            unreadCount = pendingCount;
            updateNotificationBadge();
        }
        
        // ========== VUE KANBAN ==========
        const listViewBtn = document.getElementById('list-view-btn');
        const kanbanViewBtn = document.getElementById('kanban-view-btn');
        const reviewsList = document.getElementById('reviews-list');
        const kanbanBoard = document.getElementById('kanban-board');
        
        // Données des réclamations pour le Kanban
        <?php 
        if (!isset($allReclamations) || !is_array($allReclamations)) {
            $allReclamations = [];
        }
        ?>
        const reclamationsData = <?php 
        if (!isset($allReclamations) || !is_array($allReclamations)) {
            $allReclamations = [];
        }
        $kanbanData = array_map(function($r) {
            return [
                'id_reclamation' => isset($r['id_reclamation']) ? intval($r['id_reclamation']) : 0,
                'sujet' => isset($r['sujet']) ? $r['sujet'] : '',
                'description' => isset($r['description']) ? $r['description'] : '',
                'email' => isset($r['email']) ? $r['email'] : '',
                'statut' => isset($r['statut']) ? $r['statut'] : 'nouveau',
                'date_creation' => isset($r['date_creation']) ? $r['date_creation'] : date('Y-m-d H:i:s'),
                'categorie' => isset($r['categorie']) ? $r['categorie'] : 'Other'
            ];
        }, $allReclamations);
        echo json_encode($kanbanData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        ?>;
        
        // Vérifier que escapeHtml est défini
        if (typeof escapeHtml === 'undefined') {
            window.escapeHtml = function(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            };
        }
        
        console.log('📊 Données Kanban chargées:', reclamationsData ? reclamationsData.length : 0, 'réclamations');
        
        // Fonction pour créer une carte Kanban
        function createKanbanCard(reclamation) {
            const card = document.createElement('div');
            card.className = 'kanban-card';
            card.draggable = true;
            card.setAttribute('data-id', reclamation.id_reclamation);
            card.setAttribute('data-status', reclamation.statut || 'nouveau');
            
            const description = (reclamation.description || '').substring(0, 100);
            const date = new Date(reclamation.date_creation || Date.now());
            const dateStr = date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
            
            card.innerHTML = `
                <div class="kanban-card-header">
                    <div>
                        <div class="kanban-card-title">${escapeHtml(reclamation.sujet || 'Sans titre')}</div>
                        <div class="kanban-card-email">${escapeHtml(reclamation.email || '')}</div>
                    </div>
                </div>
                <div class="kanban-card-description">${escapeHtml(description)}${description.length >= 100 ? '...' : ''}</div>
                <div class="kanban-card-footer">
                    <div class="kanban-card-date">
                        <i class="far fa-calendar"></i> ${dateStr}
                    </div>
                    <div class="kanban-card-actions">
                        <button onclick="viewRequestKanban(${reclamation.id_reclamation})" title="Voir détails">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="addResponseKanban(${reclamation.id_reclamation})" title="Ajouter réponse">
                            <i class="fas fa-reply"></i>
                        </button>
                    </div>
                </div>
            `;
            
            return card;
        }
        
        // Fonction pour remplir le Kanban avec les réclamations
        function populateKanban() {
            console.log('🔄 populateKanban() appelée');
            const kanbanNouveau = document.getElementById('kanban-nouveau');
            const kanbanEnCours = document.getElementById('kanban-en_cours');
            const kanbanResolu = document.getElementById('kanban-resolu');
            
            console.log('🔍 Éléments trouvés:', {
                nouveau: !!kanbanNouveau,
                en_cours: !!kanbanEnCours,
                resolu: !!kanbanResolu
            });
            
            if (!kanbanNouveau || !kanbanEnCours || !kanbanResolu) {
                console.warn('⚠️ Éléments non trouvés, réessai dans 100ms...');
                setTimeout(populateKanban, 100);
                return;
            }
            
            console.log('📊 Données:', reclamationsData ? reclamationsData.length : 0, 'réclamations');
            
            if (!reclamationsData || !Array.isArray(reclamationsData)) {
                console.error('❌ reclamationsData n\'est pas un tableau:', typeof reclamationsData);
                return;
            }
            
            if (reclamationsData.length === 0) {
                console.warn('⚠️ Aucune réclamation à afficher');
                return;
            }
            
            // Vider toutes les colonnes
            kanbanNouveau.innerHTML = '';
            kanbanEnCours.innerHTML = '';
            kanbanResolu.innerHTML = '';
            
            const counts = { nouveau: 0, en_cours: 0, resolu: 0 };
            
            // Ajouter les cartes dans les bonnes colonnes selon leur statut
            reclamationsData.forEach((reclamation, index) => {
                if (!reclamation || !reclamation.id_reclamation) {
                    console.warn('⚠️ Réclamation invalide à l\'index', index);
                    return;
                }
                
                // Normaliser le statut
                let status = (reclamation.statut || 'nouveau').toLowerCase().trim();
                console.log(`📝 Réclamation ${index + 1}: ID=${reclamation.id_reclamation}, Statut="${status}"`);
                
                try {
                    // Créer la carte
                    const card = createKanbanCard(reclamation);
                    
                    // Placer dans la bonne colonne selon le statut
                    if (status === 'nouveau' || status === 'new') {
                        kanbanNouveau.appendChild(card);
                        counts.nouveau++;
                        console.log('  ✅ Ajoutée à "Nouveau"');
                    } else if (status === 'en_cours' || status === 'en cours' || status === 'in_progress' || status === 'in progress') {
                        kanbanEnCours.appendChild(card);
                        counts.en_cours++;
                        console.log('  ✅ Ajoutée à "En cours"');
                    } else if (status === 'resolu' || status === 'resolved') {
                        kanbanResolu.appendChild(card);
                        counts.resolu++;
                        console.log('  ✅ Ajoutée à "Résolu"');
                    } else {
                        // Par défaut, mettre dans "nouveau"
                        console.warn('  ⚠️ Statut inconnu, mise dans "Nouveau"');
                        kanbanNouveau.appendChild(card);
                        counts.nouveau++;
                    }
                } catch(e) {
                    console.error('❌ Erreur création carte:', e, reclamation);
                }
            });
            
            console.log('✅ Kanban rempli:', counts);
            
            // Mettre à jour les compteurs
            const countNouveau = document.getElementById('kanban-count-nouveau');
            const countEnCours = document.getElementById('kanban-count-en_cours');
            const countResolu = document.getElementById('kanban-count-resolu');
            
            if (countNouveau) countNouveau.textContent = counts.nouveau;
            if (countEnCours) countEnCours.textContent = counts.en_cours;
            if (countResolu) countResolu.textContent = counts.resolu;
            
            // Initialiser le drag & drop
            initKanbanDragDrop();
        }
        
        // Appeler populateKanban() automatiquement au chargement
        // Attendre que le DOM soit prêt
        function initKanbanAuto() {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    populateKanban();
                    setTimeout(populateKanban, 500);
                });
            } else {
                populateKanban();
                setTimeout(populateKanban, 500);
            }
        }
        
        // Initialiser immédiatement
        initKanbanAuto();
        
        // Fonction pour initialiser le drag & drop
        function initKanbanDragDrop() {
            const cards = document.querySelectorAll('.kanban-card');
            const columns = document.querySelectorAll('.kanban-column-content');
            
            cards.forEach(card => {
                card.addEventListener('dragstart', function(e) {
                    e.dataTransfer.setData('text/plain', this.getAttribute('data-id'));
                    this.classList.add('dragging');
                });
                
                card.addEventListener('dragend', function(e) {
                    this.classList.remove('dragging');
                    document.querySelectorAll('.kanban-column').forEach(col => {
                        col.classList.remove('drag-over');
                    });
                });
            });
            
            columns.forEach(column => {
                column.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.closest('.kanban-column').classList.add('drag-over');
                });
                
                column.addEventListener('dragleave', function(e) {
                    this.closest('.kanban-column').classList.remove('drag-over');
                });
                
                column.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.closest('.kanban-column').classList.remove('drag-over');
                    
                    const cardId = e.dataTransfer.getData('text/plain');
                    const newStatus = this.closest('.kanban-column').getAttribute('data-status');
                    const card = document.querySelector(`.kanban-card[data-id="${cardId}"]`);
                    
                    if (card && card.getAttribute('data-status') !== newStatus) {
                        // Mettre à jour le statut via AJAX
                        updateReclamationStatus(cardId, newStatus, card);
                    }
                });
            });
        }
        
        // Fonction pour mettre à jour le statut d'une réclamation
        async function updateReclamationStatus(id, newStatus, cardElement) {
            try {
                const formData = new FormData();
                formData.append('update_status', '1');
                formData.append('id_reclamation', id);
                formData.append('statut', newStatus);
                
                const response = await fetch('reclamback.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    // Déplacer la carte vers la nouvelle colonne
                    const targetColumn = document.getElementById(`kanban-${newStatus}`);
                    cardElement.setAttribute('data-status', newStatus);
                    targetColumn.appendChild(cardElement);
                    
                    // Mettre à jour les compteurs
                    populateKanban();
                    
                    // Recharger la page pour synchroniser
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    console.error('❌ Erreur lors de la mise à jour du statut');
                    alert('Erreur lors de la mise à jour du statut');
                }
            } catch(error) {
                console.error('❌ Erreur:', error);
                alert('Erreur: ' + error.message);
            }
        }
        
        // Fonctions helper pour les boutons des cartes Kanban
        window.viewRequestKanban = function(id) {
            const btn = document.querySelector(`.view-request[data-id="${id}"]`);
            if (btn) {
                btn.click();
            } else {
                window.location.href = 'reclamback.php?view_id=' + id;
            }
        };
        
        window.addResponseKanban = function(id) {
            const btn = document.querySelector(`.add-response-btn[data-id="${id}"]`);
            if (btn) {
                btn.click();
            }
        };
        
        // Toggle entre vue Liste et Kanban
        if (listViewBtn && kanbanViewBtn) {
            listViewBtn.addEventListener('click', function() {
                this.classList.add('active');
                kanbanViewBtn.classList.remove('active');
                reviewsList.style.display = 'flex';
                kanbanBoard.style.display = 'none';
                localStorage.setItem('view-mode', 'list');
            });
            
            kanbanViewBtn.addEventListener('click', function() {
                this.classList.add('active');
                listViewBtn.classList.remove('active');
                reviewsList.style.display = 'none';
                kanbanBoard.style.display = 'grid';
                
                // Toujours remplir le Kanban quand on clique sur le bouton
                console.log('🖱️ Bouton Kanban cliqué, remplissage...');
                setTimeout(function() {
                    populateKanban();
                }, 100);
                
                localStorage.setItem('view-mode', 'kanban');
            });
            
            // Initialiser le Kanban automatiquement au chargement
            function initKanbanView() {
                console.log('🔄 initKanbanView() appelée');
                console.log('📊 Données disponibles:', reclamationsData ? reclamationsData.length : 0, 'réclamations');
                console.log('📊 État du DOM:', document.readyState);
                
                // Appeler immédiatement
                populateKanban();
                
                // Attendre que le DOM soit complètement chargé
                setTimeout(function() {
                    console.log('⏰ Réessai après 100ms...');
                    populateKanban();
                    
                    // Restaurer la vue sauvegardée après avoir rempli le Kanban
                    const savedView = localStorage.getItem('view-mode') || 'list';
                    console.log('💾 Vue sauvegardée:', savedView);
                    if (savedView === 'kanban') {
                        // Activer la vue Kanban
                        kanbanViewBtn.classList.add('active');
                        listViewBtn.classList.remove('active');
                        reviewsList.style.display = 'none';
                        kanbanBoard.style.display = 'grid';
                        console.log('✅ Vue Kanban activée');
                    }
                }, 100);
                
                // Réessayer après 500ms au cas où
                setTimeout(function() {
                    console.log('⏰ Réessai après 500ms...');
                    populateKanban();
                }, 500);
            }
            
            // Initialiser au chargement de la page
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initKanbanView);
            } else {
                initKanbanView();
            }
            
            // Réessayer après un court délai pour être sûr
            setTimeout(populateKanban, 200);
        }
        
        // Toujours essayer de remplir le Kanban au chargement
        setTimeout(populateKanban, 300);
        setTimeout(populateKanban, 1000);
    </script>
</body>
</html>