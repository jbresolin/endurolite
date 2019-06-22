<?php

namespace App;

final class Auth extends App {

  public function login_home($request, $response) {
    $response = $this->view->render($response, "login.phtml");

    return $response;    
  }

  public function login($request, $response) {
    $vars['page'] = 'login';

    $data = $request->getParsedBody();

    $login_name = strtolower(filter_var($data['login_name'], FILTER_SANITIZE_STRING));
    $password = filter_var($data['password'], FILTER_SANITIZE_STRING);

    if ($login_name != '' && $password != '') {
      $sql1 = 'SELECT user_id, username, passhash
                FROM user
               WHERE username = ?';
      $db1 = $this->db->prepare($sql1);
      $db1->execute(array($login_name));

      if ($db1->rowCount() != 1) {
        $vars['error_message'] = "Usuário não encontrado.";
      } else {
        $user_data = $db1->fetch(\PDO::FETCH_ASSOC);

        if (!password_verify($password, $user_data["passhash"])) {
          
          $vars['error_message'] = "Senha incorreta.";

        } else {
          //Incluir verificação de prova ativa
          $sql2 = "SELECT r.role, r.evento_id, e.titulo
                     FROM roles r
               INNER JOIN prova p
                       ON p.prova_id=r.prova_id
               INNER JOIN evento e
                       ON e.evento_id=p.evento_id
                      AND e.evento_id=r.evento_id
                    WHERE r.user_id=?
                      AND r.prova_id='END'
                      AND e.ativo=1
                      AND e.finalizado=0";
          $db2 = $this->db->prepare($sql2);
          $db2->execute(array($user_data["user_id"]));

          if ($db2->rowCount() == 1) {
            $roles = $db2->fetch(\PDO::FETCH_ASSOC);

            $_SESSION['loggedIn'] = true;
            $_SESSION['user_id'] = $user_data["user_id"];
            $_SESSION['username'] = $user_data["username"];
            $_SESSION['evento_id'] = $roles["evento_id"];
            $_SESSION['eventoname'] = $roles["titulo"];
            $_SESSION['role'] = $roles["role"];

            return $response->withRedirect('/');

          } elseif ($db2->rowCount() > 1) {
            $_SESSION['loggedIn'] = true;
            $_SESSION['user_id'] = $user_data["user_id"];
            $_SESSION['username'] = $user_data["username"];

            return $response->withRedirect('/');
          } else {
            $vars['error_message'] = "Usuário sem função definida em nenhum evento ativo.";
          }
        }
      }
    }

    $response = $this->view->render($response, "login.phtml", $vars);

    return $response;
  }

  public function logout($request, $response) 
    {   
        $_SESSION['loggedIn'] = false;
        session_destroy();
        
        return $response->withRedirect('/login');
    }
}


?>