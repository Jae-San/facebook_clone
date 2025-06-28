<?php
/**
 * Modèle Post
 * Gestion des articles/posts et commentaires
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/Utils.php';

class Post {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Créer un nouveau post
     */
    public function createPost($data, $userId) {
        try {
            $sql = "INSERT INTO posts (user_id, content, image_url, video_url, privacy, location) 
                    VALUES (:user_id, :content, :image_url, :video_url, :privacy, :location)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'content' => $data['content'],
                'image_url' => $data['image_url'] ?? null,
                'video_url' => $data['video_url'] ?? null,
                'privacy' => $data['privacy'] ?? 'public',
                'location' => $data['location'] ?? null
            ]);
            
            $postId = $this->db->lastInsertId();
            
            // Récupérer le post complet
            $post = $this->getPostById($postId);
            
            return [
                'success' => true,
                'post' => $post,
                'message' => 'Post créé avec succès'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la création du post: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la création du post'];
        }
    }
    
    /**
     * Obtenir les posts pour l'utilisateur connecté
     */
    public function getPosts($userId, $limit = 20, $offset = 0) {
        try {
            $sql = "SELECT p.*, u.username, u.first_name, u.last_name, u.avatar_url,
                           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
                           (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = :user_id) as user_liked
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    WHERE (p.privacy = 'public' OR p.user_id = :user_id OR 
                           (p.privacy = 'friends' AND EXISTS (
                               SELECT 1 FROM friendships 
                               WHERE ((user_id = p.user_id AND friend_id = :user_id) OR 
                                      (user_id = :user_id AND friend_id = p.user_id)) 
                               AND status = 'accepted'
                           )))
                    ORDER BY p.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $posts = $stmt->fetchAll();
            
            // Ajouter les commentaires pour chaque post
            foreach ($posts as &$post) {
                $post['comments'] = $this->getCommentsForPost($post['id'], 5); // 5 derniers commentaires
            }
            
            return [
                'success' => true,
                'posts' => $posts
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des posts: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la récupération des posts'];
        }
    }
    
    /**
     * Obtenir un post par ID
     */
    public function getPostById($postId, $userId = null) {
        try {
            $sql = "SELECT p.*, u.username, u.first_name, u.last_name, u.avatar_url,
                           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count";
            
            if ($userId) {
                $sql .= ", (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = :user_id) as user_liked";
            }
            
            $sql .= " FROM posts p
                      JOIN users u ON p.user_id = u.id
                      WHERE p.id = :post_id";
            
            $stmt = $this->db->prepare($sql);
            $params = ['post_id' => $postId];
            
            if ($userId) {
                $params['user_id'] = $userId;
            }
            
            $stmt->execute($params);
            $post = $stmt->fetch();
            
            if ($post) {
                $post['comments'] = $this->getCommentsForPost($postId);
            }
            
            return $post;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du post: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Mettre à jour un post
     */
    public function updatePost($postId, $data, $userId) {
        try {
            // Vérifier que l'utilisateur est l'auteur du post
            $sql = "SELECT user_id FROM posts WHERE id = :post_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['post_id' => $postId]);
            $post = $stmt->fetch();
            
            if (!$post) {
                return ['success' => false, 'message' => 'Post non trouvé'];
            }
            
            if ($post['user_id'] != $userId) {
                return ['success' => false, 'message' => 'Vous ne pouvez pas modifier ce post'];
            }
            
            // Mettre à jour le post
            $sql = "UPDATE posts SET content = :content, image_url = :image_url, video_url = :video_url, 
                           privacy = :privacy, location = :location, is_edited = 1, updated_at = NOW() 
                    WHERE id = :post_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'content' => $data['content'],
                'image_url' => $data['image_url'] ?? null,
                'video_url' => $data['video_url'] ?? null,
                'privacy' => $data['privacy'] ?? 'public',
                'location' => $data['location'] ?? null,
                'post_id' => $postId
            ]);
            
            // Récupérer le post mis à jour
            $updatedPost = $this->getPostById($postId, $userId);
            
            return [
                'success' => true,
                'post' => $updatedPost,
                'message' => 'Post mis à jour avec succès'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour du post: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour du post'];
        }
    }
    
    /**
     * Supprimer un post
     */
    public function deletePost($postId, $userId) {
        try {
            // Vérifier que l'utilisateur est l'auteur du post
            $sql = "SELECT user_id FROM posts WHERE id = :post_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['post_id' => $postId]);
            $post = $stmt->fetch();
            
            if (!$post) {
                return ['success' => false, 'message' => 'Post non trouvé'];
            }
            
            if ($post['user_id'] != $userId) {
                return ['success' => false, 'message' => 'Vous ne pouvez pas supprimer ce post'];
            }
            
            // Supprimer le post (cascade automatique pour likes et commentaires)
            $sql = "DELETE FROM posts WHERE id = :post_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['post_id' => $postId]);
            
            return [
                'success' => true,
                'message' => 'Post supprimé avec succès'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du post: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la suppression du post'];
        }
    }
    
    /**
     * Supprimer un post par un administrateur
     */
    public function deletePostByAdmin($postId, $reason = null, $adminId = null) {
        try {
            // Vérifier que le post existe
            $sql = "SELECT user_id FROM posts WHERE id = :post_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['post_id' => $postId]);
            $post = $stmt->fetch();
            
            if (!$post) {
                return ['success' => false, 'message' => 'Post non trouvé'];
            }
            
            // Supprimer le post
            $sql = "DELETE FROM posts WHERE id = :post_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['post_id' => $postId]);
            
            // Logger l'action
            if ($adminId) {
                Utils::logAction('admin_delete_post', $adminId, [
                    'post_id' => $postId,
                    'post_user_id' => $post['user_id'],
                    'reason' => $reason
                ]);
            }
            
            return [
                'success' => true,
                'message' => 'Post supprimé par l\'administrateur'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du post par admin: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la suppression du post'];
        }
    }
    
    /**
     * Ajouter un commentaire
     */
    public function addComment($data, $userId) {
        try {
            $sql = "INSERT INTO comments (post_id, user_id, content, parent_id) 
                    VALUES (:post_id, :user_id, :content, :parent_id)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'post_id' => $data['post_id'],
                'user_id' => $userId,
                'content' => $data['content'],
                'parent_id' => $data['parent_id'] ?? null
            ]);
            
            $commentId = $this->db->lastInsertId();
            
            // Récupérer le commentaire complet
            $comment = $this->getCommentById($commentId);
            
            return [
                'success' => true,
                'comment' => $comment,
                'message' => 'Commentaire ajouté avec succès'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout du commentaire: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'ajout du commentaire'];
        }
    }
    
    /**
     * Obtenir les commentaires d'un post
     */
    public function getCommentsForPost($postId, $limit = null) {
        try {
            $sql = "SELECT c.*, u.username, u.first_name, u.last_name, u.avatar_url,
                           (SELECT COUNT(*) FROM likes WHERE comment_id = c.id) as likes_count,
                           (SELECT COUNT(*) FROM comments WHERE parent_id = c.id) as replies_count
                    FROM comments c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.post_id = :post_id AND c.parent_id IS NULL
                    ORDER BY c.created_at ASC";
            
            if ($limit) {
                $sql .= " LIMIT :limit";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
            
            if ($limit) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $comments = $stmt->fetchAll();
            
            // Ajouter les réponses pour chaque commentaire
            foreach ($comments as &$comment) {
                $comment['replies'] = $this->getCommentReplies($comment['id']);
            }
            
            return $comments;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des commentaires: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir les réponses d'un commentaire
     */
    private function getCommentReplies($commentId) {
        try {
            $sql = "SELECT c.*, u.username, u.first_name, u.last_name, u.avatar_url,
                           (SELECT COUNT(*) FROM likes WHERE comment_id = c.id) as likes_count
                    FROM comments c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.parent_id = :comment_id
                    ORDER BY c.created_at ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['comment_id' => $commentId]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des réponses: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir un commentaire par ID
     */
    private function getCommentById($commentId) {
        try {
            $sql = "SELECT c.*, u.username, u.first_name, u.last_name, u.avatar_url,
                           (SELECT COUNT(*) FROM likes WHERE comment_id = c.id) as likes_count
                    FROM comments c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.id = :comment_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['comment_id' => $commentId]);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du commentaire: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Supprimer un commentaire par un administrateur
     */
    public function deleteCommentByAdmin($commentId, $reason = null, $adminId = null) {
        try {
            // Vérifier que le commentaire existe
            $sql = "SELECT user_id, post_id FROM comments WHERE id = :comment_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['comment_id' => $commentId]);
            $comment = $stmt->fetch();
            
            if (!$comment) {
                return ['success' => false, 'message' => 'Commentaire non trouvé'];
            }
            
            // Supprimer le commentaire
            $sql = "DELETE FROM comments WHERE id = :comment_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['comment_id' => $commentId]);
            
            // Logger l'action
            if ($adminId) {
                Utils::logAction('admin_delete_comment', $adminId, [
                    'comment_id' => $commentId,
                    'comment_user_id' => $comment['user_id'],
                    'post_id' => $comment['post_id'],
                    'reason' => $reason
                ]);
            }
            
            return [
                'success' => true,
                'message' => 'Commentaire supprimé par l\'administrateur'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du commentaire par admin: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la suppression du commentaire'];
        }
    }
    
    /**
     * Obtenir les posts pour l'administration
     */
    public function getPostsForAdmin($limit = 20, $offset = 0, $search = '', $userId = '') {
        try {
            $whereConditions = [];
            $params = [];
            
            if (!empty($search)) {
                $whereConditions[] = "p.content LIKE :search";
                $params['search'] = "%$search%";
            }
            
            if (!empty($userId)) {
                $whereConditions[] = "p.user_id = :user_id";
                $params['user_id'] = $userId;
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $sql = "SELECT p.*, u.username, u.first_name, u.last_name,
                           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    $whereClause
                    ORDER BY p.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $posts = $stmt->fetchAll();
            
            // Compter le total
            $countSql = "SELECT COUNT(*) FROM posts p JOIN users u ON p.user_id = u.id $whereClause";
            $countStmt = $this->db->prepare($countSql);
            
            foreach ($params as $key => $value) {
                $countStmt->bindValue(":$key", $value);
            }
            
            $countStmt->execute();
            $total = $countStmt->fetchColumn();
            
            return [
                'success' => true,
                'posts' => $posts,
                'total' => $total
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des posts pour admin: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la récupération des posts'];
        }
    }
    
    /**
     * Obtenir les commentaires pour l'administration
     */
    public function getCommentsForAdmin($limit = 20, $offset = 0, $search = '', $userId = '', $postId = '') {
        try {
            $whereConditions = [];
            $params = [];
            
            if (!empty($search)) {
                $whereConditions[] = "c.content LIKE :search";
                $params['search'] = "%$search%";
            }
            
            if (!empty($userId)) {
                $whereConditions[] = "c.user_id = :user_id";
                $params['user_id'] = $userId;
            }
            
            if (!empty($postId)) {
                $whereConditions[] = "c.post_id = :post_id";
                $params['post_id'] = $postId;
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $sql = "SELECT c.*, u.username, u.first_name, u.last_name, p.content as post_content
                    FROM comments c
                    JOIN users u ON c.user_id = u.id
                    JOIN posts p ON c.post_id = p.id
                    $whereClause
                    ORDER BY c.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $comments = $stmt->fetchAll();
            
            // Compter le total
            $countSql = "SELECT COUNT(*) FROM comments c JOIN users u ON c.user_id = u.id JOIN posts p ON c.post_id = p.id $whereClause";
            $countStmt = $this->db->prepare($countSql);
            
            foreach ($params as $key => $value) {
                $countStmt->bindValue(":$key", $value);
            }
            
            $countStmt->execute();
            $total = $countStmt->fetchColumn();
            
            return [
                'success' => true,
                'comments' => $comments,
                'total' => $total
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des commentaires pour admin: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la récupération des commentaires'];
        }
    }
    
    /**
     * Obtenir les statistiques des posts
     */
    public function getStatistics($period = 30) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_posts,
                        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL :period DAY) THEN 1 END) as posts_last_period,
                        COUNT(CASE WHEN image_url IS NOT NULL THEN 1 END) as posts_with_images,
                        COUNT(CASE WHEN video_url IS NOT NULL THEN 1 END) as posts_with_videos,
                        AVG(LENGTH(content)) as avg_content_length
                    FROM posts";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['period' => $period]);
            $postStats = $stmt->fetch();
            
            // Statistiques des commentaires
            $sql = "SELECT 
                        COUNT(*) as total_comments,
                        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL :period DAY) THEN 1 END) as comments_last_period
                    FROM comments";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['period' => $period]);
            $commentStats = $stmt->fetch();
            
            // Statistiques des likes
            $sql = "SELECT 
                        COUNT(*) as total_likes,
                        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL :period DAY) THEN 1 END) as likes_last_period
                    FROM likes";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['period' => $period]);
            $likeStats = $stmt->fetch();
            
            $statistics = array_merge($postStats, $commentStats, $likeStats);
            
            return [
                'success' => true,
                'statistics' => $statistics
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des statistiques des posts: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la récupération des statistiques'];
        }
    }
    
    /**
     * Obtenir les données du tableau de bord
     */
    public function getDashboardData() {
        try {
            // Posts par jour (7 derniers jours)
            $sql = "SELECT DATE(created_at) as date, COUNT(*) as count
                    FROM posts
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $postsByDay = $stmt->fetchAll();
            
            // Top posts (plus de likes)
            $sql = "SELECT p.id, p.content, p.created_at, u.username,
                           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    ORDER BY likes_count DESC
                    LIMIT 5";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $topPosts = $stmt->fetchAll();
            
            // Utilisateurs les plus actifs
            $sql = "SELECT u.username, u.first_name, u.last_name,
                           COUNT(p.id) as posts_count,
                           (SELECT COUNT(*) FROM comments WHERE user_id = u.id) as comments_count
                    FROM users u
                    LEFT JOIN posts p ON u.id = p.user_id
                    GROUP BY u.id
                    ORDER BY posts_count DESC, comments_count DESC
                    LIMIT 5";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $topUsers = $stmt->fetchAll();
            
            return [
                'success' => true,
                'dashboard' => [
                    'posts_by_day' => $postsByDay,
                    'top_posts' => $topPosts,
                    'top_users' => $topUsers
                ]
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des données du tableau de bord: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la récupération du tableau de bord'];
        }
    }
}
