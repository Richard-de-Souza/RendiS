@extends('layouts.app')

@section('content')
<div class="page-header" style="justify-content: space-between; align-items: flex-end;">
    <div>
        <h1 class="page-title">Carteira Recomendada</h1>
        <p style="color: var(--text-secondary);">Estratégia personalizada para o perfil <strong>{{ ucfirst($user->investment_profile) }}</strong></p>
    </div>
    <div style="text-align: right;">
        <span class="badge" style="background: rgba(14, 165, 233, 0.1); color: var(--primary-color); font-size: 0.9rem; padding: 8px 16px;">
            Perfil {{ ucfirst($user->investment_profile) }}
        </span>
        <a href="{{ route('investments.assessment') }}" style="display: block; font-size: 0.75rem; margin-top: 8px; color: var(--text-secondary); text-decoration: underline;">Alterar Perfil</a>
    </div>
</div>

<div class="stats-grid">
    <div class="card stat-card" style="border-top: 4px solid var(--primary-color);">
        <div class="stat-label">Ativos Recomendados</div>
        <div class="stat-value">{{ $recommendations->count() }}</div>
    </div>
    
    <div class="card stat-card" style="border-top: 4px solid var(--success-color);">
        <div class="stat-label">Objetivo Mensal</div>
        <div class="stat-value">Aposentadoria</div>
    </div>
</div>

<div class="card">
    <h2 style="font-size: 1.25rem; margin-bottom: 24px;">Distribuição Ideal do Patrimônio</h2>
    
    <div style="display: flex; flex-direction: column; gap: 24px;">
        @forelse($recommendations as $rec)
        <div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; align-items: flex-end;">
                <div>
                    <span style="display: block; font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase;">{{ $rec->investmentAsset->category }}</span>
                    <span style="font-weight: 600; font-size: 1.1rem;">{{ $rec->investmentAsset->name }}</span>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 1.25rem; font-weight: 700; color: var(--primary-color);">{{ $rec->percentage }}%</span>
                </div>
            </div>
            <!-- Progress Bar -->
            <div style="width: 100%; height: 12px; background: var(--border-color); border-radius: 6px; overflow: hidden;">
                <div style="width: {{ $rec->percentage }}%; height: 100%; background: var(--primary-color); border-radius: 6px;"></div>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 8px;">{{ $rec->investmentAsset->description }}</p>
        </div>
        @empty
        <div style="padding: 48px; text-align: center; color: var(--text-secondary);">
            <i class='bx bx-info-circle' style="font-size: 48px; opacity: 0.1; margin-bottom: 16px;"></i>
            <p>Nenhuma recomendação definida pelo administrador ainda.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection

