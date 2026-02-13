<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'article_count',
        'color',
        'order',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'article_count' => 'integer',
            'parent_id' => 'integer',
            'order' => 'integer',
        ];
    }

    /**
     * Parent category relationship
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Subcategories relationship
     */
    public function subcategories(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('order');
    }

    /**
     * Check if category has subcategories
     */
    public function hasSubcategories(): bool
    {
        return $this->subcategories()->exists();
    }

    /**
     * Check if category is a subcategory
     */
    public function isSubcategory(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Scope to get only parent categories
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get only subcategories
     */
    public function scopeSubcategories($query)
    {
        return $query->whereNotNull('parent_id');
    }
}