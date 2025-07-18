<?php 
include("../config/config.php"); //DB
include("../includes/classes/User.php");
include("../includes/classes/Notification.php");

$limit = 6;//Number of notification to load

// request comes from ajax request in facebook.js, function get DropdownData
$notification = new Notification($con, $_REQUEST['userLoggedIn']); //Request includes pagename and userLoggedIn data
echo $notification->getNotifications($_REQUEST, $limit);
?>
