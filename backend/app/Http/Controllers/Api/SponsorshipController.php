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
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $sponsorships = Sponsorship::orderBy('created_at', 'desc')->get();
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
                $validated['image'] = $path;
            }

            $sponsorship = Sponsorship::create($validated);

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
                // Delete old image
                if ($sponsorship->image && Storage::disk('public')->exists($sponsorship->image)) {
                    Storage::disk('public')->delete($sponsorship->image);
                }

                $file = $request->file('image');
                $filename = 'sponsorship_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('sponsorships', $filename, 'public');
                $validated['image'] = $path;
            }

            $sponsorship->update($validated);

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
            $sponsorship = Sponsorship::findOrFail($id);

            if ($sponsorship->image && Storage::disk('public')->exists($sponsorship->image)) {
                Storage::disk('public')->delete($sponsorship->image);
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
