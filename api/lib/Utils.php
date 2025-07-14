<?php
/**
 * Utilitaires généraux pour l'API
 * Fonctions de validation, sécurité et réponse JSON
 */

require_once __DIR__ . '/../config/database.php';

class Utils {
    
    /**
     * Envoyer une réponse JSON
     */
    public static function sendJSONResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Envoyer une réponse d'erreur
     */
    public static function sendErrorResponse($message, $statusCode = 400, $errors = null) {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        self::sendJSONResponse($response, $statusCode);
    }
    
    /**
     * Envoyer une réponse de succès
     */
    public static function sendSuccessResponse($data = null, $message = 'Opération réussie') {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        self::sendJSONResponse($response, 200);
    }
    
    /**
     * Valider et nettoyer les données d'entrée
     */
    public static function validateInput($data, $rules) {
        $errors = [];
        $cleaned = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Vérifier si le champ est requis
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "Le champ '$field' est requis";
                continue;
            }
            
            // Si le champ est vide et non requis, passer au suivant
            if (empty($value) && !isset($rule['required'])) {
                continue;
            }
            
            // Nettoyer la valeur
            $cleanedValue = self::sanitizeInput($value);
            
            // Validation de type
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!filter_var($cleanedValue, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "Format d'email invalide";
                        }
                        break;
                    case 'int':
                        if (!is_numeric($cleanedValue)) {
                            $errors[$field] = "Le champ '$field' doit être un nombre";
                        } else {
                            $cleanedValue = (int) $cleanedValue;
                        }
                        break;
                    case 'float':
                        if (!is_numeric($cleanedValue)) {
                            $errors[$field] = "Le champ '$field' doit être un nombre décimal";
                        } else {
                            $cleanedValue = (float) $cleanedValue;
                        }
                        break;
                    case 'boolean':
                        $cleanedValue = filter_var($cleanedValue, FILTER_VALIDATE_BOOLEAN);
                        break;
                }
            }
            
            // Validation de longueur
            if (isset($rule['min_length']) && strlen($cleanedValue) < $rule['min_length']) {
                $errors[$field] = "Le champ '$field' doit contenir au moins {$rule['min_length']} caractères";
            }
            
            if (isset($rule['max_length']) && strlen($cleanedValue) > $rule['max_length']) {
                $errors[$field] = "Le champ '$field' ne peut pas dépasser {$rule['max_length']} caractères";
            }
            
            // Validation de pattern (regex)
            if (isset($rule['pattern']) && !preg_match($rule['pattern'], $cleanedValue)) {
                $errors[$field] = "Le format du champ '$field' est invalide";
            }
            
            // Validation de valeurs autorisées
            if (isset($rule['allowed_values']) && !in_array($cleanedValue, $rule['allowed_values'])) {
                $errors[$field] = "Valeur non autorisée pour le champ '$field'";
            }
            
            $cleaned[$field] = $cleanedValue;
        }
        
        return [
            'errors' => $errors,
            'cleaned' => $cleaned
        ];
    }
    
    /**
     * Nettoyer les données d'entrée
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
    
    /**
     * Générer un token sécurisé
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Hasher un mot de passe
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Vérifier un mot de passe
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Valider la force d'un mot de passe
     */
    public static function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une majuscule";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une minuscule";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un chiffre";
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un caractère spécial";
        }
        
        return $errors;
    }
    
    /**
     * Obtenir les données JSON de la requête
     */
    public static function getJSONInput() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::sendErrorResponse('Données JSON invalides', 400);
        }
        
        return $data;
    }
    
    /**
     * Obtenir les données POST
     */
    public static function getPOSTData() {
        return $_POST;
    }
    
    /**
     * Obtenir les paramètres GET
     */
    public static function getGETParams() {
        return $_GET;
    }
    
    /**
     * Vérifier si la requête est en AJAX
     */
    public static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Obtenir l'adresse IP du client
     */
    public static function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Logger une action
     */
    public static function logAction($action, $userId = null, $details = null) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'user_id' => $userId,
            'ip_address' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'details' => $details
        ];
        
        error_log(json_encode($logEntry) . PHP_EOL, 3, __DIR__ . '/../../logs/api.log');
    }
    
    /**
     * Formater une date pour l'affichage
     */
    public static function formatDate($date, $format = 'd/m/Y H:i') {
        if (is_string($date)) {
            $date = new DateTime($date);
        }
        
        return $date->format($format);
    }
    
    /**
     * Calculer le temps écoulé depuis une date
     */
    public static function timeAgo($date) {
        $now = new DateTime();
        $date = is_string($date) ? new DateTime($date) : $date;
        $diff = $now->diff($date);
        
        if ($diff->y > 0) {
            return $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
        } elseif ($diff->m > 0) {
            return $diff->m . ' mois';
        } elseif ($diff->d > 0) {
            return $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
        } elseif ($diff->h > 0) {
            return $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        } else {
            return 'À l\'instant';
        }
    }
    
    /**
     * Créer un slug à partir d'une chaîne
     */
    public static function createSlug($string) {
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
        $string = preg_replace('/[\s-]+/', '-', $string);
        $string = trim($string, '-');
        
        return $string;
    }
    
    /**
     * Valider une image uploadée
     */
    public static function validateImage($file, $maxSize = 5242880, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
        $errors = [];
        
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Erreur lors de l'upload du fichier";
            return $errors;
        }
        
        if ($file['size'] > $maxSize) {
            $errors[] = "Le fichier est trop volumineux (max: " . ($maxSize / 1024 / 1024) . "MB)";
        }
        
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension']);
        
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = "Type de fichier non autorisé";
        }
        
        // Vérifier le type MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        ];
        
        if (!in_array($mimeType, $allowedMimes)) {
            $errors[] = "Type MIME non autorisé";
        }
        
        return $errors;
    }
    
    /**
     * Uploader une image
     */
    public static function uploadImage($file, $destination, $filename = null) {
        if (!$filename) {
            $filename = uniqid() . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        }
        
        $fullPath = $destination . '/' . $filename;
        
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            return $filename;
        }
        
        return false;
    }
}
