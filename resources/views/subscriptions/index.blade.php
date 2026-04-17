@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">Projeção de Mensalidades</h1>
    <a href="{{ route('subscriptions.create') }}" class="btn btn-primary">
        <i class='bx bx-plus'></i> Nova Mensalidade
    </a>
</div>

<!-- Seção de Projeção (Forecast) -->
<h2 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 16px; color: var(--text-secondary);">
    <i class='bx bx-line-chart'></i> Previsão para os Próximos Meses
</h2>

<div class="responsive-grid-3" style="gap: 48px; margin-bottom: 32px;">
    @foreach($forecast as $monthData)
    <div class="glass-card" style="padding: 20px; border-left: 4px solid {{ $monthData['balance'] >= 0 ? 'var(--primary-color)' : 'var(--danger-color)' }};">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
            <div>
                <span style="display: block; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary);">{{ $monthData['year'] }}</span>
                <span style="display: block; font-size: 1.25rem; font-weight: 700; color: var(--text-primary);">{{ $monthData['month'] }}</span>
            </div>
            <div style="text-align: right;">
                <span style="display: block; font-size: 0.75rem; color: var(--text-secondary);">Contas: {{ count($monthData['subscriptions']) }}</span>
            </div>
        </div>
        
        <div style="margin-bottom: 16px; padding: 12px; background: rgba(0,0,0,0.03); border-radius: var(--radius-md);">
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 0.85rem;">
                <span style="color: var(--text-secondary);">Salário:</span>
                <span style="color: var(--text-primary); font-weight: 500;">R$ {{ number_format($salary, 2, ',', '.') }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
                <span style="color: var(--text-secondary);">Faturas:</span>
                <span style="color: var(--danger-color); font-weight: 500;">
                    - R$ {{ number_format($monthData['total_expenses'], 2, ',', '.') }}
                    <small style="display: block; font-size: 0.75rem; opacity: 0.7; text-align: right;">({{ auth()->user()->formatHoursToReadableTime(auth()->user()->convertMoneyToHours($monthData['total_expenses'])) }})</small>
                </span>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-secondary);">Estimativa Livre:</span>
            <span style="font-size: 1.1rem; font-weight: 800; color: {{ $monthData['balance'] >= 0 ? 'var(--success-color)' : 'var(--danger-color)' }}; text-align: right;">
                R$ {{ number_format($monthData['balance'], 2, ',', '.') }}
                <div style="font-size: 0.85rem; font-weight: 600; opacity: 0.8;">{{ auth()->user()->formatHoursToReadableTime(auth()->user()->convertMoneyToHours($monthData['balance'])) }}</div>
            </span>
        </div>
    </div>
    @endforeach
</div>

<!-- Lista de Assinaturas -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="font-size: 1.25rem; font-weight: 600;">Serviços e Contas Ativas</h2>
    </div>

    @if($subscriptions->isEmpty())
        <div style="padding: 48px; text-align: center; color: var(--text-secondary);">
            <i class='bx bx-calendar-x' style="font-size: 48px; opacity: 0.3; margin-bottom: 16px;"></i>
            <p>Nenhuma mensalidade cadastrada ainda.</p>
        </div>
    @else
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Vencimento</th>
                        <th>Tipo/Duração</th>
                        <th>Valor</th>
                        <th style="text-align: right;">Custo/Vida</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subscriptions as $sub)
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--text-primary);">{{ $sub->description }}</div>
                        </td>
                        <td>
                            <span class="badge" style="background-color: var(--border-color); color: var(--text-secondary);">{{ $sub->category }}</span>
                        </td>
                        <td>Dia {{ $sub->due_day }}</td>
                        <td>
                            @if($sub->is_indefinite)
                                <span style="font-size: 0.85rem; color: var(--text-secondary);">Assinatura (Infinito)</span>
                            @else
                                <span style="font-size: 0.85rem; color: var(--text-secondary);">{{ $sub->duration_months }} Meses (Início em {{ $sub->start_date->format('m/Y') }})</span>
                            @endif
                        </td>
                        <td style="font-weight: 700; color: var(--danger-color);">R$ {{ number_format($sub->amount, 2, ',', '.') }}</td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; align-items: center; gap: 4px; background: var(--glass-bg); padding: 4px 8px; border-radius: var(--radius-md); font-size: 0.85rem; color: var(--text-secondary);">
                                <i class='bx bx-time' style="color: var(--danger-color)"></i>
                                {{ auth()->user()->formatHoursToReadableTime(auth()->user()->convertMoneyToHours($sub->amount)) }}
                            </div>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="{{ route('subscriptions.edit', $sub) }}" class="btn btn-outline" style="padding: 6px; color: var(--primary-color); border-color: transparent;">
                                <i class='bx bx-edit-alt' style="font-size: 18px;"></i>
                            </a>
                            <form action="{{ route('subscriptions.destroy', $sub) }}" method="POST" style="display: inline;" class="delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-outline btn-delete" style="padding: 6px; color: var(--danger-color); border-color: transparent;" onclick="confirmDelete(this.closest('form'))">
                                    <i class='bx bx-trash' style="font-size: 18px;"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<script>
    function confirmDelete(form) {
        if (window.openModal) {
            window.openModal({
                type: 'error',
                title: 'Confirmar Exclusão',
                message: 'Você tem certeza que deseja remover esta mensalidade? Todas as projeções futuras vinculadas a ela serão perdidas.',
                confirmText: 'Excluir Mensalidade',
                cancelText: 'Manter',
                showCancel: true,
                onConfirm: () => {
                    form.submit();
                }
            });
        } else {
            if(confirm('Deseja realmente excluir este item?')) {
                form.submit();
            }
        }
    }
</script>
@endsection
