<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TopProductController extends Controller
{
    public function topProductsByRevenue(int $top = 10): JsonResponse
    {
        try {
            $products = Product::with('orderInfo')
                ->select('products.*')
                ->withSum('orderInfo as revenue', DB::raw('quantity * price'))
                ->orderByDesc('revenue')
                ->limit($top)
                ->get();

            if ($products->isEmpty()) {
                return response()->json([
                    'message' => 'No products found.',
                ], 404);
            }

            $formatted = $products->map(fn ($product) => [
                'sku' => $product->sku,
                'name' => $product->name,
                'revenue' => number_format($product->revenue, 2),
            ]);

            return response()->json([
                'data' => $formatted,
                'count' => $formatted->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to fetch top products: {$e->getMessage()}");

            return response()->json([
                'message' => 'An unexpected error occurred while fetching top products.',
            ], 500);
        }
    }
}
