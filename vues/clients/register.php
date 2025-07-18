<?php

require '../../config/config.php';
require '../../api/register_handler.php'; //register php (contains error_array, must be before login.php)
require '../../api/login_handler.php'; //login php (supprimé pour éviter la redirection)

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="https://cdn1.iconfinder.com/data/icons/logotypes/32/square-facebook-512.png">
    <title>Facebook - Register Page</title>
    <link rel="stylesheet" href="/Facebook-clone/assets/css/register_style.css">
</head>

<body>

    <?php
    //To show error messages correctly! FIX FIX FIX FIX FIX FIX FIX 
    if (isset($_POST['register_button'])) { //If register button is pressed
        echo "
                <script>
                    const loginForm = document.getElementById('first');
                    const registerForm = document.getElementById('second');

                    loginForm.style.display = 'none';
                    registerForm.style.display = 'block';
                    
                </script>
            ";
    }
    ?>

    <div class="wrapper">
        <div class="login-box">
            <div class="login_header">
                <h1>Facebook</h1>
                <p>Login or sign up below!</p>
            </div>
            <div id="first">
                <form action="register.php" method="POST">
                    <!-- Login Form -->
                    <input type="email" name="log_email" placeholder="Email Address" value="<?php
                                                                                            if (isset($_SESSION['log_email'])) {
                                                                                                echo $_SESSION['log_email'];
                                                                                            } ?>" required><br>
                    <input type="password" name="log_password" placeholder="Password" required><br>

                    <?php if (in_array("Email or password was incorrect<br>", $error_array)) echo "Email or password was incorrect<br>"; ?>

                    <!-- Submit button -->
                    <input type="submit" name="login_button" value="Login"><br>

                    <?php
                    if (isset($_GET['reset_success']) && isset($_GET['newpw'])) {
                        echo "<span style='color: #14C800;'>Votre mot de passe a été réinitialisé avec succès ! Nouveau mot de passe : <b>".htmlspecialchars($_GET['newpw'])."</b></span><br>";
                    }
                    ?>

                    <a href="/Facebook-clone/rle/forgotpassword.php" class="forgot">Mot de passe oublié ?</a><br>

                    <a href="#" id="signup" class="signup">Need an account? Register here!</a>
                </form>
            </div>

            <div id="second">
                <!-- form action shows which file handles the data from the form -->
                <form action="register.php" method="POST">
                    <!-- First name -->
                    <input type="text" name="reg_fname" placeholder="First Name" value="<?php
                                                                                        if (isset($_SESSION['reg_fname'])) {
                                                                                            echo $_SESSION['reg_fname'];
                                                                                        } ?>" required>
                    <br>
                    <?php if (in_array("Your first name must be between 2 and 25 characters<br>", $error_array)) echo "Your first name must be between 2 and 25 characters<br>"; ?>

                    <!-- Last name -->
                    <input type="text" name="reg_lname" placeholder="Last Name" value="<?php
                                                                                        if (isset($_SESSION['reg_lname'])) {
                                                                                            echo $_SESSION['reg_lname'];
                                                                                        } ?>" required>
                    <br>
                    <?php if (in_array("Your last name must be between 2 and 25 characters<br>", $error_array)) echo "Your last name must be between 2 and 25 characters<br>"; ?>

                    <!-- Email -->
                    <input type="email" name="reg_email" placeholder="Email" value="<?php
                                                                                    if (isset($_SESSION['reg_email'])) {
                                                                                        echo $_SESSION['reg_email'];
                                                                                    } ?>" required>
                    <br>

                    <!-- Email confirm -->
                    <input type="email" name="reg_email2" placeholder="Confirm Email" value="<?php
                                                                                                if (isset($_SESSION['reg_email2'])) {
                                                                                                    echo $_SESSION['reg_email2'];
                                                                                                } ?>" required>
                    <br>
                    <?php if (in_array("Email already in use<br>", $error_array)) echo "Email already in use<br>";
                    else if (in_array("Invalid email format<br>", $error_array)) echo "Invalid email format<br>";
                    else if (in_array("Emails don't match<br>", $error_array)) echo "Emails don't match<br>"; ?>

                    <!-- Password -->
                    <input type="password" name="reg_password" placeholder="Password" required>
                    <br>

                    <!-- Password Confirm -->
                    <input type="password" name="reg_password2" placeholder="Confirm Password" required>
                    <br>
                    <?php if (in_array("Your passwords do not match!<br>", $error_array)) echo "Your passwords do not match!<br>";
                    else if (in_array("Your password can only contain english characters or numbers<br>", $error_array)) echo "Your password can only contain english characters or numbers<br>";
                    else if (in_array("Your pw must be between 5 and 30 characters<br>", $error_array)) echo "Your pw must be between 5 and 30 characters<br>"; ?>

                    <!-- Submit button -->
                    <input type="submit" name="register_button" value="Register">
                    <br>

                    <?php 
                    if (in_array("<span style='color: #14C800;'>Inscription réussie ! Veuillez vérifier votre email pour activer votre compte.</span><br>", $error_array)) 
                        echo "<span style='color: #14C800;'>Inscription réussie ! Veuillez vérifier votre email pour activer votre compte. Un email de confirmation vient de vous être envoyé. Vous devez confirmer votre email avant de pouvoir vous connecter.</span><br>";
                    else if (in_array("<span style='color: #14C800;'>You're all set! Go ahead and login!</span><br>", $error_array)) 
                        echo "<span style='color: #14C800;'>You're all set! Go ahead and login!</span><br>";
                    ?>
                    <?php
                    // Messages de confirmation d'email
                    if (isset($_GET['email_confirmed'])) {
                        echo "<span style='color: #14C800;'>Email confirmé avec succès ! Vous pouvez maintenant vous connecter.</span><br>";
                    }
                    if (isset($_GET['error']) && $_GET['error'] == 'invalid_token') {
                        echo "<span style='color: #ff0000;'>Lien de confirmation invalide ou expiré.</span><br>";
                    }
                    if (isset($_GET['error']) && $_GET['error'] == 'confirmation_failed') {
                        echo "<span style='color: #ff0000;'>Erreur lors de la confirmation. Veuillez réessayer.</span><br>";
                    }
                    if (isset($_GET['reset_success']) && isset($_GET['newpw'])) {
                        echo "<span style='color: #14C800;'>Votre mot de passe a été réinitialisé avec succès ! Nouveau mot de passe : <b>".htmlspecialchars($_GET['newpw'])."</b></span><br>";
                    }
                    ?>

                    <a href="#" id="signin" class="signin">Already have an account? Sign in here!</a>
                </form>
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="/Facebook-clone/assets/js/register.js"></script>
</body>

</html>