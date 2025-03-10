<?php

    use Firebase\JWT\JWT;    

    class UserController{

        private static $secretKey = "claveSecreta"; //Clave secreta para firmar el token
        private $connection; //Conexión a la base de datos

        public function __construct(){ //Constructor de la clase
            $this->connection = DatabaseController::connect(); //Obtiene la conexión a la base de datos

        }

        public static function signUp($username, $email, $name, $surname, $password) { //Función para registrar un usuario
            if((new self)->exist($email)) { //Verifica si el usuario ya existe
                echo "Account already exists";
                return;
            } else {
                try {
                    $sql = "INSERT into users (username, email, name, surname, password) VALUES (:username, :email, :name, :surname, :password)"; //Consulta SQL

                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT); //Encripta la contraseña

                    $stmt = (new self)->connection->prepare($sql); //Prepara la consulta
                    $stmt-> bindValue(':username', $username); //Asigna los valores a los parámetros
                    $stmt-> bindValue(':email', $email);
                    $stmt-> bindValue(':name', $name);
                    $stmt-> bindValue(':surname', $surname);
                    $stmt-> bindValue(':password', $hashedPassword);
                    $stmt->setFetchMode(PDO::FETCH_OBJ); //Establece el modo de recuperación de datos
                    $stmt-> execute(); //Ejecuta la consulta

                    return true;
                } catch(PDOException $error) {
                    echo $sql . "<br>" . $error->getMessage();
                    return false;
                }
            }
        }

        public static function signIn($emailUsername, $password) {
            $db = (new self())->connection;
        
            try {
                // Buscar al usuario por email o nombre de usuario
                $sql = "SELECT id, username, email, password FROM users WHERE email = :email OR username = :username";
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':email', $emailUsername);
                $stmt->bindValue(':username', $emailUsername);
                $stmt->execute();
        
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
                // Si el usuario no existe, devolver mensaje de error
                if (!$user) {
                    return "Account does not exist.";
                }
        
                // ✅ Comparar la contraseña ingresada con el hash almacenado
                if (!password_verify($password, $user['password'])) {
                    return "Incorrect password.";
                }
        
                // ✅ Generar token JWT para mantener la sesión
                $token = self::generateToken($user['id'], $user['username'], $user['email']);
        
                // ✅ Guardar el token en una cookie
                setcookie("token", $token, time() + 3600, "/", "", false, true);
        
                return true; // Éxito
            } catch (PDOException $error) {
                return "Database error: " . $error->getMessage();
            }
        }
        
        public static function exist($emailUsername): bool{ //Función para verificar si un usuario ya existe
            if (str_contains($emailUsername, "@")) { //Verifica si el parámetro es un email
                try {
                    $sql = "SELECT * FROM users WHERE email = :email"; //Consulta SQL

                    $stmt = (new self)->connection->prepare($sql);
                    $stmt-> bindValue(':email', $emailUsername);
                    $stmt-> setFetchMode(PDO::FETCH_OBJ);
                    $stmt-> execute();

                    $result = $stmt->fetch(); //Obtiene el resultado de la consulta

                    return (bool) $result; //Retorna si el resultado es verdadero o falso

                } catch (PDOException $error) { //Manejo de errores
                    echo $sql . "<br>" . $error->getMessage();
                }
            } else { //Si no es un email, entonces es un nombre de usuario
                try {
                    $sql = "SELECT * FROM users WHERE username = :username"; //Consulta SQL

                    $stmt = (new self)->connection->prepare($sql);
                    $stmt-> bindValue(':username', $emailUsername);
                    $stmt->setFetchMode(PDO::FETCH_OBJ);
                    $stmt->execute();

                    $result = $stmt->fetch();

                    return (bool) $result;

                } catch (PDOException $error) {
                    echo $sql . "<br>" . $error->getMessage();
                }
            }
        }

        public function generateToken($id, $username, $email) { //Función para generar un token JWT
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
    }
?>