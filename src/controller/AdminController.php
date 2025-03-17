<?php

 // Controlador de Administración - Maneja operaciones CRUD de jugadores y autenticación

require_once __DIR__ . '/DatabaseController.php';
require_once __DIR__ . '/UserController.php';

class AdminController {
    // Inicializar la conexión a la base de datos
    private $connection;

    public function __construct() {
        $this->connection = DatabaseController::connect();
    }

    // Función para verificar si un token es válido
    public static function isAuthenticated($token) {
        if (!$token) return false;
        try {
            \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key(UserController::$secretKey, 'HS256'));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // Función para obtener todos los jugadores con sus estadísticas
    public static function getAllPlayersWithStats() {
        // Conectar a la base de datos
        $db = DatabaseController::connect();
        // Consulta para obtener todos los jugadores con sus estadísticas
        $query = $db->query("
            SELECT p.player_id, p.name, p.surname, p.birthday, p.height, 
                   s.stats_id, s.def, s.spd, s.off, s.pass, s.drb, s.shoot
            FROM Player p JOIN Stats s ON p.player_id = s.player_id ORDER BY p.name ASC;
        ");
        // Obtener los resultados de la consulta
        $players = $query->fetchAll(PDO::FETCH_ASSOC);
        return ['status' => 'success', 'data' => $players, 'count' => count($players)];
    }

    // Función para actualizar un jugador
    public static function updatePlayer($playerId, $playerData) {
        // Conectar a la base de datos
        $db = DatabaseController::connect();
        try {
            // Iniciar una transacción
            $db->beginTransaction();
            // Actualizar los datos del jugador
            $stmt = $db->prepare("UPDATE Player SET name = :name, surname = :surname, birthday = :birthday, height = :height WHERE player_id = :player_id");
            $stmt->execute([
                ':name' => $playerData['name'], 
                ':surname' => $playerData['surname'], 
                ':birthday' => $playerData['birthday'], 
                ':height' => $playerData['height'],
                ':player_id' => $playerId
            ]);

            $stmt = $db->prepare("UPDATE Stats SET def = :def, spd = :spd, off = :off, pass = :pass, drb = :drb, shoot = :shoot WHERE player_id = :player_id");
            $stmt->execute([
                ':def' => $playerData['def'], 
                ':spd' => $playerData['spd'], 
                ':off' => $playerData['off'], 
                ':pass' => $playerData['pass'], 
                ':drb' => $playerData['drb'], 
                ':shoot' => $playerData['shoot'],
                ':player_id' => $playerId
            ]);
            // Confirmar la transacción
            $db->commit();
            return ['status' => 'success', 'message' => 'Player updated successfully'];
        } catch (PDOException $e) {
            $db->rollBack();
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Función para agregar un nuevo jugador
    public static function addPlayer($playerData) {
        // Conectar a la base de datos
        $db = DatabaseController::connect();
        try {
            // Iniciar una transacción
            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO Player (name, surname, birthday, height) VALUES (:name, :surname, :birthday, :height)");
            $stmt->execute([
                ':name' => $playerData['name'], 
                ':surname' => $playerData['surname'], 
                ':birthday' => $playerData['birthday'], 
                ':height' => $playerData['height']
            ]);
            // Obtener el ID del jugador recién insertado
            $playerId = $db->lastInsertId();
            // Insertar las estadísticas del jugador
            $stmt = $db->prepare("INSERT INTO Stats (def, spd, off, pass, drb, shoot, player_id) VALUES (:def, :spd, :off, :pass, :drb, :shoot, :player_id)");
            $stmt->execute([
                ':def' => $playerData['def'], 
                ':spd' => $playerData['spd'], 
                ':off' => $playerData['off'], 
                ':pass' => $playerData['pass'], 
                ':drb' => $playerData['drb'], 
                ':shoot' => $playerData['shoot'],
                ':player_id' => $playerId
            ]);
            // Confirmar la transacción
            $db->commit();
            return ['status' => 'success', 'message' => 'Player added successfully', 'player_id' => $playerId];
        } catch (PDOException $e) {
            $db->rollBack();
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Función para eliminar un jugador
    public static function deletePlayer($playerId) {
        // Conectar a la base de datos
        $db = DatabaseController::connect();
        try {
            // Iniciar una transacción
            $db->beginTransaction();
            // Eliminar las estadísticas del jugador
            $db->prepare("DELETE FROM Stats WHERE player_id = ?")->execute([$playerId]);
            // Eliminar el jugador
            $db->prepare("DELETE FROM Player WHERE player_id = ?")->execute([$playerId]);
            // Confirmar la transacción
            $db->commit();
            return ['status' => 'success', 'message' => 'Player deleted successfully'];
        } catch (PDOException $e) {
            $db->rollBack();
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Función para procesar las solicitudes de la API
    public static function processApiRequest($method, $action, $id = null, $data = null) {
        // Verificar si el token de autorización es válido
        $authHeader = '';
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 
                         (isset($headers['authorization']) ? $headers['authorization'] : '');
        } else {
            foreach(['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION', 'HTTP_X_AUTHORIZATION'] as $header) {
                if (isset($_SERVER[$header])) {
                    $authHeader = $_SERVER[$header];
                    break;
                }
            }
        }

        // Obtener el token de autorización
        $token = null;
        if (!empty($authHeader) && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }

        // Verificar el token de autorización
        if (!$token) return ['status' => 'error', 'message' => 'Authorization token required'];
        if (!self::isAuthenticated($token)) return ['status' => 'error', 'message' => 'Access denied: Invalid token'];

        // Procesar las solicitudes de la API
        switch ($method) {
            case 'GET':
                if ($action === 'players') return self::getAllPlayersWithStats();
                break;
            case 'POST':
                if ($action === 'players') return self::addPlayer($data);
                break;
            case 'PUT':
                if ($action === 'players' && $id) return self::updatePlayer($id, $data);
                break;
            case 'DELETE':
                if ($action === 'players' && $id) return self::deletePlayer($id);
                break;
            default:
                return ['status' => 'error', 'message' => 'Method not allowed'];
        }

        return ['status' => 'error', 'message' => 'Invalid action'];
    }
}
