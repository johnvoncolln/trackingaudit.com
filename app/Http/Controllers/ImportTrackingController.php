<?php

namespace App\Http\Controllers;

use App\Enums\Carrier;
use App\Jobs\DelegateTrackersJob;
use App\Rules\FedexTrackingNumber;
use App\Rules\UpsTrackingNumber;
use App\Rules\UspsTrackingNumber;
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

            if (count($records) > 500) {
                return back()->withErrors(['csv_file' => 'CSV file contains more than 500 records.']);
            }

            // Validate required columns
            $headers = $csv->getHeader();
            if (! in_array('tracking_number', $headers)) {
                return back()->withErrors(['csv_file' => 'CSV file must contain a tracking_number column.']);
            }

            if (! in_array('carrier', $headers)) {
                return back()->withErrors(['csv_file' => 'CSV file must contain a carrier column.']);
            }

            $validRecords = 0;
            $failedRecords = 0;

            foreach ($records as $record) {
                $validator = Validator::make($record, [
                    'tracking_number' => ['required', 'string', 'max:50'],
                    'carrier' => ['required', 'string', new Enum(Carrier::class)],
                    'reference_id' => ['nullable', 'string', 'max:50'],
                    'reference_name' => ['nullable', 'string', 'max:100'],
                    'reference_data' => ['nullable', 'string'],
                    'recipient_name' => ['nullable', 'string', 'max:100'],
                    'recipient_email' => ['nullable', 'email', 'max:100'],
                ]);

                $validator->after(function ($validator) use ($record) {
                    $carrier = Carrier::tryFrom($record['carrier'] ?? '');

                    if ($carrier === null) {
                        return;
                    }

                    [$rule, $label] = match ($carrier) {
                        Carrier::UPS => [new UpsTrackingNumber, 'UPS'],
                        Carrier::USPS => [new UspsTrackingNumber, 'USPS'],
                        Carrier::FEDEX => [new FedexTrackingNumber, 'FedEx'],
                    };

                    $rule->validate('tracking_number', $record['tracking_number'] ?? '', function ($message) use ($validator, $label) {
                        $validator->errors()->add('tracking_number', "This is not a valid {$label} tracking number.");
                    });
                });

                if ($validator->fails()) {
                    $failedRecords++;

                    continue;
                }

                $validRecords++;
            }

            if ($failedRecords > 0) {
                return redirect()->route('tracking.index')
                    ->with('flash.banner', "{$failedRecords} ".($failedRecords == 1 ? 'record' : 'records').' failed validation. Import has been aborted. Please check your records and try again.')
                    ->with('flash.bannerStyle', 'danger');
            }

            DelegateTrackersJob::dispatch($request->user(), $records);

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
