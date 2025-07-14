<?php
/**
 * Modèle User
 * Gestion des utilisateurs et authentification
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/Utils.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Créer un nouvel utilisateur
     */
    public function create($data) {
        try {
            // Validation des données
            $rules = [
                'username' => ['required' => true, 'min_length' => 3, 'max_length' => 50, 'pattern' => '/^[a-zA-Z0-9_]+$/'],
                'email' => ['required' => true, 'type' => 'email'],
                'password' => ['required' => true, 'min_length' => 8],
                'first_name' => ['required' => true, 'min_length' => 2, 'max_length' => 100],
                'last_name' => ['required' => true, 'min_length' => 2, 'max_length' => 100],
                'date_of_birth' => ['required' => false],
                'gender' => ['required' => false, 'allowed_values' => ['male', 'female', 'other', 'prefer_not_to_say']]
            ];
            
            $validation = Utils::validateInput($data, $rules);
            
            if (!empty($validation['errors'])) {
                return ['success' => false, 'errors' => $validation['errors']];
            }
            
            $cleaned = $validation['cleaned'];
            
            // Vérifier si l'email existe déjà
            if ($this->emailExists($cleaned['email'])) {
                return ['success' => false, 'errors' => ['email' => 'Cet email est déjà utilisé']];
            }
            
            // Vérifier si le nom d'utilisateur existe déjà
            if ($this->usernameExists($cleaned['username'])) {
                return ['success' => false, 'errors' => ['username' => 'Ce nom d\'utilisateur est déjà pris']];
            }
            
            // Valider la force du mot de passe
            $passwordErrors = Utils::validatePasswordStrength($cleaned['password']);
            if (!empty($passwordErrors)) {
                return ['success' => false, 'errors' => ['password' => $passwordErrors]];
            }
            
            // Hasher le mot de passe
            $passwordHash = Utils::hashPassword($cleaned['password']);
            
            // Préparer la requête
            $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name, date_of_birth, gender, role_id) 
                    VALUES (:username, :email, :password_hash, :first_name, :last_name, :date_of_birth, :gender, 1)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'username' => $cleaned['username'],
                'email' => $cleaned['email'],
                'password_hash' => $passwordHash,
                'first_name' => $cleaned['first_name'],
                'last_name' => $cleaned['last_name'],
                'date_of_birth' => $cleaned['date_of_birth'] ?? null,
                'gender' => $cleaned['gender'] ?? null
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Logger l'action
            Utils::logAction('user_created', $userId, ['username' => $cleaned['username'], 'email' => $cleaned['email']]);
            
            return [
                'success' => true,
                'user_id' => $userId,
                'message' => 'Utilisateur créé avec succès'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de l'utilisateur: " . $e->getMessage());
            return ['success' => false, 'errors' => ['database' => 'Erreur lors de la création du compte']];
        }
    }
    
    /**
     * Authentifier un utilisateur
     */
    public function authenticate($email, $password) {
        try {
            $sql = "SELECT u.*, r.name as role_name 
                    FROM users u 
                    LEFT JOIN roles r ON u.role_id = r.id 
                    WHERE u.email = :email AND u.is_active = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            
            if (!$user || !Utils::verifyPassword($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Email ou mot de passe incorrect'];
            }
            
            // Mettre à jour la dernière connexion
            $this->updateLastLogin($user['id']);
            
            // Générer un token de session
            $token = $this->createSessionToken($user['id']);
            
            // Logger la connexion
            Utils::logAction('user_login', $user['id'], ['ip' => Utils::getClientIP()]);
            
            // Retourner les données utilisateur (sans le mot de passe)
            unset($user['password_hash']);
            
            return [
                'success' => true,
                'user' => $user,
                'token' => $token,
                'message' => 'Connexion réussie'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'authentification: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la connexion'];
        }
    }
    
    /**
     * Vérifier un token de session
     */
    public function verifyToken($token) {
        try {
            $sql = "SELECT u.*, r.name as role_name, at.expires_at 
                    FROM auth_tokens at 
                    JOIN users u ON at.user_id = u.id 
                    LEFT JOIN roles r ON u.role_id = r.id 
                    WHERE at.token = :token AND at.type = 'session' AND at.expires_at > NOW() AND u.is_active = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['token' => $token]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Token invalide ou expiré'];
            }
            
            unset($user['password_hash']);
            
            return [
                'success' => true,
                'user' => $user
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification du token: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la vérification du token'];
        }
    }
    
    /**
     * Déconnecter un utilisateur
     */
    public function logout($token) {
        try {
            $sql = "DELETE FROM auth_tokens WHERE token = :token AND type = 'session'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['token' => $token]);
            
            return ['success' => true, 'message' => 'Déconnexion réussie'];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la déconnexion: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la déconnexion'];
        }
    }
    
    /**
     * Obtenir un utilisateur par ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT u.*, r.name as role_name 
                    FROM users u 
                    LEFT JOIN roles r ON u.role_id = r.id 
                    WHERE u.id = :id AND u.is_active = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Utilisateur non trouvé'];
            }
            
            unset($user['password_hash']);
            
            return [
                'success' => true,
                'user' => $user
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'utilisateur: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la récupération de l\'utilisateur'];
        }
    }
    
    /**
     * Mettre à jour un utilisateur
     */
    public function update($id, $data) {
        try {
            // Validation des données
            $rules = [
                'first_name' => ['required' => false, 'min_length' => 2, 'max_length' => 100],
                'last_name' => ['required' => false, 'min_length' => 2, 'max_length' => 100],
                'bio' => ['required' => false, 'max_length' => 1000],
                'location' => ['required' => false, 'max_length' => 255],
                'phone' => ['required' => false, 'max_length' => 20],
                'gender' => ['required' => false, 'allowed_values' => ['male', 'female', 'other', 'prefer_not_to_say']]
            ];
            
            $validation = Utils::validateInput($data, $rules);
            
            if (!empty($validation['errors'])) {
                return ['success' => false, 'errors' => $validation['errors']];
            }
            
            $cleaned = $validation['cleaned'];
            
            // Construire la requête dynamiquement
            $updateFields = [];
            $params = ['id' => $id];
            
            foreach ($cleaned as $field => $value) {
                if ($value !== null && $value !== '') {
                    $updateFields[] = "$field = :$field";
                    $params[$field] = $value;
                }
            }
            
            if (empty($updateFields)) {
                return ['success' => false, 'message' => 'Aucune donnée à mettre à jour'];
            }
            
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Utilisateur non trouvé'];
            }
            
            // Logger l'action
            Utils::logAction('user_updated', $id, $cleaned);
            
            return [
                'success' => true,
                'message' => 'Profil mis à jour avec succès'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de l'utilisateur: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour du profil'];
        }
    }
    
    /**
     * Changer le mot de passe
     */
    public function changePassword($id, $currentPassword, $newPassword) {
        try {
            // Vérifier l'utilisateur actuel
            $user = $this->getById($id);
            if (!$user['success']) {
                return $user;
            }
            
            // Récupérer le hash du mot de passe actuel
            $sql = "SELECT password_hash FROM users WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $currentUser = $stmt->fetch();
            
            // Vérifier le mot de passe actuel
            if (!Utils::verifyPassword($currentPassword, $currentUser['password_hash'])) {
                return ['success' => false, 'message' => 'Mot de passe actuel incorrect'];
            }
            
            // Valider la force du nouveau mot de passe
            $passwordErrors = Utils::validatePasswordStrength($newPassword);
            if (!empty($passwordErrors)) {
                return ['success' => false, 'errors' => ['password' => $passwordErrors]];
            }
            
            // Hasher le nouveau mot de passe
            $newPasswordHash = Utils::hashPassword($newPassword);
            
            // Mettre à jour le mot de passe
            $sql = "UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'password_hash' => $newPasswordHash,
                'id' => $id
            ]);
            
            // Logger l'action
            Utils::logAction('password_changed', $id);
            
            return [
                'success' => true,
                'message' => 'Mot de passe modifié avec succès'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors du changement de mot de passe: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors du changement de mot de passe'];
        }
    }
    
    /**
     * Demander une réinitialisation de mot de passe
     */
    public function requestPasswordReset($email) {
        try {
            $sql = "SELECT id, username, first_name FROM users WHERE email = :email AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Aucun compte associé à cet email'];
            }
            
            // Générer un token de réinitialisation
            $token = Utils::generateToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Supprimer les anciens tokens de réinitialisation
            $sql = "DELETE FROM auth_tokens WHERE user_id = :user_id AND type = 'reset_password'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $user['id']]);
            
            // Créer le nouveau token
            $sql = "INSERT INTO auth_tokens (user_id, token, type, expires_at) VALUES (:user_id, :token, 'reset_password', :expires_at)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $user['id'],
                'token' => $token,
                'expires_at' => $expiresAt
            ]);
            
            // TODO: Envoyer l'email de réinitialisation
            // require_once __DIR__ . '/../lib/EmailSender.php';
            // EmailSender::sendPasswordResetEmail($user['email'], $user['first_name'], $token);
            
            return [
                'success' => true,
                'message' => 'Email de réinitialisation envoyé'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la demande de réinitialisation: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la demande de réinitialisation'];
        }
    }
    
    /**
     * Réinitialiser le mot de passe avec un token
     */
    public function resetPassword($token, $newPassword) {
        try {
            // Vérifier le token
            $sql = "SELECT user_id FROM auth_tokens WHERE token = :token AND type = 'reset_password' AND expires_at > NOW()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['token' => $token]);
            $tokenData = $stmt->fetch();
            
            if (!$tokenData) {
                return ['success' => false, 'message' => 'Token invalide ou expiré'];
            }
            
            // Valider la force du nouveau mot de passe
            $passwordErrors = Utils::validatePasswordStrength($newPassword);
            if (!empty($passwordErrors)) {
                return ['success' => false, 'errors' => ['password' => $passwordErrors]];
            }
            
            // Hasher le nouveau mot de passe
            $newPasswordHash = Utils::hashPassword($newPassword);
            
            // Mettre à jour le mot de passe
            $sql = "UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'password_hash' => $newPasswordHash,
                'user_id' => $tokenData['user_id']
            ]);
            
            // Supprimer le token utilisé
            $sql = "DELETE FROM auth_tokens WHERE token = :token";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['token' => $token]);
            
            // Logger l'action
            Utils::logAction('password_reset', $tokenData['user_id']);
            
            return [
                'success' => true,
                'message' => 'Mot de passe réinitialisé avec succès'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la réinitialisation du mot de passe: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la réinitialisation du mot de passe'];
        }
    }
    
    /**
     * Vérifier si un email existe
     */
    private function emailExists($email) {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Vérifier si un nom d'utilisateur existe
     */
    private function usernameExists($username) {
        $sql = "SELECT COUNT(*) FROM users WHERE username = :username";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['username' => $username]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Mettre à jour la dernière connexion
     */
    private function updateLastLogin($userId) {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $userId]);
    }
    
    /**
     * Créer un token de session
     */
    private function createSessionToken($userId) {
        $token = Utils::generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $sql = "INSERT INTO auth_tokens (user_id, token, type, expires_at) VALUES (:user_id, :token, 'session', :expires_at)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt
        ]);
        
        return $token;
    }
    
    /**
     * Obtenir les amis d'un utilisateur
     */
    public function getFriends($userId, $limit = 20, $offset = 0) {
        try {
            $sql = "SELECT u.id, u.username, u.first_name, u.last_name, u.avatar_url, u.is_online, fs.created_at as friendship_date
                    FROM friendships fs
                    JOIN users u ON (fs.user_id = u.id AND fs.user_id != :user_id) OR (fs.friend_id = u.id AND fs.friend_id != :user_id)
                    WHERE (fs.user_id = :user_id OR fs.friend_id = :user_id) AND fs.status = 'accepted'
                    ORDER BY fs.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return [
                'success' => true,
                'friends' => $stmt->fetchAll()
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des amis: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la récupération des amis'];
        }
    }
    
    /**
     * Rechercher des utilisateurs
     */
    public function search($query, $currentUserId, $limit = 20, $offset = 0) {
        try {
            $sql = "SELECT u.id, u.username, u.first_name, u.last_name, u.avatar_url, u.bio,
                           CASE 
                               WHEN fs.status = 'accepted' THEN 'friend'
                               WHEN fs.status = 'pending' AND fs.user_id = :current_user_id THEN 'request_sent'
                               WHEN fs.status = 'pending' AND fs.friend_id = :current_user_id THEN 'request_received'
                               ELSE 'none'
                           END as friendship_status
                    FROM users u
                    LEFT JOIN friendships fs ON (fs.user_id = u.id AND fs.friend_id = :current_user_id) 
                                            OR (fs.friend_id = u.id AND fs.user_id = :current_user_id)
                    WHERE u.id != :current_user_id 
                      AND u.is_active = 1
                      AND (u.username LIKE :query OR u.first_name LIKE :query OR u.last_name LIKE :query)
                    ORDER BY u.first_name, u.last_name
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':current_user_id', $currentUserId, PDO::PARAM_INT);
            $stmt->bindValue(':query', "%$query%", PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return [
                'success' => true,
                'users' => $stmt->fetchAll()
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche d'utilisateurs: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la recherche'];
        }
    }
}
