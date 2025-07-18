<?php
require_once('../../config/config.php');
include_once('../../includes/classes/Post.php');

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
    // Suppression d'article
    if (isset($_POST['delete']) && isset($_POST['post_id'])) {
        $post_id = intval($_POST['post_id']);
        $delete_query = mysqli_query($con, "DELETE FROM posts WHERE id='$post_id'");
        if ($delete_query) {
            echo "Article supprimé.";
        } else {
            echo "Erreur lors de la suppression.";
        }
        exit();
    }
    // Affichage de la liste des articles
    $posts_query = mysqli_query($con, "SELECT id, body, added_by, date_added FROM posts ORDER BY date_added DESC");
    echo '<table border="1" cellpadding="5"><tr><th>ID</th><th>Auteur</th><th>Contenu</th><th>Date</th><th>Action</th></tr>';
    while ($row = mysqli_fetch_array($posts_query)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['added_by']) . '</td>';
        echo '<td>' . htmlspecialchars(substr($row['body'],0,50)) . '...</td>';
        echo '<td>' . htmlspecialchars($row['date_added']) . '</td>';
        echo '<td><button class="deleteArticleBtn" data-postid="' . htmlspecialchars($row['id']) . '">Supprimer</button></td>';
        echo '</tr>';
    }
    echo '</table>';
    exit();
}
?> 