<?php 
require __DIR__ . '/../config/config.php';
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
        $role = isset($row['role']) ? $row['role'] : 'user'; // Ajout récupération du rôle

        // Vérifie avec password_verify (nouveaux hash) ou md5 (anciens comptes)
        if (password_verify($password, $hash) || $hash === md5($password)) {

            if ($isconfirm == 0) {
                $error_msg = "Vous devez confirmer votre email avant de pouvoir vous connecter.";
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(["success" => false, "error" => $error_msg]);
                    exit();
                } else {
                    array_push($error_array, $error_msg);
                }
            } else {
                $user_closed_query = mysqli_query($con, "SELECT * FROM users WHERE email='$email' AND user_closed='yes'");

                if (mysqli_num_rows($user_closed_query) == 1) {
                    $reopen_account = mysqli_query($con, "UPDATE users SET user_closed='no' WHERE email='$email'");   
                }

                $_SESSION['username'] = $username;
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(["success" => true, "username" => $username, "role" => $role]); // Ajout du rôle dans la réponse
                    exit();
                } else {
                    header('Location: /Facebook-clone/index.php'); // ✅ chemin corrigé
                    exit();
                }
            }

        } else {
            $error_msg = "Email or password was incorrect";
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(["success" => false, "error" => $error_msg]);
                exit();
            } else {
                array_push($error_array, $error_msg);
            }
        }
    } else {
        $error_msg = "Email or password was incorrect";
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(["success" => false, "error" => $error_msg]);
            exit();
        } else {
            array_push($error_array, $error_msg);
        }
    }
}
?>
