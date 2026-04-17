<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InvestmentAsset;
use App\Models\PortfolioRecommendation;

class AdminInvestmentController extends Controller
{
    public function index()
    {
        $assets = InvestmentAsset::all();
        $recommendations = PortfolioRecommendation::all()->groupBy('profile_level');
        
        return view('admin.investments.index', compact('assets', 'recommendations'));
    }

    public function storeAsset(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        InvestmentAsset::create($validated);

        return redirect()->route('admin.investments')->with('success', 'Ativo criado com sucesso!');
    }

    public function updatePortfolio(Request $request)
    {
        $data = $request->validate([
            'recommendations' => 'required|array',
            'recommendations.*.*' => 'nullable|integer|min:0|max:100',
        ]);

        foreach ($data['recommendations'] as $profile => $assets) {
            foreach ($assets as $assetId => $percentage) {
                if ($percentage !== null) {
                    PortfolioRecommendation::updateOrCreate(
                        ['profile_level' => $profile, 'investment_asset_id' => $assetId],
                        ['percentage' => $percentage]
                    );
                }
            }
        }

        return redirect()->route('admin.investments')->with('success', 'Carteiras atualizadas com sucesso!');
    }
}
