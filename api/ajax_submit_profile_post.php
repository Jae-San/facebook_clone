<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../config/config.php';
include("../includes/classes/User.php");
include("../includes/classes/Post.php");
include("../includes/classes/Notification.php");

if (isset($_POST['post_body'])) {
    $user_from = $_POST['user_from'] ?? '';
    $user_to = $_POST['user_to'] ?? '';
    $post_body = $_POST['post_body'] ?? '';

    if ($user_from && $user_to && $post_body) {
        $post = new Post($con, $user_from);
        $post->submitPost($post_body, $user_to, "");
        echo "success";
    } else {
        echo "error: missing data";
    }
} else {
    echo "error: no post_body";
}
?>
