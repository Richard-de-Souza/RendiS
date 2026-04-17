<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvestmentAsset extends Model
{
    protected $fillable = ['name', 'category', 'description'];

    public function recommendations()
    {
        return $this->hasMany(PortfolioRecommendation::class);
    }
}
