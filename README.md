# Evently

Веб-приложение для организации мероприятий и продажи билетов. Организаторы создают события и настраивают рассадку, участники выбирают места и покупают билеты, администраторы модерируют события и следят за статистикой.

## Возможности

**Участник**
- регистрация, вход, восстановление пароля
- поиск мероприятий с фильтрами, календарь событий
- страница события с картой
- выбор мест в зале и покупка билетов
- личный кабинет и раздел «Мои билеты»

**Организатор**
- создание мероприятий с загрузкой афиши
- конструктор рассадки (ряды, места, цена)
- список участников
- открытие и закрытие регистрации

**Администратор**
- модерация мероприятий
- управление пользователями
- отчёты

## Технологии

- PHP 8.2
- MariaDB 10.4 / MySQL
- HTML, CSS, JavaScript (без фреймворков)
- Яндекс.Карты

## Установка

Нужны PHP 8.0+, MariaDB или MySQL и веб-сервер (например, XAMPP или OpenServer).

1. Склонируйте репозиторий в папку веб-сервера:

   ```bash
   git clone https://github.com/Fulle-stack/evently.git
   ```

2. Создайте базу данных `evently` и импортируйте дамп:

   ```bash
   mysql -u root -p -e "CREATE DATABASE evently CHARACTER SET utf8mb4;"
   mysql -u root -p evently < evently.sql
   ```

   Или импортируйте `evently.sql` через phpMyAdmin.

3. Создайте конфиг из примера и укажите свои данные подключения:

   ```bash
   cp config.example.php config.php
   ```

   ```php
   return [
       'bd' => [
           'host'    => 'localhost',
           'name'    => 'evently',
           'user'    => 'root',
           'pass'    => '',
           'charset' => 'utf8mb4',
       ]
   ];
   ```

4. Убедитесь, что у веб-сервера есть право записи в папку `uploads/`.

5. Откройте в браузере `http://localhost/evently/`.

## Структура проекта

Каждая страница — это отдельный `.php` файл со своим `.css`.

| Файл | Назначение |
|------|------------|
| `index.php`, `Evently-hub.php` | главная страница |
| `registration.php`, `login.php`, `forgot-password.php` | авторизация |
| `personal-account.php` | личный кабинет |
| `event-search.php`, `event-page.php` | поиск и страница мероприятия |
| `forma-event.php`, `my-event.php` | создание и список событий организатора |
| `buy-ticket.php`, `my-tickets.php`, `ticket.php` | билеты |
| `admin-page.php`, `admin-event.php`, `admin-reports.php` | админ-панель |
| `*-user-handler.php` | добавление, редактирование и удаление пользователей |
| `seat-builder.js`, `seats-calc.js` | рассадка и подсчёт стоимости |
| `yandex-map.js` | карта |
| `bd.php`, `helpers.php` | подключение к БД и вспомогательные функции |
| `evently.sql` | дамп базы данных |
| `uploads/` | загруженные афиши |

## База данных

| Таблица | Содержимое |
|---------|------------|
| `participants` | участники |
| `organizers` | организаторы |
| `admins` | администраторы |
| `event` | мероприятия (статусы: moderation, active, rejected) |
| `seats` | места (free, booked, sold) |
| `payments` | платежи |
| `tickets` | билеты |
| `reservation` | связь участника, события и платежа |
| `questions` | обращения пользователей |

## Автор

[Fulle-stack](https://github.com/Fulle-stack)
