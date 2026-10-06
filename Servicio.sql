CREATE TABLE modelos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);
INSERT INTO modelos (nombre) VALUES
('Ford Fiesta'),
('Chevrolet'),
('Toyota'),
('Peugeot 208');

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apellido_nombre VARCHAR(100) NOT NULL,
    dni INT(10) NOT NULL,
    tel_linea VARCHAR(30),
    movil VARCHAR(30),
    email VARCHAR(100),
    observaciones TEXT
);
INSERT INTO clientes (apellido_nombre, dni, tel_linea, movil, email, observaciones) VALUES
('Juan Pérez', 30123456, '4267890', '154123456', 'juan@mail.com', 'Cliente habitual'),
('María Gómez', 28987654, '4378123', '155678912', 'maria@mail.com', ''),
('Carlos López', 33456789, '4456789', '156789123', 'carlos@mail.com', ''),
('Ana Torres', 31222333, '4212345', '154567890', 'ana@mail.com', '');


CREATE TABLE vehiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patente VARCHAR(7) NOT NULL,
    id_modelo INT NOT NULL,
    id_cliente INT NOT NULL,
    FOREIGN KEY (id_modelo) REFERENCES modelos(id),
    FOREIGN KEY (id_cliente) REFERENCES clientes(id)
);
INSERT INTO vehiculos (patente, id_modelo, id_cliente) VALUES
('ABC123', 1, 1),
('DEF456', 3, 1),
('GHI789', 2, 2),
('JKL321', 4, 3),
('MNO654', 3, 4);

CREATE TABLE servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    id_vehiculo INT NOT NULL,
    estado INT NOT NULL COMMENT '0=recibido, 1=en proceso, 2=terminado no entregado, 3=entregado',
    fecha_estado DATE,
    importe DECIMAL(10,2),
    FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id)
);


CREATE TABLE trabajos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    importe DECIMAL(10,2) NOT NULL
);
INSERT INTO trabajos (nombre, importe) VALUES
('Cambio de aceite', 15000),
('Cambio de filtro', 8000),
('Alineación y balanceo', 12000),
('Revisión general', 10000);


CREATE TABLE servicios_det (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_servicio INT NOT NULL,
    id_trabajo INT NOT NULL,
    cantidad INT NOT NULL,
    importe DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_servicio) REFERENCES servicios(id),
    FOREIGN KEY (id_trabajo) REFERENCES trabajos(id)
);


TRUNCATE TABLE trabajos;
TRUNCATE TABLE servicios;
TRUNCATE TABLE servicios_det;