<?php
// ===== ОБЩИЕ ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ EVENTLY =====
// Подключается после session_start() и bd.php.

// Экранирование вывода (защита от XSS)
if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

// ===== CSRF-ЗАЩИТА =====
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

// Скрытое поле для вставки в любую форму
if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
    }
}

// Проверка токена при обработке POST; при провале — 403 и стоп.
if (!function_exists('csrf_check')) {
    function csrf_check(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return;
        $sent = $_POST['csrf_token'] ?? '';
        if (!is_string($sent) || !hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
            http_response_code(403);
            exit('Ошибка безопасности: недействительный токен формы. Обновите страницу и повторите.');
        }
    }
}

// ===== РОЛЬ → ТАБЛИЦА + ПЕРВИЧНЫЙ КЛЮЧ =====
// В этой БД у participants первичный ключ id_participants, у остальных — id.
if (!function_exists('role_table')) {
    function role_table(string $role): ?array {
        switch ($role) {
            case 'admin':       return ['table' => 'admins',       'pk' => 'id'];
            case 'organizer':   return ['table' => 'organizers',   'pk' => 'id'];
            case 'participant': return ['table' => 'participants', 'pk' => 'id_participants'];
        }
        return null;
    }
}
