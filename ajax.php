<?php
require_once 'class_servicio.php';
header('Content-Type: application/json; charset=utf-8');

$pdo  = new PDO("mysql:host=localhost;dbname=servicio;charset=utf8", "root", "",
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$serv = new Servicio($pdo);
$ac   = $_GET['accion'] ?? '';

switch ($ac) {

    case 'buscarVehiculo':
        $v = $serv->buscarVehiculo($_GET['patente'] ?? '');
        if (!$v) { echo json_encode(['ok'=>false, 'msg'=>'Patente no encontrada']); break; }
        $sa  = $serv->buscarServiceActivo($v['id_vehiculo']);
        $det = $sa ? $serv->detalle($sa['id']) : null;
        echo json_encode([
            'ok'                   => true,
            'id_vehiculo'          => $v['id_vehiculo'],
            'patente'              => $v['patente'],
            'modelo'               => $v['modelo'],
            'apellido_nombre'      => $v['apellido_nombre'],
            'dni'                  => $v['dni'],
            'tel_linea'            => $v['tel_linea'],
            'movil'                => $v['movil'],
            'service_activo'       => $det ? [
                'id_servicio' => $det['id'],
                'estado'      => $det['estado'],
                'trabajos'    => $det['trabajos'],
            ] : null,
        ]);
        break;

    case 'recibirVehiculo':
        $id  = (int)($_POST['id_vehiculo'] ?? 0);
        $sa  = $serv->buscarServiceActivo($id);
        $nuevo = !$sa;
        $id_serv = $sa ? $sa['id'] : $serv->crear($id);
        $det = $serv->detalle($id_serv);
        echo json_encode([
            'ok'                   => true,
            'id_servicio'          => $id_serv,
            'estado'               => $det['estado'],
            'trabajos'             => $det['trabajos'],
        ]);
        break;

    case 'gestionarTrabajo':
        echo json_encode($serv->gestionarTrabajo(
            (int)($_POST['id_servicio'] ?? 0),
            (int)($_POST['id_trabajo']  ?? 0),
            (int)($_POST['cantidad']    ?? 1),
            $_POST['accion_det'] ?? 'agregar'
        ));
        break;

    case 'cambiarEstado':
        echo json_encode($serv->cambiarEstado(
            (int)($_POST['id_servicio'] ?? 0),
            (int)($_POST['estado']      ?? -1)
        ));
        break;

    case 'consultarServicio':
        $r = $serv->detalle((int)($_GET['id'] ?? 0));
        echo json_encode($r ?? ['ok'=>false, 'msg'=>'Servicio no encontrado']);
        break;

    case 'consultarCliente':
        $dni = trim($_GET['dni'] ?? '');
        $cli = $serv->buscarCliente($dni);
        if (!$cli) { echo json_encode(['ok'=>false, 'msg'=>'Cliente no encontrado']); break; }
        echo json_encode(['ok'=>true, 'cliente'=>$cli, 'servicios'=>$serv->serviciosPorCliente($dni)]);
        break;

    case 'listarTrabajos':
        echo json_encode($serv->listarTrabajos());
        break;

    default:
        echo json_encode(['ok'=>false, 'msg'=>'Acción no válida']);
}