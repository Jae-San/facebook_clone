<?php
/**
 * Modèle Message
 * Gestion des conversations et messages du chat
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/Utils.php';

class Message {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Créer une nouvelle conversation
     */
    public function createConversation($data, $createdBy) {
        try {
            $this->db->beginTransaction();
            
            // Créer la conversation
            $sql = "INSERT INTO conversations (name, is_group, created_by) VALUES (:name, :is_group, :created_by)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'name' => $data['name'] ?? null,
                'is_group' => $data['is_group'] ?? false,
                'created_by' => $createdBy
            ]);
            
            $conversationId = $this->db->lastInsertId();
            
            // Ajouter les participants
            foreach ($data['participants'] as $participantId) {
                $sql = "INSERT INTO conversation_participants (conversation_id, user_id, is_admin) 
                        VALUES (:conversation_id, :user_id, :is_admin)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'conversation_id' => $conversationId,
                    'user_id' => $participantId,
                    'is_admin' => ($participantId == $createdBy) // Le créateur est admin
                ]);
            }
            
            $this->db->commit();
            
            // Récupérer les détails de la conversation
            $conversation = $this->getConversationById($conversationId);
            
            return [
                'success' => true,
                'conversation' => $conversation,
                'message' => 'Conversation créée avec succès'
            ];
            
        } catch (PDOException $e) {
            $this->db->rollback();
            error_log("Erreur lors de la création de la conversation: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la création de la conversation'];
        }
    }
    
    /**
     * Envoyer un message
     */
    public function sendMessage($data, $senderId) {
        try {
            $sql = "INSERT INTO messages (conversation_id, sender_id, content, message_type, file_url, file_name, file_size) 
                    VALUES (:conversation_id, :sender_id, :content, :message_type, :file_url, :file_name, :file_size)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'conversation_id' => $data['conversation_id'],
                'sender_id' => $senderId,
                'content' => $data['content'],
                'message_type' => $data['message_type'] ?? 'text',
                'file_url' => $data['file_url'] ?? null,
                'file_name' => $data['file_name'] ?? null,
                'file_size' => $data['file_size'] ?? null
            ]);
            
            $messageId = $this->db->lastInsertId();
            
            // Mettre à jour la conversation (dernier message)
            $sql = "UPDATE conversations SET updated_at = NOW() WHERE id = :conversation_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['conversation_id' => $data['conversation_id']]);
            
            // Récupérer le message complet
            $message = $this->getMessageById($messageId);
            
            return [
                'success' => true,
                'message' => $message,
                'message_text' => 'Message envoyé avec succès'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'envoi du message: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'envoi du message'];
        }
    }
    
    /**
     * Créer une session de chat
     */
    public function createChatSession($userId) {
        try {
            $sessionToken = Utils::generateToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Supprimer les anciennes sessions
            $sql = "DELETE FROM chat_sessions WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            
            // Créer la nouvelle session
            $sql = "INSERT INTO chat_sessions (user_id, session_token, last_activity) 
                    VALUES (:user_id, :session_token, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'session_token' => $sessionToken
            ]);
            
            return [
                'success' => true,
                'session_token' => $sessionToken
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de la session: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la création de la session'];
        }
    }
    
    /**
     * Obtenir les conversations d'un utilisateur
     */
    public function getConversations($userId, $limit = 20, $offset = 0) {
        try {
            $sql = "SELECT c.*, 
                           (SELECT COUNT(*) FROM conversation_participants WHERE conversation_id = c.id) as participant_count,
                           (SELECT m.content FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_message,
                           (SELECT m.created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_message_time,
                           (SELECT COUNT(*) FROM messages m 
                            WHERE m.conversation_id = c.id 
                              AND m.sender_id != :user_id 
                              AND m.id NOT IN (
                                  SELECT DISTINCT message_id FROM message_reads WHERE user_id = :user_id
                              )) as unread_count
                    FROM conversations c
                    JOIN conversation_participants cp ON c.id = cp.conversation_id
                    WHERE cp.user_id = :user_id AND cp.left_at IS NULL
                    ORDER BY c.updated_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $conversations = $stmt->fetchAll();
            
            // Ajouter les participants pour chaque conversation
            foreach ($conversations as &$conversation) {
                $conversation['participants'] = $this->getConversationParticipants($conversation['id']);
            }
            
            return [
                'success' => true,
                'conversations' => $conversations
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des conversations: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la récupération des conversations'];
        }
    }
    
    /**
     * Obtenir les messages d'une conversation
     */
    public function getMessages($conversationId, $userId, $limit = 50, $offset = 0) {
        try {
            $sql = "SELECT m.*, u.username, u.first_name, u.last_name, u.avatar_url
                    FROM messages m
                    JOIN users u ON m.sender_id = u.id
                    WHERE m.conversation_id = :conversation_id AND m.is_deleted = 0
                    ORDER BY m.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':conversation_id', $conversationId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $messages = array_reverse($stmt->fetchAll()); // Inverser pour avoir l'ordre chronologique
            
            // Vérifier s'il y a plus de messages
            $sql = "SELECT COUNT(*) FROM messages WHERE conversation_id = :conversation_id AND is_deleted = 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['conversation_id' => $conversationId]);
            $totalMessages = $stmt->fetchColumn();
            
            $hasMore = ($offset + $limit) < $totalMessages;
            
            return [
                'success' => true,
                'messages' => $messages,
                'has_more' => $hasMore
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des messages: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la récupération des messages'];
        }
    }
    
    /**
     * Obtenir le nombre de messages non lus
     */
    public function getUnreadCount($userId, $conversationId = null) {
        try {
            if ($conversationId) {
                $sql = "SELECT COUNT(*) FROM messages m
                        WHERE m.conversation_id = :conversation_id 
                          AND m.sender_id != :user_id 
                          AND m.is_deleted = 0
                          AND m.id NOT IN (
                              SELECT DISTINCT message_id FROM message_reads WHERE user_id = :user_id
                          )";
                $params = ['conversation_id' => $conversationId, 'user_id' => $userId];
            } else {
                $sql = "SELECT COUNT(*) FROM messages m
                        JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id
                        WHERE cp.user_id = :user_id 
                          AND m.sender_id != :user_id 
                          AND m.is_deleted = 0
                          AND m.id NOT IN (
                              SELECT DISTINCT message_id FROM message_reads WHERE user_id = :user_id
                          )";
                $params = ['user_id' => $userId];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $unreadCount = $stmt->fetchColumn();
            
            return [
                'success' => true,
                'unread_count' => (int)$unreadCount
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des messages non lus: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors du comptage des messages non lus'];
        }
    }
    
    /**
     * Mettre à jour un message
     */
    public function updateMessage($messageId, $content, $userId) {
        try {
            // Vérifier que l'utilisateur est l'auteur du message
            $sql = "SELECT sender_id FROM messages WHERE id = :message_id AND is_deleted = 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['message_id' => $messageId]);
            $message = $stmt->fetch();
            
            if (!$message) {
                return ['success' => false, 'message' => 'Message non trouvé'];
            }
            
            if ($message['sender_id'] != $userId) {
                return ['success' => false, 'message' => 'Vous ne pouvez pas modifier ce message'];
            }
            
            // Mettre à jour le message
            $sql = "UPDATE messages SET content = :content, is_edited = 1, updated_at = NOW() 
                    WHERE id = :message_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'content' => $content,
                'message_id' => $messageId
            ]);
            
            // Récupérer le message mis à jour
            $updatedMessage = $this->getMessageById($messageId);
            
            return [
                'success' => true,
                'message' => $updatedMessage,
                'message_text' => 'Message mis à jour avec succès'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour du message: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour du message'];
        }
    }
    
    /**
     * Marquer les messages comme lus
     */
    public function markAsRead($conversationId, $userId, $messageIds = null) {
        try {
            if ($messageIds) {
                // Marquer des messages spécifiques comme lus
                $placeholders = str_repeat('?,', count($messageIds) - 1) . '?';
                $sql = "INSERT IGNORE INTO message_reads (message_id, user_id, read_at) 
                        SELECT id, ?, NOW() FROM messages 
                        WHERE id IN ($placeholders) AND conversation_id = ?";
                
                $params = array_merge([$userId], $messageIds, [$conversationId]);
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            } else {
                // Marquer tous les messages de la conversation comme lus
                $sql = "INSERT IGNORE INTO message_reads (message_id, user_id, read_at)
                        SELECT m.id, ?, NOW() FROM messages m
                        WHERE m.conversation_id = ? AND m.sender_id != ? AND m.is_deleted = 0
                          AND m.id NOT IN (
                              SELECT DISTINCT message_id FROM message_reads WHERE user_id = ?
                          )";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId, $conversationId, $userId, $userId]);
            }
            
            return [
                'success' => true,
                'message' => 'Messages marqués comme lus'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors du marquage des messages comme lus: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors du marquage des messages comme lus'];
        }
    }
    
    /**
     * Supprimer un message
     */
    public function deleteMessage($messageId, $userId) {
        try {
            // Vérifier que l'utilisateur est l'auteur du message ou un modérateur
            $sql = "SELECT sender_id FROM messages WHERE id = :message_id AND is_deleted = 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['message_id' => $messageId]);
            $message = $stmt->fetch();
            
            if (!$message) {
                return ['success' => false, 'message' => 'Message non trouvé'];
            }
            
            // TODO: Ajouter la vérification des permissions de modérateur
            if ($message['sender_id'] != $userId) {
                return ['success' => false, 'message' => 'Vous ne pouvez pas supprimer ce message'];
            }
            
            // Marquer le message comme supprimé (soft delete)
            $sql = "UPDATE messages SET is_deleted = 1, updated_at = NOW() WHERE id = :message_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['message_id' => $messageId]);
            
            return [
                'success' => true,
                'message' => 'Message supprimé avec succès'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du message: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la suppression du message'];
        }
    }
    
    /**
     * Supprimer une conversation
     */
    public function deleteConversation($conversationId, $userId) {
        try {
            // Vérifier que l'utilisateur fait partie de la conversation
            if (!$this->isUserInConversation($conversationId, $userId)) {
                return ['success' => false, 'message' => 'Vous n\'êtes pas autorisé à supprimer cette conversation'];
            }
            
            // Marquer l'utilisateur comme ayant quitté la conversation
            $sql = "UPDATE conversation_participants SET left_at = NOW() 
                    WHERE conversation_id = :conversation_id AND user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'conversation_id' => $conversationId,
                'user_id' => $userId
            ]);
            
            return [
                'success' => true,
                'message' => 'Conversation supprimée avec succès'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la conversation: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la suppression de la conversation'];
        }
    }
    
    /**
     * Vérifier si un utilisateur fait partie d'une conversation
     */
    public function isUserInConversation($conversationId, $userId) {
        try {
            $sql = "SELECT COUNT(*) FROM conversation_participants 
                    WHERE conversation_id = :conversation_id AND user_id = :user_id AND left_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'conversation_id' => $conversationId,
                'user_id' => $userId
            ]);
            
            return $stmt->fetchColumn() > 0;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de l'appartenance à la conversation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir une conversation par ID
     */
    private function getConversationById($conversationId) {
        try {
            $sql = "SELECT c.*, 
                           (SELECT COUNT(*) FROM conversation_participants WHERE conversation_id = c.id) as participant_count
                    FROM conversations c 
                    WHERE c.id = :conversation_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['conversation_id' => $conversationId]);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la conversation: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtenir un message par ID
     */
    private function getMessageById($messageId) {
        try {
            $sql = "SELECT m.*, u.username, u.first_name, u.last_name, u.avatar_url
                    FROM messages m
                    JOIN users u ON m.sender_id = u.id
                    WHERE m.id = :message_id AND m.is_deleted = 0";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['message_id' => $messageId]);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du message: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtenir les participants d'une conversation
     */
    private function getConversationParticipants($conversationId) {
        try {
            $sql = "SELECT cp.*, u.username, u.first_name, u.last_name, u.avatar_url
                    FROM conversation_participants cp
                    JOIN users u ON cp.user_id = u.id
                    WHERE cp.conversation_id = :conversation_id AND cp.left_at IS NULL
                    ORDER BY cp.joined_at";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['conversation_id' => $conversationId]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des participants: " . $e->getMessage());
            return [];
        }
    }
}
