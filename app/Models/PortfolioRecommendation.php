<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioRecommendation extends Model
{
    protected $fillable = ['profile_level', 'investment_asset_id', 'percentage'];

    public function investmentAsset()
    {
        return $this->belongsTo(InvestmentAsset::class, 'investment_asset_id');
    }
}
