<?php
/**
 * API d'Authentification
 * Gestion de l'inscription, connexion, déconnexion et réinitialisation de mot de passe
 */

require_once __DIR__ . '/lib/Utils.php';
require_once __DIR__ . '/models/User.php';

// Gestion des requêtes CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    Utils::sendJSONResponse([], 200);
}

// Router simple basé sur la méthode HTTP et les paramètres
$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['action'] ?? '';

try {
    $userModel = new User();
    
    switch ($method) {
        case 'POST':
            switch ($path) {
                case 'register':
                    handleRegister($userModel);
                    break;
                    
                case 'login':
                    handleLogin($userModel);
                    break;
                    
                case 'logout':
                    handleLogout($userModel);
                    break;
                    
                case 'forgot-password':
                    handleForgotPassword($userModel);
                    break;
                    
                case 'reset-password':
                    handleResetPassword($userModel);
                    break;
                    
                case 'change-password':
                    handleChangePassword($userModel);
                    break;
                    
                case 'verify-token':
                    handleVerifyToken($userModel);
                    break;
                    
                default:
                    Utils::sendErrorResponse('Action non reconnue', 404);
            }
            break;
            
        case 'GET':
            switch ($path) {
                case 'profile':
                    handleGetProfile($userModel);
                    break;
                    
                case 'search':
                    handleSearchUsers($userModel);
                    break;
                    
                default:
                    Utils::sendErrorResponse('Action non reconnue', 404);
            }
            break;
            
        case 'PUT':
            switch ($path) {
                case 'profile':
                    handleUpdateProfile($userModel);
                    break;
                    
                default:
                    Utils::sendErrorResponse('Action non reconnue', 404);
            }
            break;
            
        default:
            Utils::sendErrorResponse('Méthode HTTP non supportée', 405);
    }
    
} catch (Exception $e) {
    error_log("Erreur dans auth.php: " . $e->getMessage());
    Utils::sendErrorResponse('Erreur interne du serveur', 500);
}

/**
 * Gérer l'inscription d'un nouvel utilisateur
 */
function handleRegister($userModel) {
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    $result = $userModel->create($data);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'user_id' => $result['user_id']
        ], $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'] ?? 'Erreur lors de l\'inscription', 400, $result['errors'] ?? null);
    }
}

/**
 * Gérer la connexion d'un utilisateur
 */
function handleLogin($userModel) {
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    // Validation des données de connexion
    $rules = [
        'email' => ['required' => true, 'type' => 'email'],
        'password' => ['required' => true]
    ];
    
    $validation = Utils::validateInput($data, $rules);
    
    if (!empty($validation['errors'])) {
        Utils::sendErrorResponse('Données invalides', 400, $validation['errors']);
    }
    
    $cleaned = $validation['cleaned'];
    $result = $userModel->authenticate($cleaned['email'], $cleaned['password']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'user' => $result['user'],
            'token' => $result['token']
        ], $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 401);
    }
}

/**
 * Gérer la déconnexion d'un utilisateur
 */
function handleLogout($userModel) {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';
    
    // Extraire le token du header Authorization (Bearer token)
    if (strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }
    
    if (empty($token)) {
        Utils::sendErrorResponse('Token d\'authentification requis', 401);
    }
    
    $result = $userModel->logout($token);
    
    if ($result['success']) {
        Utils::sendSuccessResponse(null, $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Gérer la demande de réinitialisation de mot de passe
 */
function handleForgotPassword($userModel) {
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    // Validation de l'email
    $rules = [
        'email' => ['required' => true, 'type' => 'email']
    ];
    
    $validation = Utils::validateInput($data, $rules);
    
    if (!empty($validation['errors'])) {
        Utils::sendErrorResponse('Email invalide', 400, $validation['errors']);
    }
    
    $cleaned = $validation['cleaned'];
    $result = $userModel->requestPasswordReset($cleaned['email']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse(null, $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Gérer la réinitialisation de mot de passe avec token
 */
function handleResetPassword($userModel) {
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    // Validation des données
    $rules = [
        'token' => ['required' => true],
        'password' => ['required' => true, 'min_length' => 8]
    ];
    
    $validation = Utils::validateInput($data, $rules);
    
    if (!empty($validation['errors'])) {
        Utils::sendErrorResponse('Données invalides', 400, $validation['errors']);
    }
    
    $cleaned = $validation['cleaned'];
    $result = $userModel->resetPassword($cleaned['token'], $cleaned['password']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse(null, $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400, $result['errors'] ?? null);
    }
}

/**
 * Gérer le changement de mot de passe (utilisateur connecté)
 */
function handleChangePassword($userModel) {
    // Vérifier l'authentification
    $currentUser = getCurrentUser($userModel);
    if (!$currentUser) {
        Utils::sendErrorResponse('Authentification requise', 401);
    }
    
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    // Validation des données
    $rules = [
        'current_password' => ['required' => true],
        'new_password' => ['required' => true, 'min_length' => 8]
    ];
    
    $validation = Utils::validateInput($data, $rules);
    
    if (!empty($validation['errors'])) {
        Utils::sendErrorResponse('Données invalides', 400, $validation['errors']);
    }
    
    $cleaned = $validation['cleaned'];
    $result = $userModel->changePassword($currentUser['id'], $cleaned['current_password'], $cleaned['new_password']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse(null, $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400, $result['errors'] ?? null);
    }
}

/**
 * Vérifier un token d'authentification
 */
function handleVerifyToken($userModel) {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';
    
    // Extraire le token du header Authorization (Bearer token)
    if (strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }
    
    if (empty($token)) {
        Utils::sendErrorResponse('Token d\'authentification requis', 401);
    }
    
    $result = $userModel->verifyToken($token);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'user' => $result['user']
        ], 'Token valide');
    } else {
        Utils::sendErrorResponse($result['message'], 401);
    }
}

/**
 * Obtenir le profil de l'utilisateur connecté
 */
function handleGetProfile($userModel) {
    // Vérifier l'authentification
    $currentUser = getCurrentUser($userModel);
    if (!$currentUser) {
        Utils::sendErrorResponse('Authentification requise', 401);
    }
    
    $result = $userModel->getById($currentUser['id']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'user' => $result['user']
        ], 'Profil récupéré avec succès');
    } else {
        Utils::sendErrorResponse($result['message'], 404);
    }
}

/**
 * Mettre à jour le profil de l'utilisateur connecté
 */
function handleUpdateProfile($userModel) {
    // Vérifier l'authentification
    $currentUser = getCurrentUser($userModel);
    if (!$currentUser) {
        Utils::sendErrorResponse('Authentification requise', 401);
    }
    
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    $result = $userModel->update($currentUser['id'], $data);
    
    if ($result['success']) {
        Utils::sendSuccessResponse(null, $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400, $result['errors'] ?? null);
    }
}

/**
 * Rechercher des utilisateurs
 */
function handleSearchUsers($userModel) {
    // Vérifier l'authentification
    $currentUser = getCurrentUser($userModel);
    if (!$currentUser) {
        Utils::sendErrorResponse('Authentification requise', 401);
    }
    
    $query = $_GET['q'] ?? '';
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = (int)($_GET['offset'] ?? 0);
    
    if (empty($query)) {
        Utils::sendErrorResponse('Paramètre de recherche requis', 400);
    }
    
    $result = $userModel->search($query, $currentUser['id'], $limit, $offset);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'users' => $result['users']
        ], 'Recherche effectuée avec succès');
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Obtenir l'utilisateur actuel à partir du token
 */
function getCurrentUser($userModel) {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';
    
    // Extraire le token du header Authorization (Bearer token)
    if (strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }
    
    if (empty($token)) {
        return null;
    }
    
    $result = $userModel->verifyToken($token);
    
    if ($result['success']) {
        return $result['user'];
    }
    
    return null;
}
