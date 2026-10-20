<?php
session_start();
unset($_SESSION['is_admin'], $_SESSION['admin_user']);
session_destroy();
header('Location: login.php');
exit;
?>