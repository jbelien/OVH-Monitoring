/** global: Chart */

var chart = null;
var consoleId = 1;

$(document).ready(function () {
  /*var intervalAlert = window.setInterval(callBackAlert, (5*60)*1000);*/ callBackAlert();
  /*var intervalStatus = window.setInterval(callBackStatus, (15*60)*1000);*/ callBackStatus();
  /*var intervalDisk = window.setInterval(callBackDisk, (5*60)*1000);*/ callBackDisk();
  /*var intervalCPU = window.setInterval(callBackCPU, (5*60)*1000);*/ callBackCPU();
  /*var intervalRAM = window.setInterval(callBackRAM, (5*60)*1000);*/ callBackRAM();

  $("#modal-info").on("show.bs.modal", function (event) {
    var vps = $(event.relatedTarget).closest("tr").data("vps");
    var params = {
      "info": 1,
      "time": Date.now(),
      "vps": vps
    };

    $("#modal-info .modal-title").empty().html("<i class=\"fa fa-info-circle\" aria-hidden=\"true\"></i> " + vps);
    $("#modal-info .modal-body").empty().load("vps-xhr.php", params);
  });

  $("#modal-alert").on("show.bs.modal", function (event) {
    var tr = $(event.relatedTarget).closest("tr")
    var vps = $(tr).data("vps");
    var alerts = $(tr).data("alerts")

    $("#modal-alert .modal-title").empty().html("<i class=\"fa fa-bell\" aria-hidden=\"true\"></i> " + vps);
    $("#modal-alert .modal-body > table > tbody").empty();
    for (var i = 0; i < alerts.length; i++) {
      var row = document.createElement("tr");

      $(row).append("<td>" + new Date(alerts[i].startDate).toString() + "</td>")
      $(row).append("<td>" + alerts[i].reference + "</td>")
      $(row).append("<td><strong>" + alerts[i].title + "</strong><br>" + alerts[i].details + "</td>")
      $(row).append("<td>" + alerts[i].status + (alerts[i].status === "inProgress" ? " (" + alerts[i].progress + "%)" : "") + "</td>")
      $(row).append("<td>" + alerts[i].type + "</td>")
      $(row).append("<td>" + alerts[i].impact + "</td>")

      $("#modal-alert .modal-body > table > tbody").append(row);
    }
  });

  $("a[href='#disk-chart'], a[href='#cpu-chart'], a[href='#ram-chart']").on("click", function (event) {
    event.preventDefault();

    $("body").css("opacity", "0.3");

    var vps = $(this).closest("tr").data("vps");
    var title = vps;

    var params = {
      "time": Date.now(),
      "vps": vps
    };
    if ($(this).is("a[href='#disk-chart']")) {
      params["disk-chart"] = 1;
      title += " (Disk)";
    } else if ($(this).is("a[href='#cpu-chart']")) {
      params["cpu-chart"] = 1;
      title += " (CPU)";
    } else if ($(this).is("a[href='#ram-chart']")) {
      params["ram-chart"] = 1;
      title += " (RAM)";
    }

    $.getJSON("vps-xhr.php", params, function (json) {
      var ctx = $("#chart");

      var data = {
        datasets: [
          {
            backgroundColor: "rgba(255,0,0,0.3)",
            borderColor: "rgba(255,0,0,0.7)",
            label: "Max (" + json[0]["max"]["unit"] + ")",
            data: json[0]["max"]["values"],
            fill: false,
            pointRadius: 0
          },
          {
            backgroundColor: "rgba(0,128,255,0.3)",
            borderColor: "rgba(0,128,255,0.7)",
            label: "Used (" + json[0]["used"]["unit"] + ")",
            data: json[0]["used"]["values"],
            fill: true,
            pointRadius: 0
          }
        ]
      };

      if (chart !== null) { chart.destroy(); }
      chart = new Chart(ctx, {
          data: data,
          options: {
            scales: {
              xAxes: [{
                time: {
                  unit: "day"
                },
                type: "time"
              }],
              yAxes: [{
                ticks: {
                  beginAtZero: true
                }
              }]
            },
            title: {
              display: true,
              text: title
            }
          },
          type: "line"
      });

      $("body").css("opacity", "");

      $("#modal-chart").modal("show");
    });
  });
});

/* ************************************************************************
 *
 */
function callBackAlert() {
  $.getJSON("alert-xhr.php", {
    "vps": 1,
    "time": Date.now()
  }, function (json) {
    $("tr[data-vps]").each(function () {
      var name = $(this).data("vps");
      var td = $(this).find("td.alert-live");

      if (typeof json[name] !== "undefined") {
        var alerts = json[name].alerts || [];
        var status = json[name].status;

        $(this).data("alerts", alerts);

        var badge = null;
        switch (status) {
          case "planned":
            badge = "badge-warning";
            break;
          case "inProgress":
            badge = "badge-danger";
            break;
          case "finished":
            badge = "badge-info";
            break;
          default:
            badge = "badge-default";
            break;
        }

        if (alerts.length > 0) {
          $(td).html("<a href=\"#modal-alert\" data-toggle=\"modal\"><span class=\"badge badge-pill " + badge + "\">" + alerts.length + "</span></a>")
        } else {
          $(td).empty();
        }
      } else {
        $(td).empty();
      }
    });
  });
}

/* ************************************************************************
 *
 */
function callBackStatus() {
  $.getJSON("vps-xhr.php", {
    "status": 1,
    "time": Date.now()
  }, function (json) {
    $("tr[data-vps]").each(function () {
      var name = $(this).data("vps");
      var status = json[name];

      $(this).find(".badge.status-ping").
        removeClass(".badge-default .badge-success .badge-danger").
        addClass((status.ping === "up" ? "badge-success" : "badge-danger"));
      $(this).find(".badge.status-ssh").
        removeClass(".badge-default .badge-success .badge-danger").
        addClass((status.ssh.state === "up" ? "badge-success" : "badge-danger")).
        attr("title", "Port " + status.ssh.port);
      $(this).find(".badge.status-dns").
        removeClass(".badge-default .badge-success .badge-danger").
        addClass((status.dns.state === "up" ? "badge-success" : "badge-danger")).
        attr("title", "Port " + status.dns.port);
      $(this).find(".badge.status-http").
        removeClass(".badge-default .badge-success .badge-danger").
        addClass((status.http.state === "up" ? "badge-success" : "badge-danger")).
        attr("title", "Port " + status.http.port);
      $(this).find(".badge.status-https").
        removeClass(".badge-default .badge-success .badge-danger").
        addClass((status.https.state === "up" ? "badge-success" : "badge-danger")).
        attr("title", "Port " + status.https.port);
      $(this).find(".badge.status-smtp").
        removeClass(".badge-default .badge-success .badge-danger").
        addClass((status.smtp.state === "up" ? "badge-success" : "badge-danger")).
        attr("title", "Port " + status.smtp.port);


      $(this).find(".badge.status-tools").
        removeClass(".badge-default .badge-success .badge-danger").
        addClass((status.tools !== null ? (status.tools === "up" ? "badge-success" : "badge-danger") : "badge-default"));
    });
  });
}

/* ************************************************************************
 *
 */
function callBackDisk() {
  $.getJSON("vps-xhr.php", {
    "disk": 1,
    "time": Date.now()
  }, function (json) {
    $("tr[data-vps]").each(function () {
      var name = $(this).data("vps");
      var disks = json[name];
      var td = $(this).find("td.disk-live");

      if (disks.length === 1) {
        if (typeof disks[0] === "object") {
          $(td).html("<span title=\"" + Math.round(disks[0][0]) + " " + disks[0][1] + "\" class=\"" + (disks[0][2] < 50 ? "text-success" : (disks[0][2] < 75 ? "text-warning" : "text-danger")) + "\">" + disks[0][2] + "%</span>");
        } else {
          $(td).html("<span class=\"text-muted\">N/A</span><a href=\"#console-" + consoleId + "\"><sup>" + consoleId + "</sup></a>");

          $("#console > ol").append("<li id=\"console-" + consoleId + "\"><samp>" + disks[0] + "</samp></li>");
          consoleId++;
        }
      } else {
        // TODO: Handle multiple disks on VPS
      }
    });
  });
}

/* ************************************************************************
 *
 */
function callBackCPU() {
  $.getJSON("vps-xhr.php", {
    "cpu": 1,
    "time": Date.now()
  }, function (json) {
    $("tr[data-vps]").each(function () {
      var name = $(this).data("vps");
      var td = $(this).find("td.cpu-live");

      if (typeof json[name] === "object") {
        $(td).html("<span title=\"" + Math.round(json[name][0]) + " " + json[name][1] + "\" class=\"" + (json[name][2] < 50 ? "text-success" : (json[name][2] < 75 ? "text-warning" : "text-danger")) + "\">" + json[name][2] + "%</span>");
        if (json[name][3] === -1) {
          $(td).append(" <i class=\"fa fa-angle-double-down fa-fw\" aria-hidden=\"true\"></i>");
        } else if (json[name][3] === 1) {
          $(td).append(" <i class=\"fa fa-angle-double-up fa-fw\" aria-hidden=\"true\"></i>");
        } else {
          $(td).append(" <i class=\"fa fa-circle-thin fa-fw\" aria-hidden=\"true\" style=\"visibility: hidden;\"></i>");
        }
      } else {
        $(td).html("<span class=\"text-muted\">N/A</span><a href=\"#console-" + consoleId + "\"><sup>" + consoleId + "</sup></a>");

        $("#console > ol").append("<li id=\"console-" + consoleId + "\"><samp>" + json[name] + "</samp></li>");
        consoleId++;
      }
    });
  });
}

/* ************************************************************************
 *
 */
function callBackRAM() {
  $.getJSON("vps-xhr.php", {
    "ram": 1,
    "time": Date.now()
  }, function (json) {
    $("tr[data-vps]").each(function () {
      var name = $(this).data("vps");
      var td = $(this).find("td.ram-live");

      if (typeof json[name] === "object") {
        $(td).html("<span title=\"" + Math.round(json[name][0]) + " " + json[name][1] + "\" class=\"" + (json[name][2] < 25 ? "text-success" : (json[name][2] < 75 ? "text-warning" : "text-danger")) + "\">" + json[name][2] + "%</span>");
        if (json[name][3] === -1) {
          $(td).append(" <i class=\"fa fa-angle-double-down fa-fw\" aria-hidden=\"true\"></i>");
        } else if (json[name][3] === 1) {
          $(td).append(" <i class=\"fa fa-angle-double-up fa-fw\" aria-hidden=\"true\"></i>");
        } else {
          $(td).append(" <i class=\"fa fa-circle-thin fa-fw\" aria-hidden=\"true\" style=\"visibility: hidden;\"></i>");
        }
      } else {
        $(td).html("<span class=\"text-muted\">N/A</span><a href=\"#console-" + consoleId + "\"><sup>" + consoleId + "</sup></a>");

        $("#console > ol").append("<li id=\"console-" + consoleId + "\"><samp>" + json[name] + "</samp></li>");
        consoleId++;
      }
    });
  });
}
