<?php


if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] === '/login.php') {
    require 'login.php';
    exit;
} elseif (isset($_GET['path']) && $_GET['path'] === 'login.php') {
    require 'login.php';
    exit;
} else {
    require 'login.php';
    exit;
}
