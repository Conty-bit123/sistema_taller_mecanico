<?php
$pdo = new PDO("mysql:host=localhost;dbname=servicio;charset=utf8", "root", "");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Sistema de Servicios</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="estilo.css">
</head>
<body>

<div style="background:#333;color:#fff;padding:10px 20px;font-size:16px;font-weight:bold;">
  Sistema de Servicios_Taller
</div>

<div class="tabs">
  <span class="tab active" onclick="showTab('t-servicio',this)">Servicio</span>
  <span class="tab" onclick="showTab('t-consultas',this)">Consultas</span>
</div>

<div id="t-servicio" class="page active"><div class="wrap">

  <div class="seccion">
    <h3>Recepción de vehículo &nbsp;<span id="badge-estado" class="badge" style="display:none"></span></h3>
    <div id="msg-recepcion" class="msg"></div>
    <label>Patente:</label>
    <input type="text" id="patente" size="10" style="text-transform:uppercase"
           onkeydown="if(event.key==='Enter') buscarVehiculo()">
    <button onclick="buscarVehiculo()">Buscar</button>
    <button id="btn-limpiar" onclick="limpiarFormulario()" style="display:none">Limpiar</button>

    <div id="datos-vehiculo" style="display:none">
      <hr>
      <div class="info-fila">
        <div class="info-campo"><span>Patente</span>  <span id="v-patente"></span></div>
        <div class="info-campo"><span>Modelo</span>   <span id="v-modelo"></span></div>
        <div class="info-campo"><span>Cliente</span>  <span id="v-cliente"></span></div>
        <div class="info-campo"><span>DNI</span>      <span id="v-dni"></span></div>
        <div class="info-campo"><span>Teléfono</span> <span id="v-tel"></span></div>
      </div>
      <input type="hidden" id="id-servicio">
      <input type="hidden" id="id-vehiculo">
      <input type="hidden" id="estado-actual">
      <div id="btn-recibir-wrap" style="display:none;margin-top:12px">
        <button onclick="recibirVehiculo()" style="font-weight:bold">Recibir vehículo</button>
      </div>
    </div>
  </div>

  <div class="seccion" id="card-trabajos" style="display:none">
    <h3>Trabajos realizados</h3>
    <div id="msg-trabajo" class="msg"></div>
    <label>Trabajo:</label> <select id="trabajo"></select> &nbsp;
    <label>Cantidad:</label> <input type="number" id="cantidad" value="1" min="1" size="4">
    <button onclick="agregarTrabajo()">Agregar</button>
    <table>
      <thead><tr><th>Trabajo</th><th class="tar">Precio Unitario</th><th class="tar">Cantidad</th><th class="tar">Importe</th><th></th></tr></thead>
      <tbody id="tbody-trabajos"></tbody>
      <tfoot><tr><td colspan="3" class="tar">Total</td><td class="tar" id="total-trabajos">$0,00</td><td></td></tr></tfoot>
    </table>
  </div>

  <div class="seccion" id="card-estado" style="display:none">
    <h3>Cambiar estado</h3>
    <div id="msg-estado" class="msg"></div>
    <div id="botones-estado"></div>
  </div>

</div></div>

<div id="t-consultas" class="page"><div class="wrap">

  <div class="seccion">
    <h3>Consultar por N° de servicio</h3>
    <div id="msg-cons-serv" class="msg"></div>
    <label>N° servicio:</label>
    <input type="number" id="id-consulta" min="1" size="8" onkeydown="if(event.key==='Enter') consultarServicio()">
    <button onclick="consultarServicio()">Buscar</button>
    <button onclick="cancelarConsulta('id-consulta','res-servicio','msg-cons-serv')">Cancelar</button>
    <div id="res-servicio"></div>
  </div>

  <div class="seccion">
    <h3>Consultar por cliente (DNI)</h3>
    <div id="msg-cons-cli" class="msg"></div>
    <label>DNI:</label>
    <input type="number" id="dni-consulta" size="12" onkeydown="if(event.key==='Enter') consultarPorCliente()">
    <button onclick="consultarPorCliente()">Buscar</button>
    <button onclick="cancelarConsulta('dni-consulta','res-cliente','msg-cons-cli')">Cancelar</button>
    <div id="res-cliente"></div>
  </div>

</div></div>

<script>

var estadoNombres = {0:'Recibido', 1:'En proceso', 2:'Terminado no entregado', 3:'Entregado'};
var estadoClases  = {0:'e0', 1:'e1', 2:'e2', 3:'e3'};

$(function(){
    $.getJSON("ajax.php?accion=listarTrabajos", function(lista){
        var opciones = "";
        for(var i = 0; i < lista.length; i++){
            opciones += "<option value='" + lista[i].id + "'>" + lista[i].nombre + " — $" + formatear(lista[i].importe) + "</option>";
        }
        $("#trabajo").html(opciones);
    });
});

function buscarVehiculo(){
    var patente = $("#patente").val().trim().toUpperCase();
    if(!patente){ mostrarMsg("msg-recepcion", "Ingrese una patente", "error"); return; }
    limpiarFormulario(false);

    $.getJSON("ajax.php?accion=buscarVehiculo&patente=" + patente, function(res){
        if(!res.ok){ mostrarMsg("msg-recepcion", res.msg, "error"); return; }

        $("#v-patente").text(res.patente);
        $("#v-modelo").text(res.modelo);
        $("#v-cliente").text(res.apellido_nombre);
        $("#v-dni").text(res.dni);
        $("#v-tel").text(res.tel_linea || res.movil || "—");
        $("#id-vehiculo").val(res.id_vehiculo);
        $("#datos-vehiculo").show();
        $("#btn-limpiar").show();

        var sa = res.service_activo;

        if(sa){
            $("#id-servicio").val(sa.id_servicio);
            $("#estado-actual").val(sa.estado);
            $("#btn-recibir-wrap").hide();
            actualizarBadge(sa.estado);
            mostrarBotonesEstado(sa.estado);
            cargarTablaTrabajos(sa.trabajos, sa.estado);
            if(sa.estado < 2){ $("#card-trabajos").show(); } else { $("#card-trabajos").hide(); }
        } else {
            $("#btn-recibir-wrap").show();
        }
    });
}

function recibirVehiculo(){
    $.post("ajax.php?accion=recibirVehiculo", {id_vehiculo: $("#id-vehiculo").val()}, function(res){
        if(!res.ok){ mostrarMsg("msg-recepcion", res.msg, "error"); return; }
        $("#btn-recibir-wrap").hide();
        $("#id-servicio").val(res.id_servicio);
        $("#estado-actual").val(res.estado);
        actualizarBadge(res.estado);
        mostrarBotonesEstado(res.estado);
        cargarTablaTrabajos(res.trabajos, res.estado);
        $("#card-trabajos").show();
    }, "json");
}

function limpiarFormulario(borrarPatente){
    if(borrarPatente !== false){ $("#patente").val(""); }
    $("#datos-vehiculo").hide();
    $("#btn-recibir-wrap").hide();
    $("#card-trabajos").hide();
    $("#card-estado").hide();
    $("#badge-estado").hide();
    $("#btn-limpiar").hide();
    $("#id-servicio").val("");
    $("#id-vehiculo").val("");
    $("#estado-actual").val("");
    ocultarMsg("msg-recepcion");
}

function agregarTrabajo(){
    var id_servicio = $("#id-servicio").val();
    if(!id_servicio){ mostrarMsg("msg-trabajo", "Primero recibí el vehículo", "error"); return; }

    $.post("ajax.php?accion=gestionarTrabajo", {
        id_servicio: id_servicio,
        id_trabajo:  $("#trabajo").val(),
        cantidad:    $("#cantidad").val(),
        accion_det:  "agregar"
    }, function(res){
        if(!res.ok){ mostrarMsg("msg-trabajo", res.msg, "error"); return; }
        ocultarMsg("msg-trabajo");
        if(res.estado != $("#estado-actual").val()){
            $("#estado-actual").val(res.estado);
            actualizarBadge(res.estado);
            mostrarBotonesEstado(res.estado);
        }
        cargarTablaTrabajos(res.trabajos, res.estado);
    }, "json");
}

function quitarTrabajo(id_trabajo){
    $.post("ajax.php?accion=gestionarTrabajo", {
        id_servicio: $("#id-servicio").val(),
        id_trabajo:  id_trabajo,
        cantidad:    0,
        accion_det:  "quitar"
    }, function(res){
        if(!res.ok){ mostrarMsg("msg-trabajo", res.msg, "error"); return; }
        cargarTablaTrabajos(res.trabajos, res.estado);
    }, "json");
}

function modificarCantidad(id_trabajo, cantidad){
    $.post("ajax.php?accion=gestionarTrabajo", {
        id_servicio: $("#id-servicio").val(),
        id_trabajo:  id_trabajo,
        cantidad:    cantidad,
        accion_det:  "modificar"
    }, function(res){
        if(!res.ok){ mostrarMsg("msg-trabajo", res.msg, "error"); return; }
        cargarTablaTrabajos(res.trabajos, res.estado);
    }, "json");
}

function cargarTablaTrabajos(trabajos, estado){
    var editable = estado < 2;
    var filas = "";
    var total = 0;

    for(var i = 0; i < trabajos.length; i++){
        var t = trabajos[i];
        total += parseFloat(t.importe_linea || 0);

        var celdaCantidad = "";
        if(editable){
            celdaCantidad += "<button onclick=\"modificarCantidad(" + t.id_trabajo + "," + (Math.max(1, t.cantidad - 1)) + ")\">−</button>";
            celdaCantidad += "<input type='number' value='" + t.cantidad + "' min='1' size='4' onchange=\"modificarCantidad(" + t.id_trabajo + ",parseInt(this.value)||1)\">";
            celdaCantidad += "<button onclick=\"modificarCantidad(" + t.id_trabajo + "," + (t.cantidad * 1 + 1) + ")\">+</button>";
        } else {
            celdaCantidad = t.cantidad;
        }

        var botonQuitar = "";
        if(editable){ botonQuitar = "<button onclick=\"quitarTrabajo(" + t.id_trabajo + ")\">Quitar</button>"; }

        filas += "<tr>";
        filas += "<td>" + t.nombre + "</td>";
        filas += "<td class='tar'>$" + formatear(t.precio_unit) + "</td>";
        filas += "<td class='tar'>" + celdaCantidad + "</td>";
        filas += "<td class='tar'>$" + formatear(t.importe_linea) + "</td>";
        filas += "<td>" + botonQuitar + "</td>";
        filas += "</tr>";
    }

    if(filas == ""){
        filas = "<tr><td colspan='5' style='text-align:center;color:#999'>Sin trabajos cargados</td></tr>";
    }

    $("#tbody-trabajos").html(filas);
    $("#total-trabajos").text("$" + formatear(total));
}

function cambiarEstado(nuevoEstado){
    $.post("ajax.php?accion=cambiarEstado", {
        id_servicio: $("#id-servicio").val(),
        estado:      nuevoEstado
    }, function(res){
        if(!res.ok){ mostrarMsg("msg-estado", res.msg, "error"); return; }
        $("#estado-actual").val(nuevoEstado);
        actualizarBadge(nuevoEstado);
        mostrarBotonesEstado(nuevoEstado);
        if(nuevoEstado < 2){ $("#card-trabajos").show(); } else { $("#card-trabajos").hide(); }
        mostrarMsg("msg-estado", "Estado: " + estadoNombres[nuevoEstado], "ok");
    }, "json");
}

function mostrarBotonesEstado(estado){
    var html = "";
    if(estado == 1){ html = "<button onclick='cambiarEstado(2)'>Marcar terminado</button>"; }
    if(estado == 2){ html = "<button onclick='cambiarEstado(3)'>Marcar entregado</button>"; }
    $("#botones-estado").html(html);
    if(html != ""){ $("#card-estado").show(); } else { $("#card-estado").hide(); }
}

function actualizarBadge(estado){
    $("#badge-estado").text(estadoNombres[estado]).removeClass("e0 e1 e2 e3").addClass(estadoClases[estado]).show();
}

function consultarServicio(){
    var id = $("#id-consulta").val();
    if(!id){ mostrarMsg("msg-cons-serv", "Ingresá un número", "error"); return; }

    $.getJSON("ajax.php?accion=consultarServicio&id=" + id, function(res){
        if(!res || res.ok === false){
            mostrarMsg("msg-cons-serv", res.msg || "No encontrado", "error");
            $("#res-servicio").html("");
            return;
        }
        $("#res-servicio").html(armarDetalleServicio(res, true));
    });
}

function consultarPorCliente(){
    var dni = $("#dni-consulta").val();
    if(!dni){ mostrarMsg("msg-cons-cli", "Ingresá un DNI", "error"); return; }

    $.getJSON("ajax.php?accion=consultarCliente&dni=" + dni, function(res){
        if(!res.ok){
            mostrarMsg("msg-cons-cli", res.msg, "error");
            $("#res-cliente").html("");
            return;
        }

        var c = res.cliente;
        var html = "<div class='seccion' style='margin-top:12px'>";
        html += "<strong>" + c.apellido_nombre + "</strong>";
        html += "</div>";

        if(res.servicios.length == 0){
            html += "<p style='color:#888'>Sin servicios registrados.</p>";
        } else {
            for(var i = 0; i < res.servicios.length; i++){
                html += armarDetalleServicio(res.servicios[i], false);
            }
        }

        $("#res-cliente").html(html);
    });
}

function armarDetalleServicio(s, mostrarDatosCliente){
    var nombreEstado = estadoNombres[s.estado];
    var claseEstado  = estadoClases[s.estado];

    var html = "<div class='seccion' style='margin-top:10px'>";
    html += "<strong>Service #" + s.id + "</strong> &nbsp;<span class='badge " + claseEstado + "'>" + nombreEstado + "</span><br><br>";
    html += "<table>";
    html += "<tr>";
    html += "<td><b>Patente:</b> " + (s.patente || "—") + "</td>";
    html += "<td><b>Modelo:</b> " + (s.modelo || "—") + "</td>";
    if(s.apellido_nombre){ html += "<td><b>Cliente:</b> " + s.apellido_nombre + "</td>"; }
    html += "</tr>";

    if(mostrarDatosCliente){
        html += "<tr>";
        if(s.dni){ html += "<td><b>DNI:</b> " + s.dni + "</td>"; }
        if(s.tel_linea || s.movil){ html += "<td><b>Teléfono:</b> " + (s.tel_linea || s.movil) + "</td>"; }
        html += "</tr>";
    }

    html += "<tr>";
    html += "<td><b>Fecha recepción:</b> " + (s.fecha || "—") + "</td>";
    html += "<td><b>Fecha estado:</b> " + (s.fecha_estado || "—") + "</td>";
    html += "<td><b>Importe:</b> $" + formatear(s.importe) + "</td>";
    html += "</tr>";
    html += "</table>";

    if(s.trabajos && s.trabajos.length > 0){
        var tot = 0;
        html += "<table style='margin-top:8px'>";
        html += "<thead><tr><th>Trabajo</th><th class='tar'>Precio Unitario</th><th class='tar'>Cantidad</th><th class='tar'>Importe</th></tr></thead>";
        html += "<tbody>";
        for(var i = 0; i < s.trabajos.length; i++){
            var t = s.trabajos[i];
            var imp = parseFloat(t.importe_linea || t.importe || 0);
            tot += imp;
            html += "<tr>";
            html += "<td>" + t.nombre + "</td>";
            html += "<td class='tar'>$" + formatear(t.precio_unit || 0) + "</td>";
            html += "<td class='tar'>" + t.cantidad + "</td>";
            html += "<td class='tar'>$" + formatear(imp) + "</td>";
            html += "</tr>";
        }
        html += "</tbody>";
        html += "<tfoot><tr><td colspan='3' class='tar'>Total</td><td class='tar'>$" + formatear(tot) + "</td></tr></tfoot>";
        html += "</table>";
    } else {
        html += "<p style='color:#888;margin-top:6px'>Sin trabajos registrados.</p>";
    }

    html += "</div>";
    return html;
}

function cancelarConsulta(inputId, resultadoId, msgId){
    $("#" + inputId).val("");
    $("#" + resultadoId).html("");
    ocultarMsg(msgId);
}

function mostrarMsg(id, texto, tipo){
    $("#" + id).text(texto).removeClass("ok error info show").addClass(tipo + " show");
}

function ocultarMsg(id){
    $("#" + id).removeClass("show");
}

function formatear(n){
    return parseFloat(n || 0).toLocaleString("es-AR", {minimumFractionDigits: 2});
}

function showTab(id, el){
    $(".page, .tab").removeClass("active");
    $("#" + id).addClass("active");
    $(el).addClass("active");
}

</script>
</body>
</html>
