<?php
require '../vendor/autoload.php';

$ini = parse_ini_file('../monitoring.ini');
$ovh = new \Ovh\Api( $ini['application_key'], $ini['application_secret'], $ini['endpoint'], $ini['consumer_key'] );

$cache = '../cache/vps.json';
if (!file_exists($cache) || filemtime($cache) < (time() - 7*24*60*60) || isset($_GET['nocache'])) {
  $json = array();

  $vps = $ovh->get('/vps');
  foreach ($vps as $v) {
    $_v = $ovh->get('/vps/'.$v);
    $_v['distribution'] = $ovh->get('/vps/'.$v.'/distribution');

    $json[] = $_v;
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
    <title>OVH VPS Monitoring</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://opensource.keycdn.com/fontawesome/4.7.0/font-awesome.min.css" integrity="sha384-dNpIIXE8U05kAbPhy3G1cz+yZmTzA6CY8Vg/u2L9xRnHjJiAK76m2BIEaSEV+/aU" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <div class="container-fluid">
      <h1 class="mt-3">OVH Monitoring</h1>
      <ul class="nav nav-tabs mt-3">
        <li class="nav-item">
          <a class="nav-link active" href="vps.php">VPS</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="cloud.php">Cloud</a>
        </li>
      </ul>
      <table class="table table-bordered table-striped table-sm mt-3">
        <thead class="thead-inverse">
          <tr>
            <th>
              VPS
              <a id="refresh" href="vps.php?nocache" class="float-right"><i class="fa fa-refresh" aria-hidden="true"></i> Refresh</a>
            </th>
            <th>Zone</th>
            <th>Offer</th>
            <th colspan="2">OS</th>
            <th colspan="3">Disk(s)</th>
            <th colspan="3">vCore(s)</th>
            <th colspan="3">RAM</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
<?php
$vps = json_decode(file_get_contents($cache));
foreach ($vps as $v) {
?>
          <tr data-vps="<?= $v->name ?>">
            <th class="text-nowrap"><?= $v->name ?><br><small><?= $v->displayName ?></small></th>
            <td style="text-nowrap"><?= $v->zone ?></td>
            <td class="text-nowrap"><?= $v->model->offer ?><br><em class="small"><?= $v->model->version ?> - <?= $v->model->name ?></em></td>
            <td style="vertical-align: middle;"><?= $v->distribution->name ?></td>
            <td style="vertical-align: middle;" class="text-nowrap"><?= $v->distribution->bitFormat ?> bits</td>
            <td style="vertical-align: middle;" class="text-nowrap text-right"><?= $v->model->disk ?> Go</td>
            <td style="vertical-align: middle;" class="text-nowrap text-right"><i class="fa fa fa-spinner fa-pulse fa-fw"></i></td>
            <td style="vertical-align: middle;" class="text-nowrap text-center"><a href="#disk-chart" style="text-decoration: none;"><i class="fa fa-line-chart" aria-hidden="true"></i></a></td>
            <td style="vertical-align: middle;" class="text-right"><?= $v->vcore ?></td>
            <td style="vertical-align: middle;" class="text-nowrap text-right"><i class="fa fa fa-spinner fa-pulse fa-fw"></i></td>
            <td style="vertical-align: middle;" class="text-nowrap text-center"><a href="#cpu-chart" style="text-decoration: none;"><i class="fa fa-line-chart" aria-hidden="true"></i></a></td>
            <td style="vertical-align: middle;" class="text-nowrap text-right"><?= ($v->memoryLimit / 1024) ?> Go</td>
            <td style="vertical-align: middle;" class="text-nowrap text-right"><i class="fa fa fa-spinner fa-pulse fa-fw"></i></td>
            <td style="vertical-align: middle;" class="text-nowrap text-center"><a href="#ram-chart" style="text-decoration: none;"><i class="fa fa-line-chart" aria-hidden="true"></i></a></td>
            <td style="vertical-align: middle;" class="text-nowrap">
              <span class="badge badge-default status-ping">ping</span>
              <span class="badge badge-default status-ssh">ssh</span>
              <span class="badge badge-default status-dns">dns</span>
              <span class="badge badge-default status-http">http</span>
              <span class="badge badge-default status-https">https</span>
              <span class="badge badge-default status-smtp">smtp</span>
              <span class="badge badge-default status-tools">tools</span>
            </td>
          </tr>
<?php
}
?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="15" class="text-right small text-muted"><?= _('Last update') ?> : <?= date('d.m.Y H:i', filemtime($cache)) ?></td>
          </tr>
        </tfoot>
      </table>

      <div id="console" class="text-danger small">
        <ol></ol>
      </div>
    </div>

    <div id="modal" class="modal fade">
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

    <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js" integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.bundle.js" integrity="sha256-jYMHiFJgIHHSIyPp1uwI5iv5dYgQZIxaQ4RwnpEeEDQ=" crossorigin="anonymous"></script>
    <script src="vps.js"></script>
  </body>
</html>