<?php

namespace App;

final class Home extends App {

  public function home($request, $response) {    
    if ($_SESSION['loggedIn'] == true && isset($_SESSION['user_id'])) {
      if (!isset($_SESSION['role'])) {
        return $response->withRedirect('/roleselect');
      } else {
        if ($_SESSION['role'] == 'INPUT') {
          return $response->withRedirect('/input');
        } elseif ($_SESSION['role'] == 'MGMT') {
          return $response->withRedirect('/supervisor');
        } else {
          return $response->withRedirect('/roleselect');
        }
      } 
    } else {
      unset($_SESSION['loggedIn']);
      session_destroy();
      return $response->withRedirect('/');
    }
  }

  public function role_select($request, $response, $msg = null) {
    if (!isset($_SESSION['user_id'])) {
      unset($_SESSION['loggedIn']);
      session_destroy();
      return $response->withRedirect('/');
    }

    if ($msg != null) {
      $vars['error_message'] = $msg;
    }

    $sql = "SELECT r.role, r.evento_id, e.titulo
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
    $db = $this->db->prepare($sql);
    $db->execute(array($_SESSION['user_id']));

    if ($db->rowCount() < 1) {
      $vars['roles'] = false;
    } else {
      $vars['roles'] = $db->fetchAll(\PDO::FETCH_ASSOC);
    }

    $vars['username'] = $_SESSION['username'];

    $response = $this->view->render($response, "roleselect.phtml", $vars);

    return $response;
  }

  public function role_option($request, $response) {
    if (!isset($_SESSION['user_id'])) {
      unset($_SESSION['loggedIn']);
      session_destroy();
      return $response->withRedirect('/');
    }

    $data = $request->getParsedBody();

    $role_req = $data['role'];
    $evento_req = $data['evento_id'];
    $evento_req_nome = $data['titulo'];

    $user_id = $_SESSION['user_id'];

    $sql  = "SELECT COUNT(*) cnt
               FROM roles
              WHERE user_id=?
                AND evento_id=?
                AND prova_id='END'
                AND role=?";
    $db = $this->db->prepare($sql);
    $db->execute(array($user_id, $evento_req, $role_req));

    $result = $db->fetch(\PDO::FETCH_ASSOC);

    if ($result['cnt'] != 1) {
      $msg = "A função selecionada não está vinculada com o seu usuário. (Evento: ".$evento_req.", Função: ".$role_req.")";
      return $this->role_select($request, $response, $msg);
    } else {
      $_SESSION['evento_id'] = $evento_req;
      $_SESSION['eventoname'] = $evento_req_nome;
      $_SESSION['role'] = $role_req;
      return $response->withRedirect('/');
    }
  }

}


?>