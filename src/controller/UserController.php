<?php
/**
 * Controlador de Usuarios
 *
 * Este controlador maneja todas las operaciones relacionadas con los usuarios,
 * incluyendo registro, inicio de sesión, actualización de perfil y verificación de roles.
 * También se encarga de la generación y validación de tokens JWT para la autenticación.
 */
require_once __DIR__ . "../DatabaseController.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class UserController
{
    /**
     * Clave secreta para firmar los tokens JWT
     * @var string
     */
    public static $secretKey = "claveSecreta"; //Clave secreta para firmar el token

    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $connection; //Conexión a la base de datos

    /**
     * Constructor que inicializa la conexión a la base de datos
     */
    public function __construct()
    { //Constructor de la clase
        $this->connection = DatabaseController::connect(); //Obtiene la conexión a la base de datos
    }

    /**
     * Registra un nuevo usuario en el sistema
     *
     * @param string $username Nombre de usuario
     * @param string $email Correo electrónico
     * @param string $name Nombre
     * @param string $surname Apellido
     * @param string $password Contraseña
     * @return array Respuesta con estado y mensaje
     */
    public static function signUp($username, $email, $name, $surname, $password)
    { //Función para registrar un usuario
        if ((new self)->exist($email)) { //Verifica si el usuario ya existe
            return [
                'status' => 'error',
                'message' => 'Account already exists'
            ];
        } else {
            try {
                $sql = "INSERT into Users (username, email, name, surname, password) VALUES (:username, :email, :name, :surname, :password)"; //Consulta SQL

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT); //Encripta la contraseña

                $stmt = (new self)->connection->prepare($sql); //Prepara la consulta
                $stmt->bindValue(':username', $username); //Asigna los valores a los parámetros
                $stmt->bindValue(':email', $email);
                $stmt->bindValue(':name', $name);
                $stmt->bindValue(':surname', $surname);
                $stmt->bindValue(':password', $hashedPassword);
                $stmt->setFetchMode(PDO::FETCH_OBJ); //Establece el modo de recuperación de datos
                $stmt->execute(); //Ejecuta la consulta

                return [
                    'status' => 'success',
                    'message' => 'User registered successfully'
                ];
            } catch (PDOException $error) {
                return [
                    'status' => 'error',
                    'message' => 'Database error: ' . $error->getMessage()
                ];
            }
        }
    }

    /**
     * Inicia sesión de un usuario verificando sus credenciales
     *
     * @param string $emailUsername Correo electrónico o nombre de usuario
     * @param string $password Contraseña
     * @return array Respuesta con estado, mensaje y token si es exitoso
     */
    public static function signIn($emailUsername, $password)
    {
        $db = (new self())->connection;

        try {
            // Buscar al usuario por email o nombre de usuario
            $sql = "SELECT id, username, email, password, name, surname FROM Users WHERE email = :email OR username = :username";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':email', $emailUsername);
            $stmt->bindValue(':username', $emailUsername);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Si el usuario no existe, devolver mensaje de error
            if (!$user) {
                return [
                    'status' => 'error',
                    'message' => 'Account does not exist.'
                ];
            }

            // Comparar la contraseña ingresada con el hash almacenado
            if (!password_verify($password, $user['password'])) {
                return [
                    'status' => 'error',
                    'message' => 'Incorrect password.'
                ];
            }

            // Generar token JWT para mantener la sesión
            $token = self::generateToken($user['id'], $user['username'], $user['email']);

            // Registrar el inicio de sesión en el log
            error_log('User logged in: ' . $user['username'] . ' (ID: ' . $user['id'] . ')');
            error_log('User data: ' . json_encode([
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'name' => $user['name'],
                'surname' => $user['surname']
            ]));

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
            return [
                'status' => 'error',
                'message' => 'Database error: ' . $error->getMessage()
            ];
        }
    }

    /**
     * Verifica si un usuario ya existe en la base de datos
     *
     * @param string $emailUsername Correo electrónico o nombre de usuario
     * @return bool True si el usuario existe, false en caso contrario
     */
    public static function exist($emailUsername): bool
    { //Función para verificar si un usuario ya existe
        if (str_contains($emailUsername, "@")) { //Verifica si el parámetro es un email
            try {
                $sql = "SELECT * FROM users WHERE email = :email"; //Consulta SQL

                $stmt = (new self)->connection->prepare($sql);
                $stmt->bindValue(':email', $emailUsername);
                $stmt->setFetchMode(PDO::FETCH_OBJ);
                $stmt->execute();

                $result = $stmt->fetch(); //Obtiene el resultado de la consulta

                return (bool) $result; //Retorna si el resultado es verdadero o falso

            } catch (PDOException $error) { //Manejo de errores
                echo $sql . "<br>" . $error->getMessage();
                return false;
            }
        } else { //Si no es un email, entonces es un nombre de usuario
            try {
                $sql = "SELECT * FROM users WHERE username = :username"; //Consulta SQL

                $stmt = (new self)->connection->prepare($sql);
                $stmt->bindValue(':username', $emailUsername);
                $stmt->setFetchMode(PDO::FETCH_OBJ);
                $stmt->execute();

                $result = $stmt->fetch();

                return (bool) $result;
            } catch (PDOException $error) {
                echo $sql . "<br>" . $error->getMessage();
                return false;
            }
        }
    }

    /**
     * Genera un token JWT para la autenticación del usuario
     *
     * @param int $id ID del usuario
     * @param string $username Nombre de usuario
     * @param string $email Correo electrónico
     * @return string Token JWT generado
     */
    public static function generateToken($id, $username, $email)
    { //Función para generar un token JWT
        $payload = [
            'id' => $id,
            'username' => $username,
            'email' => $email,
            'iat' => time(),
            'exp' => time() + 3600
        ];

        $jwt = JWT::encode($payload, self::$secretKey, 'HS256'); //Firma el token
        return $jwt;
    }

    /**
     * Procesa las solicitudes API para los endpoints de usuario
     *
     * @param string $method Método HTTP
     * @param string $action Acción a realizar
     * @param array $data Datos de la solicitud
     * @return array Datos de respuesta
     */
    public static function processApiRequest($method, $action, $data = null) {
        switch ($method) {
            case 'POST':
                if ($action === 'login') {
                    // Aceptar tanto datos JSON como datos de formulario
                    $emailUsername = $data['user'] ?? '';
                    $password = $data['password'] ?? '';

                    return self::signIn($emailUsername, $password);
                } elseif ($action === 'register') {
                    $name = $data['name'] ?? '';
                    $surname = $data['surname'] ?? '';
                    $username = $data['user'] ?? '';
                    $email = $data['email'] ?? '';
                    $password = $data['password'] ?? '';

                    return self::signUp($username, $email, $name, $surname, $password);
                } elseif ($action === 'update') {
                    // Get token from Authorization header
                    $token = self::getTokenFromRequest();

                    if (!$token) {
                        return [
                            'status' => 'error',
                            'message' => 'Authorization token required'
                        ];
                    }

                    // Validate token and get user ID
                    try {
                        // Attempt to decode the token
                        $key = new Key(self::$secretKey, 'HS256');
                        $decoded = JWT::decode($token, $key);

                        $userId = $decoded->id;

                        // Update user profile
                        return self::updateUserProfile($userId, $data);
                    } catch (Exception $e) {
                        return [
                            'status' => 'error',
                            'message' => 'Invalid token: ' . $e->getMessage()
                        ];
                    }
                } elseif ($action === 'isAdmin') {
                    // Get token from Authorization header
                    $token = self::getTokenFromRequest();

                    if (!$token) {
                        return [
                            'status' => 'error',
                            'message' => 'Authorization token required'
                        ];
                    }

                    // Validate token and get user ID
                    try {
                        // Attempt to decode the token
                        $key = new Key(self::$secretKey, 'HS256');
                        $decoded = JWT::decode($token, $key);

                        $userId = $decoded->id;

                        // Check if user is admin
                        return self::isAdmin($userId);
                    } catch (Exception $e) {
                        return [
                            'status' => 'error',
                            'message' => 'Invalid token: ' . $e->getMessage()
                        ];
                    }
                }
                break;

            case 'GET':
                if ($action === 'profile') {
                    // Get token from Authorization header
                    $token = self::getTokenFromRequest();

                    if (!$token) {
                        return [
                            'status' => 'error',
                            'message' => 'Authorization token required'
                        ];
                    }

                    // Validate token and get user data
                    try {
                        // Attempt to decode the token
                        $key = new Key(self::$secretKey, 'HS256');
                        $decoded = JWT::decode($token, $key);

                        $userId = $decoded->id;

                        // Get user profile data
                        return self::getUserProfile($userId);
                    } catch (Exception $e) {
                        return [
                            'status' => 'error',
                            'message' => 'Invalid token: ' . $e->getMessage()
                        ];
                    }
                } elseif ($action === 'check-admin') {
                    // Get token from Authorization header
                    $token = self::getTokenFromRequest();

                    if (!$token) {
                        return [
                            'status' => 'error',
                            'message' => 'Authorization token required'
                        ];
                    }

                    // Validate token and get user ID
                    try {
                        // Attempt to decode the token
                        $key = new Key(self::$secretKey, 'HS256');
                        $decoded = JWT::decode($token, $key);

                        $userId = $decoded->id;

                        // Check if user is admin
                        $result = self::isAdmin($userId);

                        if ($result['status'] === 'success') {
                            return [
                                'status' => 'success',
                                'isAdmin' => true
                            ];
                        } else {
                            return [
                                'status' => 'success',
                                'isAdmin' => false
                            ];
                        }
                    } catch (Exception $e) {
                        return [
                            'status' => 'error',
                            'message' => 'Invalid token: ' . $e->getMessage()
                        ];
                    }
                }
                break;

            default:
                return [
                    'status' => 'error',
                    'message' => 'Method not allowed'
                ];
        }

        return [
            'status' => 'error',
            'message' => 'Invalid action'
        ];
    }

    /**
     * Obtiene los datos del perfil de un usuario
     *
     * @param int $userId ID del usuario
     * @return array Respuesta con estado, mensaje y datos del usuario
     */
    private static function getUserProfile($userId) {
        try {
            $sql = "SELECT id, username, email, name, surname FROM Users WHERE id = :id";
            $stmt = (new self)->connection->prepare($sql);
            $stmt->bindValue(':id', $userId);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return [
                    'status' => 'error',
                    'message' => 'User not found'
                ];
            }

            return [
                'status' => 'success',
                'message' => 'Profile data retrieved successfully',
                'data' => $user
            ];
        } catch (PDOException $error) {
            return [
                'status' => 'error',
                'message' => 'Database error: ' . $error->getMessage()
            ];
        }
    }

    /**
     * Actualiza los datos del perfil de un usuario
     *
     * @param int $userId ID del usuario
     * @param array $data Datos del usuario a actualizar
     * @return array Respuesta con estado y mensaje
     */
    private static function updateUserProfile($userId, $data) {
        try {
            // Check if password change is requested
            if (!empty($data['currentPassword']) && !empty($data['newPassword'])) {
                // Verify current password
                $sql = "SELECT password FROM Users WHERE id = :id";
                $stmt = (new self)->connection->prepare($sql);
                $stmt->bindValue(':id', $userId);
                $stmt->execute();

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user || !password_verify($data['currentPassword'], $user['password'])) {
                    return [
                        'status' => 'error',
                        'message' => 'Current password is incorrect'
                    ];
                }

                // Update user data with new password
                $sql = "UPDATE Users SET name = :name, surname = :surname, password = :password WHERE id = :id";
                $stmt = (new self)->connection->prepare($sql);
                $stmt->bindValue(':name', $data['name']);
                $stmt->bindValue(':surname', $data['surname']);
                $stmt->bindValue(':password', password_hash($data['newPassword'], PASSWORD_DEFAULT));
                $stmt->bindValue(':id', $userId);
                $stmt->execute();
            } else {
                // Update user data without changing password
                $sql = "UPDATE Users SET name = :name, surname = :surname WHERE id = :id";
                $stmt = (new self)->connection->prepare($sql);
                $stmt->bindValue(':name', $data['name']);
                $stmt->bindValue(':surname', $data['surname']);
                $stmt->bindValue(':id', $userId);
                $stmt->execute();
            }

            // Get updated user data
            $sql = "SELECT id, username, email FROM Users WHERE id = :id";
            $stmt = (new self)->connection->prepare($sql);
            $stmt->bindValue(':id', $userId);
            $stmt->execute();

            $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'user' => $updatedUser
            ];
        } catch (PDOException $error) {
            return [
                'status' => 'error',
                'message' => 'Database error: ' . $error->getMessage()
            ];
        }
    }

    /**
     * Verifica si un usuario tiene rol de administrador
     *
     * @param int $userId ID del usuario
     * @return array Respuesta con estado y mensaje
     */
    private static function isAdmin($userId) {
        try {
            $sql = "SELECT role FROM Users WHERE id = :id";
            $stmt = (new self)->connection->prepare($sql);
            $stmt->bindValue(':id', $userId);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return [
                    'status' => 'error',
                    'message' => 'User not found'
                ];
            }

            if ($user['role'] === 'admin') {
                return [
                    'status' => 'success',
                    'message' => 'User is admin'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'User is not admin'
                ];
            }
        } catch (PDOException $error) {
            return [
                'status' => 'error',
                'message' => 'Database error: ' . $error->getMessage()
            ];
        }
    }

    /**
     * Extrae el token JWT de los encabezados de la solicitud
     *
     * @return string|null Token o null si no se encuentra
     */
    private static function getTokenFromRequest() {
        // Try to get headers using getallheaders() if available
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 
                         (isset($headers['authorization']) ? $headers['authorization'] : '');
        } else {
            // Fallback for servers without getallheaders()
            $authHeader = '';

            // Try to get the Authorization header from $_SERVER
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['HTTP_X_AUTHORIZATION'])) {
                $authHeader = $_SERVER['HTTP_X_AUTHORIZATION'];
            }
        }

        // Extract token from header
        if (!empty($authHeader) && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
