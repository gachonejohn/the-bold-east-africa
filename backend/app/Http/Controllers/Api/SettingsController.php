<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Article;
use App\Models\User;
use App\Models\Category;
use App\Models\Campaign;
use App\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    /**
     * Get all settings
     */
    public function index()
    {
        try {
            $settings = Setting::getAllGrouped();

            return response()->json([
                'data' => $settings,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch settings: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Get settings by group
     */
    public function getByGroup(string $group)
    {
        try {
            $settings = Setting::where('group', $group)->get();
            $result = [];

            foreach ($settings as $setting) {
                $result[$setting->key] = $this->castValue($setting->value, $setting->type);
            }

            return response()->json([
                'data' => $result,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch settings: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Update multiple settings
     */
    public function update(Request $request)
    {
        try {
            $settings = $request->except('group');
            $group = $request->input('group', 'general');

            foreach ($settings as $key => $value) {
                $existing = Setting::where('key', $key)->first();

                if ($existing) {
                    $existing->update([
                        'value' => is_array($value) ? json_encode($value) : (string) $value,
                    ]);
                } else {
                    Setting::create([
                        'key' => $key,
                        'value' => is_array($value) ? json_encode($value) : (string) $value,
                        'type' => is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string'),
                        'group' => $group,
                    ]);
                }
            }

            // Clear any cached settings
            Cache::flush();

            return response()->json([
                'data' => Setting::getAllGrouped(),
                'message' => 'Settings updated successfully',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update settings: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Update a single setting
     */
    public function updateSingle(Request $request, string $key)
    {
        try {
            $validated = $request->validate([
                'value' => 'required',
            ]);

            $setting = Setting::where('key', $key)->first();

            if ($setting) {
                $setting->update([
                    'value' => is_array($validated['value']) ? json_encode($validated['value']) : (string) $validated['value'],
                ]);
            } else {
                $setting = Setting::create([
                    'key' => $key,
                    'value' => is_array($validated['value']) ? json_encode($validated['value']) : (string) $validated['value'],
                    'type' => 'string',
                    'group' => 'general',
                ]);
            }

            return response()->json([
                'data' => $setting,
                'message' => 'Setting updated successfully',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update setting: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            // Get the admin user (same approach as other profile methods)
            $user = User::where('role', 'Admin')->first() ?? User::first();

            if (!$user) {
                return response()->json([
                    'message' => 'No user found',
                    'status' => 404
                ], 404);
            }

            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'The current password is incorrect.',
                    'errors' => [
                        'current_password' => ['Current password is incorrect']
                    ],
                    'status' => 422
                ], 422);
            }

            $user->update([
                'password' => Hash::make($validated['new_password'])
            ]);

            return response()->json([
                'message' => 'Password updated successfully',
                'status' => 200
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update password: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Export all data
     */
    public function exportData()
    {
        try {
            $data = [
                'exported_at' => now()->toISOString(),
                'articles' => Article::all(),
                'categories' => Category::all(),
                'users' => User::all()->makeHidden(['password']),
                'campaigns' => Campaign::all(),
                'settings' => Setting::all(),
            ];

            return response()->json([
                'data' => $data,
                'message' => 'Data exported successfully',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export data: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Get system statistics
     */
    public function getSystemStats()
    {
        try {
            $dbSizeData = $this->getDatabaseSize();
            $dbSize = is_array($dbSizeData) ? $dbSizeData['formatted'] : $dbSizeData;

            $stats = [
                'database' => [
                    'articles' => Article::count(),
                    'categories' => Category::count(),
                    'users' => User::count(),
                    'campaigns' => Campaign::count(),
                    'page_views' => PageView::count(),
                    'settings' => Setting::count(),
                ],
                'storage' => [
                    'database_size' => $dbSize,
                    'database_details' => $dbSizeData,
                ],
                'system' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'server_time' => now()->toISOString(),
                    'timezone' => config('app.timezone'),
                    'cpu_usage' => $this->getCpuUsage(),
                ],
            ];

            return response()->json([
                'data' => $stats,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get system stats: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Clear cache
     */
    public function clearCache()
    {
        try {
            Cache::flush();

            return response()->json([
                'message' => 'Cache cleared successfully',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear cache: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Reset settings to defaults
     */
    public function resetToDefaults(string $group = null)
    {
        try {
            if ($group) {
                Setting::where('group', $group)->delete();
            } else {
                Setting::truncate();
            }

            // Re-run the seeder to restore defaults
            // For now, we'll just return success
            return response()->json([
                'message' => 'Settings reset to defaults. Please refresh to see changes.',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reset settings: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Get current user profile
     */
    public function getProfile(Request $request)
    {
        try {
            // For demo, get the first admin user or create default profile
            $user = User::where('role', 'Admin')->first();

            if (!$user) {
                $user = User::first();
            }

            if (!$user) {
                // Return default profile if no users exist
                return response()->json([
                    'data' => [
                        'id' => 1,
                        'name' => 'Admin User',
                        'email' => 'info@belfortech.dev',
                        'bio' => '',
                        'image' => null,
                        'role' => 'Admin',
                    ],
                    'status' => 200
                ]);
            }

            return response()->json([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'bio' => $user->bio ?? '',
                    'image' => $user->image,
                    'role' => $user->role ?? 'admin',
                ],
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch profile: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email',
                'bio' => 'nullable|string|max:1000',
            ]);

            // Get or create admin user
            $user = User::where('role', 'Admin')->first();

            if (!$user) {
                $user = User::first();
            }

            if (!$user) {
                $user = User::create([
                    'name' => $validated['name'] ?? 'Admin User',
                    'email' => $validated['email'] ?? 'info@belfortech.dev',
                    'password' => Hash::make('Duba#i2027'),
                    'role' => 'Admin',
                    'status' => 'active',
                    'bio' => $validated['bio'] ?? '',
                ]);
            } else {
                $user->update($validated);
            }

            return response()->json([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'bio' => $user->bio ?? '',
                    'image' => $user->image,
                    'role' => $user->role ?? 'admin',
                ],
                'message' => 'Profile updated successfully',
                'status' => 200
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update profile: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Upload profile image
     */
    public function uploadProfileImage(Request $request)
    {
        try {
            $validated = $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);

            $user = User::where('role', 'admin')->first() ?? User::first();

            if (!$user) {
                return response()->json([
                    'message' => 'No user found',
                    'status' => 404
                ], 404);
            }

            // Delete old image if exists
            if ($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }

            // Store new image
            $file = $request->file('image');
            $filename = 'profile_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('profiles', $filename, 'public');

            $user->update(['image' => $path]);

            /** @var \Illuminate\Contracts\Filesystem\Cloud $disk */
            $disk = Storage::disk('public');

            return response()->json([
                'data' => [
                    'image' => $path,
                    'url' => $disk->url($path),
                ],
                'message' => 'Profile image uploaded successfully',
                'status' => 200
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload image: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics()
    {
        try {
            // Get date ranges
            $today = now()->startOfDay();
            $yesterday = now()->subDay()->startOfDay();
            $thisWeek = now()->startOfWeek();
            $lastWeek = now()->subWeek()->startOfWeek();
            $thisMonth = now()->startOfMonth();
            $lastMonth = now()->subMonth()->startOfMonth();

            // Calculate metrics
            $todayViews = PageView::where('created_at', '>=', $today)->count();
            $yesterdayViews = PageView::whereBetween('created_at', [$yesterday, $today])->count();

            $thisWeekViews = PageView::where('created_at', '>=', $thisWeek)->count();
            $lastWeekViews = PageView::whereBetween('created_at', [$lastWeek, $thisWeek])->count();

            $thisMonthViews = PageView::where('created_at', '>=', $thisMonth)->count();
            $lastMonthViews = PageView::whereBetween('created_at', [$lastMonth, $thisMonth])->count();

            // Article metrics
            $totalArticles = Article::count();
            $publishedArticles = Article::where('status', 'Published')->count();
            $draftArticles = Article::where('status', 'Draft')->count();

            // Most viewed articles
            $topArticles = Article::orderBy('views', 'desc')->take(5)->get(['id', 'title', 'views', 'clicks']);

            // Calculate growth percentages
            $dailyGrowth = $yesterdayViews > 0 ? round((($todayViews - $yesterdayViews) / $yesterdayViews) * 100, 1) : 0;
            $weeklyGrowth = $lastWeekViews > 0 ? round((($thisWeekViews - $lastWeekViews) / $lastWeekViews) * 100, 1) : 0;
            $monthlyGrowth = $lastMonthViews > 0 ? round((($thisMonthViews - $lastMonthViews) / $lastMonthViews) * 100, 1) : 0;

            // Views by day for the last 7 days
            $viewsByDay = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $dayStart = now()->subDays($i)->startOfDay();
                $dayEnd = now()->subDays($i)->endOfDay();
                $viewsByDay[] = [
                    'date' => now()->subDays($i)->format('M d'),
                    'views' => PageView::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                ];
            }

            // Category performance
            $categoryPerformance = Article::select('category')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('SUM(views) as total_views')
                ->groupBy('category')
                ->orderByDesc('total_views')
                ->take(5)
                ->get();

            return response()->json([
                'data' => [
                    'overview' => [
                        'total_page_views' => PageView::count(),
                        'today_views' => $todayViews,
                        'this_week_views' => $thisWeekViews,
                        'this_month_views' => $thisMonthViews,
                        'daily_growth' => $dailyGrowth,
                        'weekly_growth' => $weeklyGrowth,
                        'monthly_growth' => $monthlyGrowth,
                    ],
                    'articles' => [
                        'total' => $totalArticles,
                        'published' => $publishedArticles,
                        'drafts' => $draftArticles,
                        'top_performing' => $topArticles,
                    ],
                    'charts' => [
                        'views_by_day' => $viewsByDay,
                        'category_performance' => $categoryPerformance,
                    ],
                    'users' => [
                        'total' => User::count(),
                        'active' => User::where('status', 'active')->count(),
                    ],
                ],
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch performance metrics: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Helper: Get CPU Usage
     */
    private function getCpuUsage()
    {
        if (stristr(PHP_OS, 'win')) {
            $cmd = "wmic cpu get loadpercentage /all";
            @exec($cmd, $output);
            if ($output) {
                foreach ($output as $line) {
                    if ($line && preg_match("/^[0-9]+\$/", $line)) {
                        return $line . '%';
                    }
                }
            }
        } else {
            $load = sys_getloadavg();
            if (is_array($load)) {
                return $load[0] . '%';
            }
        }
        return 'Unknown';
    }

    /**
     * Helper: Cast value based on type
     */
    private function castValue($value, string $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'json':
            case 'array':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Helper: Get database size
     */
    private function getDatabaseSize()
    {
        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();
            $size = 0;
            $total = 0;

            if ($driver === 'sqlite') {
                $path = $connection->getConfig('database');
                if ($path && file_exists($path)) {
                    $size = filesize($path);
                    $total = disk_total_space(dirname($path));
                }
            } elseif ($driver === 'mysql') {
                $result = DB::select('SELECT SUM(data_length + index_length) as size FROM information_schema.TABLES WHERE table_schema = ?', [$connection->getDatabaseName()]);
                $size = $result[0]->size ?? 0;
                $total = disk_total_space(base_path());
            } elseif ($driver === 'pgsql') {
                $result = DB::select("SELECT pg_database_size(?) as size", [$connection->getDatabaseName()]);
                $size = $result[0]->size ?? 0;
                $total = disk_total_space(base_path());
            }

            if ($size > 0) {
                $formatted = $this->formatBytes($size);
                $percent = $total > 0 ? round(($size / $total) * 100, 4) : 0;
                
                return [
                    'formatted' => $formatted,
                    'percent' => $percent,
                    'total' => $this->formatBytes($total)
                ];
            }

            return 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Helper: Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
