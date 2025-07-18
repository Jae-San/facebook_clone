<?php
require_once('../../config/config.php');
include_once('../../includes/classes/User.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['username'])) {
    echo 'Accès interdit (non connecté).';
    exit();
}
$user_query = mysqli_query($con, "SELECT role FROM users WHERE username='" . $_SESSION['username'] . "'");
$row = mysqli_fetch_array($user_query);
$role = isset($row['role']) ? $row['role'] : '';
if ($role !== 'admin' && $role !== 'moderator') {
    echo 'Accès interdit (rôle insuffisant).';
    exit();
}

if (isset($_POST['ajax'])) {
    // Suppression d'utilisateur
    if (isset($_POST['delete']) && isset($_POST['username'])) {
        $username = $_POST['username'];
        $delete_query = mysqli_query($con, "DELETE FROM users WHERE username='$username'");
        if ($delete_query) {
            echo "Utilisateur supprimé.";
        } else {
            echo "Erreur lors de la suppression.";
        }
        exit();
    }
    // Changement de rôle
    if (isset($_POST['setrole']) && isset($_POST['username']) && isset($_POST['role'])) {
        $username = $_POST['username'];
        $role = $_POST['role'];
        $update_query = mysqli_query($con, "UPDATE users SET role='$role' WHERE username='$username'");
        if ($update_query) {
            echo "Rôle mis à jour.";
        } else {
            echo "Erreur lors de la mise à jour du rôle.";
        }
        exit();
    }
    // Affichage de la liste des utilisateurs
    $users_query = mysqli_query($con, "SELECT username, first_name, last_name, email, role FROM users");
    echo '<table border="1" cellpadding="5"><tr><th>Username</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Action</th></tr>';
    while ($row = mysqli_fetch_array($users_query)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['username']) . '</td>';
        echo '<td>' . htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
        echo '<td>';
        echo '<select class="roleSelect" data-username="' . htmlspecialchars($row['username']) . '">';
        $roles = ['user' => 'Utilisateur', 'moderator' => 'Modérateur', 'admin' => 'Administrateur'];
        foreach ($roles as $val => $label) {
            $selected = ($row['role'] === $val) ? 'selected' : '';
            echo '<option value="' . $val . '" ' . $selected . '>' . $label . '</option>';
        }
        echo '</select>';
        echo '</td>';
        echo '<td><button class="deleteUserBtn" data-username="' . htmlspecialchars($row['username']) . '">Supprimer</button></td>';
        echo '</tr>';
    }
    echo '</table>';
    exit();
}
?> 