<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Medicine;
use App\Models\Order;
use App\Models\PharmacyProfile;
use App\Models\PrescriptionUpload;
use App\Models\StockBatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard overview statistics
     */
    public function overview()
    {
        try {
            // 1. Total active users with breakdown by roles
            $totalActiveUsers = User::where('status', 'active')->count();
            $activeUsersByRole = User::where('status', 'active')
                ->selectRaw('role, COUNT(*) as count')
                ->groupBy('role')
                ->pluck('count', 'role')
                ->toArray();

            // 2. Active orders count (processing and completed)
            $activeOrdersCount = Order::whereIn('status', ['processing', 'completed'])->count();

            // 3. Medicine shortage percentage (medicines with total quantity < 10)
            $totalMedicines = Medicine::count();

            // Get medicines with total stock quantity less than 10
            $shortageMedicines = Medicine::withSum('stockBatches', 'quantity')
                ->having('stock_batches_sum_quantity', '<', 10)
                ->count();

            $medicineShortagePercentage = $totalMedicines > 0
                ? round(($shortageMedicines / $totalMedicines) * 100, 2)
                : 0;

            // 4. Total donations
            $totalDonations = Donation::count();

            // 5. Processed prescriptions (validated by doctor)
            $processedPrescriptions = PrescriptionUpload::where('validated_by_doctor', true)->count();

            // Additional statistics for better insights
            $additionalStats = [
                'total_orders' => Order::count(),
                'pending_orders' => Order::where('status', 'pending')->count(),
                'total_prescriptions' => PrescriptionUpload::count(),
                'pending_prescriptions' => PrescriptionUpload::where('validated_by_doctor', false)->count(),
                'verified_donations' => Donation::where('verified', true)->count(),
                'unverified_donations' => Donation::where('verified', false)->count(),
            ];

            $overview = [
                'total_active_users' => $totalActiveUsers,
                'active_users_by_role' => $activeUsersByRole,
                'active_orders_count' => $activeOrdersCount,
                'medicine_shortage_percentage' => $medicineShortagePercentage,
                'total_donations' => $totalDonations,
                'processed_prescriptions' => $processedPrescriptions,
                'additional_statistics' => $additionalStats,
                'last_updated' => now()->toISOString(),
            ];

            return $this->success($overview, 'Dashboard overview retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve dashboard overview: ' . $e->getMessage());
        }
    }

    /**
     * Get dashboard summary for quick stats
     */
    public function summary()
    {
        try {
            $summary = [
                'users' => [
                    'total' => User::count(),
                    'active' => User::where('status', 'active')->count(),
                    'doctors' => User::where('role', 'doctor')->where('status', 'active')->count(),
                    'pharmacies' => User::where('role', 'pharmacy')->where('status', 'active')->count(),
                    'patients' => User::where('role', 'patient')->where('status', 'active')->count(),
                ],
                'orders' => [
                    'total' => Order::count(),
                    'active' => Order::whereIn('status', ['processing', 'completed'])->count(),
                    'pending' => Order::where('status', 'pending')->count(),
                ],
                'medicines' => [
                    'total' => Medicine::count(),
                    'in_shortage' => Medicine::withSum('stockBatches', 'quantity')
                        ->having('stock_batches_sum_quantity', '<', 10)
                        ->count(),
                ],
                'donations' => [
                    'total' => Donation::count(),
                    'verified' => Donation::where('verified', true)->count(),
                ],
                'prescriptions' => [
                    'total' => PrescriptionUpload::count(),
                    'processed' => PrescriptionUpload::where('validated_by_doctor', true)->count(),
                ],
            ];

            return $this->success($summary, 'Dashboard summary retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve dashboard summary: ' . $e->getMessage());
        }
    }

    /**
     * Get medicine shortage data by pharmacy for bar chart
     */
    public function medicineShortageChart()
    {
        try {
            // Get pharmacies with their low-stock medicine counts
            $shortageData = PharmacyProfile::with(['user'])
                ->withCount([
                    'stockBatches as low_stock_count' => function ($query) {
                        $query->where('quantity', '<', 10);
                    }
                ])
                ->having('low_stock_count', '>', 0)
                ->orderBy('low_stock_count', 'desc')
                ->get()
                ->map(function ($pharmacy) {
                    return [
                        'pharmacy_name' => $pharmacy->name ?: $pharmacy->user->name ?: "Pharmacy #{$pharmacy->id}",
                        'low_stock_count' => $pharmacy->low_stock_count,
                        'pharmacy_id' => $pharmacy->id,
                    ];
                });

            $chartData = [
                'labels' => $shortageData->pluck('pharmacy_name')->toArray(),
                'datasets' => [
                    [
                        'label' => 'Low Stock Medicines',
                        'data' => $shortageData->pluck('low_stock_count')->toArray(),
                        'backgroundColor' => [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                        ],
                        'borderColor' => [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                        ],
                        'borderWidth' => 1
                    ]
                ]
            ];

            return $this->success($chartData, 'Medicine shortage chart data retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve medicine shortage chart data: ' . $e->getMessage());
        }
    }

    /**
     * Get daily orders data for line chart (last 7 days)
     */
    public function dailyOrdersChart()
    {
        try {
            $endDate = Carbon::now();
            $startDate = Carbon::now()->subDays(6);

            // Generate array of last 7 days
            $dates = [];
            $orderCounts = [];

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $dates[] = $date->format('M d');
                $orderCounts[] = Order::whereDate('created_at', $date->format('Y-m-d'))->count();
            }

            $chartData = [
                'labels' => $dates,
                'datasets' => [
                    [
                        'label' => 'Daily Orders',
                        'data' => $orderCounts,
                        'borderColor' => '#36A2EB',
                        'backgroundColor' => 'rgba(54, 162, 235, 0.1)',
                        'borderWidth' => 2,
                        'fill' => true,
                        'tension' => 0.4
                    ]
                ]
            ];

            return $this->success($chartData, 'Daily orders chart data retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve daily orders chart data: ' . $e->getMessage());
        }
    }

    /**
     * Get user role distribution data for pie chart
     */
    public function userRoleChart()
    {
        try {
            $roleData = User::where('status', 'active')
                ->selectRaw('role, COUNT(*) as count')
                ->groupBy('role')
                ->orderBy('count', 'desc')
                ->get();

            $chartData = [
                'labels' => $roleData->pluck('role')->map(function ($role) {
                    return ucfirst($role) . 's';
                })->toArray(),
                'datasets' => [
                    [
                        'data' => $roleData->pluck('count')->toArray(),
                        'backgroundColor' => [
                            '#FF6384', // Patients - Red
                            '#36A2EB', // Doctors - Blue
                            '#FFCE56', // Pharmacies - Yellow
                            '#4BC0C0', // Admin - Teal
                            '#9966FF', // Other roles - Purple
                        ],
                        'borderColor' => [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'
                        ],
                        'borderWidth' => 2
                    ]
                ]
            ];

            return $this->success($chartData, 'User role distribution chart data retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve user role chart data: ' . $e->getMessage());
        }
    }

    /**
     * Global search across users and medicines
     */
    public function globalSearch(Request $request)
    {
        try {
            $query = $request->input('q', '');
            $limit = $request->input('limit', 10);

            if (empty($query)) {
                return $this->success([
                    'users' => [],
                    'medicines' => [],
                    'total_results' => 0
                ], 'Search query is empty');
            }

            // Search active users
            $users = User::where('status', 'active')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('email', 'LIKE', "%{$query}%");
                })
                ->select('id', 'name', 'email', 'role', 'status')
                ->limit($limit)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'type' => 'user',
                        'display_text' => "{$user->name} ({$user->email}) - " . ucfirst($user->role)
                    ];
                });

            // Search medicines
            $medicines = Medicine::where(function ($q) use ($query) {
                    $q->where('brand_name', 'LIKE', "%{$query}%")
                      ->orWhere('manufacturer', 'LIKE', "%{$query}%")
                      ->orWhereHas('activeIngredient', function ($subQ) use ($query) {
                          $subQ->where('name', 'LIKE', "%{$query}%");
                      });
                })
                ->with(['activeIngredient:id,name'])
                ->select('id', 'brand_name', 'manufacturer', 'price', 'active_ingredient_id')
                ->limit($limit)
                ->get()
                ->map(function ($medicine) {
                    return [
                        'id' => $medicine->id,
                        'name' => $medicine->brand_name,
                        'manufacturer' => $medicine->manufacturer,
                        'price' => $medicine->price,
                        'active_ingredient' => $medicine->activeIngredient?->name,
                        'type' => 'medicine',
                        'display_text' => "{$medicine->brand_name} - {$medicine->manufacturer}"
                    ];
                });

            $totalResults = $users->count() + $medicines->count();

            return $this->success([
                'users' => $users,
                'medicines' => $medicines,
                'total_results' => $totalResults,
                'query' => $query,
                'search_metadata' => [
                    'users_found' => $users->count(),
                    'medicines_found' => $medicines->count(),
                    'limit_per_type' => $limit
                ]
            ], 'Global search completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to perform global search: ' . $e->getMessage());
        }
    }

    /**
     * Search users only
     */
    public function searchUsers(Request $request)
    {
        try {
            $query = $request->input('q', '');
            $limit = $request->input('limit', 10);

            if (empty($query)) {
                return $this->success([], 'Search query is empty');
            }

            $users = User::where('status', 'active')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('email', 'LIKE', "%{$query}%");
                })
                ->select('id', 'name', 'email', 'role', 'status', 'created_at')
                ->limit($limit)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'status' => $user->status,
                        'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                        'display_text' => "{$user->name} ({$user->email}) - " . ucfirst($user->role)
                    ];
                });

            return $this->success([
                'users' => $users,
                'total_found' => $users->count(),
                'query' => $query
            ], 'User search completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to search users: ' . $e->getMessage());
        }
    }

    /**
     * Search medicines only
     */
    public function searchMedicines(Request $request)
    {
        try {
            $query = $request->input('q', '');
            $limit = $request->input('limit', 10);

            if (empty($query)) {
                return $this->success([], 'Search query is empty');
            }

            $medicines = Medicine::where(function ($q) use ($query) {
                    $q->where('brand_name', 'LIKE', "%{$query}%")
                      ->orWhere('manufacturer', 'LIKE', "%{$query}%")
                      ->orWhereHas('activeIngredient', function ($subQ) use ($query) {
                          $subQ->where('name', 'LIKE', "%{$query}%");
                      });
                })
                ->with(['activeIngredient:id,name', 'stockBatches'])
                ->select('id', 'brand_name', 'manufacturer', 'price', 'form', 'dosage_strength', 'active_ingredient_id')
                ->limit($limit)
                ->get()
                ->map(function ($medicine) {
                    $totalQuantity = $medicine->stockBatches->sum('quantity');
                    return [
                        'id' => $medicine->id,
                        'name' => $medicine->brand_name,
                        'manufacturer' => $medicine->manufacturer,
                        'price' => $medicine->price,
                        'form' => $medicine->form,
                        'dosage_strength' => $medicine->dosage_strength,
                        'active_ingredient' => $medicine->activeIngredient?->name,
                        'total_quantity' => $totalQuantity,
                        'display_text' => "{$medicine->brand_name} - {$medicine->manufacturer}",
                        'is_low_stock' => $totalQuantity < 10
                    ];
                });

            return $this->success([
                'medicines' => $medicines,
                'total_found' => $medicines->count(),
                'query' => $query
            ], 'Medicine search completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to search medicines: ' . $e->getMessage());
        }
    }

    /**
     * Get all chart data in one endpoint
     */
    public function chartsData()
    {
        try {
            $charts = [
                'medicine_shortage' => $this->getMedicineShortageData(),
                'daily_orders' => $this->getDailyOrdersData(),
                'user_roles' => $this->getUserRoleData(),
            ];

            return $this->success($charts, 'All charts data retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve charts data: ' . $e->getMessage());
        }
    }

    /**
     * Helper method to get medicine shortage data
     */
    private function getMedicineShortageData()
    {
        $shortageData = PharmacyProfile::with(['user'])
            ->withCount([
                'stockBatches as low_stock_count' => function ($query) {
                    $query->where('quantity', '<', 10);
                }
            ])
            ->having('low_stock_count', '>', 0)
            ->orderBy('low_stock_count', 'desc')
            ->get()
            ->map(function ($pharmacy) {
                return [
                    'pharmacy_name' => $pharmacy->name ?: $pharmacy->user->name ?: "Pharmacy #{$pharmacy->id}",
                    'low_stock_count' => $pharmacy->low_stock_count,
                    'pharmacy_id' => $pharmacy->id,
                ];
            });

        return [
            'labels' => $shortageData->pluck('pharmacy_name')->toArray(),
            'datasets' => [
                [
                    'label' => 'Low Stock Medicines',
                    'data' => $shortageData->pluck('low_stock_count')->toArray(),
                    'backgroundColor' => [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                    ],
                    'borderColor' => [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                    ],
                    'borderWidth' => 1
                ]
            ]
        ];
    }

    /**
     * Helper method to get daily orders data
     */
    private function getDailyOrdersData()
    {
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(6);

        $dates = [];
        $orderCounts = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates[] = $date->format('M d');
            $orderCounts[] = Order::whereDate('created_at', $date->format('Y-m-d'))->count();
        }

        return [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Daily Orders',
                    'data' => $orderCounts,
                    'borderColor' => '#36A2EB',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4
                ]
            ]
        ];
    }

    /**
     * Helper method to get user role data
     */
    private function getUserRoleData()
    {
        $roleData = User::where('status', 'active')
            ->selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->orderBy('count', 'desc')
            ->get();

        return [
            'labels' => $roleData->pluck('role')->map(function ($role) {
                return ucfirst($role) . 's';
            })->toArray(),
            'datasets' => [
                [
                    'data' => $roleData->pluck('count')->toArray(),
                    'backgroundColor' => [
                        '#FF6384', // Patients - Red
                        '#36A2EB', // Doctors - Blue
                        '#FFCE56', // Pharmacies - Yellow
                        '#4BC0C0', // Admin - Teal
                        '#9966FF', // Other roles - Purple
                    ],
                    'borderColor' => [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'
                    ],
                    'borderWidth' => 2
                ]
            ]
        ];
    }
}
