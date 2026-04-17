@extends('layouts.app')

@section('content')
<div class="page-header" style="align-items: center;">
    <div style="display: flex; align-items: center; gap: 16px;">
        <a href="{{ route('subscriptions.index') }}" class="btn btn-outline" style="padding: 8px; border-radius: 50%;">
            <i class='bx bx-arrow-back' style="font-size: 20px;"></i>
        </a>
        <h1 class="page-title" style="margin: 0;">Nova Mensalidade</h1>
    </div>
</div>

<div class="card" style="max-width: 600px;">
    <form action="{{ route('subscriptions.store') }}" method="POST">
        @csrf
        
        <div class="form-group" style="margin-bottom: 24px;">
            <label class="form-label" style="font-size: 1rem;">Tipo de Recorrência</label>
            <div style="display: flex; gap: 16px; margin-top: 12px;">
                <label class="type-label active" id="label-subscription" style="position: relative; flex: 1; text-align: center; border: 1px solid var(--primary-color); border-radius: var(--radius-md); padding: 12px; cursor: pointer; transition: var(--transition); background-color: rgba(14, 165, 233, 0.05);">
                    <input type="radio" name="type" value="subscription" checked style="opacity: 0; position: absolute;">
                    <div class="type-text" style="color: var(--primary-color); font-weight: 600;">
                        <i class='bx bx-refresh' style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                        Assinatura (Infinito)
                    </div>
                </label>
                
                <label class="type-label" id="label-installment" style="position: relative; flex: 1; text-align: center; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px; cursor: pointer; transition: var(--transition); background-color: var(--glass-bg);">
                    <input type="radio" name="type" value="installment" {{ old('type') == 'installment' ? 'checked' : '' }} style="opacity: 0; position: absolute;">
                    <div class="type-text" style="color: var(--text-secondary); font-weight: 600;">
                        <i class='bx bx-calendar-event' style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                        Parcelamento (Meses)
                    </div>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Descrição do Serviço/Conta</label>
            <input type="text" class="form-control" name="description" value="{{ old('description') }}" placeholder="Ex: Netflix, Internet, Aluguel" required>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Valor Mensal (R$)</label>
                <input type="number" step="0.01" class="form-control" name="amount" value="{{ old('amount') }}" placeholder="0,00" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Dia do Vencimento</label>
                <input type="number" min="1" max="31" class="form-control" name="due_day" value="{{ old('due_day', 5) }}" required>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Categoria</label>
                <select class="form-control" name="category" required>
                    <option value="">Selecione...</option>
                    <option value="Streaming" {{ old('category') == 'Streaming' ? 'selected' : '' }}>Streaming</option>
                    <option value="Moradia" {{ old('category') == 'Moradia' ? 'selected' : '' }}>Moradia</option>
                    <option value="Educação" {{ old('category') == 'Educação' ? 'selected' : '' }}>Educação</option>
                    <option value="Saúde" {{ old('category') == 'Saúde' ? 'selected' : '' }}>Saúde</option>
                    <option value="Serviços" {{ old('category') == 'Serviços' ? 'selected' : '' }}>Serviços</option>
                    <option value="Outros" {{ old('category') == 'Outros' ? 'selected' : '' }}>Outros</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Data de Início</label>
                <input type="date" class="form-control" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required>
            </div>
        </div>

        <div id="duration-section" style="display: none; margin-top: 16px;">
            <div class="form-group">
                <label class="form-label">Duração (Quantos meses?)</label>
                <input type="number" min="1" class="form-control" name="duration_months" value="{{ old('duration_months') }}" placeholder="Ex: 12">
            </div>
        </div>
        
        <div style="margin-top: 32px; display: flex; justify-content: flex-end; gap: 12px;">
            <a href="{{ route('subscriptions.index') }}" class="btn btn-outline" style="padding: 12px 24px;">Cancelar</a>
            <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">Salvar Mensalidade</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('input[name="type"]');
    const labelSubscription = document.getElementById('label-subscription');
    const labelInstallment = document.getElementById('label-installment');
    const durationSection = document.getElementById('duration-section');

    function updateVisuals() {
        const selected = document.querySelector('input[name="type"]:checked').value;
        
        if (selected === 'subscription') {
            labelSubscription.style.borderColor = 'var(--primary-color)';
            labelSubscription.style.backgroundColor = 'rgba(14, 165, 233, 0.05)';
            labelSubscription.querySelector('.type-text').style.color = 'var(--primary-color)';
            
            labelInstallment.style.borderColor = 'var(--border-color)';
            labelInstallment.style.backgroundColor = 'var(--glass-bg)';
            labelInstallment.querySelector('.type-text').style.color = 'var(--text-secondary)';
            
            durationSection.style.display = 'none';
        } else {
            labelInstallment.style.borderColor = 'var(--primary-color)';
            labelInstallment.style.backgroundColor = 'rgba(14, 165, 233, 0.05)';
            labelInstallment.querySelector('.type-text').style.color = 'var(--primary-color)';
            
            labelSubscription.style.borderColor = 'var(--border-color)';
            labelSubscription.style.backgroundColor = 'var(--glass-bg)';
            labelSubscription.querySelector('.type-text').style.color = 'var(--text-secondary)';
            
            durationSection.style.display = 'block';
        }
    }

    radios.forEach(r => r.addEventListener('change', updateVisuals));
    updateVisuals();
});
</script>
@endsection
