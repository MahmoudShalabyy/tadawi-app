<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\StockBatch;
use App\Models\Order;
use App\Models\Donation;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ReportingController extends Controller
{
    use ApiResponse;

    public function getShortageReport(Request $request)
    {
        try {
            $threshold = $request->input('threshold', 10);
            $location = $request->input('location', null);

            $shortageQuery = Medicine::select([
                'medicines.id',
                'medicines.brand_name',
                'medicines.form',
                'medicines.dosage_strength',
                'medicines.manufacturer',
                'medicines.price',
                DB::raw('COALESCE(SUM(stock_batches.quantity), 0) as total_stock'),
                DB::raw('COUNT(DISTINCT stock_batches.pharmacy_id) as pharmacy_count')
            ])
            ->leftJoin('stock_batches', 'medicines.id', '=', 'stock_batches.medicine_id')
            ->groupBy([
                'medicines.id', 'medicines.brand_name', 'medicines.form',
                'medicines.dosage_strength', 'medicines.manufacturer', 'medicines.price'
            ])
            ->having('total_stock', '<=', $threshold);

            if ($location) {
                $shortageQuery->whereHas('stockBatches.pharmacy', function ($query) use ($location) {
                    $query->where('location', 'LIKE', "%{$location}%");
                });
            }

            $shortages = $shortageQuery->orderBy('total_stock', 'asc')->get();

            $stats = [
                'total_shortages' => $shortages->count(),
                'critical_shortages' => $shortages->where('total_stock', 0)->count(),
                'low_stock' => $shortages->where('total_stock', '>', 0)->where('total_stock', '<=', $threshold)->count(),
                'threshold_used' => $threshold,
                'location_filter' => $location,
            ];

            return $this->success([
                'shortages' => $shortages,
                'statistics' => $stats,
                'generated_at' => now()->toISOString(),
            ], 'Shortage report generated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to generate shortage report: ' . $e->getMessage());
        }
    }

    public function getShortageHeatmap(Request $request)
    {
        try {
            $threshold = $request->input('threshold', 10);
            $medicineId = $request->input('medicine_id', null);

            $heatmapQuery = DB::table('pharmacy_profiles')
                ->select([
                    'pharmacy_profiles.id',
                    'pharmacy_profiles.name',
                    'pharmacy_profiles.location',
                    'pharmacy_profiles.latitude',
                    'pharmacy_profiles.longitude',
                    DB::raw('COALESCE(SUM(stock_batches.quantity), 0) as total_stock'),
                    DB::raw('COUNT(DISTINCT stock_batches.medicine_id) as medicine_count')
                ])
                ->leftJoin('stock_batches', 'pharmacy_profiles.id', '=', 'stock_batches.pharmacy_id')
                ->leftJoin('medicines', 'stock_batches.medicine_id', '=', 'medicines.id')
                ->whereNotNull('pharmacy_profiles.latitude')
                ->whereNotNull('pharmacy_profiles.longitude')
                ->groupBy([
                    'pharmacy_profiles.id', 'pharmacy_profiles.name',
                    'pharmacy_profiles.location', 'pharmacy_profiles.latitude',
                    'pharmacy_profiles.longitude'
                ]);

            if ($medicineId) {
                $heatmapQuery->where('stock_batches.medicine_id', $medicineId);
            }

            $pharmacyData = $heatmapQuery->get();

            $heatmapData = $pharmacyData->map(function ($pharmacy) use ($threshold) {
                $shortageLevel = $this->calculateShortageLevel($pharmacy->total_stock, $threshold);

                return [
                    'id' => $pharmacy->id,
                    'name' => $pharmacy->name,
                    'location' => $pharmacy->location,
                    'coordinates' => [
                        'lat' => (float) $pharmacy->latitude,
                        'lng' => (float) $pharmacy->longitude,
                    ],
                    'total_stock' => $pharmacy->total_stock,
                    'medicine_count' => $pharmacy->medicine_count,
                    'shortage_level' => $shortageLevel,
                    'color' => $this->getShortageColor($shortageLevel),
                    'intensity' => $this->getShortageIntensity($shortageLevel),
                ];
            });

            $mapConfig = $this->getMapConfiguration();

            return $this->success([
                'heatmap_data' => $heatmapData,
                'map_config' => $mapConfig,
                'statistics' => [
                    'total_pharmacies' => $heatmapData->count(),
                    'critical_shortages' => $heatmapData->where('shortage_level', 'critical')->count(),
                    'moderate_shortages' => $heatmapData->where('shortage_level', 'moderate')->count(),
                    'low_shortages' => $heatmapData->where('shortage_level', 'low')->count(),
                    'no_shortages' => $heatmapData->where('shortage_level', 'none')->count(),
                ],
                'generated_at' => now()->toISOString(),
            ], 'Shortage heatmap data generated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to generate shortage heatmap: ' . $e->getMessage());
        }
    }

    public function getDashboardAnalytics(Request $request)
    {
        try {
            $period = $request->input('period', '30');

            $stats = [
                'total_medicines' => Medicine::count(),
                'total_orders' => Order::count(),
                'total_donations' => Donation::count(),
                'total_stock_quantity' => StockBatch::sum('quantity'),
                'verified_donations' => Donation::where('verified', true)->count(),
                'pending_donations' => Donation::where('verified', false)->count(),
            ];

            $recentActivity = [
                'orders_last_30_days' => Order::where('created_at', '>=', now()->subDays(30))->count(),
                'donations_last_30_days' => Donation::where('created_at', '>=', now()->subDays(30))->count(),
                'new_medicines_last_30_days' => Medicine::where('created_at', '>=', now()->subDays(30))->count(),
            ];

            $shortageAlerts = Medicine::select([
                'medicines.id',
                'medicines.brand_name',
                'medicines.form',
                'medicines.dosage_strength',
                DB::raw('COALESCE(SUM(stock_batches.quantity), 0) as total_stock')
            ])
            ->leftJoin('stock_batches', 'medicines.id', '=', 'stock_batches.medicine_id')
            ->groupBy(['medicines.id', 'medicines.brand_name', 'medicines.form', 'medicines.dosage_strength'])
            ->having('total_stock', '<=', 5)
            ->orderBy('total_stock', 'asc')
            ->limit(10)
            ->get();

            return $this->success([
                'statistics' => $stats,
                'recent_activity' => $recentActivity,
                'shortage_alerts' => $shortageAlerts,
                'generated_at' => now()->toISOString(),
            ], 'Dashboard analytics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get dashboard analytics: ' . $e->getMessage());
        }
    }

    private function calculateShortageLevel(int $stock, int $threshold): string
    {
        if ($stock === 0) {
            return 'critical';
        } elseif ($stock <= $threshold * 0.3) {
            return 'high';
        } elseif ($stock <= $threshold * 0.6) {
            return 'moderate';
        } elseif ($stock <= $threshold) {
            return 'low';
        } else {
            return 'none';
        }
    }

    private function getShortageColor(string $level): string
    {
        return match ($level) {
            'critical' => '#FF0000',
            'high' => '#FF6600',
            'moderate' => '#FFCC00',
            'low' => '#99CC00',
            'none' => '#00CC00',
            default => '#CCCCCC',
        };
    }

    private function getShortageIntensity(string $level): float
    {
        return match ($level) {
            'critical' => 1.0,
            'high' => 0.8,
            'moderate' => 0.6,
            'low' => 0.4,
            'none' => 0.1,
            default => 0.0,
        };
    }

    private function getMapConfiguration(): array
    {
        return [
            'center' => [
                'lat' => 30.0444,
                'lng' => 31.2357,
            ],
            'zoom' => 10,
            'map_type' => 'roadmap',
            'heatmap_options' => [
                'radius' => 20,
                'opacity' => 0.6,
                'gradient' => [
                    'rgba(0, 255, 255, 0)',
                    'rgba(0, 255, 255, 1)',
                    'rgba(0, 191, 255, 1)',
                    'rgba(0, 127, 255, 1)',
                    'rgba(0, 63, 255, 1)',
                    'rgba(0, 0, 255, 1)',
                    'rgba(0, 0, 223, 1)',
                    'rgba(0, 0, 191, 1)',
                    'rgba(0, 0, 159, 1)',
                    'rgba(0, 0, 127, 1)',
                    'rgba(63, 0, 91, 1)',
                    'rgba(127, 0, 63, 1)',
                    'rgba(191, 0, 31, 1)',
                    'rgba(255, 0, 0, 1)',
                ],
            ],
        ];
    }
}
