<?php

require_once __DIR__ . '/../controller/DatabaseController.php';

class PlayerController {
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

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
