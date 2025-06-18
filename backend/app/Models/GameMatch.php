<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameMatch extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'matches';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'home_team_id',
        'away_team_id',
        'week',
        'home_goals',
        'away_goals',
        'is_played',
        'played_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'home_team_id' => 'integer',
        'away_team_id' => 'integer',
        'week' => 'integer',
        'home_goals' => 'integer',
        'away_goals' => 'integer',
        'is_played' => 'boolean',
        'played_at' => 'datetime',
    ];

    /**
     * Get the home team.
     * @return Team
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * Get the away team.
     * @return Team
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    /**
     * Get match result (W/D/L from home team perspective).
     * @return string|null
     */
    public function getResultAttribute(): ?string
    {
        if (!$this->is_played) {
            return null;
        }

        if ($this->home_goals > $this->away_goals) {
            return 'W'; // Home win
        } elseif ($this->home_goals < $this->away_goals) {
            return 'L'; // Home loss
        } else {
            return 'D'; // Draw
        }
    }

    /**
     * Get match score as string.
     * @return string|null
     */
    public function getScoreAttribute(): ?string
    {
        if (!$this->is_played) {
            return null;
        }

        return $this->home_goals . '-' . $this->away_goals;
    }

    /**
     * Check if match is scheduled for a specific week.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $week
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForWeek($query, int $week)
    {
        return $query->where('week', $week);
    }

    /**
     * Check if match is already played.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePlayed($query)
    {
        return $query->where('is_played', true);
    }

    /**
     * Check if match is not played yet.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotPlayed($query)
    {
        return $query->where('is_played', false);
    }
}
