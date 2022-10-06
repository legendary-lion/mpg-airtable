<?php

$user = $_POST['user'];
$pass = $_POST['pass'];
include("header.php");
if ($user == "mpgadmin" && $pass == "mpgadmin") {
    session_start();
    $_SESSION['syncauth'] = 1;
}
if ($_SESSION['syncauth']) {
    include("sync-form.php");
}
else {
    if (isset($_POST)) { 
        include("login-form.php");
    }
}
include("footer.php");
?>

