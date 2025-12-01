<?php

namespace App\Http\Controllers;

use App\Repositories\BalanceRepository;
use App\Repositories\UserRepository;
use App\Repositories\OperationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    private UserRepository $userRepository;
    private OperationRepository $operationRepository;
    private BalanceRepository $balanceReporitory;

    public function __construct(
        UserRepository $userRepository,
        OperationRepository $operationRepository,
        BalanceRepository $balanceReporitory
    ) {
        $this->userRepository = $userRepository;
        $this->operationRepository = $operationRepository;
        $this->balanceReporitory = $balanceReporitory;
    }

    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        return redirect('/dashboard/history');

    }

    public function history(Request $request)
    {
        if (!Auth::check()) {
            \Log::info('History: User not authenticated, redirecting to login');
            return redirect()->route('login');
        }

        $user = Auth::user();
        $perPage = 15;

        \Log::info('History: Loading operations for user', ['user_id' => $user->id, 'email' => $user->email]);

        try {
            $operations = $this->operationRepository->getRecentOperations($perPage, $user->id);
            $balance = $this->balanceReporitory->findByUserId($user->id);

            \Log::info('History: Operations loaded', ['count' => $operations->count(), 'total' => $operations->total()]);
        } catch (\Exception $e) {
            \Log::error('History: Error loading operations', ['error' => $e->getMessage()]);
            $operations = collect(); // empty collection
        }

        return view('dashboard.history', compact('operations', 'balance'));
    }

    public function login()
    {
        return view('dashboard.login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Неверные учетные данные.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function getUserBalance()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $balanceDTO = $this->balanceReporitory->getBalanceValueDTO($user->id);

        return response()->json(['balance' => $balanceDTO->balance]);
    }
}
