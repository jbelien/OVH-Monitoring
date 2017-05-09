var chart = null;
var consoleId = 1;

$(document).ready(function() {
  /*var intervalStatus = window.setInterval(callBackStatus, (15*60)*1000);*/ callBackStatus();
  /*var intervalDisk = window.setInterval(callBackDisk, (5*60)*1000);*/ callBackDisk();
  /*var intervalCPU = window.setInterval(callBackCPU, (5*60)*1000);*/ callBackCPU();
  /*var intervalRAM = window.setInterval(callBackRAM, (5*60)*1000);*/ callBackRAM();

  $("a[href='#disk-chart'], a[href='#cpu-chart'], a[href='#ram-chart']").on("click", function(event) {
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

    $.getJSON("vps-xhr.php", params, function(json) {
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

      $("#modal").modal("show");
    });
  });
});

/* ************************************************************************
 *
 */
function callBackStatus() {
  $.getJSON("vps-xhr.php", {
    "status": 1,
    "time": Date.now()
  }, function(json) {
    $("tr[data-vps]").each(function() {
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
  }, function(json) {
    $("tr[data-vps]").each(function() {
      var name = $(this).data("vps");
      var disks = json[name];

      if (disks.length === 1) {
        if (typeof disks[0] === "object") {
          $(this).find("td:eq(5)").
            html("<span title=\"" + Math.round(disks[0][0]) + " " + disks[0][1] + "\" class=\"" + (disks[0][2] < 50 ? "text-success" : (disks[0][2] < 75 ? "text-warning" : "text-danger")) + "\">" + disks[0][2] + "%</span>")
          } else {
            $(this).find("td:eq(5)").
              html("<span class=\"text-muted\">N/A</span><a href=\"#console-" + consoleId + "\"><sup>" + consoleId + "</sup></a>");

            $("#console > ol").append("<li id=\"console-" + consoleId + "\"><samp>" + disks[0] + "</samp></li>");
            consoleId++;
          }
      } else {

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
  }, function(json) {
    $("tr[data-vps]").each(function() {
      var name = $(this).data("vps");

      if (typeof json[name] === "object") {
        $(this).find("td:eq(8)").
          html("<span title=\"" + Math.round(json[name][0]) + " " + json[name][1] + "\" class=\"" + (json[name][2] < 50 ? "text-success" : (json[name][2] < 75 ? "text-warning" : "text-danger")) + "\">" + json[name][2] + "%</span>");
        if (json[name][3] === -1) {
          $(this).find("td:eq(8)").
            append(" <i class=\"fa fa-angle-double-down fa-fw\" aria-hidden=\"true\"></i>");
        } else if (json[name][3] === 1) {
          $(this).find("td:eq(8)").
            append(" <i class=\"fa fa-angle-double-up fa-fw\" aria-hidden=\"true\"></i>");
        } else {
          $(this).find("td:eq(8)").
            append(" <i class=\"fa fa-circle-thin fa-fw\" aria-hidden=\"true\" style=\"visibility: hidden;\"></i>");
        }
      } else {
        $(this).find("td:eq(8)").
          html("<span class=\"text-muted\">N/A</span><a href=\"#console-" + consoleId + "\"><sup>" + consoleId + "</sup></a>");

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
  }, function(json) {
    $("tr[data-vps]").each(function() {
      var name = $(this).data("vps");

      if (typeof json[name] === "object") {
        $(this).find("td:eq(11)").
          html("<span title=\"" + Math.round(json[name][0]) + " " + json[name][1] + "\" class=\"" + (json[name][2] < 25 ? "text-success" : (json[name][2] < 75 ? "text-warning" : "text-danger")) + "\">" + json[name][2] + "%</span>");
        if (json[name][3] === -1) {
          $(this).find("td:eq(11)").
            append(" <i class=\"fa fa-angle-double-down fa-fw\" aria-hidden=\"true\"></i>");
        } else if (json[name][3] === 1) {
          $(this).find("td:eq(11)").
            append(" <i class=\"fa fa-angle-double-up fa-fw\" aria-hidden=\"true\"></i>");
        } else {
          $(this).find("td:eq(11)").
            append(" <i class=\"fa fa-circle-thin fa-fw\" aria-hidden=\"true\" style=\"visibility: hidden;\"></i>");
        }
      } else {
        $(this).find("td:eq(11)").
          html("<span class=\"text-muted\">N/A</span><a href=\"#console-" + consoleId + "\"><sup>" + consoleId + "</sup></a>");

        $("#console > ol").append("<li id=\"console-" + consoleId + "\"><samp>" + json[name] + "</samp></li>");
        consoleId++;
      }
    });
  });
}
