@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">Gestão de Perfis de Acesso</h1>
</div>

<div class="card">
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Perfil</th>
                    <th>Slug</th>
                    <th>Nível de Acesso</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="badge" style="background: var(--text-primary); color: white;">Admin</span></td>
                    <td>admin</td>
                    <td>Telas Financeiras e Áreas Administrativas</td>
                </tr>
                <tr>
                    <td><span class="badge" style="background: #e2e8f0; color: var(--text-secondary);">Usuário Comum</span></td>
                    <td>user</td>
                    <td>Apenas Telas Financeiras Pessoais</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
