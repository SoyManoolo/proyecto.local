<?php

require_once __DIR__ . '/../../vendor/autoload.php';
// Cargar controladores
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false, // Deshabilita la caché en desarrollo
]);

return $twig;
