<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MonthlyRevenueController extends Controller
{
    public function monthlyRevenue(int $year): JsonResponse
    {
        try {
            $monthlyRevenue = Order::join('order_infos', 'orders.id', '=', 'order_infos.order_id')
                ->whereYear('orders.order_date', $year) // <-- utilisez order_date ici
                ->selectRaw("strftime('%Y-%m', orders.order_date) as month, SUM(order_infos.quantity * order_infos.price) as revenue")
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            if ($monthlyRevenue->isEmpty()) {
                return response()->json([
                    'message' => "No orders found for year {$year}.",
                ], 404);
            }

            $formatted = $monthlyRevenue->map(fn ($item) => [
                'month' => $item->month,
                'revenue' => number_format($item->revenue, 2),
            ]);

            return response()->json([
                'data' => $formatted,
                'count' => $formatted->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to fetch monthly revenue for year {$year}: {$e->getMessage()}");

            return response()->json([
                'message' => 'An unexpected error occurred while calculating monthly revenue.',
            ], 500);
        }
    }
}
