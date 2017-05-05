<?php
require '../vendor/autoload.php';

$ini = parse_ini_file('../monitoring.ini');
$ovh = new \Ovh\Api( $ini['application_key'], $ini['application_secret'], $ini['endpoint'], $ini['consumer_key'] );

$cache = '../cache/cloud.json';

header('Content-Type: application/json');

/* ************************************************************************
 *
 */
if (isset($_GET['cpu'])) {
  $result = array();

  $project = json_decode(file_get_contents($cache));
  foreach ($project as $p) {
    $result[$p->project_id] = array();
    foreach ($p->instances as $i) {
      try {
        $max = $ovh->get('/cloud/project/'.$p->project_id.'/instance/'.$i->id.'/monitoring', array( 'period' => 'today', 'type' => 'cpu:max' ));
        $used = $ovh->get('/cloud/project/'.$p->project_id.'/instance/'.$i->id.'/monitoring', array( 'period' => 'today', 'type' => 'cpu:used' ));

        $lastMax = end($max['values']);
        $lastUsed = end($used['values']);

        $result[$p->project_id][$i->id] = array($lastUsed['value'], $used['unit'], round($lastUsed['value'] / $lastMax['value'] * 100));
      } catch (Exception $e) {
        $result[$p->project_id][$i->id] = $e->getMessage();
      }
    }
  }

  echo json_encode($result);
}
/* ************************************************************************
 *
 */
else if (isset($_GET['cpu-chart'], $_GET['project'], $_GET['instance'])) {
  $result = array();

  try {
    $max = $ovh->get('/cloud/project/'.$_GET['project'].'/instance/'.$_GET['instance'].'/monitoring', array( 'period' => 'lastweek', 'type' => 'cpu:max' ));
    $used = $ovh->get('/cloud/project/'.$_GET['project'].'/instance/'.$_GET['instance'].'/monitoring', array( 'period' => 'lastweek', 'type' => 'cpu:used' ));

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

  $project = json_decode(file_get_contents($cache));
  foreach ($project as $p) {
    $result[$p->project_id] = array();
    foreach ($p->instances as $i) {
      try {
        $max = $ovh->get('/cloud/project/'.$p->project_id.'/instance/'.$i->id.'/monitoring', array( 'period' => 'today', 'type' => 'mem:max' ));
        $used = $ovh->get('/cloud/project/'.$p->project_id.'/instance/'.$i->id.'/monitoring', array( 'period' => 'today', 'type' => 'mem:used' ));

        $lastMax = end($max['values']);
        $lastUsed = end($used['values']);

        $result[$p->project_id][$i->id] = array($lastUsed['value'], $used['unit'], round($lastUsed['value'] / $lastMax['value'] * 100));
      } catch (Exception $e) {
        $result[$p->project_id][$i->id] = $e->getMessage();
      }
    }
  }

  echo json_encode($result);
}
/* ************************************************************************
 *
 */
else if (isset($_GET['ram-chart'], $_GET['project'], $_GET['instance'])) {
  $result = array();

  try {
    $max = $ovh->get('/cloud/project/'.$_GET['project'].'/instance/'.$_GET['instance'].'/monitoring', array( 'period' => 'lastweek', 'type' => 'mem:max' ));
    $used = $ovh->get('/cloud/project/'.$_GET['project'].'/instance/'.$_GET['instance'].'/monitoring', array( 'period' => 'lastweek', 'type' => 'mem:used' ));

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