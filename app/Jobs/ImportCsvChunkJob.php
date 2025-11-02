<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderInfo;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportCsvChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $chunk;

    protected array $header;

    public function __construct(array $chunk, array $header)
    {
        $this->chunk = $chunk;
        $this->header = $header;
    }

    public function handle(): void
    {
        foreach ($this->chunk as $index => $line) {
            $data = array_combine($this->header, $line);

            if (! $this->validateLine($data, $index)) {
                continue;
            }

            $product = $this->getOrCreateProduct($data, $index);
            $order = $this->getOrCreateOrder($data, $index);

            $this->createOrderInfo($order, $product, $data, $index);
        }
    }

    protected function validateLine(array $data, int $index): bool
    {
        $errors = [];

        if (empty($data['order_id'])) {
            $errors[] = 'order_id missing';
        }

        if (empty($data['order_date'])) {
            $errors[] = 'order_date missing';
        } else {
            try {
                Carbon::parse($data['order_date']);
            } catch (\Exception $e) {
                $errors[] = 'invalid order_date';
            }
        }

        if (empty($data['customer_email'])) {
            $errors[] = 'customer_email missing';
        } elseif (! filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'invalid customer_email';
        }

        if (! isset($data['unit_price']) || ! is_numeric($data['unit_price']) || $data['unit_price'] <= 0) {
            $errors[] = 'invalid unit_price';
        }

        if (! isset($data['quantity']) || ! is_numeric($data['quantity']) || $data['quantity'] <= 0) {
            $errors[] = 'invalid quantity';
        }

        if (empty($data['product_sku'])) {
            $errors[] = 'product_sku missing';
        }

        if (empty($data['product_name'])) {
            $errors[] = 'product_name missing';
        }

        if (! empty($errors)) {
            Log::warning("Line {$index}: skipped due to errors: ".implode(', ', $errors));

            return false;
        }

        return true;
    }

    private function getOrCreateProduct(array $data, int $index): Product
    {
        return Product::firstOrCreate(
            ['sku' => $data['product_sku']],
            [
                'name' => $data['product_name'],
                'price' => (float) $data['unit_price'],
            ]
        );
    }

    private function getOrCreateOrder(array $data, int $index): Order
    {
        $orderDate = now();
        try {
            $orderDate = Carbon::parse($data['order_date']);
        } catch (\Exception $e) {
            Log::warning("Line {$index}: invalid date '{$data['order_date']}', using current date instead.");
        }

        return Order::firstOrCreate(
            ['order_id' => $data['order_id']],
            [
                'order_date' => $orderDate,
                'customer_email' => $data['customer_email'],
            ]
        );
    }

    private function createOrderInfo(Order $order, Product $product, array $data, int $index): void
    {
        OrderInfo::firstOrCreate(
            ['order_id' => $order->id, 'product_id' => $product->id],
            [
                'quantity' => (int) $data['quantity'],
                'price' => (float) $data['unit_price'],
            ]
        );
    }
}
