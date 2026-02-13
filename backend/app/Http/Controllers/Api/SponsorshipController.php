<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sponsorship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SponsorshipController extends Controller
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
        
        // If it's a relative path without /storage/, add it
        if (!str_starts_with($imageUrl, '/storage/') && !str_starts_with($imageUrl, 'storage/')) {
            $imageUrl = '/storage/' . $imageUrl;
        }
        
        // Convert relative path to frontend URL
        return $frontendUrl . $imageUrl;
    }

    /**
     * Normalize sponsorship images in collection or single model
     */
    private function normalizeSponsorshipImages($sponsorships)
    {
        if ($sponsorships instanceof \Illuminate\Support\Collection) {
            return $sponsorships->map(function ($sponsorship) {
                $sponsorship->image = $this->normalizeImageUrl($sponsorship->image);
                return $sponsorship;
            });
        }
        
        $sponsorships->image = $this->normalizeImageUrl($sponsorships->image);
        return $sponsorships;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $sponsorships = Sponsorship::orderBy('created_at', 'desc')->get();
            $sponsorships = $this->normalizeSponsorshipImages($sponsorships);
            
            return response()->json([
                'data' => $sponsorships,
                'message' => 'Sponsorships retrieved successfully',
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error fetching sponsorships: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch sponsorships',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $frontendUrl = config('app.url');
            
            $validated = $request->validate([
                'client_name' => 'required|string|max:255',
                'campaign_name' => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'amount' => 'required|numeric',
                'status' => 'required|string|in:Active,Pending,Completed,Cancelled',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5048',
                'description' => 'nullable|string',
            ]);

            // Handle Image Upload
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = 'sponsorship_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('sponsorships', $filename, 'public');
                $validated['image'] = $frontendUrl . '/storage/' . $path;
            }

            $sponsorship = Sponsorship::create($validated);
            $sponsorship = $this->normalizeSponsorshipImages($sponsorship);

            return response()->json([
                'data' => $sponsorship,
                'message' => 'Sponsorship campaign created successfully',
                'status' => 200
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error creating sponsorship: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create sponsorship: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $sponsorship = Sponsorship::findOrFail($id);
            $sponsorship = $this->normalizeSponsorshipImages($sponsorship);
            
            return response()->json([
                'data' => $sponsorship,
                'message' => 'Sponsorship retrieved successfully',
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sponsorship not found',
                'status' => 404
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $frontendUrl = config('app.url');
            $sponsorship = Sponsorship::findOrFail($id);

            $validated = $request->validate([
                'client_name' => 'sometimes|string|max:255',
                'campaign_name' => 'sometimes|string|max:255',
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date|after_or_equal:start_date',
                'amount' => 'sometimes|numeric',
                'status' => 'sometimes|string|in:Active,Pending,Completed,Cancelled',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5048',
                'description' => 'nullable|string',
            ]);

            if ($request->hasFile('image')) {
                // Delete old image - handle both full URLs and relative paths
                if ($sponsorship->image) {
                    $oldPath = $sponsorship->image;
                    // Remove frontend URL if present
                    $oldPath = str_replace($frontendUrl, '', $oldPath);
                    // Remove /storage/ prefix
                    $oldPath = str_replace('/storage/', '', $oldPath);
                    
                    if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }

                $file = $request->file('image');
                $filename = 'sponsorship_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('sponsorships', $filename, 'public');
                $validated['image'] = $frontendUrl . '/storage/' . $path;
            }

            $sponsorship->update($validated);
            $sponsorship = $this->normalizeSponsorshipImages($sponsorship->fresh());

            return response()->json([
                'data' => $sponsorship,
                'message' => 'Sponsorship updated successfully',
                'status' => 200
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update sponsorship: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $frontendUrl = config('app.url');
            $sponsorship = Sponsorship::findOrFail($id);

            if ($sponsorship->image) {
                $path = $sponsorship->image;
                // Remove frontend URL if present
                $path = str_replace($frontendUrl, '', $path);
                // Remove /storage/ prefix
                $path = str_replace('/storage/', '', $path);
                
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            $sponsorship->delete();

            return response()->json([
                'message' => 'Sponsorship deleted successfully',
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete sponsorship: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }
}