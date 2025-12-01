# Руководство по использованию очередей в Laravel Balance System

## Обзор

Система поддерживает асинхронную обработку операций с балансом через Laravel Queues. Это позволяет распределять нагрузку и обрабатывать операции в фоне.

## Архитектура очередей

### Основные компоненты

1. **Job классы**: `app/Jobs/ProcessBalanceOperation.php`
2. **Консольные команды**: `php artisan queue:test`
3. **Драйверы очередей**: Database, Redis, SQS и др.

### Структура файлов

```
app/
├── Jobs/
│   └── ProcessBalanceOperation.php  # Основной job класс
├── Console/
│   └── Commands/
│       └── TestQueueCommand.php     # Команда тестирования
├── config/
│   └── queue.php                    # Конфигурация очередей
database/
└── migrations/
    └── create_jobs_table.php        # Таблица очередей
```

## Быстрый старт

### 1. Настройка базы данных для очередей

```bash
# Создаем таблицу для очередей (если используем database драйвер)
php artisan queue:table
php artisan migrate
```

### 2. Запуск обработчика очередей

```bash
# В отдельном терминале запустите worker
php artisan queue:work --tries=3 --timeout=90
```

### 3. Создайте тестового пользователя

```bash
php artisan user:create --name="Test User" --email="test@example.com" --with-balance
```

### 4. Тестирование очередей

```bash
# Отправить 5 задач на пополнение баланса
php artisan queue:test test@example.com --jobs=5 --type=deposit --amount=100

# Отправить 3 задачи на списание с задержкой 10 секунд
php artisan queue:test test@example.com --jobs=3 --type=withdraw --amount=50 --delay=10
```

## Детальное руководство

## Job класс: ProcessBalanceOperation

### Назначение
Обрабатывает асинхронные операции с балансом пользователя (пополнение/списание).

### Параметры
- `$userId`: ID пользователя
- `$operationType`: 'deposit' или 'withdraw'
- `$amount`: Сумма операции
- `$description`: Описание операции

### Функциональность

#### Автоматические повторы
При ошибке job автоматически повторяется до 3 раз с задержкой 60 секунд между попытками.

#### Логирование
Все операции детально логируются в `storage/logs/laravel.log`:
- Начало обработки операции
- Успешное завершение
- Ошибки и исключения
- Постоянные неудачи

#### Обработка ошибок
- Проверка существования пользователя
- Контроль достаточности средств при списании
- Логирование всех исключений

## Команда тестирования: queue:test

### Синтаксис

```bash
php artisan queue:test [user?] [опции]
```

### Параметры командной строки

| Параметр | Описание | Тип | По умолчанию |
|----------|----------|-----|--------------|
| `user` | Email или имя пользователя | string | Запрашивается |
| `--type` | Тип операции (deposit/withdraw) | string | deposit |
| `--amount` | Сумма операции | float | 100 |
| `--description` | Описание операции | string | Автоматическое |
| `--jobs` | Количество задач | int | 5 |
| `--delay` | Задержка выполнения (сек) | int | 0 |

### Примеры использования

#### 1. Базовое тестирование
```bash
# Создает 5 задач на пополнение баланса по 100 руб
php artisan queue:test user@example.com
```

#### 2. Списание средств с задержкой
```bash
# 3 задачи по списанию 50 руб через 30 секунд после отправки
php artisan queue:test user@example.com --type=withdraw --amount=50 --jobs=3 --delay=30
```

#### 3. Массовое тестирование
```bash
# 10 задач пополнения для нагрузочного тестирования
php artisan queue:test admin@example.com --jobs=10 --amount=1000
```

#### 4. Интерактивный режим
```bash
# Команда запросит данные пользователя
php artisan queue:test
```

## Мониторинг и управление

### Проверка статуса очередей

```bash
# Посмотреть очереди в базе данных
php artisan queue:table

# Посмотреть неудачные джобы
php artisan queue:failed

# Очистить неудачные джобы
php artisan queue:flush
```

### Логирование

```bash
# Мониторинг логов в реальном времени
tail -f storage/logs/laravel.log

# Поиск ошибок очередей
grep "ProcessBalanceOperation" storage/logs/laravel.log
```

### Производительность

```bash
# Одновременный запуск нескольких workers
php artisan queue:work --sleep=3 --tries=3 --max-jobs=1000

# Разные очереди для разных типов задач
php artisan queue:work --queue=high,default --tries=3
```

## Конфигурация

### Основные настройки (config/queue.php)

```php
'default' => env('QUEUE_CONNECTION', 'sync'),

'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
    ],
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
    ],
],
```

### Переменные окружения (.env)

```env
# Драйвер очередей
QUEUE_CONNECTION=database

# Для Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_QUEUE=laravel
```

## Продвинутые возможности

### Приоритеты очередей

```php
// Отправка в очередь с высоким приоритетом
ProcessBalanceOperation::dispatch($userId, $type, $amount)
    ->onQueue('high');

// Отправка с задержкой
ProcessBalanceOperation::dispatch($userId, $type, $amount)
    ->delay(now()->addMinutes(10));

// Условная отправка
if (condition) {
    ProcessBalanceOperation::dispatch($userId, $type, $amount);
}
```

### Мониторинг производительности

```php
// Использование Horizon для UI мониторинга (требует установки)
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

### Тестирование

```php
// Unit тестирование Job
public function test_job_processes_balance_operation()
{
    Queue::fake();

    $user = User::factory()->create();

    ProcessBalanceOperation::dispatch($user->id, 'deposit', 100);

    Queue::assertPushed(ProcessBalanceOperation::class);
}
```

## Устранение неполадок

### Распространенные проблемы

#### 1. Задачи не обрабатываются
```bash
# Проверить запущен ли worker
ps aux | grep queue:work

# Логи worker'а
tail -f storage/logs/laravel.log
```

#### 2. Задачи падают с ошибками
```bash
# Проверить неудачные джобы
php artisan queue:failed

# Повторить неудачные джобы
php artisan queue:retry all
```

#### 3. Высокая нагрузка на базу
```bash
# Использовать Redis вместо Database
QUEUE_CONNECTION=redis

# Настроить соединения
DB_CONNECTION=mysql_queue  # Отдельное соединение для очередей
```

#### 4. Память исчерпана
```bash
# Ограничить количество задач на worker
php artisan queue:work --max-jobs=1000 --memory=128
```

### Оптимизация производительности

```bash
# Supervisor для управления процессами
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-your-project/artisan queue:work --睡=3 --tries=3
directory=/path-to-your-project
autostart=true
autorestart=true
numprocs=8
user=forge
```

## Резюме

Система очередей позволяет:
- ✅ Асинхронная обработка операций с балансом
- ✅ Масштабируемость и распределение нагрузки
- ✅ Детальное логирование и мониторинг
- ✅ Автоматическое восстановление при ошибках
- ✅ Простое тестирование через `queue:test`

Для быстрого тестирования используйте команды из раздела "Быстрый старт".
