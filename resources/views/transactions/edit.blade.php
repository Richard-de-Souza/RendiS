@extends('layouts.app')

@section('content')
<div class="page-header" style="align-items: center;">
    <div style="display: flex; align-items: center; gap: 16px;">
        <a href="{{ route('transactions.index') }}" class="btn btn-outline" style="padding: 8px; border-radius: 50%;">
            <i class='bx bx-arrow-back' style="font-size: 20px;"></i>
        </a>
        <h1 class="page-title" style="margin: 0;">Editar Transação</h1>
    </div>
</div>

<div class="card" style="max-width: 600px;">
    <form action="{{ route('transactions.update', $transaction) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="form-group" style="margin-bottom: 24px;">
            <label class="form-label" style="font-size: 1rem;">Tipo de Transação</label>
            <div style="display: flex; gap: 16px; margin-top: 12px;">
                <label class="type-label {{ $transaction->type == 'income' ? 'active' : '' }}" id="label-income" style="flex: 1; text-align: center; border: 1px solid {{ $transaction->type == 'income' ? 'var(--success-color)' : 'var(--border-color)' }}; border-radius: var(--radius-md); padding: 12px; cursor: pointer; transition: var(--transition); background-color: {{ $transaction->type == 'income' ? 'rgba(16, 185, 129, 0.05)' : 'var(--glass-bg)' }};">
                    <input type="radio" name="type" value="income" {{ $transaction->type == 'income' ? 'checked' : '' }} style="display: none;">
                    <div class="type-text" style="color: {{ $transaction->type == 'income' ? 'var(--success-color)' : 'var(--text-secondary)' }}; font-weight: 600;">
                        <i class='bx bx-trending-up' style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                        Receita
                    </div>
                </label>
                
                <label class="type-label {{ $transaction->type == 'expense' ? 'active' : '' }}" id="label-expense" style="flex: 1; text-align: center; border: 1px solid {{ $transaction->type == 'expense' ? 'var(--danger-color)' : 'var(--border-color)' }}; border-radius: var(--radius-md); padding: 12px; cursor: pointer; transition: var(--transition); background-color: {{ $transaction->type == 'expense' ? 'rgba(239, 68, 68, 0.05)' : 'var(--glass-bg)' }};">
                    <input type="radio" name="type" value="expense" {{ $transaction->type == 'expense' ? 'checked' : '' }} style="display: none;">
                    <div class="type-text" style="color: {{ $transaction->type == 'expense' ? 'var(--danger-color)' : 'var(--text-secondary)' }}; font-weight: 600;">
                        <i class='bx bx-trending-down' style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                        Despesa
                    </div>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Valor (R$)</label>
            <input type="number" step="0.01" class="form-control" name="amount" value="{{ old('amount', $transaction->amount) }}" placeholder="0,00" required style="font-size: 1.5rem; height: 60px;">
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Data</label>
                <input type="date" class="form-control" name="date" required value="{{ old('date', $transaction->date) }}">
            </div>
            
            <div class="form-group">
                <label class="form-label">Categoria</label>
                <select class="form-control" name="category" required>
                    <option value="">Selecione...</option>
                    @foreach(['Trabalho' => 'Trabalho/Salário', 'Moradia' => 'Moradia', 'Alimentação' => 'Alimentação', 'Contas' => 'Contas', 'Saúde' => 'Saúde', 'Lazer' => 'Lazer'] as $value => $label)
                        <option value="{{ $value }}" {{ old('category', $transaction->category) == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Descrição</label>
            <input type="text" class="form-control" name="description" value="{{ old('description', $transaction->description) }}" placeholder="Ex: Supermercado do mês" required>
        </div>
        
        <div style="margin-top: 32px; display: flex; justify-content: flex-end; gap: 12px;">
            <a href="{{ route('transactions.index') }}" class="btn btn-outline" style="padding: 12px 24px;">Cancelar</a>
            <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">Atualizar Transação</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('input[name="type"]');
    const labelIncome = document.getElementById('label-income');
    const labelExpense = document.getElementById('label-expense');

    function updateVisuals() {
        const selected = document.querySelector('input[name="type"]:checked').value;
        
        if (selected === 'income') {
            labelIncome.style.borderColor = 'var(--success-color)';
            labelIncome.style.backgroundColor = 'rgba(16, 185, 129, 0.05)';
            labelIncome.querySelector('.type-text').style.color = 'var(--success-color)';
            
            labelExpense.style.borderColor = 'var(--border-color)';
            labelExpense.style.backgroundColor = 'var(--glass-bg)';
            labelExpense.querySelector('.type-text').style.color = 'var(--text-secondary)';
        } else {
            labelExpense.style.borderColor = 'var(--danger-color)';
            labelExpense.style.backgroundColor = 'rgba(239, 68, 68, 0.05)';
            labelExpense.querySelector('.type-text').style.color = 'var(--danger-color)';
            
            labelIncome.style.borderColor = 'var(--border-color)';
            labelIncome.style.backgroundColor = 'var(--glass-bg)';
            labelIncome.querySelector('.type-text').style.color = 'var(--text-secondary)';
        }
    }

    radios.forEach(r => r.addEventListener('change', updateVisuals));
    updateVisuals();
});
</script>
@endsection
