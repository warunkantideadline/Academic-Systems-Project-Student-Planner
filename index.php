<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // hanya SEKALI di sini

require_once 'config/database.php'; // database dulu
require_once 'config/auth.php';
requireLogin();

// ── ROUTER ──
$page   = isset($_GET['page'])   ? trim($_GET['page'])   : 'home';
$action = isset($_GET['action']) ? trim($_GET['action'])  : 'index';
$id     = isset($_GET['id'])     ? trim($_GET['id'])      : null;

$allowedPages = ['home', 'semester', 'matakuliah', 'rekap'];
if (!in_array($page, $allowedPages)) $page = 'home';

switch ($page) {
    case 'home':
        require_once 'views/home/index.php';
        break;
    case 'semester':
        require_once 'views/semester/index.php';
        break;
    case 'matakuliah':
        if ($action === 'detail' && !empty($id)) {
            require_once 'views/matakuliah/detail.php';
        } else {
            require_once 'views/matakuliah/index.php';
        }
        break;
    case 'rekap':
        require_once 'views/rekap/index.php';
        break;
    default:
        require_once 'views/home/index.php';
        break;
}