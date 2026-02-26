<?php
require_once 'auth_check.php';
session_destroy();
header('Location: login.php?logged_out=1');
exit();
?>

