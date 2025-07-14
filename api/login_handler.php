<?php 

if (isset($_POST['login_button'])) {
    $email = filter_var($_POST['log_email'], FILTER_SANITIZE_EMAIL); //sanitize email

    $_SESSION['log_email'] = $email; //Store email into session variable
    $password = $_POST['log_password']; // On ne hash pas ici

    $check_database_query = mysqli_query($con, "SELECT * FROM users WHERE email='$email'");

    if (mysqli_num_rows($check_database_query) == 1) {
        $row = mysqli_fetch_array($check_database_query);
        $username = $row['username'];
        $isconfirm = $row['isconfirm'];
        $hash = $row['password'];

        // Vérifie avec password_verify (pour les nouveaux hash) ou md5 (pour les anciens comptes)
        if (password_verify($password, $hash) || $hash === md5($password)) {
            // Vérifier si l'email est confirmé
            if ($isconfirm == 0) {
                array_push($error_array, "<span style='color: #ff0000;'>Vous devez confirmer votre email avant de pouvoir vous connecter. Un email de confirmation vous a été envoyé lors de l'inscription. Vérifiez votre boîte de réception et vos spams.</span><br>");
            } else {
                $user_closed_query = mysqli_query($con, "SELECT * FROM users WHERE email='$email' AND user_closed='yes'");

                //if user account closed, logging in will reopen it
                if (mysqli_num_rows($user_closed_query) == 1) {
                    $reopen_account = mysqli_query($con, "UPDATE users SET user_closed='no' WHERE email='$email'");   
                }

                $_SESSION['username'] = $username; //Create a new user session with the username
                header('Location: ../../index.php'); //redirect to index page if logged in!
                exit();
            }
        } else {
            array_push($error_array, "Email or password was incorrect<br>");
        }
    } else {
        array_push($error_array, "Email or password was incorrect<br>");
    }
}

?>