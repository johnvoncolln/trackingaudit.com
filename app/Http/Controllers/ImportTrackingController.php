<?php

namespace App\Http\Controllers;

use App\Enums\Carrier;
use App\Jobs\DelegateTrackersJob;
use App\Services\CarrierDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use League\Csv\Reader;
use League\Csv\Writer;

class ImportTrackingController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'], // 2MB max
        ]);

        try {
            $csv = Reader::createFromPath($request->file('csv_file')->getPathname());
            $csv->setHeaderOffset(0);

            $records = iterator_to_array($csv->getRecords());

            if (count($records) > 1500) {
                return back()->withErrors(['csv_file' => 'CSV file contains more than 1500 records.']);
            }

            // Validate required columns
            $headers = $csv->getHeader();
            if (! in_array('tracking_number', $headers)) {
                return back()->withErrors(['csv_file' => 'CSV file must contain a tracking_number column.']);
            }

            $validRecords = 0;
            $failedRecords = 0;

            // Auto-detect carrier when not provided
            $processedRecords = [];

            foreach ($records as $record) {
                $validator = Validator::make($record, [
                    'tracking_number' => ['required', 'string', 'max:50'],
                    'carrier' => ['nullable', 'string', new Enum(Carrier::class)],
                    'reference_id' => ['nullable', 'string', 'max:50'],
                    'reference_name' => ['nullable', 'string', 'max:100'],
                    'reference_data' => ['nullable', 'string'],
                    'recipient_name' => ['nullable', 'string', 'max:100'],
                    'recipient_email' => ['nullable', 'email', 'max:100'],
                ]);

                if ($validator->fails()) {
                    $failedRecords++;

                    continue;
                }

                // Auto-detect carrier if not provided or empty
                if (empty($record['carrier'])) {
                    $record['carrier'] = CarrierDetector::detect($record['tracking_number'])->value;
                }

                $processedRecords[] = $record;
                $validRecords++;
            }

            if ($failedRecords > 0) {
                return redirect()->route('tracking.index')
                    ->with('flash.banner', "{$failedRecords} ".($failedRecords == 1 ? 'record' : 'records').' failed validation. Import has been aborted. Please check your records and try again.')
                    ->with('flash.bannerStyle', 'danger');
            }

            DelegateTrackersJob::dispatch($request->user(), $processedRecords);

            return redirect()->route('tracking.index')
                ->with('flash.banner', "Successfully imported {$validRecords} tracking numbers.")
                ->with('flash.bannerStyle', 'success');

        } catch (\Exception $e) {
            return back()->withErrors(['csv_file' => 'Error processing CSV file: '.$e->getMessage()]);
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'tracking_number',
            'carrier',
            'reference_id',
            'reference_name',
            'reference_data',
            'recipient_name',
            'recipient_email',
        ];

        $csv = Writer::createFromString('');
        $csv->insertOne($headers);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="tracking-import-template.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        return response((string) $csv, 200, $headers);
    }
}
