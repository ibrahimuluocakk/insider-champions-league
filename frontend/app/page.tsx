'use client';

import { useState, useEffect } from 'react';
import { apiClient } from '@/lib/api';
import { Team, Fixture, LeagueTableEntry, ChampionshipPredictionResponse } from '@/types';

// Import components
import Teams from '@/components/Teams';
import Fixtures from '@/components/Fixtures';
import LeagueTable from '@/components/LeagueTable';
import MatchSimulation from '@/components/MatchSimulation';
import ChampionshipPredictions from '@/components/ChampionshipPredictions';

export default function Home() {
  const [teams, setTeams] = useState<Team[]>([]);
  const [fixtures, setFixtures] = useState<Fixture[]>([]);
  const [leagueTable, setLeagueTable] = useState<LeagueTableEntry[]>([]);
  const [championshipPredictions, setChampionshipPredictions] = useState<ChampionshipPredictionResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [fixturesLoading, setFixturesLoading] = useState(false);
  const [leagueTableLoading, setLeagueTableLoading] = useState(false);
  const [predictionsLoading, setPredictionsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  // Simulation states
  const [fixturesGenerated, setFixturesGenerated] = useState(false);
  const [simulationLoading, setSimulationLoading] = useState(false);
  const [simulationMessage, setSimulationMessage] = useState<string | null>(null);

  useEffect(() => {
    fetchTeams();
    checkAndFetchFixtures();
    fetchLeagueTable();
    fetchChampionshipPredictions();
  }, []);

  const fetchTeams = async () => {
    try {
      setLoading(true);
      const response = await apiClient.getTeams();
      if (response.success) {
        setTeams(response.data);
      } else {
        setError('Failed to fetch teams');
      }
    } catch (error) {
      setError('Failed to connect to API');
      console.error('Error fetching teams:', error);
    } finally {
      setLoading(false);
    }
  };

  const checkAndFetchFixtures = async () => {
    try {
      setFixturesLoading(true);
      const response = await apiClient.getFixtures();
      if (response.success && response.data.length > 0) {
        setFixtures(response.data);
        setFixturesGenerated(true);
      } else {
        setFixtures([]);
        setFixturesGenerated(false);
      }
    } catch {
      console.log('No fixtures found yet');
      setFixtures([]);
      setFixturesGenerated(false);
    } finally {
      setFixturesLoading(false);
    }
  };

  const fetchLeagueTable = async () => {
    try {
      setLeagueTableLoading(true);
      const response = await apiClient.getLeagueTable();
      if (response.success) {
        setLeagueTable(response.data.standings);
      } else {
        setLeagueTable([]);
      }
    } catch {
      console.log('No league table data yet');
      setLeagueTable([]);
    } finally {
      setLeagueTableLoading(false);
    }
  };

  const fetchChampionshipPredictions = async () => {
    try {
      setPredictionsLoading(true);
      const response = await apiClient.getChampionshipPredictions();
      
      // Handle both success and expected failure cases
      if (response.success) {
        setChampionshipPredictions(response.data);
      } else {
        // This handles the case where predictions are not available yet (400 status)
        setChampionshipPredictions(response.data);
      }
    } catch (error) {
      console.log('Championship predictions request failed:', error);
      setChampionshipPredictions({
        is_available: false,
        reason: 'Championship predictions available after week 4'
      });
    } finally {
      setPredictionsLoading(false);
    }
  };

  const handleGenerateFixtures = async () => {
    try {
      setSimulationLoading(true);
      setSimulationMessage(null);
      
      const response = await apiClient.generateFixtures();
      if (response.success) {
        setFixtures(response.data);
        setFixturesGenerated(true);
        setSimulationMessage('Fixtures generated successfully!');
        // Refresh league table and predictions after fixtures change
        await fetchLeagueTable();
        await fetchChampionshipPredictions();
      } else {
        setSimulationMessage('Failed to generate fixtures');
      }
    } catch (error) {
      setSimulationMessage('Error generating fixtures');
      console.error('Error generating fixtures:', error);
    } finally {
      setSimulationLoading(false);
    }
  };

  const handleSimulateNextWeek = async () => {
    try {
      setSimulationLoading(true);
      setSimulationMessage(null);
      
      const response = await apiClient.simulateNextWeek();
      if (response.success) {
        setSimulationMessage(`Week ${response.data.week} simulated successfully!`);
        // Refresh fixtures, league table and predictions to show updated results
        await checkAndFetchFixtures();
        await fetchLeagueTable();
        await fetchChampionshipPredictions();
      } else {
        setSimulationMessage('Failed to simulate next week');
      }
    } catch (error) {
      setSimulationMessage('Error simulating next week');
      console.error('Error simulating next week:', error);
    } finally {
      setSimulationLoading(false);
    }
  };

  const handleSimulateAll = async () => {
    try {
      setSimulationLoading(true);
      setSimulationMessage(null);
      
      const response = await apiClient.simulateAllRemaining();
      if (response.success) {
        setSimulationMessage(`All remaining matches simulated! (${response.data.total_matches_simulated} matches)`);
        // Refresh fixtures, league table and predictions to show updated results
        await checkAndFetchFixtures();
        await fetchLeagueTable();
        await fetchChampionshipPredictions();
      } else {
        setSimulationMessage('Failed to simulate all matches');
      }
    } catch (error) {
      setSimulationMessage('Error simulating all matches');
      console.error('Error simulating all matches:', error);
    } finally {
      setSimulationLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800">
      {/* Header */}
      <header className="bg-white dark:bg-slate-900 shadow-sm border-b border-slate-200 dark:border-slate-700">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            <div className="flex items-center space-x-3">
              <div className="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                <span className="text-white font-bold text-sm">ICL</span>
              </div>
              <h1 className="text-xl font-bold text-slate-900 dark:text-white">
                Insider Champions League
              </h1>
            </div>
            <div className="text-sm text-slate-500 dark:text-slate-400">
              Season 2025
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          
          {/* Teams Component */}
          <Teams 
            teams={teams}
            loading={loading}
            error={error}
          />

          {/* Fixtures Component */}
          <Fixtures 
            fixtures={fixtures}
            loading={fixturesLoading}
          />

          {/* League Table Component */}
          <LeagueTable 
            leagueTable={leagueTable}
            loading={leagueTableLoading}
          />

          {/* Match Simulation Component */}
          <MatchSimulation 
            fixturesGenerated={fixturesGenerated}
            simulationLoading={simulationLoading}
            simulationMessage={simulationMessage}
            onGenerateFixtures={handleGenerateFixtures}
            onSimulateNextWeek={handleSimulateNextWeek}
            onSimulateAll={handleSimulateAll}
          />

        </div>

        {/* Championship Predictions Component - Full Width */}
        <div className="mt-6">
          <ChampionshipPredictions 
            championshipPredictions={championshipPredictions}
            loading={predictionsLoading}
          />
        </div>
      </main>
    </div>
  );
}
