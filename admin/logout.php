<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/lib/bootstrap.php';
require dirname(__DIR__) . '/lib/auth.php';

admin_logout($config);
header('Location: login.php');
exit;
