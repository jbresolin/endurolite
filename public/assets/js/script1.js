var db;
var store;
var rawTrans;
var rawStore;
var visible = new Array();
var displayed = new Array();
//timeDelta é servidor-cliente, somar à hora do cliente no envio
var timeDelta = 0;
var servPreviousStatus = 1;
var ready = false;

function addPass(form) {  
  formData = document.getElementById('pass_data');
  var ptime = new Date().getTime();
  var td = timeDelta;
  ptime += Math.floor(td);
  if (formData.value == '') {
    return;    
  } else {
    rawTrans = db.transaction(["passRaw"], "readwrite");
    rawStore = rawTrans.objectStore("passRaw");
    var newPass = {pass_time: ptime, pass_data: formData.value, time_offset: Number(td), synced: 0};
    var request = rawStore.add(newPass);
    request.onsuccess = function(event) {
      sendPass(newPass).then().catch();
      visible.unshift(newPass);
      if (visible.length >= 10) {
        visible.splice(-1,1);        
      }
      updateDisplay();
    }
    request.onerror = function(event) {
      console.log("Error on: addPass->Add\n");
      console.log(event);
    }
  }
  formData.value = '';
  formData.focus();
}

function sendPass(pass) {
  return new Promise((resolve, reject) => {
    let ajaxData = {pass_data: pass["pass_data"], pass_time: pass["pass_time"], time_offset: pass["time_offset"]};
    var a = ($.ajax({
      type: 'POST',
      url: '/addPass',
      data: ajaxData}));
    a.then(
      function(msg) {
        serverStatus(1);
        if (msg[0]["result"] == "error") {
          () => reject(msg);
          console.log(msg);
        } else {
          msg = JSON.parse(msg);
          rawTrans = db.transaction(["passRaw"], "readwrite");
          rawStore = rawTrans.objectStore("passRaw");
          var putData = {pass_time: 1*msg["pass_time"], pass_data: msg["pass_data"], time_offset: Number(msg['time_offset']), synced: 1};
          var request = rawStore.put(putData);
          request.onsuccess = function(event) {          
            var visIndex = visible.map(function(e) { return e.pass_time }).indexOf(putData.pass_time);
            if (visIndex != -1) {            
              visible.splice(visIndex, 1, putData);
              updateDisplay();
            }
            resolve(true);
          },
          request.onerror = function(event) {
            console.log("Error on: SendPass->Ajax->Put\n");
            console.log(event);
            reject(event);
          }
        }
      }
    );
    a.catch(
      function(msg) {
        serverStatus(0);
        console.log("Error on: SendPass->Ajax\n");
        console.log(msg);
        reject(msg);        
      }
    );
  });   
}

function sendUnsynced(cback = null) {
  getNext();
  function getNext() {
    rawTrans = db.transaction(["passRaw"], "readonly");
    rawStore = rawTrans.objectStore("passRaw");
    var cursorRequest = rawStore.index("synced").openCursor(0);
    cursorRequest.onsuccess = function(event) {
      var cursor = event.target.result;    
      if (cursor) {
        sendPass(cursor.value).then(_=> {getNext();}).catch(_=> {if (cback != null) { window[cback]; } else { return false; }});
      }
      if (cback != null) { window[cback]; }
    }
    cursorRequest.onerror = function(event) {
      console.log("Error on: sendUnsynced->cursor\n");
      console.log(event);
      if (cback != null) { window[cback]; }
    }
  }
  updateDisplay();  
}

//REWRITE START

//FUNÇÕES GERAIS

function serverWait(func) {
  window.setTimeout(func, 2000);
}

function requirementError(errCode) {
  var errorBox = document.getElementById('erros');
  var inputBox = document.getElementById('input');
  switch(errCode) {
    case 0:
      errorBox.innerHTML = "Sem erros.";
      errorBox.classList.add('hidden');
      inputBox.classList.remove('hidden');
      document.getElementById('footer').classList.remove('shown');
      break;
    case 1:
      errorBox.innerHTML = "Navegador não suporta bancos de dados locais. Utilização não será possível. Procure outro dispositivo/navegador com suporte à API IndexedDB.";
      errorBox.classList.remove('hidden');
      inputBox.classList.add('hidden');
      break;
    case 2:
      errorBox.innerHTML = "Navegador não suporta dados locais. Utilização não será possível. Procure outro dispositivo/navegador com suporte à API localStorage.";
      errorBox.classList.remove('hidden');
      inputBox.classList.add('hidden');
      break;
    case 31:
      errorBox.innerHTML = "Seu navegador tem dados salvos relativos a outro usuário ou evento, faça login com o usuário <i>"+store.getItem('username')+"</i> e selecione o evento <i>"+store.getItem('evento_id')+"</i> para enviar os dados pendentes ou consulte suporte técnico.";
      errorBox.classList.remove('hidden');
      inputBox.classList.add('hidden');
      break;
    case 32:
      errorBox.innerHTML = "Não foi possível ler os dados armazenados no navegador. Consulte suporte técnico.";
      errorBox.classList.remove('hidden');
      inputBox.classList.add('hidden');
      break;
    case 4:
      errorBox.innerHTML = "Não foi possível gravar os dados do servidor. Consulte suporte técnico";
      errorBox.classList.remove('hidden');
      inputBox.classList.add('hidden');
      break;
    case 6:
      errorBox.innerHTML = "Inserção de dados não habilitada, verificando novamente em 15&nbsp;s.";
      errorBox.classList.remove('hidden');
      inputBox.classList.add('hidden');
      break;
  }
}

function checkListUpdate(item, status) {
  var chkItem = document.getElementById('chk'+item);
  if (status == 0 ) {
    chkItem.classList.remove('fa-spinner');
    chkItem.classList.remove('fa-spin');
    chkItem.classList.remove('fa-check');
    chkItem.classList.add('fa-times');    
  } else if (status == 1) {
    chkItem.classList.remove('fa-spinner');
    chkItem.classList.remove('fa-spin');
    chkItem.classList.remove('fa-times');
    chkItem.classList.add('fa-check');
  } else if (status == 2) {
    chkItem.classList.remove('fa-check');
    chkItem.classList.remove('fa-times');
    chkItem.classList.add('fa-spinner');
    chkItem.classList.add('fa-spin');
  }
}

function serverStatus(status) {
  var serverUpBox = document.getElementById('serverup');
  var serverDownBox = document.getElementById('serverdown');
  if (status == 0) {
    serverUpBox.classList.add('hidden');
    serverDownBox.classList.remove('hidden');
    servPreviousStatus = 0;
  } else if (status == 1 && servPreviousStatus == 0) {
    serverDownBox.classList.add('hidden');
    serverUpBox.classList.remove('hidden');
    servPreviousStatus = 1;
    sendUnsynced();
    setTimeout(function() { serverUpBox.classList.add('hidden');}, 2000);
  }
}

//FUNÇÕES DE INICIALIZAÇÃO
window.onload = function() {
  //Ini - EventListeners
  document.getElementById('pass_data').addEventListener("keydown", function(event) {
    if (event.keyCode === 13 || event.keyCode === 0) {
      event.preventDefault();
      return addPass();
    }
  });
  document.getElementById('submit_pass').addEventListener("touchstart", addPass());
  document.getElementById('menutoggle').addEventListener("click", function() {
    document.getElementById('footer').classList.toggle('shown');
  });
  //Fim - EventListeners

  openDB();
}

function openDB() {
  var dbOpenReq = window.indexedDB.open("EnduroDB", 1);

  dbOpenReq.onerror = function(event) {
    requirementError(1);
    checkListUpdate(1,0);
    console.log("Erro ao abrir banco de dados local.\nCódigo de erro:"+event.target.errorCode);    
    db = false;    
  }

  dbOpenReq.onsuccess = function(event) {
    db = event.target.result;
    rawTrans = db.transaction(["passRaw"], "readwrite");
    rawStore = rawTrans.objectStore("passRaw");
    checkListUpdate(1,1);
    openStorage();
  }

  dbOpenReq.onupgradeneeded = function(event) {
    db = event.target.result;

    rawStore = db.createObjectStore("passRaw", { keyPath: "pass_time" });

    rawStore.createIndex("pass_data", "pass_data", { unique: false });
    rawStore.createIndex("time_offset", "time_offset", { unique: false });
    rawStore.createIndex("synced", "synced", { unique: false });    

    rawStore.transaction.oncomplete = function(event) {
      rawTrans = db.transaction(["passRaw"], "readwrite");
      rawStore = rawTrans.objectStore("passRaw");      
    };    
  }
}

function openStorage() {
  try {
    store = window.localStorage;
    store.setItem('test', 'test');
    store.removeItem('test');
    checkListUpdate(2,1);
    checkExistingData();
  } catch(e) {
    store = false;
    requirementError(2);
    checkListUpdate(2,0);
  }
}

function checkExistingData() {
  rawTrans = db.transaction(["passRaw"], "readwrite");
  rawStore = rawTrans.objectStore("passRaw");
  var cursorRequest = rawStore.index("synced").openCursor(0);
  cursorRequest.onsuccess = function(event) {
    var cursor = event.target.result;    
    if (cursor) {
      if (store.getItem('username') == username && store.getItem('evento_id') == evento_id) {
        checkListUpdate(3,1);
        sendUnsynced(checkExistingData) ;       
      } else {        
        checkListUpdate(3,0);
        requirementError(31);
      }
    } else {
      
        rawStore.clear();
        store.setItem('username', username);
        store.setItem('evento_id', evento_id);
      
      checkListUpdate(3,1);
      getPassData();
    }
  }
  cursorRequest.onerror = function(event) {
    checkListUpdate(3,0);
    requirementError(32);
  }
}

function getPassData() {
  $.ajax({
    type: 'POST',
    url: '/getPassData',    
    success: function(msg) {
      serverStatus(1);
      var response = JSON.parse(msg);
      if (response != "noData") {        
        rawTrans = db.transaction(["passRaw"], "readwrite");
        rawStore = rawTrans.objectStore("passRaw");
        var i = 0;
        putNext();        
        function putNext() {
          if (i < response.length) {
            pass = response[i];
            let addData = { pass_time: Number(pass["pass_time"]), pass_data: pass["pass_data"], time_offset: Number(pass["time_offset"]), synced: 1 };
            rawStore.put(addData).onsuccess = function(event) { putNext(); }
            rawStore.put(addData).onerror = function(event) {
              requirementError(4); 
              checkListUpdate(4,0);
            }
            i++;
          } else {
            checkListUpdate(4,1);
            timeOffset().then(_=> {checkHabilitado();});
          }
        }        
      } else {
        checkListUpdate(4,1);
        timeOffset().then(_=> {checkHabilitado();});
      }
    },
    error: function(msg) {
      serverStatus(0);
      serverWait(getPassData);
    }    
  });
}

function timeOffset() {
  return new Promise((resolve, reject) => {
    checkListUpdate(5,3);
    timeCheck();
    function timeCheck() {
      var t1;
      var t2;
      var t3;
      var t0 = new Date().getTime();
      var a = ($.ajax({
        type: 'POST',
        url: '/timeOffset'
      }));
      a.then(function(msg) {      
        t3 = new Date().getTime();
        serverStatus(1);
        msg = JSON.parse(msg);
        t1 = msg['0'];
        t2 = msg['1'];
        timeDelta = (t1+t2-t0-t3)/2;
        console.log("timeDelta= "+timeDelta+" ms");
        checkListUpdate(5,1);
        resolve(timeDelta);
      });
      a.catch(function(msg) {
        checkListUpdate(5,0);
        serverStatus(0);
        setTimeout(timeCheck, 5000);
      });
    }
  });
}

function checkHabilitado() {
  checkListUpdate(6,3);
  $.ajax({
    type: 'POST',
    url: '/checkHabilitado',
    success: function(msg) {
      serverStatus(1);
      habilitado = JSON.parse(msg);      
      if (habilitado == 1) {
        checkListUpdate(6,1);
        requirementError(0);
        recurringCalls();
        initialFill();
        document.getElementById('pass_data').focus();
      } else {
        checkListUpdate(6,0);        
        requirementError(6);
        habilitadoUpdate();
      }
    },
    error: function(msg) {
      serverStatus(0);
      serverWait(checkHabilitado);
    }
  });
}

function habilitadoUpdate() {
  window.setTimeout(checkListUpdate, 12000, 6, 2);
  window.setTimeout(checkHabilitado, 15000);
}

var interv1;
var interv2;
function recurringCalls() {
  interv1 = window.setInterval(timeOffset, 30000);
  interv2 = window.setInterval(sendUnsynced, 30000);
}

function initialFill() {  
  rawTrans = db.transaction(["passRaw"], "readonly");
  rawStore = rawTrans.objectStore("passRaw");
  var cursorRequest = rawStore.openCursor(null, "prev");
  cursorRequest.onsuccess = function(event) {
    var cursor = event.target.result;    
    if (cursor && visible.length < 10) {
      visible.push(cursor.value);      
      cursor.continue();
    } else {
      ready = true;
      updateDisplay();
    }
  }
}

function updateDisplay() {
  if (ready === true) {
    display = document.getElementById('display');
    if (visible != displayed) {
      if (visible[1] == displayed[0]) {
        displayed.unshift(visible[0]);
        addDisplayElement(visible[0], 'afterbegin');
        if (displayed.length > 10) {
          displayed.splice(-1,1);
          display.removeChild(display.lastChild);
        }
      } else {
        display.innerHTML = "";
        displayed = [];
        visible.forEach(function(visElement) {
          displayed.push(visElement);
          addDisplayElement(visElement, 'beforeend');
        });
      }
    }
  }  
}

function addDisplayElement(element, position) {
  var pass_time = new Date(element["pass_time"]);  
  var time = ''
      time += (pass_time.getHours() < 10) ? ('0'+pass_time.getHours()) : pass_time.getHours();
      time += (':' + ((pass_time.getMinutes() < 10) ? ('0'+pass_time.getMinutes()) : pass_time.getMinutes()));
      time += (':' + ((pass_time.getSeconds() < 10) ? ('0'+pass_time.getSeconds()) : pass_time.getSeconds()));
  var html = '<div class="pass" id="' + time + '.' + pass_time.getMilliseconds() + '">';
      html += '<p class="numero">' + element["pass_data"] + '</p>';
      html += '<p class="hora">' + time + '</p>';
      html += '<p class="status">';
    if (element["synced"] == 0) {
      html += '<i class="fas fa-hourglass-half"></i>';
    } else {
      html += '<i class="fas fa-check"></i>';
    }
    html += '</div>';
  display.insertAdjacentHTML(position, html);
}

function logout(e) {
  if (window.confirm("Tem certeza que deseja sair?")) {
    window.location.href = "/logout";
  }
}