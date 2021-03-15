<?php
require '../vendor/autoload.php';

$ini = parse_ini_file('../monitoring.ini');
$timeout = $ini['timeout'] ?? 0;
$client = new \GuzzleHttp\Client([
    'connect_timeout' => $timeout,
    'read_timeout'    => $timeout,
    'timeout'         => $timeout,
]);
$ovh = new \Ovh\Api($ini['application_key'], $ini['application_secret'], $ini['endpoint'], $ini['consumer_key'], $client);

$cache = '../cache/vps.json';

/* ************************************************************************
 *
 */
if (isset($_GET['status'])) {
    $result = [];

    $vps = json_decode(file_get_contents($cache));
    foreach ($vps as $v) {
        if (in_array($v->infos->status, ['expired', 'unPaid'])) {
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
elseif (isset($_GET['disk'])) {
    $result = [];

    $vps = json_decode(file_get_contents($cache));
    foreach ($vps as $v) {
        if (in_array($v->infos->status, ['expired', 'unPaid'])) {
            continue;
        }

        $result[$v->name] = [];

        $disks = $ovh->get('/vps/'.$v->name.'/disks');
        foreach ($disks as $i => $d) {
            try {
                $max = $ovh->get('/vps/'.$v->name.'/disks/'.$d.'/use', ['type' => 'max']);
                $used = $ovh->get('/vps/'.$v->name.'/disks/'.$d.'/use', ['type' => 'used']);

                if ($max['value'] > 0) {
                    $result[$v->name][] = [$used['value'], $used['unit'], round($used['value'] / $max['value'] * 100)];
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
elseif (isset($_GET['disk-chart'], $_GET['vps'])) {
    $result = [];

    $disks = $ovh->get('/vps/'.$_GET['vps'].'/disks');
    foreach ($disks as $i => $d) {
        try {
            $max = $ovh->get('/vps/'.$_GET['vps'].'/disks/'.$d.'/monitoring', ['period' => 'lastweek', 'type' => 'max']);
            $used = $ovh->get('/vps/'.$_GET['vps'].'/disks/'.$d.'/monitoring', ['period' => 'lastweek', 'type' => 'used']);

            $values = [];
            foreach ($max['values'] as $v) {
                $values[] = [
                    'x' => date('c', $v['timestamp']),
                    'y' => $v['value'],
                ];
            }
            $max['values'] = $values;

            $values = [];
            foreach ($used['values'] as $v) {
                $values[] = [
                    'x' => date('c', $v['timestamp']),
                    'y' => $v['value'],
                ];
            }
            $used['values'] = $values;

            $result[] = ['max' => $max, 'used' => $used];
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
elseif (isset($_GET['cpu'])) {
    $result = [];

    $vps = json_decode(file_get_contents($cache));
    foreach ($vps as $v) {
        if (in_array($v->infos->status, ['expired', 'unPaid'])) {
            continue;
        }

        try {
            if (substr($v->model->version, 0, 4) === '2014') {
                $max = $ovh->get('/vps/'.$v->name.'/use', ['type' => 'cpu:max']);
                $used = $ovh->get('/vps/'.$v->name.'/use', ['type' => 'cpu:used']);

                $result[$v->name] = [$used['value'], $used['unit'], round($used['value'] / $max['value'] * 100)];
            } else {
                $max = $ovh->get('/vps/'.$v->name.'/monitoring', ['period' => 'today', 'type' => 'cpu:max']);
                $used = $ovh->get('/vps/'.$v->name.'/monitoring', ['period' => 'today', 'type' => 'cpu:used']);

                $lastMax = array_pop($max['values']);
                $lastUsed = array_pop($used['values']);

                $prevUsed = array_pop($used['values']);
                $status = (round($lastUsed['value']) > round($prevUsed['value']) ? 1 : (round($lastUsed['value']) < round($prevUsed['value']) ? -1 : 0));

                $result[$v->name] = [$lastUsed['value'], $used['unit'], round($lastUsed['value'] / $lastMax['value'] * 100), $status];
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
elseif (isset($_GET['cpu-chart'], $_GET['vps'])) {
    $result = [];

    try {
        $max = $ovh->get('/vps/'.$_GET['vps'].'/monitoring', ['period' => 'lastweek', 'type' => 'cpu:max']);
        $used = $ovh->get('/vps/'.$_GET['vps'].'/monitoring', ['period' => 'lastweek', 'type' => 'cpu:used']);

        $values = [];
        foreach ($max['values'] as $v) {
            $values[] = [
                'x' => date('c', $v['timestamp']),
                'y' => $v['value'],
            ];
        }
        $max['values'] = $values;

        $values = [];
        foreach ($used['values'] as $v) {
            $values[] = [
                'x' => date('c', $v['timestamp']),
                'y' => $v['value'],
            ];
        }
        $used['values'] = $values;

        $result[] = ['max' => $max, 'used' => $used];
    } catch (Exception $e) {
        $result[] = $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($result);
}
/* ************************************************************************
 *
 */
elseif (isset($_GET['ram'])) {
    $result = [];

    $vps = json_decode(file_get_contents($cache));
    foreach ($vps as $v) {
        if (in_array($v->infos->status, ['expired', 'unPaid'])) {
            continue;
        }

        try {
            if (substr($v->model->version, 0, 4) === '2014') {
                $max = $ovh->get('/vps/'.$v->name.'/use', ['type' => 'mem:max']);
                $used = $ovh->get('/vps/'.$v->name.'/use', ['type' => 'mem:used']);

                $result[$v->name] = [$used['value'], $used['unit'], round($used['value'] / $max['value'] * 100)];
            } else {
                $max = $ovh->get('/vps/'.$v->name.'/monitoring', ['period' => 'today', 'type' => 'mem:max']);
                $used = $ovh->get('/vps/'.$v->name.'/monitoring', ['period' => 'today', 'type' => 'mem:used']);

                $lastMax = array_pop($max['values']);
                $lastUsed = array_pop($used['values']);

                $prevUsed = array_pop($used['values']);
                $status = (round($lastUsed['value']) > round($prevUsed['value']) ? 1 : (round($lastUsed['value']) < round($prevUsed['value']) ? -1 : 0));

                $result[$v->name] = [$lastUsed['value'], $used['unit'], round($lastUsed['value'] / $lastMax['value'] * 100), $status];
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
elseif (isset($_GET['ram-chart'], $_GET['vps'])) {
    $result = [];

    try {
        $max = $ovh->get('/vps/'.$_GET['vps'].'/monitoring', ['period' => 'lastweek', 'type' => 'mem:max']);
        $used = $ovh->get('/vps/'.$_GET['vps'].'/monitoring', ['period' => 'lastweek', 'type' => 'mem:used']);

        $values = [];
        foreach ($max['values'] as $v) {
            $values[] = [
                'x' => date('c', $v['timestamp']),
                'y' => $v['value'],
            ];
        }
        $max['values'] = $values;

        $values = [];
        foreach ($used['values'] as $v) {
            $values[] = [
                'x' => date('c', $v['timestamp']),
                'y' => $v['value'],
            ];
        }
        $used['values'] = $values;

        $result[] = ['max' => $max, 'used' => $used];
    } catch (Exception $e) {
        $result[] = $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($result);
}
/* ************************************************************************
 *
 */
elseif (isset($_REQUEST['info'], $_REQUEST['vps'])) {
    $json = json_decode(file_get_contents($cache));
    $vps = null;
    foreach ($json as $j) {
        if ($j->name === $_REQUEST['vps']) {
            $vps = $j;
            break;
        }
    }

    if (!is_null($vps)) {
        $d1 = new DateTime();
        $d2 = new DateTime($vps->infos->expiration);
        $diff = $d1->diff($d2); ?>
  <table class="table table-sm table-striped">
    <tbody>
      <tr<?= ($vps->state === 'maintenance' || in_array($vps->infos->status, ['expired', 'unPaid']) ? ' class="text-danger"' : '') ?>>
        <th><i class="fa fa-fw fa-circle" aria-hidden="true"></i> <?= _('Status') ?></th>
        <td><?= ($vps->state === 'maintenance' ? $vps->state : $vps->infos->status) ?></td>
      </tr>
      <tr>
        <th><i class="fa fa-fw fa-calendar" aria-hidden="true"></i> <?= _('Creation') ?></th>
        <td><?= $vps->infos->creation ?></td>
      </tr>
      <tr<?= ($diff->days < 30 ? ' class="text-warning"' : '') ?>>
        <th><i class="fa fa-fw fa-calendar" aria-hidden="true"></i> <?= _('Expiration') ?></th>
        <td><?= $vps->infos->expiration ?> (<?= $diff->format('%r%a days') ?>)</td>
      </tr>
      <tr>
        <th><i class="fa fa-fw fa-credit-card" aria-hidden="true"></i> <?= _('Renewal') ?></th>
        <td>
<?php
  if ($vps->infos->renewalType === 'manual' || $vps->infos->renew->manualPayment === true) {
      echo _('Manual');
  } elseif ($vps->infos->renew->automatic === true) {
      echo sprintf(ngettext('Automatic: %d month', 'Automatic: %d months', $vps->infos->renew->period), $vps->infos->renew->period);
  } ?>
        </td>
      </tr>
      <tr>
        <th><i class="fa fa-fw fa-user" aria-hidden="true"></i> <?= _('Administration contact') ?></th>
        <td><?= $vps->infos->contactAdmin ?></td>
      </tr>
      <tr>
        <th><i class="fa fa-fw fa-user" aria-hidden="true"></i> <?= _('Billing contact') ?></th>
        <td><?= $vps->infos->contactBilling ?></td>
      </tr>
      <tr>
        <th><i class="fa fa-fw fa-user" aria-hidden="true"></i> <?= _('Technical contact') ?></th>
        <td><?= $vps->infos->contactTech ?></td>
      </tr>
    </tbody>
  </table>
<?php
    }
}

exit();
