@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">Histórico de Transações</h1>
    <a href="{{ route('transactions.create') }}" class="btn btn-primary">
        <i class='bx bx-plus'></i> Nova Transação
    </a>
</div>


<div class="card">
    <div style="display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;">
        <!-- Mock Filters -->
        <select class="form-control" style="width: auto; min-width: 150px;">
            <option>Todas as Categorias</option>
            <option>Trabalho</option>
            <option>Moradia</option>
            <option>Alimentação</option>
        </select>
        
        <select class="form-control" style="width: auto; min-width: 150px;">
            <option>Todos os Tipos</option>
            <option>Receitas</option>
            <option>Despesas</option>
        </select>
        
        <input type="month" class="form-control" style="width: auto; min-width: 150px;" value="2023-10">
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Categoria</th>
                    <th>Status</th>
                    <th style="text-align: right;">Valor</th>
                    <th style="text-align: right;">Custo/Vida</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $transaction)
                <tr>
                    <td>{{ date('d/m/Y', strtotime($transaction->date)) }}</td>
                    <td>
                        <div style="font-weight: 500; color: var(--text-primary);">{{ $transaction->description }}</div>
                    </td>
                    <td>
                        <span class="badge" style="background-color: var(--border-color); color: var(--text-secondary);">{{ $transaction->category }}</span>
                    </td>
                    <td>
                        @if($transaction->status == 'completed')
                            <span class="badge badge-success">Concluído</span>
                        @else
                            <span class="badge badge-danger" style="background-color: #fef08a; color: #854d0e;">Pendente</span>
                        @endif
                    </td>
                    <td style="text-align: right; font-weight: 600; color: {{ $transaction->type == 'income' ? 'var(--success-color)' : 'var(--danger-color)' }};">
                        {{ $transaction->type == 'income' ? '+' : '-' }} R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                    </td>
                    <td style="text-align: right; color: var(--text-secondary);">
                        @php $hours = auth()->user()->convertMoneyToHours($transaction->amount); @endphp
                        @if($hours > 0)
                            <div style="display: inline-flex; align-items: center; gap: 4px; background: var(--glass-bg); padding: 4px 8px; border-radius: var(--radius-md); font-size: 0.85rem;" title="{{ $transaction->type == 'income' ? 'Horas de vida compensadas' : 'Horas de vida gastas' }}">
                                <i class='bx bx-time' style="{{ $transaction->type == 'income' ? 'color: var(--success-color)' : 'color: var(--danger-color)' }}"></i> 
                                {{ auth()->user()->formatHoursToReadableTime($hours) }}
                            </div>
                        @else
                            -
                        @endif
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <a href="{{ route('transactions.edit', $transaction) }}" class="btn btn-outline" style="padding: 6px; color: var(--primary-color); border-color: transparent;">
                            <i class='bx bx-edit-alt' style="font-size: 18px;"></i>
                        </a>
                        <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" style="display: inline;" class="delete-form">
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
</div>

<script>
    function confirmDelete(form) {
        if (window.openModal) {
            window.openModal({
                type: 'error',
                title: 'Confirmar Exclusão',
                message: 'Você tem certeza que deseja remover este item? Esta ação não pode ser desfeita.',
                confirmText: 'Excluir Item',
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
