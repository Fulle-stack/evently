<?php

declare(strict_types=1); // Строгая типезация

//Подключение config

$config = require __DIR__.'/config.php';
$bd = $config['bd'];

// формируем строку подключения
$dsn = "mysql:host={$bd['host']};dbname={$bd['name']};charset={$bd['charset']}";

$options = [
    // ошибки будут выбрасыватся как исключение
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    // Fetch() будет возвращать ассоц массив ключ - значение
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// подкключение базы
try {
    // создаем базу 
    $pdo = new PDO($dsn, $bd['user'], $bd['pass'], $options);
} catch(PDOException $e) {
    // выводит ошибку 500
    http_response_code(500);
    // Ловит и возвращает ошибку 
    exit("Ошибка бд: ".htmlspecialchars($e->getMessage()));
}
?>