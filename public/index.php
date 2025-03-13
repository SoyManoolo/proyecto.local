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
$chunks[0] = strtolower($chunks[0]);

switch ($chunks[0]) {
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

    default:
        http_response_code(404);
        echo $twig->render('404.twig', ['translations' => $translations]);
        break;
}
