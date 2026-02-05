<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->string('group')->default('general'); // general, appearance, notifications, seo, content, security
            $table->timestamps();
        });

        // Insert default settings
        $defaults = [
            // General Settings
            ['key' => 'site_name', 'value' => 'The Bold East Africa', 'type' => 'string', 'group' => 'general'],
            ['key' => 'site_tagline', 'value' => 'Bold Stories. Bold Perspectives.', 'type' => 'string', 'group' => 'general'],
            ['key' => 'site_description', 'value' => 'East Africa\'s leading news and analysis platform', 'type' => 'string', 'group' => 'general'],
            ['key' => 'site_logo', 'value' => '', 'type' => 'string', 'group' => 'general'],
            ['key' => 'site_favicon', 'value' => '', 'type' => 'string', 'group' => 'general'],
            ['key' => 'contact_email', 'value' => 'info@theboldeastafrica.com', 'type' => 'string', 'group' => 'general'],
            ['key' => 'contact_phone', 'value' => '+254 700 000 000', 'type' => 'string', 'group' => 'general'],
            ['key' => 'timezone', 'value' => 'Africa/Nairobi', 'type' => 'string', 'group' => 'general'],
            ['key' => 'date_format', 'value' => 'M d, Y', 'type' => 'string', 'group' => 'general'],
            ['key' => 'time_format', 'value' => 'h:i A', 'type' => 'string', 'group' => 'general'],

            // Appearance Settings
            ['key' => 'theme_mode', 'value' => 'light', 'type' => 'string', 'group' => 'appearance'],
            ['key' => 'primary_color', 'value' => '#001733', 'type' => 'string', 'group' => 'appearance'],
            ['key' => 'accent_color', 'value' => '#e5002b', 'type' => 'string', 'group' => 'appearance'],
            ['key' => 'sidebar_collapsed', 'value' => 'false', 'type' => 'boolean', 'group' => 'appearance'],
            ['key' => 'compact_mode', 'value' => 'false', 'type' => 'boolean', 'group' => 'appearance'],
            ['key' => 'show_animations', 'value' => 'true', 'type' => 'boolean', 'group' => 'appearance'],

            // Notification Settings
            ['key' => 'email_notifications', 'value' => 'true', 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'push_notifications', 'value' => 'false', 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'weekly_report', 'value' => 'true', 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'new_article_alerts', 'value' => 'true', 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'comment_notifications', 'value' => 'true', 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'marketing_emails', 'value' => 'false', 'type' => 'boolean', 'group' => 'notifications'],

            // SEO Settings
            ['key' => 'meta_title', 'value' => 'The Bold East Africa - News & Analysis', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'meta_description', 'value' => 'Stay informed with the latest news, analysis, and insights from East Africa.', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'meta_keywords', 'value' => 'news, east africa, kenya, politics, business, sports', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'og_image', 'value' => '', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'twitter_handle', 'value' => '@theboldea', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'facebook_url', 'value' => '', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'google_analytics_id', 'value' => '', 'type' => 'string', 'group' => 'seo'],

            // Content Settings
            ['key' => 'default_article_status', 'value' => 'Draft', 'type' => 'string', 'group' => 'content'],
            ['key' => 'auto_save_interval', 'value' => '30', 'type' => 'integer', 'group' => 'content'],
            ['key' => 'require_featured_image', 'value' => 'false', 'type' => 'boolean', 'group' => 'content'],
            ['key' => 'enable_comments', 'value' => 'true', 'type' => 'boolean', 'group' => 'content'],
            ['key' => 'moderate_comments', 'value' => 'true', 'type' => 'boolean', 'group' => 'content'],
            ['key' => 'articles_per_page', 'value' => '10', 'type' => 'integer', 'group' => 'content'],
            ['key' => 'excerpt_length', 'value' => '150', 'type' => 'integer', 'group' => 'content'],

            // Security Settings
            ['key' => 'two_factor_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'security'],
            ['key' => 'session_timeout', 'value' => '60', 'type' => 'integer', 'group' => 'security'],
            ['key' => 'max_login_attempts', 'value' => '5', 'type' => 'integer', 'group' => 'security'],
            ['key' => 'password_expiry_days', 'value' => '90', 'type' => 'integer', 'group' => 'security'],
            ['key' => 'require_strong_password', 'value' => 'true', 'type' => 'boolean', 'group' => 'security'],
        ];

        foreach ($defaults as $setting) {
            \DB::table('settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
