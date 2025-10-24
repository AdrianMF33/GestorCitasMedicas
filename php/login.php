<?php
// Configuración de cabeceras para API y CORS
header('Content-Type: application/json');
// Permitir solicitudes CORS (necesario en entorno local y de desarrollo)
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
$contrasena = $data['contrasena']; // Valor esperado: 'demo123'

// --- 2. Conexión a MySQL/MariaDB (XAMPP por defecto) ---
$host = 'localhost'; 
$dbname = 'citas_curico';
$user = 'root'; // Usuario por defecto de XAMPP
$password = ''; // Contraseña por defecto (vacía) de XAMPP

try {
    // CRÍTICO: Usar 'mysql' en el DSN
    $db = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500); 
    // Muestra el error de MySQL solo en desarrollo. En producción, solo el mensaje genérico.
    error_log("Error de conexión a BD MySQL: " . $e->getMessage()); 
    echo json_encode(['detail' => 'Error al conectar con la base de datos MySQL. Revise XAMPP.']);
    exit;
}

// --- 3. Buscar Usuario y Verificar Credenciales ---
// Se busca en la tabla paciente, asumiendo que los roles 'admin' y 'paciente' están allí.
// Para esta fase de prueba, la verificación de la contraseña es la cadena literal 'demo123'.

$sql = "SELECT email, rol FROM paciente WHERE email = :correo";
$stmt = $db->prepare($sql);
$stmt->execute([':correo' => $correo]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Simulación de verificación de contraseña
// En un entorno real: password_verify($contrasena, $usuario['contrasena_hash'])
if ($usuario && $contrasena === 'demo123') { 
    
    // --- 4. Respuesta Exitosa (Token y Rol) ---
    // Generación de un token simulado para el Frontend:
    $token_acceso = bin2hex(random_bytes(32)); 
    $rol = $usuario['rol'];
    
    http_response_code(200);
    echo json_encode([
        'token_acceso' => $token_acceso,
        'rol' => $rol 
    ]);
} else {
    // Fallo de autenticación
    http_response_code(401); // Unauthorized
    echo json_encode(['detail' => 'Credenciales inválidas.']);
}

?>