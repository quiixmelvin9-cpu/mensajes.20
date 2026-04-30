<?php

// Configuracion de conexion MySQL
$Servidor = "localhost";
$Usuario = "root";
$password = "";
$BaseDeDatos = "bd_mensajeria_nueva";

// crear conexion
$conn = new mysqli($Servidor, $Usuario, $password, $BaseDeDatos);

// verificar conexion
if ($conn->connect_error) {
    die("Conexion fallida: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
