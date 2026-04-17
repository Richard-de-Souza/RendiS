@extends('layouts.app')

@section('content')
<div class="card" style="text-align: center; padding: 64px 32px; margin-top: 48px;">
    <div style="background: rgba(14, 165, 233, 0.1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px;">
        <i class='bx bx-compass' style="font-size: 40px; color: var(--primary-color);"></i>
    </div>
    
    <h1 style="font-size: 2rem; margin-bottom: 16px;">Sua Jornada de Investimentos</h1>
    <p style="color: var(--text-secondary); margin-bottom: 40px; max-width: 500px; margin-left: auto; margin-right: auto;">
        Para começarmos, precisamos saber que tipo de investidor você é. Isso nos permite sugerir uma carteira ideal para os seus objetivos.
    </p>

    <a href="{{ route('investments.assessment') }}" class="btn btn-primary" style="padding: 16px 48px; font-size: 1.1rem;">
        Fazer Avaliação de Perfil
    </a>
</div>
@endsection
