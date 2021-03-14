<?php
require '../vendor/autoload.php';

$ini = parse_ini_file('../monitoring.ini');
$timeout = $ini['timeout'] ?? 0;
$client = new \GuzzleHttp\Client([
'connect_timeout' => $timeout,
'read_timeout' => $timeout,
'timeout' => $timeout,
]);
$ovh = new \Ovh\Api($ini['application_key'], $ini['application_secret'], $ini['endpoint'], $ini['consumer_key'], $client);

$cache = '../cache/cloud.json';
if (!file_exists($cache) || filemtime($cache) < (time() - 7 * 24 * 60 * 60) || isset($_GET['nocache'])) {
    $json = [];
    $errors = [];

    $project = $ovh->get('/cloud/project');
    foreach ($project as $p) {
        $current = $ovh->get('/cloud/project/'.$p);

        $instances = [];
        $instance = $ovh->get('/cloud/project/'.$p.'/instance');
        foreach ($instance as $i) {
            try {
                $i['flavor'] = $ovh->get('/cloud/project/'.$p.'/flavor/'.$i['flavorId']);
                $i['image'] = $ovh->get('/cloud/project/'.$p.'/image/'.$i['imageId']);
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }

            $instances[] = $i;
        }

        $current['instances'] = $instances;

        $volumes = [];
        $volume = $ovh->get('/cloud/project/'.$p.'/volume');
        foreach ($volume as $v) {
            $volumes[] = $v;
        }

        $current['volumes'] = $volumes;

        $json[] = $current;
    }

    if (!file_exists('../cache') || !is_dir('../cache')) {
        mkdir('../cache');
    }
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
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
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
      <table class="table table-bordered table-striped table-sm mt-3">
        <thead class="thead-inverse">
          <tr>
            <th colspan="14">Instance</th>
            <th colspan="5">Volume(s)</th>
          </tr>
          <tr>
            <th colspan="2"></th>
            <th class="text-center"><i class="fa fa-bell" aria-hidden="true"></i></th>
            <th>IP</th>
            <th>Region</th>
            <th>Flavor</th>
            <th>Image</th>
            <th>Disk</th>
            <th colspan="3">vCPU(s)</th>
            <th colspan="3">RAM</th>
            <th></th>
            <th>Region</th>
            <th>Type</th>
            <th>Size</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
<?php foreach ($p->instances as $index => $i) {
        ?>
<?php
  $instanceVolumes = [];
        foreach ($p->volumes as $v) {
            if (in_array($i->id, $v->attachedTo)) {
                $instanceVolumes[] = $v;
            }
        } ?>
          <tr data-project="<?= $p->project_id ?>" data-instance="<?= $i->id ?>" class="<?= ($index % 2 === 0 ? 'even' : 'odd') ?>">
            <th <?= (count($instanceVolumes) > 1 ? 'rowspan="'.count($instanceVolumes).'"' : '') ?>>
              <?= $i->name ?><br>
              <small><?= $i->id ?></small>
            </th>
            <td <?= (count($instanceVolumes) > 1 ? 'rowspan="'.count($instanceVolumes).'"' : '') ?>><a href="#modal-info" data-toggle="modal"><i class="fa fa-info-circle" aria-hidden="true"></i></a></td>
            <td <?= (count($instanceVolumes) > 1 ? 'rowspan="'.count($instanceVolumes).'"' : '') ?> class="text-center alert-live"><i class="fa fa fa-spinner fa-pulse fa-fw"></i></td>
            <td <?= (count($instanceVolumes) > 1 ? 'rowspan="'.count($instanceVolumes).'"' : '') ?>>
              <ul class="list-unstyled mb-0">
<?php foreach ($i->ipAddresses as $ip) {
            ?>
                <li><?= $ip->ip ?></li>
<?php
        } ?>
              </ul>
            </td>
            <td <?= (count($instanceVolumes) > 1 ? 'rowspan="'.count($instanceVolumes).'"' : '') ?>><?= $i->region ?></td>
            <td <?= (count($instanceVolumes) > 1 ? 'rowspan="'.count($instanceVolumes).'"' : '') ?>><?= $i->flavor->type ?> - <?= $i->flavor->name ?> (<?= $i->flavor->osType ?>)</td>
            <td <?= (count($instanceVolumes) > 1 ? 'rowspan="'.count($instanceVolumes).'"' : '') ?>><?= (isset($i->image) ? $i->image->name : '<span class="text-muted">N/A</span>') ?></td>
            <td <?= (count($instanceVolumes) > 1 ? 'rowspan="'.count($instanceVolumes).'"' : '') ?> class="text-nowrap text-right"><?= $i->flavor->disk ?> Go</td>
            <td <?= (count($instanceVolumes) > 1 ? 'rowspan="'.count($instanceVolumes).'"' : '') ?> class="text-right"><?= $i->flavor->vcpus ?></td>
            <td <?= (count($instanceVolumes) > 1 ? 'rowspan="'.count($instanceVolumes).'"' : '') ?> class="text-nowrap text-right cpu-live"><i class="fa fa fa-spinner fa-pulse fa-fw"></i></td>
            <td <?= (count($instanceVolumes) > 1 ? 'rowspan="'.count($instanceVolumes).'"' : '') ?> class="text-nowrap text-center"><a href="#cpu-chart" style="text-decoration: none;"><i class="fa fa-line-chart" aria-hidden="true"></i></a></td>
            <td <?= (count($instanceVolumes) > 1 ? 'rowspan="'.count($instanceVolumes).'"' : '') ?> class="text-nowrap text-right"><?= ($i->flavor->ram / 1000) ?> Go</td>
            <td <?= (count($instanceVolumes) > 1 ? 'rowspan="'.count($instanceVolumes).'"' : '') ?> class="text-nowrap text-right ram-live"><i class="fa fa fa-spinner fa-pulse fa-fw"></i></td>
            <td <?= (count($instanceVolumes) > 1 ? 'rowspan="'.count($instanceVolumes).'"' : '') ?> class="text-nowrap text-center"><a href="#ram-chart" style="text-decoration: none;"><i class="fa fa-line-chart" aria-hidden="true"></i></a></td>
<?php if (count($instanceVolumes) === 0) {
            ?>
            <td colspan="5"></td>
<?php
        } else {
            ?>
            <th><?= $instanceVolumes[0]->name ?><br><small><?= $instanceVolumes[0]->id ?></small></th>
            <td style="vertical-align: middle;"><?= $instanceVolumes[0]->region ?></td>
            <td style="vertical-align: middle;"><?= $instanceVolumes[0]->type ?></td>
            <td style="vertical-align: middle;" class="text-nowrap text-right"><?= $instanceVolumes[0]->size ?> Go</td>
            <td style="vertical-align: middle;"><?= $instanceVolumes[0]->status ?></td>
<?php
        } ?>
          </tr>
<?php
  if (count($instanceVolumes) > 1) {
      for ($k = 1; $k < count($instanceVolumes); $k++) {
          ?>
          <tr class="<?= ($index % 2 === 0 ? 'even' : 'odd') ?>">
            <th><?= $instanceVolumes[$k]->name ?><br><small><?= $instanceVolumes[$k]->id ?></small></th>
            <td style="vertical-align: middle;"><?= $instanceVolumes[$k]->region ?></td>
            <td style="vertical-align: middle;"><?= $instanceVolumes[$k]->type ?></td>
            <td style="vertical-align: middle;" class="text-nowrap text-right"><?= $instanceVolumes[$k]->size ?> Go</td>
            <td style="vertical-align: middle;"><?= $instanceVolumes[$k]->status ?></td>
          </tr>
<?php
      }
  } ?>
<?php
    } ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="19" class="text-right small text-muted">
              <?= _('Last update') ?> : <?= date('d.m.Y H:i', filemtime($cache)) ?>
              <a id="refresh" href="cloud.php?nocache"><i class="fa fa-refresh" aria-hidden="true"></i> Refresh</a>
            </td>
          </tr>
        </tfoot>
      </table>
<?php
}
?>
      <div id="console" class="text-danger small">
<?php if (!empty($errors)) {
    ?>
        <ul>
<?php foreach ($errors as $e) {
        ?>
          <li><?= $e ?></li>
<?php
    } ?>
        </ul>
<?php
} ?>
        <ol></ol>
      </div>
    </div>

    <div id="modal-alert" class="modal fade">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <table class="table table-striped table-sm small">
              <thead>
                <tr>
                  <th>Time</th>
                  <th>Reference</th>
                  <th>Description</th>
                  <th>Status</th>
                  <th>Type</th>
                  <th>Impact</th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div id="modal-chart" class="modal fade">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-body">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
            <canvas id="chart" width="468" height="400"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div id="modal-info" class="modal fade">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body"></div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.bundle.js" integrity="sha256-jYMHiFJgIHHSIyPp1uwI5iv5dYgQZIxaQ4RwnpEeEDQ=" crossorigin="anonymous"></script>
    <script src="cloud.js"></script>
  </body>
</html>
