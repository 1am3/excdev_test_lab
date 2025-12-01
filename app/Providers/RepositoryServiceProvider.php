<?php

namespace App\Providers;

use App\Models\Balance;
use App\Models\Operation;
use App\Models\User;
use App\Repositories\BalanceRepository;
use App\Repositories\OperationRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(UserRepository::class, function ($app) {
            return new UserRepository(new User());
        });

        $this->app->singleton(BalanceRepository::class, function ($app) {
            return new BalanceRepository(new Balance());
        });

        $this->app->singleton(OperationRepository::class, function ($app) {
            return new OperationRepository(new Operation());
        });
    }

    public function boot()
    {
        //
    }
}
