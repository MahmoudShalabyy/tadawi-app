<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\SiteConfigRequest;
use App\Models\Config;
use App\Models\StockBatch;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Traits\ImageHandling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    use ApiResponse, ImageHandling;

    /**
     * Get current site settings
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $settings = Config::getAllAsArray();

            return $this->success($settings, 'Settings retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve settings: ' . $e->getMessage());
        }
    }

    /**
     * Update site settings
     *
     * @param SiteConfigRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(SiteConfigRequest $request)
    {
        try {
            $validatedData = $request->validated();

            // Use database transaction to ensure data consistency
            DB::transaction(function () use ($validatedData, $request) {
                // Update basic settings
                Config::setValue('site_name', $validatedData['site_name']);
                Config::setValue('email_config', $validatedData['email_config']);
                Config::setValue('map_api_key', $validatedData['map_api_key']);
                Config::setValue('ocr_api_key', $validatedData['ocr_api_key']);
                Config::setValue('site_theme', $validatedData['site_theme']);
                Config::setValue('timezone', $validatedData['timezone']);
                Config::setValue('currency', $validatedData['currency']);
                Config::setValue('public_key', $validatedData['public_key']);
                Config::setValue('private_key', $validatedData['private_key']);
                Config::setValue('ai_link', $validatedData['ai_link']);

                // Handle site logo upload if provided
                if ($request->hasFile('site_logo')) {
                    $logoPath = $this->uploadImage($request->file('site_logo'), 'logos', 'site_logo');
                    Config::setValue('site_logo', $logoPath);
                }
            });

            // Get updated settings
            $updatedSettings = Config::getAllAsArray();

            return $this->success($updatedSettings, 'Settings updated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to update settings: ' . $e->getMessage());
        }
    }

    /**
     * Get permissions management data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPermissions()
    {
        try {
            // Get user roles from the users table
            $roles = User::select('role')
                ->distinct()
                ->whereNotNull('role')
                ->pluck('role')
                ->map(function ($role) {
                    return [
                        'name' => $role,
                        'display_name' => ucfirst($role),
                        'user_count' => User::where('role', $role)->count(),
                    ];
                });

            // Define available permissions based on user roles
            $permissions = [
                ['name' => 'dashboard_access', 'description' => 'Access to dashboard'],
                ['name' => 'user_management', 'description' => 'Manage users'],
                ['name' => 'medicine_management', 'description' => 'Manage medicines'],
                ['name' => 'order_management', 'description' => 'Manage orders'],
                ['name' => 'prescription_management', 'description' => 'Manage prescriptions'],
                ['name' => 'donation_management', 'description' => 'Manage donations'],
                ['name' => 'settings_management', 'description' => 'Manage settings'],
                ['name' => 'reports_access', 'description' => 'Access reports'],
            ];

            return $this->success([
                'roles' => $roles,
                'permissions' => $permissions,
            ], 'Permissions data retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve permissions: ' . $e->getMessage());
        }
    }

    /**
     * Update permissions for a role
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePermissions(Request $request)
    {
        try {
            $request->validate([
                'role' => 'required|string|in:admin,doctor,pharmacy,patient',
                'permissions' => 'required|array',
                'permissions.*' => 'string|in:dashboard_access,user_management,medicine_management,order_management,prescription_management,donation_management,settings_management,reports_access',
            ]);

            // Store role permissions in config table
            $rolePermissions = [
                'admin' => ['dashboard_access', 'user_management', 'medicine_management', 'order_management', 'prescription_management', 'donation_management', 'settings_management', 'reports_access'],
                'doctor' => ['dashboard_access', 'prescription_management', 'reports_access'],
                'pharmacy' => ['dashboard_access', 'medicine_management', 'order_management', 'reports_access'],
                'patient' => ['dashboard_access'],
            ];

            // Update permissions for the role
            $rolePermissions[$request->role] = $request->permissions;

            // Save to config
            Config::setValue('role_permissions', $rolePermissions, 'json', 'Role-based permissions configuration');

            return $this->success([
                'role' => $request->role,
                'permissions' => $request->permissions,
                'message' => 'Role permissions updated successfully',
            ], 'Role permissions updated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to update permissions: ' . $e->getMessage());
        }
    }

    /**
     * Get reports by type
     *
     * @param string $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReports($type)
    {
        try {
            switch ($type) {
                case 'shortages':
                    return $this->getShortagesReport();
                case 'user_stats':
                    return $this->getUserStatsReport();
                default:
                    return $this->error('Invalid report type', 400);
            }

        } catch (\Exception $e) {
            return $this->serverError('Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Get shortages report
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function getShortagesReport()
    {
        try {
            // Get medicines with low stock (sum quantity < 10)
            $shortages = StockBatch::select([
                'medicine_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT pharmacy_id) as affected_pharmacies')
            ])
            ->groupBy('medicine_id')
            ->having('total_quantity', '<', 10)
            ->with(['medicine:id,brand_name,form,dosage_strength,manufacturer'])
            ->get()
            ->map(function ($stock) {
                return [
                    'medicine_id' => $stock->medicine_id,
                    'medicine_name' => $stock->medicine->brand_name ?? 'Unknown',
                    'form' => $stock->medicine->form ?? null,
                    'dosage_strength' => $stock->medicine->dosage_strength ?? null,
                    'manufacturer' => $stock->medicine->manufacturer ?? null,
                    'total_quantity' => $stock->total_quantity,
                    'affected_pharmacies' => $stock->affected_pharmacies,
                    'status' => $stock->total_quantity == 0 ? 'Out of Stock' : 'Low Stock',
                ];
            });

            $report = [
                'type' => 'shortages',
                'total_shortages' => $shortages->count(),
                'out_of_stock' => $shortages->where('status', 'Out of Stock')->count(),
                'low_stock' => $shortages->where('status', 'Low Stock')->count(),
                'data' => $shortages,
                'generated_at' => now()->toISOString(),
            ];

            return $this->success($report, 'Shortages report generated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to generate shortages report: ' . $e->getMessage());
        }
    }

    /**
     * Get user statistics report
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function getUserStatsReport()
    {
        try {
            // Get user count by role where status = 'active'
            $userStats = User::selectRaw('role, COUNT(*) as count')
                ->where('status', 'active')
                ->groupBy('role')
                ->get()
                ->map(function ($stat) {
                    return [
                        'role' => $stat->role,
                        'count' => $stat->count,
                        'percentage' => 0, // Will be calculated below
                    ];
                });

            $totalActiveUsers = $userStats->sum('count');

            // Calculate percentages
            $userStats = $userStats->map(function ($stat) use ($totalActiveUsers) {
                $stat['percentage'] = $totalActiveUsers > 0 ? round(($stat['count'] / $totalActiveUsers) * 100, 2) : 0;
                return $stat;
            });

            // Additional statistics
            $totalUsers = User::count();
            $inactiveUsers = User::where('status', '!=', 'active')->count();
            $recentUsers = User::where('created_at', '>=', now()->subDays(30))->count();

            $report = [
                'type' => 'user_stats',
                'total_users' => $totalUsers,
                'active_users' => $totalActiveUsers,
                'inactive_users' => $inactiveUsers,
                'recent_users' => $recentUsers,
                'role_distribution' => $userStats,
                'generated_at' => now()->toISOString(),
            ];

            return $this->success($report, 'User statistics report generated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to generate user statistics report: ' . $e->getMessage());
        }
    }
}
