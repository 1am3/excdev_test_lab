<?php

namespace App\Console\Commands;

use App\Jobs\ProcessBalanceOperation;
use App\Repositories\UserRepository;
use Illuminate\Console\Command;

class TestQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:test
                          {user? : Email или имя пользователя}
                          {--type=deposit : Тип операции (deposit/withdraw)}
                          {--amount=100 : Сумма операции}
                          {--description= : Описание операции}
                          {--jobs=5 : Количество задач для генерации}
                          {--delay=0 : Задержка в секундах перед выполнением}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирование системы очередей для операций с балансом';
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userEmail = $this->argument('user');
        $operationType = $this->option('type');
        $amount = (float) $this->option('amount');
        $description = $this->option('description') ?: "Тестовая очередь - $operationType $amount ₽";
        $jobsCount = (int) $this->option('jobs');
        $delay = (int) $this->option('delay');

        // Поиск пользователя
        if (!$userEmail) {
            $userEmail = $this->ask('Введите email или имя пользователя');
        }

        $user = $this->findUser($userEmail);
        if (!$user) {
            $this->error("Пользователь '$userEmail' не найден");
            return Command::FAILURE;
        }

        $this->info("🚀 Запуск тестирования очередей");
        $this->line("Пользователь: {$user->name} ({$user->email})");
        $this->line("Баланс до начала: {$user->current_balance} ₽");
        $this->line("Количество задач: $jobsCount");
        $this->line("Тип операции: $operationType");
        $this->line("Сумма: $amount ₽");
        $this->line("Задержка: $delay сек");
        $this->line("---");

        $progressBar = $this->output->createProgressBar($jobsCount);
        $progressBar->start();

        for ($i = 0; $i < $jobsCount; $i++) {
            // Создаем уникальное описание для каждой задачи
            $jobDescription = $description . " #" . ($i + 1);

            // Отправляем задачу в очередь
            $job = new ProcessBalanceOperation(
                $user->id,
                $operationType,
                $amount,
                $jobDescription
            );

            if ($delay > 0) {
                $job->delay(now()->addSeconds($delay));
            }

            dispatch($job);

            $progressBar->advance();

            // Небольшая пауза между задачами чтобы избежать перегрузки
            usleep(100000); // 0.1 секунды
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Задачи успешно отправлены в очередь!");
        $this->line("Проверить статус: php artisan queue:work --tries=3");
        $this->line("Посмотреть логи: tail -f storage/logs/laravel.log");
        $this->line("Проверить джобы: php artisan queue:table или php artisan queue:failed");
        $this->newLine();

        // Показываем текущий баланс после отправки задач (может не обновиться сразу)
        $user->refresh();
        $this->line("Текущий баланс пользователя: {$user->current_balance} ₽");
        $this->line("<comment>⚠️  Баланс обновится после обработки задач очередью</comment>");

        return Command::SUCCESS;
    }

    /**
     * Найти пользователя по email или имени
     */
    private function findUser(string $identifier): ?\App\Models\User
    {
        // Сначала ищем по email
        $user = \App\Models\User::where('email', $identifier)->first();

        if (!$user) {
            // Если не найдено по email, ищем по имени
            $user = \App\Models\User::where('name', $identifier)->first();
        }

        return $user;
    }
}
