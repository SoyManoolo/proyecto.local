<?php
//Controlador para manejar las solicitudes relacionadas con los usuarios
require_once __DIR__ . "/DatabaseController.php";

// Importar la clase JWT de Firebase
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class UserController
{
    // Clave secreta para firmar tokens JWT
    public static $secretKey = "claveSecreta";
    private $connection;

    // Constructor para inicializar la conexión a la base de datos
    public function __construct()
    {
        $this->connection = DatabaseController::connect();
    }

    // Función para registrar un nuevo usuario
    public static function signUp($username, $email, $name, $surname, $password)
    {
        // Verificar si el usuario ya existe
        if ((new self)->exist($email)) {
            return ['status' => 'error', 'message' => 'Account already exists'];
        }

        try {
            // Hash de la contraseña antes de guardarla en la base de datos
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            // Preparar la consulta SQL para insertar un nuevo usuario
            $stmt = (new self)->connection->prepare("INSERT into Users (username, email, name, surname, password) VALUES (:username, :email, :name, :surname, :password)");
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':surname', $surname);
            $stmt->bindValue(':password', $hashedPassword);
            $stmt->execute();

            // Devolvemos token para permitir login automático tras registro
            $userId = (new self)->connection->lastInsertId();
            $token = self::generateToken($userId, $username, $email);

            return [
                'status' => 'success',
                'message' => 'User registered successfully',
                'token' => $token,
                'user' => [
                    'id' => $userId,
                    'username' => $username,
                    'email' => $email,
                    'name' => $name,
                    'surname' => $surname
                ]
            ];
        } catch (PDOException $error) {
            return ['status' => 'error', 'message' => 'Database error: ' . $error->getMessage()];
        }
    }

    public static function signIn($emailUsername, $password)
    {
        try {
            $db = (new self())->connection;
            $stmt = $db->prepare("SELECT id, username, email, password, name, surname FROM Users WHERE email = :email OR username = :username");
            $stmt->bindValue(':email', $emailUsername);
            $stmt->bindValue(':username', $emailUsername);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['status' => 'error', 'message' => 'Account does not exist.'];
            }

            if (!password_verify($password, $user['password'])) {
                return ['status' => 'error', 'message' => 'Incorrect password.'];
            }

            $token = self::generateToken($user['id'], $user['username'], $user['email']);

            return [
                'status' => 'success',
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'surname' => $user['surname']
                ]
            ];
        } catch (PDOException $error) {
            return ['status' => 'error', 'message' => 'Database error: ' . $error->getMessage()];
        }
    }

    public static function exist($emailUsername): bool
    {
        $isEmail = str_contains($emailUsername, "@");
        $field = $isEmail ? 'email' : 'username';

        try {
            $stmt = (new self)->connection->prepare("SELECT * FROM users WHERE $field = :value");
            $stmt->bindValue(':value', $emailUsername);
            $stmt->execute();
            return (bool) $stmt->fetch();
        } catch (PDOException $error) {
            return false;
        }
    }

    public static function generateToken($id, $username, $email)
    {
        $payload = [
            'id' => $id,
            'username' => $username,
            'email' => $email,
            'iat' => time(),
            'exp' => time() + 3600
        ];

        return JWT::encode($payload, self::$secretKey, 'HS256');
    }

    public static function processApiRequest($method, $action, $data = null)
    {
        header('Content-Type: application/json');

        if ($method === 'POST') {
            if ($action === 'login') {
                return self::signIn($data['user'] ?? '', $data['password'] ?? '');
            } elseif ($action === 'register') {
                return self::signUp(
                    $data['user'] ?? '',
                    $data['email'] ?? '',
                    $data['name'] ?? '',
                    $data['surname'] ?? '',
                    $data['password'] ?? ''
                );
            } elseif (in_array($action, ['update', 'isAdmin'])) {
                return self::processAuthenticatedRequest($action, $data);
            }
        } elseif ($method === 'GET' && in_array($action, ['profile', 'check-admin'])) {
            return self::processAuthenticatedRequest($action, $data);
        } elseif ($method === 'OPTIONS') {
            return ['status' => 'success', 'message' => 'CORS preflight request successful'];
        }

        return ['status' => 'error', 'message' => 'Invalid action or method'];
    }

    private static function processAuthenticatedRequest($action, $data = null)
    {
        $token = self::getTokenFromRequest();
        if (!$token) {
            return ['status' => 'error', 'message' => 'Authorization token required'];
        }

        try {
            $decoded = JWT::decode($token, new Key(self::$secretKey, 'HS256'));
            $userId = $decoded->id;

            switch ($action) {
                case 'update':
                    return self::updateUserProfile($userId, $data);

                case 'profile':
                    return self::getUserProfile($userId);
            }
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Invalid token: ' . $e->getMessage()];
        }

        return ['status' => 'error', 'message' => 'Unknown action'];
    }

    private static function getUserProfile($userId)
    {
        try {
            $stmt = (new self)->connection->prepare("SELECT id, username, email, name, surname FROM Users WHERE id = :id");
            $stmt->bindValue(':id', $userId);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user ?
                ['status' => 'success', 'message' => 'Profile data retrieved successfully', 'data' => $user] :
                ['status' => 'error', 'message' => 'User not found'];
        } catch (PDOException $error) {
            return ['status' => 'error', 'message' => 'Database error: ' . $error->getMessage()];
        }
    }

    private static function updateUserProfile($userId, $data)
    {
        try {
            $db = (new self)->connection;

            if (!empty($data['currentPassword']) && !empty($data['newPassword'])) {
                // Verify current password
                $stmt = $db->prepare("SELECT password FROM Users WHERE id = :id");
                $stmt->bindValue(':id', $userId);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user || !password_verify($data['currentPassword'], $user['password'])) {
                    return ['status' => 'error', 'message' => 'Current password is incorrect'];
                }

                // Update with new password
                $stmt = $db->prepare("UPDATE Users SET name = :name, surname = :surname, password = :password WHERE id = :id");
                $stmt->bindValue(':password', password_hash($data['newPassword'], PASSWORD_DEFAULT));
            } else {
                // Update without changing password
                $stmt = $db->prepare("UPDATE Users SET name = :name, surname = :surname WHERE id = :id");
            }

            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':surname', $data['surname']);
            $stmt->bindValue(':id', $userId);
            $stmt->execute();

            // Get updated user data
            $stmt = $db->prepare("SELECT id, username, email FROM Users WHERE id = :id");
            $stmt->bindValue(':id', $userId);
            $stmt->execute();
            $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);

            return ['status' => 'success', 'message' => 'Profile updated successfully', 'user' => $updatedUser];
        } catch (PDOException $error) {
            return ['status' => 'error', 'message' => 'Database error: ' . $error->getMessage()];
        }
    }

    private static function getTokenFromRequest()
    {
        // Try with getallheaders() if available
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        } else {
            // Fallback method
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_AUTHORIZATION'] ?? '';
        }

        return preg_match('/Bearer\s(\S+)/', $authHeader, $matches) ? $matches[1] : null;
    }
}
