<?php

namespace App\Http\Controllers;

use App\Jobs\ImportCsvJob;
use Illuminate\Support\Facades\Storage;

class CsvImportController extends Controller
{
    public function import()
    {
        if (app()->environment('testing')) {
            $file = Storage::disk('local')->path('sales.csv'); // fichier fake
        } else {
            $file = storage_path('app/sales.csv'); // fichier réel
        }
        if (! file_exists($file)) {
            return response()->json(['error' => "CSV file not found at $file"], 404);
        }

        if (filesize($file) === 0) {
            return response()->json(['error' => 'CSV file is empty'], 422);
        }

        ImportCsvJob::dispatch($file);

        return response()->json([
            'message' => 'Import CSV en cours… Le traitement se fait en arrière-plan.',
            'imported' => 0,
            'skipped' => 0,
        ]);
    }
}
