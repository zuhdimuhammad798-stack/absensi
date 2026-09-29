<?php
require_once __DIR__ . '/config/auth.php';
header('Location: ' . (isLoggedIn() ? 'dashboard.php' : 'login.php'));
exit;
