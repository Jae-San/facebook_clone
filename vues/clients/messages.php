<?php
include("../../includes/header.php"); //Also includes classes like User and Post

$message_obj = new Message($con, $userLoggedIn);

if (isset($_GET['u'])) { //U for username
    $user_to = $_GET['u'];
} else {
    $user_to = $message_obj->getMostRecentUser();

    //If user has not started a convo with anybody yet
    if ($user_to == false) {
        $user_to = 'new';
    }
}

if ($user_to != "new") {
    $user_to_obj = new User($con, $user_to); /* you're not trying to send a new message then create a user object of the username that we have here */

    $friend_name = $user_to_obj->getFirstAndLastName();
    $friend_username = $user_to_obj->getUsername();

    $friendProfilePic = mysqli_query($con, "SELECT profile_pic FROM users WHERE username='$friend_username'");

    $friendPic = mysqli_fetch_array($friendProfilePic);
}

if ((isset($_POST['post_message']) || isset($_POST['ajax'])) && $user_to != "new") {
    $body = isset($_POST['message_body']) ? mysqli_real_escape_string($con, $_POST['message_body']) : '';
    $date = date("Y-m-d H:i:s");
    $imagePath = "";
    if(isset($_FILES['chatImage']) && $_FILES['chatImage']['name'] != "") {
        $targetDir = "../../assets/images/messages/";
        if(!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $imageName = uniqid() . basename($_FILES['chatImage']['name']);
        $imageFileType = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
        $targetFile = $targetDir . $imageName;
        if(($_FILES['chatImage']['size'] <= 1000000) && in_array($imageFileType, ['jpg','jpeg','png'])) {
            if(move_uploaded_file($_FILES['chatImage']['tmp_name'], $targetFile)) {
                $imagePath = "assets/images/messages/" . $imageName; // <-- à enregistrer en base
            } else {
                $imagePath = "";
            }
        } else {
            $imagePath = "";
        }
    }
    $message_obj->sendMessage($user_to, $body, $date, $imagePath);
    if (isset($_POST['ajax'])) {
        echo $message_obj->getMessages($user_to);
        exit();
    }
}
?>
<script>
if (!sessionStorage.getItem('username')) {
    window.location.href = '/Facebook-clone/vues/clients/register.php';
}
</script>

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
    <?php
    //GET MESSAGES
    if ($user_to != "new") {

        $open_query = mysqli_query($con, "SELECT opened, id FROM messages WHERE user_from='$userLoggedIn' AND user_to='$user_to' ORDER BY id DESC LIMIT 1"); //my last message
        $latest_query_rec = mysqli_query($con, "SELECT id FROM messages WHERE user_to='$userLoggedIn' AND user_from='$user_to' ORDER BY id DESC LIMIT 1"); //friend's last message

        $check_mess = mysqli_fetch_array($open_query);
        $check_latest = mysqli_fetch_array($latest_query_rec);

        $seen = (isset($check_mess['opened']) && $check_mess['opened'] === 'yes') ? "Seen" : ""; //check if he opened my last message

        if ($user_to == $userLoggedIn)
            echo "<h4>Just you</h4><hr><br>";
        else
            echo '<h4>You and <a href="' . $user_to . '">' . $user_to_obj->getFirstAndLastName() . '</a></h4><hr><br>';

        echo "<div class='loaded_messages' id='scroll_messages'>";
        echo $message_obj->getMessages($user_to);

        if (isset($check_mess['id']) && isset($check_latest['id']) && $check_mess['id'] > $check_latest['id']) //check if mine is the last message in the conversation
            echo "<div style='float:right; position:relative; bottom:5px; right:3px;'>" . $seen . "</div><br>";

        echo "</div>";
    } else {
        echo "<h4>New Message</h4>";
    }
    ?>

    <div class="message_post">
        <!-- action blank = this page -->
        <form action="/Facebook-clone/vues/clients/messages.php?u=<?php echo $user_to; ?>" method="POST" enctype="multipart/form-data" id="sendMessageForm">
            <?php
            if ($user_to == "new") {
                echo "Select the friend you would like to message <br><br>";
                // Search javascript ajax request is in facebook.js
            ?>
                To: <input type='text' onkeyup='getUsers(this.value, "<?php echo $userLoggedIn; ?>")' name='q' placeholder='Name' autocomplete='off' id='search_text_input'>
            <?php
                echo "<div class='results'></div>";
            } else {
                echo "<textarea name='message_body' id='message_textarea' placeholder='Write your message...'></textarea>";
                echo "<input type='file' name='chatImage' accept='image/*' style='margin-top:8px;'><br>";
                echo "<input type='submit' name='post_message' class='info' id='message_submit' value='Send'>";
            }
            ?>
        </form>
    </div>

    <script>
        // Always scroll to most recent message
        var div = document.getElementById("scroll_messages");
        div.scrollTop = div.scrollHeight;

        $(function() {

            $(document).keypress(function(e) {

                if (e.keyCode === 13 && e.shiftKey === false && $("#message_textarea").is(":focus")) {

                    e.preventDefault();

                    $("#message_submit").click();

                    const scrollDown = () => {

                        div.scrollTop = div.scrollHeight;
                    }

                    setTimeout(scrollDown, 800);
                }

            });

        });
    </script>

</div>
<div class="user_details column" id="conversations">
    <h4>Conversations</h4>

    <div class="loaded_conversations">
        <?php
        //Get conversations list
        echo $message_obj->getConvos();
        ?>
    </div>
    <br>
    <a href="/Facebook-clone/vues/clients/messages.php?u=new" style="text-align:center;">New Message</a>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function() {
    $('#sendMessageForm').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('ajax', 1);
        $.ajax({
            url: '/Facebook-clone/vues/clients/messages.php?u=<?php echo $user_to; ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {
                $('#scroll_messages').html(data);
                $('#message_textarea').val('');
                var div = document.getElementById('scroll_messages');
                div.scrollTop = div.scrollHeight;
            }
        });
    });
    // Rafraîchissement automatique des messages toutes les 3 secondes
    setInterval(function() {
        $.ajax({
            url: '/Facebook-clone/vues/clients/messages.php?u=<?php echo $user_to; ?>',
            type: 'POST',
            data: { ajax: 1 },
            success: function(data) {
                $('#scroll_messages').html(data);
                var div = document.getElementById('scroll_messages');
                div.scrollTop = div.scrollHeight;
            }
        });
    }, 3000);
});
</script>