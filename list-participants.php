<?php
session_start();
require __DIR__ . '/bd.php';
require __DIR__ . '/helpers.php';

// Доступ: организатор (свои события) или администратор (любые)
$role = $_SESSION['user_role'] ?? '';
if (!isset($_SESSION['user_id']) || !in_array($role, ['organizer', 'admin'], true)) {
    header('Location: Evently-hub.php');
    exit;
}
$uid = (int) $_SESSION['user_id'];
$eventId = (int) ($_GET['event'] ?? $_GET['id'] ?? 0);

// Проверка прав на конкретное событие
function eventOwnedOrAdmin(PDO $pdo, int $eventId, int $uid, string $role): ?array {
    $stmt = $pdo->prepare("SELECT id_event, title, id AS organizer_id FROM event WHERE id_event = :e");
    $stmt->execute(['e' => $eventId]);
    $ev = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ev) return null;
    if ($role === 'admin') return $ev;
    return ((int)$ev['organizer_id'] === $uid) ? $ev : null;
}

// Получить участников события (по бронированиям + покупкам билетов)
function fetchParticipants(PDO $pdo, int $eventId): array {
    $sql = "SELECT DISTINCT p.id_participants, p.name, p.surname, p.email, p.phone
            FROM reservation r
            JOIN participants p ON p.id_participants = r.id_participants
            WHERE r.id_event = :e
            ORDER BY p.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['e' => $eventId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ===== ЭКСПОРТ CSV =====
if ($eventId && isset($_GET['export'])) {
    $ev = eventOwnedOrAdmin($pdo, $eventId, $uid, $role);
    if (!$ev) { http_response_code(403); exit('Нет доступа к событию.'); }
    $rows = fetchParticipants($pdo, $eventId);
    $fname = 'participants_event_' . $eventId . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    $out = fopen('php://output', 'w');
    fprintf($out, "\xEF\xBB\xBF"); // BOM для корректной кириллицы в Excel
    fputcsv($out, ['№', 'Имя', 'Фамилия', 'Email', 'Телефон'], ';');
    $i = 1;
    foreach ($rows as $r) {
        fputcsv($out, [$i++, $r['name'], $r['surname'], $r['email'], $r['phone']], ';');
    }
    fclose($out);
    exit;
}

// ===== ДАННЫЕ ДЛЯ СТРАНИЦЫ =====
// Список событий организатора (или всех — для админа) для выбора
if ($role === 'admin') {
    $evStmt = $pdo->query("SELECT id_event, title FROM event ORDER BY start_date DESC");
    $myEvents = $evStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $evStmt = $pdo->prepare("SELECT id_event, title FROM event WHERE id = :uid ORDER BY start_date DESC");
    $evStmt->execute(['uid' => $uid]);
    $myEvents = $evStmt->fetchAll(PDO::FETCH_ASSOC);
}

$currentEvent = null;
$participants = [];
if ($eventId) {
    $currentEvent = eventOwnedOrAdmin($pdo, $eventId, $uid, $role);
    if ($currentEvent) {
        $participants = fetchParticipants($pdo, $eventId);
    }
}

$colors = ['bg-indigo', 'bg-purple', 'bg-sky', 'bg-teal'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evently — Список участников</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="list-participants.css">
    <style>
        .event-picker{display:flex;flex-wrap:wrap;gap:10px;margin:18px 0}
        .event-chip{padding:8px 14px;border:1px solid #e2e8f0;border-radius:999px;background:#fff;color:#334155;text-decoration:none;font-size:14px;transition:.15s}
        .event-chip:hover{border-color:#0ea5e9;color:#0ea5e9}
        .event-chip.current{background:#0ea5e9;color:#fff;border-color:#0ea5e9}
        .empty-note{padding:40px;text-align:center;color:#64748b}
        .avatar-text{width:40px;height:40px;border-radius:50%;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:600}
        .bg-indigo{background:#6366f1}.bg-purple{background:#a855f7}.bg-sky{background:#0ea5e9}.bg-teal{background:#14b8a6}
    </style>
    <link rel="stylesheet" href="ui-polish.css">
</head>
<body>
    <header class="header">
        <div class="header__left">
            <a href="Evently-hub.php" class="logo">
                <span class="logo__icon material-symbols-outlined">event_available</span>
                <span class="logo__text">Evently</span>
            </a>
            <nav class="nav">
                <a href="event-search.php" class="nav__link">События</a>
                <?php if ($role === 'organizer'): ?><a href="my-event.php" class="nav__link">Мои события</a><?php endif; ?>
                <?php if ($role === 'admin'): ?>
                    <a href="admin-event.php" class="nav__link">Модерация</a>
                    <a href="admin-page.php" class="nav__link">Пользователи</a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="header__right">
            <div class="teg-name"><?= e($_SESSION['name'] ?? 'Пользователь') ?></div>
        </div>
    </header>

    <main class="main-content">
        <div class="breadcrumbs">
            <a href="<?= $role === 'admin' ? 'admin-page.php' : 'my-event.php' ?>">Панель</a>
            <span class="separator material-symbols-outlined">chevron_right</span>
            <span class="current">Участники</span>
        </div>

        <div class="page-header">
            <h1 class="page-title">Список участников</h1>
            <?php if ($currentEvent): ?>
            <div class="page-actions">
                <a href="list-participants.php?event=<?= (int)$currentEvent['id_event'] ?>&export=1" class="btn-outline download-btn">
                    <span class="material-symbols-outlined icon-download">download</span> Экспорт CSV
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Выбор события -->
        <div class="event-picker">
            <?php if (empty($myEvents)): ?>
                <span class="empty-note">У вас пока нет событий.</span>
            <?php else: foreach ($myEvents as $ev): ?>
                <a class="event-chip <?= ($currentEvent && (int)$ev['id_event'] === (int)$currentEvent['id_event']) ? 'current' : '' ?>"
                   href="list-participants.php?event=<?= (int)$ev['id_event'] ?>"><?= e($ev['title']) ?></a>
            <?php endforeach; endif; ?>
        </div>

        <?php if ($eventId && !$currentEvent): ?>
            <div class="empty-note">Событие не найдено или у вас нет к нему доступа.</div>
        <?php elseif ($currentEvent): ?>
            <table class="data-table">
                <thead>
                    <tr><th>№</th><th>Участник</th><th>Email</th><th>Телефон</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($participants)): ?>
                        <tr><td colspan="4" class="empty-note">На это событие пока никто не зарегистрировался.</td></tr>
                    <?php else: $i = 1; foreach ($participants as $p): $fio = trim($p['name'] . ' ' . $p['surname']); ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td style="display:flex;align-items:center;gap:10px">
                                <span class="avatar-text <?= $colors[$i % count($colors)] ?>"><?= e(mb_strtoupper(mb_substr($p['name'], 0, 1))) ?></span>
                                <?= e($fio) ?>
                            </td>
                            <td><?= e($p['email']) ?></td>
                            <td><?= e($p['phone']) ?></td>
                        </tr>
                    <?php $i++; endforeach; endif; ?>
                </tbody>
            </table>
            <div class="pagination-wrapper">
                <span class="pagination-info">Всего участников: <?= count($participants) ?></span>
            </div>
        <?php else: ?>
            <div class="empty-note">Выберите событие, чтобы увидеть список участников.</div>
        <?php endif; ?>
    </main>
</body>
</html>
