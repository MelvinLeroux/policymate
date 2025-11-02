<?php

namespace App\Http\Controllers;

use App\Jobs\ImportCsvJob;

class CsvImportController extends Controller
{
    public function import()
    {
        $file = storage_path('app/sales.csv');

        if (! file_exists($file)) {
            return response()->json(['error' => "CSV file not found at $file"], 404);
        }

        ImportCsvJob::dispatch($file);

        return response()->json([
            'message' => 'Import CSV en cours… Le traitement se fait en arrière-plan.',
        ]);
    }
}
