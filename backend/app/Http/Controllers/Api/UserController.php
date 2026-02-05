<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserInvitation;
use App\Models\Article;
use App\Mail\NewUserWelcome;
use App\Mail\InvitationMail;
use App\Mail\PasswordResetOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Authenticate user and create session
     */
    public function login(Request $request)
    {
        try {
            Log::info("Login attempt for: " . $request->input('email'));

            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $email = strtolower(trim($request->input('email')));

            // Manual authentication check for better debugging
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

            if (!$user) {
                Log::warning("Login failed: User not found for email: {$email}");
                return response()->json(['message' => "User not found with email: {$email}", 'status' => 401], 401);
            }

            if (!Hash::check($request->input('password'), $user->password)) {
                // Debug: Check if password is stored as plain text (common dev issue)
                if ($request->input('password') === $user->password) {
                    Log::info("Dev Fix: Hashing plain text password for {$email}");
                    $user->password = Hash::make($request->input('password'));
                    $user->save();
                } else {
                    Log::warning("Login failed: Password mismatch for email: {$email}");
                    return response()->json(['message' => 'Invalid password provided.', 'status' => 401], 401);
                }
            }

            // Check status if column exists
            if (isset($user->status) && strtolower($user->status) !== 'active') {
                Log::warning("Login failed: User status is {$user->status} for email: {$email}");
                return response()->json(['message' => "Account is {$user->status}.", 'status' => 401], 401);
            }

            // Login successful - using token-based auth (no session needed)
            Log::info("User logged in: {$user->email}");

            $user->last_login_at = now();
            $user->last_active = now();
            $user->last_login_ip = $request->ip();
            $user->login_count = ($user->login_count ?? 0) + 1;
            $user->save();

            $token = $user->createToken('auth_token')->plainTextToken;

            $user->makeHidden(['password', 'remember_token', 'email_verified_at', 'created_at', 'updated_at', 'two_factor_secret', 'two_factor_recovery_codes']);

            return response()->json([
                'data' => $user,
                'token' => $token,
                'message' => 'Login successful',
                'status' => 200
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors(), 'status' => 422], 422);
        } catch (\Exception $e) {
            Log::error("Login error: " . $e->getMessage());
            return response()->json(['message' => 'Login failed: ' . $e->getMessage(), 'status' => 500], 500);
        }
    }

    /**
     * Log the user out
     */
    public function logout(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        if ($user) {
            $user->last_active = now();
            $user->save();
            // Revoke current token for token-based auth
            $user->currentAccessToken()?->delete();
        }

        return response()->json(['message' => 'Logged out successfully', 'status' => 200]);
    }

    /**
     * Get all users with statistics
     */
    public function index(Request $request)
    {
        try {
            $query = User::query();

            // Search filter
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%");
                });
            }

            // Role filter
            if ($request->has('role') && $request->role) {
                $query->where('role', $request->role);
            }

            // Status filter
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            $users = $query->orderBy('created_at', 'desc')->get();

            // Get article statistics in a SINGLE query (avoid N+1)
            $articleStats = Article::selectRaw('
                author,
                COUNT(*) as article_count,
                SUM(CASE WHEN status = "Published" THEN 1 ELSE 0 END) as published_count,
                SUM(CASE WHEN status = "Draft" THEN 1 ELSE 0 END) as draft_count,
                COALESCE(SUM(views), 0) as total_views,
                COALESCE(AVG(seo_score), 0) as avg_seo_score
            ')
            ->groupBy('author')
            ->get()
            ->keyBy('author');

            // Map stats to users
            $users = $users->map(function ($user) use ($articleStats) {
                $stats = $articleStats->get($user->name);
                $user->article_count = $stats ? (int)$stats->article_count : 0;
                $user->published_count = $stats ? (int)$stats->published_count : 0;
                $user->draft_count = $stats ? (int)$stats->draft_count : 0;
                $user->total_views = $stats ? (int)$stats->total_views : 0;
                $user->avg_seo_score = $stats ? round((float)$stats->avg_seo_score) : 0;
                $user->makeHidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes']);
                return $user;
            });

            return response()->json([
                'data' => $users,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch users: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Get user by ID with detailed stats
     */
    public function show(string $id)
    {
        try {
            $user = User::findOrFail($id);

            // Get article statistics
            $articles = Article::where('author', $user->name)->get();
            $user->articles = $articles;
            $user->article_count = $articles->count();
            $user->published_count = $articles->where('status', 'Published')->count();
            $user->draft_count = $articles->where('status', 'Draft')->count();
            $user->total_views = $articles->sum('views');
            $user->total_clicks = $articles->sum('clicks');
            $user->avg_seo_score = $articles->count() > 0
                ? round($articles->avg('seo_score'))
                : 0;

            // Get inviter info
            if ($user->invited_by) {
                $user->inviter = User::find($user->invited_by);
            }

            $user->makeHidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes']);

            return response()->json([
                'data' => $user,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'User not found',
                'status' => 404
            ], 404);
        }
    }

    /**
     * Create user directly (without invitation)
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'nullable|string|min:8',
                'role' => 'nullable|string|in:Admin,Editor,Contributor,Viewer',
                'status' => 'nullable|string|in:Active,Inactive,Suspended',
                'department' => 'nullable|string|max:100',
                'phone' => 'nullable|string|max:20',
                'bio' => 'nullable|string|max:1000',
            ]);

            $temporaryPassword = $validated['password'] ?? str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $validated['password'] = Hash::make($temporaryPassword);
            $validated['role'] = $validated['role'] ?? 'Contributor';
            $validated['status'] = $validated['status'] ?? 'Active';
            $validated['invited_via'] = 'direct';

            $user = User::create($validated);

            // Send welcome email to new user
            try {
                Mail::to($user->email)->send(new NewUserWelcome($user, $temporaryPassword));
                Log::info("Welcome email sent to {$user->email}");
            } catch (\Exception $e) {
                Log::error("Failed to send welcome email to {$user->email}: " . $e->getMessage());
                // Don't fail the request if email fails
            }

            $user->makeHidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes']);

            return response()->json([
                'data' => $user,
                'message' => 'User created successfully. Welcome email sent.',
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
                'message' => 'Failed to create user: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Invite user with OTP
     */
    public function invite(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email|unique:user_invitations,email',
                'role' => 'nullable|string|in:Admin,Editor,Contributor,Viewer',
                'department' => 'nullable|string|max:100',
                'phone' => 'nullable|string|max:20',
                'bio' => 'nullable|string|max:1000',
                'image' => 'nullable|string',
            ]);

            // Generate OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpHash = Hash::make($otp);
            $expiresAt = now()->addHours(24);

            // Get inviter (first admin user for demo)
            $inviter = User::where('role', 'Admin')->first() ?? User::first();

            // Create invitation
            $invitation = UserInvitation::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'] ?? 'Contributor',
                'department' => $validated['department'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'image' => $validated['image'] ?? null,
                'otp_code' => $otp,
                'otp_hash' => $otpHash,
                'otp_expires_at' => $expiresAt,
                'invited_by' => $inviter?->id,
                'status' => 'pending',
            ]);

            // In production, send email here
            // For demo, we'll log and return the OTP
            Log::info("User invitation sent to {$validated['email']} with OTP: {$otp}");

            // Simulate email sending
            $emailSent = $this->sendInvitationEmail($invitation, $otp);

            return response()->json([
                'data' => [
                    'invitation' => $invitation,
                    'otp' => $otp, // Only for demo - remove in production
                    'expires_at' => $expiresAt->toISOString(),
                    'email_sent' => $emailSent,
                ],
                'message' => 'Invitation sent successfully. User will receive an email with temporary login credentials.',
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
                'message' => 'Failed to send invitation: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Upload user image during invitation
     */
    public function uploadInviteImage(Request $request)
    {
        try {
            $validated = $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5048',
            ]);

            $file = $request->file('image');
            $filename = 'user_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('users', $filename, 'public');

            /** @var \Illuminate\Contracts\Filesystem\Cloud $disk */
            $disk = Storage::disk('public');

            return response()->json([
                'data' => [
                    'path' => $path,
                    'url' => $disk->url($path),
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

    /**
     * Accept invitation with OTP
     */
    public function acceptInvitation(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'otp' => 'required|string|min:6|max:10',
                'password' => 'required|string|min:8',
                'password_confirmation' => 'required|string|same:password',
            ]);

            // Find invitation
            $invitation = UserInvitation::where('email', $validated['email'])
                ->where('status', 'pending')
                ->first();

            if (!$invitation) {
                return response()->json([
                    'message' => 'Invalid or expired invitation',
                    'status' => 400
                ], 400);
            }

            // Check if expired
            if ($invitation->isExpired()) {
                $invitation->markAsExpired();
                return response()->json([
                    'message' => 'Invitation has expired. Please request a new invitation.',
                    'status' => 400
                ], 400);
            }

            // Verify OTP
            if (!Hash::check($validated['otp'], $invitation->otp_hash)) {
                return response()->json([
                    'message' => 'Invalid OTP code',
                    'status' => 400
                ], 400);
            }

            // Create user
            $user = User::create([
                'name' => $invitation->name,
                'email' => $invitation->email,
                'password' => Hash::make($validated['password']),
                'role' => $invitation->role,
                'status' => 'Active',
                'department' => $invitation->department,
                'phone' => $invitation->phone,
                'bio' => $invitation->bio,
                'image' => $invitation->image,
                'invited_via' => 'invitation',
                'invited_by' => $invitation->invited_by,
                'invitation_accepted_at' => now(),
            ]);

            // Mark invitation as accepted
            $invitation->markAsAccepted();

            // Send welcome email to newly registered user
            try {
                Mail::to($user->email)->send(new NewUserWelcome($user));
                Log::info("Welcome email sent to {$user->email} after accepting invitation");
            } catch (\Exception $e) {
                Log::error("Failed to send welcome email to {$user->email}: " . $e->getMessage());
                // Don't fail the request if email fails
            }

            $user->makeHidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes']);

            return response()->json([
                'data' => $user,
                'message' => 'Account created successfully. You can now login.',
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
                'message' => 'Failed to accept invitation: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Resend invitation OTP
     */
    public function resendInvitation(string $id)
    {
        try {
            $invitation = UserInvitation::findOrFail($id);

            // Regenerate OTP
            $newOtp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $invitation->update([
                'otp_code' => $newOtp,
                'otp_hash' => Hash::make($newOtp),
                'otp_expires_at' => now()->addHours(24),
                'status' => 'pending'
            ]);

            // Send email
            $emailSent = $this->sendInvitationEmail($invitation, $newOtp);

            Log::info("Invitation resent to {$invitation->email} with new OTP: {$newOtp}");

            return response()->json([
                'data' => [
                    'otp' => $newOtp, // Only for demo
                    'expires_at' => $invitation->otp_expires_at->toISOString(),
                    'email_sent' => $emailSent,
                ],
                'message' => 'Invitation resent successfully',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to resend invitation: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Get all invitations
     */
    public function getInvitations(Request $request)
    {
        try {
            $query = UserInvitation::query();

            // Filter by status
            if ($request->has('status') && $request->status) {
                if ($request->status === 'expired') {
                    $query->expired();
                } else {
                    $query->where('status', $request->status);
                }
            }

            $invitations = $query->orderBy('created_at', 'desc')->get();

            // Mark expired invitations
            $invitations = $invitations->map(function ($inv) {
                if ($inv->status === 'pending' && $inv->otp_expires_at->isPast()) {
                    $inv->status = 'expired';
                }
                return $inv;
            });

            return response()->json([
                'data' => $invitations,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch invitations: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Cancel invitation
     */
    public function cancelInvitation(string $id)
    {
        try {
            $invitation = UserInvitation::findOrFail($id);
            $invitation->delete();

            return response()->json([
                'message' => 'Invitation cancelled successfully',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel invitation: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Update user
     */
    public function update(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'password' => 'nullable|string|min:8',
                'role' => 'nullable|string|in:Admin,Editor,Contributor,Viewer',
                'status' => 'nullable|string|in:Active,Inactive,Suspended',
                'department' => 'nullable|string|max:100',
                'phone' => 'nullable|string|max:20',
                'bio' => 'nullable|string|max:1000',
                'image' => 'nullable|string',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            $user->makeHidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes']);

            return response()->json([
                'data' => $user,
                'message' => 'User updated successfully',
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
                'message' => 'Failed to update user: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Upload user profile image
     */
    public function uploadImage(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);

            // Delete old image if exists
            if ($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }

            // Store new image
            $file = $request->file('image');
            $filename = 'user_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('users', $filename, 'public');

            $user->update(['image' => $path]);

            /** @var \Illuminate\Contracts\Filesystem\Cloud $disk */
            $disk = Storage::disk('public');

            return response()->json([
                'data' => [
                    'image' => $path,
                    'url' => $disk->url($path),
                ],
                'message' => 'Profile image updated successfully',
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

    /**
     * Delete user
     */
    public function destroy(string $id)
    {
        try {
            $user = User::findOrFail($id);

            // Delete profile image if exists
            if ($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }

            $user->delete();

            return response()->json([
                'message' => 'User deleted successfully',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete user: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Update user status
     */
    public function updateStatus(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'status' => 'required|string|in:Active,Inactive,Suspended',
            ]);

            $user->update(['status' => $validated['status']]);

            return response()->json([
                'data' => $user,
                'message' => 'User status updated successfully',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update status: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Bulk update user status
     */
    public function bulkUpdateStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
                'status' => 'required|string|in:Active,Inactive,Suspended',
            ]);

            User::whereIn('id', $validated['user_ids'])
                ->update(['status' => $validated['status']]);

            return response()->json([
                'message' => 'Users updated successfully',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update users: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Get user statistics
     */
    public function getStatistics()
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::where('status', 'Active')->count(),
                'inactive_users' => User::where('status', 'Inactive')->count(),
                'suspended_users' => User::where('status', 'Suspended')->count(),
                'by_role' => [
                    'admin' => User::where('role', 'Admin')->count(),
                    'editor' => User::where('role', 'Editor')->count(),
                    'contributor' => User::where('role', 'Contributor')->count(),
                    'viewer' => User::where('role', 'Viewer')->count(),
                ],
                'recent_users' => User::orderBy('created_at', 'desc')->take(5)->get(),
                'pending_invitations' => UserInvitation::where('status', 'pending')
                    ->where('otp_expires_at', '>', now())
                    ->count(),
                'active_today' => User::whereDate('last_active', today())->count(),
                'new_this_week' => User::where('created_at', '>=', now()->startOfWeek())->count(),
                'new_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
            ];

            return response()->json([
                'data' => $stats,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch statistics: ' . $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Request password reset OTP
     */
    public function forgotPassword(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email|exists:users,email']);

            $user = User::where('email', $request->email)->first();

            // Generate 6-digit numeric OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $token = Hash::make($otp);

            // Store in password_reset_tokens with 30-minute expiry
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'email' => $user->email,
                    'token' => $token,
                    'created_at' => now(),
                    'expires_at' => now()->addMinutes(30),
                ]
            );

            // Send email with OTP
            try {
                Mail::to($user->email)->send(new PasswordResetOtpMail($user, $otp));
                Log::info("Password reset OTP sent to {$user->email}");
            } catch (\Exception $e) {
                Log::error("Failed to send password reset email to {$user->email}: " . $e->getMessage());
                // Still return success as OTP is stored
            }

            return response()->json([
                'message' => 'OTP sent to your email. It will expire in 30 minutes.',
                'status' => 200
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        } catch (\Exception $e) {
            Log::error("Password reset error: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to send OTP. Please try again.',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Reset password with OTP
     */
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'otp' => 'required|string|size:6',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

            // Check if token exists
            if (!$record) {
                return response()->json([
                    'message' => 'No password reset request found. Please request a new OTP.',
                    'status' => 400
                ], 400);
            }

            // Check if token is expired
            if (isset($record->expires_at) && now()->isAfter($record->expires_at)) {
                DB::table('password_reset_tokens')->where('email', $request->email)->delete();
                return response()->json([
                    'message' => 'OTP has expired. Please request a new password reset.',
                    'status' => 400
                ], 400);
            }

            // Verify OTP
            if (!Hash::check($request->otp, $record->token)) {
                return response()->json([
                    'message' => 'Invalid OTP code. Please check and try again.',
                    'status' => 400
                ], 400);
            }

            // Update password
            $user = User::where('email', $request->email)->first();
            $user->password = Hash::make($request->password);
            $user->save();

            // Delete token after successful reset
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            Log::info("Password reset successful for {$user->email}");

            return response()->json([
                'message' => 'Password reset successfully. You can now login with your new password.',
                'status' => 200
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        } catch (\Exception $e) {
            Log::error("Password reset error: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to reset password. Please try again.',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Simulate sending invitation email
     */
    private function sendInvitationEmail(UserInvitation $invitation, string $otp): bool
    {
        try {
            Mail::to($invitation->email)->send(new InvitationMail($invitation, $otp));
            Log::info("Invitation email sent to {$invitation->email}");

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send invitation email: " . $e->getMessage());
            return false;
        }
    }
}
