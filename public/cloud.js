var chart = null;
var consoleId = 1;

$(document).ready(function() {
  /*var intervalCPU = window.setInterval(callBackCPU, (5*60)*1000);*/ callBackCPU();
  /*var intervalRAM = window.setInterval(callBackRAM, (5*60)*1000);*/ callBackRAM();

  $("a[href='#disk-chart'], a[href='#cpu-chart'], a[href='#ram-chart']").on("click", function(event) {
    event.preventDefault();

    $("body").css("opacity", "0.3");

    var project = $(this).closest("tr").data("project");
    var instance = $(this).closest("tr").data("instance");
    var title = instance;

    var params = {
      "instance": instance,
      "project": project,
      "time": Date.now()
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

    $.getJSON("cloud-xhr.php", params, function(json) {
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
function callBackCPU() {
  $.getJSON("cloud-xhr.php", {
    "cpu": 1,
    "time": Date.now()
  }, function(json) {
    $("tr[data-instance]").each(function() {
      var project = $(this).data("project");
      var instance = $(this).data("instance");

      if (typeof json[project][instance] === "object") {
        $(this).find("td:eq(5)").
          html("<span title=\"" + Math.round(json[project][instance][0]) + " " + json[project][instance][1] + "\" class=\"" + (json[project][instance][2] < 50 ? "text-success" : (json[project][instance][2] < 75 ? "text-warning" : "text-danger")) + "\">" + json[project][instance][2] + "%</span>");
        if (json[project][instance][3] === -1) {
          $(this).find("td:eq(5)").
            append(" <i class=\"fa fa-angle-double-down fa-fw\" aria-hidden=\"true\"></i>");
        } else if (json[project][instance][3] === 1) {
          $(this).find("td:eq(5)").
            append(" <i class=\"fa fa-angle-double-up fa-fw\" aria-hidden=\"true\"></i>");
        } else {
          $(this).find("td:eq(5)").
            append(" <i class=\"fa fa-circle-thin fa-fw\" aria-hidden=\"true\" style=\"visibility: hidden;\"></i>");
        }
      } else {
        $(this).find("td:eq(5)").
          html("<span class=\"text-muted\">N/A</span><a href=\"#console-" + consoleId + "\"><sup>" + consoleId + "</sup></a>");

        $("#console > ol").append("<li id=\"console-" + consoleId + "\"><samp>" + json[project][instance] + "</samp></li>");
        consoleId++;
      }
    });
  });
}

/* ************************************************************************
 *
 */
function callBackRAM() {
  $.getJSON("cloud-xhr.php", {
    "ram": 1,
    "time": Date.now()
  }, function(json) {
    $("tr[data-instance]").each(function() {
      var project = $(this).data("project");
      var instance = $(this).data("instance");

      if (typeof json[project][instance] === "object") {
        $(this).find("td:eq(8)").
          html("<span title=\"" + Math.round(json[project][instance][0]) + " " + json[project][instance][1] + "\" class=\"" + (json[project][instance][2] < 50 ? "text-success" : (json[project][instance][2] < 75 ? "text-warning" : "text-danger")) + "\">" + json[project][instance][2] + "%</span>");
        if (json[project][instance][3] === -1) {
          $(this).find("td:eq(8)").
            append(" <i class=\"fa fa-angle-double-down fa-fw\" aria-hidden=\"true\"></i>");
        } else if (json[project][instance][3] === 1) {
          $(this).find("td:eq(8)").
            append(" <i class=\"fa fa-angle-double-up fa-fw\" aria-hidden=\"true\"></i>");
        } else {
          $(this).find("td:eq(8)").
            append(" <i class=\"fa fa-circle-thin fa-fw\" aria-hidden=\"true\" style=\"visibility: hidden;\"></i>");
        }
      } else {
        $(this).find("td:eq(8)").
          html("<span class=\"text-muted\">N/A</span><a href=\"#console-" + consoleId + "\"><sup>" + consoleId + "</sup></a>");

        $("#console > ol").append("<li id=\"console-" + consoleId + "\"><samp>" + json[project][instance] + "</samp></li>");
        consoleId++;
      }
    });
  });
}
