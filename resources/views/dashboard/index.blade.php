@extends('layouts.dashboard')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Главная</h1>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Текущий баланс -->
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium opacity-90">Текущий баланс</h3>
                                <p class="text-3xl font-bold mt-2">{{ number_format($balance, 2) }} ₽</p>
                            </div>
                            <div class="text-blue-200">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Всего начислено -->
                    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium opacity-90">Всего начислено</h3>
                                <p class="text-3xl font-bold mt-2">{{ number_format($totalDeposits, 2) }} ₽</p>
                            </div>
                            <div class="text-green-200">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Всего списано -->
                    <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-lg p-6 text-white">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium opacity-90">Всего списано</h3>
                                <p class="text-3xl font-bold mt-2">{{ number_format($totalWithdrawals, 2) }} ₽</p>
                            </div>
                            <div class="text-red-200">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Быстрые действия -->
                <div class="mt-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Быстрые действия</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="{{ route('dashboard.history') }}"
                           class="bg-gray-50 hover:bg-gray-100 p-4 rounded-lg border border-gray-200 transition-colors">
                            <div class="text-center">
                                <svg class="w-8 h-8 text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <span class="text-sm font-medium text-gray-900">История операций</span>
                            </div>
                        </a>

                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <div class="text-center">
                                <svg class="w-8 h-8 text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                <span class="text-sm font-medium text-gray-900">Добавить операцию</span>
                                <p class="text-xs text-gray-500 mt-1">В разработке</p>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <div class="text-center">
                                <svg class="w-8 h-8 text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="text-sm font-medium text-gray-900">Настройки</span>
                                <p class="text-xs text-gray-500 mt-1">В разработке</p>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <div class="text-center">
                                <svg class="w-8 h-8 text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="text-sm font-medium text-gray-900">Отчеты</span>
                                <p class="text-xs text-gray-500 mt-1">В разработке</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
