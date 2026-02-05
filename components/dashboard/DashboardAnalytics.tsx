import React, { useState } from 'react';

interface DashboardAnalyticsProps {
  data: any;
  logs: any[];
}

const DashboardAnalytics: React.FC<DashboardAnalyticsProps> = ({ data, logs }) => {
  const [timeRange, setTimeRange] = useState<'daily' | 'monthly'>('daily');
  const [locationView, setLocationView] = useState<'global' | 'kenya'>('global');
  const [hoveredIndex, setHoveredIndex] = useState<number | null>(null);

  const stats = data?.stats || {};
  const deviceBreakdown = data?.deviceBreakdown || [];
  const topLocations = data?.topLocations || [];
  const kenyaCounties = data?.kenyaCounties || [];
  const articlesByCategory = data?.articlesByCategory || [];
  const dailyPageViews = data?.dailyPageViews || [];
  const monthlyPageViews = data?.monthlyPageViews || [];
  const liveTraffic = data?.liveTraffic || [];

  // Determine which data to show for growth
  const growthData = timeRange === 'daily' ? dailyPageViews : monthlyPageViews;
  const maxViews = Math.max(...growthData.map((d: any) => d.pageViews || 0), 1);

  // Chart dimensions
  const svgHeight = 300;
  const svgWidth = 1000;
  const padding = { top: 20, right: 20, bottom: 40, left: 50 };
  const graphWidth = svgWidth - padding.left - padding.right;
  const graphHeight = svgHeight - padding.top - padding.bottom;

  const getX = (index: number) => {
    if (growthData.length <= 1) return padding.left + graphWidth / 2;
    return padding.left + (index / (growthData.length - 1)) * graphWidth;
  };

  const getY = (value: number) => {
    return svgHeight - padding.bottom - (value / maxViews) * graphHeight;
  };

  const pathD = growthData.map((d: any, i: number) =>
    `${i === 0 ? 'M' : 'L'} ${getX(i)},${getY(d.pageViews || 0)}`
  ).join(' ');

  // Helper functions for System Activity
  const getActivityIcon = (action: string) => {
    const lower = action.toLowerCase();
    if (lower.includes('login')) return <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>;
    if (lower.includes('logout')) return <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>;
    if (lower.includes('delete') || lower.includes('remove')) return <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>;
    if (lower.includes('update') || lower.includes('edit') || lower.includes('modify')) return <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>;
    if (lower.includes('create') || lower.includes('add') || lower.includes('upload')) return <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" /></svg>;
    if (lower.includes('approve') || lower.includes('publish')) return <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>;
    if (lower.includes('settings')) return <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>;
    return <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>;
  };

  const getActivityColor = (action: string, level: string) => {
    if (level === 'error') return 'bg-red-50 text-red-600 border-red-100';
    const lower = action.toLowerCase();
    if (lower.includes('delete') || lower.includes('remove')) return 'bg-red-50 text-red-600 border-red-100';
    if (lower.includes('approve') || lower.includes('publish') || lower.includes('login') || lower.includes('create')) return 'bg-green-50 text-green-600 border-green-100';
    if (lower.includes('update') || lower.includes('settings') || lower.includes('edit')) return 'bg-blue-50 text-blue-600 border-blue-100';
    if (lower.includes('logout')) return 'bg-gray-50 text-gray-600 border-gray-100';
    return 'bg-gray-50 text-gray-600 border-gray-100';
  };

  return (
    <div className="space-y-8 animate-in fade-in duration-500">
      {/* KPI Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {[
          { label: 'Total Page Views', value: stats.totalPageViews || 0, change: '+12%', trend: 'up', color: 'text-[#001733]' },
          { label: 'Unique Visitors', value: stats.uniqueVisitors || 0, change: '+5%', trend: 'up', color: 'text-[#001733]' },
          { label: "Today's Views", value: stats.todayPageViews || 0, change: 'Live', trend: 'neutral', color: 'text-[#e5002b]' },
          { label: 'Total Articles', value: stats.totalArticles || 0, change: `+${stats.recentActivity || 0} this week`, trend: 'up', color: 'text-[#001733]' },
        ].map((metric, i) => (
          <div key={i} className="bg-white p-6 rounded-sm border border-gray-100 shadow-sm">
            <p className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">{metric.label}</p>
            <div className="flex items-end justify-between">
              <h3 className={`text-3xl font-black ${metric.color}`}>{metric.value.toLocaleString()}</h3>
              <span className={`text-xs font-bold ${metric.trend === 'up' ? 'text-green-500' : metric.trend === 'down' ? 'text-red-500' : 'text-blue-500'}`}>
                {metric.change}
              </span>
            </div>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Audience Growth Chart */}
        <div className="lg:col-span-2 bg-white border border-gray-100 rounded-sm shadow-sm p-6">
          <div className="flex justify-between items-center mb-8">
            <h3 className="text-sm font-black uppercase tracking-widest text-[#001733]">Audience Growth</h3>
            <div className="flex bg-gray-100 rounded-sm p-1">
              <button
                onClick={() => setTimeRange('daily')}
                className={`px-3 py-1 text-[10px] font-bold uppercase tracking-widest rounded-sm transition-all ${timeRange === 'daily' ? 'bg-white shadow-sm text-[#001733]' : 'text-gray-400 hover:text-gray-600'}`}
              >
                Daily
              </button>
              <button
                onClick={() => setTimeRange('monthly')}
                className={`px-3 py-1 text-[10px] font-bold uppercase tracking-widest rounded-sm transition-all ${timeRange === 'monthly' ? 'bg-white shadow-sm text-[#001733]' : 'text-gray-400 hover:text-gray-600'}`}
              >
                Monthly
              </button>
            </div>
          </div>

          <div className="relative w-full h-80 select-none">
            {growthData.length > 0 ? (
              <svg viewBox={`0 0 ${svgWidth} ${svgHeight}`} className="w-full h-full overflow-visible">
                {/* Grid Lines */}
                {[0, 0.25, 0.5, 0.75, 1].map((tick) => {
                  const y = svgHeight - padding.bottom - tick * graphHeight;
                  return (
                    <g key={tick}>
                      <line x1={padding.left} y1={y} x2={svgWidth - padding.right} y2={y} stroke="#f3f4f6" strokeWidth="1" />
                      <text x={padding.left - 10} y={y + 4} textAnchor="end" className="text-[10px] fill-gray-400 font-mono">
                        {Math.round(tick * maxViews).toLocaleString()}
                      </text>
                    </g>
                  );
                })}

                {/* Area */}
                <path d={`${pathD} L ${getX(growthData.length - 1)},${svgHeight - padding.bottom} L ${getX(0)},${svgHeight - padding.bottom} Z`} fill="url(#gradient)" opacity="0.1" />

                {/* Line */}
                <path d={pathD} fill="none" stroke="#001733" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />

                {/* Gradient Definition */}
                <defs>
                  <linearGradient id="gradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#001733" />
                    <stop offset="100%" stopColor="#001733" stopOpacity="0" />
                  </linearGradient>
                </defs>

                {/* Data Points & Tooltip Triggers */}
                {growthData.map((d: any, i: number) => {
                  const rectWidth = growthData.length > 1 ? graphWidth / (growthData.length - 1) : graphWidth;
                  return (
                    <g key={i} onMouseEnter={() => setHoveredIndex(i)} onMouseLeave={() => setHoveredIndex(null)}>
                      <rect x={getX(i) - rectWidth / 2} y={padding.top} width={rectWidth} height={graphHeight} fill="transparent" />
                      <circle cx={getX(i)} cy={getY(d.pageViews || 0)} r={hoveredIndex === i ? 6 : 0} fill="#e5002b" stroke="white" strokeWidth="2" className="transition-all duration-200" />
                      {i % Math.ceil(growthData.length / 6) === 0 && (
                        <text x={getX(i)} y={svgHeight - 10} textAnchor="middle" className="text-[10px] fill-gray-400 font-bold uppercase">
                          {timeRange === 'daily' ? d.date : d.shortMonth}
                        </text>
                      )}
                    </g>
                  );
                })}
              </svg>
            ) : (
              <div className="w-full h-full flex items-center justify-center text-gray-400 text-xs italic">No data available</div>
            )}

            {/* Tooltip Overlay */}
            {hoveredIndex !== null && growthData[hoveredIndex] && (
              <div className="absolute bg-[#001733] text-white text-xs rounded py-2 px-3 shadow-xl pointer-events-none z-10 transform -translate-x-1/2 -translate-y-full" style={{ left: `${(getX(hoveredIndex) / svgWidth) * 100}%`, top: `${(getY(growthData[hoveredIndex].pageViews || 0) / svgHeight) * 100}%`, marginTop: '-10px' }}>
                <div className="font-bold mb-1">{timeRange === 'daily' ? growthData[hoveredIndex].date : growthData[hoveredIndex].shortMonth}</div>
                <div className="whitespace-nowrap">Views: <span className="font-mono font-bold text-[#e5002b]">{growthData[hoveredIndex].pageViews.toLocaleString()}</span></div>
                <div className="whitespace-nowrap">Visitors: <span className="font-mono text-gray-300">{growthData[hoveredIndex].visitors.toLocaleString()}</span></div>
                <div className="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 w-2 h-2 bg-[#001733] rotate-45"></div>
              </div>
            )}
          </div>
        </div>

        {/* Device Breakdown */}
        <div className="bg-white border border-gray-100 rounded-sm shadow-sm p-6">
          <h3 className="text-sm font-black uppercase tracking-widest text-[#001733] mb-6">Device Breakdown</h3>
          <div className="space-y-6">
            {deviceBreakdown.map((device: any, i: number) => (
              <div key={i}>
                <div className="flex justify-between items-end mb-2">
                  <span className="text-xs font-bold text-gray-600 flex items-center gap-2">
                    {device.label === 'Desktop' && <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>}
                    {device.label === 'Mobile' && <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>}
                    {device.label === 'Tablet' && <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>}
                    {device.label}
                  </span>
                  <span className="text-xs font-black text-[#001733]">{device.val}%</span>
                </div>
                <div className="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                  <div
                    className="h-full rounded-full transition-all duration-1000"
                    style={{ width: `${device.val}%`, backgroundColor: device.color }}
                  ></div>
                </div>
                <p className="text-[10px] text-gray-400 mt-1 text-right">{device.count.toLocaleString()} sessions</p>
              </div>
            ))}
            {deviceBreakdown.length === 0 && (
               <p className="text-center text-gray-400 text-xs italic py-4">No device data.</p>
            )}
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Live Global Traffic */}
        <div className="bg-[#001733] text-white rounded-sm shadow-lg p-6 relative overflow-hidden">
           <div className="flex justify-between items-center mb-6 relative z-10">
              <h3 className="text-sm font-black uppercase tracking-widest">Live Global Traffic</h3>
              <div className="flex items-center gap-2">
                 <span className="relative flex h-2 w-2">
                   <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                   <span className="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                 </span>
                 <span className="text-[10px] font-bold uppercase tracking-widest text-green-400">Live</span>
              </div>
           </div>

           {/* Simplified World Map Representation */}
           <div className="relative h-64 w-full bg-[#002244] rounded border border-white/10 mb-4">
              {/* Grid lines for map effect */}
              <div className="absolute inset-0" style={{ backgroundImage: 'linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px)', backgroundSize: '20px 20px' }}></div>

              {/* World Map SVG Silhouette (Simplified) */}
              <svg className="absolute inset-0 w-full h-full text-white/20 pointer-events-none" fill="currentColor" viewBox="0 0 1000 500">
                 {/* North America */}
                 <path d="M150,50 L250,50 L300,150 L200,200 L100,150 Z" opacity="0.5" />
                 {/* South America */}
                 <path d="M250,220 L350,220 L300,400 L250,300 Z" opacity="0.5" />
                 {/* Europe & Asia */}
                 <path d="M400,50 L800,50 L900,200 L700,250 L500,200 L450,100 Z" opacity="0.5" />
                 {/* Africa */}
                 <path d="M450,220 L600,220 L550,400 L450,300 Z" opacity="0.5" />
                 {/* Australia */}
                 <path d="M750,300 L850,300 L850,400 L750,400 Z" opacity="0.5" />
              </svg>

              {liveTraffic.map((visit: any, i: number) => (
                 <div
                   key={i}
                   className="absolute w-3 h-3 -ml-1.5 -mt-1.5 group cursor-pointer"
                   style={{ top: visit.top, left: visit.left }}
                 >
                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#e5002b] opacity-75"></span>
                    <span className="relative inline-flex rounded-full h-3 w-3 bg-[#e5002b]"></span>

                    {/* Tooltip */}
                    <div className="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 bg-white text-[#001733] text-[10px] font-bold py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-20">
                       {visit.country} ({visit.count})
                    </div>
                 </div>
              ))}
           </div>

           <div className="grid grid-cols-2 gap-4 text-xs">
              {liveTraffic.slice(0, 4).map((visit: any, i: number) => (
                 <div key={i} className="flex justify-between border-b border-white/10 pb-1">
                    <span className="text-gray-400">{visit.country}</span>
                    <span className="font-bold">{visit.count} active</span>
                 </div>
              ))}
           </div>
        </div>

        {/* Top Locations Table */}
        <div className="bg-white border border-gray-100 rounded-sm shadow-sm p-6">
           <div className="flex justify-between items-center mb-6">
            <h3 className="text-sm font-black uppercase tracking-widest text-[#001733]">Top Locations</h3>
            <div className="flex bg-gray-100 rounded-sm p-1">
              <button
                onClick={() => setLocationView('global')}
                className={`px-3 py-1 text-[10px] font-bold uppercase tracking-widest rounded-sm transition-all ${locationView === 'global' ? 'bg-white shadow-sm text-[#001733]' : 'text-gray-400 hover:text-gray-600'}`}
              >
                Global
              </button>
              <button
                onClick={() => setLocationView('kenya')}
                className={`px-3 py-1 text-[10px] font-bold uppercase tracking-widest rounded-sm transition-all ${locationView === 'kenya' ? 'bg-white shadow-sm text-[#001733]' : 'text-gray-400 hover:text-gray-600'}`}
              >
                Kenya (47)
              </button>
            </div>
          </div>

          <div className="overflow-y-auto max-h-64">
             <table className="w-full text-left">
                <thead className="bg-gray-50 sticky top-0">
                   <tr>
                      <th className="p-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Region</th>
                      <th className="p-3 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">Users</th>
                      <th className="p-3 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">%</th>
                   </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                   {(locationView === 'global' ? topLocations : kenyaCounties).map((loc: any, i: number) => (
                      <tr key={i} className="hover:bg-gray-50 transition-colors">
                         <td className="p-3 text-xs font-bold text-[#001733]">
                            {locationView === 'global' ? (
                               <span className="flex items-center gap-2">
                                  <span className="text-gray-400 font-normal">{i+1}.</span> {loc.country}
                               </span>
                            ) : (
                               <span className="flex items-center gap-2">
                                  <span className="text-gray-400 font-normal">{i+1}.</span> {loc.county}
                               </span>
                            )}
                         </td>
                         <td className="p-3 text-xs font-medium text-gray-600 text-right">{loc.count.toLocaleString()}</td>
                         <td className="p-3 text-xs font-medium text-gray-600 text-right">
                            <div className="flex items-center justify-end gap-2">
                               <span>{loc.percentage}</span>
                               <div className="w-12 bg-gray-100 h-1 rounded-full overflow-hidden">
                                  <div className="bg-[#001733] h-full" style={{ width: loc.percentage }}></div>
                               </div>
                            </div>
                         </td>
                      </tr>
                   ))}
                   {(locationView === 'global' ? topLocations : kenyaCounties).length === 0 && (
                      <tr><td colSpan={3} className="p-4 text-center text-xs text-gray-400 italic">No location data available.</td></tr>
                   )}
                </tbody>
             </table>
          </div>
        </div>
      </div>

      {/* Articles by Category */}
      <div className="bg-white border border-gray-100 rounded-sm shadow-sm p-6">
         <h3 className="text-sm font-black uppercase tracking-widest text-[#001733] mb-6">Content Performance by Category</h3>
         <div className="space-y-4">
            {articlesByCategory.map((cat: any, i: number) => (
               <div key={i}>
                  <div className="flex justify-between text-xs mb-1">
                     <span className="font-bold text-gray-700">{cat.category}</span>
                     <span className="font-bold text-[#001733]">{cat.count} articles</span>
                  </div>
                  <div className="w-full bg-gray-100 h-3 rounded-sm overflow-hidden">
                     <div
                        className="h-full rounded-sm transition-all duration-1000"
                        style={{ width: `${(cat.count / (stats.totalArticles || 1)) * 100}%`, backgroundColor: cat.color || '#001733' }}
                     ></div>
                  </div>
               </div>
            ))}
            {articlesByCategory.length === 0 && (
               <p className="text-center text-gray-400 text-xs italic py-4">No category data available.</p>
            )}
          </div>
        </div>

        {/* System Logs */}
        <div className="bg-white border border-gray-100 rounded-sm shadow-sm flex flex-col h-[400px]">
          <div className="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 className="text-sm font-black uppercase tracking-widest text-[#001733]">System Activity</h3>
            <span className="text-xs font-bold text-gray-400">{logs.length} events</span>
          </div>
          <div className="flex-grow overflow-y-auto p-0">
            <div className="divide-y divide-gray-50">
              {logs.map((log, i) => (
                <div key={i} className="flex items-start gap-4 p-4 hover:bg-gray-50 transition-colors">
                  <div className={`w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 border ${getActivityColor(log.action, log.level)}`}>
                    {getActivityIcon(log.action)}
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-bold text-[#001733] leading-tight mb-0.5">{log.action}</p>
                    <div className="flex items-center gap-2 text-xs text-gray-500">
                      <span className="font-medium text-gray-700">{log.user}</span>
                      <span>•</span>
                      <span>{log.timestamp || log.time}</span>
                      {log.ip && (
                        <>
                          <span>•</span>
                          <span className="font-mono text-gray-400">{log.ip}</span>
                        </>
                      )}
                    </div>
                  </div>
                </div>
              ))}
              {logs.length === 0 && (
                <div className="flex flex-col items-center justify-center h-full py-10 text-gray-400">
                  <svg className="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                  <p className="text-sm italic">No recent activity recorded.</p>
                </div>
              )}
            </div>
          </div>
          <div className="p-3 border-t border-gray-100 bg-gray-50 text-center">
            <button className="text-xs font-bold text-[#001733] hover:text-[#e5002b] uppercase tracking-widest transition-colors">View All Activity Log</button>
          </div>
        </div>
    </div>
  );
};

export default DashboardAnalytics;
