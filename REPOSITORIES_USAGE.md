# Руководство по использованию репозиториев

## Обзор

Репозитории предоставляют высокоуровневый интерфейс для работы с данными в приложении. Они инкапсулируют бизнес-логику и обеспечивают единообразный доступ к данным.

## Структура

- `RepositoryInterface` - базовый интерфейс
- `BaseRepository` - абстрактная реализация общих методов
- `UserRepository` - операции с пользователями и балансами
- `BalanceRepository` - управление балансами
- `OperationRepository` - работа с операциями

## Регистрация

Все репозитории зарегистрированы как синглтоны в `RepositoryServiceProvider`.

## Примеры использования

### UserRepository

```php
use App\Repositories\UserRepository;

$userRepo = app(UserRepository::class);

// Создание пользователя
$user = $userRepo->create([
    'name' => 'Иван Петров',
    'email' => 'ivan@example.com',
    'password' => 'password123'
]);

// Создание пользователя с балансом
$user = $userRepo->createWithBalance([
    'name' => 'Мария Сидорова',
    'email' => 'maria@example.com',
    'password' => 'password123'
]);

// Пополнение баланса
$operation = $userRepo->deposit(1, 1000.00, 'Бонус');

// Списание средств
$operation = $userRepo->withdraw(1, 200.00, 'Покупка товара');

// Безопасное списание
$success = $userRepo->tryWithdraw(1, 500.00, 'Оплата услуги');

// Получение баланса
$balance = $userRepo->getBalance(1);

// Проверка наличия средств
$hasEnough = $userRepo->hasEnoughBalance(1, 300.00);

// Перевод между пользователями
[$withdrawOp, $depositOp] = $userRepo->transfer(1, 2, 500.00, 'Перевод');

// История операций
$history = $userRepo->getOperationsHistory(1, now()->subMonth(), now());

// Пользователи с активностью
$activeUsers = $userRepo->getUsersWithRecentActivity(7);
```

### BalanceRepository

```php
use App\Repositories\BalanceRepository;

$balanceRepo = app(BalanceRepository::class);

// Получение баланса по пользователю
$balance = $balanceRepo->findByUserId(1);

// Создание баланса для пользователя
$balance = $balanceRepo->firstOrCreateForUser(1);

// Изменение баланса
$balanceRepo->increaseBalance(1, 100.00);
$balanceRepo->decreaseBalance(1, 50.00);
$balanceRepo->updateBalance(1, 1000.00);

// Фильтры по балансу
$highBalances = $balanceRepo->getBalancesAbove(500.00);
$lowBalances = $balanceRepo->getBalancesBelow(100.00);
$zeroBalances = $balanceRepo->getZeroBalances();

// Проверка достаточности
$hasEnough = $balanceRepo->hasEnoughBalance(1, 200.00);

// Сумма всех балансов
$total = $balanceRepo->getTotalBalanceSum();

// Массовые операции
$balanceRepo->addToAllUsers(100.00); // Бонус всем
$balanceRepo->resetAllBalances();    // Обнуление всех балансов

// Создание балансов для пользователей без них
$count = $balanceRepo->createMissingForAllUsers();
```

### OperationRepository

```php
use App\Repositories\OperationRepository;

$operationRepo = app(OperationRepository::class);

// Получение операций
$userOperations = $operationRepo->findByUserId(1);
$deposits = $operationRepo->getDeposits(1);
$withdrawals = $operationRepo->getWithdrawals(1);

// Статистика
$totalDeposits = $operationRepo->getTotalDepositsSum(1);
$totalWithdrawals = $operationRepo->getTotalWithdrawalsSum(1);
$operationsCount = $operationRepo->getOperationsCount(1);

// Фильтры по статусу
$pendingOps = $operationRepo->getPendingOperations();
$failedOps = $operationRepo->getFailedOperations();
$completedOps = $operationRepo->getCompletedOperations(1);

// Диапазон дат
$monthOps = $operationRepo->getOperationsByDateRange(
    now()->startOfMonth(),
    now()->endOfMonth(),
    1
);

// Последние операции
$recent = $operationRepo->getRecentOperations(20, 1);

// Крупнейшие операции
$largest = $operationRepo->getLargestOperations(10, 'deposit');

// Изменение статуса операций
$operationRepo->completeOperation(123);
$operationRepo->failOperation(124);
$operationRepo->cancelOperation(125);
$operationRepo->updateOperationStatus(126, 'completed');
```

## Best Practices

1. **Используйте dependency injection** для репозиториев в контроллерах
2. **Группируйте операции в транзакции** для обеспечения целостности данных
3. **Проверяйте права доступа** перед выполнением операций
4. **Логируйте важные операции** для аудита
5. **Используйте pagination** для больших наборов данных

## Пример контроллера

```php
<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use App\Repositories\OperationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BalanceController extends Controller
{
    public function __construct(
        private UserRepository $userRepository,
        private OperationRepository $operationRepository
    ) {}

    public function deposit(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string'
        ]);

        try {
            DB::transaction(function () use ($request) {
                $operation = $this->userRepository->deposit(
                    $request->user_id,
                    $request->amount,
                    $request->description
                );
            });

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function userHistory(Request $request, int $userId)
    {
        $operations = $this->operationRepository->getRecentOperations(20, $userId);
        $stats = [
            'total_deposits' => $this->operationRepository->getTotalDepositsSum($userId),
            'total_withdrawals' => $this->operationRepository->getTotalWithdrawalsSum($userId),
            'current_balance' => $this->userRepository->getBalance($userId)
        ];

        return response()->json([
            'operations' => $operations,
            'statistics' => $stats
        ]);
    }
}
```

## Тестирование

### Unit тесты репозиториев

Для репозиториев написаны comprehensive unit тесты с использованием `RefreshDatabase` трейта Laravel.

#### UserRepositoryTest (27 тестов)
```bash
php artisan test tests/Unit/UserRepositoryTest.php
```

**Основные тесты:**
- CRUD операции пользователей
- Пополнение баланса (deposit)
- Списание средств (withdraw)
- Безопасное списание (tryWithdraw)
- Проверка баланса
- Переводы между пользователями
- История операций
- Accessor свойства модели

#### BalanceRepositoryTest (9 тестов)
```bash
php artisan test tests/Unit/BalanceRepositoryTest.php
```

**Основные тесты:**
- Поиск баланса по user_id
- Создание/получение баланса
- Изменение баланса (increase/decrease)
- Массовые операции со всеми балансами
- Проверка достаточности средств

#### OperationRepositoryTest (13 тестов)
```bash
php artisan test tests/Unit/OperationRepositoryTest.php
```

**Основные тесты:**
- Получение операций по пользователю/типу/статусу
- Статистика операций (суммы)
- Изменение статусов операций
- Фильтрация по дате/размерам

### Запуск всех тестов

```bash
# Запуск unit тестов
php artisan test tests/Unit/

# Запуск всех тестов
php artisan test

# С подробным выводом
php artisan test --verbose

# С покрытием кода
php artisan test --coverage
```

### Структура тестов

Все тесты используют:
- `RefreshDatabase` для чистой базы данных
- Фабрики моделей Laravel
- Assert методы PHPUnit
- Транзакции для комплексных операций

### Best practices в тестах

1. **Каждый тест независим** - не зависит от результатов других тестов
2. **Тестируйте ожидаемое поведение** - happy path и edge cases
3. **Проверяйте связи и аксессоры** - тестируйте модель полностью
4. **Используйте factories** - для создания тестовых данных
5. **Тестируйте исключения** - проверяйте правильную обработку ошибок
