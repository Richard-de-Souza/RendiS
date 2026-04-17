<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InvestmentAsset;
use App\Models\PortfolioRecommendation;
use App\Models\User;

class InvestmentController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        if (!$user->investment_profile) {
            return view('investments.welcome');
        }

        $recommendations = PortfolioRecommendation::with('investmentAsset')
            ->where('profile_level', $user->investment_profile)
            ->get();

        return view('investments', compact('user', 'recommendations'));
    }

    public function assessment()
    {
        return view('investments.assessment');
    }

    public function setProfile(Request $request)
    {
        $validated = $request->validate([
            'profile' => 'required|in:conservative,moderate,aggressive'
        ]);

        $user = auth()->user();
        $user->update(['investment_profile' => $validated['profile']]);

        return redirect()->route('investments')->with('success', 'Perfil de investimento definido com sucesso!');
    }
}
