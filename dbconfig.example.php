<?php

// copy this file to dbconfig.php and change values according to your environment

$config = [
    'dsn' => 'mysql:host=localhost;dbname=sm_users',
    'username' => 'root',
    'password' => '',
    'options' => [
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]
];
