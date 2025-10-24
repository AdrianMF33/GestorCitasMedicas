<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}


$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? null;

if (!preg_match('/Bearer\s(\S+)/', $auth_header) ) {
    http_response_code(401);
    echo json_encode(['detail' => 'Acceso denegado. Se requiere un token de autenticación.']);
    exit;
}

$rol_simulado = 'admin'; 

$host = '127.0.0.1'; 
$dbname = 'citas_medicas'; 
$user = 'root'; 
$password = ''; 

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500); 
    error_log("Error de conexión a BD MySQL en usuarios.php: " . $e->getMessage()); 
    echo json_encode(['detail' => 'Error al conectar con la base de datos (Verifique credenciales y XAMPP).']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    try {
        $sql_usuarios = "SELECT id_paciente, rut, nombres, apellidos, email, telefono, rol FROM paciente ORDER BY apellidos";
        $stmt_usuarios = $db->query($sql_usuarios);
        $usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

        $sql_conteos = "SELECT rol, COUNT(*) as total FROM paciente GROUP BY rol";
        $stmt_conteos = $db->query($sql_conteos);
        $conteos_raw = $stmt_conteos->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $conteos = array_map('intval', $conteos_raw); 

        http_response_code(200);
        echo json_encode([
            'usuarios' => $usuarios, 
            'conteos' => $conteos
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Error GET en usuarios.php: " . $e->getMessage());
        echo json_encode(['detail' => 'Error al obtener la lista de usuarios. Revise la estructura de la tabla.']);
    }
    exit;
}

if ($method === 'DELETE') {
    $id_paciente = $_GET['id'] ?? null;
    
    if (!$id_paciente) {
        http_response_code(400);
        echo json_encode(['detail' => 'ID de usuario no proporcionado para eliminar.']);
        exit;
    }

    try {
        $sql = "DELETE FROM paciente WHERE id_paciente = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id_paciente]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['detail' => 'Usuario no encontrado.']);
            exit;
        }

        http_response_code(200);
        echo json_encode(['detail' => 'Usuario eliminado exitosamente.']);
    } catch (PDOException $e) {
        http_response_code(500);
        if ($e->getCode() === '23000') {
            echo json_encode(['detail' => 'Error: No se puede eliminar al usuario porque tiene registros (citas) asociados.']);
        } else {
            echo json_encode(['detail' => 'Error interno al eliminar el usuario.']);
        }
    }
    exit;
}


if ($method === 'PUT') {

    $data = json_decode(file_get_contents('php://input'), true);
    
    $id_paciente = $data['id_paciente'] ?? null;
    $rol = $data['rol'] ?? null;
    $rut = $data['rut'] ?? null;
    $nombres = $data['nombres'] ?? null;
    $apellidos = $data['apellidos'] ?? null;
    $email = $data['email'] ?? null;
    $telefono = $data['telefono'] ?? null; 

    if (!$id_paciente || !$rol) {
        http_response_code(400);
        echo json_encode(['detail' => 'Datos incompletos para la modificación.']);
        exit;
    }
    
    $roles_validos = ['paciente', 'doctor', 'admin'];
    if (!in_array($rol, $roles_validos)) {
        http_response_code(400);
        echo json_encode(['detail' => 'Rol no válido.']);
        exit;
    }

    try {
        $sql = "UPDATE paciente SET rol = :rol, rut = :rut, nombres = :nombres, apellidos = :apellidos, email = :email, telefono = :telefono WHERE id_paciente = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':rol' => $rol,
            ':rut' => $rut,
            ':nombres' => $nombres,
            ':apellidos' => $apellidos,
            ':email' => $email,
            ':telefono' => $telefono,
            ':id' => $id_paciente
        ]);

        http_response_code(200);
        echo json_encode(['detail' => 'Usuario modificado exitosamente.']);

    } catch (PDOException $e) {
        http_response_code(500);
        if ($e->getCode() === '23000') {
             echo json_encode(['detail' => 'Error de unicidad: El RUT o Email ya está registrado por otro usuario.']);
        } else {
             error_log("Error PUT en usuarios.php: " . $e->getMessage());
             echo json_encode(['detail' => 'Error interno al modificar el usuario.']);
        }
    }
    exit;
}


http_response_code(405); 
echo json_encode(['detail' => 'Método no permitido.']);
?>