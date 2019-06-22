<?php

namespace App;

use DateTime;

final class Input extends App {

	public function home($request, $response) {
    if (!isset($_SESSION['user_id'])) {
      unset($_SESSION['loggedIn']);
      session_destroy();
      return $response->withRedirect('/');
    }

    if (!isset($_SESSION['evento_id']) || !isset($_SESSION['role'])) {      
      return $response->withRedirect('/roleselect');
    }

    $vars['user_id'] = $_SESSION['user_id'];
    $vars['username'] = $_SESSION['username'];
    $vars['evento_id'] = $_SESSION['evento_id'];
    $vars['eventoname'] = $_SESSION['eventoname'];

		$response = $this->view->render($response, "input.phtml", $vars);

    return $response;
	}

  public function get_pass_data($request, $response) {
    $data = $request->getParsedBody();

    //$user_id = 88;
    //$evento = '18BR';

    $user_id = $_SESSION['user_id'];
    $evento = $_SESSION['evento_id'];

    $sql1 = "SELECT pass_time, pass_data, time_offset, 1 as synced
               FROM enduro_pass_raw
              WHERE evento = ?
                AND user = ?
                AND deletado=0
           ORDER BY pass_time ASC";

    $db1 = $this->db->prepare($sql1);
    $db1->execute(array($evento, $user_id));

    if ( $db1->rowCount() > 0 ) {
      echo json_encode($db1->fetchAll(\PDO::FETCH_OBJ));           
    } else {
      echo json_encode("noData");
    }
  }

  public function add_pass($request, $response) {
    $data = $request->getParsedBody();

    //$user_id = 88;
    //$evento = '18BR';

    $user_id = $_SESSION['user_id'];
    $evento = $_SESSION['evento_id'];

    $pass_data = filter_var($data['pass_data'], FILTER_SANITIZE_STRING);
    $pass_time = filter_var($data['pass_time'], FILTER_SANITIZE_NUMBER_INT);
    $pass_time_friendly = date('Y-m-d H:i:s', floor($pass_time/1000));
    $pass_time_friendly .= '.';
    $pass_time_friendly .= floor(1000*($pass_time/1000 - floor($pass_time/1000)));
    $time_offset = filter_var($data['time_offset'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    $sql1 = "INSERT INTO enduro_pass_raw
                         (user, evento, pass_data, pass_time_friendly, pass_time, time_offset, pass_data_original, pass_time_original, time_offset_original)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $db1 = $this->db->prepare($sql1);
    $db1->execute(array($user_id, $evento, $pass_data, $pass_time_friendly, $pass_time, $time_offset, $pass_data, $pass_time, $time_offset));

    if ( $db1->errorCode() > 0 ) {
      echo json_encode(array("result"=>"error", "message"=>$db1->errorCode()));
    } else {
      $pass_id = $this->db->lastInsertId();
      echo json_encode($data, JSON_FORCE_OBJECT);
      $opdata['operation'] = 'ADD_PASS';
      $opdata['evento_id'] = $evento;
      $opdata['user_id'] = $user_id;
      $opdata['pass_id'] = $pass_id;      
      $buffer = fopen('..\app\op_buffer', 'a');
      flock($buffer, LOCK_EX);
      fwrite($buffer, json_encode($opdata + $data)."\n");
      flock($buffer, LOCK_UN);
      fclose($buffer);
    }
  }

  public function time_offset($request, $response) {
    $t1 = microtime(true);
    $t1 = floor($t1*1000);    
    echo json_encode(array($t1, floor(microtime(true)*1000)), JSON_FORCE_OBJECT);
  }

  public function check_habilitado($request, $response) {
    $sql = "SELECT habilitado
              FROM roles
             WHERE user_id=?
               AND evento_id=?
               AND role=?";
    $db = $this->db->prepare($sql);
    $db->execute(array($_SESSION['user_id'], $_SESSION['evento_id'], $_SESSION['role']));

    if ($db->rowCount() == 1) {
      $result = $db->fetch(\PDO::FETCH_ASSOC);
      echo json_encode($result['habilitado']);
    }
  }
}


?>