<?php

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

// Obtener la URL solicitada y limpiar los parámetros
$request = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), "/");
$chunks = explode("/", $request);
$baseRoute = strtolower($chunks[0] ?? '');

// Definir rutas para API
$isApiRequest = $baseRoute === 'api';

// Si es una solicitud a la API
if ($isApiRequest) {
    header('Content-Type: application/json');
    $endpoint = $chunks[1] ?? '';
    $action = $chunks[2] ?? '';
    $id = $chunks[3] ?? null;

    // Manejar solicitudes a la API
    switch ($endpoint) {
        case 'players':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                if ($action === 'get' && $id) {
                    // GET /api/players/get/{id}
                    $player = PlayerController::getPlayerById($id);
                    echo json_encode($player);
                } else {
                    // GET /api/players
                    $players = PlayerController::getAllPlayers();
                    echo json_encode($players);
                }
            }
            break;

        case 'users':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);

                if ($action === 'login') {
                    // POST /api/users/login
                    $emailUsername = $data['user'] ?? '';
                    $password = $data['password'] ?? '';

                    $loginResult = UserController::signIn($emailUsername, $password);

                    if ($loginResult === true) {
                        echo json_encode(['success' => true, 'message' => 'Login successful']);
                    } else {
                        echo json_encode(['success' => false, 'message' => $loginResult]);
                    }
                } elseif ($action === 'register') {
                    // POST /api/users/register
                    $name = $data['name'] ?? '';
                    $surname = $data['surname'] ?? '';
                    $username = $data['user'] ?? '';
                    $email = $data['email'] ?? '';
                    $password = $data['password'] ?? '';

                    $signupResult = UserController::signUp($username, $email, $name, $surname, $password);

                    if ($signupResult === true) {
                        echo json_encode(['success' => true, 'message' => 'Registration successful']);
                    } else {
                        echo json_encode(['success' => false, 'message' => $signupResult]);
                    }
                }
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
    exit;
}

// Si no es una solicitud a la API, manejar las rutas normales
switch ($baseRoute) {
    case '':
    case 'home':
        $players = PlayerController::getAllPlayers();
        echo $twig->render('home.twig', [
            'translations' => $translations,
            'players' => $players
        ]);
        break;

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $emailUsername = $_POST['user'] ?? '';
            $password = $_POST['password'] ?? '';

            $loginResult = UserController::signIn($emailUsername, $password);

            if ($loginResult === true) {
                header('Location: /home');
                exit;
            } else {
                echo $twig->render('login.twig', [
                    'error' => $loginResult,
                    'translations' => $translations
                ]);
            }
        } else {
            echo $twig->render('login.twig', ['translations' => $translations]);
        }
        break;

    case 'signup':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $surname = $_POST['surname'] ?? '';
            $username = $_POST['user'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $signupResult = UserController::signUp($username, $email, $name, $surname, $password);

            if ($signupResult === true) {
                header('Location: /home');
                exit;
            } else {
                echo $twig->render('signup.twig', [
                    'translations' => $translations,
                    'error' => $signupResult
                ]);
            }
        } else {
            echo $twig->render('signup.twig', ['translations' => $translations]);
        }
        break;

    case 'player':
        $id = $chunks[1] ?? null;
        if ($id) {
            $player = PlayerController::getPlayerById($id);
            if ($player) {
                echo $twig->render('player_detail.twig', [
                    'translations' => $translations,
                    'player' => $player
                ]);
            } else {
                http_response_code(404);
                echo $twig->render('404.twig', ['translations' => $translations]);
            }
        } else {
            http_response_code(404);
            echo $twig->render('404.twig', ['translations' => $translations]);
        }
        break;

    default:
        http_response_code(404);
        echo $twig->render('404.twig', ['translations' => $translations]);
        break;
}
