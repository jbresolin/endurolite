<?php

require '../config/config.php';

$db = new PDO('mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'], $config['db']['user'], $config['db']['password']);  

// Tolerância entre pass_raws para busca como simultâneo, vincluar com configurações. (em ms)
$match_tol = 1500;
$lap_reject = 60000;


function checkData($passEv, $passData) {
  //Futuro, incluir verificação de carro habilitado à prova, carro atualmente parado por penalização
  global $db;
  $sql1 = "SELECT presente
             FROM equipe
            WHERE evento_id=?
              AND equipe_id=?";
  $db1 = $db->prepare($sql1);
  $db1->execute(array($passEv, $passData));

  if ($db1->rowCount() == 0) {
    return "UNKNOWN";
  } else {
    $status = $db1->fetch(\PDO::FETCH_ASSOC);
    if ($status['presente'] == 0) {
      return "ABSENT";
    } else {
      return "OK";
    }
  }
}

function checkTime($passEv, $passUsr, $passTime, $passData) {
  global $db, $match_tol;

  $result = array();

  $sql1 = "SELECT MAX(pass_time) pt
             FROM enduro_pass_master
            WHERE evento=?
              AND pass_data=?
              AND pass_time< (?-".$match_tol.")";
  $db1 = $db->prepare($sql1);
  $db1->execute(array($passEv, $passData, $passTime));

  $prevLap = $db1->fetch(\PDO::FETCH_ASSOC);

  if ($prevLap == null || $prevLap == 0) {
    $result['lap0'] = true;
    $result['reject'] = false;
  } else {
    $result['lap0'] = false;
    $result['lap_time'] = $passTime-$prevLap;

    if ($result['lap_time'] < $lap_reject) {
      $result['reject'] = true;
    } else {
      $result['reject'] = false;
    }
    //FUTURO, MAIS COMPARAÇÕES
  }  
  return $result;
}

function checkPlausible($passEv, $passUsr, $passTime, $passData) {
  $check1 = checkData($passEv, $passData);
  if ($check1 == "OK") {
    $check2 = checkTime($passEv, $passUsr, $passTime, $passData);
    if ($check2['reject'] === true) {
      $result['plausible'] = false;
      $result['checkData'] = $check1;
      $result['checkTime'] = $check2;  
    } else {
      $result['plausible'] = true;
      $result['checkData'] = $check1;
      $result['checkTime'] = $check2;  
    }
  } else {
    $result['plausible'] = false;
    $result['checkData'] = $check1;
    $result['checkTime'] = false;
  }
  return $result;
}

function checkPair($passEv, $passUsr, $passId, $passTime, $passData) {
  global $match_tol, $db;
  $sql1 = "SELECT *
             FROM enduro_pass_raw
            WHERE evento = ?
              AND user <> ?
              AND pass_raw_match IS NULL
              AND pass_time 
                  BETWEEN (? - ".$match_tol.")
                  AND (? + ".$match_tol.")
              AND deletado = 0
         ORDER BY pass_time ASC";
  $db1 = $db->prepare($sql1);
  $db1->execute(array($passEv, $passUsr, $passTime, $passTime));  
  if ($db1->rowCount() == 0) {
    $check = checkPlausible($passEv, $passUsr, $passTime, $passData);
    if ($check['plausible'] === true) {
      createSingle($passEv, $passUsr, $passTime, $passData, $check);
    } else {
      createReject($passEv, $passUsr, $passTime, $passData, (['timeMatch'=>'0']+$check));
    }
  } else {
    $data = $db1->fetchAll(\PDO::FETCH_ASSOC);    
    $filter_same = array_keys(array_column($data, "pass_data"), $passData);
    if (count($filter_same) == 0) {
      if (count($data) > 1) {
        createReject($passEv, $passUsr, $passTime, $passData, ['timeMatch'=>'>1', 'filterSame'=>'0']);
      } else {
        $check1 = checkPlausible($passEv, $passUsr, $passTime, $passData);
        $check2 = checkPlausible($data[0]['evento'], $data[0]['user'], $data[0]['pass_time'], $data[0]['pass_data']);
        if (($check1['plausible'] === true && $check2['plausible'] === true) || ($check1['plausible'] === false && $check2['plausible'] === false)) {
          createReject($passEv, $passUsr, $passTime, $passData, (['timeMatch'=>'1', 'filterSame'=>'0']+$check1));
        } else {
          createMatch($passEv, $passUsr, $passTime, $passData, $check1, $data[0]['user'], $data[0]['pass_time'], $data[0]['pass_data'], $check2);
        }
      }
    } else {
      $check1 = checkPlausible($passEv, $passUsr, $passTime, $passData);
      $check2 = checkPlausible($data[$fiter_same[0]]['evento'], $data[$fiter_same[0]]['user'], $data[$fiter_same[0]]['pass_time'], $data[$fiter_same[0]]['pass_data']);
      if ($check1['plausible'] == false || $check2['plausible'] == false) {
        createReject($passEv, $passUsr, $passTime, $passData, (['timeMatch'=>'>=1', 'filterSame'=>'1']+$check1));
      } else {
        createMatch($passEv, $passUsr, $passTime, $passData, $check1, $data[$fiter_same[0]]['user'], $data[$fiter_same[0]]['pass_time'], $data[$fiter_same[0]]['pass_data'], $check2);
      }
    }    
  }
}

function createMatch($passEv, $passUsr1, $passTime1, $passData1, $checks1, $passUsr2, $passTime2, $passData2, $checks2) {


}

function createSingle($passEv, $passUsr, $passId, $passTime, $passData, $checks) {
  $child_id = $passId;

  $sql1 = "INSERT INTO enduro_pass_master
                  (evento, pass_time, pass_data, child1)
                  VALUES (?, ?, ?, ?)";
  $db1 = $db->prepare($sql1);
  $db1->execute(array($passEv, $passTime, $passData, $child_id));

  if ($db2->errorCode() === '00000') {
    $master_id = $db->lastInsertId();

    $sql2 = "UPDATE enduro_pass_raw
                SET master_pass=?
              WHERE id=?";
    $db2 = $db->prepare($sql2);
    $db2->execute(array($master_id, $child_id));

    if ($db3->errorCode() !== '00000') {
      //HANDLE ERROR
    }

    $response[0] = ['type'=>'single-child', 'evento'=>$passEv, 'usuario'=>$passUsr, 'id'=>$child_id, 'pass_time'=>$passTime, 'pass_data'=>$passData, 'eval'=>$checks];
    $response[1] = ['type'=>'single-master', 'evento'=>$passEv, 'id'=>$master_id, 'pass_time'=>$passTime, 'pass_data'=>$passData];

  } else {
    //HANDLE ERROR
  }
}

function createReject($passEv, $passUsr, $passTime, $passData, $checks) {
  $sql1 = "UPDATE enduro_pass_raw
              SET eval = ?
            WHERE evento = ?
              AND user = ?
              AND pass_time = ?
              AND pass_data = ?";
  $db1 = $db->prepare($sql1);
  $db1->execute(array(json_encode($checks), $passEv, $passUsr, $passTime, $passData));

  //HANDLE ERROR!!!

  $response[0] = ['type'=>'reject', 'evento'=>$passEv, 'usuario'=>$passUsr, 'pass_time'=>$passTime, 'pass_data'=>$passData, 'eval'=>$checks];

  return $response;
}




$file1_path = '../op_buffer';
$file2_path = '../push_buffer';
$file3_path = '../op_counter';

$file1 = fopen($file1_path, 'r');
$file2 = fopen($file2_path, 'a');
$file3 = fopen($file3_path, 'r+');

$prev = 1*fread($file3, 4096);

$counter = 0;



while (1) {
  $counter++;  
  $now = filesize($file1_path);
  clearstatcache();  

  if ($prev != $now) {
    $newdata = substr(fread($file1, $now), strlen(fread($file1, $prev)));
    $newdata = explode("\n", $newdata);

    foreach($newdata as $new) {
      if ($new != '') {
        $operation = json_decode($new);

        if ($operation['operation'] == "ADD_PASS") {
          


        }









      }
    }
  }

  ftruncate($file3, 0);
  rewind($file3);
  fwrite($file3, $now);

  $prev = $now;

  sleep(5);
}

echo("ended");

?>