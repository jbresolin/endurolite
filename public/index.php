<?php

session_start();

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// arquivos de carga dos módulos
require '../app/vendor/autoload.php';
// arquivos de configuração
require '../app/config/config.php';

date_default_timezone_set('America/Sao_Paulo');

//instancia o slim no obj appp
$app = new \Slim\App(["settings" => $config]);

//define o container para tratamento dos modulos
$container = $app->getContainer();

$container['view'] = new \Slim\Views\PhpRenderer("../app/templates/");

$container['db'] = function ($c) {
  $db = $c['settings']['db'];
  $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'].';charset=UTF8', $db['user'], $db['password']);  
  return $pdo;
};

require '../app/config/routes.php';

$app->run();