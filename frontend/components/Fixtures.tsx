'use client';

import { Fixture } from '@/types';

interface FixturesProps {
  fixtures: Fixture[];
  loading: boolean;
}

export default function Fixtures({ fixtures, loading }: FixturesProps) {
  // Group fixtures by week
  const fixturesByWeek = fixtures.reduce((acc, fixture) => {
    if (!acc[fixture.week]) {
      acc[fixture.week] = [];
    }
    acc[fixture.week].push(fixture);
    return acc;
  }, {} as Record<number, Fixture[]>);

  return (
    <div className="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
          Fixtures
        </h2>
        <span className="text-sm text-slate-500 dark:text-slate-400">
          {fixtures.length} matches
        </span>
      </div>
      <div className="space-y-4 max-h-96 overflow-y-auto">
        {loading ? (
          <div className="text-slate-600 dark:text-slate-300 text-center py-8">
            Loading fixtures...
          </div>
        ) : fixtures.length === 0 ? (
          <div className="text-slate-600 dark:text-slate-300 text-center py-8">
            No fixtures generated yet
          </div>
        ) : (
          Object.keys(fixturesByWeek)
            .sort((a, b) => parseInt(a) - parseInt(b))
            .map(week => (
              <div key={week} className="space-y-2">
                <h3 className="text-sm font-medium text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-600 pb-1">
                  Week {week}
                </h3>
                {fixturesByWeek[parseInt(week)].map((fixture) => (
                  <div key={fixture.id} className="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700 rounded-lg">
                    <div className="flex items-center space-x-3 flex-1">
                      <div className="text-sm">
                        <span className="font-medium text-slate-900 dark:text-white">
                          {fixture.home_team.name}
                        </span>
                        <span className="text-slate-500 dark:text-slate-400 mx-2">vs</span>
                        <span className="font-medium text-slate-900 dark:text-white">
                          {fixture.away_team.name}
                        </span>
                      </div>
                    </div>
                    <div className="text-right">
                      {fixture.is_played ? (
                        <div className="text-sm font-medium text-slate-900 dark:text-white">
                          {fixture.score}
                        </div>
                      ) : (
                        <div className="text-xs text-slate-500 dark:text-slate-400">
                          Not played
                        </div>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            ))
        )}
      </div>
    </div>
  );
} 