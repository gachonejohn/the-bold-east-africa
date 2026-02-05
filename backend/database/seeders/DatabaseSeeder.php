<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed Users
        User::updateOrCreate(
            ['email' => 'info@belfortech.dev'],
            [
                'name' => 'Admin Root',
                'password' => Hash::make('password123'),
                'role' => 'Admin',
                'status' => 'Active',
                'last_active' => now(),
            ]
        );

        // Seed Categories
        $this->call(CategorySeeder::class);

        // Seed Sample Articles
        $articles = [
            [
                'title' => 'Henry Mutai reflects on leaving Kenya School of Law, fitness and what\'s next',
                'excerpt' => 'The outgoing director discusses the legacy of legal education reforms and his personal journey towards marathon fitness.',
                'image' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=1200&h=800',
                'category' => 'CORPORATE',
                'author' => 'James Kariuki',
                'read_time' => '8 min read',
                'is_prime' => true,
                'is_headline' => true,
                'seo_score' => 85,
            ],
            [
                'title' => 'BREAKING: Central Bank signals interest rate hold as inflation dips',
                'excerpt' => 'The monetary policy committee cites stabilizing commodity prices for the decision to maintain the current lending rates.',
                'image' => 'https://images.unsplash.com/photo-1611974717482-48cd9744405d?auto=format&fit=crop&w=800&h=600',
                'category' => 'LATEST NEWS',
                'author' => 'Econ Watch',
                'read_time' => '3 min read',
                'is_prime' => false,
                'seo_score' => 90,
            ],
            [
                'title' => 'Coalition talks intensify as 2027 election roadmap takes shape',
                'excerpt' => 'Major political factions are beginning to align their interests as the electoral commission outlines the next transition phase.',
                'image' => 'https://images.unsplash.com/photo-1529107386315-e1a2ed48a620?auto=format&fit=crop&w=1200&h=800',
                'category' => 'POLITICS',
                'author' => 'Political Editor',
                'read_time' => '10 min read',
                'is_prime' => true,
                'seo_score' => 82,
            ],
            [
                'title' => 'New Health Policy aims to provide universal coverage by 2030',
                'excerpt' => 'The Ministry of Health outlines a strategic roadmap to overhaul public health insurance frameworks.',
                'image' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=800&h=600',
                'category' => 'HEALTH',
                'author' => 'Medical Editor',
                'read_time' => '7 min read',
                'is_prime' => true,
            ],
            [
                'title' => 'Nairobi Tech Hub secures $50M in Series C funding round',
                'excerpt' => 'The capital injection will be used to expand operations across the East African Community.',
                'image' => 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=800&h=600',
                'category' => 'STARTUP & TECH',
                'author' => 'Startup Beat',
                'read_time' => '5 min read',
                'is_prime' => true,
            ],
            [
                'title' => 'Continental Games: Athletics stars prepare for high-altitude showdown',
                'excerpt' => 'Olympic hopefuls gather in Nairobi for the final qualifying round ahead of the summer games.',
                'image' => 'https://images.unsplash.com/photo-1541252260730-0412e3e2108e?auto=format&fit=crop&w=800&h=600',
                'category' => 'SPORTS',
                'author' => 'Sports Desk',
                'read_time' => '5 min read',
                'is_prime' => true,
            ],
            [
                'title' => 'Why East Africa needs to pivot to Green Manufacturing now',
                'excerpt' => 'The transition to sustainable production is no longer a choice but a competitive necessity.',
                'image' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=400&h=400',
                'category' => 'OPINIONS',
                'author' => 'Dr. Sheila Mwangi',
                'read_time' => '5 min read',
                'is_prime' => true,
            ],
            [
                'title' => 'New unicorn emerges in regional agritech space',
                'excerpt' => 'A Nairobi-based startup specializing in direct-to-farm supply chain tech reaches $1B valuation.',
                'image' => 'https://images.unsplash.com/photo-1518131359043-4001796d7443?auto=format&fit=crop&w=800&h=600',
                'category' => 'STARTUP & TECH',
                'author' => 'Tech Reporter',
                'read_time' => '5 min read',
                'is_prime' => true,
                'seo_score' => 92,
            ],
            [
                'title' => 'Regional trade summit opens with focus on cross-border logistics',
                'excerpt' => 'Heads of state gather in Kigali to discuss the removal of non-tariff barriers and harmonized customs procedures.',
                'image' => 'https://images.unsplash.com/photo-1521295121783-8a321d551ad2?auto=format&fit=crop&w=800&h=600',
                'category' => 'LATEST NEWS',
                'author' => 'Global Desk',
                'read_time' => '6 min read',
                'is_prime' => false,
                'seo_score' => 85,
            ],
            [
                'title' => 'Trading suspended on major tech stocks following flash crash',
                'excerpt' => 'Regulatory bodies investigate a sudden 15% drop in leading software firm valuations during early morning trade.',
                'image' => 'https://images.unsplash.com/photo-1590283603385-17ffb3a7f29f?auto=format&fit=crop&w=800&h=600',
                'category' => 'CORPORATE',
                'author' => 'Market Desk',
                'read_time' => '4 min read',
                'is_prime' => true,
                'seo_score' => 88,
            ],
        ];

        foreach ($articles as $article) {
            Article::updateOrCreate(
                ['title' => $article['title']],
                $article
            );
        }

        // Seed Activity Logs
        $logs = [
            ['action' => 'System Login', 'user' => 'Admin Root', 'ip_address' => '192.168.1.1', 'status' => 'Success', 'level' => 'Info'],
            ['action' => 'Categories Seeded', 'user' => 'System', 'ip_address' => 'localhost', 'status' => 'Success', 'level' => 'Info'],
            ['action' => 'Articles Seeded', 'user' => 'System', 'ip_address' => 'localhost', 'status' => 'Success', 'level' => 'Info'],
        ];

        foreach ($logs as $log) {
            ActivityLog::create($log);
        }
    }
}
