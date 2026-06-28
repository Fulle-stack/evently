<?php

session_start();

// Создавать события может только организатор. Гостя — на вход (а не молча на главную).
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['user_role'] !== 'organizer') {
    header('Location: Evently-hub.php');
    exit;
}

// ===== ПОЛУЧАЕМ FLASH-СООБЩЕНИЯ =====
// Переменные для сообщений
$success_message = $_SESSION['flash_success'] ?? '';
$error_message = $_SESSION['flash_error'] ?? '';
// УДАЛЯЕМ сообщения из сессии (чтобы не показывались снова)
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

require __DIR__ . '/bd.php';
require __DIR__ . '/helpers.php';



// ========== ОБРАБОТКА ОТПРАВКИ ФОРМЫ ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // ========== ПРОВЕРКА ФАЙЛА (БЕЗОПАСНАЯ) ==========
    $allowedTypes = ['image/jpeg', 'image/png'];  // разрешённые MIME-типы
    $maxSize = 5 * 1024 * 1024; // 5 MB
    $photoPath = '';   // сюда запишем путь к файлу

    // Как это выглядит в php массив $_FILES 

    // Пример структуры $_FILES['photo']:
    // [
    //     'name' => 'original_filename.jpg',
    //     'type' => 'image/jpeg',
    //     'tmp_name' => '/tmp/phpXb3s2F',
    //     'error' => 0,
    //     'size' => 123456
    // ];

    // Проверка 1: существует ли файл и нет ли ошибок при загрузке
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $errorCode = $_FILES['photo']['error'] ?? 'Файл не загружен';
        $error_message = 'Ошибка загрузки файла. Код:' . $errorCode;
    }
    // Проверка 2: размер файла
    elseif ($_FILES['photo']['size'] > $maxSize) {
        $error_message = 'Файл слишком большой. Максимум 5 МБ';
    }
    // Проверка 3: реальный MIME-тип (безопасная проверка)
    else {
        // ШАГ 1: Открываем "инструмент" для анализа файлов
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        // ШАГ 2: Определяем реальный тип файла
        $realType = finfo_file($finfo, $_FILES['photo']['tmp_name']);
        // Результат: например, 'image/jpeg' или 'image/png' или 'application/x-php'
        // ШАГ 3: Закрываем инструмент
        $finfo = null; // освобождаем ресурс
        // ШАГ 4: Проверяем, разрешён ли такой тип
        if (!in_array($realType, $allowedTypes)) {
            $error_message = 'Можно загрузить только JPG, JPEG или PNG файлы';
        } else {
            // ========== СОХРАНЕНИЕ ФАЙЛА ==========
            // Склеивает путь к текущей папке и название папки uploads/.
            $uploadDir = __DIR__ . '/uploads/';
            // Проверяет, НЕ существует ли папка по указанному пути.
            // Если папки для фото нет — создай её автоматически
            if (!is_dir($uploadDir)) {
                // Папки НЕТ → создаём её
                mkdir($uploadDir, 0755, true);
            }
            // 1. Узнаём расширение файла и сохраняем в переменную $extension 
            // pathinfo() - Функция, разбирающая путь к файлу
            // PATHINFO_EXTENSION - Константа, указывающая "дай расширение"
            // pathinfo(..., PATHINFO_EXTENSION) - Извлекает расширение файла - JPG 
            $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            // 2. Создаём уникальное имя: uniqid() - Генерирует уникальную строку из букв и цифр
            $filename = uniqid() . '_' . time() . '.' . $extension;
            // 3. Сохраняем в папку
            // $uploadDir Путь к папке uploads/ (где лежит твой проект)
            // $filename Уникальное имя файла (например, "abc123_1712345678.jpg")
            // $filePath Полный путь к файлу (папка + имя)
            $filePath = $uploadDir . $filename;

            // Перемещаем файл из временной папки в uploads/
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $filePath)) {
                // Запоминает веб-адрес файла, чтобы потом сохранить в БД и показывать картинку
                $photoPath = 'uploads/' . $filename;

                // ========== ПОЛУЧАЕМ ДАННЫЕ ИЗ ФОРМЫ ==========
                $title = trim($_POST['title'] ?? '');
                $organizer_name = trim($_POST['organizer_name'] ?? '');
                $category = $_POST['category'] ?? null;
                $event_type = $_POST['event_type'] ?? null;
                $rows_count = (int) ($_POST['rows_count'] ?? 0); // ряды
                $seats_per_row = (int) ($_POST['seats_per_row'] ?? 0); // места
                $start_date = $_POST['start_date'] ?? '';
                $start_time = $_POST['start_time'] ?? '';
                $end_time = $_POST['end_time'] ?? '';
                $street = trim($_POST['street'] ?? '');
                $district = trim($_POST['district'] ?? '');
                $city = trim($_POST['city'] ?? '');
                $country = trim($_POST['country'] ?? '');
                $venue_name = trim($_POST['venue_name'] ?? '');
                // Если пользователь ввёл значение в поле 'этаж', то преобразуй 
                // его в число и запиши в переменную $floor.
                // Если поле пустое — запиши null.
                $floor = !empty($_POST['floor']) ? (int) $_POST['floor'] : null;
                $entrance = trim($_POST['entrance'] ?? '');
                $region = $_POST['region'] ?? '';
                $price = !empty($_POST['price']) ? (int) $_POST['price'] : 0;
                $status = 'moderation'; // Все новые события отправляются на модерацию
                $speakers = trim($_POST['speakers'] ?? '');
                $described = trim($_POST['described'] ?? '');
                $organizer_id = $_SESSION['user_id'];  // ID из сессии

                // ========== ВАЛИДАЦИЯ ОБЯЗАТЕЛЬНЫХ ПОЛЕЙ ==========
                if (empty($title)) {
                    $error_message = 'Ошибка: введите название события!';
                } elseif (empty($start_date)) {
                    $error_message = 'Ошибка: укажите дату начала!';
                } elseif (empty($start_time)) {
                    $error_message = 'Ошибка: укажите время начала!';
                } elseif (empty($end_time)) {
                    $error_message = 'Ошибка: укажите время окончания!';
                } elseif (empty($street)) {
                    $error_message = 'Ошибка: введите улицу!';
                } elseif (empty($city)) {
                    $error_message = 'Ошибка: введите город!';
                } elseif (empty($country)) {
                    $error_message = 'Ошибка: введите страну!';
                } elseif (empty($organizer_name)) {
                    $error_message = 'Укажите название организации или имя организатора!';
                } else {
                    // ========== СОХРАНЯЕМ В БАЗУ ДАННЫХ ==========
                    $query = 'INSERT INTO event (
                photo, title, organizer_name, category, event_type, start_date, start_time, end_time, 
                street, district, city, country, venue_name,
                floor, entrance, region, price, status,
                speakers, described, id
            ) VALUES (
            :photo, :title, :organizer_name, :category, :event_type, :start_date, :start_time, :end_time,
            :street, :district, :city, :country, :venue_name,
            :floor, :entrance, :region, :price, :status,
            :speakers, :described, :organizer_id
            )';

                    $stmt = $pdo->prepare($query);

                    try {
                        $stmt->execute([
                            ':photo' => $photoPath,
                            ':title' => $title,
                            ':organizer_name' => $organizer_name,
                            ':category' => $category,
                            ':event_type' => $event_type,
                            ':start_date' => $start_date,
                            ':start_time' => $start_time,
                            ':end_time' => $end_time,
                            ':street' => $street,
                            ':district' => $district,
                            ':city' => $city,
                            ':country' => $country,
                            ':venue_name' => $venue_name,
                            ':floor' => $floor,
                            ':entrance' => $entrance,
                            ':region' => $region,
                            ':price' => $price,
                            ':status' => $status,
                            ':speakers' => $speakers,
                            ':described' => $described,
                            ':organizer_id' => $organizer_id
                        ]);

                        // Сохраняем сообщение в сессию
                        $_SESSION['flash_success'] = 'Мероприятие отправлено на модерацию. После проверки оно появится на сайте.';

                        // ===== КОД СОЗДАНИЯ МЕСТ =====
                        // 1. ПОЛУЧАЕМ ID ТОЛЬКО ЧТО СОЗДАННОГО СОБЫТИЯ
                        // lastInsertId() - возвращает номер (ID), который база данных 
                        // автоматически присвоила новому событию
                        // Например: 15
                        $event_id = $pdo->lastInsertId();

                        // Всего мест = ряды × места в ряду
                        $totalSeats = $rows_count * $seats_per_row;

                        // Конструктор зала передаёт точную раскладку мест (с проходами и
                        // ценовыми зонами) в скрытом поле seats_layout как JSON.
                        $layout = json_decode($_POST['seats_layout'] ?? '', true);

                        // СОЗДАНИЕ МЕСТ
                        if (is_array($layout) && count($layout) > 0) {
                            // ВАРИАНТ 1: интерактивный конструктор — вставляем ровно выбранные
                            // места с индивидуальной ценой каждой зоны.
                            $seatStmt = $pdo->prepare("INSERT INTO seats (event_id, seat_row, seat_number, price, status) VALUES(:event_id, :rowLetter, :seatNum, :price, 'free')");
                            $made = 0;
                            foreach ($layout as $s) {
                                $rowLetter = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) ($s['row'] ?? '')));
                                $seatNum   = (int) ($s['num'] ?? 0);
                                $seatPrice = (int) ($s['price'] ?? $price);
                                // Валидация: буква ряда (1-2 симв.), номер места, неотрицательная цена
                                if ($rowLetter === '' || strlen($rowLetter) > 2 || $seatNum < 1 || $seatNum > 100 || $seatPrice < 0) {
                                    continue;
                                }
                                $seatStmt->execute([
                                    'event_id'  => $event_id,
                                    'rowLetter' => $rowLetter,
                                    'seatNum'   => $seatNum,
                                    'price'     => $seatPrice
                                ]);
                                $made++;
                                if ($made >= 2000) break; // предохранитель от гигантских залов
                            }

                            $pdo->prepare("UPDATE event SET total_seats = :total WHERE id_event = :id")
                                ->execute(['total' => $made, 'id' => $event_id]);

                        } elseif ($rows_count > 0 && $seats_per_row > 0) {
                            // ВАРИАНТ 2 (запасной): простая прямоугольная сетка ряды × места
                            for ($rowNum = 1; $rowNum <= $rows_count; $rowNum++) {
                                // Прямо здесь превращаем номер ряда в букву
                                $rowLetter = chr(64 + $rowNum);

                                for ($seatNum = 1; $seatNum <= $seats_per_row; $seatNum++) {
                                    $seatStmt = $pdo->prepare("INSERT INTO seats (event_id, seat_row, seat_number, price, status) VALUES(:event_id, :rowLetter, :seatNum, :price, 'free')");

                                    $seatStmt->execute([
                                        'event_id' => $event_id,
                                        'rowLetter' => $rowLetter,
                                        'seatNum' => $seatNum,
                                        'price' => $price
                                    ]);
                                }
                            }

                            // Обновляем total_seats только если есть места
                            $updateStmt = $pdo->prepare("UPDATE event SET total_seats = :total WHERE id_event = :id");

                            // Выполняем: записываем общее число мест (40) для события с ID = 15
                            $updateStmt->execute([
                                'total' => $totalSeats,
                                'id' => $event_id
                            ]);

                        } else {
                            // Если ряды или места не указаны, просто оставляем total_seats = 0
                            // И при этом не создаём записи в таблице seats, так как мест нет
                            $updateStmt = $pdo->prepare("UPDATE event SET total_seats = 0 WHERE id_event = :id");

                            // Выполняем: записываем 0 мест для события с ID = 15
                            $updateStmt->execute(['id' => $event_id]);
                        }


                        // Очищаем форму после успешной отправки
                        $_POST = [];

                        // Перенаправляем на эту же страницу, чтобы при обновлении (F5) форма не отправлялась повторно
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;

                        // Если ошибка с БД произошла → заходим сюда
                    } catch (PDOException $e) {
                        error_log('Event save DB error: ' . $e->getMessage());
                        $_SESSION['flash_error'] = 'Произошла ошибка при сохранении. Попробуйте позже.';
                        // Удаляем загруженное фото, так как сохранение в БД не удалось
                        // file_exists()	
                        // Функция PHP, которая проверяет, существует ли файл по указанному пути
                        // $filePath Путь к файлу, который мы создали (например, uploads/abc123.jpg)
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }
            } else {
                // Если файл не удалось нормально переместить,
                // мы проверяем, не осталось ли чего-то лишнего, и удаляем. 
                $error_message = 'Ошибка при сохранение файла на сервер!';
                // Удаляем файл, если он был создан
                // isset($filePath) проверяем, существует ли переменная $filePath (мы её создали)
                // file_exists($filePath) проверяем, есть ли физический файл по этому пути
                if (isset($filePath) && file_exists($filePath)) {
                    // если файл есть, удаляем его
                    unlink($filePath);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Evently - Создание события</title>

    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />

    <link href="forma-event.css" rel="stylesheet" />
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
                <!-- Ссылка на события (для всех) -->
                <a href="event-search.php" class="nav__link">События</a>

                <!-- Мои билеты (только для авторизованных) -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="my-tickets.php" class="nav__link">Мои билеты</a>
                <?php endif; ?>

                <!-- Мои события (только для организатора и админа) -->
                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'organizer' || $_SESSION['user_role'] === 'admin')): ?>
                    <a href="my-event.php" class="nav__link">Мои события</a>
                    <a href="registration.php" class="nav__link">Организаторам</a>
                <?php endif; ?>

                <!-- Ссылки только для администратора -->
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin-event.php" class="nav__link">Модерация</a>
                    <a href="admin-page.php" class="nav__link">Пользователи</a>
                <?php endif; ?>

                <a href="data-processing-law.php" class="nav__link">Помощь</a>
            </nav>
        </div>

        <div class="header__right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-profile">
                    <div class="teg-name">
                        <?= htmlspecialchars($_SESSION['name'] ?? 'Пользователь'); ?>
                    </div>
                    <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=100&h=100&auto=format&fit=crop"
                        alt="User" class="nav-avatar">
                </div>
            <?php else: ?>
                <a href="login.php" class="btn-login">Вход</a>
                <a href="registration.php" class="btn-login">Регистрация</a>
            <?php endif; ?>

            <button class="burger">
                <span></span><span></span><span></span>
            </button>

            <!-- Мобильное меню -->
            <nav class="menu">
                <a href="event-search.php">События</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="my-tickets.php">Мои билеты</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'organizer' || $_SESSION['user_role'] === 'admin')): ?>
                    <a href="my-event.php">Мои события</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin-event.php">Модерация</a>
                    <a href="admin-page.php">Пользователи</a>
                <?php endif; ?>
                <a href="data-processing-law.php">Помощь</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="logout.php" class="logout-btn" onclick="return confirm('Вы уверены, что хотите выйти?')">Выйти</a>
                <?php else: ?>
                    <a href="login.php">Войти</a>
                    <a href="registration.php">Регистрация</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="page-title">
                <h1>Создание нового мероприятия</h1>
                <p>Заполните форму ниже, чтобы опубликовать ваше событие.</p>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <form class="event-form" method="POST" action="" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label">Обложка мероприятия</label>
                    <div class="upload-area">
                        <span class="material-symbols-outlined upload-icon">add_photo_alternate</span>
                        <span class="upload-text">Загрузить обложку</span>
                        <span class="upload-hint">PNG, JPG до 5MB</span>
                        <input type="file" name="photo" accept="image/jpeg, image/png, .jpg, .jpeg, .png"
                            class="upload-input" required />
                        <img id="preview" src="#" alt="Предпросмотр"
                            style="display: none; margin-top: 10px; max-width: 100%; max-height: 120px; border-radius: 8px;">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="event-name">Название события</label>
                    <input name="title" class="form-control" id="event-name" type="text"
                        placeholder="Например, Конференция по дизайну 2024"
                        value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required />
                </div>

                <div class="form-group">
                    <label class="form-label" for="organizer-name">Организатор / Компания</label>
                    <div class="input-with-icon">
                        <span class="material-symbols-outlined input-icon">business</span>
                        <input name="organizer_name" class="form-control pl-icon" id="organizer-name" type="text"
                            placeholder="Например: TechHub Russia или Иван Иванов"
                            value="<?= htmlspecialchars($_POST['organizer_name'] ?? '') ?>" required />
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="category">Категория</label>
                    <div class="select-wrapper">
                        <select name="category" class="form-control" id="category" required>
                            <option disabled selected value="">Выберите категорию</option>
                            <option value="education">Образование</option>
                            <option value="business">Бизнес</option>
                            <option value="sports">Спорт</option>
                            <option value="music">Музыка</option>
                            <option value="art">Искусство</option>
                            <option value="texh">Технологии</option>
                            <option value="entertainment">Развлечение</option>
                        </select>
                        <span class="material-symbols-outlined select-icon">expand_more</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="event_type">Формат мероприятия</label>
                    <div class="select-wrapper">
                        <select name="event_type" class="form-control" id="event_type" required>
                            <option disabled selected value="">Выберите формат</option>
                            <option value="conference">Конференция</option>
                            <option value="masterclass">Мастер-класс</option>
                            <option value="webinar">Вебинар</option>
                            <option value="exhibition">Выставка</option>
                            <option value="concert">Концерт</option>
                            <option value="festival">Фестиваль</option>
                            <option value="meetup">Встреча</option>
                            <option value="workshop">Воркшоп</option>
                            <option value="lecture">Лекция</option>
                            <option value="hackathon">Хакатон</option>
                        </select>
                        <span class="material-symbols-outlined select-icon">expand_more</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="start-date">Дата начала</label>
                    <div class="input-with-icon">
                        <span class="material-symbols-outlined input-icon">calendar_today</span>
                        <input name="start_date" class="form-control pl-icon" id="start-date" type="date"
                            value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>" required />
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="start-time">Время начала</label>
                        <div class="input-with-icon">
                            <span class="material-symbols-outlined input-icon">schedule</span>
                            <input name="start_time" class="form-control pl-icon" id="start-time" type="time"
                                value="<?= htmlspecialchars($_POST['start_time'] ?? '') ?>" required />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="end-time">Время окончания</label>
                        <div class="input-with-icon">
                            <span class="material-symbols-outlined input-icon">schedule</span>
                            <input name="end_time" class="form-control pl-icon" id="end-time" type="time"
                                value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>" required />
                        </div>
                    </div>
                </div>

                <div class="address-section">
                    <h3 class="section-title">Адрес и местоположение</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="country">Страна</label>
                            <input name="country" class="form-control" id="country" type="text" placeholder="Россия"
                                value="<?= htmlspecialchars($_POST['country'] ?? '') ?>" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="city">Город</label>
                            <input name="city" class="form-control" id="city" type="text" placeholder="Москва"
                                value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" required />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="region-status">Статус региона</label>
                        <div class="select-wrapper">
                            <select name="region" class="form-control" id="region-status">
                                <option disabled selected value="">Выберите статус</option>
                                <option value="capital">Столичный регион</option>
                                <option value="regional">Областной центр</option>
                                <option value="other">Другое</option>
                            </select>
                            <span class="material-symbols-outlined select-icon">expand_more</span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="street">Улица</label>
                            <input name="street" class="form-control" id="street" type="text"
                                placeholder="Улица Пушкина, д. 10"
                                value="<?= htmlspecialchars($_POST['street'] ?? '') ?>" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="district">Район</label>
                            <input name="district" class="form-control" id="district" type="text"
                                placeholder="Центральный" value="<?= htmlspecialchars($_POST['district'] ?? '') ?> " />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="entrance">Подъезд/Вход</label>
                            <input name="entrance" class="form-control" id="entrance" type="text" placeholder="1"
                                value="<?= htmlspecialchars($_POST['entrance'] ?? '') ?>" />
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="floor">Этаж здания</label>
                            <input name="floor" class="form-control" id="floor" type="text" placeholder="3"
                                value="<?= htmlspecialchars($_POST['floor'] ?? '') ?>" />

                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="location">Ориентир / Название места</label>
                    <div class="input-with-icon">
                        <span class="material-symbols-outlined input-icon">location_on</span>
                        <input name="venue_name" class="form-control pl-icon" id="location" type="text"
                            placeholder="БЦ 'Москва-Сити', Конференц-зал"
                            value="<?= htmlspecialchars($_POST['venue_name'] ?? '') ?>" required />
                    </div>
                </div>



                <div class="form-group">
                    <label class="form-label" for="speakers-description">Описание выступающих</label>
                    <textarea name="speakers" class="form-control textarea" id="speakers-description" rows="3"
                        placeholder="Имена спикеров..."><?= htmlspecialchars($_POST['speakers'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Общее описание события</label>
                    <textarea name="described" class="form-control textarea" id="description" rows="4"
                        placeholder="Расскажите подробнее о мероприятии..."><?= htmlspecialchars($_POST['described'] ?? '') ?></textarea>
                </div>

                <div class="address-section hall-builder" id="hallBuilder">
                    <h3 class="section-title">Конструктор зала</h3>
                    <p class="builder-intro">Задайте размер зала, затем кликами (или протягиванием) уберите проходы и раскрасьте ценовые зоны. Схема обновляется вживую — сразу виден масштаб помещения.</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="rows_count">Количество рядов</label>
                            <div class="input-with-icon">
                                <span class="material-symbols-outlined input-icon">table_rows</span>
                                <input type="number" name="rows_count" id="rows_count" class="form-control pl-icon"
                                    value="5" min="0" max="26" />
                            </div>
                            <small class="field-hint">Ряды обозначаются буквами A, B, C…</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="seats_per_row">Мест в ряду</label>
                            <div class="input-with-icon">
                                <span class="material-symbols-outlined input-icon">event_seat</span>
                                <input type="number" name="seats_per_row" id="seats_per_row"
                                    class="form-control pl-icon" value="10" min="0" max="50">
                            </div>
                            <small class="field-hint">Стартовое число мест в каждом ряду</small>
                        </div>
                    </div>

                    <!-- Панель кистей: проход + до 3 ценовых зон -->
                    <div class="builder-toolbar" id="builderToolbar">
                        <span class="toolbar-label">Кисть:</span>
                        <button type="button" class="brush-btn is-active" data-brush="erase" aria-pressed="true">
                            <span class="material-symbols-outlined">do_not_disturb_on</span> Проход
                        </button>
                        <button type="button" class="brush-btn zone-brush" data-brush="0" aria-pressed="false">
                            <span class="zone-dot zone-c0"></span> Зона 1 <span class="zone-hint">(базовая цена)</span>
                        </button>
                        <div class="brush-btn zone-brush" data-brush="1" role="button" tabindex="0" aria-pressed="false">
                            <span class="zone-dot zone-c1"></span> Зона 2
                            <input type="number" class="zone-price" id="zonePrice1" min="0" value="2500" aria-label="Цена зоны 2"> ₽
                        </div>
                        <div class="brush-btn zone-brush" data-brush="2" role="button" tabindex="0" aria-pressed="false">
                            <span class="zone-dot zone-c2"></span> Зона 3
                            <input type="number" class="zone-price" id="zonePrice2" min="0" value="3500" aria-label="Цена зоны 3"> ₽
                        </div>
                    </div>

                    <!-- Живая схема зала -->
                    <div class="builder-stage-wrap">
                        <div class="builder-screen">СЦЕНА</div>
                        <div class="builder-hall" id="builderHall" aria-label="Схема зала, кликните по месту чтобы изменить"></div>
                    </div>

                    <div class="builder-counters" id="builderCounters">
                        <span class="counter-total"><span class="material-symbols-outlined">event_seat</span> Мест: <strong id="totalSeatsPreview">0</strong></span>
                        <span class="counter-zone"><span class="zone-dot zone-c0"></span> <strong id="zoneCount0">0</strong></span>
                        <span class="counter-zone"><span class="zone-dot zone-c1"></span> <strong id="zoneCount1">0</strong></span>
                        <span class="counter-zone"><span class="zone-dot zone-c2"></span> <strong id="zoneCount2">0</strong></span>
                    </div>

                    <div class="seats-hint">
                        <span class="material-symbols-outlined">info</span>
                        Оставьте 0 рядов и 0 мест — билеты будут продаваться без выбора мест (только количество).
                    </div>

                    <!-- Сюда конструктор кладёт точную раскладку (JSON) перед отправкой -->
                    <input type="hidden" name="seats_layout" id="seatsLayout" value="">
                </div>

                <div class="form-group">
                    <label class="form-label" for="price">Базовая цена билета — Зона 1 (₽)</label>
                    <div class="input-with-icon">
                        <span class="material-symbols-outlined input-icon">payments</span>
                        <input name="price" class="form-control pl-icon" id="price" type="number" min="0"
                            placeholder="Например, 1500 (или 0 — бесплатно)"
                            value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required />
                    </div>
                </div>

                <input type="hidden" name="status" value="active" />
                <input type="hidden" name="id" value="1" /> <!-- Временно, пока нет авторизации -->

                <div class="form-actions">
                    <a class="btn-cancel" type="button">Отмена</a>
                    <button class="btn-submit" type="submit">
                        <span>Опубликовать</span>
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer class="footer">
        <p>© 2024 Evently. Все права защищены.</p>
    </footer>
    <script src="seat-builder.js"></script>
    <script src="burger-menu.js"></script>

</body>

</html>