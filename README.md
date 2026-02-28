```{=html}
<p align="center">
```
`<a href="https://laravel.com" target="_blank">`{=html}
`<img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">`{=html}
`</a>`{=html}
```{=html}
</p>
```
```{=html}
<p align="center">
```
`<a href="https://github.com/him1k0ta/bookingApi/actions">`{=html}
`<img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status">`{=html}
`</a>`{=html}
`<a href="https://packagist.org/packages/laravel/framework">`{=html}
`<img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads">`{=html}
`</a>`{=html}
`<a href="https://packagist.org/packages/laravel/framework">`{=html}
`<img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version">`{=html}
`</a>`{=html} `<a href="https://opensource.org/licenses/MIT">`{=html}
`<img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License">`{=html}
`</a>`{=html}
```{=html}
</p>
```
# Booking API

REST API для системы бронирования отелей. Проект разработан на Laravel с
использованием JWT аутентификации.

------------------------------------------------------------------------

## 📌 О проекте

Booking API предоставляет функционал для бронирования номеров в отеле.

### Возможности:

-   Аутентификация и авторизация (JWT, роли user/admin)
-   Управление комнатами (CRUD)
-   Система бронирования (создание, проверка доступности, отмена)
-   Отзывы к комнатам
-   Валидация входных данных
-   10+ автотестов
-   Docker контейнеризация

------------------------------------------------------------------------

## Технологии

-   Laravel 12\
-   PHP 8+\
-   MySQL 8.0\
-   JWT Auth\
-   Docker + Docker Compose\

------------------------------------------------------------------------

# Установка и запуск

## 🔹 Локальный запуск

### 1. Клонировать репозиторий

``` bash
git clone https://github.com/him1k0ta/bookingApi.git
cd bookingApi
```

### 2. Установить зависимости

``` bash
composer install
npm install
```

### 3. Настроить окружение

``` bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

### 4. Настроить подключение к БД (.env)

``` env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booking_api
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Выполнить миграции

``` bash
php artisan migrate
```

### 6. Запустить сервер

``` bash
php artisan serve
```

Приложение будет доступно по адресу: http://localhost:8000

------------------------------------------------------------------------

# 🐳 Запуск через Docker

### 1. Убедитесь, что Docker установлен и запущен

### 2. Скопируйте конфигурацию

``` bash
cp .env.docker.example .env
```

### 3. Запустите контейнеры

``` bash
docker-compose up -d --build
```

### 4. Выполните миграции внутри контейнера

``` bash
docker-compose exec app php artisan migrate
```

Приложение будет доступно по адресу: http://localhost:8080

------------------------------------------------------------------------

## Полезные команды Docker

  Команда                        Описание
  ------------------------------ -----------------------
  docker-compose down            Остановить контейнеры
  docker-compose logs -f         Посмотреть логи
  docker-compose exec app bash   Зайти в контейнер
  docker-compose ps              Статус контейнеров

------------------------------------------------------------------------

# API Endpoints

## Аутентификация

  Метод   Endpoint        Описание                   Доступ
  ------- --------------- -------------------------- --------
  POST    /api/register   Регистрация пользователя   Public
  POST    /api/login      Вход в систему             Public

------------------------------------------------------------------------

## Комнаты

  Метод    Endpoint          Описание               Доступ
  -------- ----------------- ---------------------- --------
  GET      /api/rooms        Список комнат          Public
  GET      /api/rooms/{id}   Информация о комнате   Public
  POST     /api/rooms        Создать комнату        Admin
  PUT      /api/rooms/{id}   Обновить комнату       Admin
  DELETE   /api/rooms/{id}   Удалить комнату        Admin

------------------------------------------------------------------------

## Бронирования

  -------------------------------------------------------------------------------
  Метод           Endpoint                    Описание            Доступ
  --------------- --------------------------- ------------------- ---------------
  GET             /api/bookings               Все бронирования    Admin

  GET             /api/my-bookings            Мои бронирования    User

  POST            /api/bookings               Создать             User
                                              бронирование        

  PATCH           /api/bookings/{id}/cancel   Отменить            Owner/Admin
                                              бронирование        
  -------------------------------------------------------------------------------

------------------------------------------------------------------------

## Отзывы

  Метод    Endpoint                      Описание         Доступ
  -------- ----------------------------- ---------------- -------------
  GET      /api/rooms/{roomId}/reviews   Отзывы комнаты   Public
  POST     /api/reviews                  Создать отзыв    User
  DELETE   /api/reviews/{id}             Удалить отзыв    Owner/Admin

------------------------------------------------------------------------

# Тестирование

Проект содержит 10+ автоматических тестов.

Запуск тестов:

``` bash
php artisan test
```

------------------------------------------------------------------------
