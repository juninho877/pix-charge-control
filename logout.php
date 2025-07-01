
<?php
require_once 'config/config.php';
require_once 'classes/Auth.php';

$auth = new Auth();
$result = $auth->logout();

redirect('login.php');
?>
