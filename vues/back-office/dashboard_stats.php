<?php
require_once('../../config/config.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['username'])) {
    echo 'Accès interdit (non connecté).';
    exit();
}
$user_query = mysqli_query($con, "SELECT role FROM users WHERE username='" . $_SESSION['username'] . "'");
$row = mysqli_fetch_array($user_query);
$role = isset($row['role']) ? $row['role'] : '';
if ($role !== 'admin' && $role !== 'moderator') {
    echo 'Accès interdit (rôle insuffisant).';
    exit();
}
if (isset($_POST['ajax'])) {
    $nb_users = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as nb FROM users"))['nb'];
    $nb_posts = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as nb FROM posts"))['nb'];
    $nb_messages = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as nb FROM messages"))['nb'];
    echo '<ul>';
    echo '<li>Utilisateurs inscrits : <b>' . $nb_users . '</b></li>';
    echo '<li>Articles publiés : <b>' . $nb_posts . '</b></li>';
    echo '<li>Messages envoyés : <b>' . $nb_messages . '</b></li>';
    echo '</ul>';
    exit();
}
?> 