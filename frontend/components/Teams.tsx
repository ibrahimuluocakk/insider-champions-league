'use client';

import { Team } from '@/types';

interface TeamsProps {
  teams: Team[];
  loading: boolean;
  error: string | null;
}

export default function Teams({ teams, loading, error }: TeamsProps) {
  return (
    <div className="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
          Teams
        </h2>
        <span className="text-sm text-slate-500 dark:text-slate-400">
          {teams.length} teams
        </span>
      </div>
      <div className="space-y-3">
        {loading ? (
          <div className="text-slate-600 dark:text-slate-300 text-center py-8">
            Loading teams...
          </div>
        ) : error ? (
          <div className="text-red-600 dark:text-red-400 text-center py-8">
            {error}
          </div>
        ) : teams.length === 0 ? (
          <div className="text-slate-600 dark:text-slate-300 text-center py-8">
            No teams found
          </div>
        ) : (
          teams.map((team) => (
            <div key={team.id} className="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700 rounded-lg">
              <div className="flex items-center space-x-3">
                <div className="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                  <span className="text-white font-bold text-sm">
                    {team.name.substring(0, 2).toUpperCase()}
                  </span>
                </div>
                <div>
                  <h3 className="font-medium text-slate-900 dark:text-white">
                    {team.name}
                  </h3>
                  <p className="text-sm text-slate-500 dark:text-slate-400">
                    Power: {team.power} | Home: +{team.home_advantage}
                  </p>
                </div>
              </div>
              <div className="text-right">
                <div className="text-sm font-medium text-slate-900 dark:text-white">
                  {team.total_strength}
                </div>
                <div className="text-xs text-slate-500 dark:text-slate-400">
                  Total Strength
                </div>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
} 