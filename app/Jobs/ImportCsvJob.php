<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $filePath;

    protected int $chunkSize;

    public function __construct(string $filePath, int $chunkSize = 1000)
    {
        $this->filePath = $filePath;
        $this->chunkSize = $chunkSize;
    }

    public function handle(): void
    {
        if (! $this->fileExists()) {
            return;
        }

        $this->processFile();
    }

    private function fileExists(): bool
    {
        if (! file_exists($this->filePath)) {
            info("[ImportCsvJob] CSV file not found at {$this->filePath}");

            return false;
        }
        info("[ImportCsvJob] CSV file found at {$this->filePath}");

        return true;
    }

    private function processFile(): void
    {
        $file = fopen($this->filePath, 'r');
        $header = fgetcsv($file);

        $chunk = [];
        $lineNumber = 1;

        while (($line = fgetcsv($file)) !== false) {
            $lineNumber++;
            $chunk[] = $line;

            if (count($chunk) >= $this->chunkSize) {
                $this->dispatchChunk($chunk, $header, $lineNumber);
                $chunk = [];
            }
        }

        if (! empty($chunk)) {
            $this->dispatchChunk($chunk, $header, $lineNumber);
        }

        fclose($file);
        info('[ImportCsvJob] CSV dispatched in chunks successfully.');
    }

    private function dispatchChunk(array $chunk, array $header, int $lineNumber): void
    {
        ImportCsvChunkJob::dispatch($chunk, $header);
        info("[ImportCsvJob] Dispatched chunk ending at line {$lineNumber} with ".count($chunk).' records');
    }
}
