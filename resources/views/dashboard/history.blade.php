<div style="font-family: Arial, sans-serif; padding: 20px;">
    <h1>История операций - Debug Mode</h1>

    <div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px;">
        <h2>Debug информация:</h2>
        <p><strong>Пользователь ID:</strong> {{ Auth::user()?->id ?? 'Не авторизован' }}</p>
        <p><strong>Имя:</strong> {{ Auth::user()?->name ?? 'Не авторизован' }}</p>
        <p id="balance"><strong>Баланс:</strong> {{ $balance->balance ?? '0' }}</p>
        <p><strong>Email:</strong> {{ Auth::user()?->email ?? 'Не авторизован' }}</p>
        <p><strong>Количество операций:</strong> {{ $operations?->count() ?? 0 }} из {{ $operations?->total() ?? 0 }}</p>
        <p><strong>Request URI:</strong> {{ request()->getRequestUri() }}</p>
        <p><strong>Memory Peak:</strong> {{ memory_get_peak_usage(true) / 1024 / 1024 }} MB</p>
        <p><strong>Server Status:</strong> OK</p>
    </div>

    @if($operations && $operations->count() > 0)
        <h2>Операции:</h2>
        @foreach($operations as $operation)
            <div style="border: 1px solid #ddd; padding: 10px; margin: 10px 0; background: #fff;">
                <p><strong>ID:</strong> {{ $operation->id }}</p>
                <p><strong>Тип:</strong> {{ $operation->type }}</p>
                <p><strong>Сумма:</strong> {{ $operation->amount }}</p>
                <p><strong>Статус:</strong> {{ $operation->status }}</p>
                <p><strong>Дата:</strong> {{ $operation->created_at->format('d.m.Y H:i') }}</p>
            </div>
        @endforeach
    @else
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0;">
            <h3 style="margin: 0 0 10px 0; color: #856404;">Нет операций</h3>
            <p style="margin: 0; color: #856404;">Если Вы ожидаете увидеть операции, создайте тестовую:</p>
            <code style="display: block; margin: 10px 0; padding: 10px; background: #f8f9fa; border: 1px solid #ddd; font-size: 12px;">
                php artisan user:balance-operation --user="{{ Auth::user()?->email ?? 'your-email@example.com' }}" --type="deposit" --amount="100" --description="Test operation"
            </code>
        </div>
    @endif

    <div style="margin-top: 20px;">
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin-left: 10px;">Выход</a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
</div>
   <!-- Vue.js Example Component -->
   <div id="vue-example" class="fixed bottom-4 right-4 bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg shadow-lg cursor-pointer transition-colors">
    Login Page Vue Example
</div>

<script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Vue компонент для автоматического обновления баланса
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    balance: 0,
                    updateInterval: null
                }
            },
            mounted() {
                // Начальная загрузка баланса
                this.fetchBalance();

                // Автоматическое обновление каждые 5 секунд
                this.updateInterval = setInterval(this.fetchBalance, 5000);
            },
            beforeUnmount() {
                // Очистка интервала при уничтожении компонента
                if (this.updateInterval) {
                    clearInterval(this.updateInterval);
                }
            },
            methods: {
                async fetchBalance() {
                    try {
                        const response = await fetch('/dashboard/balance');
                        if (response.ok) {
                            const data = await response.json();
                            this.balance = data.balance;
                            this.updateBalanceDisplay();
                        } else if (response.status === 401) {
                            // Если не авторизован, редирект на логин
                            window.location.href = '/login';
                        }
                    } catch (error) {
                        console.error('Ошибка при получении баланса:', error);
                    }
                },
                updateBalanceDisplay() {
                    const balanceElement = document.getElementById('balance');
                    if (balanceElement) {
                        balanceElement.innerHTML = `<strong>Баланс:</strong> ${this.balance}`;
                    }
                }
            }
        }).mount('#vue-app');
    });
</script>

<!-- Vue app container -->
<div id="vue-app"></div>
