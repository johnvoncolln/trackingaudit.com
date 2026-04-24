<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\DelegateTrackersJob;
use App\Models\User;
use App\Services\CarrierDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingApiController extends Controller
{
    public function store(Request $request, string $apiToken): JsonResponse
    {
        $user = User::where('api_token', $apiToken)->first();

        if (! $user) {
            return response()->json(['error' => 'Invalid API token.'], 404);
        }

        $validated = $request->validate([
            'tracking_numbers' => ['required', 'array', 'max:1500'],
            'tracking_numbers.*.tracking_number' => ['required', 'string', 'max:50'],
            'tracking_numbers.*.reference_id' => ['nullable', 'string', 'max:50'],
            'tracking_numbers.*.reference_name' => ['nullable', 'string', 'max:100'],
            'tracking_numbers.*.recipient_name' => ['nullable', 'string', 'max:200'],
            'tracking_numbers.*.recipient_email' => ['nullable', 'email', 'max:200'],
        ]);

        $records = collect($validated['tracking_numbers'])->map(function (array $record) {
            $record['carrier'] = CarrierDetector::detect($record['tracking_number'])->value;

            return $record;
        })->all();

        DelegateTrackersJob::dispatch($user, $records);

        $count = count($records);

        return response()->json([
            'message' => "Accepted {$count} tracking ".($count === 1 ? 'number' : 'numbers').' for processing.',
            'accepted' => $count,
        ]);
    }
}
