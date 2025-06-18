<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('away_team_id')->constrained('teams')->onDelete('cascade');
            $table->integer('week')->comment('Week number (1-6)');
            $table->integer('home_goals')->nullable()->comment('Goals scored by home team');
            $table->integer('away_goals')->nullable()->comment('Goals scored by away team');
            $table->boolean('is_played')->default(false)->comment('Whether match has been played');
            $table->timestamp('played_at')->nullable()->comment('When the match was played');
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['home_team_id', 'away_team_id']);
            $table->index('week');
            $table->index('is_played');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
