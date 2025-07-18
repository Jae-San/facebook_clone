<?php
class Message {
    private $user_obj;
    private $con;

    public function __construct($con, $user) {
        $this->con = $con;
        $this->user_obj = new User($con, $user);
    }

    public function getMostRecentUser() {
        $userLoggedIn = $this->user_obj->getUsername();
        $query = mysqli_query($this->con, "SELECT user_to, user_from FROM messages WHERE user_to='$userLoggedIn' OR user_from='$userLoggedIn' ORDER BY id DESC LIMIT 1");
        if (mysqli_num_rows($query) == 0) return false;

        $row = mysqli_fetch_array($query);
        return ($row['user_to'] != $userLoggedIn) ? $row['user_to'] : $row['user_from'];
    }

    public function sendMessage($user_to, $body, $date, $image = '') {
        if ($body != "") {
            $userLoggedIn = $this->user_obj->getUsername();
            mysqli_query($this->con, "INSERT INTO messages VALUES('', '$user_to', '$userLoggedIn', '$body', '$date', 'no', 'no', 'no', '$image')");
        }
    }

    public function getMessages($otherUser) {
        $userLoggedIn = $this->user_obj->getUsername();
        $data = "";

        mysqli_query($this->con, "UPDATE messages SET opened='yes' WHERE user_to='$userLoggedIn' AND user_from='$otherUser'");
        $get_messages_query = mysqli_query($this->con, "SELECT * FROM messages WHERE (user_to='$userLoggedIn' AND user_from='$otherUser') OR (user_from='$userLoggedIn' AND user_to='$otherUser')");

        while ($row = mysqli_fetch_array($get_messages_query)) {
            $info = ($row['user_to'] === $userLoggedIn) ? (new User($this->con, $otherUser))->getFirstName() . " on " . date("M d Y H:i", strtotime($row['date'])) : "You on " . date("M d Y H:i", strtotime($row['date']));
            $div_top = ($row['user_to'] == $userLoggedIn) ? "<div class='message_g' id='green'>" : "<div class='message_b' id='blue'>";
            $data .= $div_top . "<span>$info</span>" . $row['body'];
            if (!empty($row['image'])) {
                $data .= "<br><img src='/Facebook-clone/" . $row['image'] . "' style='max-width:200px;'><br>";
            }
            $data .= "</div><br><br>";
        }

        return $data;
    }

    public function getLatestMessage($userLoggedIn, $user2) {
        $query = mysqli_query($this->con, "SELECT body, user_to, date FROM messages WHERE (user_to='$userLoggedIn' AND user_from='$user2') OR (user_to='$user2' AND user_from='$userLoggedIn') ORDER BY id DESC LIMIT 1");

        if (mysqli_num_rows($query) == 0) return ["", "", ""];

        $row = mysqli_fetch_array($query);
        $sent_by = ($row['user_to'] == $userLoggedIn) ? "They said: " : "You said: ";

        $date_time_now = date("Y-m-d H:i:s");
        $start_date = new DateTime($row['date']);
        $end_date = new DateTime($date_time_now);
        $interval = $start_date->diff($end_date);

        if ($interval->y >= 1) $time_message = $interval->y . (($interval->y == 1) ? " year ago" : " years ago");
        elseif ($interval->m >= 1) {
            $days = ($interval->d == 0) ? " ago" : (($interval->d == 1) ? " 1 day ago" : " " . $interval->d . " days ago");
            $time_message = $interval->m . (($interval->m == 1) ? " month" : " months") . $days;
        }
        elseif ($interval->d >= 1) $time_message = ($interval->d == 1) ? "Yesterday" : $interval->d . " days ago";
        elseif ($interval->h >= 1) $time_message = $interval->h . (($interval->h == 1) ? " hour ago" : " hours ago");
        elseif ($interval->i >= 1) $time_message = $interval->i . (($interval->i == 1) ? " minute ago" : " minutes ago");
        else $time_message = ($interval->s < 30) ? "Just now" : $interval->s . " seconds ago";

        return [$sent_by, $row['body'], $time_message];
    }

    public function getConvos() {
        $userLoggedIn = $this->user_obj->getUsername();
        $return_string = "";
        $convos = [];

        $query = mysqli_query($this->con, "SELECT user_to, user_from FROM messages WHERE user_to='$userLoggedIn' OR user_from='$userLoggedIn' ORDER BY id DESC");

        while ($row = mysqli_fetch_array($query)) {
            $user_to_push = ($row['user_to'] != $userLoggedIn) ? $row['user_to'] : $row['user_from'];
            if (!in_array($user_to_push, $convos)) array_push($convos, $user_to_push);
        }

        foreach($convos as $username) {
            $user_found_obj = new User($this->con, $username);
            $latest_message_details = $this->getLatestMessage($userLoggedIn, $username);
            $dots = (strlen($latest_message_details[1]) >= 12) ? "..." : "";
            $split = str_split($latest_message_details[1], 12)[0] . $dots;

            $return_string .= "<a href='/Facebook-clone/vues/clients/messages.php?u=$username'><div class='user_found_messages'>
                <img src='/Facebook-clone/" . $user_found_obj->getProfilePic() . "' style='border-radius: 5px; margin-right:5px;'>
                " . $user_found_obj->getFirstAndLastName() . "
                <span class='timestamp_smaller' id='grey'>" . $latest_message_details[2] . "</span>
                <p id='grey' style='margin: 0;'>" . $latest_message_details[0] . $split . "</p>
                </div></a>";
        }

        return $return_string;
    }

    public function getConvosDropdown($data, $limit) {
        $page = $data['page'];
        $userLoggedIn = $this->user_obj->getUsername();
        $return_string = "";
        $convos = [];

        $start = ($page == 1) ? 0 : ($page - 1) * $limit;
        mysqli_query($this->con, "UPDATE messages SET viewed='yes' WHERE user_to='$userLoggedIn'");

        $query = mysqli_query($this->con, "SELECT user_to, user_from FROM messages WHERE user_to='$userLoggedIn' OR user_from='$userLoggedIn' ORDER BY id DESC");

        while ($row = mysqli_fetch_array($query)) {
            $user_to_push = ($row['user_to'] != $userLoggedIn) ? $row['user_to'] : $row['user_from'];
            if (!in_array($user_to_push, $convos)) array_push($convos, $user_to_push);
        }

        $num_iterations = 0;
        $count = 1;

        foreach($convos as $username) {
            if($num_iterations++ < $start) continue;
            if($count > $limit) break;
            else $count++;

            $is_unread_query = mysqli_query($this->con, "SELECT * FROM messages WHERE (user_to='$userLoggedIn' AND user_from='$username') OR (user_from='$userLoggedIn' AND user_to='$username') ORDER BY id DESC");
            $row = mysqli_fetch_array($is_unread_query);
            $style = ($row['opened'] == 'no') ? "background-color: #DDEDFF" : "";

            $user_found_obj = new User($this->con, $username);
            $latest_message_details = $this->getLatestMessage($userLoggedIn, $username);
            $dots = (strlen($latest_message_details[1]) >= 12) ? "..." : "";
            $split = str_split($latest_message_details[1], 12)[0] . $dots;

            if($row['opened'] === 'yes' && $row['user_from'] === $userLoggedIn && $row['user_to'] === $username) {
                $latest_message_details[2] .= " ✓";
            }

            if ($row['opened'] === 'no' && $row['user_from'] === $userLoggedIn && $row['user_to'] === $username) {
                $style = "";
                $latest_message_details[2] .= " ←";
            }

            $return_string .= "<a href='/Facebook-clone/vues/clients/messages.php?u=$username'> <div class='user_found_messages' style='" . $style . "'>
                <img src='/Facebook-clone/" . $user_found_obj->getProfilePic() . "' style='border-radius: 5px; margin-right: 5px;'>
                " . $user_found_obj->getFirstAndLastName() . "<br>
                <span class='timestamp_smaller' id='grey'>" . $latest_message_details[2] . "</span>
                <p id='grey' style='margin: 0;'>" . $latest_message_details[0] . $split  . "</p>
                </div></a>";
        }

        if ($count > $limit) {
            $return_string .= "<input type='hidden' class='nextPageDropdownData' value='" . ($page + 1) . "'>
                                <input type='hidden' class='noMoreDropdownData' value='false'>";
        } else {
            $return_string .= "<input type='hidden' class='noMoreDropdownData' value='true'><p class='nomoremessages' style='text-align:center;'>No more messages to load!</p>";
        }

        return $return_string;
    }

    public function getUnreadNumber() {
        $userLoggedIn = $this->user_obj->getUsername();
        $query = mysqli_query($this->con, "SELECT * FROM messages WHERE viewed='no' AND user_to='$userLoggedIn'");
        return mysqli_num_rows($query);
    }
}
?>
