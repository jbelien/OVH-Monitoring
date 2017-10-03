<?php
require '../vendor/autoload.php';

$ini = parse_ini_file('../monitoring.ini');
$ovh = new \Ovh\Api($ini['application_key'], $ini['application_secret'], $ini['endpoint'], $ini['consumer_key']);

$cache = '../cache/vps.json';

/* ************************************************************************
 *
 */
if (isset($_GET['status'])) {
  $result = array();

  $vps = json_decode(file_get_contents($cache));
  foreach ($vps as $v) {
    if (in_array($v->infos->status, array('expired', 'unPaid'))) {
      continue;
    }

    $status = $ovh->get('/vps/'.$v->name.'/status');

    $result[$v->name] = $status;
  }

  header('Content-Type: application/json');
  echo json_encode($result);
}
/* ************************************************************************
 *
 */
else if (isset($_GET['disk'])) {
  $result = array();

  $vps = json_decode(file_get_contents($cache));
  foreach ($vps as $v) {
    if (in_array($v->infos->status, array('expired', 'unPaid'))) {
      continue;
    }

    $result[$v->name] = array();

    $disks = $ovh->get('/vps/'.$v->name.'/disks');
    foreach ($disks as $i => $d) {
      try {
        $max = $ovh->get('/vps/'.$v->name.'/disks/'.$d.'/use', array('type' => 'max'));
        $used = $ovh->get('/vps/'.$v->name.'/disks/'.$d.'/use', array('type' => 'used'));

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

  header('Content-Type: application/json');
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
      $max = $ovh->get('/vps/'.$_GET['vps'].'/disks/'.$d.'/monitoring', array('period' => 'lastweek', 'type' => 'max'));
      $used = $ovh->get('/vps/'.$_GET['vps'].'/disks/'.$d.'/monitoring', array('period' => 'lastweek', 'type' => 'used'));

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

      $result[] = array('max' => $max, 'used' => $used);
    } catch (Exception $e) {
      $result[] = $e->getMessage();
    }
  }

  header('Content-Type: application/json');
  echo json_encode($result);
}
/* ************************************************************************
 *
 */
else if (isset($_GET['cpu'])) {
  $result = array();

  $vps = json_decode(file_get_contents($cache));
  foreach ($vps as $v) {
    if (in_array($v->infos->status, array('expired', 'unPaid'))) {
      continue;
    }

    try {
      if (substr($v->model->version, 0, 4) === '2014') {
        $max = $ovh->get('/vps/'.$v->name.'/use', array('type' => 'cpu:max'));
        $used = $ovh->get('/vps/'.$v->name.'/use', array('type' => 'cpu:used'));

        $result[$v->name] = array($used['value'], $used['unit'], round($used['value'] / $max['value'] * 100));
      } else {
        $max = $ovh->get('/vps/'.$v->name.'/monitoring', array('period' => 'today', 'type' => 'cpu:max'));
        $used = $ovh->get('/vps/'.$v->name.'/monitoring', array('period' => 'today', 'type' => 'cpu:used'));

        $lastMax = array_pop($max['values']);
        $lastUsed = array_pop($used['values']);

        $prevUsed = array_pop($used['values']);
        $status = (round($lastUsed['value']) > round($prevUsed['value']) ? 1 : (round($lastUsed['value']) < round($prevUsed['value']) ? -1 : 0));

        $result[$v->name] = array($lastUsed['value'], $used['unit'], round($lastUsed['value'] / $lastMax['value'] * 100), $status);
      }
    } catch (Exception $e) {
      $result[$v->name] = $e->getMessage();
    }
  }

  header('Content-Type: application/json');
  echo json_encode($result);
}
/* ************************************************************************
 *
 */
else if (isset($_GET['cpu-chart'], $_GET['vps'])) {
  $result = array();

  try {
    $max = $ovh->get('/vps/'.$_GET['vps'].'/monitoring', array('period' => 'lastweek', 'type' => 'cpu:max'));
    $used = $ovh->get('/vps/'.$_GET['vps'].'/monitoring', array('period' => 'lastweek', 'type' => 'cpu:used'));

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

    $result[] = array('max' => $max, 'used' => $used);
  } catch (Exception $e) {
    $result[] = $e->getMessage();
  }

  header('Content-Type: application/json');
  echo json_encode($result);
}
/* ************************************************************************
 *
 */
else if (isset($_GET['ram'])) {
  $result = array();

  $vps = json_decode(file_get_contents($cache));
  foreach ($vps as $v) {
    if (in_array($v->infos->status, array('expired', 'unPaid'))) {
      continue;
    }

    try {
      if (substr($v->model->version, 0, 4) === '2014') {
        $max = $ovh->get('/vps/'.$v->name.'/use', array('type' => 'mem:max'));
        $used = $ovh->get('/vps/'.$v->name.'/use', array('type' => 'mem:used'));

        $result[$v->name] = array($used['value'], $used['unit'], round($used['value'] / $max['value'] * 100));
      } else {
        $max = $ovh->get('/vps/'.$v->name.'/monitoring', array('period' => 'today', 'type' => 'mem:max'));
        $used = $ovh->get('/vps/'.$v->name.'/monitoring', array('period' => 'today', 'type' => 'mem:used'));

        $lastMax = array_pop($max['values']);
        $lastUsed = array_pop($used['values']);

        $prevUsed = array_pop($used['values']);
        $status = (round($lastUsed['value']) > round($prevUsed['value']) ? 1 : (round($lastUsed['value']) < round($prevUsed['value']) ? -1 : 0));

        $result[$v->name] = array($lastUsed['value'], $used['unit'], round($lastUsed['value'] / $lastMax['value'] * 100), $status);
      }
    } catch (Exception $e) {
      $result[$v->name] = $e->getMessage();
    }
  }

  header('Content-Type: application/json');
  echo json_encode($result);
}
/* ************************************************************************
 *
 */
else if (isset($_GET['ram-chart'], $_GET['vps'])) {
  $result = array();

  try {
    $max = $ovh->get('/vps/'.$_GET['vps'].'/monitoring', array('period' => 'lastweek', 'type' => 'mem:max'));
    $used = $ovh->get('/vps/'.$_GET['vps'].'/monitoring', array('period' => 'lastweek', 'type' => 'mem:used'));

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

    $result[] = array('max' => $max, 'used' => $used);
  } catch (Exception $e) {
    $result[] = $e->getMessage();
  }

  header('Content-Type: application/json');
  echo json_encode($result);
}
/* ************************************************************************
 *
 */
else if (isset($_REQUEST['info'], $_REQUEST['vps'])) {
  $json = json_decode(file_get_contents($cache));
  $vps = NULL;
  foreach ($json as $j) {
    if ($j->name === $_REQUEST['vps']) {
      $vps = $j;
      break;
    }
  }

  if (!is_null($vps)) {
    $d1 = new DateTime($vps->infos->expiration);
    $d2 = new DateTime();
    $diff = $d1->diff($d2);
?>
  <table class="table table-sm table-striped">
    <tbody>
      <tr<?= (in_array($vps->infos->status, array('expired', 'unPaid')) ? ' class="text-danger"' : '') ?>>
        <th><i class="fa fa-fw fa-circle" aria-hidden="true"></i> <?= _('Status') ?></th>
        <td><?= $vps->infos->status ?></td>
      </tr>
      <tr>
        <th><i class="fa fa-calendar" aria-hidden="true"></i> <?= _('Creation') ?></th>
        <td><?= $vps->infos->creation ?></td>
      </tr>
      <tr<?= ($diff->days < 30 ? ' class="text-warning"' : '') ?>>
        <th><i class="fa fa-calendar" aria-hidden="true"></i> <?= _('Expiration') ?></th>
        <td><?= $vps->infos->expiration ?> (<?= sprintf(_('%d days'), $diff->days) ?>)</td>
      </tr>
      <tr>
        <th><i class="fa fa-credit-card" aria-hidden="true"></i> <?= _('Renewal') ?></th>
        <td><?= $vps->infos->renewalType ?></td>
      </tr>
      <tr>
        <th><i class="fa fa-user" aria-hidden="true"></i> <?= _('Administration contact') ?></th>
        <td><?= $vps->infos->contactAdmin ?></td>
      </tr>
      <tr>
        <th><i class="fa fa-user" aria-hidden="true"></i> <?= _('Billing contact') ?></th>
        <td><?= $vps->infos->contactBilling ?></td>
      </tr>
      <tr>
        <th><i class="fa fa-user" aria-hidden="true"></i> <?= _('Technical contact') ?></th>
        <td><?= $vps->infos->contactTech ?></td>
      </tr>
    </tbody>
  </table>
<?php
  }
}

exit();
