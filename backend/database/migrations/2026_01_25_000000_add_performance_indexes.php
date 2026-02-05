<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PERFORMANCE OPTIMIZATION: Add indexes to frequently queried columns
     * These indexes significantly improve query performance for:
     * - Article filtering by status, category, author
     * - Campaign filtering by status and date ranges
     * - Page view analytics queries
     * - User filtering by role and status
     */
    public function up(): void
    {
        // Articles table indexes
        Schema::table('articles', function (Blueprint $table) {
            // Index for status filtering (Published/Draft)
            $table->index('status', 'idx_articles_status');
            // Index for category filtering
            $table->index('category', 'idx_articles_category');
            // Index for author filtering (used in user stats)
            $table->index('author', 'idx_articles_author');
            // Index for prime/headline filtering
            $table->index('is_prime', 'idx_articles_is_prime');
            $table->index('is_headline', 'idx_articles_is_headline');
            // Index for date sorting
            $table->index('created_at', 'idx_articles_created_at');
            // Composite index for common queries
            $table->index(['status', 'created_at'], 'idx_articles_status_date');
        });

        // Campaigns table indexes
        Schema::table('campaigns', function (Blueprint $table) {
            // Index for active campaign filtering
            $table->index('status', 'idx_campaigns_status');
            // Index for date range queries
            $table->index('start_date', 'idx_campaigns_start_date');
            $table->index('end_date', 'idx_campaigns_end_date');
            // Index for type filtering
            $table->index('type', 'idx_campaigns_type');
            // Composite index for active ads query
            $table->index(['status', 'start_date', 'end_date'], 'idx_campaigns_active');
        });

        // Page views table indexes (critical for analytics)
        Schema::table('page_views', function (Blueprint $table) {
            // Index for session-based queries
            $table->index('session_id', 'idx_page_views_session');
            // Index for date-based analytics
            $table->index('created_at', 'idx_page_views_created_at');
            // Index for location analytics
            $table->index('country_code', 'idx_page_views_country');
            // Index for device analytics
            $table->index('device_type', 'idx_page_views_device');
            // Composite index for time-based unique visitor counts
            $table->index(['created_at', 'session_id'], 'idx_page_views_date_session');
        });

        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            // Index for role filtering
            $table->index('role', 'idx_users_role');
            // Index for status filtering
            $table->index('status', 'idx_users_status');
            // Index for last active queries
            $table->index('last_active', 'idx_users_last_active');
        });

        // Activity logs table indexes
        Schema::table('activity_logs', function (Blueprint $table) {
            // Index for date-based queries
            $table->index('created_at', 'idx_activity_logs_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex('idx_articles_status');
            $table->dropIndex('idx_articles_category');
            $table->dropIndex('idx_articles_author');
            $table->dropIndex('idx_articles_is_prime');
            $table->dropIndex('idx_articles_is_headline');
            $table->dropIndex('idx_articles_created_at');
            $table->dropIndex('idx_articles_status_date');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex('idx_campaigns_status');
            $table->dropIndex('idx_campaigns_start_date');
            $table->dropIndex('idx_campaigns_end_date');
            $table->dropIndex('idx_campaigns_type');
            $table->dropIndex('idx_campaigns_active');
        });

        Schema::table('page_views', function (Blueprint $table) {
            $table->dropIndex('idx_page_views_session');
            $table->dropIndex('idx_page_views_created_at');
            $table->dropIndex('idx_page_views_country');
            $table->dropIndex('idx_page_views_device');
            $table->dropIndex('idx_page_views_date_session');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_status');
            $table->dropIndex('idx_users_last_active');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('idx_activity_logs_created_at');
        });
    }
};
