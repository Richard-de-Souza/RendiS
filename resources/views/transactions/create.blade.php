@extends('layouts.app')

@section('content')
<div class="page-header" style="align-items: center;">
    <div style="display: flex; align-items: center; gap: 16px;">
        <a href="{{ route('transactions.index') }}" class="btn btn-outline" style="padding: 8px; border-radius: 50%;">
            <i class='bx bx-arrow-back' style="font-size: 20px;"></i>
        </a>
        <h1 class="page-title" style="margin: 0;">Nova Transação</h1>
    </div>
</div>

<div class="card" style="max-width: 600px;">
    <form action="{{ route('transactions.store') }}" method="POST">
        @csrf
        
        <div class="form-group" style="margin-bottom: 24px;">
            <label class="form-label" style="font-size: 1rem;">Tipo de Transação</label>
            <div style="display: flex; gap: 16px; margin-top: 12px;">
                <label class="type-label" id="label-income" style="flex: 1; text-align: center; border: 1px solid var(--success-color); border-radius: var(--radius-md); padding: 12px; cursor: pointer; transition: var(--transition); background-color: rgba(16, 185, 129, 0.05);">
                    <input type="radio" name="type" value="income" checked style="display: none;">
                    <div class="type-text" style="color: var(--success-color); font-weight: 600;">
                        <i class='bx bx-trending-up' style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                        Receita
                    </div>
                </label>
                
                <label class="type-label" id="label-expense" style="flex: 1; text-align: center; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px; cursor: pointer; transition: var(--transition); background-color: var(--glass-bg);">
                    <input type="radio" name="type" value="expense" {{ old('type') == 'expense' ? 'checked' : '' }} style="display: none;">
                    <div class="type-text" style="color: var(--text-secondary); font-weight: 600;">
                        <i class='bx bx-trending-down' style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                        Despesa
                    </div>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Valor (R$)</label>
            <input type="number" step="0.01" class="form-control" name="amount" value="{{ old('amount') }}" placeholder="0,00" required style="font-size: 1.5rem; height: 60px;">
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Data</label>
                <input type="date" class="form-control" name="date" required value="{{ old('date', date('Y-m-d')) }}">
            </div>
            
            <div class="form-group">
                <label class="form-label">Categoria</label>
                <select class="form-control" name="category" required>
                    <option value="">Selecione...</option>
                    <option value="Trabalho">Trabalho/Salário</option>
                    <option value="Moradia">Moradia</option>
                    <option value="Alimentação">Alimentação</option>
                    <option value="Contas">Contas</option>
                    <option value="Saúde">Saúde</option>
                    <option value="Lazer">Lazer</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Descrição</label>
            <input type="text" class="form-control" name="description" value="{{ old('description') }}" placeholder="Ex: Supermercado do mês" required>
        </div>
        
        <div style="margin-top: 32px; display: flex; justify-content: flex-end; gap: 12px;">
            <a href="{{ route('transactions.index') }}" class="btn btn-outline" style="padding: 12px 24px;">Cancelar</a>
            <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">Salvar Transação</button>
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
    
    // Call once on load in case old() selected expense
    updateVisuals();
});
</script>
@endsection
