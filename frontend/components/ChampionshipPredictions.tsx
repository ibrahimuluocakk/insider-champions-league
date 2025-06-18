'use client';

import { ChampionshipPredictionResponse } from '@/types';

interface ChampionshipPredictionsProps {
  championshipPredictions: ChampionshipPredictionResponse | null;
  loading: boolean;
}

export default function ChampionshipPredictions({ championshipPredictions, loading }: ChampionshipPredictionsProps) {
  return (
    <div className="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
          Championship Predictions
        </h2>
        <span className="text-sm text-slate-500 dark:text-slate-400">
          {championshipPredictions?.is_available ? 
            `Week ${championshipPredictions.current_week}` : 
            'Available after week 4'
          }
        </span>
      </div>
      <div className="space-y-3">
        {loading ? (
          <div className="text-slate-600 dark:text-slate-300 text-center py-8">
            Loading predictions...
          </div>
        ) : !championshipPredictions?.is_available ? (
          <div className="text-center py-8">
            <div className="text-slate-600 dark:text-slate-300 mb-2">
              {championshipPredictions?.reason || 'Predictions will be available after week 4'}
            </div>
            {championshipPredictions?.current_week && (
              <div className="text-sm text-slate-500 dark:text-slate-400">
                Current week: {championshipPredictions.current_week} | 
                Matches played: {championshipPredictions.matches_played}
              </div>
            )}
          </div>
        ) : championshipPredictions.season_completed ? (
          <div className="text-center py-6">
            <div className="mb-4">
              <div className="text-2xl font-bold text-slate-900 dark:text-white mb-2">
                🏆 Season Completed!
              </div>
              <div className="text-lg font-semibold text-green-600 dark:text-green-400">
                Champion: {championshipPredictions.champion?.team_name}
              </div>
              <div className="text-sm text-slate-500 dark:text-slate-400">
                Final Points: {championshipPredictions.champion?.final_points}
              </div>
            </div>
          </div>
        ) : (
          <div className="space-y-4">
            {/* Predictions Info */}
            <div className="bg-slate-50 dark:bg-slate-700 rounded-lg p-4">
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                  <span className="text-slate-500 dark:text-slate-400">Current Week:</span>
                  <span className="ml-2 font-medium text-slate-900 dark:text-white">
                    {championshipPredictions.current_week}
                  </span>
                </div>
                <div>
                  <span className="text-slate-500 dark:text-slate-400">Matches Remaining:</span>
                  <span className="ml-2 font-medium text-slate-900 dark:text-white">
                    {championshipPredictions.matches_remaining}
                  </span>
                </div>
                <div>
                  <span className="text-slate-500 dark:text-slate-400">Simulations:</span>
                  <span className="ml-2 font-medium text-slate-900 dark:text-white">
                    {championshipPredictions.simulation_iterations?.toLocaleString()}
                  </span>
                </div>
                <div>
                  <span className="text-slate-500 dark:text-slate-400">Algorithm:</span>
                  <span className="ml-2 font-medium text-slate-900 dark:text-white">
                    {championshipPredictions.methodology?.type}
                  </span>
                </div>
              </div>
            </div>

            {/* Predictions List */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {championshipPredictions.predictions?.map((prediction) => (
                <div key={prediction.team_id} className="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700 rounded-lg">
                  <div className="flex items-center space-x-3">
                    <div className="flex items-center space-x-2">
                      <span className="text-sm font-medium text-slate-500 dark:text-slate-400 w-6">
                        #{prediction.current_position}
                      </span>
                      <div className="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                        <span className="text-white font-bold text-xs">
                          {prediction.team_name.substring(0, 2).toUpperCase()}
                        </span>
                      </div>
                    </div>
                    <div>
                      <h3 className="font-medium text-slate-900 dark:text-white">
                        {prediction.team_name}
                      </h3>
                      <p className="text-sm text-slate-500 dark:text-slate-400">
                        {prediction.current_points} pts | GD: {prediction.goal_difference > 0 ? '+' : ''}{prediction.goal_difference}
                      </p>
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="flex items-center space-x-2">
                      <div className={`text-xl font-bold ${
                        prediction.championship_probability >= 50 
                          ? 'text-green-600 dark:text-green-400'
                          : prediction.championship_probability >= 25
                            ? 'text-yellow-600 dark:text-yellow-400'
                            : 'text-slate-600 dark:text-slate-300'
                      }`}>
                        {prediction.championship_probability}%
                      </div>
                    </div>
                    <div className="w-20 bg-slate-200 dark:bg-slate-600 rounded-full h-2 mt-2">
                      <div 
                        className={`h-2 rounded-full ${
                          prediction.championship_probability >= 50 
                            ? 'bg-green-500'
                            : prediction.championship_probability >= 25
                              ? 'bg-yellow-500'
                              : 'bg-slate-400'
                        }`}
                        style={{ width: `${prediction.championship_probability}%` }}
                      ></div>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            {/* Methodology Info */}
            {championshipPredictions.methodology && (
              <div className="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div className="text-sm text-blue-800 dark:text-blue-200">
                  <div className="font-medium mb-1">Prediction Methodology:</div>
                  <div className="text-xs">
                    {championshipPredictions.methodology.algorithm}
                  </div>
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
} 