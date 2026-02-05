import React, { useState, useEffect } from 'react';

interface Fixture {
  idEvent: string;
  strHomeTeam: string;
  strAwayTeam: string;
  strHomeTeamBadge: string;
  strAwayTeamBadge: string;
  intHomeScore: string | null;
  intAwayScore: string | null;
  strStatus: string;
  strTime: string;
  strTimestamp: string;
  dateEvent: string;
  strLeague: string;
  strLeagueBadge: string;
  strVenue: string;
}

export const MatchFixturesWidget: React.FC = () => {
  const [fixtures, setFixtures] = useState<Fixture[]>([]);
  const [fixturesLoading, setFixturesLoading] = useState(true);

  useEffect(() => {
    const fetchFixtures = async () => {
      setFixturesLoading(true);
      try {
        const res = await fetch('https://www.thesportsdb.com/api/v1/json/3/eventsnextleague.php?id=4328');
        const data = await res.json();
        setFixtures((data.events || []).slice(0, 6));
      } catch (error) {
        console.error('Failed to fetch fixtures', error);
      } finally {
        setFixturesLoading(false);
      }
    };
    fetchFixtures();
    // Refresh fixtures every 60 seconds
    const interval = setInterval(fetchFixtures, 60000);
    return () => clearInterval(interval);
  }, []);

  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div className="bg-gradient-to-r from-[#001733] to-[#003366] px-5 py-4">
        <h3 className="font-black uppercase tracking-widest text-sm flex items-center gap-2 text-white">
          <svg className="w-5 h-5 text-green-400" viewBox="0 0 24 24" fill="currentColor">
            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" strokeWidth="2"/>
            <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" strokeWidth="1.5"/>
            <line x1="12" y1="2" x2="12" y2="5" stroke="currentColor" strokeWidth="1.5"/>
            <line x1="12" y1="19" x2="12" y2="22" stroke="currentColor" strokeWidth="1.5"/>
            <line x1="2" y1="12" x2="5" y2="12" stroke="currentColor" strokeWidth="1.5"/>
            <line x1="19" y1="12" x2="22" y2="12" stroke="currentColor" strokeWidth="1.5"/>
          </svg>
          Match Fixtures
          <span className="ml-auto flex items-center gap-1.5 text-[10px] font-bold text-green-400 tracking-wider">
            <span className="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
            LIVE
          </span>
        </h3>
      </div>
      <div className="divide-y divide-gray-100">
        {fixturesLoading ? (
          <div className="p-8 text-center">
            <div className="animate-spin w-6 h-6 border-2 border-gray-200 border-t-[#e5002b] rounded-full mx-auto mb-2"></div>
            <span className="text-xs text-gray-400">Loading fixtures...</span>
          </div>
        ) : fixtures.length === 0 ? (
          <div className="p-8 text-center text-sm text-gray-400">No upcoming fixtures</div>
        ) : (
          fixtures.map(fixture => {
            const matchTime = new Date(fixture.strTimestamp);
            const isLive = fixture.strStatus === 'Match In Progress' || fixture.strStatus === '1H' || fixture.strStatus === '2H' || fixture.strStatus === 'HT';
            const isFinished = fixture.strStatus === 'Match Finished' || fixture.strStatus === 'FT';
            return (
              <div key={fixture.idEvent} className="px-4 py-3 hover:bg-gray-50 transition-colors">
                <div className="flex items-center justify-between mb-2">
                  <span className="text-[9px] font-bold uppercase tracking-widest text-gray-400">{fixture.strLeague}</span>
                  {isLive ? (
                    <span className="text-[9px] font-black uppercase tracking-wider text-red-500 flex items-center gap-1">
                      <span className="w-1.5 h-1.5 bg-red-500 rounded-full animate-pulse"></span>
                      LIVE
                    </span>
                  ) : isFinished ? (
                    <span className="text-[9px] font-bold uppercase tracking-wider text-gray-400">FT</span>
                  ) : (
                    <span className="text-[9px] font-bold text-gray-500">
                      {matchTime.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} &middot; {matchTime.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })}
                    </span>
                  )}
                </div>
                <div className="flex items-center gap-3">
                  <div className="flex-1 flex items-center gap-2 min-w-0">
                    <img src={fixture.strHomeTeamBadge} alt="" className="w-6 h-6 object-contain flex-shrink-0" />
                    <span className="text-xs font-bold text-[#001733] truncate">{fixture.strHomeTeam}</span>
                  </div>
                  <div className={`px-2.5 py-1 rounded text-xs font-black min-w-[52px] text-center ${
                    isLive ? 'bg-red-50 text-red-600 border border-red-200' :
                    isFinished ? 'bg-gray-100 text-[#001733]' :
                    'bg-gray-50 text-gray-400'
                  }`}>
                    {fixture.intHomeScore !== null && fixture.intAwayScore !== null
                      ? `${fixture.intHomeScore} - ${fixture.intAwayScore}`
                      : 'vs'}
                  </div>
                  <div className="flex-1 flex items-center gap-2 justify-end min-w-0">
                    <span className="text-xs font-bold text-[#001733] truncate text-right">{fixture.strAwayTeam}</span>
                    <img src={fixture.strAwayTeamBadge} alt="" className="w-6 h-6 object-contain flex-shrink-0" />
                  </div>
                </div>
              </div>
            );
          })
        )}
      </div>
    </div>
  );
};