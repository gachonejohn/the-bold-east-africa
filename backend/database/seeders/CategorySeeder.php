<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Latest News', 'slug' => 'latest', 'color' => '#e5002b'],
            ['name' => 'Politics', 'slug' => 'politics', 'color' => '#001733'],
            ['name' => 'Corporate', 'slug' => 'corporate', 'color' => '#001733'],
            ['name' => 'Health', 'slug' => 'health', 'color' => '#10b981'],
            ['name' => 'Law & Order', 'slug' => 'law-order', 'color' => '#6366f1'],
            ['name' => 'Startup & Tech', 'slug' => 'startup-tech', 'color' => '#3b82f6'],
            ['name' => 'Career', 'slug' => 'career', 'color' => '#f59e0b'],
            ['name' => 'Sports', 'slug' => 'sports', 'color' => '#e5002b'],
            ['name' => 'Opinions', 'slug' => 'opinion', 'color' => '#8b5cf6'],
            ['name' => 'Lifestyle', 'slug' => 'lifestyle', 'color' => '#ec4899'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
