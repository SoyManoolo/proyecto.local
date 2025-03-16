<?php

/**
 * Archivo principal de enrutamiento de la aplicación Blue Lock
 * Este archivo maneja todas las solicitudes entrantes, tanto para la API como para las páginas web
 */

// Configurar la visualización de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cargar Twig y traducciones
require_once __DIR__ . '/../src/config/twig.php';
$translations = require __DIR__ . '/../src/config/i18n.php';
$twig->addGlobal('translations', $translations);

// Cargar controladores
require_once __DIR__ . '/../src/controller/UserController.php';
require_once __DIR__ . '/../src/controller/PlayerController.php';
require_once __DIR__ . '/../src/controller/AdminController.php';

// Obtener y limpiar la URL solicitada
$request = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), "/");
$chunks = explode("/", $request);
$baseRoute = strtolower($chunks[0] ?? '');

// Determinar si es una solicitud a la API
$isApiRequest = $baseRoute === 'api';

// Manejo de solicitudes a la API
if ($isApiRequest) {
    // Configurar cabeceras para la API
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    // Manejar solicitudes OPTIONS (preflight)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    // Obtener componentes de la ruta
    $endpoint = $chunks[1] ?? '';
    $action = $chunks[2] ?? '';
    $id = $chunks[3] ?? null;
    $data = null;

    // Obtener datos de la solicitud
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = $_POST; // Fallback si el JSON falla
        }
    }

    // Procesar las solicitudes a la API
    switch ($endpoint) {
        case 'players':
            // Maneja solicitudes relacionadas con jugadores
            $response = PlayerController::processApiRequest($_SERVER['REQUEST_METHOD'], $action, $id, $data);
            break;

        case 'user':
            // Maneja solicitudes relacionadas con usuarios (login, registro, etc.)
            $response = UserController::processApiRequest($_SERVER['REQUEST_METHOD'], $action, $data);
            break;

        case 'admin':
            // Maneja solicitudes relacionadas con la administración
            $response = AdminController::processApiRequest($_SERVER['REQUEST_METHOD'], $action, $id, $data);
            break;

        default:
            // Si el endpoint no existe, devuelve un error 404
            http_response_code(404);
            $response = [
                'status' => 'error',
                'message' => 'Endpoint not found'
            ];
            break;
    }

    // Establecer código de respuesta HTTP según el estado
    if (isset($response['status']) && $response['status'] === 'error') {
        http_response_code(400);
    }

    // Devolver la respuesta como JSON
    echo json_encode($response);
    exit;
}

// Manejo de rutas normales (no API)
// Redirigir si la ruta es vacía o raíz
if ($request === '' || $request === '/') {
    header('Location: /home');
    exit;
}

// Procesar rutas normales
switch ($baseRoute) {
    case 'home':
        // Página principal que muestra todos los jugadores
        $players = PlayerController::getAllPlayers();
        echo $twig->render('home.twig', [
            'translations' => $translations,
            'players' => $players
        ]);
        break;

    case 'login':
        // Página de inicio de sesión
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Procesar el formulario de inicio de sesión
            $emailUsername = $_POST['user'] ?? '';
            $password = $_POST['password'] ?? '';

            $loginResult = UserController::signIn($emailUsername, $password);

            if ($loginResult['status'] === 'success') {
                // Redirigir a la página principal si el inicio de sesión es exitoso
                header('Location: /home');
                exit;
            } else {
                // Mostrar error si el inicio de sesión falla
                echo $twig->render('login.twig', [
                    'error' => $loginResult['message'],
                    'translations' => $translations
                ]);
            }
        } else {
            // Mostrar el formulario de inicio de sesión
            echo $twig->render('login.twig', ['translations' => $translations]);
        }
        break;

    case 'signup':
        // Página de registro
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Procesar el formulario de registro
            $name = $_POST['name'] ?? '';
            $surname = $_POST['surname'] ?? '';
            $username = $_POST['user'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $signupResult = UserController::signUp($username, $email, $name, $surname, $password);

            if ($signupResult === true) {
                // Redirigir a la página principal si el registro es exitoso
                header('Location: /home');
                exit;
            } else {
                // Mostrar error si el registro falla
                echo $twig->render('signup.twig', [
                    'translations' => $translations,
                    'error' => $signupResult
                ]);
            }
        } else {
            // Mostrar el formulario de registro
            echo $twig->render('signup.twig', ['translations' => $translations]);
        }
        break;

    case 'player':
        // Página de detalle de un jugador específico
        $id = $chunks[1] ?? null;
        if ($id) {
            $player = PlayerController::getPlayerById($id);
            if ($player) {
                // Mostrar la página de detalle del jugador
                echo $twig->render('player_detail.twig', [
                    'translations' => $translations,
                    'playerId' => $id
                ]);
            } else {
                // Mostrar página 404 si el jugador no existe
                http_response_code(404);
                echo $twig->render('404.twig', ['translations' => $translations]);
            }
        } else {
            // Mostrar página 404 si no se proporciona ID
            http_response_code(404);
            echo $twig->render('404.twig', ['translations' => $translations]);
        }
        break;

    case 'profile':
        // Página de perfil de usuario
        // Renderizar la página de perfil (la autenticación se manejará en el frontend)
        echo $twig->render('profile.twig', ['translations' => $translations]);
        break;

    case 'admin':
        // Página del panel de administración
        // Renderizar la página de administración (verificando autenticación)
        $token = null;

        // Obtener token de la cookie o del localStorage (mediante JavaScript)
        if (isset($_COOKIE['token'])) {
            $token = $_COOKIE['token'];
        }

        // Renderizar la página, la autenticación se verificará en el frontend
        echo $twig->render('admin.twig', ['translations' => $translations]);
        break;

    default:
        // Página 404 para rutas no encontradas
        http_response_code(404);
        echo $twig->render('404.twig', ['translations' => $translations]);
        break;
}