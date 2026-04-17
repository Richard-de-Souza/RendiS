@extends('layouts.app')

@section('content')
<div class="page-header" style="align-items: center;">
    <div style="display: flex; align-items: center; gap: 16px;">
        <a href="{{ route('admin.users') }}" class="btn btn-outline" style="padding: 8px; border-radius: 50%;">
            <i class='bx bx-arrow-back' style="font-size: 20px;"></i>
        </a>
        <h1 class="page-title" style="margin: 0;">Novo Usuário</h1>
    </div>
</div>

<div class="card" style="max-width: 600px;">
    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label class="form-label">Nome Completo</label>
            <input type="text" name="name" class="form-control" placeholder="Ex: João Silva" required value="{{ old('name') }}">
        </div>

        <div class="form-group">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" placeholder="joao@exemplo.com" required value="{{ old('email') }}">
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Senha Inicial</label>
                <input type="password" name="password" class="form-control" placeholder="Pelo menos 8 caracteres" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Perfil (Role)</label>
                <select name="role_id" class="form-control" required>
                    <option value="">Selecione...</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="margin-top: 32px; display: flex; justify-content: flex-end; gap: 12px;">
            <a href="{{ route('admin.users') }}" class="btn btn-outline" style="padding: 12px 24px;">Cancelar</a>
            <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">Criar Usuário</button>
        </div>
    </form>
</div>
@endsection
