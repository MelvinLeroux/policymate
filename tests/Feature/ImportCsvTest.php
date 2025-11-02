<?php

namespace Tests\Feature;

use App\Jobs\ImportCsvJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportCsvTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'sync']);

        Storage::fake('local');

        Log::spy();
    }

    public function test_invalid_rows_are_skipped_and_logged()
    {
        $csvContent = <<<'CSV'
order_id,order_date,customer_email,product_sku,product_name,unit_price,quantity
1,invalid-date,alice@example.com,SKU-1,Widget A,10.00,3
2,2025-08-01,bad-email,SKU-2,Widget B,20.00,2
3,2025-08-15,bob@example.com,SKU-3,Widget C,-5,1
4,2025-08-16,carol@example.com,SKU-4,Widget D,15.00,0
CSV;

        $filePath = Storage::disk('local')->path('sales.csv');
        file_put_contents($filePath, $csvContent);

        (new ImportCsvJob($filePath))->handle();

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_infos', 0);

        Log::shouldHaveReceived('warning')->times(4);
    }

    public function test_valid_rows_are_imported_correctly()
    {
        $csvContent = <<<'CSV'
order_id,order_date,customer_email,product_sku,product_name,unit_price,quantity
1,2025-08-01,alice@example.com,SKU-1,Widget A,10.00,3
2,2025-08-02,bob@example.com,SKU-2,Widget B,20.00,2
CSV;

        $filePath = Storage::disk('local')->path('sales.csv');
        file_put_contents($filePath, $csvContent);

        (new ImportCsvJob($filePath))->handle();

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('order_infos', 2);

        Log::shouldNotHaveReceived('warning');
    }

    public function test_import_fails_with_empty_file()
    {
        $filePath = Storage::disk('local')->path('sales.csv');
        file_put_contents($filePath, '');

        $response = $this->get('/import');

        $response->assertStatus(422);

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_infos', 0);
    }

    public function test_import_fails_if_file_missing()
    {
        $response = $this->get('/import');

        $response->assertStatus(404);

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_infos', 0);
    }
}
