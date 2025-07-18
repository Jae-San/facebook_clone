<?php
include("../../includes/header.php");

if (isset($_GET['id'])) {
    $id = $_GET['id'];
} else {
    $id = 0;
}
?>
<script>
if (!sessionStorage.getItem('username')) {
    window.location.href = '/Facebook-clone/vues/clients/register.php';
}
</script>
<?php
?>

<!-- USER DETAILS -->
<div class="user_details column">
    <!-- comes from header page, rewrite in .htaccess -->
    <a href="/Facebook-clone/vues/clients/profile.php?u=<?php echo $userLoggedIn; ?>">
        <img src="/Facebook-clone/<?php echo $user['profile_pic']; ?>" alt="Profile picture">
    </a>
    <div class="user_details_left_right">
        <a href="/Facebook-clone/vues/clients/profile.php?u=<?php echo $userLoggedIn; ?>">
            <?php
            echo $user['first_name'] . " " . $user['last_name'];
            ?>
        </a>
        <br>
        <?php
        echo "Posts: " . $user['num_posts'] . "<br>";
        echo "Likes: " . $user['num_likes'];
        ?>
    </div>
</div>

<div class="main_column column" id="main_column">
    <div class="posts_area">

        <?php
            $post = new Post($con, $userLoggedIn);
            echo $post->getSinglePost($id);
        ?>

    </div>
</div>