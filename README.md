# Process Balancer Microservice

Микросервис для балансировки процессов между рабочими машинами с поддержкой нескольких алгоритмов распределения.

[![Docker](https://img.shields.io/badge/Docker-23.0%2B-blue)](https://docker.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-purple)](https://php.net)
[![Symfony](https://img.shields.io/badge/Symfony-7.2%2B-green)](https://symfony.com)

## Оглавление
- [Возможности](#возможности)
- [Быстрый старт](#быстрый-старт)
    - [С Docker](#с-docker)
    - [Без Docker](#без-docker)
- [Использование API](#использование-api)
- [Примеры](#примеры)
- [Тестирование](#тестирование)
- [Настройки](#настройки)
- [Технологии](#технологии)
- [Лицензия](#лицензия)

## Возможности
- ✅ Распределение процессов между машинами
- 🔄 Поддержка алгоритмов:
    - ✅ Минимальная нагрузка
    - ❌ Round-Robin (В планах)
- 🔧 Автоматическая ребалансировка
- 🔨 Тесты для минимальной нагрузки

## Быстрый старт

### Клонируйте репозиторий:
```bash
git clone https://github.com/PlexILIghT/process-balancer.git
cd process-balancer
```

Установите зависимости:

```bash
composer install
```

Настройте БД в .env.local:

```ini
DATABASE_URL="mysql://user:password@127.0.0.1:3306/process_balancer?serverVersion=8.0"
```

### С Docker
Запустите контейнеры:

```bash
docker compose up --build
```
Примените миграции:

```bash
docker compose exec php bin/console doctrine:migrations:migrate
```
Сервис будет доступен по адресу: http://localhost:8000

### Без Docker

Примените миграции:

```bash
php bin/console doctrine:migrations:migrate
```
Если миграций не оказалось, то создайте их и повторите
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```
Запустите сервер:
```bash
symfony server:start
```
## Использование API
Машины

| Метод  | Путь              | Описание              |
|--------|-------------------|-----------------------|
| POST   | /api/machine      | Создать машину        |
| DELETE | /api/machine/{id} | Удалить машину        |
| GET    | /api/machine      | Список всех машин     |


Процессы

| Метод  | Путь              | Описание              |
|--------|-------------------|-----------------------|
| POST   | /api/process      | Создать процесс       |
| DELETE | /api/process/{id} | Удалить процесс       |
| GET    | /api/process      | Список всех процессов |

## Примеры
Создание машины:

```bash
curl -X POST -H "Content-Type: application/json" \
  -d '{"memory": 1000, "cpu": 4}' \
  http://localhost:8000/api/machine
```

Создание процесса:

```bash
curl -X POST -H "Content-Type: application/json" \
  -d '{"memory": 500, "cpu": 2}' \
  http://localhost:8000/api/process
```

## Тестирование
Запуск всех тестов:

```bash
./bin/phpunit
```
Пример тестового сценария:

```bash
# 1. Создаем машину
# 2. Создаем процесс
# 3. Проверяем распределение
# 4. Удаляем машину
# 5. Проверяем ребалансировку
```

## Настройки

Файл .env:

```ini
APP_ENV=dev
DATABASE_URL=mysql://root:root@mysql:3306/process_balancer
# BALANCING_STRATEGY=min_load # или round_robin (Not ready yet)
```

## Технологии
🐘 PHP 8.3

🎻 Symfony 7.2

🗃️ MySQL 8.0

🐳 Docker

### Лицензия
Apache License