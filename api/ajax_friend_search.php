<?php 
include("../config/config.php");
include("../includes/classes/User.php");

// Requête AJAX envoyée par facebook.js
$query = $_POST['query'];
$userLoggedIn = $_POST['userLoggedIn'];

$names = explode(" ", $query);

// Si on tape un "_" → recherche par username
if (strpos($query, "_") !== false) {
    $usersReturned = mysqli_query($con, "SELECT * FROM users WHERE username LIKE '$query%' AND user_closed='no' LIMIT 8");
}
else if (count($names) >= 2 && count($names) < 4) {
    $usersReturned = mysqli_query($con, "SELECT * FROM users WHERE (first_name LIKE '$names[0]%' AND last_name LIKE '$names[1]%') AND user_closed='no' LIMIT 8");
}
else {
    $usersReturned = mysqli_query($con, "SELECT * FROM users WHERE (first_name LIKE '$names[0]%' OR last_name LIKE '$names[0]%') AND user_closed='no' LIMIT 8");
}

if ($query != "") {
    while ($row = mysqli_fetch_array($usersReturned)) {

        $user = new User($con, $userLoggedIn);

        $mutual_friends = ($row['username'] != $userLoggedIn) 
            ? $user->getMutualFriends($row['username']) . " friends in common" 
            : "";

        // Génération du HTML pour chaque utilisateur trouvé
        if ($user->isFriend($row['username'])) {
            echo "<div class='resultDisplay'>
                <a href='/Facebook-clone/vues/clients/messages.php?u=" . $row['username'] . "' style='color:#000;'>
                    <div class='liveSearchProfilePic'>
                        <img src='/Facebook-clone/" . $row['profile_pic'] . "'>
                    </div>

                    <div class='liveSearchText'>
                        ". $row['first_name'] . " " . $row['last_name'] . "
                        <p style='margin: 0;'>" . $row['username'] . "</p>
                        <p id='grey'>" . $mutual_friends . "</p>
                    </div>
                </a>
            </div>";
        }
    }
}
?>
