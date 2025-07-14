<?php
/**
 * API Administrateur
 * Gestion complète des utilisateurs, articles, et statistiques
 */

require_once __DIR__ . '/lib/Utils.php';
require_once __DIR__ . '/lib/AuthHelper.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Post.php';

// Gestion des requêtes CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    Utils::sendJSONResponse([], 200);
}

// Router simple basé sur la méthode HTTP et les paramètres
$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['action'] ?? '';

try {
    $userModel = new User();
    $postModel = new Post();
    
    switch ($method) {
        case 'POST':
            switch ($path) {
                case 'user':
                    handleCreateUser($userModel);
                    break;
                    
                case 'role':
                    handleUpdateUserRole($userModel);
                    break;
                    
                case 'ban':
                    handleBanUser($userModel);
                    break;
                    
                case 'unban':
                    handleUnbanUser($userModel);
                    break;
                    
                case 'delete-post':
                    handleDeletePost($postModel);
                    break;
                    
                case 'delete-comment':
                    handleDeleteComment($postModel);
                    break;
                    
                default:
                    Utils::sendErrorResponse('Action non reconnue', 404);
            }
            break;
            
        case 'GET':
            switch ($path) {
                case 'users':
                    handleGetUsers($userModel);
                    break;
                    
                case 'user':
                    handleGetUser($userModel);
                    break;
                    
                case 'posts':
                    handleGetPosts($postModel);
                    break;
                    
                case 'comments':
                    handleGetComments($postModel);
                    break;
                    
                case 'reports':
                    handleGetReports($userModel);
                    break;
                    
                case 'statistics':
                    handleGetStatistics($userModel, $postModel);
                    break;
                    
                case 'dashboard':
                    handleGetDashboard($userModel, $postModel);
                    break;
                    
                default:
                    Utils::sendErrorResponse('Action non reconnue', 404);
            }
            break;
            
        case 'PUT':
            switch ($path) {
                case 'user':
                    handleUpdateUser($userModel);
                    break;
                    
                case 'report':
                    handleUpdateReport($userModel);
                    break;
                    
                default:
                    Utils::sendErrorResponse('Action non reconnue', 404);
            }
            break;
            
        case 'DELETE':
            switch ($path) {
                case 'user':
                    handleDeleteUser($userModel);
                    break;
                    
                default:
                    Utils::sendErrorResponse('Action non reconnue', 404);
            }
            break;
            
        default:
            Utils::sendErrorResponse('Méthode HTTP non supportée', 405);
    }
    
} catch (Exception $e) {
    error_log("Erreur dans admin.php: " . $e->getMessage());
    Utils::sendErrorResponse('Erreur interne du serveur', 500);
}

/**
 * Créer un nouvel utilisateur (Admin)
 */
function handleCreateUser($userModel) {
    AuthHelper::requireAdmin();
    
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    // Validation des données
    $rules = [
        'username' => ['required' => true, 'min_length' => 3, 'max_length' => 50],
        'email' => ['required' => true, 'type' => 'email'],
        'password' => ['required' => true, 'min_length' => 8],
        'first_name' => ['required' => true, 'max_length' => 100],
        'last_name' => ['required' => true, 'max_length' => 100],
        'role_id' => ['required' => false, 'type' => 'int', 'allowed_values' => [1, 2, 3]]
    ];
    
    $validation = Utils::validateInput($data, $rules);
    
    if (!empty($validation['errors'])) {
        Utils::sendErrorResponse('Données invalides', 400, $validation['errors']);
    }
    
    $cleaned = $validation['cleaned'];
    $result = $userModel->createUserByAdmin($cleaned);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'user' => $result['user']
        ], $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400, $result['errors'] ?? null);
    }
}

/**
 * Mettre à jour le rôle d'un utilisateur
 */
function handleUpdateUserRole($userModel) {
    AuthHelper::requireAdmin();
    
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    // Validation des données
    $rules = [
        'user_id' => ['required' => true, 'type' => 'int'],
        'role_id' => ['required' => true, 'type' => 'int', 'allowed_values' => [1, 2, 3]]
    ];
    
    $validation = Utils::validateInput($data, $rules);
    
    if (!empty($validation['errors'])) {
        Utils::sendErrorResponse('Données invalides', 400, $validation['errors']);
    }
    
    $cleaned = $validation['cleaned'];
    $result = $userModel->updateUserRole($cleaned['user_id'], $cleaned['role_id']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'user' => $result['user']
        ], $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Bannir un utilisateur
 */
function handleBanUser($userModel) {
    AuthHelper::requireModerator();
    
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    // Validation des données
    $rules = [
        'user_id' => ['required' => true, 'type' => 'int'],
        'reason' => ['required' => false, 'max_length' => 500]
    ];
    
    $validation = Utils::validateInput($data, $rules);
    
    if (!empty($validation['errors'])) {
        Utils::sendErrorResponse('Données invalides', 400, $validation['errors']);
    }
    
    $cleaned = $validation['cleaned'];
    $currentUser = AuthHelper::getCurrentUser();
    $result = $userModel->banUser($cleaned['user_id'], $cleaned['reason'] ?? null, $currentUser['id']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse(null, $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Débannir un utilisateur
 */
function handleUnbanUser($userModel) {
    AuthHelper::requireModerator();
    
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    // Validation des données
    $rules = [
        'user_id' => ['required' => true, 'type' => 'int']
    ];
    
    $validation = Utils::validateInput($data, $rules);
    
    if (!empty($validation['errors'])) {
        Utils::sendErrorResponse('Données invalides', 400, $validation['errors']);
    }
    
    $cleaned = $validation['cleaned'];
    $currentUser = AuthHelper::getCurrentUser();
    $result = $userModel->unbanUser($cleaned['user_id'], $currentUser['id']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse(null, $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Supprimer un post
 */
function handleDeletePost($postModel) {
    AuthHelper::requireModerator();
    
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    // Validation des données
    $rules = [
        'post_id' => ['required' => true, 'type' => 'int'],
        'reason' => ['required' => false, 'max_length' => 500]
    ];
    
    $validation = Utils::validateInput($data, $rules);
    
    if (!empty($validation['errors'])) {
        Utils::sendErrorResponse('Données invalides', 400, $validation['errors']);
    }
    
    $cleaned = $validation['cleaned'];
    $currentUser = AuthHelper::getCurrentUser();
    $result = $postModel->deletePostByAdmin($cleaned['post_id'], $cleaned['reason'] ?? null, $currentUser['id']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse(null, $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Supprimer un commentaire
 */
function handleDeleteComment($postModel) {
    AuthHelper::requireModerator();
    
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    // Validation des données
    $rules = [
        'comment_id' => ['required' => true, 'type' => 'int'],
        'reason' => ['required' => false, 'max_length' => 500]
    ];
    
    $validation = Utils::validateInput($data, $rules);
    
    if (!empty($validation['errors'])) {
        Utils::sendErrorResponse('Données invalides', 400, $validation['errors']);
    }
    
    $cleaned = $validation['cleaned'];
    $currentUser = AuthHelper::getCurrentUser();
    $result = $postModel->deleteCommentByAdmin($cleaned['comment_id'], $cleaned['reason'] ?? null, $currentUser['id']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse(null, $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Obtenir la liste des utilisateurs
 */
function handleGetUsers($userModel) {
    AuthHelper::requireModerator();
    
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = (int)($_GET['offset'] ?? 0);
    $search = $_GET['search'] ?? '';
    $role = $_GET['role'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $result = $userModel->getUsersForAdmin($limit, $offset, $search, $role, $status);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'users' => $result['users'],
            'total' => $result['total'] ?? 0
        ], 'Utilisateurs récupérés avec succès');
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Obtenir les détails d'un utilisateur
 */
function handleGetUser($userModel) {
    AuthHelper::requireModerator();
    
    $userId = $_GET['user_id'] ?? null;
    
    if (!$userId) {
        Utils::sendErrorResponse('ID utilisateur requis', 400);
    }
    
    $result = $userModel->getUserDetailsForAdmin($userId);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'user' => $result['user'],
            'statistics' => $result['statistics'] ?? []
        ], 'Détails utilisateur récupérés avec succès');
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Obtenir la liste des posts
 */
function handleGetPosts($postModel) {
    AuthHelper::requireModerator();
    
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = (int)($_GET['offset'] ?? 0);
    $search = $_GET['search'] ?? '';
    $user_id = $_GET['user_id'] ?? '';
    
    $result = $postModel->getPostsForAdmin($limit, $offset, $search, $user_id);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'posts' => $result['posts'],
            'total' => $result['total'] ?? 0
        ], 'Posts récupérés avec succès');
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Obtenir la liste des commentaires
 */
function handleGetComments($postModel) {
    AuthHelper::requireModerator();
    
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = (int)($_GET['offset'] ?? 0);
    $search = $_GET['search'] ?? '';
    $user_id = $_GET['user_id'] ?? '';
    $post_id = $_GET['post_id'] ?? '';
    
    $result = $postModel->getCommentsForAdmin($limit, $offset, $search, $user_id, $post_id);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'comments' => $result['comments'],
            'total' => $result['total'] ?? 0
        ], 'Commentaires récupérés avec succès');
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Obtenir la liste des rapports
 */
function handleGetReports($userModel) {
    AuthHelper::requireModerator();
    
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = (int)($_GET['offset'] ?? 0);
    $status = $_GET['status'] ?? '';
    $type = $_GET['type'] ?? '';
    
    $result = $userModel->getReportsForAdmin($limit, $offset, $status, $type);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'reports' => $result['reports'],
            'total' => $result['total'] ?? 0
        ], 'Rapports récupérés avec succès');
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Obtenir les statistiques générales
 */
function handleGetStatistics($userModel, $postModel) {
    AuthHelper::requireModerator();
    
    $period = $_GET['period'] ?? '30'; // jours
    
    $result = $userModel->getStatistics($period);
    $postStats = $postModel->getStatistics($period);
    
    if ($result['success'] && $postStats['success']) {
        $statistics = array_merge($result['statistics'], $postStats['statistics']);
        
        Utils::sendSuccessResponse([
            'statistics' => $statistics,
            'period' => $period
        ], 'Statistiques récupérées avec succès');
    } else {
        Utils::sendErrorResponse('Erreur lors de la récupération des statistiques', 400);
    }
}

/**
 * Obtenir les données du tableau de bord
 */
function handleGetDashboard($userModel, $postModel) {
    AuthHelper::requireModerator();
    
    $result = $userModel->getDashboardData();
    $postDashboard = $postModel->getDashboardData();
    
    if ($result['success'] && $postDashboard['success']) {
        $dashboard = array_merge($result['dashboard'], $postDashboard['dashboard']);
        
        Utils::sendSuccessResponse([
            'dashboard' => $dashboard
        ], 'Données du tableau de bord récupérées avec succès');
    } else {
        Utils::sendErrorResponse('Erreur lors de la récupération du tableau de bord', 400);
    }
}

/**
 * Mettre à jour un utilisateur
 */
function handleUpdateUser($userModel) {
    AuthHelper::requireModerator();
    
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    // Validation des données
    $rules = [
        'user_id' => ['required' => true, 'type' => 'int'],
        'first_name' => ['required' => false, 'max_length' => 100],
        'last_name' => ['required' => false, 'max_length' => 100],
        'email' => ['required' => false, 'type' => 'email'],
        'bio' => ['required' => false, 'max_length' => 1000],
        'location' => ['required' => false, 'max_length' => 255],
        'phone' => ['required' => false, 'max_length' => 20]
    ];
    
    $validation = Utils::validateInput($data, $rules);
    
    if (!empty($validation['errors'])) {
        Utils::sendErrorResponse('Données invalides', 400, $validation['errors']);
    }
    
    $cleaned = $validation['cleaned'];
    $result = $userModel->updateUserByAdmin($cleaned['user_id'], $cleaned);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'user' => $result['user']
        ], $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400, $result['errors'] ?? null);
    }
}

/**
 * Mettre à jour un rapport
 */
function handleUpdateReport($userModel) {
    AuthHelper::requireModerator();
    
    $data = Utils::getJSONInput();
    
    if (!$data) {
        Utils::sendErrorResponse('Données JSON requises', 400);
    }
    
    // Validation des données
    $rules = [
        'report_id' => ['required' => true, 'type' => 'int'],
        'status' => ['required' => true, 'allowed_values' => ['pending', 'reviewed', 'resolved', 'dismissed']],
        'action_taken' => ['required' => false, 'max_length' => 500]
    ];
    
    $validation = Utils::validateInput($data, $rules);
    
    if (!empty($validation['errors'])) {
        Utils::sendErrorResponse('Données invalides', 400, $validation['errors']);
    }
    
    $cleaned = $validation['cleaned'];
    $currentUser = AuthHelper::getCurrentUser();
    $result = $userModel->updateReport($cleaned['report_id'], $cleaned['status'], $cleaned['action_taken'] ?? null, $currentUser['id']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse([
            'report' => $result['report']
        ], $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}

/**
 * Supprimer un utilisateur
 */
function handleDeleteUser($userModel) {
    AuthHelper::requireAdmin();
    
    $userId = $_GET['user_id'] ?? null;
    
    if (!$userId) {
        Utils::sendErrorResponse('ID utilisateur requis', 400);
    }
    
    $currentUser = AuthHelper::getCurrentUser();
    
    // Empêcher l'admin de se supprimer lui-même
    if ($userId == $currentUser['id']) {
        Utils::sendErrorResponse('Vous ne pouvez pas supprimer votre propre compte', 400);
    }
    
    $result = $userModel->deleteUserByAdmin($userId, $currentUser['id']);
    
    if ($result['success']) {
        Utils::sendSuccessResponse(null, $result['message']);
    } else {
        Utils::sendErrorResponse($result['message'], 400);
    }
}
