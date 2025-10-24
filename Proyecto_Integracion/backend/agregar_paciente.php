<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }


$data = json_decode(file_get_contents('php://input'), true);
$required_fields = ['rut', 'nombres', 'apellidos', 'email', 'telefono', 'contrasena'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['detail' => "El campo '$field' es obligatorio."]);
        exit;
    }
}


$contrasena_hash = password_hash($data['contrasena'], PASSWORD_DEFAULT);

$host = '127.0.0.1'; 
$dbname = 'citas_medicas'; 
$user = 'root'; 
$password = ''; 

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "INSERT INTO paciente (rut, nombres, apellidos, email, telefono, contrasena_hash, rol) 
            VALUES (:rut, :nombres, :apellidos, :email, :telefono, :contrasena_hash, 'paciente')";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':rut' => $data['rut'],
        ':nombres' => $data['nombres'],
        ':apellidos' => $data['apellidos'],
        ':email' => $data['email'],
        ':telefono' => $data['telefono'],
        ':contrasena_hash' => $contrasena_hash
    ]);

    http_response_code(201); 
    echo json_encode(['detail' => 'Paciente agregado exitosamente.', 'id' => $db->lastInsertId()]);

} catch (PDOException $e) {
    http_response_code(500);

    if ($e->getCode() === '23000' || strpos($e->getMessage(), '1062') !== false) {
         echo json_encode(['detail' => 'Error: El RUT o Email ya existe en el sistema.']);
    } else {
        error_log("Error de BD: " . $e->getMessage()); 
        echo json_encode(['detail' => 'Error interno al procesar la solicitud.']);
    }
}
?>