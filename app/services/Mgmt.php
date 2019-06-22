<?php

namespace App;

final class Mgmt extends App {

  public function home($request, $response) {
    if (!isset($_SESSION['user_id'])) {
      unset($_SESSION['loggedIn']);
      session_destroy();
      return $response->withRedirect('/');
    }

    if (!isset($_SESSION['evento_id']) || !isset($_SESSION['role'])) {      
      return $response->withRedirect('/roleselect');
    }

    $sql1 = "SELECT et.equipe_id, eq.equipe, et.lap_count, et.lap_time_avg, et.lap_time_min, et.lap_time_max
               FROM enduro_totals et
         INNER JOIN equipe eq ON eq.equipe_id=et.equipe_id AND eq.evento_id=et.evento_id
              WHERE et.evento_id=?
           ORDER BY et.equipe_id ASC";
    $db1 = $this->db->prepare($sql1);
    $db1->execute(array($_SESSION['evento_id']));

    $vars['evento_id'] = $_SESSION['evento_id'];
    $vars['username'] = $_SESSION['username'];

    if ($db1->rowCount() == 0) {
      //ERRO SEM EQUIPES
    } else {
      $vars['dados'] = $db1->fetchAll(\PDO::FETCH_ASSOC);
    }

    $response = $this->view->render($response, "mgmt.phtml", $vars);

    return $response;
  }

  public function uploadObject($request, $response) {
    if (!isset($_SESSION['user_id'])) {
      unset($_SESSION['loggedIn']);
      session_destroy();
      return $response->withRedirect('/');
    }

    if (!isset($_SESSION['evento_id']) || !isset($_SESSION['role'])) {      
      return $response->withRedirect('/roleselect');
    }

    $sql1 = "SELECT equipe_id number, lap_count laps, lap_time_min bestLapTime
               FROM enduro_totals
              WHERE evento_id = ?";
    $db1 = $this->db->prepare($sql1);
    $db1->execute(array($_SESSION['evento_id']));

    if ($db1->rowCount() == 0) {
      return "No Data";
    } else {
      $object = $db1->fetchAll(\PDO::FETCH_OBJ);
    }

    $sql2 = "SELECT tempo FROM prova WHERE evento_id=? AND prova_id='END'";
    $db2 = $this->db->prepare($sql2);
    $db2->execute(array($_SESSION['evento_id']));

    $hora_ini = $db2->fetch(\PDO::FETCH_ASSOC);

    if ($hora_ini['tempo'] == null) {
      return "Not Started";
    }

    $result[0] = json_encode($object, JSON_NUMERIC_CHECK);
    $result[1] = $hora_ini['tempo'];

    return json_encode($result);
  }

  public function startEnduro($request, $response) {
    $data = $request->getParsedBody();

    $hora = 1*$data['hora'];

    $sql1 = "UPDATE prova SET tempo = ? WHERE evento_id=? AND prova_id='END' AND tempo IS NULL";
    $db1 = $this->db->prepare($sql1);
    $db1->execute(array($hora, $_SESSION['evento_id']));

    if ($db1->rowCount() == 1) {
      return "Iniciado";
    } else {
      $sql2 = "SELECT * FROM prova WHERE evento_id=? AND prova_id='END' AND tempo IS NOT NULL";
      $db2 = $this->db->prepare($sql2);
      $db2->execute(array($_SESSION['evento_id']));

      if ($db2->rowCount() == 1) {
        return "Já estava iniciado";
      } else {
        return "Evento/prova não cadastrado";
      }
    }

  }
}


?>