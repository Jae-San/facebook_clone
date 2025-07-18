<?php
require '../config/config.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['ajax']) && isset($_POST['email']) && isset($_POST['password'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $check_database_query = mysqli_query($con, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($check_database_query) == 1) {
        $row = mysqli_fetch_array($check_database_query);
        $username = $row['username'];
        $hash = $row['password'];
        $role = isset($row['role']) ? $row['role'] : '';
        // On considère admin ou modo
        if (($role === 'admin' || $role === 'moderator') && (password_verify($password, $hash) || $hash === md5($password))) {
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            echo json_encode([
                'success' => true,
                'username' => $username,
                'role' => $role
            ]);
            exit();
        } else if ($role !== 'admin' && $role !== 'moderator') {
            echo json_encode([
                'success' => false,
                'error' => 'Accès réservé aux administrateurs et modérateurs.'
            ]);
            exit();
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Email ou mot de passe incorrect.'
            ]);
            exit();
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Email ou mot de passe incorrect.'
        ]);
        exit();
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Requête invalide.'
    ]);
    exit();
} 