@extends('layouts.app')

@section('content')
<style>
    .profile-layout {
        display: grid;
        grid-template-columns: 1.8fr 1fr;
        gap: 32px;
        margin-top: 24px;
    }

    .form-card {
        background: var(--surface-color);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 32px;
        box-shadow: var(--shadow-md);
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 24px;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .section-title i {
        font-size: 1.5rem;
        color: var(--primary-color);
    }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--text-secondary);
    }

    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-wrapper i {
        position: absolute;
        left: 14px;
        color: var(--text-secondary);
        font-size: 1.2rem;
        transition: var(--transition);
    }

    .input-wrapper input, .input-wrapper select {
        width: 100%;
        padding: 12px 16px 12px 42px;
        background: var(--bg-color);
        border: 1.5px solid var(--border-color);
        border-radius: 12px;
        color: var(--text-primary);
        font-size: 0.95rem;
        font-family: inherit;
        transition: var(--transition);
    }

    .input-wrapper input:focus, .input-wrapper select:focus {
        outline: none;
        border-color: var(--primary-color);
        background: var(--surface-color);
        box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
    }

    .input-wrapper input:focus + i, .input-wrapper select:focus + i {
        color: var(--primary-color);
    }

    .divider {
        height: 1px;
        background: var(--border-color);
        margin: 32px 0;
    }

    .sidebar-cards {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .highlight-card {
        background: linear-gradient(135deg, var(--primary-color), #6366f1);
        color: white;
        padding: 32px;
        border-radius: 20px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 25px -5px rgba(14, 165, 233, 0.4);
    }

    .highlight-card .label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        opacity: 0.9;
    }

    .highlight-card .value {
        font-size: 3rem;
        font-weight: 800;
        margin: 12px 0;
        display: block;
    }

    .highlight-card .desc {
        font-size: 0.9rem;
        line-height: 1.6;
        opacity: 0.85;
    }

    .highlight-card .bg-icon {
        position: absolute;
        right: -20px;
        bottom: -20px;
        font-size: 120px;
        opacity: 0.15;
    }

    .simulator-widget {
        background: var(--surface-color);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 24px;
    }

    .simulator-result {
        margin-top: 20px;
        padding: 20px;
        background: var(--bg-color);
        border: 2px dashed var(--border-color);
        border-radius: 16px;
        text-align: center;
        transition: var(--transition);
    }

    @media (max-width: 992px) {
        .profile-layout {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Configurações de Perfil</h1>
        <p style="color: var(--text-secondary); margin-top: 4px;">Personalize sua experiência e monitore o valor do seu esforço.</p>
    </div>
</div>

<div class="profile-layout animate-fade-in">
    <!-- Main Form Section -->
    <div class="form-card">
        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <h2 class="section-title"><i class='bx bx-id-card'></i> Dados Pessoais</h2>
            
            <div class="responsive-grid-1-1">
                <div class="form-group">
                    <label class="form-label">Nome Completo</label>
                    <div class="input-wrapper">
                        <i class='bx bx-user'></i>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Minha Idade</label>
                    <div class="input-wrapper">
                        <i class='bx bx-calendar-event'></i>
                        <input type="number" name="age" value="{{ old('age', $user->age) }}" placeholder="Ex: 28">
                    </div>
                </div>
            </div>

            <div class="responsive-grid-1-1" style="margin-top: 20px;">
                <div class="form-group">
                    <label class="form-label">Perfil de Investidor</label>
                    <div class="input-wrapper">
                        <i class='bx bx-trending-up'></i>
                        <select name="investment_profile">
                            <option value="">Selecione um perfil...</option>
                            <option value="conservative" {{ old('investment_profile', $user->investment_profile) === 'conservative' ? 'selected' : '' }}>Conservador</option>
                            <option value="moderate" {{ old('investment_profile', $user->investment_profile) === 'moderate' ? 'selected' : '' }}>Moderado</option>
                            <option value="aggressive" {{ old('investment_profile', $user->investment_profile) === 'aggressive' ? 'selected' : '' }}>Arrojado</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Alterar Senha</label>
                    <div class="input-wrapper">
                        <i class='bx bx-lock-alt'></i>
                        <input type="password" name="password" placeholder="Nova senha (opcional)">
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            <h2 class="section-title"><i class='bx bx-wallet'></i> Base Financeira</h2>
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 20px; line-height: 1.5;">
                Essas informações são usadas para calcular o custo de vida nas transações e dashboard.
            </p>

            <div class="responsive-grid-1-1">
                <div class="form-group">
                    <label class="form-label">Salário Mensal Líquido</label>
                    <div class="input-wrapper">
                        <i class='bx bx-money'></i>
                        <input type="number" step="0.01" name="salary" id="user-salary" value="{{ old('salary', $user->salary) }}" placeholder="0,00">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Carga Horária (Mensal)</label>
                    <div class="input-wrapper">
                        <i class='bx bx-stopwatch'></i>
                        <input type="number" name="monthly_working_hours" id="user-hours" value="{{ old('monthly_working_hours', $user->monthly_working_hours) }}" placeholder="Ex: 160">
                    </div>
                </div>
            </div>

            <div style="margin-top: 40px; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary" style="padding: 14px 40px; border-radius: 14px; font-size: 1rem;">
                    Atualizar Perfil
                </button>
            </div>
        </form>
    </div>

    <!-- Sidebar Widgets -->
    <div class="sidebar-cards">
        <div class="highlight-card">
            <span class="label">O valor do seu tempo</span>
            @php
                $hourlyRate = ($user->salary && $user->monthly_working_hours && $user->monthly_working_hours > 0) 
                    ? $user->salary / $user->monthly_working_hours 
                    : 0;
            @endphp
            <span class="value">R$ {{ number_format($hourlyRate, 2, ',', '.') }}</span>
            <p class="desc">
                Cada hora da sua vida em que você está trabalhando equivale a este valor. Lembre-se disso ao realizar um novo gasto.
            </p>
            <i class='bx bx-time-five bg-icon'></i>
        </div>

        <div class="simulator-widget">
            <h3 class="section-title" style="font-size: 1.1rem; margin-bottom: 8px;">Simulador de Esforço</h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 20px;">Quanto tempo de trabalho custa esse item?</p>
            
            <div class="input-wrapper">
                <i class='bx bx-shopping-bag'></i>
                <input type="number" step="0.01" id="simulator-amount" placeholder="Valor do produto (R$)">
            </div>
            
            <div id="simulator-result" style="display: none;" class="simulator-result">
                <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--text-secondary);">Isso custará:</span>
                <div id="simulator-hours" style="font-size: 2.5rem; font-weight: 800; color: var(--primary-color); margin: 8px 0;">0h</div>
                <span style="font-size: 0.85rem; color: var(--text-secondary);">de trabalho dedicado.</span>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hourlyRatePhp = {{ $hourlyRate }};
    const inputSimulator = document.getElementById('simulator-amount');
    const resultDiv = document.getElementById('simulator-result');
    const hoursSpan = document.getElementById('simulator-hours');
    
    const userSalaryInput = document.getElementById('user-salary');
    const userHoursInput = document.getElementById('user-hours');

    function calculateHours() {
        const amount = parseFloat(inputSimulator.value);
        let currentRate = hourlyRatePhp;
        
        const salary = parseFloat(userSalaryInput.value);
        const hours = parseFloat(userHoursInput.value);
        
        if(!isNaN(salary) && !isNaN(hours) && hours > 0) {
            currentRate = salary / hours;
        }

        if(isNaN(amount) || amount <= 0 || currentRate <= 0) {
            resultDiv.style.display = 'none';
        } else {
            const result = amount / currentRate;
            resultDiv.style.display = 'block';
            hoursSpan.textContent = result.toFixed(1) + 'h';
        }
    }

    inputSimulator.addEventListener('input', calculateHours);
    userSalaryInput.addEventListener('input', calculateHours);
    userHoursInput.addEventListener('input', calculateHours);
});
</script>
@endsection
