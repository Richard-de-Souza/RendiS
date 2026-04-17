@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">Gestão de Investimentos (Admin)</h1>
</div>

<div class="grid-2">
    <!-- Cadastro de Ativos -->
    <div class="card">
        <h2 style="font-size: 1.25rem; margin-bottom: 24px;">Cadastrar Novo Ativo</h2>
        <form action="{{ route('admin.assets.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Nome do Ativo</label>
                <input type="text" name="name" class="form-control" placeholder="Ex: Tesouro Selic, PETR4, Bitcoin" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Categoria</label>
                <select name="category" class="form-control" required>
                    <option value="Renda Fixa">Renda Fixa</option>
                    <option value="Ações">Ações</option>
                    <option value="FIIs">Fundos Imobiliários</option>
                    <option value="Cripto">Criptomoedas</option>
                    <option value="Internacional">Internacional</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Descrição</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Opcional..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-full" style="margin-top: 16px;">Criar Ativo</button>
        </form>
    </div>

    <!-- Tabela de Ativos -->
    <div class="card">
        <h2 style="font-size: 1.25rem; margin-bottom: 24px;">Ativos Cadastrados</h2>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Categoria</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assets as $asset)
                    <tr>
                        <td>#{{ $asset->id }}</td>
                        <td style="font-weight: 600;">{{ $asset->name }}</td>
                        <td>{{ $asset->category }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Matriz de Carteira Recomendada -->
<div class="card" style="margin-top: 32px;">
    <h2 style="font-size: 1.25rem; margin-bottom: 24px;">Configurar Carteiras Recomendadas</h2>
    
    <form action="{{ route('admin.portfolio.update') }}" method="POST">
        @csrf
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ativo</th>
                        <th style="text-align: center; color: #16a34a;">Conservador (%)</th>
                        <th style="text-align: center; color: #ca8a04;">Moderado (%)</th>
                        <th style="text-align: center; color: #dc2626;">Arrojado (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assets as $asset)
                    <tr>
                        <td style="font-weight: 600;">{{ $asset->name }}</td>
                        <td style="text-align: center;">
                            <input type="number" name="recommendations[conservative][{{ $asset->id }}]" 
                                value="{{ optional($recommendations->get('conservative')?->where('investment_asset_id', $asset->id)->first())->percentage }}" 
                                class="form-control" style="width: 80px; margin: 0 auto; text-align: center;">
                        </td>
                        <td style="text-align: center;">
                            <input type="number" name="recommendations[moderate][{{ $asset->id }}]" 
                                value="{{ optional($recommendations->get('moderate')?->where('investment_asset_id', $asset->id)->first())->percentage }}" 
                                class="form-control" style="width: 80px; margin: 0 auto; text-align: center;">
                        </td>
                        <td style="text-align: center;">
                            <input type="number" name="recommendations[aggressive][{{ $asset->id }}]" 
                                value="{{ optional($recommendations->get('aggressive')?->where('investment_asset_id', $asset->id)->first())->percentage }}" 
                                class="form-control" style="width: 80px; margin: 0 auto; text-align: center;">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 24px; text-align: right;">
            <button type="submit" class="btn btn-primary">Salvar Alocações</button>
        </div>
    </form>
</div>
@endsection
