<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\User;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Track a page view
     */
    public function trackPageView(Request $request)
    {
        try {
            $validated = $request->validate([
                'session_id' => 'required|string',
                'page_url' => 'nullable|string',
                'page_title' => 'nullable|string',
                'referrer' => 'nullable|string',
                'device_type' => 'nullable|string',
                'browser' => 'nullable|string',
                'os' => 'nullable|string',
                'screen_width' => 'nullable|integer',
            ]);

            // Get IP address
            $ip = $request->ip();
            if ($ip === '127.0.0.1' || $ip === '::1') {
                // For local development, use a sample IP
                $ip = $request->header('X-Forwarded-For') ?? $ip;
            }

            // Detect device type from user agent if not provided
            $userAgent = $request->userAgent();
            $deviceType = $validated['device_type'] ?? $this->detectDeviceType($userAgent);
            $browser = $validated['browser'] ?? $this->detectBrowser($userAgent);
            $os = $validated['os'] ?? $this->detectOS($userAgent);

            // Get location from IP (using ip-api.com free service)
            $location = $this->getLocationFromIP($ip);

            $pageView = PageView::create([
                'session_id' => $validated['session_id'],
                'ip_address' => $ip,
                'country' => $location['country'] ?? null,
                'country_code' => $location['countryCode'] ?? null,
                'region' => $location['regionName'] ?? null,
                'city' => $location['city'] ?? null,
                'latitude' => $location['lat'] ?? null,
                'longitude' => $location['lon'] ?? null,
                'device_type' => $deviceType,
                'browser' => $browser,
                'os' => $os,
                'page_url' => $validated['page_url'] ?? null,
                'page_title' => $validated['page_title'] ?? null,
                'referrer' => $validated['referrer'] ?? null,
            ]);

            return response()->json([
                'message' => 'Page view tracked',
                'id' => $pageView->id,
                'status' => 201
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to track page view: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Get comprehensive dashboard metrics
     */
    public function getDashboardMetrics()
    {
        // OPTIMIZED: Get all article stats in ONE query
        $articleStats = Article::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_prime = 1 THEN 1 ELSE 0 END) as prime_count,
            SUM(CASE WHEN is_headline = 1 THEN 1 ELSE 0 END) as headline_count
        ')->first();

        // OPTIMIZED: Get all basic counts in parallel using single queries
        $totalUsers = User::count();
        $totalCategories = Category::count();
        $activeCampaigns = Campaign::where('status', 'Active')->count();

        // Get articles by category with colors
        $articlesByCategory = $this->getArticlesByCategory();

        // Get recent activity count (last 7 days)
        $recentActivity = ActivityLog::where('created_at', '>=', now()->subDays(7))->count();

        // Get user breakdown by role
        $usersByRole = User::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        // OPTIMIZED: Get all page view stats in ONE query
        $pageViewStats = PageView::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_count,
            COUNT(DISTINCT session_id) as unique_visitors
        ')->first();

        // Get real analytics data (these are already optimized)
        $audienceGrowth = $this->getAudienceGrowth();
        $deviceBreakdown = $this->getDeviceBreakdown();
        $topLocations = $this->getTopLocations();
        $kenyaCounties = $this->getKenyaCounties();
        $liveTraffic = $this->getLiveTraffic();
        $dailyPageViews = $this->getDailyPageViews();
        $monthlyPageViews = $this->getMonthlyPageViews();

        return response()->json([
            'data' => [
                'stats' => [
                    'totalArticles' => (int)$articleStats->total,
                    'totalUsers' => $totalUsers,
                    'totalCategories' => $totalCategories,
                    'activeCampaigns' => $activeCampaigns,
                    'primeArticles' => (int)$articleStats->prime_count,
                    'headlineArticles' => (int)$articleStats->headline_count,
                    'recentActivity' => $recentActivity,
                    'totalPageViews' => (int)$pageViewStats->total,
                    'todayPageViews' => (int)$pageViewStats->today_count,
                    'uniqueVisitors' => (int)$pageViewStats->unique_visitors,
                ],
                'audienceGrowth' => $audienceGrowth,
                'dailyPageViews' => $dailyPageViews,
                'monthlyPageViews' => $monthlyPageViews,
                'deviceBreakdown' => $deviceBreakdown,
                'topLocations' => $topLocations,
                'kenyaCounties' => $kenyaCounties,
                'articlesByCategory' => $articlesByCategory,
                'usersByRole' => $usersByRole,
                'liveTraffic' => $liveTraffic,
            ],
            'status' => 200
        ]);
    }

    /**
     * Get articles grouped by category
     */
    private function getArticlesByCategory()
    {
        $categories = Article::selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('count')
            ->get();

        $colors = ['#e5002b', '#001733', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16', '#f97316'];

        return $categories->map(function ($cat, $index) use ($colors) {
            return [
                'category' => $cat->category,
                'count' => $cat->count,
                'color' => $colors[$index % count($colors)]
            ];
        })->toArray();
    }

    /**
     * Get audience growth data (last 6 months)
     * OPTIMIZED: Single query instead of 6 separate queries
     */
    private function getAudienceGrowth()
    {
        $startDate = now()->subMonths(5)->startOfMonth();

        // Single query to get all data
        $data = PageView::selectRaw('
            DATE_FORMAT(created_at, "%Y-%m") as month_key,
            DATE_FORMAT(created_at, "%b") as month,
            COUNT(DISTINCT session_id) as visitors
        ')
        ->where('created_at', '>=', $startDate)
        ->groupBy('month_key', 'month')
        ->orderBy('month_key')
        ->get()
        ->keyBy('month_key');

        // Fill in missing months with zeros
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $months[] = [
                'month' => $date->format('M'),
                'visitors' => isset($data[$key]) ? (int)$data[$key]->visitors : 0
            ];
        }
        return $months;
    }

    /**
     * Get daily page views (last 7 days)
     * OPTIMIZED: Single query instead of 14 separate queries
     */
    private function getDailyPageViews()
    {
        $startDate = now()->subDays(6)->startOfDay();

        // Single query to get all data
        $data = PageView::selectRaw('
            DATE(created_at) as date_key,
            COUNT(*) as page_views,
            COUNT(DISTINCT session_id) as visitors
        ')
        ->where('created_at', '>=', $startDate)
        ->groupBy('date_key')
        ->get()
        ->keyBy('date_key');

        // Fill in missing days with zeros
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $days[] = [
                'date' => $date->format('D'),
                'fullDate' => $date->format('M d'),
                'pageViews' => isset($data[$key]) ? (int)$data[$key]->page_views : 0,
                'visitors' => isset($data[$key]) ? (int)$data[$key]->visitors : 0
            ];
        }
        return $days;
    }

    /**
     * Get monthly page views (last 12 months)
     * OPTIMIZED: Single query instead of 24 separate queries
     */
    private function getMonthlyPageViews()
    {
        $startDate = now()->subMonths(11)->startOfMonth();

        // Single query to get all data
        $data = PageView::selectRaw('
            DATE_FORMAT(created_at, "%Y-%m") as month_key,
            DATE_FORMAT(created_at, "%b %Y") as month,
            DATE_FORMAT(created_at, "%b") as short_month,
            COUNT(*) as page_views,
            COUNT(DISTINCT session_id) as visitors
        ')
        ->where('created_at', '>=', $startDate)
        ->groupBy('month_key', 'month', 'short_month')
        ->orderBy('month_key')
        ->get()
        ->keyBy('month_key');

        // Fill in missing months with zeros
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $months[] = [
                'month' => $date->format('M Y'),
                'shortMonth' => $date->format('M'),
                'pageViews' => isset($data[$key]) ? (int)$data[$key]->page_views : 0,
                'visitors' => isset($data[$key]) ? (int)$data[$key]->visitors : 0
            ];
        }
        return $months;
    }

    /**
     * Get device breakdown percentages
     */
    private function getDeviceBreakdown()
    {
        $total = PageView::count();
        if ($total === 0) {
            return [
                ['label' => 'Desktop', 'val' => 0, 'color' => '#001733'],
                ['label' => 'Mobile', 'val' => 0, 'color' => '#e5002b'],
                ['label' => 'Tablet', 'val' => 0, 'color' => '#94a3b8']
            ];
        }

        $devices = PageView::selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();

        $colors = [
            'desktop' => '#001733',
            'mobile' => '#e5002b',
            'tablet' => '#94a3b8'
        ];

        $result = [];
        foreach (['desktop', 'mobile', 'tablet'] as $device) {
            $count = $devices[$device] ?? 0;
            $result[] = [
                'label' => ucfirst($device),
                'val' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
                'count' => $count,
                'color' => $colors[$device]
            ];
        }

        return $result;
    }

    /**
     * Get top locations by country
     */
    private function getTopLocations()
    {
        $total = PageView::whereNotNull('country')->count();
        if ($total === 0) {
            return [];
        }

        $locations = PageView::selectRaw('country, country_code, COUNT(*) as count')
            ->whereNotNull('country')
            ->groupBy('country', 'country_code')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return $locations->map(function ($loc) use ($total) {
            return [
                'country' => $loc->country,
                'countryCode' => $loc->country_code,
                'count' => $loc->count,
                'percentage' => round(($loc->count / $total) * 100, 1) . '%'
            ];
        })->toArray();
    }

    /**
     * Get Kenya counties breakdown
     */
    private function getKenyaCounties()
    {
        $total = PageView::where('country_code', 'KE')->count();
        if ($total === 0) {
            return [];
        }

        $counties = PageView::selectRaw('region, COUNT(*) as count')
            ->where('country_code', 'KE')
            ->whereNotNull('region')
            ->groupBy('region')
            ->orderByDesc('count')
            ->limit(47)
            ->get();

        return $counties->map(function ($county) use ($total) {
            return [
                'county' => $county->region,
                'count' => $county->count,
                'percentage' => round(($county->count / $total) * 100, 1) . '%'
            ];
        })->toArray();
    }

    /**
     * Get live traffic data with coordinates
     */
    private function getLiveTraffic()
    {
        // Get recent visitors (last 15 minutes) with location
        $recentViews = PageView::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->selectRaw('country, country_code, latitude, longitude, COUNT(*) as count')
            ->groupBy('country', 'country_code', 'latitude', 'longitude')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        // Convert to map coordinates (approximate percentages for world map)
        return $recentViews->map(function ($view) {
            // Convert lat/lon to approximate map position percentages
            $top = 50 - ($view->latitude / 1.8);
            $left = 50 + ($view->longitude / 3.6);

            return [
                'country' => $view->country,
                'countryCode' => $view->country_code,
                'count' => $view->count,
                'lat' => $view->latitude,
                'lon' => $view->longitude,
                'top' => max(5, min(95, $top)) . '%',
                'left' => max(5, min(95, $left)) . '%'
            ];
        })->toArray();
    }

    /**
     * Detect device type from user agent
     */
    private function detectDeviceType($userAgent)
    {
        $userAgent = strtolower($userAgent ?? '');

        if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $userAgent)) {
            if (preg_match('/tablet|ipad/i', $userAgent)) {
                return 'tablet';
            }
            return 'mobile';
        }

        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Detect browser from user agent
     */
    private function detectBrowser($userAgent)
    {
        $userAgent = $userAgent ?? '';

        if (preg_match('/edg/i', $userAgent)) return 'Edge';
        if (preg_match('/chrome/i', $userAgent)) return 'Chrome';
        if (preg_match('/firefox/i', $userAgent)) return 'Firefox';
        if (preg_match('/safari/i', $userAgent)) return 'Safari';
        if (preg_match('/opera|opr/i', $userAgent)) return 'Opera';
        if (preg_match('/msie|trident/i', $userAgent)) return 'IE';

        return 'Other';
    }

    /**
     * Detect OS from user agent
     */
    private function detectOS($userAgent)
    {
        $userAgent = $userAgent ?? '';

        if (preg_match('/windows/i', $userAgent)) return 'Windows';
        if (preg_match('/macintosh|mac os/i', $userAgent)) return 'macOS';
        if (preg_match('/linux/i', $userAgent)) return 'Linux';
        if (preg_match('/android/i', $userAgent)) return 'Android';
        if (preg_match('/iphone|ipad|ipod/i', $userAgent)) return 'iOS';

        return 'Other';
    }

    /**
     * Get location from IP address using ip-api.com
     */
    private function getLocationFromIP($ip)
    {
        try {
            // Skip for localhost
            if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
                return [
                    'country' => 'Kenya',
                    'countryCode' => 'KE',
                    'regionName' => 'Nairobi',
                    'city' => 'Nairobi',
                    'lat' => -1.2921,
                    'lon' => 36.8219
                ];
            }

            $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,regionName,city,lat,lon");
            if ($response) {
                $data = json_decode($response, true);
                if ($data && $data['status'] === 'success') {
                    return $data;
                }
            }
        } catch (\Exception $e) {
            // Silently fail and return default
        }

        return [];
    }

    /**
     * Get activity logs
     */
    public function getLogs()
    {
        $logs = ActivityLog::orderBy('created_at', 'desc')->get()->map(function ($log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'user' => $log->user,
                'ip' => $log->ip_address,
                'status' => $log->status,
                'timestamp' => $log->created_at->diffForHumans(),
                'level' => $log->level,
            ];
        });

        return response()->json([
            'data' => $logs,
            'status' => 200
        ]);
    }

    /**
     * Get real-time active visitors
     */
    public function getActiveVisitors()
    {
        $activeCount = PageView::where('created_at', '>=', now()->subMinutes(5))
            ->distinct('session_id')
            ->count('session_id');

        $recentLocations = PageView::where('created_at', '>=', now()->subMinutes(5))
            ->whereNotNull('country')
            ->selectRaw('country, COUNT(DISTINCT session_id) as count')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return response()->json([
            'data' => [
                'activeVisitors' => $activeCount,
                'locations' => $recentLocations
            ],
            'status' => 200
        ]);
    }
}
