<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('operations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['deposit', 'withdrawal'])->comment('Тип операции: deposit - пополнение, withdrawal - списание');
            $table->decimal('amount', 15, 2)->comment('Сумма операции');
            $table->decimal('balance_before', 15, 2)->nullable()->comment('Баланс до операции');
            $table->decimal('balance_after', 15, 2)->nullable()->comment('Баланс после операции');
            $table->enum('status', ['pending', 'completed', 'cancelled', 'failed'])->default('pending')->comment('Статус операции');
            $table->string('description')->nullable()->comment('Описание операции');
            $table->timestamps();
            
            // Индексы для оптимизации запросов
            $table->index('user_id');
            $table->index('type');
            $table->index('status');
            
            // Внешний ключ на таблицу users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operations');
    }
};
