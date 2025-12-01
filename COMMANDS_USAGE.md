# Руководство по использованию Artisan команд

## Обзор

Система содержит настраиваемые artisan команды для административных задач, связанных с управлением пользователями и балансами.

## Создание пользователя

### Основная команда: `user:create`

```bash
php artisan user:create [опции]
```

### Опции команды

| Опция | Описание | Тип | Обязательная |
|-------|----------|-----|-------------|
| `--name` | Имя пользователя | string | Нет (будет запрошено) |
| `--email` | Email пользователя | string | Нет (будет запрошено) |
| `--password` | Пароль пользователя | string | Нет (генерируется автоматически) |
| `--with-balance` | Создать баланс для пользователя | flag | Нет |

### Примеры использования

#### 1. Интерактивное создание
```bash
php artisan user:create
```
```
Создание нового пользователя...
Введите имя пользователя: Иван Иванов
Введите email пользователя: ivan@example.com
Пользователь успешно создан!
ID: 1
Имя: Иван Иванов
Email: ivan@example.com
Пароль был сгенерирован автоматически: Abc123!def456
Рекомендуется сохранить его в надежном месте!
```

#### 2. Создание с указанными параметрами
```bash
php artisan user:create --name="Иван Иванов" --email="ivan@example.com" --password="secret123"
```

#### 3. Создание с балансом
```bash
php artisan user:create --name="Мария Петрова" --email="maria@example.com" --password="secret123" --with-balance
```

#### 4. Автоматическая генерация пароля
```bash
php artisan user:create --name="Алексей Сидоров" --email="alex@example.com" --with-balance
```

#### 5. Пакетное создание пользователей
```bash
# В Unix/Linux shell
for i in {1..5}; do
    php artisan user:create --name="User $i" --email="user$i@example.com" --with-balance
done

# Или в PowerShell
1..5 | ForEach-Object {
    php artisan user:create --name="User $_" --email="user$_.example.com" --with-balance
}
```

### Функциональность команды

#### Валидация данных
- Email проверяется на уникальность в базе данных
- Пароль должен быть минимум 8 символов
- Имя обязательно для заполнения

#### Автоматическая генерация пароля
- Длина: 12 символов
- Содержит: буквы верхнего/нижнего регистра, цифры, специальные символы
- Алгоритм: безопасная генерация на основе перемешивания символов

#### Создание баланса
- Опция `--with-balance` создаёт нулевой баланс
- Использует метод `UserRepository::createWithBalance()`
- Интегрируется с системой балансовых операций

#### Обработка ошибок
```bash
# Пример ошибки
php artisan user:create --email="existing@example.com"
# Результат: Email уже занят в базе данных
```

### Интеграция с репозиториями

Команда использует следующие репозитории:

- **UserRepository**: создание пользователей и балансов
- **RepositoryInterface**: стандартные CRUD операции

```php
// Логика команды
$userRepo = app(UserRepository::class);

if ($withBalance) {
    $user = $userRepo->createWithBalance($userData);
} else {
    $user = $userRepo->create($userData);
}
```

### Структура файлов

```
app/
└── Console/
    ├── Commands/
    │   └── CreateUserCommand.php
    └── Kernel.php
```

### Расширение команды

Для добавления новых опций редактируйте:
- `signature` в `CreateUserCommand.php`
- `handle()` метод для дополнительной логики

## Операции с балансом пользователя

### Основная команда: `user:balance-operation`

```bash
php artisan user:balance-operation [опции]
```

### Опции команды

| Опция | Описание | Тип | Обязательная |
|-------|----------|-----|-------------|
| `--user` | Email или имя пользователя | string | Нет (будет запрошено) |
| `--type` | Тип операции: deposit (начисление) или withdraw (списание) | string | Нет (будет запрошено) |
| `--amount` | Сумма операции | float | Нет (будет запрошено) |
| `--description` | Описание операции | string | Нет (будет запрошено) |

### Примеры использования

#### 1. Интерактивное списание средств
```bash
php artisan user:balance-operation
```
```
Операция с балансом пользователя...
Введите email или имя пользователя: ivan@example.com
Выберите тип операции:
  [0] deposit
  [1] withdraw
 > 1
Введите сумму операции: 500
Введите описание операции: Штраф за нарушение

Пользователь найден: Иван Иванов (ivan@example.com)
Текущий баланс: 1000.00 ₽
Выполняется списание средств...
✅ Списание выполнено успешно!
Сумма: -500.00 ₽
Новый баланс: 500.00 ₽
Операция #: 42
Тип: Списание
Описание: Штраф за нарушение
```

#### 2. Начисление через параметры
```bash
php artisan user:balance-operation --user="ivan@example.com" --type="deposit" --amount="1000" --description="Бонус за активность"
```

#### 3. Списание с проверкой баланса
```bash
php artisan user:balance-operation --user="admin@example.com" --type="withdraw" --amount="2000" --description="Вывод средств"
```
```
Операция с балансом пользователя...
Пользователь найден: Администратор (admin@example.com)
Текущий баланс: 1500.00 ₽
❌ Недостаточно средств для списания!
Требуется: 2000.00 ₽
Доступно: 1500.00 ₽
```

#### 4. Массовая операция через скрипт
```bash
# Начисление бонуса всем пользователям из файла
cat users.txt | while read email; do
    php artisan user:balance-operation --user="$email" --type="deposit" --amount="100" --description="Ежемесячный бонус"
done
```

### Функциональность команды

#### Поиск пользователя
- Поиск сначала по email, затем по имени
- Возврат ошибки если пользователь не найден

#### Защита от отрицательного баланса
- Для операций списания всегда проверяется достаточность средств
- Операция блокируется если баланс меньше требуемой суммы
- Детальное сообщение об ошибке с указанием доступного баланса

#### Валидация данных
- Сумма должна быть положительной (> 0.01)
- Тип операции только 'deposit' или 'withdraw'
- Пользователь должен существовать

#### Финансовые операции
- **Начисление** - всегда выполняется успешно
- **Списание** - выполняется только при наличии достаточных средств
- Все операции записываются в таблицу `operations`
- Автоматическое обновление баланса через репозиторий

#### Обработка ошибок
```bash
# Пользователь не найден
php artisan user:balance-operation --user="nonexistent@example.com"
# Результат: Пользователь с email или именем 'nonexistent@example.com' не найден.

# Недостаточно средств
php artisan user:balance-operation --user="user@example.com" --type="withdraw" --amount="2000"
# Результат: Недостаточно средств для списания!

# Неверный тип операции
php artisan user:balance-operation --type="invalid"
# Результат: Тип операции должен быть: deposit, withdraw
```

### Интеграция с репозиториями

Команда использует **UserRepository** для всех операций:

```php
// Поиск пользователя и проверка баланса
$currentBalance = $this->userRepository->getBalance($user->id);
$hasEnough = $this->userRepository->hasEnoughBalance($user->id, $amount);

// Выполнение операций
$operation = $this->userRepository->deposit($user->id, $amount, $description);
// или
$operation = $this->userRepository->withdraw($user->id, $amount, $description);
```

### Структура файлов

```
app/
└── Console/
    ├── Commands/
    │   ├── CreateUserCommand.php
    │   └── BalanceOperationCommand.php # Новая команда
    └── Kernel.php
```

### Расширение команды

Для добавления новых функций редактируйте:
- Опции в `signature` класса `BalanceOperationCommand`
- Логику валидации в методе `handle()`
- Условия поиска пользователей в методе `findUser()`

## Совместное использование команд

```bash
# 1. Создать пользователя
php artisan user:create --name="Иван Петров" --email="ivan@example.com" --with-balance

# 2. Пополнить баланс
php artisan user:balance-operation --user="ivan@example.com" --type="deposit" --amount="1000" --description="Стартовый бонус"

# 3. Проверить баланс (требуется дополнительная команда или база данных)
php artisan tinker --execute="dd(\App\Repositories\UserRepository::make(new \App\Models\User)->getBalance(1))"
```

## Безопасность

### Защита паролей
- Все пароли хэшируются через `Hash::make()`
- Используется BCrypt алгоритм Laravel

### Валидация
- Email проверяется на корректность и уникальность
- Пароли должны соответствовать требованиям безопасности

## Мониторинг и логирование

Команда выводит подробную информацию о создаваемых пользователях. Для дополнительного логирования:

```php
// В методе handle()
logger()->info('User created via artisan command', [
    'user_id' => $user->id,
    'email' => $user->email,
    'created_with_balance' => $withBalance
]);
```

## Получение справки

```bash
php artisan user:create --help
php artisan user:balance-operation --help
```
