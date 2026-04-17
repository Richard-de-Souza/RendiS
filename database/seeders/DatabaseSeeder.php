<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminRole = \App\Models\Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);

        $userRole = \App\Models\Role::create([
            'name' => 'Usuário Comum',
            'slug' => 'user',
        ]);

        \App\Models\User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@rendis.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
        ]);

        $joao = \App\Models\User::factory()->create([
            'name' => 'João (Comum)',
            'email' => 'joao@rendis.com',
            'password' => bcrypt('password'),
            'role_id' => $userRole->id,
            'investment_profile' => null, // Reset for assessment
        ]);

        // Seed Assets
        $selic = \App\Models\InvestmentAsset::create([
            'name' => 'Tesouro Selic',
            'category' => 'Renda Fixa',
            'description' => 'Ideal para reserva de emergência, baixa volatilidade.',
        ]);

        $acoes = \App\Models\InvestmentAsset::create([
            'name' => 'Ações Brasil (IBOV)',
            'category' => 'Ações',
            'description' => 'Participação em grandes empresas brasileiras.',
        ]);

        $bitcoin = \App\Models\InvestmentAsset::create([
            'name' => 'Bitcoin',
            'category' => 'Cripto',
            'description' => 'Ativo digital escasso com alto potencial de valorização.',
        ]);

        $fii = \App\Models\InvestmentAsset::create([
            'name' => 'Fundos Imobiliários',
            'category' => 'FIIs',
            'description' => 'Renda mensal isenta de IR através de imóveis.',
        ]);

        // Seed Recommendations
        // Conservative
        \App\Models\PortfolioRecommendation::create(['profile_level' => 'conservative', 'investment_asset_id' => $selic->id, 'percentage' => 100]);

        // Moderate
        \App\Models\PortfolioRecommendation::create(['profile_level' => 'moderate', 'investment_asset_id' => $selic->id, 'percentage' => 50]);
        \App\Models\PortfolioRecommendation::create(['profile_level' => 'moderate', 'investment_asset_id' => $fii->id, 'percentage' => 30]);
        \App\Models\PortfolioRecommendation::create(['profile_level' => 'moderate', 'investment_asset_id' => $acoes->id, 'percentage' => 20]);

        // Aggressive
        \App\Models\PortfolioRecommendation::create(['profile_level' => 'aggressive', 'investment_asset_id' => $selic->id, 'percentage' => 20]);
        \App\Models\PortfolioRecommendation::create(['profile_level' => 'aggressive', 'investment_asset_id' => $acoes->id, 'percentage' => 40]);
        \App\Models\PortfolioRecommendation::create(['profile_level' => 'aggressive', 'investment_asset_id' => $fii->id, 'percentage' => 20]);
        \App\Models\PortfolioRecommendation::create(['profile_level' => 'aggressive', 'investment_asset_id' => $bitcoin->id, 'percentage' => 20]);
    }
}
