<?php

namespace App\Console\Commands;

use App\Repositories\UserRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create {--name= : Имя пользователя} {--email= : Email пользователя} {--password= : Пароль (если не указан, генерируется автоматически)} {--with-balance : Создать баланс для пользователя}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создание нового пользователя с опциональным балансом';

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
        $this->info('Создание нового пользователя...');

        // Получаем параметры из коммандной строки или запрашиваем у пользователя
        $name = $this->option('name') ?: $this->ask('Введите имя пользователя');
        $email = $this->option('email') ?: $this->ask('Введите email пользователя');
        $password = $this->option('password') ?: $this->generatePassword();
        $withBalance = $this->option('with-balance');

        // Валидируем введенные данные
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->error('Ошибки валидации:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('- ' . $error);
            }
            return Command::FAILURE;
        }

        // Хешируем пароль перед сохранением
        $hashedPassword = Hash::make($password);

        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'email_verified_at' => now(),
        ];

        try {
            // Создаем пользователя через репозиторий
            if ($withBalance) {
                $this->info('Создание пользователя с балансом...');
                $user = $this->userRepository->createWithBalance($userData);
            } else {
                $this->info('Создание пользователя...');
                $user = $this->userRepository->create($userData);
            }

            // Выводим результат
            $this->info('Пользователь успешно создан!');
            $this->line('ID: ' . $user->id);
            $this->line('Имя: ' . $user->name);
            $this->line('Email: ' . $user->email);

            if ($withBalance) {
                $this->line('Баланс: ' . $user->balance?->balance ?? 'Не создан');
            }

            // Предупреждаем о безопасности пароля
            if (!$this->option('password')) {
                $this->warn('Пароль был сгенерирован автоматически: ' . $password);
                $this->warn('Рекомендуется сохранить его в надежном месте!');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Ошибка при создании пользователя: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Генерирует случайный пароль
     *
     * @return string
     */
    private function generatePassword(): string
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*'), 0, 12);
    }
}
