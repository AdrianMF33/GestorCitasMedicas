<?php
// login.php
// Este script maneja la autenticación de usuarios contra la base de datos MySQL/MariaDB.

// Configuración de cabeceras para API y CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar la solicitud OPTIONS (pre-flight check)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// --- 1. Leer datos de entrada (JSON) ---
$data = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE || empty($data['correo']) || empty($data['contrasena'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['detail' => 'Datos de entrada inválidos. Se requiere correo y contraseña.']);
    exit;
}

$correo = $data['correo'];
$contrasena = $data['contrasena']; 

// --- 2. Conexión a MySQL/MariaDB (XAMPP por defecto) ---
$host = '127.0.0.1'; // IP local recomendada sobre 'localhost'
$dbname = 'citas_medicas'; // NOMBRE DE BD CORREGIDO
$user = 'root'; 
$password = ''; // Contraseña por defecto (vacía) de XAMPP

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500); 
    // Muestra un error interno después de fallar la conexión
    error_log("Error de conexión a BD MySQL: " . $e->getMessage()); 
    echo json_encode(['detail' => 'Error al conectar con la base de datos MySQL. Revise XAMPP.']);
    exit;
}

// --- 3. Buscar Usuario y Verificar Credenciales (Seguridad RNF1) ---

// Se busca el hash y el rol en la tabla paciente
$sql = "SELECT contrasena_hash, rol FROM paciente WHERE email = :correo";
$stmt = $db->prepare($sql);
$stmt->execute([':correo' => $correo]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$hash_almacenado = $usuario['contrasena_hash'] ?? '';


if ($usuario && password_verify($contrasena, $hash_almacenado)) { 

    $token_acceso = bin2hex(random_bytes(32)); 
    $rol = $usuario['rol'];
    
    http_response_code(200);
    echo json_encode([
        'token_acceso' => $token_acceso,
        'rol' => $rol 
    ]);
} else {
    http_response_code(401); 
    echo json_encode(['detail' => 'Credenciales inválidas.']);
}

?>  