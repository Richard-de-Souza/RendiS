@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">Qual é o seu Perfil de Investidor?</h1>
</div>

<p style="color: var(--text-secondary); margin-bottom: 32px; max-width: 600px;">
    Para recomendarmos a melhor estratégia para o seu patrimônio, precisamos entender sua tolerância a riscos e seus objetivos.
</p>

<form action="{{ route('investments.profile') }}" method="POST">
    @csrf
    <div class="responsive-grid-3">
        <!-- Conservador -->
        <label class="profile-card-label">
            <input type="radio" name="profile" value="conservative" style="display: none;">
            <div class="glass-card profile-card">
                <div class="profile-icon" style="background-color: #dcfce7; color: #16a34a;">
                    <i class='bx bx-shield-quarter'></i>
                </div>
                <h3>Conservador</h3>
                <p>Priorizo a segurança e liquidez. Não me sinto confortável com oscilações no patrimônio.</p>
                <div class="profile-tags">
                    <span>Baixo Risco</span>
                    <span>Segurança</span>
                </div>
            </div>
        </label>

        <!-- Moderado -->
        <label class="profile-card-label">
            <input type="radio" name="profile" value="moderate" style="display: none;">
            <div class="glass-card profile-card">
                <div class="profile-icon" style="background-color: #fef9c3; color: #ca8a04;">
                    <i class='bx bx-layer'></i>
                </div>
                <h3>Moderado</h3>
                <p>Aceito pequenas oscilações em busca de um retorno acima da inflação no longo prazo.</p>
                <div class="profile-tags">
                    <span>Médio Risco</span>
                    <span>Equilíbrio</span>
                </div>
            </div>
        </label>

        <!-- Arrojado -->
        <label class="profile-card-label">
            <input type="radio" name="profile" value="aggressive" style="display: none;">
            <div class="glass-card profile-card">
                <div class="profile-icon" style="background-color: #fee2e2; color: #dc2626;">
                    <i class='bx bx-rocket'></i>
                </div>
                <h3>Arrojado</h3>
                <p>Busco a maximização do patrimônio e aceito volatilidade em troca de altos retornos.</p>
                <div class="profile-tags">
                    <span>Alto Risco</span>
                    <span>Crescimento</span>
                </div>
            </div>
        </label>
    </div>

    <div style="margin-top: 48px; text-align: center;">
        <button type="submit" class="btn btn-primary" style="padding: 16px 48px; font-size: 1.1rem;">
            Confirmar meu Perfil
        </button>
    </div>
</form>

<style>
.profile-card-label {
    cursor: pointer;
}

.profile-card {
    padding: 32px;
    height: 100%;
    transition: var(--transition);
    border: 2px solid transparent;
    text-align: center;
}

.profile-card:hover {
    transform: translateY(-8px);
    border-color: var(--primary-color);
}

.profile-card-label input:checked + .profile-card {
    border-color: var(--primary-color);
    background: rgba(14, 165, 233, 0.05);
}

.profile-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin: 0 auto 24px;
}

.profile-card h3 {
    margin-bottom: 12px;
    font-size: 1.5rem;
}

.profile-card p {
    font-size: 0.95rem;
    color: var(--text-secondary);
    line-height: 1.6;
    margin-bottom: 24px;
}

.profile-tags {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.profile-tags span {
    font-size: 0.75rem;
    padding: 4px 12px;
    background: var(--border-color);
    border-radius: 20px;
    color: var(--text-secondary);
}
</style>
@endsection
