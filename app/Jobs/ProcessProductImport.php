<?php

namespace App\Jobs;

use App\Models\ImportJob;
use App\Models\Product;
use DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Log;
use Storage;

class ProcessProductImport implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 3;

    protected $importJobId;
    protected $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct($importJobId, $filePath)
    {
        $this->importJobId = $importJobId;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importJob = ImportJob::find($this->importJobId);

        if (!$importJob) {
            Log::error("Import job {$this->importJobId} not found");
            return;
        }

        try {
            $importJob->update([
                'status' => 'in_progress',
            ]);

            if (!Storage::exists($this->filePath)) {
                throw new \Exception('File not found: ' . $this->filePath);
            }

            $handle = Storage::readStream($this->filePath);
            if ($handle === false) {
                throw new \Exception('Failed to open file: ' . $this->filePath);
            }

            $header = fgetcsv($handle, 0, ';');
            $totalRows = 0;
            $successRows = 0;
            $failedRows = 0;
            $batchSize = 100;
            $batch = [];

            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $totalRows++;
                $data = [
                    'name' => $row[0] ?? null,
                    'sku' => $row[1] ?? null,
                    'price' => $row[2] ?? null,
                    'stock' => $row[3] ?? null,
                ];

                if (empty($data['name']) || empty($data['sku'])) {
                    $failedRows++;
                    Log::warning("Invalid data at row {$totalRows}: " . json_encode($data));
                    continue;
                }

                $batch[] = $data;

                if (count($batch) >= $batchSize) {
                    $result = $this->processBatch($batch);
                    $successRows += $result['success'];
                    $failedRows += $result['failed'];
                    $batch = [];
                }

                $importJob->update([
                    'total' => $totalRows,
                    'success' => $successRows,
                    'failed' => $failedRows,
                ]);
            }

            if (!empty($batch)) {
                $result = $this->processBatch($batch);
                $successRows += $result['success'];
                $failedRows += $result['failed'];
            }

            fclose($handle);

            $importJob->update([
                'status' => 'completed',
                'total' => $totalRows,
                'success' => $successRows,
                'failed' => $failedRows,
            ]);

        } catch (\Throwable $th) {
            Log::error("Failed to process import job {$this->importJobId}: " . $th->getMessage());
            $importJob->update([
                'status' => 'failed',
                'error_message' => $th->getMessage(),
            ]);
            throw $th;
        }
    }

    protected function processBatch($batch)
    {
        $success = 0;
        $failed = 0;

        DB::beginTransaction();
        try {
            foreach ($batch as $data) {
                Log::info('Processing Product Data: ' . json_encode($data));
                try {
                    Product::updateOrCreate(
                        ['sku' => $data['sku']],
                        [
                            'name' => $data['name'],
                            'sku' => $data['sku'],
                            'price' => $data['price'],
                            'stock' => $data['stock'],
                        ]
                    );
                    Log::info('Imported Product Data: ' . json_encode($data));
                    $success++;
                } catch (\Throwable $th) {
                    $failed++;
                    Log::error('Failed to Import Product Data: ' . $th->getMessage());
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            $failed += count($batch);
            Log::error('Batch Failed to Import Product Data: ' . $th->getMessage());
        }
        return [
            'success' => $success,
            'failed' => $failed,
        ];
    }
}
