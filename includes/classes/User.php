<?php
//Object oriented PHP - User class
class User {
    //Will only be able to use these variables inside this class
    private $user; //Basically equals this->user
    private $con;

    //Constructor (like in react) - creates the user object upon call = $new_obj = new User($con, $userLoggedIn) ...
    public function __construct($con, $user) {
        //"THIS" REFERENCES THE CLASS OBJECT
        $this->con = $con;
        $user_details_query = mysqli_query($con, "SELECT * FROM users WHERE username='$user'");
        $this->user = mysqli_fetch_array($user_details_query);
        if (!$this->user) {
            $this->user = null;
        }
    }

    public function getUsername() {
        if (!$this->user || !isset($this->user['username'])) return null;
        return $this->user['username']; //Used in Post.php class for example
    }

    public function getNumberOfFriendRequests() {
        if (!$this->user || !isset($this->user['username'])) return 0;
        $username = $this->user['username'];

        $query = mysqli_query($this->con, "SELECT * FROM friend_requests WHERE user_to='$username'");
        
        return mysqli_num_rows($query);
    }

    public function getNumPosts() {
        if (!$this->user || !isset($this->user['username'])) return 0;
        $username = $this->user['username'];
        $query = mysqli_query($this->con, "SELECT num_posts FROM users WHERE username='$username'"); 
        $row = mysqli_fetch_array($query);
        return $row ? $row['num_posts'] : 0;
    }

    public function getFirstName() {
        if (!$this->user || !isset($this->user['first_name'])) return null;
        return $this->user['first_name'];
    }

    public function getFirstAndLastName() {
        if (!$this->user || !isset($this->user['username'])) return null;
        $username = $this->user['username'];
        $query = mysqli_query($this->con, "SELECT first_name, last_name FROM users WHERE username='$username'");
        $row = mysqli_fetch_array($query);
        return $row ? $row['first_name'] . " " . $row['last_name'] : null;
    }

    public function getProfilePic() {
        if (!$this->user || !isset($this->user['username'])) return null;
        $username = $this->user['username'];
        $query = mysqli_query($this->con, "SELECT profile_pic FROM users WHERE username='$username'");
        $row = mysqli_fetch_array($query);
        return $row ? $row['profile_pic'] : null;
    }

    public function isClosed() {
        if (!$this->user || !isset($this->user['username'])) return false;
        $username = $this->user['username'];
        $query = mysqli_query($this->con, "SELECT user_closed FROM users WHERE username='$username'");
        $row = mysqli_fetch_array($query);

        if ($row && $row['user_closed'] == "yes")
            return true;
        else
            return false;
    }

    public function isFriend($username_to_check) {
        if (!$this->user || !isset($this->user['friend_array']) || !isset($this->user['username'])) {
            return false;
        }
        $usernameComma = "," . $username_to_check . ","; //friend_array in db is with commas

        if ((strstr($this->user['friend_array'], $usernameComma)) || $username_to_check == $this->user['username']) { //Show friends or yourself
            return true;
        } else {
            return false;
        } //Needle, haystack
    }
    
    public function didReceiveRequest($user_from) {
        if (!$this->user || !isset($this->user['username'])) return false;
        $user_to = $this->user['username'];

        $check_request_query = mysqli_query($this->con, "SELECT * FROM friend_requests WHERE user_to='$user_to' AND user_from='$user_from'");

        // If there is more than 0 rows returned from db 
        if (mysqli_num_rows($check_request_query) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function didSendRequest($user_to) {
        if (!$this->user || !isset($this->user['username'])) return false;
        $user_from = $this->user['username'];

        $check_request_query = mysqli_query($this->con, "SELECT * FROM friend_requests WHERE user_to='$user_to' AND user_from='$user_from'");

        // If there is more than 0 rows returned from db 
        if (mysqli_num_rows($check_request_query) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function removeFriend($user_to_remove) {
        if (!$this->user || !isset($this->user['username'])) return;
        $logged_in_user = $this->user['username'];

        $query = mysqli_query($this->con, "SELECT friend_array FROM users WHERE username='$user_to_remove'");
        $row = mysqli_fetch_array($query);
        $friend_array_username = $row ? $row['friend_array'] : '';

        // Update the logged in users friend array, remove friend!
        $new_friend_array = str_replace($user_to_remove . ",", "", $this->user['friend_array']); //Search , replace, subject
        $remove_friend = mysqli_query($this->con, "UPDATE users SET friend_array='$new_friend_array' WHERE username='$logged_in_user'");

        // Update the user friend array that was friends with the logged in user!
        $new_friend_array = str_replace($this->user['username'] . ",", "", $friend_array_username);
        $remove_friend = mysqli_query($this->con, "UPDATE users SET friend_array='$new_friend_array' WHERE username='$user_to_remove'");
    }

    public function sendRequest($user_to) {
        if (!$this->user || !isset($this->user['username'])) return;
        $user_from = $this->user['username'];
        $query = mysqli_query($this->con, "INSERT INTO friend_requests VALUES('', '$user_to', '$user_from')");
    }

    public function getFriendArray() {
        if (!$this->user || !isset($this->user['username'])) return '';
        $username = $this->user['username'];
        $query = mysqli_query($this->con, "SELECT friend_array FROM users WHERE username='$username'");
        $row = mysqli_fetch_array($query);
        return $row ? $row['friend_array'] : '';
    }

    public function getMutualFriends($user_to_check) {
        if (!$this->user || !isset($this->user['friend_array'])) return 0;
        $mutualFriends = 0; //Initialize

        //Get the logged in user friends
        $user_array = $this->user['friend_array'];
        $user_array_explode = explode(",", $user_array); //Splits a string and looks for commas

        //Get the profile's mutual friends that user is on
        $query = mysqli_query($this->con, "SELECT friend_array FROM users WHERE username='$user_to_check'");
        $row = mysqli_fetch_array($query);
        $user_to_check_array = $row ? $row['friend_array'] : '';
        $user_to_check_array_explode = explode(",", $user_to_check_array); //Splits a string and looks for commas

        //Count the matching friends
        foreach($user_array_explode as $i) {
            foreach($user_to_check_array_explode as $j) {
                if ($i == $j && $i != "") {
                    $mutualFriends++;
                }

            }
        }
        return $mutualFriends;
    }
    
    
}


?>