<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index()
    {
        // Get all users, including soft deleted ones, with their roles and basic transaction counts
        $users = User::withTrashed()
            ->with(['role'])
            ->withCount(['transactions', 'subscriptions'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
        ]);

        return redirect()->route('admin.users')->with('success', 'Usuário criado com sucesso!');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Você não pode banir a si mesmo.']);
        }

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'Usuário banido com sucesso!');
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return redirect()->route('admin.users')->with('success', 'Usuário restaurado com sucesso!');
    }

    public function audits(User $user)
    {
        $audits = $user->loginAudits()->orderBy('logged_at', 'desc')->get();
        return view('admin.users.audits', compact('user', 'audits'));
    }

    public function calendar()
    {
        $now = \Carbon\Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        
        // Buscar auditorias do mês vigente
        $audits = \App\Models\LoginAudit::with('user')
            ->whereBetween('logged_at', [$startOfMonth, $endOfMonth])
            ->get()
            ->groupBy(function($audit) {
                return $audit->logged_at->format('j'); // Agrupar pelo dia do mês (1-31)
            });

        return view('admin.users.calendar', [
            'auditsByDay' => $audits,
            'currentMonth' => $now->translatedFormat('F Y'),
            'daysInMonth' => $now->daysInMonth,
            'startDayOfWeek' => $startOfMonth->dayOfWeek, // 0 (Sun) to 6 (Sat)
        ]);
    }
}
