<?php
/**
 * auth/logout.php — Destroy session and redirect
 */

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('ROOT_URL', '../');

require_once ROOT_PATH . 'config/helpers.php';

session_destroy();
redirect(ROOT_URL . 'auth/login.php');
