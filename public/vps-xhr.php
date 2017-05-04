<?php
require '../vendor/autoload.php';

$ini = parse_ini_file('../monitoring.ini');
$ovh = new \Ovh\Api( $ini['application_key'], $ini['application_secret'], $ini['endpoint'], $ini['consumer_key'] );

$cache = '../cache/vps.json';

header('Content-Type: application/json');

/* ************************************************************************
 *
 */
if (isset($_GET['status'])) {
  $result = array();

  $vps = json_decode(file_get_contents($cache));
  foreach ($vps as $v) {
    $status = $ovh->get('/vps/'.$v->name.'/status');

    $result[$v->name] = $status;
  }

  echo json_encode($result);
}
/* ************************************************************************
 *
 */
else if (isset($_GET['disk'])) {
  $result = array();

  $vps = json_decode(file_get_contents($cache));
  foreach ($vps as $v) {
    $result[$v->name] = array();

    $disks = $ovh->get('/vps/'.$v->name.'/disks');
    foreach ($disks as $i => $d) {
      try {
        $max = $ovh->get('/vps/'.$v->name.'/disks/'.$d.'/use', array( 'type' => 'max' ));
        $used = $ovh->get('/vps/'.$v->name.'/disks/'.$d.'/use', array( 'type' => 'used' ));

        if ($max['value'] > 0) {
          $result[$v->name][] = array($used['value'], $used['unit'], round($used['value'] / $max['value'] * 100));
        } else {
          $result[$v->name][] = 'Max value = 0';
        }
      } catch (Exception $e) {
        $result[$v->name][] = $e->getMessage();
      }
    }
  }

  echo json_encode($result);
}
/* ************************************************************************
 *
 */
else if (isset($_GET['disk-chart'], $_GET['vps'])) {
  $result = array();

  $disks = $ovh->get('/vps/'.$_GET['vps'].'/disks');
  foreach ($disks as $i => $d) {
    try {
      $max = $ovh->get('/vps/'.$_GET['vps'].'/disks/'.$d.'/monitoring', array( 'period' => 'lastweek', 'type' => 'max' ));
      $used = $ovh->get('/vps/'.$_GET['vps'].'/disks/'.$d.'/monitoring', array( 'period' => 'lastweek', 'type' => 'used' ));

      $values = array();
      foreach ($max['values'] as $v) {
        $values[] = array(
          'x' => date('c', $v['timestamp']),
          'y' => $v['value']
        );
      }
      $max['values'] = $values;

      $values = array();
      foreach ($used['values'] as $v) {
        $values[] = array(
          'x' => date('c', $v['timestamp']),
          'y' => $v['value']
        );
      }
      $used['values'] = $values;

      $result[] = array( 'max' => $max, 'used' => $used );
    } catch (Exception $e) {
      $result[] = $e->getMessage();
    }
  }

  echo json_encode($result);
}
/* ************************************************************************
 *
 */
else if (isset($_GET['cpu'])) {
  $result = array();

  $vps = json_decode(file_get_contents($cache));
  foreach ($vps as $v) {
    try {
      $max = $ovh->get('/vps/'.$v->name.'/use', array( 'type' => 'cpu:max' ));
      $used = $ovh->get('/vps/'.$v->name.'/use', array( 'type' => 'cpu:used' ));

      $result[$v->name] = array($used['value'], $used['unit'], round($used['value'] / $max['value'] * 100));
    } catch (Exception $e) {
      $result[$v->name] = $e->getMessage();
    }
  }

  echo json_encode($result);
}
/* ************************************************************************
 *
 */
else if (isset($_GET['cpu-chart'], $_GET['vps'])) {
  $result = array();

  try {
    $max = $ovh->get('/vps/'.$_GET['vps'].'/monitoring', array( 'period' => 'lastweek', 'type' => 'cpu:max' ));
    $used = $ovh->get('/vps/'.$_GET['vps'].'/monitoring', array( 'period' => 'lastweek', 'type' => 'cpu:used' ));

    $values = array();
    foreach ($max['values'] as $v) {
      $values[] = array(
        'x' => date('c', $v['timestamp']),
        'y' => $v['value']
      );
    }
    $max['values'] = $values;

    $values = array();
    foreach ($used['values'] as $v) {
      $values[] = array(
        'x' => date('c', $v['timestamp']),
        'y' => $v['value']
      );
    }
    $used['values'] = $values;

    $result[] = array( 'max' => $max, 'used' => $used );
  } catch (Exception $e) {
    $result[] = $e->getMessage();
  }

  echo json_encode($result);
}
/* ************************************************************************
 *
 */
else if (isset($_GET['ram'])) {
  $result = array();

  $vps = json_decode(file_get_contents($cache));
  foreach ($vps as $v) {
    try {
      $max = $ovh->get('/vps/'.$v->name.'/use', array( 'type' => 'mem:max' ));
      $used = $ovh->get('/vps/'.$v->name.'/use', array( 'type' => 'mem:used' ));

      $result[$v->name] = array($used['value'], $used['unit'], round($used['value'] / $max['value'] * 100));
    } catch (Exception $e) {
      $result[$v->name] = $e->getMessage();
    }
  }

  echo json_encode($result);
}
/* ************************************************************************
 *
 */
else if (isset($_GET['ram-chart'], $_GET['vps'])) {
  $result = array();

  try {
    $max = $ovh->get('/vps/'.$_GET['vps'].'/monitoring', array( 'period' => 'lastweek', 'type' => 'mem:max' ));
    $used = $ovh->get('/vps/'.$_GET['vps'].'/monitoring', array( 'period' => 'lastweek', 'type' => 'mem:used' ));

    $values = array();
    foreach ($max['values'] as $v) {
      $values[] = array(
        'x' => date('c', $v['timestamp']),
        'y' => $v['value']
      );
    }
    $max['values'] = $values;

    $values = array();
    foreach ($used['values'] as $v) {
      $values[] = array(
        'x' => date('c', $v['timestamp']),
        'y' => $v['value']
      );
    }
    $used['values'] = $values;

    $result[] = array( 'max' => $max, 'used' => $used );
  } catch (Exception $e) {
    $result[] = $e->getMessage();
  }

  echo json_encode($result);
}

exit();