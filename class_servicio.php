<?php
class Servicio {
    var $id;
    var $fecha;
    var $id_vehiculo;
    var $estado;
    var $fecha_estado;
    var $importe;
    var $pdo;

    function __construct($pdo){
        $this->pdo = $pdo;
    }

    function buscarVehiculo($patente){
        $stmt = $this->pdo->prepare("
            SELECT v.id AS id_vehiculo, v.patente, m.nombre AS modelo,
                   c.apellido_nombre, c.dni, c.tel_linea, c.movil, c.email
            FROM vehiculos v
            JOIN modelos m  ON m.id = v.id_modelo
            JOIN clientes c ON c.id = v.id_cliente
            WHERE v.patente = ?
        ");
        $stmt->execute([strtoupper(trim($patente))]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    function buscarServiceActivo($id_vehiculo){
        $stmt = $this->pdo->prepare("SELECT id, estado FROM servicios WHERE id_vehiculo = ? AND estado < 3 ORDER BY id DESC LIMIT 1");
        $stmt->execute([$id_vehiculo]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }


    function crear($id_vehiculo){
        $stmt = $this->pdo->prepare("INSERT INTO servicios (fecha, id_vehiculo, estado, fecha_estado) VALUES (CURDATE(), ?, 0, CURDATE())");
        $stmt->execute([$id_vehiculo]);
        return (int)$this->pdo->lastInsertId();
    }

    function gestionarTrabajo($id_servicio, $id_trabajo, $cantidad, $accion){
        $stmt = $this->pdo->prepare("SELECT estado FROM servicios WHERE id = ?");
        $stmt->execute([$id_servicio]);
        $estado = (int)$stmt->fetchColumn();
        if($estado >= 2) return ['ok'=>false, 'msg'=>'No se pueden modificar trabajos en este estado'];

        if($accion == 'quitar'){
            $stmt = $this->pdo->prepare("DELETE FROM servicios_det WHERE id_servicio=? AND id_trabajo=?");
            $stmt->execute([$id_servicio, $id_trabajo]);
        } else {
            $stmt = $this->pdo->prepare("SELECT importe FROM trabajos WHERE id=?");
            $stmt->execute([$id_trabajo]);
            $importe = (float)$stmt->fetchColumn() * $cantidad;

            $stmt = $this->pdo->prepare("SELECT id FROM servicios_det WHERE id_servicio=? AND id_trabajo=?");
            $stmt->execute([$id_servicio, $id_trabajo]);

            if($stmt->fetchColumn()){
                $stmt = $this->pdo->prepare("UPDATE servicios_det SET cantidad=?, importe=? WHERE id_servicio=? AND id_trabajo=?");
                $stmt->execute([$cantidad, $importe, $id_servicio, $id_trabajo]);
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO servicios_det (id_servicio,id_trabajo,cantidad,importe) VALUES(?,?,?,?)");
                $stmt->execute([$id_servicio, $id_trabajo, $cantidad, $importe]);
            }

            if($estado == 0){
                $stmt = $this->pdo->prepare("UPDATE servicios SET estado=1, fecha_estado=CURDATE() WHERE id=?");
                $stmt->execute([$id_servicio]);
            }
        }

        $stmt = $this->pdo->prepare("SELECT estado FROM servicios WHERE id=?");
        $stmt->execute([$id_servicio]);
        return ['ok'=>true, 'estado'=>(int)$stmt->fetchColumn(), 'trabajos'=>$this->getTrabajosDet($id_servicio)];
    }

    function cambiarEstado($id_servicio, $nuevo_estado){
        $stmt = $this->pdo->prepare("SELECT estado FROM servicios WHERE id=?");
        $stmt->execute([$id_servicio]);
        $actual = (int)$stmt->fetchColumn();

        $transiciones = [0=>[1], 1=>[2], 2=>[3], 3=>[]];
        if(!in_array($nuevo_estado, $transiciones[$actual]))
            return ['ok'=>false, 'msg'=>'Transición de estado no permitida'];

        if($nuevo_estado == 2){
            $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(importe),0) FROM servicios_det WHERE id_servicio=?");
            $stmt->execute([$id_servicio]);
            $importe = (float)$stmt->fetchColumn();
            if($importe <= 0) return ['ok'=>false, 'msg'=>'El servicio no tiene trabajos cargados'];
            $stmt = $this->pdo->prepare("UPDATE servicios SET estado=?, fecha_estado=CURDATE(), importe=? WHERE id=?");
            $stmt->execute([$nuevo_estado, $importe, $id_servicio]);
        } else {
            $stmt = $this->pdo->prepare("UPDATE servicios SET estado=?, fecha_estado=CURDATE() WHERE id=?");
            $stmt->execute([$nuevo_estado, $id_servicio]);
        }

        return ['ok'=>true, 'estado'=>$nuevo_estado];
    }

    function detalle($id_servicio){
        $stmt = $this->pdo->prepare("
            SELECT s.id, s.fecha, s.estado, s.fecha_estado, s.importe,
                   v.patente, m.nombre AS modelo,
                   c.apellido_nombre, c.dni, c.tel_linea, c.movil, c.email
            FROM servicios s
            JOIN vehiculos v ON v.id = s.id_vehiculo
            JOIN modelos m   ON m.id = v.id_modelo
            JOIN clientes c  ON c.id = v.id_cliente
            WHERE s.id = ?
        ");
        $stmt->execute([$id_servicio]);
        $serv = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$serv) return null;
        $serv['trabajos'] = $this->getTrabajosDet($id_servicio);
        return $serv;
    }

    function serviciosPorCliente($dni){
        $stmt = $this->pdo->prepare("
            SELECT s.id, s.fecha, s.estado, s.fecha_estado, s.importe,
                   v.patente, m.nombre AS modelo
            FROM servicios s
            JOIN vehiculos v ON v.id = s.id_vehiculo
            JOIN modelos m   ON m.id = v.id_modelo
            JOIN clientes c  ON c.id = v.id_cliente
            WHERE c.dni = ?
            ORDER BY s.fecha DESC, s.id DESC
        ");
        $stmt->execute([$dni]);
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($lista as &$s){
            $s['trabajos'] = $this->getTrabajosDet($s['id']);
        }
        return $lista;
    }

    function listarTrabajos(){
        $stmt = $this->pdo->query("SELECT id, nombre, importe FROM trabajos ORDER BY nombre");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function buscarCliente($dni){
        $stmt = $this->pdo->prepare("SELECT id, apellido_nombre, dni, tel_linea, movil, email FROM clientes WHERE dni = ?");
        $stmt->execute([$dni]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    function getTrabajosDet($id_servicio){
        $stmt = $this->pdo->prepare("
            SELECT t.id AS id_trabajo, t.nombre, sd.cantidad,
                   t.importe AS precio_unit, sd.importe AS importe_linea
            FROM servicios_det sd
            JOIN trabajos t ON t.id = sd.id_trabajo
            WHERE sd.id_servicio = ?
            ORDER BY t.nombre
        ");
        $stmt->execute([$id_servicio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}