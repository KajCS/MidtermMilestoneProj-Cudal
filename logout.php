<?php
declare(strict_types=1);
require 'init.php';

// Logging out changes state, so it only happens on POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session->logout();
}

header('Location: login.php');
exit;
