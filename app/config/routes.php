<?php

$app->group('', function() {

  $this->  get('[/]',                   '\App\Home:home');
  $this->  get('/roleselect[/]',        '\App\Home:role_select');
  $this-> post('/roleoption[/]',        '\App\Home:role_option');

  $this->  get('/input[/]',             '\App\Input:home');  
  $this-> post('/getPassData[/]',       '\App\Input:get_pass_data');
  $this-> post('/addPass[/]',           '\App\Input:add_pass');
  $this-> post('/timeOffset[/]',        '\App\Input:time_offset');
  $this-> post('/checkHabilitado[/]',   '\App\Input:check_habilitado');

  $this->  get('/supervisor[/]',        '\App\Mgmt:home');
  $this-> post('/uploadObject[/]',      '\App\Mgmt:uploadObject');
  $this-> post('/startEnduro[/]',       '\App\Mgmt:startEnduro');

})->add(App\AuthClass::class);

$app->  get('/login[/]',        '\App\Auth:login_home');
$app-> post('/loginAction[/]',  '\App\Auth:login');
$app->  get('/logout[/]',       '\App\Auth:logout');
$app-> post('/logout[/]',       '\App\Auth:logout');
 



?>