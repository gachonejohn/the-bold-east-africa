<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CampaignController extends Controller
{
    /**
     * Normalize image URL to always return full URL with frontend domain
     */
    private function normalizeImageUrl($imageUrl)
    {
        if (!$imageUrl) {
            return null;
        }

        $frontendUrl = config('app.url');
        
        // If already a full URL
        if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            // Replace API domain with frontend domain if present
            return str_replace(
                ['https://api.theboldeastafrica.com', 'http://api.theboldeastafrica.com'],
                $frontendUrl,
                $imageUrl
            );
        }
        
        // Convert relative path to frontend URL
        return $frontendUrl . $imageUrl;
    }

    /**
     * Normalize campaign images in collection or single model
     */
    private function normalizeCampaignImages($campaigns)
    {
        if ($campaigns instanceof \Illuminate\Support\Collection) {
            return $campaigns->map(function ($campaign) {
                $campaign->image = $this->normalizeImageUrl($campaign->image);
                return $campaign;
            });
        }
        
        $campaigns->image = $this->normalizeImageUrl($campaigns->image);
        return $campaigns;
    }

    public function index()
    {
        $campaigns = Campaign::orderBy('created_at', 'desc')->get();
        $campaigns = $this->normalizeCampaignImages($campaigns);
        
        return response()->json([
            'data' => $campaigns,
            'status' => 200
        ]);
    }

    /**
     * Get active ads for public display (frontend)
     * OPTIMIZED: Uses index-friendly queries and limits random selection
     */
    public function getActiveAds(Request $request)
    {
        $type = $request->query('type');
        $today = now()->toDateString(); // Use date-only for proper comparison with date columns

        $query = Campaign::where('status', 'Active')
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $today);
            });

        // OPTIMIZED: Direct comparison instead of LOWER() for index usage
        if ($type) {
            $query->where('type', $type);
        }

        // OPTIMIZED: Limit results before random, then shuffle in PHP
        $ads = $query->limit(20)->get()->shuffle();
        $ads = $this->normalizeCampaignImages($ads);

        return response()->json([
            'data' => $ads,
            'status' => 200
        ]);
    }

    /**
     * Clean input - convert empty strings to null
     */
    private function cleanInput(array $input): array
    {
        foreach ($input as $key => $value) {
            if ($value === '' || $value === []) {
                $input[$key] = null;
            }
        }
        return $input;
    }

    public function store(Request $request)
    {
        try {
            $frontendUrl = config('app.url');
            
            // Clean all empty strings/arrays to null
            $input = $this->cleanInput($request->all());
            $request->replace($input);

            // All fields nullable except name and type
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'company' => 'nullable|string|max:255',
                'type' => 'required|string',
                'status' => 'nullable|string',
                'price' => 'nullable',
                'invoice' => 'nullable|string',
                'image' => 'nullable',
                'targetUrl' => 'nullable',
                'startDate' => 'nullable',
                'endDate' => 'nullable',
                'impressions' => 'nullable',
                'clicks' => 'nullable',
            ]);

            // Map frontend camelCase to database snake_case
            $data = [
                'name' => $validated['name'],
                'company' => $validated['company'] ?? null,
                'type' => $validated['type'],
                'status' => $validated['status'] ?? 'Scheduled',
                'price' => is_numeric($validated['price'] ?? null) ? $validated['price'] : null,
                'invoice' => $validated['invoice'] ?? null,
                'image' => $validated['image'] ?? null,
                'target_url' => $validated['targetUrl'] ?? null,
                'start_date' => $validated['startDate'] ?? null,
                'end_date' => $validated['endDate'] ?? null,
                'impressions' => $validated['impressions'] ?? '0',
                'clicks' => $validated['clicks'] ?? '0',
            ];

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = 'campaign_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('campaigns', $filename, 'public');
                $data['image'] = $frontendUrl . '/storage/' . $path;
            }

            $campaign = Campaign::create($data);
            $campaign = $this->normalizeCampaignImages($campaign);

            return response()->json([
                'data' => $campaign,
                'message' => 'Campaign created successfully',
                'status' => 201
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create campaign: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function show(string $id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign = $this->normalizeCampaignImages($campaign);
        
        return response()->json([
            'data' => $campaign,
            'status' => 200
        ]);
    }

    public function update(Request $request, string $id)
    {
        try {
            $frontendUrl = config('app.url');
            $campaign = Campaign::findOrFail($id);

            // Clean all empty strings/arrays to null
            $input = $this->cleanInput($request->all());
            $request->replace($input);

            // All fields nullable
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'company' => 'nullable|string|max:255',
                'type' => 'nullable|string',
                'status' => 'nullable|string',
                'price' => 'nullable',
                'invoice' => 'nullable|string',
                'image' => 'nullable',
                'targetUrl' => 'nullable',
                'startDate' => 'nullable',
                'endDate' => 'nullable',
                'impressions' => 'nullable',
                'clicks' => 'nullable',
            ]);

            // Map frontend camelCase to database snake_case
            $data = [];
            if (isset($validated['name'])) $data['name'] = $validated['name'];
            if (array_key_exists('company', $validated)) $data['company'] = $validated['company'];
            if (isset($validated['type'])) $data['type'] = $validated['type'];
            if (array_key_exists('status', $validated)) $data['status'] = $validated['status'];
            if (array_key_exists('price', $validated)) $data['price'] = is_numeric($validated['price']) ? $validated['price'] : null;
            if (array_key_exists('invoice', $validated)) $data['invoice'] = $validated['invoice'];
            if (array_key_exists('image', $validated)) $data['image'] = $validated['image'];
            if (array_key_exists('targetUrl', $validated)) $data['target_url'] = $validated['targetUrl'];
            if (array_key_exists('startDate', $validated)) $data['start_date'] = $validated['startDate'];
            if (array_key_exists('endDate', $validated)) $data['end_date'] = $validated['endDate'];
            if (array_key_exists('impressions', $validated)) $data['impressions'] = $validated['impressions'];
            if (array_key_exists('clicks', $validated)) $data['clicks'] = $validated['clicks'];

            if ($request->hasFile('image')) {
                $oldPath = str_replace('/storage/', '', $campaign->image ?? '');
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }

                $file = $request->file('image');
                $filename = 'campaign_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('campaigns', $filename, 'public');
                $data['image'] = $frontendUrl . '/storage/' . $path;
            }

            $campaign->update($data);
            $campaign = $this->normalizeCampaignImages($campaign->fresh());

            return response()->json([
                'data' => $campaign,
                'message' => 'Campaign updated successfully',
                'status' => 200
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update campaign: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $campaign = Campaign::findOrFail($id);

            $path = str_replace('/storage/', '', $campaign->image ?? '');
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            $campaign->delete();

            return response()->json([
                'message' => 'Campaign deleted successfully',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete campaign: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Track ad impression
     */
    public function trackImpression(string $id)
    {
        try {
            $campaign = Campaign::findOrFail($id);
            $campaign->increment('impressions');

            return response()->json([
                'message' => 'Impression tracked',
                'impressions' => $campaign->impressions,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to track impression',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Track ad click
     */
    public function trackClick(string $id)
    {
        try {
            $campaign = Campaign::findOrFail($id);
            $campaign->increment('clicks');

            return response()->json([
                'message' => 'Click tracked',
                'clicks' => $campaign->clicks,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to track click',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Upload campaign image
     */
    public function uploadImage(Request $request)
    {
        try {
            $frontendUrl = config('app.url');
            
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);

            $file = $request->file('image');
            $filename = 'campaign_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('campaigns', $filename, 'public');

            return response()->json([
                'data' => [
                    'path' => $frontendUrl . '/storage/' . $path,
                    'url' => $frontendUrl . '/storage/' . $path,
                ],
                'message' => 'Image uploaded successfully',
                'status' => 200
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload image: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }
}