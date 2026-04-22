<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Meal;
use App\Models\Exercise;
use App\Models\WeightLog;
use App\Models\TutorialVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Middleware to check if user is admin
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if ($request->user()->role !== 'admin') {
                return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
            }
            return $next($request);
        });
    }

    // ==================== DASHBOARD ====================
    
    /**
     * Get admin dashboard statistics
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('created_at', '>=', now()->subDays(30))->count(),
            'total_meals' => Meal::count(),
            'total_exercises' => Exercise::count(),
            'total_weight_logs' => WeightLog::count(),
            'total_videos' => TutorialVideo::count(),
            
            // Today's activity
            'today_meals' => Meal::whereDate('created_at', today())->count(),
            'today_exercises' => Exercise::whereDate('created_at', today())->count(),
            'today_registrations' => User::whereDate('created_at', today())->count(),
            
            // Recent activity
            'recent_users' => User::latest()->take(5)->get(['id', 'name', 'email', 'created_at']),
            'recent_meals' => Meal::with('user:id,name')->latest()->take(5)->get(),
        ];

        return response()->json($stats);
    }

    // ==================== USER MANAGEMENT ====================
    
    /**
     * Get all users with pagination and filters
     */
    public function getUsers(Request $request)
    {
        $query = User::query();

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $users = $query->paginate($request->get('per_page', 20));

        return response()->json($users);
    }

    /**
     * Get single user details with stats
     */
    public function getUser($id)
    {
        $user = User::with('profile')->findOrFail($id);
        
        $stats = [
            'total_meals' => $user->meals()->count(),
            'total_exercises' => $user->exercises()->count(),
            'total_weight_logs' => $user->weightLogs()->count(),
            'total_calories_consumed' => $user->meals()->sum('total_calories'),
            'total_calories_burned' => $user->exercises()->sum('calories_burned'),
            'last_activity' => $user->meals()->latest()->first()?->created_at 
                            ?? $user->exercises()->latest()->first()?->created_at,
        ];

        return response()->json([
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    /**
     * Create new user
     */
    public function createUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'nullable|in:user,admin',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->get('role', 'user'),
        ]);

        // Create profile
        $user->profile()->create([
            'current_weight' => $request->get('current_weight', 0),
            'target_weight' => $request->get('target_weight', 0),
            'height' => $request->get('height', 0),
            'age' => $request->get('age', 0),
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load('profile'),
        ], 201);
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:6',
            'role' => 'sometimes|in:user,admin',
        ]);

        $data = $request->only(['name', 'email', 'role']);
        
        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Update profile if provided
        if ($request->has('current_weight') || $request->has('target_weight') || 
            $request->has('height') || $request->has('age')) {
            
            $profileData = $request->only(['current_weight', 'target_weight', 'height', 'age']);
            
            if ($user->profile) {
                $user->profile->update($profileData);
            } else {
                $user->profile()->create($profileData);
            }
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('profile'),
        ]);
    }

    /**
     * Delete user
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'Cannot delete your own account'], 400);
        }

        // Prevent deleting other admins (optional)
        if ($user->role === 'admin') {
            return response()->json(['error' => 'Cannot delete admin users'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Ban/Unban user
     */
    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'Cannot ban yourself'], 400);
        }

        // Toggle banned status (you'll need to add 'banned' column to users table)
        // For now, we'll use a simple approach
        $user->update(['role' => $user->role === 'banned' ? 'user' : 'banned']);

        return response()->json([
            'message' => $user->role === 'banned' ? 'User banned successfully' : 'User unbanned successfully',
            'user' => $user,
        ]);
    }

    // ==================== MEAL/POST MANAGEMENT ====================
    
    /**
     * Get all meals with filters
     */
    public function getMeals(Request $request)
    {
        $query = Meal::with(['user:id,name,email', 'foodLogs']);

        // Search by meal name or user
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('meal_time', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('meal_time', '<=', $request->to_date);
        }

        // Include soft deleted
        if ($request->get('include_deleted', false)) {
            $query->withTrashed();
        }

        $meals = $query->latest('meal_time')->paginate($request->get('per_page', 20));

        return response()->json($meals);
    }

    /**
     * Update meal
     */
    public function updateMeal(Request $request, $id)
    {
        $meal = Meal::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string',
            'notes' => 'nullable|string',
            'meal_time' => 'sometimes|date',
        ]);

        $meal->update($request->only(['name', 'notes', 'meal_time']));

        return response()->json([
            'message' => 'Meal updated successfully',
            'meal' => $meal->load('foodLogs'),
        ]);
    }

    /**
     * Delete meal (admin can force delete)
     */
    public function deleteMeal($id, Request $request)
    {
        $meal = Meal::withTrashed()->findOrFail($id);

        if ($request->get('force', false)) {
            $meal->forceDelete();
            return response()->json(['message' => 'Meal permanently deleted']);
        }

        $meal->delete();
        return response()->json(['message' => 'Meal deleted successfully']);
    }

    // ==================== TUTORIAL VIDEO MANAGEMENT ====================
    
    /**
     * Get all tutorial videos
     */
    public function getVideos(Request $request)
    {
        $query = TutorialVideo::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->has('gender_target')) {
            $query->where('gender_target', $request->gender_target);
        }

        $videos = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json($videos);
    }

    /**
     * Create tutorial video
     */
    public function createVideo(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'required|url',
            'thumbnail_url' => 'nullable|url',
            'gender_target' => 'required|in:boy,girl,both',
            'muscle_group' => 'nullable|string',
        ]);

        $video = TutorialVideo::create($request->all());

        return response()->json([
            'message' => 'Video created successfully',
            'video' => $video,
        ], 201);
    }

    /**
     * Update tutorial video
     */
    public function updateVideo(Request $request, $id)
    {
        $video = TutorialVideo::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'sometimes|url',
            'thumbnail_url' => 'nullable|url',
            'gender_target' => 'sometimes|in:boy,girl,both',
            'muscle_group' => 'nullable|string',
        ]);

        $video->update($request->all());

        return response()->json([
            'message' => 'Video updated successfully',
            'video' => $video,
        ]);
    }

    /**
     * Delete tutorial video
     */
    public function deleteVideo($id)
    {
        $video = TutorialVideo::findOrFail($id);
        $video->delete();

        return response()->json(['message' => 'Video deleted successfully']);
    }

    // ==================== REPORTS ====================
    
    /**
     * Generate comprehensive report
     */
    public function generateReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:users,meals,exercises,overview',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        $fromDate = $request->get('from_date', now()->subDays(30));
        $toDate = $request->get('to_date', now());

        $report = [];

        switch ($request->report_type) {
            case 'users':
                $report = $this->generateUsersReport($fromDate, $toDate);
                break;
            case 'meals':
                $report = $this->generateMealsReport($fromDate, $toDate);
                break;
            case 'exercises':
                $report = $this->generateExercisesReport($fromDate, $toDate);
                break;
            case 'overview':
                $report = $this->generateOverviewReport($fromDate, $toDate);
                break;
        }

        return response()->json($report);
    }

    private function generateUsersReport($fromDate, $toDate)
    {
        return [
            'report_type' => 'users',
            'period' => ['from' => $fromDate, 'to' => $toDate],
            'total_users' => User::count(),
            'new_users' => User::whereBetween('created_at', [$fromDate, $toDate])->count(),
            'active_users' => User::whereHas('meals', function($q) use ($fromDate, $toDate) {
                $q->whereBetween('created_at', [$fromDate, $toDate]);
            })->count(),
            'users_by_role' => User::select('role', DB::raw('count(*) as count'))
                ->groupBy('role')
                ->get(),
            'top_active_users' => User::withCount(['meals' => function($q) use ($fromDate, $toDate) {
                $q->whereBetween('created_at', [$fromDate, $toDate]);
            }])
                ->orderBy('meals_count', 'desc')
                ->take(10)
                ->get(['id', 'name', 'email']),
        ];
    }

    private function generateMealsReport($fromDate, $toDate)
    {
        return [
            'report_type' => 'meals',
            'period' => ['from' => $fromDate, 'to' => $toDate],
            'total_meals' => Meal::whereBetween('meal_time', [$fromDate, $toDate])->count(),
            'total_calories' => Meal::whereBetween('meal_time', [$fromDate, $toDate])->sum('total_calories'),
            'avg_calories_per_meal' => Meal::whereBetween('meal_time', [$fromDate, $toDate])->avg('total_calories'),
            'meals_with_photos' => Meal::whereBetween('meal_time', [$fromDate, $toDate])
                ->whereNotNull('photo_path')
                ->count(),
            'meals_by_day' => Meal::whereBetween('meal_time', [$fromDate, $toDate])
                ->select(DB::raw('DATE(meal_time) as date'), DB::raw('count(*) as count'))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];
    }

    private function generateExercisesReport($fromDate, $toDate)
    {
        return [
            'report_type' => 'exercises',
            'period' => ['from' => $fromDate, 'to' => $toDate],
            'total_exercises' => Exercise::whereBetween('date', [$fromDate, $toDate])->count(),
            'total_calories_burned' => Exercise::whereBetween('date', [$fromDate, $toDate])->sum('calories_burned'),
            'avg_duration' => Exercise::whereBetween('date', [$fromDate, $toDate])->avg('duration'),
            'exercises_by_day' => Exercise::whereBetween('date', [$fromDate, $toDate])
                ->select(DB::raw('DATE(date) as date'), DB::raw('count(*) as count'))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];
    }

    private function generateOverviewReport($fromDate, $toDate)
    {
        return [
            'report_type' => 'overview',
            'period' => ['from' => $fromDate, 'to' => $toDate],
            'users' => [
                'total' => User::count(),
                'new' => User::whereBetween('created_at', [$fromDate, $toDate])->count(),
            ],
            'meals' => [
                'total' => Meal::whereBetween('meal_time', [$fromDate, $toDate])->count(),
                'total_calories' => Meal::whereBetween('meal_time', [$fromDate, $toDate])->sum('total_calories'),
            ],
            'exercises' => [
                'total' => Exercise::whereBetween('date', [$fromDate, $toDate])->count(),
                'total_calories_burned' => Exercise::whereBetween('date', [$fromDate, $toDate])->sum('calories_burned'),
            ],
            'weight_logs' => [
                'total' => WeightLog::whereBetween('date', [$fromDate, $toDate])->count(),
            ],
            'videos' => [
                'total' => TutorialVideo::count(),
            ],
        ];
    }

    /**
     * Export report as CSV
     */
    public function exportReport(Request $request)
    {
        // This would generate a CSV file
        // Implementation depends on your requirements
        return response()->json(['message' => 'Export functionality coming soon']);
    }
}
