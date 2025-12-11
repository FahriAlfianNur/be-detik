<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ImportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Jobs\ProcessProductImport;
use Log;
use Storage;

class ProductController extends Controller
{
    public function import(Request $request)
    {
        Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'file' => [
                'required',
                'file',
                'mimetypes:text/csv,text/plain,application/vnd.ms-excel,application/csv',
                'max:2048',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'Validation error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $file = $request->file('file');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('imports', $filename);

            $importJob = new ImportJob();
            $importJob->filename = $filename;
            $importJob->status = 'pending';
            $importJob->save();

            ProcessProductImport::dispatch($importJob->id, $filePath);


            return response()->json([
                'job_id' => $importJob->id,
                'status' => 'pending',
                'message' => 'Import has been queued',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'Failed to Import',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function status($id)
    {
        $importJob = ImportJob::find($id);
        if (!$importJob) {
            return response()->json([
                'status' => 'error',
                'message' => 'Import job not found',
            ], 404);
        }

        return response()->json([
            'job_id' => $importJob->id,
            'status' => $importJob->status,
            'total' => $importJob->total,
            'success' => $importJob->success,
            'failed' => $importJob->failed,
            'created_at' => $importJob->created_at,
            'updated_at' => $importJob->updated_at,
        ]);
    }
}
