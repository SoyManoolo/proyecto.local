<?php
/**
 * Controlador de Administración
 *
 * Este controlador maneja todas las operaciones relacionadas con la administración
 * de jugadores, incluyendo la obtención, actualización, creación y eliminación de jugadores
 * y sus estadísticas. También verifica la autenticación de usuarios.
 */

require_once __DIR__ . '/DatabaseController.php';
require_once __DIR__ . '/UserController.php';

class AdminController {
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $connection;

    /**
     * Constructor que inicializa la conexión a la base de datos
     */
    public function __construct() {
        $this->connection = DatabaseController::connect();
    }

    /**
     * Verifica si un usuario está autenticado mediante un token JWT
     *
     * @param string $token JWT token
     * @return bool True si el usuario está autenticado
     */
    public static function isAuthenticated($token) {
        if (!$token) {
            return false;
        }

        try {
            $secretKey = UserController::$secretKey;
            $key = new \Firebase\JWT\Key($secretKey, 'HS256');
            $decoded = \Firebase\JWT\JWT::decode($token, $key);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtiene todos los jugadores con sus estadísticas
     *
     * @return array Respuesta con los datos de los jugadores
     */
    public static function getAllPlayersWithStats() {
        $db = DatabaseController::connect();

        // Consulta SQL para obtener jugadores y sus estadísticas
        $query = $db->query("
            SELECT 
                p.player_id,
                p.name, 
                p.surname, 
                p.birthday, 
                p.height, 
                s.stats_id,
                s.def, 
                s.spd, 
                s.off, 
                s.pass, 
                s.drb, 
                s.shoot
            FROM Player p
            JOIN Stats s ON p.player_id = s.player_id
            ORDER BY p.name ASC;
        ");

        $players = $query->fetchAll(PDO::FETCH_ASSOC);

        return [
            'status' => 'success',
            'data' => $players,
            'count' => count($players)
        ];
    }

    /**
     * Actualiza la información de un jugador y sus estadísticas
     *
     * @param int $playerId ID del jugador
     * @param array $playerData Datos del jugador a actualizar
     * @return array Respuesta con estado y mensaje
     */
    public static function updatePlayer($playerId, $playerData) {
        $db = DatabaseController::connect();

        try {
            // Inicia una transacción para asegurar la integridad de los datos
            $db->beginTransaction();

            // Actualiza la información básica del jugador
            $stmt = $db->prepare("
                UPDATE Player 
                SET name = :name, 
                    surname = :surname, 
                    birthday = :birthday, 
                    height = :height
                WHERE player_id = :player_id
            ");

            $stmt->bindParam(':name', $playerData['name'], PDO::PARAM_STR);
            $stmt->bindParam(':surname', $playerData['surname'], PDO::PARAM_STR);
            $stmt->bindParam(':birthday', $playerData['birthday'], PDO::PARAM_STR);
            $stmt->bindParam(':height', $playerData['height'], PDO::PARAM_STR);
            $stmt->bindParam(':player_id', $playerId, PDO::PARAM_INT);
            $stmt->execute();

            // Actualiza las estadísticas del jugador
            $stmt = $db->prepare("
                UPDATE Stats 
                SET def = :def, 
                    spd = :spd, 
                    off = :off, 
                    pass = :pass, 
                    drb = :drb, 
                    shoot = :shoot
                WHERE player_id = :player_id
            ");

            $stmt->bindParam(':def', $playerData['def'], PDO::PARAM_INT);
            $stmt->bindParam(':spd', $playerData['spd'], PDO::PARAM_INT);
            $stmt->bindParam(':off', $playerData['off'], PDO::PARAM_INT);
            $stmt->bindParam(':pass', $playerData['pass'], PDO::PARAM_INT);
            $stmt->bindParam(':drb', $playerData['drb'], PDO::PARAM_INT);
            $stmt->bindParam(':shoot', $playerData['shoot'], PDO::PARAM_INT);
            $stmt->bindParam(':player_id', $playerId, PDO::PARAM_INT);
            $stmt->execute();

            // Confirma la transacción
            $db->commit();

            return [
                'status' => 'success',
                'message' => 'Player updated successfully'
            ];
        } catch (PDOException $e) {
            // Revierte la transacción en caso de error
            $db->rollBack();

            return [
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Añade un nuevo jugador con sus estadísticas
     *
     * @param array $playerData Datos del jugador a añadir
     * @return array Respuesta con estado y mensaje
     */
    public static function addPlayer($playerData) {
        $db = DatabaseController::connect();

        try {
            // Inicia una transacción para asegurar la integridad de los datos
            $db->beginTransaction();

            // Inserta la información básica del jugador
            $stmt = $db->prepare("
                INSERT INTO Player (name, surname, birthday, height)
                VALUES (:name, :surname, :birthday, :height)
            ");

            $stmt->bindParam(':name', $playerData['name'], PDO::PARAM_STR);
            $stmt->bindParam(':surname', $playerData['surname'], PDO::PARAM_STR);
            $stmt->bindParam(':birthday', $playerData['birthday'], PDO::PARAM_STR);
            $stmt->bindParam(':height', $playerData['height'], PDO::PARAM_STR);
            $stmt->execute();

            // Obtiene el ID del jugador recién insertado
            $playerId = $db->lastInsertId();

            // Inserta las estadísticas del jugador
            $stmt = $db->prepare("
                INSERT INTO Stats (def, spd, off, pass, drb, shoot, player_id)
                VALUES (:def, :spd, :off, :pass, :drb, :shoot, :player_id)
            ");

            $stmt->bindParam(':def', $playerData['def'], PDO::PARAM_INT);
            $stmt->bindParam(':spd', $playerData['spd'], PDO::PARAM_INT);
            $stmt->bindParam(':off', $playerData['off'], PDO::PARAM_INT);
            $stmt->bindParam(':pass', $playerData['pass'], PDO::PARAM_INT);
            $stmt->bindParam(':drb', $playerData['drb'], PDO::PARAM_INT);
            $stmt->bindParam(':shoot', $playerData['shoot'], PDO::PARAM_INT);
            $stmt->bindParam(':player_id', $playerId, PDO::PARAM_INT);
            $stmt->execute();

            // Confirma la transacción
            $db->commit();

            return [
                'status' => 'success',
                'message' => 'Player added successfully',
                'player_id' => $playerId
            ];
        } catch (PDOException $e) {
            // Revierte la transacción en caso de error
            $db->rollBack();

            return [
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Elimina un jugador y sus estadísticas
     *
     * @param int $playerId ID del jugador
     * @return array Respuesta con estado y mensaje
     */
    public static function deletePlayer($playerId) {
        $db = DatabaseController::connect();

        try {
            // Inicia una transacción para asegurar la integridad de los datos
            $db->beginTransaction();

            // Elimina primero las estadísticas del jugador (debido a la restricción de clave foránea)
            $stmt = $db->prepare("DELETE FROM Stats WHERE player_id = :player_id");
            $stmt->bindParam(':player_id', $playerId, PDO::PARAM_INT);
            $stmt->execute();

            // Elimina el jugador
            $stmt = $db->prepare("DELETE FROM Player WHERE player_id = :player_id");
            $stmt->bindParam(':player_id', $playerId, PDO::PARAM_INT);
            $stmt->execute();

            // Confirma la transacción
            $db->commit();

            return [
                'status' => 'success',
                'message' => 'Player deleted successfully'
            ];
        } catch (PDOException $e) {
            // Revierte la transacción en caso de error
            $db->rollBack();

            return [
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Procesa las solicitudes API para los endpoints de administración
     *
     * @param string $method Método HTTP
     * @param string $action Acción a realizar
     * @param mixed $id ID del recurso (si aplica)
     * @param array $data Datos de la solicitud (si aplica)
     * @return array Datos de respuesta
     */
    public static function processApiRequest($method, $action, $id = null, $data = null) {
        // Obtiene el token y verifica la autenticación
        $authHeader = '';

        // Intenta obtener los encabezados usando getallheaders() si está disponible
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 
                         (isset($headers['authorization']) ? $headers['authorization'] : '');
        } else {
            // Alternativa para servidores sin getallheaders()
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['HTTP_X_AUTHORIZATION'])) {
                $authHeader = $_SERVER['HTTP_X_AUTHORIZATION'];
            }
        }

        // Extrae el token del encabezado
        $token = null;
        if (!empty($authHeader) && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }

        // Verifica si existe un token
        if (!$token) {
            return [
                'status' => 'error',
                'message' => 'Authorization token required'
            ];
        }

        // Valida el token y verifica si el usuario está autenticado
        if (!self::isAuthenticated($token)) {
            return [
                'status' => 'error',
                'message' => 'Access denied: Invalid token'
            ];
        }

        // Procesa la solicitud según el método y la acción
        switch ($method) {
            case 'GET':
                if ($action === 'players') {
                    // Obtiene todos los jugadores con sus estadísticas
                    return self::getAllPlayersWithStats();
                }
                break;

            case 'POST':
                if ($action === 'players') {
                    // Añade un nuevo jugador
                    return self::addPlayer($data);
                }
                break;

            case 'PUT':
                if ($action === 'players' && $id) {
                    // Actualiza un jugador existente
                    return self::updatePlayer($id, $data);
                }
                break;

            case 'DELETE':
                if ($action === 'players' && $id) {
                    // Elimina un jugador
                    return self::deletePlayer($id);
                }
                break;

            default:
                return [
                    'status' => 'error',
                    'message' => 'Method not allowed'
                ];
        }

        // Si la acción no es válida
        return [
            'status' => 'error',
            'message' => 'Invalid action'
        ];
    }
}
