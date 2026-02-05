<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'image',
        'category',
        'categories',
        'author',
        'read_time',
        'is_prime',
        'is_headline',
        'status',
        'meta_tags',
        'meta_description',
        'seo_score',
        'views',
        'clicks',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'is_prime' => 'boolean',
            'is_headline' => 'boolean',
            'meta_tags' => 'array',
            'categories' => 'array',
            'seo_score' => 'integer',
        ];
    }
}
