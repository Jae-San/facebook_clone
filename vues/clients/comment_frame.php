 <?php

    require '../../config/config.php'; //getting $con var
    include("../../includes/classes/User.php"); //Call in the USER CLASS
    include("../../includes/classes/Post.php"); //Call in the Post CLASS
    include("../../includes/classes/Notification.php"); //Call in the Notification CLASS

    //If user is logged in 
    if (isset($_SESSION['username'])) {
        $userLoggedIn = $_SESSION['username'];

        //Get user details from db
        $user_details_query = mysqli_query($con, "SELECT * FROM users WHERE username='$userLoggedIn'");

        $user = mysqli_fetch_array($user_details_query); //return array from db

    } else {
        header("Location: /Facebook-clone/vues/clients/register.php");
        exit(); //If not logged in, redirect to register
    }
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
            font-size: 12px;
            font-family: Arial, Helvetica, sans-serif;
        }
    </style>

    <script>
        //Basic toggle function to show/hide the comment section
        function toggle() {
            let element = document.getElementById("comment_section");

            if (element.style.display == "block") {
                element.style.display == "none";
            } else {
                element.style.display == "block";
            }
        }
    </script>

    <?php
    //Get id of post that the use wants to comment on
    if (isset($_GET['post_id'])) {
        $post_id = $_GET['post_id'];
    }

    $user_query = mysqli_query($con, "SELECT added_by, user_to FROM posts WHERE id='$post_id'");
    $row = mysqli_fetch_array($user_query);

    $posted_to = $row['added_by'];
    $user_to = $row['user_to'];

    if ((isset($_POST['post_body']) && isset($_POST['userLoggedIn']) && isset($_POST['ajax'])) || isset($_POST['postComment' . $post_id])) {
        $post_body = isset($_POST['post_body']) ? $_POST['post_body'] : '';
        $userLoggedIn = isset($_POST['userLoggedIn']) ? $_POST['userLoggedIn'] : $userLoggedIn;
        $post_body = mysqli_escape_string($con, $post_body);
        $date_time_now = date("Y-m-d H:i:s");
        $insertpost = mysqli_query($con, "INSERT INTO comments VALUES ('', '$post_body', '$userLoggedIn', '$posted_to', '$date_time_now', 'no', '$post_id')");
        //Insert notification
        if ($posted_to != $userLoggedIn) {
            $notification = new Notification($con, $userLoggedIn);
            $notification->insertNotification($post_id, $posted_to, "comment");
        }
        if ($user_to != 'none' && $user_to != $userLoggedIn) {
            $notification = new Notification($con, $userLoggedIn);
            $notification->insertNotification($post_id, $user_to, "profile_comment");
        }
        // Générer le HTML du nouveau commentaire
        $user_obj = new User($con, $userLoggedIn);
        $time_message = "Just now";
        $comment_html = '<div class="comment_section">'
            .'<a href="/Facebook-clone/vues/clients/profile.php?u=' . $userLoggedIn . '" target="_parent">'
            .'<img src="/Facebook-clone/' . $user_obj->getProfilePic() . '" alt="Comment_profile_pic" title="' . $userLoggedIn . '" style="float:left; height: 30px;">'
            .'</a>'
            .'<a href="/Facebook-clone/vues/clients/profile.php?u=' . $userLoggedIn . '" target="_parent">'
            .'<b>' . $user_obj->getFirstAndLastName() . ' </b>'
            .'</a>'
            .'&nbsp;&nbsp;&nbsp;&nbsp; ' . $time_message . "<br>" . htmlspecialchars($post_body)
            .'<hr>'
            .'</div>';
        if (isset($_POST['ajax'])) {
            echo $comment_html;
            exit();
        }
        // Sinon, comportement classique
        echo "<php>Comment posted!</php>";
    }

    ?>

    <form action="/Facebook-clone/vues/clients/comment_frame.php?post_id=<?php echo $post_id; ?>" id="comment_form" name="postComment<?php echo $post_id; ?>" method="POST">
        <textarea name="post_body"></textarea>
        <input type="submit" name="postComment<?php echo $post_id; ?>" value="Post">
    </form>

    <div id="comments_list">
    <?php
    $get_comments = mysqli_query($con, "SELECT * FROM comments WHERE post_id='$post_id' ORDER BY id DESC");
    $count = mysqli_num_rows($get_comments);

    if ($count != 0) {
        while ($comment = mysqli_fetch_array($get_comments)) {
            $comment_body = $comment['post_body'];
            $posted_by = $comment['posted_by'];
            $posted_to = $comment['posted_to'];
            $date_added = $comment['date_added'];
            $removed = $comment['removed'];
            //Timeframe
            $date_time_now = date("Y-m-d H:i:s");
            $start_date = new DateTime($date_added); //Time of post
            $end_date = new DateTime($date_time_now); //Current time
            $interval = $start_date->diff($end_date); //Difference between dates
            if ($interval->y >= 1) {
                if ($interval->y == 1) {
                    $time_message = $interval->y . " year ago"; //1 year ago
                } else {
                    $time_message = $interval->y . " years ago"; //.. years ago
                }
            } else if ($interval->m >= 1) {
                if ($interval->d == 0) {
                    $days = " ago";
                } else if ($interval->d == 1) {
                    $days = $interval->d . " day ago";
                } else {
                    $days = $interval->d . " days ago";
                }
                if ($interval->m == 1) {
                    $time_message = $interval->m . " month" . $days;
                } else {
                    $time_message = $interval->m . " months" . $days;
                }
            } else if ($interval->d >= 1) {
                if ($interval->d == 1) {
                    $time_message = "Yesterday";
                } else {
                    $time_message = $interval->d . " days ago";
                }
            } else if ($interval->h >= 1) {
                if ($interval->h == 1) {
                    $time_message = $interval->h . " hour ago";
                } else {
                    $time_message = $interval->h . " hours ago";
                }
            } else if ($interval->i >= 1) {
                if ($interval->i == 1) {
                    $time_message = $interval->i . " minute ago";
                } else {
                    $time_message = $interval->i . " minutes ago";
                }
            } else {
                if ($interval->s < 30) {
                    $time_message = "Just now";
                } else {
                    $time_message = $interval->s . " seconds ago";
                }
            }
            $user_obj = new User($con, $posted_by);
            ?>
            <div class="comment_section">
                <a href="/Facebook-clone/vues/clients/profile.php?u=<?php echo $posted_by; ?>" target="_parent">
                    <img src="/Facebook-clone/<?php echo $user_obj->getProfilePic(); ?>" alt="Comment_profile_pic" title="<?php echo $posted_by; ?>" style="float:left; height: 30px;">
                </a>
                <a href="/Facebook-clone/vues/clients/profile.php?u=<?php echo $posted_by; ?>" target="_parent">
                    <b><?php echo $user_obj->getFirstAndLastName(); ?> </b>
                </a>
                 &nbsp;&nbsp;&nbsp;&nbsp; <?php echo $time_message . "<br>" . $comment_body; ?>
                <hr>
            </div>
            <?php
        }
    } else {
        echo "<center><br><br>No Comments to Show!</center>";
    }
    ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#comment_form').on('submit', function(e) {
            e.preventDefault();
            var post_body = $(this).find('textarea[name=post_body]').val();
            var post_id = <?php echo json_encode($post_id); ?>;
            var username = sessionStorage.getItem('username');
            $.ajax({
                url: '/Facebook-clone/vues/clients/comment_frame.php?post_id=' + post_id,
                type: 'POST',
                data: {
                    post_body: post_body,
                    userLoggedIn: username,
                    ajax: 1
                },
                success: function(data) {
                    $('#comments_list').prepend(data);
                    $('#comment_form textarea[name=post_body]').val('');
                }
            });
        });
    });
    </script>

  

</body>

</html>