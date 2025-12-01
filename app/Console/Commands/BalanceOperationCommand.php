<?php

namespace App\Console\Commands;

use App\Repositories\UserRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class BalanceOperationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:balance-operation {--user= : Email или имя пользователя} {--type= : Тип операции: deposit (начисление) или withdraw (списание)} {--amount= : Сумма операции} {--description= : Описание операции}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Производит операции с балансом пользователя (начисление/списание) по email или имени';

    /**
     * Репозиторий для работы с пользователями
     *
     * @var UserRepository
     */
    private UserRepository $userRepository;

    /**
     * Create a new command instance.
     *
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Операция с балансом пользователя...');

        // Получаем параметры из коммандной строки или запрашиваем у пользователя
        $userIdentifier = $this->option('user') ?: $this->ask('Введите email или имя пользователя');
        $type = $this->option('type') ?: $this->choice('Выберите тип операции', ['deposit', 'withdraw'], 'deposit');
        $amount = $this->option('amount') ?: $this->ask('Введите сумму операции');
        $description = $this->option('description') ?: $this->ask('Введите описание операции');

        // Преобразуем сумму в float
        $amount = (float) $amount;
        // Валидируем введенные данные
        $validator = Validator::make([
            'user' => $userIdentifier,
            'type' => $type,
            'amount' => $amount,
        ], [
            'user' => 'required|string',
            'type' => 'required|in:deposit,withdraw',
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            $this->error('Ошибки валидации:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('- ' . $error);
            }
            return Command::FAILURE;
        }

        // Находим пользователя по email или имени
        $user = $this->findUser($userIdentifier);

        if (!$user) {
            $this->error("Пользователь с email или именем '{$userIdentifier}' не найден.");
            return Command::FAILURE;
        }
        // Выполняем операцию
        try {
            $this->info("Пользователь найден: {$user->name} ({$user->email})");

            $currentBalance = $this->userRepository->getBalance($user->id);
            $this->info("Текущий баланс: " . number_format($currentBalance, 2) . " ₽");

            if ($type === 'withdraw') {
                // Для списания - проверка наличия средств
                if (!$this->userRepository->hasEnoughBalance($user->id, $amount)) {
                    $this->error('Недостаточно средств для списания!');
                    $this->error('Требуется: ' . number_format($amount, 2) . ' ₽');
                    $this->error('Доступно: ' . number_format($currentBalance, 2) . ' ₽');
                    return Command::FAILURE;
                }

                $this->info('Выполняется списание средств...');
                $operation = $this->userRepository->withdraw($user->id, $amount, $description);

                if ($operation) {
                    $newBalance = $this->userRepository->getBalance($user->id);
                    $this->info('✅ Списание выполнено успешно!');
                    $this->line('Сумма: -' . number_format($amount, 2) . ' ₽');
                    $this->line('Новый баланс: ' . number_format($newBalance, 2) . ' ₽');
                }

            } else {
                // Для начисления
                $this->info('Выполняется начисление средств...');
                $operation = $this->userRepository->deposit($user->id, $amount, $description);

                if ($operation) {
                    $newBalance = $this->userRepository->getBalance($user->id);
                    $this->info('✅ Начисление выполнено успешно!');
                    $this->line('Сумма: +' . number_format($amount, 2) . ' ₽');
                    $this->line('Новый баланс: ' . number_format($newBalance, 2) . ' ₽');
                }
            }

            $this->line('Операция #: ' . $operation->id);
            $this->line('Тип: ' . ($type === 'deposit' ? 'Начисление' : 'Списание'));
            $this->line('Описание: ' . ($description ?: 'Не указано'));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Ошибка при выполнении операции: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Находит пользователя по email или имени
     *
     * @param string $identifier
     * @return \App\Models\User|null
     */
    private function findUser(string $identifier)
    {
        // Сначала ищем по email
        $user = $this->userRepository->findBy(['email' => $identifier]);

        if ($user) {
            return $user;
        }

        // Если не найден по email, ищем по имени
        return $this->userRepository->findBy(['name' => $identifier]);
    }

    /**
     * Генерирует случайное описание операции, если не указано
     *
     * @param string $type
     * @return string
     */
    private function generateDescription(string $type): string
    {
        $depositDescriptions = [
            'Административное начисление',
            'Бонус для пользователя',
            'Корректировка баланса',
            'Возврат средств',
            'Акционная программа'
        ];

        $withdrawDescriptions = [
            'Административное списание',
            'Корректировка баланса',
            'Возврат средств поставщику',
            'Аннулирование бонуса',
            'Техническая операция'
        ];

        return $type === 'deposit'
            ? $depositDescriptions[array_rand($depositDescriptions)]
            : $withdrawDescriptions[array_rand($withdrawDescriptions)];
    }
}
