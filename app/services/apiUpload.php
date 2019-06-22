<?php 

require '../config/config.php';

$db = new PDO('mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'], $config['db']['user'], $config['db']['password']);  









?>