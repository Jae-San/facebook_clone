<?php

include("../config/config.php");
include("../includes/classes/User.php");
include("../includes/classes/Post.php");

$limit = 5; //Nr of posts to be loaded per call

$posts = new Post($con, $_REQUEST['userLoggedIn']);
$posts->loadPostsFriends($_REQUEST, $limit);

?>
