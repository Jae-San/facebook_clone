<?php
/**
 * Helper d'Authentification
 * Gestion des rôles, permissions et middleware d'authentification
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class AuthHelper {
    
    /**
     * Vérifier si l'utilisateur est authentifié
     */
    public static function isAuthenticated() {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? '';
        
        // Extraire le token du header Authorization (Bearer token)
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }
        
        if (empty($token)) {
            return false;
        }
        
        $userModel = new User();
        $result = $userModel->verifyToken($token);
        
        return $result['success'];
    }
    
    /**
     * Obtenir l'utilisateur actuel
     */
    public static function getCurrentUser() {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? '';
        
        // Extraire le token du header Authorization (Bearer token)
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }
        
        if (empty($token)) {
            return null;
        }
        
        $userModel = new User();
        $result = $userModel->verifyToken($token);
        
        if ($result['success']) {
            return $result['user'];
        }
        
        return null;
    }
    
    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public static function hasRole($requiredRole) {
        $user = self::getCurrentUser();
        
        if (!$user) {
            return false;
        }
        
        return $user['role_name'] === $requiredRole;
    }
    
    /**
     * Vérifier si l'utilisateur a au moins un des rôles requis
     */
    public static function hasAnyRole($requiredRoles) {
        $user = self::getCurrentUser();
        
        if (!$user) {
            return false;
        }
        
        return in_array($user['role_name'], $requiredRoles);
    }
    
    /**
     * Vérifier si l'utilisateur est administrateur
     */
    public static function isAdmin() {
        return self::hasRole('admin');
    }
    
    /**
     * Vérifier si l'utilisateur est modérateur ou administrateur
     */
    public static function isModerator() {
        return self::hasAnyRole(['moderator', 'admin']);
    }
    
    /**
     * Vérifier si l'utilisateur peut modifier un contenu
     */
    public static function canEditContent($contentUserId) {
        $user = self::getCurrentUser();
        
        if (!$user) {
            return false;
        }
        
        // L'utilisateur peut modifier son propre contenu
        if ($user['id'] == $contentUserId) {
            return true;
        }
        
        // Les modérateurs et admins peuvent modifier tout contenu
        return self::isModerator();
    }
    
    /**
     * Vérifier si l'utilisateur peut supprimer un contenu
     */
    public static function canDeleteContent($contentUserId) {
        $user = self::getCurrentUser();
        
        if (!$user) {
            return false;
        }
        
        // L'utilisateur peut supprimer son propre contenu
        if ($user['id'] == $contentUserId) {
            return true;
        }
        
        // Seuls les modérateurs et admins peuvent supprimer le contenu d'autres utilisateurs
        return self::isModerator();
    }
    
    /**
     * Vérifier si l'utilisateur peut gérer les utilisateurs
     */
    public static function canManageUsers() {
        return self::isModerator();
    }
    
    /**
     * Vérifier si l'utilisateur peut voir le profil d'un autre utilisateur
     */
    public static function canViewProfile($targetUserId) {
        $user = self::getCurrentUser();
        
        if (!$user) {
            return false;
        }
        
        // L'utilisateur peut toujours voir son propre profil
        if ($user['id'] == $targetUserId) {
            return true;
        }
        
        // Les modérateurs et admins peuvent voir tous les profils
        if (self::isModerator()) {
            return true;
        }
        
        // TODO: Ajouter la logique pour vérifier si les utilisateurs sont amis
        // Pour l'instant, on autorise la vue des profils publics
        
        return true;
    }
    
    /**
     * Middleware pour exiger l'authentification
     */
    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            Utils::sendErrorResponse('Authentification requise', 401);
        }
    }
    
    /**
     * Middleware pour exiger un rôle spécifique
     */
    public static function requireRole($role) {
        self::requireAuth();
        
        if (!self::hasRole($role)) {
            Utils::sendErrorResponse('Permissions insuffisantes', 403);
        }
    }
    
    /**
     * Middleware pour exiger au moins un des rôles
     */
    public static function requireAnyRole($roles) {
        self::requireAuth();
        
        if (!self::hasAnyRole($roles)) {
            Utils::sendErrorResponse('Permissions insuffisantes', 403);
        }
    }
    
    /**
     * Middleware pour exiger les droits d'administrateur
     */
    public static function requireAdmin() {
        self::requireRole('admin');
    }
    
    /**
     * Middleware pour exiger les droits de modérateur
     */
    public static function requireModerator() {
        self::requireAnyRole(['moderator', 'admin']);
    }
    
    /**
     * Middleware pour exiger les droits de modification de contenu
     */
    public static function requireEditPermission($contentUserId) {
        self::requireAuth();
        
        if (!self::canEditContent($contentUserId)) {
            Utils::sendErrorResponse('Permissions insuffisantes pour modifier ce contenu', 403);
        }
    }
    
    /**
     * Middleware pour exiger les droits de suppression de contenu
     */
    public static function requireDeletePermission($contentUserId) {
        self::requireAuth();
        
        if (!self::canDeleteContent($contentUserId)) {
            Utils::sendErrorResponse('Permissions insuffisantes pour supprimer ce contenu', 403);
        }
    }
    
    /**
     * Middleware pour exiger les droits de gestion des utilisateurs
     */
    public static function requireUserManagement() {
        self::requireModerator();
        
        if (!self::canManageUsers()) {
            Utils::sendErrorResponse('Permissions insuffisantes pour gérer les utilisateurs', 403);
        }
    }
    
    /**
     * Middleware pour exiger les droits de vue de profil
     */
    public static function requireProfileViewPermission($targetUserId) {
        self::requireAuth();
        
        if (!self::canViewProfile($targetUserId)) {
            Utils::sendErrorResponse('Permissions insuffisantes pour voir ce profil', 403);
        }
    }
    
    /**
     * Obtenir les permissions de l'utilisateur actuel
     */
    public static function getUserPermissions() {
        $user = self::getCurrentUser();
        
        if (!$user) {
            return [];
        }
        
        $permissions = [
            'can_edit_own_content' => true,
            'can_delete_own_content' => true,
            'can_view_profiles' => true,
            'can_search_users' => true,
            'can_send_friend_requests' => true,
            'can_send_messages' => true,
            'can_create_posts' => true,
            'can_comment' => true,
            'can_like' => true
        ];
        
        // Permissions pour modérateurs
        if (self::isModerator()) {
            $permissions = array_merge($permissions, [
                'can_edit_any_content' => true,
                'can_delete_any_content' => true,
                'can_manage_users' => true,
                'can_view_reports' => true,
                'can_moderate_comments' => true,
                'can_ban_users' => true
            ]);
        }
        
        // Permissions pour administrateurs
        if (self::isAdmin()) {
            $permissions = array_merge($permissions, [
                'can_manage_admins' => true,
                'can_manage_moderators' => true,
                'can_view_statistics' => true,
                'can_manage_system' => true,
                'can_access_admin_panel' => true
            ]);
        }
        
        return $permissions;
    }
    
    /**
     * Vérifier si l'utilisateur a une permission spécifique
     */
    public static function hasPermission($permission) {
        $permissions = self::getUserPermissions();
        return isset($permissions[$permission]) && $permissions[$permission];
    }
    
    /**
     * Middleware pour exiger une permission spécifique
     */
    public static function requirePermission($permission) {
        self::requireAuth();
        
        if (!self::hasPermission($permission)) {
            Utils::sendErrorResponse('Permission requise: ' . $permission, 403);
        }
    }
    
    /**
     * Logger une action avec l'utilisateur actuel
     */
    public static function logAction($action, $details = null) {
        $user = self::getCurrentUser();
        $userId = $user ? $user['id'] : null;
        
        Utils::logAction($action, $userId, $details);
    }
    
    /**
     * Obtenir les informations de session de l'utilisateur
     */
    public static function getSessionInfo() {
        $user = self::getCurrentUser();
        
        if (!$user) {
            return null;
        }
        
        return [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role_name'],
            'permissions' => self::getUserPermissions(),
            'last_login' => $user['last_login'] ?? null
        ];
    }
    
    /**
     * Vérifier si l'utilisateur est bloqué
     */
    public static function isUserBlocked($userId) {
        $db = getDB();
        
        try {
            $sql = "SELECT is_active FROM users WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch();
            
            return !$user || !$user['is_active'];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification du statut de l'utilisateur: " . $e->getMessage());
            return true; // En cas d'erreur, on considère l'utilisateur comme bloqué par sécurité
        }
    }
    
    /**
     * Middleware pour vérifier que l'utilisateur n'est pas bloqué
     */
    public static function requireActiveUser() {
        self::requireAuth();
        
        $user = self::getCurrentUser();
        
        if (self::isUserBlocked($user['id'])) {
            Utils::sendErrorResponse('Votre compte a été désactivé', 403);
        }
    }
}
