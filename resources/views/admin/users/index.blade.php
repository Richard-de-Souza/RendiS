@extends('layouts.app')

@section('content')
<div class="page-header" style="justify-content: space-between;">
    <div>
        <h1 class="page-title">Gestão de Usuários</h1>
        <p style="color: var(--text-secondary);">Gerencie permissões e monitore o uso da plataforma.</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class='bx bx-user-plus'></i> Novo Usuário
    </a>
</div>

<div class="card">
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Perfil</th>
                    <th style="text-align: center;">Atividades</th>
                    <th>Status</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr style="{{ $user->trashed() ? 'opacity: 0.6; background-color: rgba(var(--danger-color-rgb), 0.02);' : '' }}">
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 32px; height: 32px; background: var(--bg-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--primary-color);">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                            <div>
                                <div style="font-weight: 600; color: var(--text-primary);">{{ $user->name }} {{ $user->id === auth()->id() ? '(Você)' : '' }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-secondary);">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge" style="background: rgba(14, 165, 233, 0.1); color: var(--primary-color);">
                            {{ $user->role->name ?? 'Sem Perfil' }}
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <span title="Transações" style="margin-right: 8px;"><i class='bx bx-transfer'></i> {{ $user->transactions_count }}</span>
                        <span title="Mensalidades"><i class='bx bx-calendar-star'></i> {{ $user->subscriptions_count }}</span>
                    </td>
                    <td>
                        @if($user->trashed())
                            <span class="badge badge-danger">Banido</span>
                        @else
                            <span class="badge badge-success">Ativo</span>
                        @endif
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <a href="{{ route('admin.users.audits', $user) }}" class="btn btn-outline" style="padding: 6px; color: var(--primary-color);" title="Histórico de Acesso">
                            <i class='bx bx-history' style="font-size: 18px;"></i>
                        </a>
                        
                        @if($user->id !== auth()->id())
                            @if($user->trashed())
                                <form action="{{ route('admin.users.restore', $user->id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-outline" style="padding: 6px; color: var(--success-color);" title="Restaurar Usuário">
                                        <i class='bx bx-undo' style="font-size: 18px;"></i>
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" style="display: inline;" onsubmit="return confirm('Deseja realmente banir este usuário? Ele não conseguirá mais acessar o sistema.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline" style="padding: 6px; color: var(--danger-color);" title="Banir Usuário">
                                        <i class='bx bx-user-x' style="font-size: 18px;"></i>
                                    </button>
                                </form>
                            @endif
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
