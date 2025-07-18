<?php
include("../../includes/header.php");
include("../../api/settings_handler.php"); //Handles buttons functionality

?>

<div class="main_column column">
    <h4>Account Settings</h4><br>
    <?php
    echo "<a href='/Facebook-clone/vues/clients/profile.php?u=$userLoggedIn'>
        <img src='/Facebook-clone/" . $user['profile_pic'] . "' id='small_profile_pic'>
      </a>";
    ?>
    <br>
    <a href="/Facebook-clone/vues/clients/upload.php">Upload new profile picture</a><br><br><br>

    <p id="grey">Modify the values and click 'Update Details'</p>

    <?php
    $user_data_query = mysqli_query($con, "SELECT first_name, last_name, email FROM users WHERE username='$userLoggedIn'");
    $row = mysqli_fetch_array($user_data_query);

    $first_name = $row['first_name'];
    $last_name = $row['last_name'];
    $email = $row['email'];
    ?>

    <form action="settings.php" method="POST" id="updateDetailsForm">

        First name: <input type="text" name="first_name" value="<?php echo $first_name; ?>" class="settings_input"><br>
        Last name: <input type="text" name="last_name" value="<?php echo $last_name; ?>" class="settings_input"><br>
        Email: <input type="text" name="email" value="<?php echo $email; ?>" class="settings_input"><br>

        <div id="updateDetailsMsg"></div>
        <input type="submit" name="update_details" id="save_details" value="Update Details" class="info settings_submit"><br>

    </form>
    <hr>
    <h4>Change Password</h4><br>
    <form action="settings.php" method="POST" id="updatePasswordForm">
        Old Password: <input type="password" name="old_password" class="settings_input"><br>
        New Password: <input type="password" name="new_password_1" class="settings_input"><br>
        New Password Again: <input type="password" name="new_password_2" class="settings_input"><br>

        <div id="updatePasswordMsg"></div>
        <input type="submit" name="update_password" id="save_password" value="Update Password" class="info settings_submit"><br>

    </form>
    <hr>
    <h4>Close Account</h4><br>
    <form action="/Facebook-clone/vues/clients/settings.php" method="POST">
        <!-- Check input, enter username to close... (IDEA) -->
        <input type="submit" name="close_account" id="close_account" value="Close Account" class="danger settings_submit">
    </form>
    <br>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function() {
    $('#updateDetailsForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            url: '../../api/settings_handler.php',
            type: 'POST',
            data: form.serialize() + '&ajax=1&userLoggedIn=' + sessionStorage.getItem('username'),
            success: function(data) {
                $('#updateDetailsMsg').html(data);
            }
        });
    });
    $('#updatePasswordForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            url: '../../api/settings_handler.php',
            type: 'POST',
            data: form.serialize() + '&ajax=1&userLoggedIn=' + sessionStorage.getItem('username'),
            success: function(data) {
                $('#updatePasswordMsg').html(data);
            }
        });
    });
});
</script>