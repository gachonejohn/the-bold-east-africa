<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all categories with hierarchical structure
     */
    public function index(Request $request)
    {
        // Option 1: Flat list with parent info
        if ($request->query('flat') === 'true') {
            $categories = Category::with('parent')
                ->orderBy('order')
                ->orderBy('name')
                ->get();
            
            return response()->json([
                'data' => $categories,
                'status' => 200
            ]);
        }

        // Option 2: Hierarchical structure (default)
        $categories = Category::with('subcategories')
            ->parents()
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $categories,
            'status' => 200
        ]);
    }

    /**
     * Get category tree for dropdown
     */
    public function tree()
    {
        $categories = Category::with('subcategories')
            ->parents()
            ->orderBy('order')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'color' => $category->color,
                    'subcategories' => $category->subcategories->map(function ($sub) {
                        return [
                            'id' => $sub->id,
                            'name' => $sub->name,
                            'slug' => $sub->slug,
                            'color' => $sub->color ?? $sub->parent->color,
                        ];
                    })
                ];
            });

        return response()->json([
            'data' => $categories,
            'status' => 200
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:categories,slug',
            'parent_id' => 'nullable|exists:categories,id',
            'article_count' => 'integer',
            'color' => 'nullable|string',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);

        $category = Category::create($validated);

        return response()->json([
            'data' => $category->load('parent', 'subcategories'),
            'message' => 'Category created successfully',
            'status' => 201
        ], 201);
    }

    public function show(string $id)
    {
        $category = Category::with('parent', 'subcategories')->findOrFail($id);
        
        return response()->json([
            'data' => $category,
            'status' => 200
        ]);
    }

    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:categories,slug,' . $id,
            'parent_id' => 'nullable|exists:categories,id',
            'article_count' => 'integer',
            'color' => 'nullable|string',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);

        // Prevent self-parenting or circular references
        if (isset($validated['parent_id']) && $validated['parent_id'] == $id) {
            return response()->json([
                'message' => 'Category cannot be its own parent',
                'status' => 422
            ], 422);
        }

        $category->update($validated);

        return response()->json([
            'data' => $category->load('parent', 'subcategories'),
            'message' => 'Category updated successfully',
            'status' => 200
        ]);
    }

    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        
        // Check if category has subcategories
        if ($category->hasSubcategories()) {
            return response()->json([
                'message' => 'Cannot delete category with subcategories. Delete subcategories first.',
                'status' => 422
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
            'status' => 200
        ]);
    }
}