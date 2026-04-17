<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $transactions = $user->transactions()->orderBy('date', 'desc')->get();
        $subscriptions = $user->subscriptions()->where('status', 'active')->get();
        
        // Calculando totais simples
        $income = $transactions->where('type', 'income')->sum('amount');
        $transactionExpenses = $transactions->where('type', 'expense')->sum('amount');
        $subscriptionExpenses = $subscriptions->sum('amount');
        
        $expense = $transactionExpenses + $subscriptionExpenses;
        $balance = $income - $expense;
        
        $recent_transactions = $transactions->take(5);
        $balanceHours = $user->convertMoneyToHours($balance);

        // Verificar se já recebeu salário este mês
        $hasClaimedSalary = $transactions->where('type', 'income')
            ->where('category', 'Salário')
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->isNotEmpty();

        return view('dashboard', compact(
            'balance', 'income', 'expense', 'recent_transactions', 
            'balanceHours', 'subscriptionExpenses', 'hasClaimedSalary'
        ));
    }

    public function claimSalary(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->salary) {
            return redirect()->route('profile.edit')->with('error', 'Por favor, configure seu salário no perfil antes de receber.');
        }

        // Verificar novamente para evitar duplicidade
        $alreadyClaimed = $user->transactions()
            ->where('type', 'income')
            ->where('category', 'Salário')
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->exists();

        if ($alreadyClaimed) {
            return redirect()->route('dashboard')->with('error', 'Salário já recebido este mês!');
        }

        $user->transactions()->create([
            'description' => 'Salário Mensal - ' . now()->translatedFormat('F'),
            'amount' => $user->salary,
            'type' => 'income',
            'category' => 'Salário',
            'date' => now(),
            'status' => 'completed'
        ]);

        return redirect()->route('dashboard')->with('success', 'Salário recebido com sucesso! Suas horas de vida foram atualizadas.');
    }
}
