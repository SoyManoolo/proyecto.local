<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Cargar Twig (para renderizar vistas) e internacionalización (para manejar traducciones)
require_once __DIR__ . '/../src/config/twig.php';

$translations = require '../src/config/i18n.php';

$twig->addGlobal('translations', $translations);
// Cargar UserController para manejar la autenticación
require_once __DIR__ . '/../src/controller/UserController.php';


// Obtener la URL solicitada y limpiar los parámetros
$request = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), "/");
$chunks = explode("/", $request);
$chunks[0] = strtolower($chunks[0]); // Convertir siempre a minúsculas para evitar errores

switch ($chunks[0]) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Capturar los valores enviados por el formulario
            $emailUsername = $_POST['user'] ?? '';
            $password = $_POST['password'] ?? '';

            // Intentar iniciar sesión
            $loginResult = UserController::signIn($emailUsername, $password);

            if ($loginResult === true) {
                // Si el login es exitoso, redirigir a la página principal
                header('Location: /home');
                exit;
            } else {
                global $twig;
                // Si hay error, mostrarlo en la vista de login
                echo $twig->render('login.twig', [
                    'error' => $loginResult // Muestra mensaje de error en la vista
                ]);
            }
        } else {
            // Si es una solicitud GET, solo mostrar la vista de login
            echo $twig->render('login.twig', ['translations' => $translations]);
        }
        break;

    case 'home':
        // Renderizar la vista de inicio
        echo $twig->render('home.twig', ['translations' => $translations]);
        break;

    case 'signup':
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                // Si hay error, mostrarlo en la vista de login
                echo $twig->render('login.twig', [
                'translations' => $translations,
                'error' => $signupResult // Muestra mensaje de error en la vista
                ]);
            }
        } else {
            // Si es una solicitud GET, solo mostrar la vista de login
            echo $twig->render('signup.twig', ['translations' => $translations]);
        }
        // Renderizar la vista de registro
        echo $twig->render('signup.twig', ['translations' => $translations]);
        break;

    default:
        // Si la ruta no existe, mostrar error 404
        http_response_code(404);
        echo $twig->render('404.twig', ['translations' => $translations]);
        break;
}
