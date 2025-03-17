<?php

require_once __DIR__ . '/../controller/DatabaseController.php';

// Controlador para manejar las solicitudes relacionadas con los jugadores
class PlayerController {

    // Obtiene todos los jugadores con sus estadísticas
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

        // Obtiene los resultados de la consulta
        $players = $query->fetchAll(PDO::FETCH_ASSOC);

        return [
            'status' => 'success',
            'data' => $players,
            'count' => count($players)
        ];
    }

    // Obtiene un jugador específico por su ID
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

        // Ejecuta la consulta con el ID del jugador como parámetro
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Obtiene los resultados de la consulta
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

    // Procesa las solicitudes de la API relacionadas con los jugadores
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
