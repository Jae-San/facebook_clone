 <?php

    require '../../config/config.php'; //getting $con var
    include("../../includes/classes/User.php"); //Call in the USER CLASS
    include("../../includes/classes/Post.php"); //Call in the Post CLASS
    include("../../includes/classes/Notification.php"); //Call in the Notification CLASS


    //If user is logged in 
    if (isset($_POST['userLoggedIn'])) {
        $userLoggedIn = $_POST['userLoggedIn'];
        //Get user details from db
        $user_details_query = mysqli_query($con, "SELECT * FROM users WHERE username='$userLoggedIn'");
        $user = mysqli_fetch_array($user_details_query); //return array from db
    } else if (isset($_SESSION['username'])) {
        $userLoggedIn = $_SESSION['username'];
        //Get user details from db
        $user_details_query = mysqli_query($con, "SELECT * FROM users WHERE username='$userLoggedIn'");
        $user = mysqli_fetch_array($user_details_query); //return array from db
    } else {
        header("Location: /Facebook-clone/vues/clients/register.php");
        exit(); //If not logged in, redirect to register
    }

    //Get id of post that the use wants to like/liked 
    if (isset($_GET['post_id'])) {
        $post_id = $_GET['post_id'];
    }

    $get_likes = mysqli_query($con, "SELECT likes, added_by FROM posts WHERE id='$post_id'");
    $row = mysqli_fetch_array($get_likes);

    $total_likes = $row['likes']; //Number of likes
    $user_liked = $row['added_by']; //User who liked some post

    $user_details_query = mysqli_query($con, "SELECT * FROM users WHERE username='$user_liked'"); //info of user who liked
    $row = mysqli_fetch_array($user_details_query);
    $total_user_likes = $row['num_likes'];

    //Like/Unlike AJAX
    if (isset($_POST['ajax']) && isset($_POST['action']) && isset($_POST['userLoggedIn'])) {
        $userLoggedIn = $_POST['userLoggedIn'];
        $action = $_POST['action'];
        if ($action == 'like') {
            $total_likes++;
            $query = mysqli_query($con, "UPDATE posts SET likes='$total_likes' WHERE id='$post_id'");
            $total_user_likes++;
            $user_likes = mysqli_query($con, "UPDATE users SET num_likes='$total_user_likes' WHERE username='$user_liked'");
            $insert_user = mysqli_query($con, "INSERT INTO likes VALUES ('', '$userLoggedIn', '$post_id')");
            if ($user_liked != $userLoggedIn) {
                $notification = new Notification($con, $userLoggedIn);
                $notification->insertNotification($post_id, $user_liked, "like");
            }
            $liked = true;
        } else if ($action == 'unlike') {
            $total_likes--;
            $query = mysqli_query($con, "UPDATE posts SET likes='$total_likes' WHERE id='$post_id'");
            $total_user_likes--;
            $user_likes = mysqli_query($con, "UPDATE users SET num_likes='$total_user_likes' WHERE username='$user_liked'");
            $insert_user = mysqli_query($con, "DELETE FROM likes WHERE username='$userLoggedIn' AND post_id='$post_id'");
            $liked = false;
        }
        echo json_encode([
            'total_likes' => $total_likes,
            'liked' => $liked
        ]);
        exit();
    }

    //Like button
    if (isset($_POST['like_button'])) {

        $total_likes++; //Increase the like count on the post and update the value in db
        $query = mysqli_query($con, "UPDATE posts SET likes='$total_likes' WHERE id='$post_id'");

        $total_user_likes++; //Increase the like count on user total likes
        $user_likes = mysqli_query($con, "UPDATE users SET num_likes='$total_user_likes' WHERE username='$user_liked'");
        $insert_user = mysqli_query($con, "INSERT INTO likes VALUES ('', '$userLoggedIn', '$post_id')");

        //Insert notification
        if ($user_liked != $userLoggedIn) { //If user didn't like their own post
            $notification = new Notification($con, $userLoggedIn);
            $notification->insertNotification($post_id, $user_liked, "like");
        }
    }

    //Unlike button
    if (isset($_POST['unlike_button'])) {

        $total_likes--; //Increase the like count on the post and update the value in db
        $query = mysqli_query($con, "UPDATE posts SET likes='$total_likes' WHERE id='$post_id'");

        $total_user_likes--; //Increase the like count on user total likes
        $user_likes = mysqli_query($con, "UPDATE users SET num_likes='$total_user_likes' WHERE username='$user_liked'");
        $insert_user = mysqli_query($con, "DELETE FROM likes WHERE username='$userLoggedIn' AND post_id='$post_id'");
    }

    //Check for previous likes
    $check_query = mysqli_query($con, "SELECT * FROM likes WHERE username='$userLoggedIn' AND post_id='$post_id'");
    $num_rows = mysqli_num_rows($check_query);
    $liked = ($num_rows > 0);
    echo '<button id="likeBtn" data-post-id="' . $post_id . '" data-liked="' . ($liked ? '1' : '0') . '">' . ($liked ? 'Unlike' : 'Like') . '</button>';
    echo '<div id="likeCount">' . $total_likes . ' Likes</div>';

    ?>

 <html lang="en">

 <head>
     <meta charset="UTF-8">
     <meta http-equiv="X-UA-Compatible" content="IE=edge">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title></title>
     <!-- My CSS -->
     <link rel="stylesheet" href="/Facebook-clone/assets/css/style.css">
 </head>

 <body>

     <style>
        * {
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background-color: #fff;
        }

        form {
            position: absolute;
            top: 0;
        }
     </style>

     <script>
if (!sessionStorage.getItem('username')) {
    window.location.href = '/Facebook-clone/vues/clients/register.php';
}
</script>
     <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
     <script>
     $(document).ready(function() {
         $('#likeBtn').on('click', function() {
             var btn = $(this);
             var post_id = btn.data('post-id');
             var liked = btn.data('liked') == 1;
             var username = sessionStorage.getItem('username');
             var action = liked ? 'unlike' : 'like';
             $.ajax({
                 url: '/Facebook-clone/vues/clients/like.php?post_id=' + post_id,
                 type: 'POST',
                 data: {
                     userLoggedIn: username,
                     action: action,
                     ajax: 1
                 },
                 dataType: 'json',
                 success: function(data) {
                     btn.text(data.liked ? 'Unlike' : 'Like');
                     btn.data('liked', data.liked ? 1 : 0);
                     $('#likeCount').text(data.total_likes + ' Likes');
                 }
             });
         });
     });
     </script>

 </body>

 </html>