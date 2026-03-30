<?php
session_start();
require_once '../config/auth.php';

logoutUser();
header('Location: ../login.php');
exit();