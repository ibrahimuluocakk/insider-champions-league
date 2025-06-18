export interface Team {
  id: number;
  name: string;
  power: number;
  home_advantage: number;
  total_strength: number;
}

export interface Fixture {
  id: number;
  week: number;
  home_team: {
    id: number;
    name: string;
    power: number;
  };
  away_team: {
    id: number;
    name: string;
    power: number;
  };
  is_played: boolean;
  home_goals: number | null;
  away_goals: number | null;
  score: string | null;
  result: string | null;
  played_at: string | null;
  created_at: string;
}

export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
}

export interface LeagueTableEntry {
  team_id: number;
  team_name: string;
  matches_played: number;
  wins: number;
  draws: number;
  losses: number;
  goals_for: number;
  goals_against: number;
  goal_difference: number;
  points: number;
}

export interface ChampionshipPrediction {
  team_id: number;
  team_name: string;
  current_position: number;
  current_points: number;
  matches_played: number;
  championship_probability: number;
  simulations_won: number;
  goal_difference: number;
}

export interface ChampionshipPredictionResponse {
  is_available: boolean;
  current_week?: number;
  matches_played?: number;
  matches_remaining?: number;
  simulation_iterations?: number;
  predictions?: ChampionshipPrediction[];
  methodology?: {
    type: string;
    factors: string[];
    iterations: number;
    algorithm: string;
  };
  reason?: string;
  season_completed?: boolean;
  champion?: {
    team_id: number;
    team_name: string;
    final_points: number;
    championship_probability: number;
  };
} 