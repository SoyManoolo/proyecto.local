<?php

function detectUserLocale() {
    // Detectar el idioma del navegador (ejemplo: "es-ES,es;q=0.9,en;q=0.8")
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2); // Extraer los 2 primeros caracteres

    // Definir los idiomas soportados
    $supportedLanguages = ['en' => 'en', 'es' => 'es'];

    // Si el idioma está en la lista, usarlo; si no, usar inglés por defecto
    return $supportedLanguages[$lang] ?? 'en';
}

// Detectar el idioma del usuario
$locale = detectUserLocale();

// Cargar el archivo de traducción correspondiente
$translations = include __DIR__ . "/../lang/$locale.php";

// Retornar las traducciones para que `index.php` las use
return $translations;
