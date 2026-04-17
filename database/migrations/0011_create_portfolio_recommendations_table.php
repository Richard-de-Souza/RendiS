<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('profile_level'); // conservative, moderate, aggressive
            $table->foreignId('investment_asset_id')->constrained('investment_assets')->onDelete('cascade');
            $table->integer('percentage');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_recommendations');
    }
};
