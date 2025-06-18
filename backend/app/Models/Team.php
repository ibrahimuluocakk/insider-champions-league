<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'power',
        'home_advantage',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'power' => 'integer',
        'home_advantage' => 'integer',
    ];

    /**
     * Get team's total strength including home advantage.
     * @return int
     */
    public function getTotalStrengthAttribute(): int
    {
        return $this->power + $this->home_advantage;
    }

    /**
     * Check if this team is stronger than another team.
     * @param Team $opponent
     * @return bool
     */
    public function isStrongerThan(Team $opponent): bool
    {
        return $this->power > $opponent->power;
    }

    /**
     * Get all home matches for this team.
     * @return HasMany
     */
    public function homeMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'home_team_id');
    }

    /**
     * Get all away matches for this team.
     * @return HasMany
     */
    public function awayMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'away_team_id');
    }

    /**
     * Get all matches for this team (both home and away).
     */
    public function getAllMatches()
    {
        return GameMatch::where('home_team_id', $this->id)
                       ->orWhere('away_team_id', $this->id);
    }
}
