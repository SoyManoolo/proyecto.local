<?php
/**
 * Controlador de Jugadores
 *
 * Este controlador maneja todas las operaciones relacionadas con los jugadores,
 * incluyendo la obtención de jugadores individuales y listas de jugadores.
 * También procesa las solicitudes API relacionadas con los jugadores.
 */

require_once __DIR__ . '/../controller/DatabaseController.php';

class PlayerController {
    /**
     * Obtiene todos los jugadores de la base de datos
     *
     * @return array Respuesta JSON con los datos de los jugadores
     */
    public static function getAllPlayers() {
        $db = DatabaseController::connect();

        // Consulta SQL para obtener todos los jugadores con sus estadísticas
        $query = $db->query("
            SELECT 
                p.player_id,
                p.name, 
                p.surname, 
                p.birthday, 
                p.height, 
                s.def, 
                s.spd, 
                s.off, 
                s.pass, 
                s.drb, 
                s.shoot
            FROM Player p
            JOIN Stats s ON p.player_id = s.player_id;
        ");

        $players = $query->fetchAll(PDO::FETCH_ASSOC);

        return [
            'status' => 'success',
            'data' => $players,
            'count' => count($players)
        ];
    }

    /**
     * Obtiene un jugador por su ID
     *
     * @param int $id ID del jugador
     * @return array Respuesta JSON con los datos del jugador
     */
    public static function getPlayerById($id) {
        $db = DatabaseController::connect();

        // Consulta SQL para obtener un jugador específico con sus estadísticas
        $stmt = $db->prepare("
            SELECT 
                p.player_id,
                p.name, 
                p.surname, 
                p.birthday, 
                p.height, 
                s.def, 
                s.spd, 
                s.off, 
                s.pass, 
                s.drb, 
                s.shoot
            FROM Player p
            JOIN Stats s ON p.player_id = s.player_id
            WHERE p.player_id = :id
        ");

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $player = $stmt->fetch(PDO::FETCH_ASSOC);

        // Devuelve los datos del jugador si se encuentra, o un mensaje de error si no
        if ($player) {
            return [
                'status' => 'success',
                'data' => $player
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Player not found'
            ];
        }
    }

    /**
     * Procesa las solicitudes API para los endpoints de jugadores
     *
     * @param string $method Método HTTP
     * @param string $action Acción a realizar
     * @param mixed $id ID del jugador (si aplica)
     * @param array $data Datos de la solicitud (si aplica)
     * @return array Datos de respuesta
     */
    public static function processApiRequest($method, $action, $id = null, $data = null) {
        switch ($method) {
            case 'GET':
                if ($action === 'get' && $id) {
                    // Obtiene un jugador específico por su ID
                    return self::getPlayerById($id);
                } else {
                    // Obtiene todos los jugadores
                    return self::getAllPlayers();
                }
                break;

            default:
                return [
                    'status' => 'error',
                    'message' => 'Method not allowed'
                ];
        }
    }
}
