'use client';

interface MatchSimulationProps {
  fixturesGenerated: boolean;
  simulationLoading: boolean;
  simulationMessage: string | null;
  onGenerateFixtures: () => void;
  onSimulateNextWeek: () => void;
  onSimulateAll: () => void;
}

export default function MatchSimulation({ 
  fixturesGenerated, 
  simulationLoading, 
  simulationMessage,
  onGenerateFixtures,
  onSimulateNextWeek,
  onSimulateAll
}: MatchSimulationProps) {
  return (
    <div className="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
          Match Simulation
        </h2>
      </div>
      
      {/* Simulation Message */}
      {simulationMessage && (
        <div className={`mb-4 p-3 rounded-lg text-sm ${
          simulationMessage.includes('Error') || simulationMessage.includes('Failed')
            ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
            : 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
        }`}>
          {simulationMessage}
        </div>
      )}

      <div className="space-y-4">
        <button 
          onClick={onGenerateFixtures}
          disabled={simulationLoading}
          className={`w-full font-medium py-2 px-4 rounded-lg transition-colors ${
            simulationLoading
              ? 'bg-slate-400 text-white cursor-not-allowed'
              : fixturesGenerated
              ? 'bg-orange-600 hover:bg-orange-700 text-white'
              : 'bg-blue-600 hover:bg-blue-700 text-white'
          }`}
        >
          {simulationLoading ? 'Generating...' : fixturesGenerated ? 'Re-generate Fixtures' : 'Generate Fixtures'}
        </button>
        
        <button 
          onClick={onSimulateNextWeek}
          disabled={simulationLoading || !fixturesGenerated}
          className="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors disabled:bg-slate-300 disabled:cursor-not-allowed"
        >
          {simulationLoading ? 'Simulating...' : 'Simulate Next Week'}
        </button>
        
        <button 
          onClick={onSimulateAll}
          disabled={simulationLoading || !fixturesGenerated}
          className="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition-colors disabled:bg-slate-300 disabled:cursor-not-allowed"
        >
          {simulationLoading ? 'Simulating...' : 'Simulate All Remaining'}
        </button>
      </div>
    </div>
  );
} 