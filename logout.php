<?php
session_start();
unset($_SESSION['vpn_user']);
session_destroy();
header("Location: login.php");
exit;

