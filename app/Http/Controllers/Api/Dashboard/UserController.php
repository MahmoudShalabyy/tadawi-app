<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UserCreateRequest;
use App\Http\Requests\Dashboard\UserUpdateRequest;
use App\Http\Resources\Dashboard\UserResource;
use App\Models\DoctorProfile;
use App\Models\PatientProfile;
use App\Models\PharmacyProfile;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Traits\ImageHandling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse, ImageHandling;
    /**
     * Display a paginated list of active users
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = User::where('status', 'active')
                ->select('id', 'name', 'email', 'role', 'status', 'travel_mode', 'google_id', 'created_at');

            // Apply search filters
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Apply role filter
            if ($request->has('role') && !empty($request->role)) {
                $query->where('role', $request->role);
            }

            // Get paginated results
            $perPage = $request->get('per_page', 10);
            $users = $query->paginate($perPage);

            // Transform data using UserResource
            $transformedData = $users->through(function ($user) {
                return new UserResource($user);
            });

            return $this->success($transformedData, 'Active users retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve users: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created user
     *
     * @param UserCreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(UserCreateRequest $request)
    {
        try {
            $validatedData = $request->validated();

            // Handle profile picture upload if provided
            if ($request->hasFile('profile_picture_path')) {
                $validatedData['profile_picture_path'] = $this->uploadImage($request->file('profile_picture_path'), 'users');
            }

            // Hash the password
            $validatedData['password'] = Hash::make($validatedData['password']);

            // Use database transaction to ensure data consistency
            $user = DB::transaction(function () use ($validatedData) {
                // Create the user
                $user = User::create($validatedData);

                // Create role-specific profile based on user role
                $this->createUserProfile($user, $validatedData['role']);

                return $user;
            });

            // Load the user with its profile relationship if it exists
            $profileRelationship = $this->getProfileRelationship($validatedData['role']);
            if ($profileRelationship) {
                $user->load($profileRelationship);
            }

            // Transform the response using UserResource
            $userResource = new UserResource($user);

            return $this->success($userResource, 'User created successfully with profile', 201);

        } catch (\Exception $e) {
            return $this->serverError('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified user
     *
     * @param UserUpdateRequest $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        try {
            $validatedData = $request->validated();

            // Handle profile picture upload if provided
            if ($request->hasFile('profile_picture_path')) {
                // Delete old profile picture if exists
                if ($user->profile_picture_path) {
                    $this->deleteImage($user->profile_picture_path, 'users');
                }

                // Upload new profile picture
                $validatedData['profile_picture_path'] = $this->uploadImage($request->file('profile_picture_path'), 'users');
            }

            // Use database transaction to ensure data consistency
            $updatedUser = DB::transaction(function () use ($user, $validatedData) {
                // Update the user
                $user->update($validatedData);

                // Handle role change - update profile if role changed
                if (isset($validatedData['role']) && $validatedData['role'] !== $user->getOriginal('role')) {
                    $this->handleRoleChange($user, $validatedData['role']);
                }

                return $user->fresh();
            });

            // Load the user with its profile relationship if it exists
            $profileRelationship = $this->getProfileRelationship($updatedUser->role);
            if ($profileRelationship) {
                $updatedUser->load($profileRelationship);
            }

            // Transform the response using UserResource
            $userResource = new UserResource($updatedUser);

            return $this->success($userResource, 'User updated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Soft delete the specified user
     *
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $user)
    {
        try {
            // Check if user is already soft deleted
            if ($user->trashed()) {
                return $this->error('User is already deleted', 404);
            }

            // Prevent admin from deleting themselves
            $currentUser = auth()->user();
            if ($currentUser && $currentUser->id === $user->id) {
                return $this->error('You cannot delete your own account', 403);
            }

            // Soft delete the user
            $user->delete();

            return $this->success(null, 'User deleted successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Restore a soft-deleted user
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($id)
    {
        try {
            // Find the user including soft-deleted ones
            $user = User::withTrashed()->find($id);

            if (!$user) {
                return $this->error('User not found', 404);
            }

            // Check if user is actually soft deleted
            if (!$user->trashed()) {
                return $this->error('User is not deleted', 400);
            }

            // Restore the user
            $user->restore();

            // Load the user with its profile relationship if it exists
            $profileRelationship = $this->getProfileRelationship($user->role);
            if ($profileRelationship) {
                $user->load($profileRelationship);
            }

            // Transform the response using UserResource
            $userResource = new UserResource($user);

            return $this->success($userResource, 'User restored successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to restore user: ' . $e->getMessage());
        }
    }

    /**
     * Get user statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        try {
            // Count of new users with status 'active' this month
            $newActiveUsersThisMonth = User::where('status', 'active')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            // Role distribution (count per role where status = 'active')
            $roleDistribution = User::where('status', 'active')
                ->selectRaw('role, COUNT(*) as count')
                ->groupBy('role')
                ->pluck('count', 'role')
                ->toArray();

            // Prepare statistics data
            $stats = [
                'new_active_users_this_month' => $newActiveUsersThisMonth,
                'role_distribution' => $roleDistribution,
                'total_active_users' => array_sum($roleDistribution),
                'month' => now()->format('F Y'),
            ];

            return $this->success($stats, 'User statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve user statistics: ' . $e->getMessage());
        }
    }

    /**
     * Create user profile based on role
     *
     * @param User $user
     * @param string $role
     * @return void
     */
    private function createUserProfile(User $user, string $role): void
    {
        switch ($role) {
            case 'patient':
                PatientProfile::create([
                    'user_id' => $user->id,
                    'date_of_birth' => null,
                    'gender' => null,
                    'national_id' => null,
                    'medical_history_summary' => null,
                    'default_address' => null,
                ]);
                break;

            case 'doctor':
                DoctorProfile::create([
                    'user_id' => $user->id,
                    'medical_license_id' => null,
                    'specialization' => null,
                    'clinic_address' => null,
                ]);
                break;

            case 'pharmacy':
                PharmacyProfile::create([
                    'user_id' => $user->id,
                    'location' => null,
                    'latitude' => null,
                    'longitude' => null,
                    'contact_info' => null,
                    'verified' => false,
                    'rating' => 0.0,
                ]);
                break;

            case 'admin':
                // Admin users don't need specific profiles
                break;

            default:
                throw new \InvalidArgumentException("Invalid role: {$role}");
        }
    }

    /**
     * Handle role change by updating user profiles
     *
     * @param User $user
     * @param string $newRole
     * @return void
     */
    private function handleRoleChange(User $user, string $newRole): void
    {
        $oldRole = $user->getOriginal('role');

        // Delete old profile if it exists
        $this->deleteUserProfile($user, $oldRole);

        // Create new profile for the new role
        $this->createUserProfile($user, $newRole);
    }

    /**
     * Delete user profile based on role
     *
     * @param User $user
     * @param string $role
     * @return void
     */
    private function deleteUserProfile(User $user, string $role): void
    {
        switch ($role) {
            case 'patient':
                $user->patientProfile()?->delete();
                break;
            case 'doctor':
                $user->doctorProfile()?->delete();
                break;
            case 'pharmacy':
                $user->pharmacyProfile()?->delete();
                break;
            case 'admin':
            default:
                // Admin users don't have specific profiles
                break;
        }
    }

    /**
     * Display the specified user
     *
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $user)
    {
        try {
            // Load the user with its profile relationship if it exists
            $profileRelationship = $this->getProfileRelationship($user->role);
            if ($profileRelationship) {
                $user->load($profileRelationship);
            }

            // Transform the response using UserResource
            $userResource = new UserResource($user);

            return $this->success($userResource, 'User retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve user: ' . $e->getMessage());
        }
    }

    /**
     * Get the appropriate profile relationship name based on role
     *
     * @param string $role
     * @return string
     */
    private function getProfileRelationship(string $role): string
    {
        switch ($role) {
            case 'patient':
                return 'patientProfile';
            case 'doctor':
                return 'doctorProfile';
            case 'pharmacy':
                return 'pharmacyProfile';
            case 'admin':
            default:
                return '';
        }
    }
}
