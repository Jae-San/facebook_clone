<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . "/../config/config.php");

// Récupère l'utilisateur connecté (session ou AJAX)
$userLoggedIn = isset($_SESSION['username']) ? $_SESSION['username'] : null;
if (!$userLoggedIn && isset($_POST['userLoggedIn'])) {
    $userLoggedIn = $_POST['userLoggedIn'];
}
if (!$userLoggedIn) {
    echo "<span style='color:red'>Session expirée. Veuillez vous reconnecter.</span>";
    exit;
}

// ====USER DETAILS===========
if (isset($_POST['update_details'])) {
    $first_name = mysqli_real_escape_string($con, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($con, $_POST['last_name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);

    // Vérifie si l'email est déjà utilisé par un autre utilisateur
    $email_check = mysqli_query($con, "SELECT username FROM users WHERE email='$email' AND username != '$userLoggedIn'");
    if (mysqli_num_rows($email_check) > 0) {
        echo "<span style='color:red'>That email is already in use!</span><br><br>";
        exit;
    }

    error_log("DEBUG: userLoggedIn = $userLoggedIn");
    $query = mysqli_query($con, "UPDATE users SET first_name='$first_name', last_name='$last_name', email='$email' WHERE username='$userLoggedIn'");
    if ($query) {
        if (mysqli_affected_rows($con) > 0) {
            echo "<span style='color:green'>Details updated!</span><br><br>";
        } else {
            echo "<span style='color:orange'>Aucune modification (données identiques ou utilisateur non trouvé).</span><br><br>";
        }
    } else {
        echo "<span style='color:red'>Erreur SQL : " . mysqli_error($con) . "</span><br><br>";
    }
    exit;
}

//=========CHANGE PW==========================
if (isset($_POST['update_password'])) {
    $old_password = strip_tags($_POST['old_password']);
    $new_password_1 = strip_tags($_POST['new_password_1']);
    $new_password_2 = strip_tags($_POST['new_password_2']);

    $password_query = mysqli_query($con, "SELECT password FROM users WHERE username='$userLoggedIn'");
    $row = mysqli_fetch_array($password_query);
    $db_password = $row['password'];

    if (md5($old_password) == $db_password) {
        if ($new_password_1 == $new_password_2) {
            if(strlen($new_password_1) <= 4) {
                echo "<span style='color:red'>Sorry, your password must be greater than 4 characters!</span><br><br>";
            } else {
                $new_password_md5 = md5($new_password_1);
                $password_query = mysqli_query($con, "UPDATE users SET password='$new_password_md5' WHERE username='$userLoggedIn'");
                echo "<span style='color:green'>Password has been changed!</span><br><br>";
            }
        } else {
            echo "<span style='color:red'>Your two new passwords need to match!</span><br><br>";
        }
    } else {
        echo "<span style='color:red'>The old password is incorrect!</span><br><br>";
    }
    exit;
}

// ==========CLOSE ACCOUNT===================
if (isset($_POST['close_account'])) {
    header("Location: /Facebook-clone/vues/clients/close_account.php");
    exit;
}
?>
