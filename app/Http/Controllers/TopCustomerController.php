<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TopCustomerController extends Controller
{
    /**
     * Return top customers by revenue.
     */
    public function topCustomersByRevenue(int $top = 10): JsonResponse
    {
        try {
            $customers = Order::select('customer_email')
                ->join('order_infos', 'orders.id', '=', 'order_infos.order_id')
                ->selectRaw('customer_email, SUM(order_infos.quantity * order_infos.price) as revenue, COUNT(DISTINCT orders.id) as orders_count')
                ->groupBy('customer_email')
                ->orderByDesc('revenue')
                ->limit($top)
                ->get();

            if ($customers->isEmpty()) {
                return response()->json([
                    'message' => 'No orders found.',
                ], 404);
            }

            $formatted = $customers->map(fn ($customer) => [
                'customer_email' => $customer->customer_email,
                'revenue' => number_format($customer->revenue, 2),
                'orders_count' => $customer->orders_count,
            ]);

            return response()->json([
                'data' => $formatted,
                'count' => $formatted->count(),
            ]);

        } catch (\Exception $e) {
            Log::error("Top customers query failed: {$e->getMessage()}");

            return response()->json([
                'message' => 'An error occurred while fetching top customers.',
            ], 500);
        }
    }
}
