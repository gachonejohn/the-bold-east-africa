<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\SettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public API routes
Route::apiResource('articles', ArticleController::class);
Route::apiResource('categories', CategoryController::class);
Route::apiResource('campaigns', CampaignController::class);
Route::apiResource('users', UserController::class);

// User management routes
Route::post('/users/invite', [UserController::class, 'invite']);
Route::post('/users/invite/image', [UserController::class, 'uploadInviteImage']);
Route::post('/users/accept-invitation', [UserController::class, 'acceptInvitation']);
Route::get('/users/invitations/list', [UserController::class, 'getInvitations']);
Route::post('/users/invitations/{id}/resend', [UserController::class, 'resendInvitation']);
Route::delete('/users/invitations/{id}', [UserController::class, 'cancelInvitation']);
Route::post('/users/{id}/image', [UserController::class, 'uploadImage']);
Route::patch('/users/{id}/status', [UserController::class, 'updateStatus']);
Route::post('/users/bulk-status', [UserController::class, 'bulkUpdateStatus']);
Route::get('/users/statistics/overview', [UserController::class, 'getStatistics']);

// Password reset routes
Route::post('/forgot-password', [UserController::class, 'forgotPassword']);
Route::post('/reset-password', [UserController::class, 'resetPassword']);

// Article tracking endpoints
Route::post('/articles/{id}/view', [ArticleController::class, 'trackView']);
Route::post('/articles/{id}/click', [ArticleController::class, 'trackClick']);

// Public ads endpoint (for frontend display)
Route::get('/ads/active', [CampaignController::class, 'getActiveAds']);

// Campaign image upload
Route::post('/campaigns/upload-image', [CampaignController::class, 'uploadImage']);

// Ad tracking endpoints
Route::post('/ads/{id}/impression', [CampaignController::class, 'trackImpression']);
Route::post('/ads/{id}/click', [CampaignController::class, 'trackClick']);

// Analytics routes
Route::get('/analytics/dashboard', [AnalyticsController::class, 'getDashboardMetrics']);
Route::get('/analytics/logs', [AnalyticsController::class, 'getLogs']);
Route::get('/analytics/active-visitors', [AnalyticsController::class, 'getActiveVisitors']);
Route::post('/analytics/track', [AnalyticsController::class, 'trackPageView']);

// Settings routes
Route::get('/settings', [SettingsController::class, 'index']);
Route::get('/settings/group/{group}', [SettingsController::class, 'getByGroup']);
Route::put('/settings', [SettingsController::class, 'update']);
Route::put('/settings/{key}', [SettingsController::class, 'updateSingle']);
Route::post('/settings/password', [SettingsController::class, 'updatePassword']);
Route::get('/settings/export', [SettingsController::class, 'exportData']);
Route::get('/settings/system-stats', [SettingsController::class, 'getSystemStats']);
Route::post('/settings/clear-cache', [SettingsController::class, 'clearCache']);
Route::post('/settings/reset/{group?}', [SettingsController::class, 'resetToDefaults']);

// Profile routes
Route::get('/settings/profile', [SettingsController::class, 'getProfile']);
Route::put('/settings/profile', [SettingsController::class, 'updateProfile']);
Route::post('/settings/profile/image', [SettingsController::class, 'uploadProfileImage']);

// Performance metrics
Route::get('/settings/performance', [SettingsController::class, 'getPerformanceMetrics']);

// Auth routes
Route::post('/login', [UserController::class, 'login']);

// Protected auth routes (require sanctum token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
