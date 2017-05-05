<?php
require 'vendor/autoload.php';

$ini = parse_ini_file('../monitoring.ini');
$ovh = new \Ovh\Api( $ini['application_key'], $ini['application_secret'], $ini['endpoint'], $ini['consumer_key'] );

$cache = '../cache/cloud.json';
if (!file_exists($cache) || filemtime($cache) < (time() - 7*24*60*60) || isset($_GET['nocache'])) {
  $json = array(); $errors = array();

  $project = $ovh->get('/cloud/project');
  foreach ($project as $p) {
    $_p =  $ovh->get('/cloud/project/'.$p);

    $instances = array();
    $instance = $ovh->get('/cloud/project/'.$p.'/instance');
    foreach ($instance as $i) {
      //$_i = $ovh->get('/cloud/project/'.$p.'/instance/'.$i);
      try {
        $i['flavor'] = $ovh->get('/cloud/project/'.$p.'/flavor/'.$i['flavorId']);
        $i['image'] = $ovh->get('/cloud/project/'.$p.'/image/'.$i['imageId']);
      } catch (Exception $e) {
        $errors[] = $e->getMessage();
      }

      $instances[] = $i;
    }

    $_p['instances'] = $instances;

    $volumes = array();
    $volume = $ovh->get('/cloud/project/'.$p.'/volume');
    foreach ($volume as $v) {
      $volumes[] = $v;
    }

    $_p['volumes'] = $volumes;

    $json[] = $_p;
  }

  if (!file_exists('../cache') || !is_dir('../cache')) { mkdir('../cache'); }
  file_put_contents($cache, json_encode($json, JSON_PRETTY_PRINT));
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OVH Cloud Monitoring</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://opensource.keycdn.com/fontawesome/4.7.0/font-awesome.min.css" integrity="sha384-dNpIIXE8U05kAbPhy3G1cz+yZmTzA6CY8Vg/u2L9xRnHjJiAK76m2BIEaSEV+/aU" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <div class="container-fluid">
      <h1 class="mt-3">OVH Monitoring</h1>
      <ul class="nav nav-tabs mt-3">
        <li class="nav-item">
          <a class="nav-link" href="vps.php">VPS</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="cloud.php">Cloud</a>
        </li>
      </ul>
<?php
$project = json_decode(file_get_contents($cache));
foreach ($project as $p) {
?>
      <h2 class="mt-3">Project &laquo; <?= $p->description ?> &raquo;<br><small><?= $p->project_id ?></small></h2>
      <div class="row">
        <div class="col">
          <table class="table table-bordered table-striped table-sm mt-3">
            <thead class="thead-inverse">
              <tr>
                <th>
                  Instance
                  <a id="refresh" href="cloud.php?nocache" class="float-right"><i class="fa fa-refresh" aria-hidden="true"></i> Refresh</a>
                </th>
                <th>Region</th>
                <th>Flavor</th>
                <th>Image</th>
                <th>Disk</th>
                <th colspan="3">vCPU(s)</th>
                <th colspan="3">RAM</th>
              </tr>
            </thead>
            <tbody>
<?php foreach ($p->instances as $i) { ?>
              <tr data-project="<?= $p->project_id ?>" data-instance="<?= $i->id ?>">
                <th><?= $i->name ?><br><small><?= $i->id ?></small></th>
                <td style="vertical-align: middle;"><?= $i->region ?></td>
                <td style="vertical-align: middle;"><?= $i->flavor->type ?> - <?= $i->flavor->name ?> (<?= $i->flavor->osType ?>)</td>
                <td style="vertical-align: middle;"><?= (isset($i->image) ? $i->image->name : '-') ?></td>
                <td style="vertical-align: middle;" class="text-nowrap text-right"><?= $i->flavor->disk ?> Go</td>
                <td style="vertical-align: middle;" class="text-right"><?= $i->flavor->vcpus ?></td>
                <td style="vertical-align: middle;" class="text-nowrap text-right"><i class="fa fa fa-spinner fa-pulse fa-fw"></i></td>
                <td style="vertical-align: middle;" class="text-nowrap text-center"><a href="#cpu-chart" style="text-decoration: none;"><i class="fa fa-line-chart" aria-hidden="true"></i></a></td>
                <td style="vertical-align: middle;" class="text-nowrap text-right"><?= ($i->flavor->ram / 1000) ?> Go</td>
                <td style="vertical-align: middle;" class="text-nowrap text-right"><i class="fa fa fa-spinner fa-pulse fa-fw"></i></td>
                <td style="vertical-align: middle;" class="text-nowrap text-center"><a href="#ram-chart" style="text-decoration: none;"><i class="fa fa-line-chart" aria-hidden="true"></i></a></td>
              </tr>
<?php } ?>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="14" class="text-right small text-muted"><?= _('Last update') ?> : <?= date('d.m.Y H:i', filemtime($cache)) ?></td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div class="col">
          <table class="table table-bordered table-striped table-sm mt-3">
            <thead class="thead-inverse">
              <tr>
                <th>Volume</th>
                <th>Region</th>
                <th>Type</th>
                <th>Size</th>
                <th>Status</th>
                <th>Attached to</th>
              </tr>
            </thead>
            <tbody>
<?php foreach ($p->volumes as $v) { ?>
              <tr>
                <th><?= $v->name ?><br><small><?= $v->id ?></small></th>
                <td style="vertical-align: middle;"><?= $v->region ?></td>
                <td style="vertical-align: middle;"><?= $v->type ?></td>
                <td style="vertical-align: middle;" class="text-nowrap text-right"><?= $v->size ?> Go</td>
                <td style="vertical-align: middle;"><?= $v->status ?></td>
                <td>
<?php
  if (count($v->attachedTo) == 1) {
    $attach = NULL; $k = 0;
    while (is_null($attach) || $k < count($p->instances)) {
      if ($p->instances[$k]->id === $v->attachedTo[0]) {
        $attach = $p->instances[$k];
      }
      $k++;
    }
?>
                  <strong><?= $attach->name ?></strong><br><small><?= $attach->id ?></small>
<?php
  }
?>
                </td>
              </tr>
<?php } ?>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="14" class="text-right small text-muted"><?= _('Last update') ?> : <?= date('d.m.Y H:i', filemtime($cache)) ?></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <hr>
<?php
}

/*
$project = $ovh->get('/cloud/project');
foreach ($project as $p) {
  $_p =  $ovh->get('/cloud/project/'.$p);
?>
      <h2 class="mt-3">Project &laquo; <?= $_p['description'] ?> &raquo;</h2>
      <table class="table table-bordered table-striped table-sm mt-3">
        <thead class="thead-inverse">
          <tr>
            <th>Instance</th>
            <th>Region</th>
            <th>Flavor</th>
            <th>Image</th>
            <th>Disk</th>
            <th colspan="2">vCPU(s)</th>
            <th colspan="2">RAM</th>
          </tr>
        </thead>
        <tbody>
<?php
  $instance = $ovh->get('/cloud/project/'.$p.'/instance');
  foreach ($instance as $i) {
    try {
      $_f = $ovh->get('/cloud/project/'.$p.'/flavor/'.$i['flavorId']);
      $_i = $ovh->get('/cloud/project/'.$p.'/image/'.$i['imageId']);
    } catch(Exception $e) {

    }
?>
          <tr>
            <th><?= $i['name'] ?><br><small><?= $i['id'] ?></small></th>
            <td><?= $i['region'] ?></td>
            <td><?= $_f['type'] ?> - <?= $_f['name'] ?> (<?= $_f['osType'] ?>)</td>
            <td><?= (isset($_i) ? $_i['name'] : '-') ?></td>
            <td class="text-nowrap text-xs-right"><?= $_f['disk'] ?> Go</td>
            <td class="text-xs-right"><?= $_f['vcpus'] ?></td>
            <td class="text-nowrap">
<?php
//  try {
//    $cpuMax = $ovh->get('/cloud/project/'.$p.'/instance/'.$i['id'].'/monitoring', array( 'period' => 'today', 'type' => 'cpu:max' ));
//    $cpuUsed = $ovh->get('/cloud/project/'.$p.'/instance/'.$i['id'].'/monitoring', array( 'period' => 'today', 'type' => 'cpu:used' ));
//
//    $lastMax = array_pop($cpuMax['values']);
//    $lastUsed = array_pop($cpuUsed['values']);
//
//    $prevUsed = array_pop($cpuUsed['values']);
//    $diff = ($lastUsed['value'] - $prevUsed['value']);
//    $diffTime = ($lastUsed['timestamp'] - $prevUsed['timestamp']) / 60;
//
//    $pct = ($lastUsed['value'] / $lastMax['value']) * 100;
//    echo '<span class="text-'.($pct >= 50 ? 'danger' : ($pct >= 25 ? 'warning' : 'success')).'" title="'.date('j M H:i', $lastUsed['timestamp']).' : '.round($lastUsed['value'],2).' '.$cpuUsed['unit'].'">';
//      echo round($pct, 1).'%';
//    echo '</span>';
//    echo ' <strong title="'.sprintf('%+.2f', $diff).' '.$cpuUsed['unit'].' ('.$diffTime.' min.)">('.($lastUsed['value'] > $prevUsed['value'] ? '&nearr;' : ($lastUsed['value'] < $prevUsed['value'] ? '&searr;' : '=')).')</strong>';
//  } catch (Exception $e) {
//    //echo '<samp class="small text-danger">'.$e->getMessage().'</samp>';
//  }
?>
            </td>
            <td class="text-nowrap text-xs-right"><?= ($_f['ram'] / 1000) ?> Go</td>
            <td class="text-nowrap">
<?php
//  try {
//    $memMax = $ovh->get('/cloud/project/'.$p.'/instance/'.$i['id'].'/monitoring', array( 'period' => 'today', 'type' => 'mem:max' ));
//    $memUsed = $ovh->get('/cloud/project/'.$p.'/instance/'.$i['id'].'/monitoring', array( 'period' => 'today', 'type' => 'mem:used' ));
//
//    $lastMax = array_pop($memMax['values']);
//    $lastUsed = array_pop($memUsed['values']);
//
//    $prevUsed = array_pop($memUsed['values']);
//    $diff = ($lastUsed['value'] - $prevUsed['value']);
//    $diffTime = ($lastUsed['timestamp'] - $prevUsed['timestamp']) / 60;
//
//    $pct = ($lastUsed['value'] / $lastMax['value']) * 100;
//    echo '<span class="text-'.($pct >= 50 ? 'danger' : ($pct >= 25 ? 'warning' : 'success')).'" title="'.date('j M H:i', $lastUsed['timestamp']).' : '.round($lastUsed['value'],2).' '.$memUsed['unit'].'">';
//      echo round($pct, 1).'%';
//    echo '</span>';
//    echo ' <strong title="'.sprintf('%+.2f', $diff).' '.$memUsed['unit'].' ('.$diffTime.' min.)">('.($lastUsed['value'] > $prevUsed['value'] ? '&nearr;' : ($lastUsed['value'] < $prevUsed['value'] ? '&searr;' : '=')).')</strong>';
//  } catch (Exception $e) {
//    //echo '<samp class="small text-danger">'.$e->getMessage().'</samp>';
//  }
?>
            </td>
          </tr>
<?php
  }
?>
        </tbody>
      </table>
      <table class="table table-bordered table-striped table-sm">
        <thead class="thead-inverse">
          <tr>
            <th>Volume</th>
            <th>Region</th>
            <th>Type</th>
            <th>Size</th>
            <th>Status</th>
            <th>Attached to</th>
          </tr>
        </thead>
        <tbody>
<?php
  $volume = $ovh->get('/cloud/project/'.$p.'/volume');
  foreach ($volume as $v) {
?>
          <tr>
            <th><?= $v['name'] ?><br><small><?= $v['id'] ?></small></th>
            <td><?= $v['region'] ?></td>
            <td><?= $v['type'] ?></td>
            <td class="text-nowrap text-xs-right"><?= $v['size'] ?> Go</td>
            <td><?= $v['status'] ?></td>
            <td>
              <ul style="padding-left:20px;">
<?php
    foreach ($v['attachedTo'] as $a) {
      $i =  $ovh->get('/cloud/project/'.$p.'/instance/'.$a);
      echo '<li>'.$i['name'].'<br><small>'.$i['id'].'</small></li>';
    }
?>
              </ul>
            </td>
          </tr>
<?php
  }
?>
        </tbody>
      </table>
<?php
}
*/
?>
      <div id="console" class="text-danger small">
<?php if (!empty($errors)) { ?>
        <ul>
<?php foreach ($errors as $e) { ?>
          <li><?= $e ?></li>
<?php } ?>
        </ul>
<?php } ?>
        <ol></ol>
      </div>
    </div>

    <div id="modal" class="modal fade">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fa fa-line-chart" aria-hidden="true"></i> Chart <small></small></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <canvas id="chart" height="400" width="400"></canvas>
          </div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js" integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.bundle.js" integrity="sha256-jYMHiFJgIHHSIyPp1uwI5iv5dYgQZIxaQ4RwnpEeEDQ=" crossorigin="anonymous"></script>
    <script src="cloud.js"></script>
  </body>
</html>