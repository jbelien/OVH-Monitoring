<?php
require '/var/www/cdn/inc/vendor/autoload.php';

$ini = parse_ini_file('monitoring.ini');
$ovh = new \Ovh\Api( $ini['application_key'], $ini['application_secret'], $ini['endpoint'], $ini['consumer_key'] );
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VPS Monitoring</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.5/css/bootstrap.min.css" integrity="sha384-AysaV+vQoT3kOAXZkl02PThvDr8HYKPZhNT5h/CXfBThSRXQ6jW5DO2ekP5ViFdi" crossorigin="anonymous">
  </head>
  <body>
    <div class="container-fluid">
      <h1>VPS Monitoring</h1>
      <table class="table table-bordered table-striped table-sm">
        <thead class="thead-inverse">
          <tr>
            <th>VPS</th>
            <th>Offer</th>
            <th colspan="2">OS</th>
            <th colspan="2">Disk(s)</th>
            <th colspan="2">vCore(s)</th>
            <th colspan="2">RAM</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
<?php
$vps = $ovh->get('/vps');
foreach ($vps as $v) {
  $_v = $ovh->get('/vps/'.$v);
  $_o = $ovh->get('/vps/'.$v.'/distribution');
  $_s = $ovh->get('/vps/'.$v.'/status');
?>
          <tr data-vps="<?= $v ?>">
            <th class="text-nowrap"><?= $v ?><br><small><?= $_v['displayName'] ?></small></th>
            <td class="text-nowrap"><?= $_v['model']['offer'] ?><br><small>(<?= $_v['model']['version'] ?> - <?= $_v['model']['name'] ?>)</small></td>
            <td><?= $_o['name'] ?></td>
            <td class="text-nowrap"><?= $_o['bitFormat'] ?> bits</td>
            <td class="text-nowrap text-xs-right"><?= $_v['model']['disk'] ?> Go</td>
            <td class="text-nowrap">
              <ul style="padding-left:20px;">
<?php
  $disks = $ovh->get('/vps/'.$v.'/disks');
  foreach ($disks as $i => $d) {
/*
    $_d = $ovh->get('/vps/'.$v.'/disks/'.$d);
    $_u = $ovh->get('/vps/'.$v.'/disks/'.$d.'/use', array( 'type' => 'used' ));

    $pct = ($_u['value'] / ($_d['size'] * 1024) * 100);
    echo '<li>'.$_d['size'].' Go - <span class="text-'.($pct >= 75 ? 'danger' : ($pct >= 50 ? 'warning' : 'success')).'">'.round($pct, 1).'%</span></li>';
*/
    try {
      $diskMax = $ovh->get('/vps/'.$v.'/disks/'.$d.'/monitoring', array( 'period' => 'today', 'type' => 'max' ));
      $diskUsed = $ovh->get('/vps/'.$v.'/disks/'.$d.'/monitoring', array( 'period' => 'today', 'type' => 'used' ));

      $lastMax = array_pop($diskMax['values']);
      $lastUsed = array_pop($diskUsed['values']);

      $prevUsed = array_pop($diskUsed['values']);
      $diff = ($lastUsed['value'] - $prevUsed['value']); if ($diff > 0) { $diff = '+'.$diff; }
      $diffTime = ($lastUsed['timestamp'] - $prevUsed['timestamp']) / 60;

      $pct = ($lastUsed['value'] / $lastMax['value']) * 100;
      echo '<li>';
        echo $lastMax['value'].' '.$diskMax['unit'].' - ';
        echo '<span class="text-'.($pct >= 75 ? 'danger' : ($pct >= 50 ? 'warning' : 'success')).'" title="'.date('j M H:i', $lastUsed['timestamp']).' : '.round($lastUsed['value'],2).' '.$diskUsed['unit'].'">';
          echo round($pct, 1).'%';
        echo '</span>';
        echo ' <strong title="'.sprintf('%+.2f', $diff).' '.$diskUsed['unit'].' ('.$diffTime.' min.)">('.($lastUsed['value'] > $prevUsed['value'] ? '&nearr;' : ($lastUsed['value'] < $prevUsed['value'] ? '&searr;' : '=')).')</strong>';
      echo '</li>';
    } catch (Exception $e) {
      //echo '<samp class="small text-danger">'.$e->getMessage().'</samp>';
    }
  }
?>
              </ul>
            </td>
            <td class="text-xs-right"><?= $_v['vcore'] ?></td>
            <td class="text-nowrap">
<?php
/*
  try {
    $cpuMax = $ovh->get('/vps/'.$v.'/use', array( 'type' => 'cpu:max' ));
    $cpuUsed = $ovh->get('/vps/'.$v.'/use', array( 'type' => 'cpu:used' ));
    $pct = ($cpuUsed['value'] / $cpuMax['value']) * 100;
    echo '<span class="text-'.($pct >= 50 ? 'danger' : ($pct >= 25 ? 'warning' : 'success')).'">'.round($pct, 1).'%</span>';
  } catch (Exception $e) {
    //echo '<samp class="small text-danger">'.$e->getMessage().'</samp>';
  }
*/
  try {
    $cpuMax = $ovh->get('/vps/'.$v.'/monitoring', array( 'period' => 'today', 'type' => 'cpu:max' ));
    $cpuUsed = $ovh->get('/vps/'.$v.'/monitoring', array( 'period' => 'today', 'type' => 'cpu:used' ));

    $lastMax = array_pop($cpuMax['values']); while(is_null($lastMax['value'])) { $lastMax = array_pop($cpuMax['values']); }
    $lastUsed = array_pop($cpuUsed['values']); while(is_null($lastUsed['value'])) { $lastUsed = array_pop($cpuUsed['values']); }

    $prevUsed = array_pop($cpuUsed['values']);
    $diff = ($lastUsed['value'] - $prevUsed['value']); if ($diff > 0) { $diff = '+'.$diff; }
    $diffTime = ($lastUsed['timestamp'] - $prevUsed['timestamp']) / 60;

    $pct = ($lastUsed['value'] / $lastMax['value']) * 100;
    echo '<span class="text-'.($pct >= 50 ? 'danger' : ($pct >= 25 ? 'warning' : 'success')).'" title="'.date('j M H:i', $lastUsed['timestamp']).' : '.round($lastUsed['value'],2).' '.$cpuUsed['unit'].'">';
      echo round($pct, 1).'%';
    echo '</span>';
    echo ' <strong title="'.sprintf('%+.2f', $diff).' '.$cpuUsed['unit'].' ('.$diffTime.' min.)">('.($lastUsed['value'] > $prevUsed['value'] ? '&nearr;' : ($lastUsed['value'] < $prevUsed['value'] ? '&searr;' : '=')).')</strong>';
  } catch (Exception $e) {
    //echo '<samp class="small text-danger">'.$e->getMessage().'</samp>';
  }
?>
            </td>
            <td class="text-nowrap text-xs-right"><?= ($_v['memoryLimit'] / 1024) ?> Go</td>
            <td class="text-nowrap">
<?php
/*
  try {
    $memMax = $ovh->get('/vps/'.$v.'/use', array( 'type' => 'mem:max' ));
    $memUsed = $ovh->get('/vps/'.$v.'/use', array( 'type' => 'mem:used' ));
    $pct = ($memUsed['value'] / $memMax['value']) * 100;
    echo '<span class="text-'.($pct >= 50 ? 'danger' : ($pct >= 25 ? 'warning' : 'success')).'">'.round($pct, 1).'%</span>';
  } catch (Exception $e) {
    //echo '<samp class="small text-danger">'.$e->getMessage().'</samp>';
  }
*/
  try {
    $memMax = $ovh->get('/vps/'.$v.'/monitoring', array( 'period' => 'today', 'type' => 'mem:max' ));
    $memUsed = $ovh->get('/vps/'.$v.'/monitoring', array( 'period' => 'today', 'type' => 'mem:used' ));

    $lastMax = array_pop($memMax['values']); while(is_null($lastMax['value'])) { $lastMax = array_pop($memMax['values']); }
    $lastUsed = array_pop($memUsed['values']); while(is_null($lastUsed['value'])) { $lastUsed = array_pop($memUsed['values']); }

    $prevUsed = array_pop($memUsed['values']);
    $diff = ($lastUsed['value'] - $prevUsed['value']); if ($diff > 0) { $diff = '+'.$diff; }
    $diffTime = ($lastUsed['timestamp'] - $prevUsed['timestamp']) / 60;

    $pct = ($lastUsed['value'] / $lastMax['value']) * 100;
    echo '<span class="text-'.($pct >= 50 ? 'danger' : ($pct >= 25 ? 'warning' : 'success')).'" title="'.date('j M H:i', $lastUsed['timestamp']).' : '.round($lastUsed['value'],2).' '.$memUsed['unit'].'">';
      echo round($pct, 1).'%';
    echo '</span>';
    echo ' <strong title="'.sprintf('%+.2f', $diff).' '.$memUsed['unit'].' ('.$diffTime.' min.)">('.($lastUsed['value'] > $prevUsed['value'] ? '&nearr;' : ($lastUsed['value'] < $prevUsed['value'] ? '&searr;' : '=')).')</strong>';
  } catch (Exception $e) {
    //echo '<samp class="small text-danger">'.$e->getMessage().'</samp>';
  }
?>
            </td>
            <td class="text-nowrap">
              <span class="tag <?= ($_s['ping'] === 'up' ? 'tag-success' : ($_s['ping'] === 'down' ? 'tag-danger' : 'tag-default')) ?>">ping</span>
              <span class="tag <?= ($_s['ssh']['state'] === 'up' ? 'tag-success' : ($_s['ssh']['state'] === 'down' ? 'tag-danger' : 'tag-default')) ?>" title="Port <?= $_s['ssh']['port'] ?>">ssh</span>
              <span class="tag <?= ($_s['dns']['state'] === 'up' ? 'tag-success' : ($_s['dns']['state'] === 'down' ? 'tag-danger' : 'tag-default')) ?>" title="Port <?= $_s['dns']['port'] ?>">dns</span>
              <span class="tag <?= ($_s['http']['state'] === 'up' ? 'tag-success' : ($_s['http']['state'] === 'down' ? 'tag-danger' : 'tag-default')) ?>" title="Port <?= $_s['http']['port'] ?>">http</span>
              <span class="tag <?= ($_s['https']['state'] === 'up' ? 'tag-success' : ($_s['https']['state'] === 'down' ? 'tag-danger' : 'tag-default')) ?>" title="Port <?= $_s['https']['port'] ?>">https</span>
              <span class="tag <?= ($_s['smtp']['state'] === 'up' ? 'tag-success' : ($_s['smtp']['state'] === 'down' ? 'tag-danger' : 'tag-default')) ?>" title="Port <?= $_s['smtp']['port'] ?>">smtp</span>
              <span class="tag <?= ($_s['tools'] === 'up' ? 'tag-success' : ($_s['tools'] === 'down' ? 'tag-danger' : 'tag-default')) ?>">tools</span>
            </td>
          </tr>
<?php
}
?>
        </tbody>
      </table>
<!--
      <hr>
      <div class="row">
        <div class="col-sm-4">
          <canvas id="chart-disks" style="width: 100%; height: 250px;"></canvas>
        </div>
        <div class="col-sm-4">
          <canvas id="chart-cpu" style="width: 100%; height: 250px;"></canvas>
        </div>
        <div class="col-sm-4">
          <canvas id="chart-mem" style="width: 100%; height: 250px;"></canvas>
        </div>
      </div>
-->
      <h1>Cloud Monitoring</h1>
<?php
$project = $ovh->get('/cloud/project');
foreach ($project as $p) {
  $_p =  $ovh->get('/cloud/project/'.$p);
?>
      <h2>Project &laquo; <?= $_p['description'] ?> &raquo;</h2>
      <table class="table table-bordered table-striped table-sm">
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
  try {
    $cpuMax = $ovh->get('/cloud/project/'.$p.'/instance/'.$i['id'].'/monitoring', array( 'period' => 'today', 'type' => 'cpu:max' ));
    $cpuUsed = $ovh->get('/cloud/project/'.$p.'/instance/'.$i['id'].'/monitoring', array( 'period' => 'today', 'type' => 'cpu:used' ));

    $lastMax = array_pop($cpuMax['values']);
    $lastUsed = array_pop($cpuUsed['values']);

    $prevUsed = array_pop($cpuUsed['values']);
    $diff = ($lastUsed['value'] - $prevUsed['value']); if ($diff > 0) { $diff = '+'.$diff; }
    $diffTime = ($lastUsed['timestamp'] - $prevUsed['timestamp']) / 60;

    $pct = ($lastUsed['value'] / $lastMax['value']) * 100;
    echo '<span class="text-'.($pct >= 50 ? 'danger' : ($pct >= 25 ? 'warning' : 'success')).'" title="'.date('j M H:i', $lastUsed['timestamp']).' : '.round($lastUsed['value'],2).' '.$cpuUsed['unit'].'">';
      echo round($pct, 1).'%';
    echo '</span>';
    echo ' <strong title="'.sprintf('%+.2f', $diff).' '.$cpuUsed['unit'].' ('.$diffTime.' min.)">('.($lastUsed['value'] > $prevUsed['value'] ? '&nearr;' : ($lastUsed['value'] < $prevUsed['value'] ? '&searr;' : '=')).')</strong>';
  } catch (Exception $e) {
    //echo '<samp class="small text-danger">'.$e->getMessage().'</samp>';
  }
?>
            </td>
            <td class="text-nowrap text-xs-right"><?= ($_f['ram'] / 1000) ?> Go</td>
            <td class="text-nowrap">
<?php
  try {
    $memMax = $ovh->get('/cloud/project/'.$p.'/instance/'.$i['id'].'/monitoring', array( 'period' => 'today', 'type' => 'mem:max' ));
    $memUsed = $ovh->get('/cloud/project/'.$p.'/instance/'.$i['id'].'/monitoring', array( 'period' => 'today', 'type' => 'mem:used' ));

    $lastMax = array_pop($memMax['values']);
    $lastUsed = array_pop($memUsed['values']);

    $prevUsed = array_pop($memUsed['values']);
    $diff = ($lastUsed['value'] - $prevUsed['value']); if ($diff > 0) { $diff = '+'.$diff; }
    $diffTime = ($lastUsed['timestamp'] - $prevUsed['timestamp']) / 60;

    $pct = ($lastUsed['value'] / $lastMax['value']) * 100;
    echo '<span class="text-'.($pct >= 50 ? 'danger' : ($pct >= 25 ? 'warning' : 'success')).'" title="'.date('j M H:i', $lastUsed['timestamp']).' : '.round($lastUsed['value'],2).' '.$memUsed['unit'].'">';
      echo round($pct, 1).'%';
    echo '</span>';
    echo ' <strong title="'.sprintf('%+.2f', $diff).' '.$memUsed['unit'].' ('.$diffTime.' min.)">('.($lastUsed['value'] > $prevUsed['value'] ? '&nearr;' : ($lastUsed['value'] < $prevUsed['value'] ? '&searr;' : '=')).')</strong>';
  } catch (Exception $e) {
    //echo '<samp class="small text-danger">'.$e->getMessage().'</samp>';
  }
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
?>
    </div>

    <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
    <script src="/cdn/js/chart.js/2.4/Chart.bundle.min.js"></script>
    <script>
/*
      $(document).ready(function() {
        var ctx = document.getElementById("chart-disks");
        var myChart = new Chart(ctx, {
          type: 'line',
          data: {
            datasets: [
<?php
//foreach ($vps as $v) {
//  $disks = $ovh->get('/vps/'.$v.'/disks');
//  foreach ($disks as $i => $d) {
//    $_d = $ovh->get('/vps/'.$v.'/disks/'.$d);
//    $__d = $ovh->get('/vps/'.$v.'/disks/'.$d.'/monitoring', array( 'period' => 'lastday', 'type' => 'used' ));
//    echo '              ';
//    echo '{';
//      echo 'label:"'.$v.' #'.($i+1).'",';
//      echo 'fill:false,';
//      echo 'steppedLine:true,';
//      echo 'data:[';
//      foreach ($__d['values'] as $j => $val) {
//        if ($j > 0) { echo ','; }
//        echo '{';
//          echo 'x:new Date('.($val['timestamp']*1000).'),';
//          echo 'y:'.($val['value'] / ($_d['size'] * 1024) * 100);
//        echo '}';
//      }
//      echo ']';
//    echo '},'.PHP_EOL;
//  }
//}
?>
            ]
          },
          options: {
            responsive: true,
            title: {
              display: true,
              text: "Disks usage"
            },
            legend: {
              display: false
            },
            elements: {
              point: {
                radius: 0
              }
            },
            scales: {
              xAxes: [{
                type: "time",
              }],
              yAxes: [{
                ticks: {
                  beginAtZero:true
                }
              }]
            }
          }
        });
      });
*/
    </script>
  </body>
</html>