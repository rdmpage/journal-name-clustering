<?php

error_reporting(E_ALL);

global $config;

// Date timezone--------------------------------------------------------------------------
date_default_timezone_set('UTC');

// Database-------------------------------------------------------------------------------
$config['pdo'] = new PDO('sqlite:containers.db');

?>
