<?php
require '../vendor/autoload.php';

$ini = parse_ini_file('../monitoring.ini');
$ovh = new \Ovh\Api( $ini['application_key'], $ini['application_secret'], $ini['endpoint'], $ini['consumer_key'] );

header('Content-Type: application/json');

$status = $ovh->get('/status/task');

$result = array();

foreach ($status as $s) {
  if (isset($_GET['cloud']) && $s['project'] !== 'PublicCloud') continue;
  if (isset($_GET['vps']) && $s['project'] !== 'VPS') continue;

  if ($s['project'] === 'PublicCloud') {
    if (!isset($result[$s['impactedService']])) {
      $result[$s['impactedService']] = array();
    }
    if (!isset($result[$s['impactedService']][$s['uuid']])) {
      $result[$s['impactedService']][$s['uuid']] = array(
        'status' => NULL,
        'alerts' => array()
      );
    }

    $result[$s['impactedService']][$s['uuid']]['alerts'][] = $s;

    switch ($s['status']) {
      case 'planned':
        if ($result[$s['impactedService']][$s['uuid']]['status'] !== 'inProgress') {
          $result[$s['impactedService']][$s['uuid']]['status'] = 'planned';
        }
        break;
      case 'inProgress':
        $result[$s['impactedService']][$s['uuid']]['status'] = 'inProgress';
        break;
      case 'finished':
        if ($result[$s['impactedService']][$s['uuid']]['status'] !== 'inProgress' && $result[$s['impactedService']][$s['uuid']]['status'] !== 'planned') {
          $result[$s['impactedService']][$s['uuid']]['status'] = 'finished';
        }
        break;
    }
  } else {
    if (!isset($result[$s['impactedService']])) {
      $result[$s['impactedService']] = array(
        'status' => NULL,
        'alerts' => array()
      );
    }

    $result[$s['impactedService']]['alerts'][] = $s;

    switch ($s['status']) {
      case 'planned':
        if ($result[$s['impactedService']]['status'] !== 'inProgress') {
          $result[$s['impactedService']]['status'] = 'planned';
        }
        break;
      case 'inProgress':
        $result[$s['impactedService']]['status'] = 'inProgress';
        break;
      case 'finished':
        if ($result[$s['impactedService']]['status'] !== 'inProgress' && $result[$s['impactedService']]['status'] !== 'planned') {
          $result[$s['impactedService']]['status'] = 'finished';
        }
        break;
    }
  }
}

echo json_encode($result);

exit();
