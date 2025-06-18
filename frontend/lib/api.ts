import { 
  Team, 
  Fixture,
  ApiResponse, 
  LeagueTableEntry, 
  ChampionshipPredictionResponse 
} from '@/types';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8001/api';

class ApiClient {
  private baseURL: string;

  constructor(baseURL: string) {
    this.baseURL = baseURL;
  }

  private async request<T>(endpoint: string, options?: RequestInit): Promise<T> {
    const url = `${this.baseURL}${endpoint}`;
    
    const config: RequestInit = {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      ...options,
    };

    try {
      const response = await fetch(url, config);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      return await response.json();
    } catch (error) {
      console.error('API request failed:', error);
      throw error;
    }
  }

  // Special request handler for championship predictions that handles 400 gracefully
  private async requestChampionshipPredictions(): Promise<ApiResponse<ChampionshipPredictionResponse>> {
    const url = `${this.baseURL}/championship-predictions`;
    
    const config: RequestInit = {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    };

    try {
      const response = await fetch(url, config);
      
      if (response.status === 400) {
        // 400 is expected when predictions are not available yet
        const errorData = await response.json();
        return {
          success: false,
          message: errorData.message || 'Predictions not available yet',
          data: {
            is_available: false,
            reason: errorData.message || 'Championship predictions available after week 4'
          } as ChampionshipPredictionResponse
        };
      }
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      return await response.json();
    } catch (error) {
      console.error('Championship predictions request failed:', error);
      // Return a graceful fallback
      return {
        success: false,
        message: 'Failed to fetch predictions',
        data: {
          is_available: false,
          reason: 'Championship predictions available after week 4'
        } as ChampionshipPredictionResponse
      };
    }
  }

  // Teams API
  async getTeams(): Promise<ApiResponse<Team[]>> {
    return this.request<ApiResponse<Team[]>>('/teams');
  }

  // Fixtures API
  async generateFixtures(): Promise<ApiResponse<Fixture[]>> {
    return this.request<ApiResponse<Fixture[]>>('/fixtures', {
      method: 'POST',
    });
  }

  async getFixtures(): Promise<ApiResponse<Fixture[]>> {
    return this.request<ApiResponse<Fixture[]>>('/fixtures');
  }

  // Simulation API
  async simulateNextWeek(): Promise<ApiResponse<{ week: number; matches: unknown[]; total_simulated: number }>> {
    return this.request<ApiResponse<{ week: number; matches: unknown[]; total_simulated: number }>>('/simulate/next-week', {
      method: 'POST',
    });
  }

  async simulateAllRemaining(): Promise<ApiResponse<{ total_matches_simulated: number; message: string }>> {
    return this.request<ApiResponse<{ total_matches_simulated: number; message: string }>>('/simulate/all', {
      method: 'POST',
    });
  }

  // League Table API
  async getLeagueTable(): Promise<ApiResponse<{ standings: LeagueTableEntry[] }>> {
    return this.request<ApiResponse<{ standings: LeagueTableEntry[] }>>('/league-table');
  }

  // Championship Predictions API - Uses special handler
  async getChampionshipPredictions(): Promise<ApiResponse<ChampionshipPredictionResponse>> {
    return this.requestChampionshipPredictions();
  }
}

export const apiClient = new ApiClient(API_BASE_URL); 