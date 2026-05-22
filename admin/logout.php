<?php
require_once __DIR__ . '/config.php';
session_name(ABH_SESSION_NAME);
session_start();
session_destroy();
header('Location: index.php');
exit;
