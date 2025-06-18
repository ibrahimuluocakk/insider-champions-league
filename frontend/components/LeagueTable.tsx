'use client';

import { LeagueTableEntry } from '@/types';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

interface LeagueTableProps {
  leagueTable: LeagueTableEntry[];
  loading: boolean;
}

export default function LeagueTable({ leagueTable, loading }: LeagueTableProps) {
  // Column definitions with tooltips
  const columnDefinitions = {
    'MP': 'Matches Played - Total number of matches played',
    'W': 'Wins - Number of matches won (3 points each)',
    'D': 'Draws - Number of matches drawn (1 point each)',
    'L': 'Losses - Number of matches lost (0 points)',
    'GF': 'Goals For - Total goals scored by the team',
    'GA': 'Goals Against - Total goals conceded by the team',
    'GD': 'Goal Difference - Difference between goals scored and conceded',
    'Pts': 'Points - Total points earned (3 for win, 1 for draw, 0 for loss)'
  };

  return (
    <div className="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
          League Table
        </h2>
        <span className="text-sm text-slate-500 dark:text-slate-400">
          Current standings
        </span>
      </div>
      <div className="space-y-3">
        {loading ? (
          <div className="text-slate-600 dark:text-slate-300 text-center py-8">
            Loading league table...
          </div>
        ) : leagueTable.length === 0 ? (
          <div className="text-slate-600 dark:text-slate-300 text-center py-8">
            No matches played yet
          </div>
        ) : (
          <div className="overflow-x-auto">
            <TooltipProvider>
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-slate-200 dark:border-slate-600">
                    <th className="text-left py-2 text-slate-900 dark:text-white font-medium">#</th>
                    <th className="text-left py-2 text-slate-900 dark:text-white font-medium">Team</th>
                    {Object.entries(columnDefinitions).map(([key, description]) => (
                      <th key={key} className="text-center py-2 text-slate-900 dark:text-white font-medium">
                        <Tooltip>
                          <TooltipTrigger asChild>
                            <span className="cursor-help underline decoration-dotted underline-offset-2">
                              {key}
                            </span>
                          </TooltipTrigger>
                          <TooltipContent>
                            <p className="max-w-xs text-center">{description}</p>
                          </TooltipContent>
                        </Tooltip>
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {leagueTable.map((team, index) => (
                    <tr key={team.team_id} className="border-b border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700">
                      <td className="py-3 text-slate-900 dark:text-white font-medium">
                        {index + 1}
                      </td>
                      <td className="py-3">
                        <div className="flex items-center space-x-2">
                          <div className="w-6 h-6 bg-gradient-to-r from-blue-500 to-purple-500 rounded flex items-center justify-center">
                            <span className="text-white font-bold text-xs">
                              {team.team_name.substring(0, 2).toUpperCase()}
                            </span>
                          </div>
                          <span className="text-slate-900 dark:text-white font-medium">
                            {team.team_name}
                          </span>
                        </div>
                      </td>
                      <td className="py-3 text-center text-slate-600 dark:text-slate-300">
                        {team.matches_played}
                      </td>
                      <td className="py-3 text-center text-green-600 dark:text-green-400">
                        {team.wins}
                      </td>
                      <td className="py-3 text-center text-yellow-600 dark:text-yellow-400">
                        {team.draws}
                      </td>
                      <td className="py-3 text-center text-red-600 dark:text-red-400">
                        {team.losses}
                      </td>
                      <td className="py-3 text-center text-slate-600 dark:text-slate-300">
                        {team.goals_for}
                      </td>
                      <td className="py-3 text-center text-slate-600 dark:text-slate-300">
                        {team.goals_against}
                      </td>
                      <td className={`py-3 text-center font-medium ${
                        team.goal_difference > 0 
                          ? 'text-green-600 dark:text-green-400' 
                          : team.goal_difference < 0 
                            ? 'text-red-600 dark:text-red-400'
                            : 'text-slate-600 dark:text-slate-300'
                      }`}>
                        {team.goal_difference > 0 ? '+' : ''}{team.goal_difference}
                      </td>
                      <td className="py-3 text-center text-slate-900 dark:text-white font-bold">
                        {team.points}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </TooltipProvider>
          </div>
        )}
      </div>
    </div>
  );
} 