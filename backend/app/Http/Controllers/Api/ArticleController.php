<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::orderBy('created_at', 'desc')->get()->map(function ($article) {
            return $this->normalizeArticleImageUrl($article);
        });
        
        return response()->json(['data' => $articles, 'status' => 200]);
    }

    /**
     * Clean input - convert empty strings to null and remove unknown fields
     */
    private function cleanInput(array $input): array
    {
        $allowedFields = [
            'title', 'slug', 'excerpt', 'image', 'category', 'author', 'read_time',
            'is_prime', 'is_headline', 'status', 'meta_tags', 'meta_description',
            'seo_score', 'content', 'categories', 'tags', 'photo_courtesy'
        ];

        $cleaned = [];
        foreach ($input as $key => $value) {
            if (!in_array($key, $allowedFields, true)) continue;

            // tags -> meta_tags
            if ($key === 'tags') {
                $cleaned['meta_tags'] = is_array($value)
                    ? $value
                    : (is_string($value) ? array_values(array_filter(array_map('trim', explode(',', $value)))) : []);
                continue;
            }

            // categories array -> category string mapping
            if ($key === 'categories') {
                $cleaned['categories'] = $value;
                if (is_array($value) && !empty($value)) {
                    $cleaned['category'] = $value[0];
                }
                continue;
            }

            // empty string -> null (except required-ish text fields)
            if ($value === '' && !in_array($key, ['title', 'excerpt', 'category'], true)) {
                $cleaned[$key] = null;
            } else {
                $cleaned[$key] = $value;
            }
        }

        return $cleaned;
    }

    /**
     * Normalize payload to prevent 500s (array-to-string, booleans, etc.)
     */
    private function normalizePayload(array $validated): array
    {
        // Booleans
        if (array_key_exists('is_prime', $validated)) {
            $validated['is_prime'] = filter_var($validated['is_prime'], FILTER_VALIDATE_BOOLEAN);
        }
        if (array_key_exists('is_headline', $validated)) {
            $validated['is_headline'] = filter_var($validated['is_headline'], FILTER_VALIDATE_BOOLEAN);
        }

        // meta_tags and categories are handled by model casts (array <-> JSON)
        // No need to json_encode here - the model will do it automatically

        return $validated;
    }

    /**
     * Normalize article image URL to always return full URL with frontend domain
     */
    private function normalizeArticleImageUrl($article)
    {
        if (!$article->image) {
            return $article;
        }

        $frontendUrl = config('app.url');
        
        // If already a full URL
        if (filter_var($article->image, FILTER_VALIDATE_URL)) {
            // Replace API domain with frontend domain if present
            $article->image = str_replace(
                ['https://api.theboldeastafrica.com', 'http://api.theboldeastafrica.com'],
                $frontendUrl,
                $article->image
            );
        } else {
            // Convert relative path to frontend URL
            $article->image = $frontendUrl . $article->image;
        }
        
        return $article;
    }

    public function store(Request $request)
    {
        try {
            $input = $this->cleanInput($request->all());

            // If multipart upload is used, take the file from the request
            if ($request->hasFile('image')) {
                $input['image'] = $request->file('image');
            }

            $validated = validator($input, [
                'title' => 'required|string|max:500',
                'slug' => 'nullable|string|unique:articles,slug',
                'excerpt' => 'required|string',
                'category' => 'required|string',
                'categories' => 'nullable', // could be array or json string; we normalize later
                'image' => 'nullable',
                'author' => 'nullable|string',
                'read_time' => 'nullable|string',
                'is_prime' => 'nullable',
                'is_headline' => 'nullable',
                'status' => 'nullable|string',
                'meta_tags' => 'nullable',
                'meta_description' => 'nullable|string',
                'seo_score' => 'nullable|integer',
                'content' => 'nullable',
                'photo_courtesy' => 'nullable|string',
            ])->validate();

            // Image (base64/url/file)
            if (array_key_exists('image', $validated) && !empty($validated['image'])) {
                $validated['image'] = $this->handleImageUpload($validated['image']);
            }

            // Slug
            if (empty($validated['slug']) && !empty($validated['title'])) {
                $validated['slug'] = $this->generateSlug($validated['title']);
            }

            $validated = $this->normalizePayload($validated);

            $article = Article::create($validated);

            return response()->json([
                'data' => $this->normalizeArticleImageUrl($article),
                'message' => 'Article created successfully',
                'status' => 201
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to create article',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function show(string $id)
    {
        $article = Article::findOrFail($id);
        return response()->json([
            'data' => $this->normalizeArticleImageUrl($article),
            'status' => 200
        ]);
    }

    public function update(Request $request, string $id)
    {
        try {
            $article = Article::findOrFail($id);

            $input = $this->cleanInput($request->all());

            // If multipart upload is used, take the file from the request
            if ($request->hasFile('image')) {
                $input['image'] = $request->file('image');
            }

            $validated = validator($input, [
                'title' => 'nullable|string|max:500',
                'slug' => 'nullable|string|unique:articles,slug,' . $id,
                'excerpt' => 'nullable|string',
                'category' => 'nullable|string',
                'categories' => 'nullable',
                'image' => 'nullable',
                'author' => 'nullable|string',
                'read_time' => 'nullable|string',
                'is_prime' => 'nullable',
                'is_headline' => 'nullable',
                'status' => 'nullable|string',
                'meta_tags' => 'nullable',
                'meta_description' => 'nullable|string',
                'seo_score' => 'nullable|integer',
                'content' => 'nullable',
                'photo_courtesy' => 'nullable|string',
            ])->validate();

            // Image (base64/url/file)
            if (array_key_exists('image', $validated) && !empty($validated['image'])) {
                $validated['image'] = $this->handleImageUpload($validated['image']);
            }

            // Slug
            if ((!array_key_exists('slug', $validated) || empty($validated['slug']))
                && array_key_exists('title', $validated) && !empty($validated['title'])) {
                $validated['slug'] = $this->generateSlug($validated['title']);
            }

            $validated = $this->normalizePayload($validated);

            $article->update($validated);

            return response()->json([
                'data' => $this->normalizeArticleImageUrl($article->fresh()),
                'message' => 'Article updated successfully',
                'status' => 200
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to update article',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $article = Article::findOrFail($id);
            $article->delete();
            return response()->json(['message' => 'Article deleted successfully', 'status' => 200]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to delete article',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Track article view
     */
    public function trackView(string $id)
    {
        try {
            $article = Article::findOrFail($id);
            $article->increment('views');
            return response()->json([
                'data' => ['views' => $article->views],
                'message' => 'View tracked successfully',
                'status' => 200
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to track view',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Track article click
     */
    public function trackClick(string $id)
    {
        try {
            $article = Article::findOrFail($id);
            $article->increment('clicks');
            return response()->json([
                'data' => ['clicks' => $article->clicks],
                'message' => 'Click tracked successfully',
                'status' => 200
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to track click',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Handle image upload (Base64, URL, or multipart file)
     * Returns full URL with frontend domain for consistency
     */
    private function handleImageUpload($image)
    {
        $frontendUrl = config('app.url');
        
        // Multipart file
        if ($image instanceof UploadedFile) {
            $path = $image->store('articles', 'public');
            return $frontendUrl . '/storage/' . $path;
        }

        // If it's not a string, don't try regex on it
        if (!is_string($image)) {
            throw new \Exception('Invalid image payload');
        }

        // If already a full URL, ensure it uses frontend domain
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return str_replace(
                ['https://api.theboldeastafrica.com', 'http://api.theboldeastafrica.com'],
                $frontendUrl,
                $image
            );
        }

        // Base64
        if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
            $data = substr($image, strpos($image, ',') + 1);
            $ext = strtolower($type[1]);

            if (!in_array($ext, ['jpg', 'jpeg', 'gif', 'png', 'webp'], true)) {
                throw new \Exception('Invalid image type');
            }

            $data = base64_decode($data);
            if ($data === false) {
                throw new \Exception('Base64 decode failed');
            }

            $filename = 'article_' . time() . '_' . Str::random(10) . '.' . $ext;
            Storage::disk('public')->put('articles/' . $filename, $data);

            return $frontendUrl . '/storage/articles/' . $filename;
        }

        // If it's a relative path, convert to frontend URL
        if (str_starts_with($image, '/storage/') || str_starts_with($image, 'storage/')) {
            return $frontendUrl . (str_starts_with($image, '/') ? $image : '/' . $image);
        }

        // Return as-is if none of the above
        return $image;
    }

    private function generateSlug(string $text): string
    {
        return Str::slug($text);
    }
}