<?php
require_once 'includes/Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: login.php');
exit;
?>
