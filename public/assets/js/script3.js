window.onload = function() {
  document.getElementById('menutoggle').addEventListener("click", function() {
    document.getElementById('footer').classList.toggle('shown');
  });
  
  updateAPI();

}

function sortTable(n, type) {
  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
  table = document.getElementById("tbenduro");
  switching = true;
  // Set the sorting direction to ascending:
  dir = "asc";
  /* Make a loop that will continue until
  no switching has been done: */
  while (switching) {
    // Start by saying: no switching is done:
    switching = false;
    rows = table.rows;
    /* Loop through all table rows (except the
    first, which contains table headers): */
    for (i = 1; i < (rows.length - 1); i++) {
      // Start by saying there should be no switching:
      shouldSwitch = false;
      /* Get the two elements you want to compare,
      one from current row and one from the next: */
      x = rows[i].getElementsByTagName("TD")[n];
      y = rows[i + 1].getElementsByTagName("TD")[n];
      /* Check if the two rows should switch place,
      based on the direction, asc or desc: */
      if (dir == "asc") {
        if (type == "num") {
          if (Number(x.innerHTML) > Number(y.innerHTML)) {
            // If so, mark as a switch and break the loop:
            shouldSwitch = true;
            break;
          }  
        } else {
          if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
            // If so, mark as a switch and break the loop:
            shouldSwitch = true;
            break;
          }
        }        
      } else if (dir == "desc") {
        if (type == "num") {
          if (Number(x.innerHTML) < Number(y.innerHTML)) {
            // If so, mark as a switch and break the loop:
            shouldSwitch = true;
            break;
          }  
        } else {
          if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
            // If so, mark as a switch and break the loop:
            shouldSwitch = true;
            break;
          }
        }
      }
    }
    if (shouldSwitch) {
      /* If a switch has been marked, make the switch
      and mark that a switch has been done: */
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switching = true;
      // Each time a switch is done, increase this count by 1:
      switchcount ++;
    } else {
      /* If no switching has been done AND the direction is "asc",
      set the direction to "desc" and run the while loop again. */
      if (switchcount == 0 && dir == "asc") {
        dir = "desc";
        switching = true;
      }
    }
  }
}


function updateAPI() {

  $.ajax({
    type: 'POST',
    url: '/uploadObject',    
    success: function(msg) {
      if (msg == 'No Data' || msg == 'Not Started') {
        console.log(msg);
      } else {
        msg = JSON.parse(msg);
        hora = new Date().getTime();
        hora_ini = msg[1];
        tempo_prova = hora - hora_ini;

        ajaxData = JSON.stringify({"key": "****", "raceTime": tempo_prova, truncate: "false", "cars": JSON.parse(msg[0])});
        
        $.ajax({
          type: 'POST',
          url: '****',
          dataType: 'json',
          data: ajaxData,
          success: function(msg) {
            if (msg == 'OK') {
              console.log(msg);
            } else {
              console.log("ERRO API\n");
              console.log(msg);
            }
          },
          error: function(msg) {
            console.log("ERRO API\n");
            console.log(msg);
          }
        });
      }
    },
    error: function(msg) {
      console.log("ERRO BANCO\n");
      console.log(msg);
    
    }    
  });

}

function startEnduro() {
  var hora = {"hora": new Date().getTime()};
  $.ajax({
    type: 'POST',
    url: '/startEnduro',
    data: hora,
    success: function(msg) {
      console.log(msg + '\n');
    },
    error: function(msg) {
      console.log("Erro request startEnduro\n");
      console.log(msg);
    }
  });
}
