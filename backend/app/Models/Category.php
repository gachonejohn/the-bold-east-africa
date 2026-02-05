<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'article_count',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'article_count' => 'integer',
        ];
    }
}
