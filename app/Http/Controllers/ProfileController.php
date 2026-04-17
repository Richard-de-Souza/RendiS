<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:8',
            'age' => 'nullable|integer|min:1|max:150',
            'salary' => 'nullable|numeric|min:0',
            'monthly_working_hours' => 'nullable|integer|min:1',
            'investment_profile' => 'nullable|string|in:conservative,moderate,aggressive',
        ]);

        $user->name = $validated['name'];
        $user->age = $validated['age'] ?? null;
        $user->salary = $validated['salary'] ?? null;
        $user->monthly_working_hours = $validated['monthly_working_hours'] ?? null;
        $user->investment_profile = $validated['investment_profile'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Perfil atualizado com sucesso!');
    }
}
