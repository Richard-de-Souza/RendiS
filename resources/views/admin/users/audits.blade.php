@extends('layouts.app')

@section('content')
<div class="page-header" style="align-items: center;">
    <div style="display: flex; align-items: center; gap: 16px;">
        <a href="{{ route('admin.users') }}" class="btn btn-outline" style="padding: 8px; border-radius: 50%;">
            <i class='bx bx-arrow-back' style="font-size: 20px;"></i>
        </a>
        <div>
            <h1 class="page-title" style="margin: 0;">Histórico de Acesso</h1>
            <p style="color: var(--text-secondary); margin: 0;">Registro de logins para <strong>{{ $user->name }}</strong></p>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Data e Hora</th>
                    <th>Endereço IP</th>
                    <th>Dispositivo / User Agent</th>
                </tr>
            </thead>
            <tbody>
                @forelse($audits as $audit)
                <tr>
                    <td style="font-weight: 600; color: var(--text-primary);">
                        {{ $audit->logged_at->format('d/m/Y H:i:s') }}
                    </td>
                    <td>
                        <code style="background: var(--bg-color); padding: 4px 8px; border-radius: 4px;">{{ $audit->ip_address }}</code>
                    </td>
                    <td style="font-size: 0.85rem; color: var(--text-secondary);">
                        {{ $audit->user_agent }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="text-align: center; padding: 48px; color: var(--text-secondary);">
                        <i class='bx bx-info-circle' style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                        Nenhum registro de acesso encontrado para este usuário.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
