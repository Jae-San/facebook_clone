<?php
/**
 * API Chat
 * Gestion des conversations, messages privés et chat en temps réel
 */

require_once __DIR__ . '/lib/Utils.php';
require_once __DIR__ . '/lib/AuthHelper.php';
require_once __DIR__ . '/models/Message.php';

// Gestion des requêtes CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    Utils::sendJSONResponse([], 200);
}

// Router simple basé sur la méthode HTTP et les paramètres
$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['action'] ?? '';

try {
    $messageModel = new Message();
    
    switch ($method) {
        case 'POST':
            switch ($path) {
                case 'conversation':
                    handleCreateConversation($messageModel);
                    break;
                    
                case 'message':
                    handleSendMessage($messageModel);
                    break;
                    
                case 'session':
                    handleCreateSession($messageModel);
                    break;
                    
                default:
                    Utils::sendErrorResponse('Action non reconnue', 404);
            }
            break;
            
        case 'GET':
            switch ($path) {
                case 'conversations':
                    handleGetConversations($messageModel);
                    break;
                    
                case 'messages':
                    handleGetMessages($messageModel);
                    break;
                    
                case 'unread':
                    handleGetUnreadCount($messageModel);
                    break;
                    
                default:
                    Utils::sendErrorResponse('Action non reconnue', 404);
            }
            break;
            
        case 'PUT':
            switch ($path) {
                case 'message':
                    handleUpdateMessage($messageModel);
                    break;
                    
                case 'read':
                    handleMarkAsRead($messageModel);
                    break;
                    
                default:
                    Utils::sendErrorResponse('Action non reconnue', 404);
            }
            break;
            
        case 'DELETE':
            switch ($path) {
                case 'message':
                    handleDeleteMessage($messageModel);
                    break;
                    
                case 'conversation':
                    handleDeleteConversation($messageModel);
                    break;
                    
                default:
                    Utils::sendErrorResponse('Action non reconnue', 404);
            }
            break;
            
        default:
            Utils::sendErrorResponse('Méthode HTTP non supportée', 405);
    }
    
} catch (Exception $e) {
    error_log("Erreur dans chat.php: " . $e->getMessage());
    Utils::sendErrorResponse('Erreur interne du serveur', 500);
}

/**
 * Créer une nouvelle conversation
 */
function handleCreateConversation($messageModel) {
    AuthHelper::requireAuth();
    
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    // Validation des données
    $rules = [
        'participants' => ['required' => true],
        'name' => ['required' => false, 'max_length' => 255],
        'is_group' => ['required' => false, 'type' => 'boolean']
    ];
    
    $validation = Utils::validateInput($data, $rules);
    
    if (!empty($validation['errors'])) {
        Utils::sendErrorResponse('Données invalides', 400, $validation['errors']);
    }
    
    $cleaned = $validation['cleaned'];
    $currentUser = AuthHelper::getCurrentUser();
    
    // Ajouter l'utilisateur actuel aux participants
    if (!in_array($currentUser['id'], $cleaned['participants'])) {
        $cleaned['participants'][] = $currentUser['id'];
    }
    
    $result = $messageModel->createConversation($cleaned, $currentUser['id']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'conversation' => $result['conversation']
        ], $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400, $result['errors'] ?? null);
    }
}

/**
 * Envoyer un message
 */
function handleSendMessage($messageModel) {
    AuthHelper::requireAuth();
    
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    // Validation des données
    $rules = [
        'conversation_id' => ['required' => true, 'type' => 'int'],
        'content' => ['required' => true, 'min_length' => 1, 'max_length' => 2000],
        'message_type' => ['required' => false, 'allowed_values' => ['text', 'image', 'video', 'file']],
        'file_url' => ['required' => false],
        'file_name' => ['required' => false],
        'file_size' => ['required' => false, 'type' => 'int']
    ];
    
    $validation = Utils::validateInput($data, $rules);
    
    if (!empty($validation['errors'])) {
        Utils::sendErrorResponse('Données invalides', 400, $validation['errors']);
    }
    
    $cleaned = $validation['cleaned'];
    $currentUser = AuthHelper::getCurrentUser();
    
    // Vérifier que l'utilisateur fait partie de la conversation
    if (!$messageModel->isUserInConversation($cleaned['conversation_id'], $currentUser['id'])) {
        Utils::sendErrorResponse('Vous n\'êtes pas autorisé à envoyer des messages dans cette conversation', 403);
    }
    
    $result = $messageModel->sendMessage($cleaned, $currentUser['id']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'message' => $result['message']
        ], $result['message_text']);
    } else {
        Utils::sendErrorResponse($result['message'], 400, $result['errors'] ?? null);
    }
}

/**
 * Créer une session de chat
 */
function handleCreateSession($messageModel) {
    AuthHelper::requireAuth();
    
    $currentUser = AuthHelper::getCurrentUser();
    
    $result = $messageModel->createChatSession($currentUser['id']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'session_token' => $result['session_token']
        ], 'Session de chat créée');
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Obtenir les conversations de l'utilisateur
 */
function handleGetConversations($messageModel) {
    AuthHelper::requireAuth();
    
    $currentUser = AuthHelper::getCurrentUser();
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = (int)($_GET['offset'] ?? 0);
    
    $result = $messageModel->getConversations($currentUser['id'], $limit, $offset);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'conversations' => $result['conversations']
        ], 'Conversations récupérées avec succès');
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Obtenir les messages d'une conversation
 */
function handleGetMessages($messageModel) {
    AuthHelper::requireAuth();
    
    $conversationId = $_GET['conversation_id'] ?? null;
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    
    if (!$conversationId) {
        Utils::sendErrorResponse('ID de conversation requis', 400);
    }
    
    $currentUser = AuthHelper::getCurrentUser();
    
    // Vérifier que l'utilisateur fait partie de la conversation
    if (!$messageModel->isUserInConversation($conversationId, $currentUser['id'])) {
        Utils::sendErrorResponse('Vous n\'êtes pas autorisé à voir cette conversation', 403);
    }
    
    $result = $messageModel->getMessages($conversationId, $currentUser['id'], $limit, $offset);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'messages' => $result['messages'],
            'has_more' => $result['has_more'] ?? false
        ], 'Messages récupérés avec succès');
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Obtenir le nombre de messages non lus
 */
function handleGetUnreadCount($messageModel) {
    AuthHelper::requireAuth();
    
    $currentUser = AuthHelper::getCurrentUser();
    $conversationId = $_GET['conversation_id'] ?? null;
    
    $result = $messageModel->getUnreadCount($currentUser['id'], $conversationId);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'unread_count' => $result['unread_count']
        ], 'Nombre de messages non lus récupéré');
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Mettre à jour un message
 */
function handleUpdateMessage($messageModel) {
    AuthHelper::requireAuth();
    
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    // Validation des données
    $rules = [
        'message_id' => ['required' => true, 'type' => 'int'],
        'content' => ['required' => true, 'min_length' => 1, 'max_length' => 2000]
    ];
    
    $validation = Utils::validateInput($data, $rules);
    
    if (!empty($validation['errors'])) {
        Utils::sendErrorResponse('Données invalides', 400, $validation['errors']);
    }
    
    $cleaned = $validation['cleaned'];
    $currentUser = AuthHelper::getCurrentUser();
    
    $result = $messageModel->updateMessage($cleaned['message_id'], $cleaned['content'], $currentUser['id']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'message' => $result['message']
        ], $result['message_text']);
    } else {
        Utils::sendErrorResponse($result['message'], 400, $result['errors'] ?? null);
    }
}

/**
 * Marquer les messages comme lus
 */
function handleMarkAsRead($messageModel) {
    AuthHelper::requireAuth();
    
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    // Validation des données
    $rules = [
        'conversation_id' => ['required' => true, 'type' => 'int'],
        'message_ids' => ['required' => false] // Optionnel, si non fourni, marquer tous les messages de la conversation
    ];
    
    $validation = Utils::validateInput($data, $rules);
    
    if (!empty($validation['errors'])) {
        Utils::sendErrorResponse('Données invalides', 400, $validation['errors']);
    }
    
    $cleaned = $validation['cleaned'];
    $currentUser = AuthHelper::getCurrentUser();
    
    // Vérifier que l'utilisateur fait partie de la conversation
    if (!$messageModel->isUserInConversation($cleaned['conversation_id'], $currentUser['id'])) {
        Utils::sendErrorResponse('Vous n\'êtes pas autorisé à accéder à cette conversation', 403);
    }
    
    $result = $messageModel->markAsRead($cleaned['conversation_id'], $currentUser['id'], $cleaned['message_ids'] ?? null);
    
    if ($result['success']) {
        Utils::sendSuccessResponse(null, $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Supprimer un message
 */
function handleDeleteMessage($messageModel) {
    AuthHelper::requireAuth();
    
    $messageId = $_GET['message_id'] ?? null;
    
    if (!$messageId) {
        Utils::sendErrorResponse('ID de message requis', 400);
    }
    
    $currentUser = AuthHelper::getCurrentUser();
    
    $result = $messageModel->deleteMessage($messageId, $currentUser['id']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse(null, $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Supprimer une conversation
 */
function handleDeleteConversation($messageModel) {
    AuthHelper::requireAuth();
    
    $conversationId = $_GET['conversation_id'] ?? null;
    
    if (!$conversationId) {
        Utils::sendErrorResponse('ID de conversation requis', 400);
    }
    
    $currentUser = AuthHelper::getCurrentUser();
    
    // Vérifier que l'utilisateur fait partie de la conversation
    if (!$messageModel->isUserInConversation($conversationId, $currentUser['id'])) {
        Utils::sendErrorResponse('Vous n\'êtes pas autorisé à supprimer cette conversation', 403);
    }
    
    $result = $messageModel->deleteConversation($conversationId, $currentUser['id']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse(null, $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}
