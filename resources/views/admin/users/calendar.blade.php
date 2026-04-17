@extends('layouts.app')

@section('content')
<style>
    .calendar-container {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 12px;
        margin-top: 24px;
    }

    .calendar-header-day {
        text-align: center;
        font-weight: 700;
        color: var(--text-secondary);
        font-size: 0.8rem;
        text-transform: uppercase;
        padding-bottom: 12px;
    }

    .calendar-day {
        min-height: 110px;
        background: var(--surface-color);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 12px;
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        cursor: pointer;
        position: relative;
    }

    .calendar-day:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-color);
    }

    .calendar-day.empty {
        background: transparent;
        border-style: dashed;
        opacity: 0.3;
        cursor: default;
    }

    .calendar-day.empty:hover {
        transform: none;
        box-shadow: none;
    }

    .day-number {
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--text-secondary);
        margin-bottom: 8px;
    }

    .day-today .day-number {
        color: var(--primary-color);
        background: rgba(14, 165, 233, 0.1);
        padding: 2px 6px;
        border-radius: 4px;
    }

    /* Avatar Stack Styling */
    .avatar-stack {
        display: flex;
        align-items: center;
        margin-top: auto;
        padding-bottom: 4px;
    }

    .avatar-bubble {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 700;
        border: 2px solid var(--surface-color);
        margin-left: -8px;
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
    }

    .avatar-bubble:first-child {
        margin-left: 0;
    }

    .avatar-bubble:hover {
        transform: translateY(-4px);
        z-index: 10;
    }

    .more-indicator {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-secondary);
        margin-left: 8px;
        background: var(--bg-color);
        padding: 2px 6px;
        border-radius: 4px;
        border: 1px solid var(--border-color);
    }

    .audit-row {
        display: flex; 
        align-items: center; 
        gap: 12px; 
        padding: 10px; 
        border-bottom: 1px solid var(--border-color);
        text-align: left;
    }
    
    .audit-row:last-child { border-bottom: none; }
    
    .audit-time { font-weight: 700; color: var(--primary-color); font-size: 0.9rem; min-width: 60px; }
    .audit-user { flex: 1; font-weight: 600; color: var(--text-primary); }
    .audit-ip { font-size: 0.8rem; color: var(--text-secondary); font-family: monospace; }
</style>

<div class="page-header" style="justify-content: space-between; display: flex; align-items: center;">
    <div>
        <h1 class="page-title">{{ $currentMonth }}</h1>
        <p style="color: var(--text-secondary);">Análise de tráfego escalonável.</p>
    </div>
    <div style="background: var(--surface-color); padding: 8px 16px; border-radius: var(--radius-full); border: 1px solid var(--border-color); font-weight: 600; font-size: 0.9rem;">
        <i class='bx bx-pulse' style="color: var(--primary-color);"></i> Total: {{ $auditsByDay->flatten()->count() }}
    </div>
</div>

<div class="calendar-container animate-fade-in">
    @foreach(['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $dayName)
        <div class="calendar-header-day">{{ $dayName }}</div>
    @endforeach

    @php 
        $today = date('j');
    @endphp

    @for($i = 0; $i < $startDayOfWeek; $i++)
        <div class="calendar-day empty"></div>
    @endfor

    @for($day = 1; $day <= $daysInMonth; $day++)
        @php
            $dayAudits = $auditsByDay[$day] ?? collect();
            $uniqueUsers = $dayAudits->unique('user_id');
            $displayUsers = $uniqueUsers->take(5);
            $moreCount = $uniqueUsers->count() - 5;
            
            // Preparar dados para o modal
            $modalData = $dayAudits->map(function($a) {
                return [
                    'time' => $a->logged_at->format('H:i'),
                    'user' => $a->user->name,
                    'ip' => $a->ip_address
                ];
            });
        @endphp
        
        <div class="calendar-day {{ $day == $today ? 'day-today' : '' }}" 
             onclick="showDayDetails('{{ $day }}', '{{ $currentMonth }}', @json($modalData))">
            <div class="day-number">{{ $day }}</div>
            
            @if($uniqueUsers->count() > 0)
                <div class="avatar-stack">
                    @foreach($displayUsers as $audit)
                        @php 
                            $initials = collect(explode(' ', $audit->user->name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('');
                        @endphp
                        <div class="avatar-bubble" title="{{ $audit->user->name }}">
                            {{ $initials }}
                        </div>
                    @endforeach
                    
                    @if($moreCount > 0)
                        <span class="more-indicator">+{{ $moreCount }}</span>
                    @endif
                </div>
            @endif
        </div>
    @endfor
</div>

<script>
function showDayDetails(day, month, data) {
    if (data.length === 0) return;

    let html = `<div style="max-height: 400px; overflow-y: auto; margin-top: 10px;">`;
    
    data.forEach(item => {
        html += `
            <div class="audit-row">
                <div class="audit-time">${item.time}</div>
                <div class="audit-user">${item.user}</div>
                <div class="audit-ip">${item.ip}</div>
            </div>
        `;
    });
    
    html += `</div>`;

    if (window.openModal) {
        window.openModal({
            type: 'info',
            title: `Acessos em ${day} de ${month}`,
            message: html,
            confirmText: 'Fechar'
        });
    }
}
</script>
@endsection
