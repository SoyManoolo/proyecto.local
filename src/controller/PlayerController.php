<?php

require_once __DIR__ . '/../controller/DatabaseController.php';

class PlayerController {
    /**
     * Get all players from the database
     * @return array JSON response with players data
     */
    public static function getAllPlayers() {
        $db = DatabaseController::connect();

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
     * Get a player by ID
     * @param int $id Player ID
     * @return array JSON response with player data
     */
    public static function getPlayerById($id) {
        $db = DatabaseController::connect();

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
     * Process API requests for player endpoints
     * @param string $method HTTP method
     * @param string $action Action to perform
     * @param mixed $id Player ID (if applicable)
     * @param array $data Request data (if applicable)
     * @return array Response data
     */
    public static function processApiRequest($method, $action, $id = null, $data = null) {
        switch ($method) {
            case 'GET':
                if ($action === 'get' && $id) {
                    return self::getPlayerById($id);
                } else {
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
