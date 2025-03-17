<?php

class DatabaseController
{
    /**
     * Host del servidor de base de datos
     * @var string
     */
    private static $host = "localhost";

    /**
     * Nombre de usuario para la conexión a la base de datos
     * @var string
     */
    private static $username = "root";

    /**
     * Contraseña para la conexión a la base de datos
     * @var string
     */
    private static $password = "Doblemanuel0426.";

    /**
     * Nombre de la base de datos
     * @var string
     */
    private static $dbname = "BlueLock";

    /**
     * Opciones de configuración para la conexión PDO
     * @var array
     */
    private static $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    );

    /**
     * Instancia única de la clase (patrón Singleton)
     * @var DatabaseController
     */
    private static $instance = null;

    /**
     * Obtiene la instancia única de la clase (patrón Singleton)
     *
     * @return DatabaseController Instancia única de la clase
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new DatabaseController();
        }

        return self::$instance;
    }

    /**
     * Establece una conexión a la base de datos
     *
     * @return PDO|null Objeto PDO de conexión o null en caso de error
     */
    public static function connect()
    {
        try {
            // Crear y devolver una nueva conexión PDO
            $connection = new PDO('mysql:host=' . self::$host . ';dbname=' . self::$dbname, self::$username, self::$password, self::$options);
            return $connection;
        } catch (PDOException $error) {
            // Mostrar mensaje de error en caso de fallo en la conexión
            echo "Error de conexión: " . $error->getMessage();
            return null;
        }
    }
}
