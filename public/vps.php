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
if (!file_exists($cache) || filemtime($cache) < (time() - 7 * 24 * 60 * 60) || isset($_GET['nocache'])) {
    $json = [];

    $vps = $ovh->get('/vps');
    foreach ($vps as $v) {
        $_v = $ovh->get('/vps/'.$v);

        $_v['infos'] = $ovh->get('/vps/'.$v.'/serviceInfos');

        if (!$_v['state'] === 'maintenance' && $_v['infos']['status'] === 'ok') {
            $_v['distribution'] = $ovh->get('/vps/'.$v.'/distribution');
            $_v['ipAddresses'] = $ovh->get('/vps/'.$v.'/ips');
        }

        $json[] = $_v;
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
    <title>OVH VPS Monitoring</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
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
            <th colspan="2">VPS</th>
            <th class="text-center"><i class="fa fa-bell" aria-hidden="true"></i></th>
            <th>IP</th>
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
    $d1 = new DateTime();
    $d2 = new DateTime($v->infos->expiration);
    $diff = $d1->diff($d2);
    $expiration = ($diff->days <= 30); ?>
          <tr data-vps="<?= $v->name ?>"<?= (in_array($v->infos->status, ['expired', 'unPaid']) ? ' class="table-danger"' : '') ?>>
            <th class="text-nowrap">
<?php if ($expiration === true && $v->infos->renewalType === 'manual') {
        ?>
              <span class="text-warning" title="<?= $diff->format('Expiration in %r%a days') ?>" style="cursor: help;">
<?php
    } ?>
              <?= $v->name ?><br>
              <small><?= $v->displayName ?></small>
<?php if ($expiration === true && $v->infos->renewalType === 'manual') {
        ?>
              </span>
<?php
    } ?>
            </th>
            <td class="text-center"><a href="#modal-info" data-toggle="modal"><i class="fa fa-info-circle" aria-hidden="true"></i></a></td>
            <td class="text-center alert-live"><i class="fa fa fa-spinner fa-pulse fa-fw"></i></td>
            <td>
<?php if (isset($v->ipAddresses)) {
        ?>
              <ul class="list-unstyled mb-0">
<?php foreach ($v->ipAddresses as $ip) {
            ?>
                <li><?= $ip ?></li>
<?php
        } ?>
              </ul>
<?php
    } ?>
            </td>
            <td style="text-nowrap"><?= $v->zone ?></td>
            <td class="text-nowrap"><?= $v->model->offer ?><br><em class="small"><?= $v->model->version ?> - <?= $v->model->name ?></em></td>
            <td style="vertical-align: middle;"><?= (isset($v->distribution) ? $v->distribution->name : '') ?></td>
            <td style="vertical-align: middle;" class="text-nowrap"><?= (isset($v->distribution) ? $v->distribution->bitFormat.' bits' : '') ?></td>
            <td style="vertical-align: middle;" class="text-nowrap text-right"><?= $v->model->disk ?> Go</td>
<?php if (!in_array($v->infos->status, ['expired', 'unPaid'])) {
        ?>
            <td style="vertical-align: middle;" class="text-nowrap text-right disk-live"><i class="fa fa fa-spinner fa-pulse fa-fw"></i></td>
            <td style="vertical-align: middle;" class="text-nowrap text-center"><a href="#disk-chart" style="text-decoration: none;"><i class="fa fa-line-chart" aria-hidden="true"></i></a></td>
<?php
    } else {
        ?>
            <td colspan="2"></td>
<?php
    } ?>
            <td style="vertical-align: middle;" class="text-right"><?= $v->vcore ?></td>
<?php if (!in_array($v->infos->status, ['expired', 'unPaid'])) {
        ?>
            <td style="vertical-align: middle;" class="text-nowrap text-right cpu-live"><i class="fa fa fa-spinner fa-pulse fa-fw"></i></td>
            <td style="vertical-align: middle;" class="text-nowrap text-center"><a href="#cpu-chart" style="text-decoration: none;"><i class="fa fa-line-chart" aria-hidden="true"></i></a></td>
<?php
    } else {
        ?>
            <td colspan="2"></td>
<?php
    } ?>
            <td style="vertical-align: middle;" class="text-nowrap text-right"><?= ($v->memoryLimit / 1024) ?> Go</td>
<?php if (!in_array($v->infos->status, ['expired', 'unPaid'])) {
        ?>
            <td style="vertical-align: middle;" class="text-nowrap text-right ram-live"><i class="fa fa fa-spinner fa-pulse fa-fw"></i></td>
            <td style="vertical-align: middle;" class="text-nowrap text-center"><a href="#ram-chart" style="text-decoration: none;"><i class="fa fa-line-chart" aria-hidden="true"></i></a></td>
            <td style="vertical-align: middle;" class="text-nowrap">
              <span class="badge badge-secondary status-ping">ping</span>
              <span class="badge badge-secondary status-ssh">ssh</span>
              <span class="badge badge-secondary status-dns">dns</span>
              <span class="badge badge-secondary status-http">http</span>
              <span class="badge badge-secondary status-https">https</span>
              <span class="badge badge-secondary status-smtp">smtp</span>
              <span class="badge badge-secondary status-tools">tools</span>
            </td>
<?php
    } else {
        ?>
            <td colspan="2"></td>
            <td></td>
<?php
    } ?>
          </tr>
<?php
}
?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="18" class="text-right small text-muted">
              <?= _('Last update') ?> : <?= date('d.m.Y H:i', filemtime($cache)) ?>
              <a id="refresh" href="vps.php?nocache"><i class="fa fa-refresh" aria-hidden="true"></i> Refresh</a>
            </td>
          </tr>
        </tfoot>
      </table>

      <div id="console" class="text-danger small">
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
    <script src="vps.js"></script>
  </body>
</html>
