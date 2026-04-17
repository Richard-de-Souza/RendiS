@extends('layouts.app')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Visão Geral</h1>
        <a href="{{ route('transactions.create') }}" class="btn btn-primary">
            <i class='bx bx-plus'></i> Nova Transação
        </a>
    </div>

    @if(!$hasClaimedSalary && auth()->user()->salary > 0)
    <div class="card animate-fade-in" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(14, 165, 233, 0.1)); border: 2px dashed var(--success-color); margin-bottom: 24px; padding: 24px; display: flex; align-items: center; justify-content: space-between; gap: 24px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="width: 56px; height: 56px; background: var(--success-color); color: white; border-radius: 16px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 16px rgba(16, 185, 129, 0.2);">
                <i class='bx bx-money-withdraw' style="font-size: 32px;"></i>
            </div>
            <div>
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">É dia de pagamento! 💸</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0;">Seu salário de <strong>R$ {{ number_format(auth()->user()->salary, 2, ',', '.') }}</strong> está pronto para ser adicionado.</p>
                <div style="display: inline-flex; align-items: center; gap: 6px; margin-top: 8px; font-size: 0.85rem; font-weight: 600; color: var(--success-color);">
                    <i class='bx bx-time'></i> + {{ auth()->user()->formatHoursToReadableTime(auth()->user()->convertMoneyToHours(auth()->user()->salary)) }} de vida
                </div>
            </div>
        </div>
        <form action="{{ route('dashboard.claim-salary') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary" style="padding: 12px 24px; font-weight: 700; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);">
                Receber Agora
            </button>
        </form>
    </div>
    @endif

    <div class="stats-grid">
        <!-- Balance Card -->
        <div class="card stat-card" style="border-top: 4px solid var(--primary-color);">
            <div class="stat-icon" style="background-color: #e0f2fe; color: var(--primary-color);">
                <i class='bx bx-wallet' style="font-size: 24px;"></i>
            </div>
            <div class="stat-label">Saldo Atual</div>
            <div class="stat-value">R$ {{ number_format($balance, 2, ',', '.') }}</div>
        </div>

        <!-- Income Card -->
        <div class="card stat-card" style="border-top: 4px solid var(--success-color);">
            <div class="stat-icon" style="background-color: #d1fae5; color: var(--success-color);">
                <i class='bx bx-trending-up' style="font-size: 24px;"></i>
            </div>
            <div class="stat-label">Receitas (Mês)</div>
            <div class="stat-value">R$ {{ number_format($income, 2, ',', '.') }}</div>
        </div>

        <!-- Expense Card -->
        <div class="card stat-card" style="border-top: 4px solid var(--danger-color);">
            <div class="stat-icon" style="background-color: #fee2e2; color: var(--danger-color);">
                <i class='bx bx-trending-down' style="font-size: 24px;"></i>
            </div>
            <div class="stat-label">Despesas (Mês)</div>
            <div class="stat-value">R$ {{ number_format($expense, 2, ',', '.') }}</div>
            @if($subscriptionExpenses > 0)
                <div style="font-size: 0.7rem; color: var(--text-secondary); margin-top: 4px;">
                    Inclui R$ {{ number_format($subscriptionExpenses, 2, ',', '.') }} em mensalidades
                </div>
            @endif
        </div>

        <!-- Life Hours Card -->
        <div class="card stat-card"
            style="border-top: 4px solid var(--primary-color); background: linear-gradient(135deg, rgba(14, 165, 233, 0.05), rgba(99, 102, 241, 0.05));">
            <div class="stat-icon" style="background-color: rgba(99, 102, 241, 0.1); color: #6366f1;">
                <i class='bx bx-time' style="font-size: 24px;"></i>
            </div>
            <div class="stat-label">Saldo em horas</div>
            <div class="stat-value" style="color: #6366f1;">{{ auth()->user()->formatHoursToReadableTime($balanceHours) }}</div>
        </div>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="font-size: 1.25rem; font-weight: 600;">Últimas Transações</h2>
            <a href="{{ route('transactions.index') }}" class="btn btn-outline"
                style="padding: 6px 12px; font-size: 0.8rem;">Ver Todas</a>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Data</th>
                        <th>Valor</th>
                        <th style="text-align: right;">Custo/Vida</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recent_transactions as $transaction)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div
                                        style="width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background-color: {{ $transaction->type == 'income' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)' }}; color: {{ $transaction->type == 'income' ? 'var(--success-color)' : 'var(--danger-color)' }};">
                                        <i class='bx {{ $transaction->type == 'income' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt' }}'
                                            style="font-size: 20px;"></i>
                                    </div>
                                    <span style="font-weight: 500;">{{ $transaction->description }}</span>
                                </div>
                            </td>
                            <td>{{ $transaction->category }}</td>
                            <td>{{ date('d/m/Y', strtotime($transaction->date)) }}</td>
                            <td>
                                <span
                                    style="font-weight: 600; color: {{ $transaction->type == 'income' ? 'var(--success-color)' : 'var(--danger-color)' }};">
                                    {{ $transaction->type == 'income' ? '+' : '-' }} R$
                                    {{ number_format($transaction->amount, 2, ',', '.') }}
                                </span>
                            </td>
                            <td style="text-align: right; color: var(--text-secondary);">
                                @php $hours = auth()->user()->convertMoneyToHours($transaction->amount); @endphp
                                @if($hours > 0)
                                    <div style="display: inline-flex; align-items: center; gap: 4px; background: var(--glass-bg); padding: 4px 8px; border-radius: var(--radius-md); font-size: 0.85rem;"
                                        title="{{ $transaction->type == 'income' ? 'Horas de vida compensadas' : 'Horas de vida gastas' }}">
                                        <i class='bx bx-time'
                                            style="{{ $transaction->type == 'income' ? 'color: var(--success-color)' : 'color: var(--danger-color)' }}"></i>
                                        {{ auth()->user()->formatHoursToReadableTime($hours) }}
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection