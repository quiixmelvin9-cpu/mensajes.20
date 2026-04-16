<?php
require_once __DIR__ . '/bootstrap.php';

session_unset();
session_destroy();

json_response(['ok' => true, 'message' => 'Sesion cerrada']);
